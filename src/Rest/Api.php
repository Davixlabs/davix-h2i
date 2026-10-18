<?php
/**
 * Small helpers for REST routes: one namespace, a capability check on every route, errors in one shape.
 */

namespace DavixWP\Rest;

use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Api {

    const NAMESPACE_V2 = 'davix-h2i/v2';

    /**
     * @param string          $route    e.g. "/jobs/(?P<id>\d+)/cancel"
     * @param string|string[] $methods
     * @param callable        $callback receives the WP_REST_Request, returns array|WP_Error
     * @param string          $level    "use" (editors and up, setting), "manage" (administrators)
     */
    public static function route( $route, $methods, $callback, $level = 'use', array $args = array() ) {
        register_rest_route(
            self::NAMESPACE_V2,
            $route,
            array(
                'methods'             => $methods,
                'callback'            => function ( \WP_REST_Request $request ) use ( $callback ) {
                    try {
                        $result = call_user_func( $callback, $request );
                    } catch ( \Throwable $e ) {
                        return new \WP_Error( 'davix_h2i_error', $e->getMessage(), array( 'status' => 500 ) );
                    }
                    return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
                },
                'permission_callback' => function () use ( $level ) {
                    return self::can( $level );
                },
                'args'                => $args,
            )
        );
    }

    public static function can( $level ) {
        if ( 'manage' === $level ) {
            return current_user_can( (string) Options::get( 'runtime.access.manage', 'manage_options' ) );
        }
        return current_user_can( (string) Options::get( 'runtime.access.use', 'upload_files' ) );
    }

    public static function error( $message, $status = 400, $code = 'davix_h2i_invalid' ) {
        return new \WP_Error( $code, $message, array( 'status' => $status ) );
    }

    /** Can the current user act on this attachment? */
    public static function can_edit_attachment( $id ) {
        return $id && current_user_can( 'edit_post', (int) $id );
    }
}
