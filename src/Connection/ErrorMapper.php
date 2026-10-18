<?php
/**
 * Turns anything the SDK throws into one plain, safe structure the UI, the job queue and the History can use.
 */

namespace DavixWP\Connection;

use Davix\H2I\Exceptions\ConfigException;
use Davix\H2I\Exceptions\DavixH2IException;
use Davix\H2I\Exceptions\NetworkException;
use Davix\H2I\Exceptions\TimeoutException;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ErrorMapper {

    /**
     * @param \Throwable $e
     * @return array{code:string,kind:string,message:string,hint:string,request_id:string,status:int,retryable:bool,retry_after:int,fatal:bool}
     */
    public static function map( $e ) {
        $out = array(
            'code'        => 'unknown_error',
            'kind'        => 'other',
            'message'     => __( 'Something went wrong. Please try again.', 'davix-h2i' ),
            'hint'        => '',
            'request_id'  => '',
            'status'      => 0,
            'retryable'   => false,
            'retry_after' => 0,
            'fatal'       => false,
        );

        if ( $e instanceof TimeoutException ) {
            $out['code']      = 'timeout';
            $out['kind']      = 'network';
            $out['message']   = __( 'The request took too long. Try again, or raise the timeout in Settings.', 'davix-h2i' );
            $out['retryable'] = true;
            return $out;
        }
        if ( $e instanceof NetworkException ) {
            $out['code']      = 'network_error';
            $out['kind']      = 'network';
            $out['message']   = __( 'Could not reach Davix H2I. Check this site\'s internet connection and the server address.', 'davix-h2i' );
            $out['retryable'] = true;
            return $out;
        }
        if ( $e instanceof ConfigException && ! $e instanceof DavixH2IException ) {
            $out['message'] = $e->getMessage();
            return $out;
        }
        if ( ! $e instanceof DavixH2IException ) {
            $out['message'] = $e->getMessage() ? $e->getMessage() : $out['message'];
            return $out;
        }

        $code               = (string) $e->errorCode;
        $out['code']        = '' !== $code ? $code : ( $e instanceof ConfigException ? 'invalid_request' : 'api_error' );
        $out['status']      = (int) $e->status;
        $out['request_id']  = (string) $e->requestId;
        $out['hint']        = (string) $e->hint;
        $out['retryable']   = (bool) $e->retryable;
        $out['retry_after'] = $e->retryAfterMs ? (int) ceil( $e->retryAfterMs / 1000 ) : 0;
        $out['message']     = self::message_for( $out['code'], $e );

        switch ( $out['code'] ) {
            case 'invalid_api_key':
            case 'key_expired':
            case 'api_key_location_not_allowed':
                $out['kind']  = 'auth';
                $out['fatal'] = true;
                break;
            case 'monthly_quota_exceeded':
            case 'ai_quota_exceeded':
                $out['kind']  = 'quota';
                $out['fatal'] = true;
                break;
            case 'endpoint_not_allowed':
            case 'format_not_allowed':
            case 'async_not_allowed':
                $out['kind'] = 'plan';
                break;
            case 'rate_limit_exceeded':
            case 'rate_limit_unavailable':
            case 'ai_rate_limited':
            case 'async_limit_reached':
                $out['kind']      = 'rate';
                $out['retryable'] = true;
                break;
            case 'ai_feature_unavailable':
            case 'feature_unavailable':
                $out['kind'] = 'unavailable';
                break;
            default:
                if ( $e->status >= 500 ) {
                    $out['kind'] = 'server';
                } elseif ( $e->status >= 400 ) {
                    $out['kind'] = 'invalid';
                }
        }
        return $out;
    }

    private static function message_for( $code, DavixH2IException $e ) {
        $field = '';
        if ( is_array( $e->details ) && ! empty( $e->details['field'] ) ) {
            $field = (string) $e->details['field'];
        }
        switch ( $code ) {
            case 'invalid_api_key':
                return __( 'The API key was not accepted. Check it in Account.', 'davix-h2i' );
            case 'key_expired':
                return __( 'This API key has expired. Create a new one in your Customer Portal.', 'davix-h2i' );
            case 'monthly_quota_exceeded':
                return __( 'Your plan\'s allowance for this period is used up.', 'davix-h2i' );
            case 'endpoint_not_allowed':
                return __( 'Your plan does not include this feature.', 'davix-h2i' );
            case 'format_not_allowed':
                return __( 'This file type is not included in your plan.', 'davix-h2i' );
            case 'rate_limit_exceeded':
            case 'rate_limit_unavailable':
                return __( 'Too many requests at once. Slowing down for a moment.', 'davix-h2i' );
            case 'async_not_allowed':
                return __( 'Background processing is not included in your plan, so this ran directly.', 'davix-h2i' );
            case 'async_limit_reached':
                return __( 'Too many background jobs are running. This will continue shortly.', 'davix-h2i' );
            case 'file_too_large':
                return __( 'This file is larger than your plan allows.', 'davix-h2i' );
            case 'invalid_parameter':
            case 'missing_field':
                return '' !== $field
                    /* translators: %s: option name */
                    ? sprintf( __( 'The option "%s" is not valid.', 'davix-h2i' ), $field )
                    : ( $e->getMessage() ? $e->getMessage() : __( 'One of the options is not valid.', 'davix-h2i' ) );
            case 'ai_feature_unavailable':
            case 'feature_unavailable':
                return __( 'This feature is not available right now.', 'davix-h2i' );
        }
        $message = $e->getMessage();
        return '' !== $message ? $message : __( 'Something went wrong. Please try again.', 'davix-h2i' );
    }
}
