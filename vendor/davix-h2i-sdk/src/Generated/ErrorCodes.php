<?php
// GENERATED from the Davix H2I API contract 3.0.0. Do not edit.
// Every error code the API can return (error.code), for comparisons such as $e->errorCode === ErrorCodes::RATE_LIMIT_EXCEEDED.
namespace DavixH2I;

final class ErrorCodes
{
    const AI_CONFIGURATION_ERROR = 'ai_configuration_error';
    const AI_FEATURE_UNAVAILABLE = 'ai_feature_unavailable';
    const AI_GENERATION_FAILED = 'ai_generation_failed';
    const AI_INPUT_INVALID = 'ai_input_invalid';
    const AI_INPUT_TOO_LARGE = 'ai_input_too_large';
    const AI_MODEL_INCOMPATIBLE = 'ai_model_incompatible';
    const AI_MODEL_NOT_FOUND = 'ai_model_not_found';
    const AI_OUTPUT_INVALID = 'ai_output_invalid';
    const AI_PARAMETER_UNSUPPORTED = 'ai_parameter_unsupported';
    const AI_PROCESSING_FAILED = 'ai_processing_failed';
    const AI_PROVIDER_UNAVAILABLE = 'ai_provider_unavailable';
    const AI_QUOTA_EXCEEDED = 'ai_quota_exceeded';
    const AI_RATE_LIMITED = 'ai_rate_limited';
    const AI_SECURITY_REJECTED = 'ai_security_rejected';
    const AI_TIMEOUT = 'ai_timeout';
    const API_KEY_LOCATION_NOT_ALLOWED = 'api_key_location_not_allowed';
    const ASYNC_LIMIT_REACHED = 'async_limit_reached';
    const ASYNC_NOT_ALLOWED = 'async_not_allowed';
    const ASYNC_REQUIRES_CUSTOMER_KEY = 'async_requires_customer_key';
    const CERTIFICATE_LIMIT_REACHED = 'certificate_limit_reached';
    const CERTIFICATES_UNAVAILABLE = 'certificates_unavailable';
    const DATA_CONVERSION_FAILED = 'data_conversion_failed';
    const DEMO_BUSY = 'demo_busy';
    const DEMO_FORBIDDEN = 'demo_forbidden';
    const DEMO_LIMIT_REACHED = 'demo_limit_reached';
    const DEMO_NOT_AVAILABLE = 'demo_not_available';
    const DEMO_UNAVAILABLE = 'demo_unavailable';
    const DEPENDENCY_UNAVAILABLE = 'dependency_unavailable';
    const DIMENSION_EXCEEDED = 'dimension_exceeded';
    const DUPLICATE_CERTIFICATE_NAME = 'duplicate_certificate_name';
    const DUPLICATE_PRESET_NAME = 'duplicate_preset_name';
    const DUPLICATE_TEMPLATE_NAME = 'duplicate_template_name';
    const ENDPOINT_API_DISABLED = 'endpoint_api_disabled';
    const ENDPOINT_DISABLED = 'endpoint_disabled';
    const ENDPOINT_NOT_ALLOWED = 'endpoint_not_allowed';
    const EXPIRED = 'expired';
    const FILE_TOO_LARGE = 'file_too_large';
    const FONT_UNAVAILABLE = 'font_unavailable';
    const FORBIDDEN = 'forbidden';
    const FORMAT_NOT_ALLOWED = 'format_not_allowed';
    const HTML_RENDER_FAILED = 'html_render_failed';
    const HTML_TOO_LARGE = 'html_too_large';
    const IDEMPOTENCY_KEY_CONFLICT = 'idempotency_key_conflict';
    const IMAGE_PROCESSING_FAILED = 'image_processing_failed';
    const INTERNAL_ERROR = 'internal_error';
    const INVALID_API_KEY = 'invalid_api_key';
    const INVALID_IDEMPOTENCY_KEY = 'invalid_idempotency_key';
    const INVALID_PARAMETER = 'invalid_parameter';
    const INVALID_SIGNATURE = 'invalid_signature';
    const INVALID_UPLOAD = 'invalid_upload';
    const JOB_NOT_CANCELLABLE = 'job_not_cancellable';
    const JOB_NOT_FOUND = 'job_not_found';
    const KEY_EXPIRED = 'key_expired';
    const KEY_LIMIT_REACHED = 'key_limit_reached';
    const KEY_NOT_FOUND = 'key_not_found';
    const KEY_QUOTA_EXCEEDED = 'key_quota_exceeded';
    const MISSING_FIELD = 'missing_field';
    const MONTHLY_QUOTA_EXCEEDED = 'monthly_quota_exceeded';
    const NOT_FOUND = 'not_found';
    const PDF_PAGE_LIMIT_EXCEEDED = 'pdf_page_limit_exceeded';
    const PDF_TOOL_FAILED = 'pdf_tool_failed';
    const PLAN_QUOTA_NOT_CONFIGURED = 'plan_quota_not_configured';
    const PLAN_RESOLUTION_FAILED = 'plan_resolution_failed';
    const RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    const RATE_LIMIT_STORE_UNAVAILABLE = 'rate_limit_store_unavailable';
    const RATE_LIMIT_UNAVAILABLE = 'rate_limit_unavailable';
    const RENDER_SIZE_EXCEEDED = 'render_size_exceeded';
    const REVOKE_FAILED = 'revoke_failed';
    const ROTATE_FAILED = 'rotate_failed';
    const SERVER_BUSY = 'server_busy';
    const TIMEOUT = 'timeout';
    const TOO_MANY_FILES = 'too_many_files';
    const TOTAL_UPLOAD_EXCEEDED = 'total_upload_exceeded';
    const UNAUTHORIZED = 'unauthorized';
    const UNSUPPORTED_MEDIA_TYPE = 'unsupported_media_type';
    const URL_BLOCKED = 'url_blocked';
    const URL_FETCH_FAILED = 'url_fetch_failed';

    /** @return string[] */
    public static function all()
    {
        return ['ai_configuration_error', 'ai_feature_unavailable', 'ai_generation_failed', 'ai_input_invalid', 'ai_input_too_large', 'ai_model_incompatible', 'ai_model_not_found', 'ai_output_invalid', 'ai_parameter_unsupported', 'ai_processing_failed', 'ai_provider_unavailable', 'ai_quota_exceeded', 'ai_rate_limited', 'ai_security_rejected', 'ai_timeout', 'api_key_location_not_allowed', 'async_limit_reached', 'async_not_allowed', 'async_requires_customer_key', 'certificate_limit_reached', 'certificates_unavailable', 'data_conversion_failed', 'demo_busy', 'demo_forbidden', 'demo_limit_reached', 'demo_not_available', 'demo_unavailable', 'dependency_unavailable', 'dimension_exceeded', 'duplicate_certificate_name', 'duplicate_preset_name', 'duplicate_template_name', 'endpoint_api_disabled', 'endpoint_disabled', 'endpoint_not_allowed', 'expired', 'file_too_large', 'font_unavailable', 'forbidden', 'format_not_allowed', 'html_render_failed', 'html_too_large', 'idempotency_key_conflict', 'image_processing_failed', 'internal_error', 'invalid_api_key', 'invalid_idempotency_key', 'invalid_parameter', 'invalid_signature', 'invalid_upload', 'job_not_cancellable', 'job_not_found', 'key_expired', 'key_limit_reached', 'key_not_found', 'key_quota_exceeded', 'missing_field', 'monthly_quota_exceeded', 'not_found', 'pdf_page_limit_exceeded', 'pdf_tool_failed', 'plan_quota_not_configured', 'plan_resolution_failed', 'rate_limit_exceeded', 'rate_limit_store_unavailable', 'rate_limit_unavailable', 'render_size_exceeded', 'revoke_failed', 'rotate_failed', 'server_busy', 'timeout', 'too_many_files', 'total_upload_exceeded', 'unauthorized', 'unsupported_media_type', 'url_blocked', 'url_fetch_failed'];
    }
}
