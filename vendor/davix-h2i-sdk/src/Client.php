<?php
namespace Davix\H2I;

use Davix\H2I\Exceptions\ConfigException;
use Davix\H2I\Exceptions\ErrorClasses;
use Davix\H2I\Exceptions\NetworkException;
use Davix\H2I\Exceptions\DavixH2IException;
use Davix\H2I\Http\CurlTransport;
use Davix\H2I\Http\HttpResponse;
use Davix\H2I\Http\Multipart;
use Davix\H2I\Http\Paths;
use Davix\H2I\Http\TransportInterface;
use Davix\H2I\Schema\EndpointSchema;

/** The result of a successful call. */
final class Response
{
    /** @var int */
    public $status;
    /** @var array<string,string> lower-cased header names */
    public $headers;
    /** @var mixed parsed JSON (array), text (string) or raw bytes (string) */
    public $data;
    /** @var string|null the Davix H2I Request-Id (header, else body.request_id) */
    public $requestId;
    /** @var string|null the Idempotency-Key this call was sent with */
    public $idempotencyKey;
    /** @var int number of HTTP attempts (1 = no retry) */
    public $attempts;

    public function __construct($status, array $headers, $data, $requestId, $idempotencyKey, $attempts)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->data = $data;
        $this->requestId = $requestId;
        $this->idempotencyKey = $idempotencyKey;
        $this->attempts = $attempts;
    }

    /** Every output object with a "url" (signed download URLs) found in the response body. @return array[] */
    public function files()
    {
        $out = [];
        $walk = function ($v) use (&$walk, &$out) {
            if (is_array($v)) {
                if (isset($v['url']) && is_string($v['url'])) {
                    $out[] = $v;
                }
                foreach ($v as $x) {
                    $walk($x);
                }
            }
        };
        $walk($this->data);
        return $out;
    }

    /** True when the server accepted an async job (HTTP 202). */
    public function accepted()
    {
        return $this->status === 202;
    }

    /** The job id of an async (202) response, else null. */
    public function jobId()
    {
        return is_array($this->data) && isset($this->data['job_id']) ? $this->data['job_id'] : null;
    }
}

/** STATIC / RUNTIME / ACCOUNT capabilities, kept strictly separate. */
final class Capabilities
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /** RUNTIME capabilities of this deployment (GET /v1/capabilities); pass a group name ("images") to narrow. */
    public function runtime($category = null)
    {
        $res = $this->client->call('capabilities');
        if ($category === null) {
            return $res;
        }
        if (!is_array($res->data) || !array_key_exists($category, $res->data)) {
            throw new ConfigException('No runtime capability group "' . $category . '". Available: ' . implode(', ', is_array($res->data) ? array_keys($res->data) : []) . '.');
        }
        return new Response($res->status, $res->headers, $res->data[$category], $res->requestId, $res->idempotencyKey, $res->attempts);
    }

    /** RUNTIME: one section ("pdfs") of the capabilities document (one small GET). Returns the body. */
    public function section($name)
    {
        return $this->client->call('capabilities.{section}', ['section' => $name])->data;
    }

    /** RUNTIME: one feature ("pdfs", "ocr"). Returns the body. */
    public function feature($section, $feature)
    {
        return $this->client->call('capabilities.{section}.{feature}', ['section' => $section, 'feature' => $feature])->data;
    }

    /** STATIC capabilities: what the contract guarantees (bundled, no request). */
    public function static_($category = null)
    {
        $cap = $this->client->contract()['capabilities'];
        if ($category === null) {
            return $cap;
        }
        if (!isset($cap['categories'][$category])) {
            throw new ConfigException('Unknown category "' . $category . '". Available: ' . implode(', ', array_keys($cap['categories'])) . '.');
        }
        return $cap['categories'][$category];
    }

    /** ACCOUNT capabilities: subscription, usage and account of the calling (individual customer) key. */
    public function account()
    {
        $s = $this->client->call('subscription');
        $u = $this->client->call('usage');
        $a = $this->client->call('account');
        return ['subscription' => $s->data, 'usage' => $u->data, 'account' => $a->data, 'requestIds' => [$s->requestId, $u->requestId, $a->requestId]];
    }

    /** Alias of static_() (PHP reserves the word "static" for some contexts). */
    public function __call($name, $args)
    {
        if ($name === 'static') {
            return $this->static_(isset($args[0]) ? $args[0] : null);
        }
        throw new \BadMethodCallException('Unknown method ' . $name);
    }
}

class Client
{
    use Generated\ClientNamespaces;

    const SDK_VERSION = '1.3.0';
    const DEFAULT_BASE_URL = 'https://app.h2i.davixlabs.io';
    const SUPPORTED_SCHEMA_MAJOR = 1;

    /** @var string */
    private $apiKey;
    /** @var array */
    private $base;
    /** @var string */
    public $baseUrl;
    /** @var TransportInterface */
    private $transport;
    /** @var int */
    public $timeoutMs;
    /** @var int */
    public $maxRetries;
    /** @var int */
    public $maxRetryDelayMs;
    /** @var string */
    public $authScheme;
    /** @var bool */
    public $autoIdempotencyKey;
    /** @var bool */
    public $validate;
    /** @var string */
    public $userAgent;
    /** @var array|null */
    private $bundled;
    /** @var array|null */
    private $live = null;
    /** @var array<string,EndpointSchema> */
    private $schemaCache = [];
    /** @var array<string,array>|null */
    private $errorIndex = null;
    /** @var callable|null sleep(ms) override for tests */
    private $sleeper;

    /**
     * @param array $options apiKey (required), baseUrl, timeoutMs, maxRetries, maxRetryDelayMs, authScheme ('x-api-key'|'bearer'),
     *                       autoIdempotencyKey (default true), validate (default false), allowInsecure, transport, userAgentSuffix, sleeper
     */
    public function __construct(array $options = [])
    {
        $apiKey = isset($options['apiKey']) ? $options['apiKey'] : null;
        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new ConfigException('Davix H2I: the "apiKey" option is required.');
        }
        if (preg_match('/[\r\n]/', $apiKey)) {
            throw new ConfigException('Davix H2I: the "apiKey" option contains invalid characters.');
        }
        $base = parse_url(isset($options['baseUrl']) ? $options['baseUrl'] : self::DEFAULT_BASE_URL);
        if (!$base || empty($base['scheme']) || empty($base['host'])) {
            throw new ConfigException('Davix H2I: "baseUrl" is not a valid URL.');
        }
        $local = in_array($base['host'], ['localhost', '127.0.0.1', '::1', '[::1]'], true) || substr($base['host'], -10) === '.localhost';
        if ($base['scheme'] !== 'https' && !($base['scheme'] === 'http' && ($local || !empty($options['allowInsecure'])))) {
            throw new ConfigException('Davix H2I: baseUrl must use https:// (http:// is only allowed for localhost, or with allowInsecure).');
        }
        if (isset($base['user']) || isset($base['pass'])) {
            throw new ConfigException('Davix H2I: credentials in baseUrl are not allowed.');
        }
        $this->apiKey = trim($apiKey);
        $this->base = $base;
        $this->baseUrl = $base['scheme'] . '://' . $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '') . rtrim(isset($base['path']) ? $base['path'] : '', '/');
        $this->timeoutMs = isset($options['timeoutMs']) ? (int) $options['timeoutMs'] : 60000;
        $this->maxRetries = isset($options['maxRetries']) ? (int) $options['maxRetries'] : 2;
        $this->maxRetryDelayMs = isset($options['maxRetryDelayMs']) ? (int) $options['maxRetryDelayMs'] : 30000;
        $this->authScheme = isset($options['authScheme']) ? $options['authScheme'] : 'x-api-key';
        if (!in_array($this->authScheme, ['x-api-key', 'bearer'], true)) {
            throw new ConfigException('Davix H2I: authScheme must be "x-api-key" or "bearer".');
        }
        $this->autoIdempotencyKey = !isset($options['autoIdempotencyKey']) || $options['autoIdempotencyKey'] !== false;
        $this->validate = !empty($options['validate']);
        $this->userAgent = 'davix-h2i-php/' . self::SDK_VERSION . (isset($options['userAgentSuffix']) ? ' ' . $options['userAgentSuffix'] : '');
        $this->transport = isset($options['transport']) ? $options['transport'] : new CurlTransport();
        $this->sleeper = isset($options['sleeper']) ? $options['sleeper'] : null;
    }

    /** var_dump()/print_r() must not reveal the API key. */
    public function __debugInfo()
    {
        return ['baseUrl' => $this->baseUrl, 'apiKey' => '[redacted]'];
    }

    /** The client holds a credential; it must not be serialized into caches or sessions. */
    public function __serialize()
    {
        throw new ConfigException('A Davix\\H2I\\Client holds an API key and cannot be serialized.');
    }

    // ----------------------------------------------------------------- metadata
    /** The bundled contract (what this SDK was generated from), or the live one after refreshContract(). */
    public function contract()
    {
        if ($this->live !== null) {
            return $this->live;
        }
        if ($this->bundled === null) {
            $path = dirname(__DIR__) . '/resources/contract.json';
            $json = @file_get_contents($path);
            $this->bundled = $json === false ? null : json_decode($json, true);
            if (!is_array($this->bundled)) {
                throw new ConfigException('The bundled contract (resources/contract.json) is missing or unreadable.');
            }
        }
        return $this->bundled;
    }

    public function contractVersion()
    {
        $c = $this->contract();
        return $c['contractVersion'];
    }

    public function bundledContract()
    {
        $live = $this->live;
        $this->live = null;
        try {
            return $this->contract();
        } finally {
            $this->live = $live;
        }
    }

    /** Fetches the live contract (GET /v1/contract) without using it. */
    public function fetchContract()
    {
        return $this->send('GET', '/v1/contract', ['attach' => false, 'options' => []])->data;
    }

    /** Fetches the live contract and uses it for schema() / capabilities()->static_() from now on. */
    public function refreshContract()
    {
        $c = $this->fetchContract();
        if (!is_array($c) || !isset($c['endpoints']) || !is_array($c['endpoints'])) {
            throw new ConfigException('The live contract is malformed.');
        }
        if ((int) floor($c['metadataSchemaVersion']) !== self::SUPPORTED_SCHEMA_MAJOR) {
            throw new ConfigException('The live contract uses metadata schema ' . $c['metadataSchemaVersion'] . ', which this SDK version (' . self::SDK_VERSION . ') does not understand. Upgrade the SDK.');
        }
        $this->live = $c;
        $this->schemaCache = [];
        $this->errorIndex = null;
        return $c;
    }

    public function resetContract()
    {
        $this->live = null;
        $this->schemaCache = [];
        $this->errorIndex = null;
    }

    /** Metadata of one endpoint by id ("images.resize"): field types, enums, minimum/maximum, defaults, actions. */
    public function schema($id)
    {
        if (!isset($this->schemaCache[$id])) {
            // a contract id ("jobs.{id}", "images.resize") or the SDK name ("jobs.get" = namespace.method)
            $found = null;
            foreach ($this->contract()['endpoints'] as $e) {
                if ($e['id'] === $id) {
                    $found = $e;
                    break;
                }
                if ($found === null && implode('.', $e['sdk']['namespace']) . '.' . $e['sdk']['method'] === $id) {
                    $found = $e;
                }
            }
            if ($found === null) {
                throw new ConfigException('Unknown endpoint "' . $id . '". See schemas().');
            }
            $this->schemaCache[$id] = new EndpointSchema($found);
        }
        return $this->schemaCache[$id];
    }

    public function schemaForRoute($method, $pathTemplate)
    {
        foreach ($this->contract()['endpoints'] as $e) {
            if ($e['method'] === strtoupper($method) && $e['path'] === $pathTemplate) {
                return $this->schema($e['id']);
            }
        }
        return null;
    }

    /** @return EndpointSchema[] optionally filtered: ['category' => 'Images', 'audience' => 'public'] */
    public function schemas(array $filter = [])
    {
        $out = [];
        foreach ($this->contract()['endpoints'] as $e) {
            if ((!isset($filter['category']) || $e['category'] === $filter['category']) && (!isset($filter['audience']) || $e['audience'] === $filter['audience'])) {
                $out[] = $this->schema($e['id']);
            }
        }
        return $out;
    }

    // ----------------------------------------------------------- LIVE contract slices
    // One small cacheable GET each (ETag + gzip/brotli on the server). They return the response body.
    private function slice($id, array $params = [])
    {
        return $this->call($id, $params)->data;
    }

    public function contractEndpoints() { return $this->slice('contract.endpoints'); }
    public function contractEndpoint($id) { return $this->slice('contract.endpoints.{id}', ['id' => $id]); }
    public function contractFields($id) { return $this->slice('contract.endpoints.{id}.fields', ['id' => $id]); }
    public function contractField($id, $name) { return $this->slice('contract.endpoints.{id}.fields.{name}', ['id' => $id, 'name' => $name]); }
    public function contractActions($id) { return $this->slice('contract.endpoints.{id}.actions', ['id' => $id]); }
    public function contractResponse($id) { return $this->slice('contract.endpoints.{id}.response', ['id' => $id]); }
    public function contractErrorsOf($id) { return $this->slice('contract.endpoints.{id}.errors', ['id' => $id]); }
    public function contractExamples($id) { return $this->slice('contract.endpoints.{id}.examples', ['id' => $id]); }
    public function contractCategories() { return $this->slice('contract.categories'); }
    public function contractCategory($name) { return $this->slice('contract.categories.{name}', ['name' => $name]); }
    public function contractEnums() { return $this->slice('contract.enums'); }
    public function contractEnum($name) { return $this->slice('contract.enums.{name}', ['name' => $name]); }
    public function contractErrorCodes() { return $this->slice('contract.errors'); }
    public function contractError($code) { return $this->slice('contract.errors.{code}', ['code' => $code]); }
    public function contractSchemas() { return $this->slice('contract.schemas'); }
    public function contractSchema($name) { return $this->slice('contract.schemas.{name}', ['name' => $name]); }
    public function contractAuth() { return $this->slice('contract.auth'); }

    public function capabilities()
    {
        return new Capabilities($this);
    }

    // -------------------------------------------------------------- typed calls
    /**
     * Calls an endpoint by contract id. Parameters are placed by the contract: path/query/header
     * parameters, then a JSON or multipart body. Every generated method ($h2i->images()->resize($p))
     * is a shortcut for this.
     *
     * @param string $id
     * @param array  $params
     * @param array  $options idempotencyKey, headers, timeoutMs, maxRetries, retry(false), validate, strict
     * @return Response
     */
    public function call($id, array $params = [], array $options = [])
    {
        $schema = $this->schema($id);
        $endpoint = $schema->raw;
        $validate = array_key_exists('validate', $options) ? (bool) $options['validate'] : $this->validate;
        if ($validate) {
            $v = $schema->validate($params);
            if (!$v['valid']) {
                $msgs = array_map(function ($e) {
                    return $e['message'];
                }, $v['errors']);
                throw new ConfigException($id . ': invalid parameters: ' . implode(' ', $msgs), ['code' => 'sdk_validation_failed', 'details' => ['errors' => $v['errors']]]);
            }
        }
        $pathValues = [];
        $query = [];
        $headers = [];
        $body = [];
        $known = [];
        foreach ($endpoint['request']['fields'] as $f) {
            $known[$f['name']] = true;
            if ($f['location'] === 'header' && !empty($f['standard'])) {
                continue; // Idempotency-Key: use the idempotencyKey option
            }
            $v = array_key_exists($f['name'], $params) ? $params[$f['name']] : null;
            if ($f['location'] === 'path') {
                $pathValues[$f['name']] = $v;
            } elseif ($v === null) {
                continue;
            } elseif ($f['location'] === 'query') {
                $query[$f['name']] = $v;
            } elseif ($f['location'] === 'header') {
                $headers[$f['name']] = (string) $v;
            } else {
                $body[$f['name']] = $v;
            }
        }
        if (!empty($options['strict'])) {
            $unknown = array_diff(array_keys($params), array_keys($known));
            if ($unknown) {
                throw new ConfigException($id . ': unknown parameter(s): ' . implode(', ', $unknown) . '.');
            }
        }
        foreach ($params as $k => $v) {
            if (!isset($known[$k]) && $v !== null) {
                $body[$k] = $v; // a newer server parameter the bundled contract does not list
            }
        }
        $urlPath = Paths::expand($endpoint['path'], $pathValues);
        $ct = $endpoint['request']['contentType'];
        $payload = null;
        $contentType = null;
        if ($ct === 'multipart/form-data') {
            $formFields = array_values(array_filter($endpoint['request']['fields'], function ($f) {
                return $f['location'] === 'form';
            }));
            list($contentType, $payload) = Multipart::build($formFields, $body);
        } elseif ($body) {
            $payload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $contentType = 'application/json';
        }
        $eligible = !empty($endpoint['idempotency']['supported']) && $endpoint['method'] === 'POST';
        return $this->send($endpoint['method'], $urlPath, [
            'query' => $query, 'headers' => $headers, 'payload' => $payload, 'contentType' => $contentType,
            'eligibleForIdempotency' => $eligible, 'options' => $options,
        ]);
    }

    // ------------------------------------------------------------ generic request
    /**
     * Generic escape hatch for endpoints this SDK version does not know yet:
     *   $h2i->request('POST', '/v1/new-feature', ['json' => [...]]);
     *   $h2i->request('POST', '/v1/new-upload', ['form' => ['image' => ['path' => 'a.png'], 'mode' => 'x']]);
     * Reuses authentication, retries, idempotency, serialization and error handling, and can ONLY reach the
     * public API (/v1/...): internal, admin and absolute URLs are refused.
     */
    public function request($method, $urlPath, array $options = [])
    {
        $m = strtoupper($method);
        if (!in_array($m, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new ConfigException('request(): unsupported method "' . $method . '".');
        }
        $policy = $this->contract()['requestPolicy'];
        Paths::assertPublic($urlPath, $policy);
        $endpoint = null;
        foreach ($this->contract()['endpoints'] as $e) {
            if ($e['method'] === $m && Paths::matches($e['path'], $urlPath)) {
                $endpoint = $e;
                break;
            }
        }
        if (array_key_exists('json', $options) && array_key_exists('form', $options)) {
            throw new ConfigException('request(): pass either "json" or "form", not both.');
        }
        $payload = null;
        $contentType = null;
        if (array_key_exists('json', $options)) {
            $payload = json_encode($options['json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $contentType = 'application/json';
        } elseif (array_key_exists('form', $options)) {
            $fields = $endpoint ? array_values(array_filter($endpoint['request']['fields'], function ($f) {
                return $f['location'] === 'form';
            })) : null;
            list($contentType, $payload) = Multipart::build($fields, $options['form']);
        }
        $eligible = $m === 'POST' && $endpoint && !empty($endpoint['idempotency']['supported']);
        return $this->send($m, $urlPath, [
            'query' => isset($options['query']) ? $options['query'] : [], 'headers' => isset($options['headers']) ? $options['headers'] : [],
            'payload' => $payload, 'contentType' => $contentType, 'eligibleForIdempotency' => $eligible, 'options' => $options,
        ]);
    }

    // ---------------------------------------------------------------- downloads
    /**
     * Downloads a signed output URL returned by Davix H2I (a "url" string or a result array with "url").
     * Only URLs on the configured Davix H2I host are fetched, and the API key is NEVER sent: signed URLs are
     * self-authenticating (the signing secret stays on the server).
     *
     * @return array ['body' => string, 'contentType' => ?string, 'size' => int]  (or ['path' => ..., ...] with $options['dest'])
     */
    public function download($urlOrResult, array $options = [])
    {
        $raw = is_array($urlOrResult) ? (isset($urlOrResult['url']) ? $urlOrResult['url'] : null) : $urlOrResult;
        if (!is_string($raw)) {
            throw new ConfigException('download(): pass a URL string or an array with a "url" key.');
        }
        $u = parse_url($raw);
        $abs = isset($u['host']) ? $raw : $this->baseUrl . (strpos($raw, '/') === 0 ? '' : '/') . $raw;
        $u = parse_url($abs);
        $origin = function ($p) {
            return strtolower($p['scheme']) . '://' . strtolower($p['host']) . (isset($p['port']) ? ':' . $p['port'] : '');
        };
        $myOrigin = strtolower($this->base['scheme']) . '://' . strtolower($this->base['host']) . (isset($this->base['port']) ? ':' . $this->base['port'] : '');
        if (!$u || $origin($u) !== $myOrigin) {
            throw new ConfigException('download(): refusing to fetch a URL that is not on the configured Davix H2I host.');
        }
        if (!preg_match('#^/(render|image|pdf|ai|data|documents)/[^/\\\\]+$#', isset($u['path']) ? $u['path'] : '')) {
            throw new ConfigException('download(): not a Davix H2I output URL.');
        }
        $res = $this->transport->send('GET', $abs, ['user-agent' => $this->userAgent], null, isset($options['timeoutMs']) ? (int) $options['timeoutMs'] : $this->timeoutMs);
        if ($res->status < 200 || $res->status >= 300) {
            $body = json_decode($res->body, true);
            throw $this->apiError($res->status, is_array($body) ? $body : null, isset($res->headers['request-id']) ? $res->headers['request-id'] : null, $res->headers);
        }
        $ct = isset($res->headers['content-type']) ? $res->headers['content-type'] : null;
        if (!empty($options['dest'])) {
            $dir = dirname($options['dest']);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($options['dest'], $res->body);
            return ['path' => $options['dest'], 'contentType' => $ct, 'size' => strlen($res->body)];
        }
        return ['body' => $res->body, 'contentType' => $ct, 'size' => strlen($res->body)];
    }

    // --------------------------------------------------------------------- core
    private function errorMeta($code)
    {
        if ($this->errorIndex === null) {
            $this->errorIndex = [];
            foreach ($this->contract()['errors'] as $e) {
                $this->errorIndex[$e['code']] = $e;
            }
        }
        return isset($this->errorIndex[$code]) ? $this->errorIndex[$code] : null;
    }

    private function apiError($status, $body, $requestId, array $headers)
    {
        $err = is_array($body) && isset($body['error']) && is_array($body['error']) ? $body['error'] : [];
        $code = isset($err['code']) ? $err['code'] : (is_array($body) && isset($body['code']) ? $body['code'] : null);
        $meta = $code ? $this->errorMeta($code) : null;
        $retryAfterMs = null;
        if (isset($headers['retry-after']) && is_numeric($headers['retry-after'])) {
            $retryAfterMs = (int) ((float) $headers['retry-after'] * 1000);
        } elseif (isset($err['details']['retry_after_seconds']) && is_numeric($err['details']['retry_after_seconds'])) {
            $retryAfterMs = (int) ((float) $err['details']['retry_after_seconds'] * 1000);
        }
        $cls = ErrorClasses::classFor($status, $meta ? $meta['family'] : null);
        $msg = isset($err['message']) ? $err['message'] : (is_array($body) && isset($body['message']) ? $body['message'] : 'Davix H2I API error (HTTP ' . $status . ')');
        return new $cls($msg, [
            'code' => $code, 'status' => $status, 'hint' => isset($err['hint']) ? $err['hint'] : null, 'details' => isset($err['details']) ? $err['details'] : null,
            'requestId' => $requestId ?: (is_array($body) && isset($body['request_id']) ? $body['request_id'] : null),
            'renewalUrl' => isset($err['renewal_url']) ? $err['renewal_url'] : null,
            'retryable' => $meta ? (bool) $meta['retryable'] : ($status === 503 || $status === 504), 'retryAfterMs' => $retryAfterMs, 'body' => $body,
        ]);
    }

    private function shouldRetry($method, $idempotencyKey, $failure)
    {
        $safe = in_array($method, ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'], true) || $idempotencyKey !== null;
        if (!empty($failure['network'])) {
            return $safe; // connection-level failure: only when repeating cannot duplicate work
        }
        $status = $failure['status'];
        $e = $failure['error'];
        if ($status === 429) {
            return $e->retryable; // e.g. rate_limit_exceeded; never monthly_quota_exceeded
        }
        if ($status === 503 || $status === 504) {
            return $e->retryable && $safe;
        }
        return false; // 4xx and 500 are not retried
    }

    private static function uuid4()
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $h = bin2hex($b);
        return substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4) . '-' . substr($h, 16, 4) . '-' . substr($h, 20, 12);
    }

    private function backoff($attempt, $retryAfterMs)
    {
        $base = min(8000, 500 * (2 ** ($attempt - 1)));
        $delay = mt_rand(0, $base);
        if ($retryAfterMs !== null) {
            $delay = max($delay, $retryAfterMs);
        }
        $delay = min($delay, $this->maxRetryDelayMs);
        if ($this->sleeper) {
            call_user_func($this->sleeper, $delay);
        } else {
            usleep($delay * 1000);
        }
    }

    private function send($method, $urlPath, array $ctx)
    {
        $options = isset($ctx['options']) ? $ctx['options'] : [];
        $attach = !isset($ctx['attach']) || $ctx['attach'] !== false;
        $url = $this->baseUrl . $urlPath;
        $query = isset($ctx['query']) ? $ctx['query'] : [];
        if ($query) {
            $parts = [];
            foreach ($query as $k => $v) {
                foreach ((is_array($v) ? $v : [$v]) as $x) {
                    $parts[] = rawurlencode($k) . '=' . rawurlencode(is_bool($x) ? ($x ? 'true' : 'false') : (string) $x);
                }
            }
            $url .= '?' . implode('&', $parts);
        }
        $headers = ['accept' => 'application/json', 'user-agent' => $this->userAgent];
        if ($attach) {
            if ($this->authScheme === 'bearer') {
                $headers['authorization'] = 'Bearer ' . $this->apiKey;
            } else {
                $headers['x-api-key'] = $this->apiKey;
            }
        }
        $extra = array_merge(isset($ctx['headers']) ? $ctx['headers'] : [], isset($options['headers']) ? $options['headers'] : []);
        foreach ($extra as $k => $v) {
            $lk = strtolower($k);
            if (in_array($lk, ['x-api-key', 'authorization', 'host'], true)) {
                throw new ConfigException('Header "' . $k . '" is managed by the SDK and cannot be overridden.');
            }
            $headers[$lk] = (string) $v;
        }
        if (!empty($ctx['contentType'])) {
            $headers['content-type'] = $ctx['contentType'];
        }
        $retries = (array_key_exists('retry', $options) && $options['retry'] === false) ? 0 : (isset($options['maxRetries']) ? (int) $options['maxRetries'] : $this->maxRetries);
        $idem = isset($options['idempotencyKey']) ? $options['idempotencyKey'] : null;
        if ($idem !== null) {
            if (!preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $idem)) {
                throw new ConfigException('idempotencyKey must be 8-128 characters from [A-Za-z0-9._:-].');
            }
        } elseif ($this->autoIdempotencyKey && !empty($ctx['eligibleForIdempotency']) && $retries > 0) {
            $idem = 'h2i-' . self::uuid4(); // one key per logical call, reused for every retry of it
        }
        if ($idem !== null) {
            $headers['idempotency-key'] = $idem;
        }
        $timeout = isset($options['timeoutMs']) ? (int) $options['timeoutMs'] : $this->timeoutMs;
        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $res = $this->transport->send($method, $url, $headers, isset($ctx['payload']) ? $ctx['payload'] : null, $timeout);
            } catch (NetworkException $e) {
                if ($attempt <= $retries && $this->shouldRetry($method, $idem, ['network' => true])) {
                    $this->backoff($attempt, null);
                    continue;
                }
                throw $e;
            }
            $ct = isset($res->headers['content-type']) ? $res->headers['content-type'] : '';
            $data = $res->body;
            if (stripos($ct, 'json') !== false) {
                $decoded = json_decode($res->body, true);
                $data = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            }
            $requestIdHeader = isset($res->headers['request-id']) ? $res->headers['request-id'] : null;
            if ($res->status >= 200 && $res->status < 300) {
                $requestId = $requestIdHeader ?: (is_array($data) && isset($data['request_id']) ? $data['request_id'] : null);
                return new Response($res->status, $res->headers, $data, $requestId, $idem, $attempt);
            }
            $error = $this->apiError($res->status, is_array($data) ? $data : null, $requestIdHeader, $res->headers);
            if ($attempt <= $retries && $this->shouldRetry($method, $idem, ['status' => $res->status, 'error' => $error])) {
                $this->backoff($attempt, $error->retryAfterMs);
                continue;
            }
            throw $error;
        }
    }
}
