<?php
/**
 * Error types. The set of error CODES (and their HTTP status / retryability / family) comes from
 * the generated contract (resources/contract.json -> errors); the classes below are keyed by the
 * contract's error *family*, so a code added to the contract is represented without an SDK
 * release. Nothing here invents error codes.
 */

namespace Davix\H2I\Exceptions;

class DavixH2IException extends \RuntimeException
{
    /** @var string|null Davix H2I error code from the API (error.code), e.g. "invalid_parameter" */
    public $errorCode;
    /** @var int|null HTTP status (null for client-side / network errors) */
    public $status;
    /** @var string|null error.hint */
    public $hint;
    /** @var array|null error.details (field, allowed, value, limit, ...) */
    public $details;
    /** @var string|null Request-Id header / request_id body field */
    public $requestId;
    /** @var string|null error.renewal_url (key_expired) */
    public $renewalUrl;
    /** @var bool from the contract's error catalog */
    public $retryable;
    /** @var int|null */
    public $retryAfterMs;
    /** @var mixed the parsed error response body */
    public $body;

    /**
     * @param string $message
     * @param array  $info keys: code, status, hint, details, requestId, renewalUrl, retryable, retryAfterMs, body, previous
     */
    public function __construct($message = '', array $info = [])
    {
        parent::__construct($message, 0, isset($info['previous']) ? $info['previous'] : null);
        $this->errorCode = isset($info['code']) ? $info['code'] : null;
        $this->status = isset($info['status']) ? $info['status'] : null;
        $this->hint = isset($info['hint']) ? $info['hint'] : null;
        $this->details = isset($info['details']) ? $info['details'] : null;
        $this->requestId = isset($info['requestId']) ? $info['requestId'] : null;
        $this->renewalUrl = isset($info['renewalUrl']) ? $info['renewalUrl'] : null;
        $this->retryable = !empty($info['retryable']);
        $this->retryAfterMs = isset($info['retryAfterMs']) ? $info['retryAfterMs'] : null;
        $this->body = isset($info['body']) ? $info['body'] : null;
    }
}

class ApiException extends DavixH2IException {}
class ValidationException extends ApiException {}     // family: request
class UploadException extends ApiException {}         // family: upload
class AuthenticationException extends ApiException {} // family: auth
class PlanException extends ApiException {}           // family: plan
class QuotaException extends ApiException {}          // family: quota
class RateLimitException extends QuotaException {}    // HTTP 429
class ProcessingException extends ApiException {}     // family: processing
class ServerException extends ApiException {}         // family: server, or any 5xx
class NotFoundException extends ApiException {}       // HTTP 404
class ConflictException extends ApiException {}       // HTTP 409
class AiException extends ApiException {}             // family: ai
class KeyException extends ApiException {}            // family: keys
class JobException extends ApiException {}            // family: jobs
class SignedUrlException extends ApiException {}      // family: signed-url
class NetworkException extends DavixH2IException {}     // connection failed / DNS / TLS
class TimeoutException extends NetworkException {}     // client-side timeout
class ConfigException extends DavixH2IException {}      // SDK misuse (bad option, forbidden path, invalid parameter)

final class ErrorClasses
{
    /** @var array<string,string> */
    private static $byFamily = [
        'request' => ValidationException::class, 'upload' => UploadException::class, 'auth' => AuthenticationException::class,
        'plan' => PlanException::class, 'quota' => QuotaException::class, 'processing' => ProcessingException::class,
        'server' => ServerException::class, 'ai' => AiException::class, 'keys' => KeyException::class,
        'jobs' => JobException::class, 'signed-url' => SignedUrlException::class,
    ];

    /** Picks the most specific class for an API error response. */
    public static function classFor($status, $family)
    {
        if ($status === 429) {
            return RateLimitException::class;
        }
        if ($status === 404 && !isset(self::$byFamily[$family])) {
            return NotFoundException::class;
        }
        if ($status === 409) {
            return ConflictException::class;
        }
        if ($family !== null && isset(self::$byFamily[$family])) {
            return self::$byFamily[$family];
        }
        if ($status >= 500) {
            return ServerException::class;
        }
        if ($status === 401 || $status === 403) {
            return AuthenticationException::class;
        }
        if ($status === 404) {
            return NotFoundException::class;
        }
        return ApiException::class;
    }
}
