<?php
namespace Davix\H2I\Http;

use Davix\H2I\Exceptions\ConfigException;
use Davix\H2I\Exceptions\NetworkException;
use Davix\H2I\Exceptions\TimeoutException;

/** The HTTP response handed from a transport to the client. */
final class HttpResponse
{
    /** @var int */
    public $status;
    /** @var array<string,string> lower-cased header names */
    public $headers;
    /** @var string raw body */
    public $body;

    public function __construct($status, array $headers, $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }
}

/** Pluggable transport (the default is cURL; tests inject their own). */
interface TransportInterface
{
    /**
     * @param string       $method
     * @param string       $url
     * @param array        $headers  name => value
     * @param string|array|null $body  a string, or an array for multipart (scalars and \CURLFile values)
     * @param int          $timeoutMs 0 = no timeout
     * @return HttpResponse
     * @throws NetworkException|TimeoutException
     */
    public function send($method, $url, array $headers, $body, $timeoutMs);
}

final class CurlTransport implements TransportInterface
{
    public function __construct()
    {
        if (!function_exists('curl_init')) {
            throw new ConfigException('The PHP curl extension is required.');
        }
    }

    public function send($method, $url, array $headers, $body, $timeoutMs)
    {
        $ch = curl_init();
        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }
        $respHeaders = [];
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs > 0 ? min($timeoutMs, 15000) : 15000,
            CURLOPT_HEADERFUNCTION => function ($c, $line) use (&$respHeaders) {
                $len = strlen($line);
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            },
        ];
        if ($timeoutMs > 0) {
            $opts[CURLOPT_TIMEOUT_MS] = $timeoutMs;
        }
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $errno = curl_errno($ch);
            $msg = curl_error($ch);
            curl_close($ch);
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                throw new TimeoutException('Request timed out after ' . $timeoutMs . ' ms.');
            }
            throw new NetworkException('Network error: ' . $msg);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return new HttpResponse($status, $respHeaders, (string) $raw);
    }
}

/**
 * The client-side boundary that keeps the generic request() from ever reaching internal or admin
 * routes: only paths inside the public API namespace (/v1/...) and the public OpenAPI document are
 * allowed. The policy (pattern, blocked prefixes) is part of the contract; the structural checks
 * below do not depend on it and cannot be disabled.
 */
final class Paths
{
    public static function assertPublic($path, $policy)
    {
        if (!is_string($path) || $path === '') {
            throw new ConfigException('request(): path must be a non-empty string.');
        }
        if (preg_match('/[\x00-\x1f\x7f\s\\\\]/', $path)) {
            throw new ConfigException('request(): path contains forbidden characters.');
        }
        if ($path[0] !== '/' || strpos($path, '//') === 0) {
            throw new ConfigException('request(): path must be a site-relative path starting with a single "/"; absolute URLs are not allowed.');
        }
        if (strpos($path, '://') !== false || preg_match('#^/[a-z][a-z0-9+.-]*:#i', $path)) {
            throw new ConfigException('request(): absolute URLs are not allowed.');
        }
        if (strpos($path, '?') !== false || strpos($path, '#') !== false) {
            throw new ConfigException('request(): put the query string in the "query" option, not in the path.');
        }
        $decoded = rawurldecode($path);
        $decoded2 = rawurldecode($decoded);
        $blocked = isset($policy['blockedPrefixes']) ? $policy['blockedPrefixes'] : [];
        foreach ([$path, $decoded, $decoded2] as $candidate) {
            if (preg_match('#(^|/)\.\.?(/|$)#', $candidate)) {
                throw new ConfigException('request(): path traversal is not allowed.');
            }
            if (strpos(substr($candidate, 1), '//') !== false) {
                throw new ConfigException('request(): empty path segments are not allowed.');
            }
            $lower = strtolower($candidate);
            foreach ($blocked as $prefix) {
                if ($lower === $prefix || strpos($lower, $prefix . '/') === 0) {
                    throw new ConfigException('request(): ' . $prefix . ' is not part of the public API and cannot be called through this SDK.');
                }
            }
        }
        $extra = isset($policy['extraAllowedPaths']) ? $policy['extraAllowedPaths'] : [];
        if (in_array($path, $extra, true)) {
            return $path;
        }
        if (strpos($path, '/v1/') !== 0 && strpos($path, '/openapi/') !== 0) {
            throw new ConfigException('request(): only public API paths under /v1/ (and the /openapi/ documents) are allowed.');
        }
        return $path;
    }

    /** Expands {name} placeholders with percent-encoded values ("/" and "." can never inject path structure). */
    public static function expand($template, array $values)
    {
        return preg_replace_callback('/\{([A-Za-z0-9_]+)\}/', function ($m) use ($values) {
            $v = isset($values[$m[1]]) ? $values[$m[1]] : null;
            if ($v === null || $v === '') {
                throw new ConfigException('Missing required path parameter "' . $m[1] . '".');
            }
            return str_replace('.', '%2E', rawurlencode((string) $v));
        }, $template);
    }

    /** "/v1/jobs/{id}" matches "/v1/jobs/12". */
    public static function matches($template, $concrete)
    {
        $re = '#^' . preg_replace('/\\\\\{[A-Za-z0-9_]+\\\\\}/', '[^/]+', preg_quote($template, '#')) . '$#';
        return (bool) preg_match($re, $concrete);
    }
}

/**
 * Builds a multipart/form-data body by hand (Davix H2I expects REPEATED parts with the same field name,
 * e.g. several "images" parts, which cURL's array-based POSTFIELDS cannot express).
 * Values: scalars as text, objects/arrays as a JSON string (contract encoding "json-string"), files as
 * file parts. A plain string is read from disk ONLY for a contract field of type "file"; for fields the
 * contract does not know, only explicit file objects (["path"=>...], ["data"=>...], a stream, a
 * \CURLFile or \SplFileInfo) count as files, so an ordinary text value can never make the SDK read a
 * local file.
 */
final class Multipart
{
    private static $types = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
        'avif' => 'image/avif', 'svg' => 'image/svg+xml', 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'pdf' => 'application/pdf',
        'json' => 'application/json', 'csv' => 'text/csv', 'xml' => 'application/xml', 'txt' => 'text/plain', 'md' => 'text/markdown',
        'html' => 'text/html', 'rtf' => 'application/rtf', 'epub' => 'application/epub+zip',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
    ];

    public static function contentType($filename)
    {
        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        return isset(self::$types[$ext]) ? self::$types[$ext] : 'application/octet-stream';
    }

    private static function isList(array $a)
    {
        return $a === [] || array_keys($a) === range(0, count($a) - 1);
    }

    public static function isFileLike($v)
    {
        if (is_resource($v) || $v instanceof \CURLFile || $v instanceof \SplFileInfo) {
            return true;
        }
        return is_array($v) && !self::isList($v) && (array_key_exists('path', $v) || array_key_exists('data', $v));
    }

    private static function allFileLike(array $list)
    {
        if ($list === [] || !self::isList($list)) {
            return false;
        }
        foreach ($list as $v) {
            if (!self::isFileLike($v)) {
                return false;
            }
        }
        return true;
    }

    /** @return array{0:string,1:string,2:string} [filename, contentType, data] */
    private static function toFile($input, $fallbackName)
    {
        if ($input instanceof \SplFileInfo) {
            $input = $input->getPathname();
        }
        if ($input instanceof \CURLFile) {
            $input = ['path' => $input->getFilename(), 'filename' => $input->getPostFilename(), 'contentType' => $input->getMimeType()];
        }
        if (is_string($input)) {
            if (!is_file($input) || !is_readable($input)) {
                throw new ConfigException('Cannot read file "' . $input . '".');
            }
            return [basename($input), self::contentType($input), (string) file_get_contents($input)];
        }
        if (is_resource($input)) {
            $meta = stream_get_meta_data($input);
            $name = isset($meta['uri']) ? basename($meta['uri']) : $fallbackName;
            return [$name, self::contentType($name), (string) stream_get_contents($input)];
        }
        if (is_array($input) && isset($input['path']) && !array_key_exists('data', $input)) {
            $f = self::toFile($input['path'], $fallbackName);
            return [isset($input['filename']) && $input['filename'] !== '' ? $input['filename'] : $f[0], isset($input['contentType']) && $input['contentType'] ? $input['contentType'] : $f[1], $f[2]];
        }
        if (is_array($input) && array_key_exists('data', $input)) {
            $data = $input['data'];
            if (is_resource($data)) {
                $data = stream_get_contents($data);
            }
            $name = isset($input['filename']) && $input['filename'] !== '' ? $input['filename'] : $fallbackName;
            return [$name, isset($input['contentType']) && $input['contentType'] ? $input['contentType'] : self::contentType($name), (string) $data];
        }
        throw new ConfigException('Unsupported file value: use a path, stream, \\CURLFile or ["data" => ..., "filename" => ...].');
    }

    private static function quote($s)
    {
        return str_replace(['"', "\r", "\n"], ['%22', '', ''], (string) $s);
    }

    /**
     * @param array|null $contractFields the endpoint's form fields (list of contract field arrays) or null
     * @param array      $params
     * @return array{0:string,1:string} [content-type header value, body]
     */
    public static function build($contractFields, array $params)
    {
        $known = [];
        foreach ($contractFields ?: [] as $f) {
            $known[$f['name']] = $f;
        }
        $boundary = '----DavixH2ISdk' . bin2hex(random_bytes(12));
        $body = '';
        $text = function ($name, $value) use (&$body, $boundary) {
            $body .= '--' . $boundary . "\r\n" . 'Content-Disposition: form-data; name="' . self::quote($name) . "\"\r\n\r\n" . $value . "\r\n";
        };
        $filePart = function ($name, $filename, $ctype, $data) use (&$body, $boundary) {
            $body .= '--' . $boundary . "\r\n" . 'Content-Disposition: form-data; name="' . self::quote($name) . '"; filename="' . self::quote($filename) . "\"\r\n" . 'Content-Type: ' . $ctype . "\r\n\r\n" . $data . "\r\n";
        };
        foreach ($params as $name => $value) {
            if ($value === null) {
                continue;
            }
            $name = (string) $name;
            if (isset($known[$name])) {
                $isFile = $known[$name]['type'] === 'file';
            } else {
                $isFile = (is_object($value) || is_resource($value) || (is_array($value) && !self::isList($value))) ? self::isFileLike($value) : (is_array($value) && self::allFileLike($value));
            }
            if ($isFile) {
                $list = (is_array($value) && self::isList($value)) ? $value : [$value];
                $i = 0;
                foreach ($list as $item) {
                    $i++;
                    $f = self::toFile($item, count($list) > 1 ? $name . '-' . $i : $name);
                    $filePart($name, $f[0], $f[1], $f[2]);
                }
            } elseif (is_bool($value)) {
                $text($name, $value ? 'true' : 'false');
            } elseif (is_array($value) || is_object($value)) {
                $text($name, json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } else {
                $text($name, (string) $value);
            }
        }
        $body .= '--' . $boundary . "--\r\n";
        return ['multipart/form-data; boundary=' . $boundary, $body];
    }
}
