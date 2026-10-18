<?php
/**
 * Remembers what the plugin did to an attachment (operation, settings hash, bytes before and after, request id), so
 * nothing is processed twice and savings can be shown.
 */

namespace DavixWP\Media;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class StateMarker {

    const META      = '_davix_h2i_state';
    const GENERATED = '_davix_h2i_generated';

    /** A stable hash of the parameters that matter. */
    public static function params_hash( array $params ) {
        ksort( $params );
        return substr( md5( wp_json_encode( $params ) ), 0, 16 );
    }

    public static function set( $attachment_id, array $state ) {
        $state['time'] = time();
        update_post_meta( $attachment_id, self::META, $state );
    }

    public static function get( $attachment_id ) {
        $s = get_post_meta( $attachment_id, self::META, true );
        return is_array( $s ) ? $s : null;
    }

    /** Has this operation already been done to this file with the same settings? */
    public static function is_done( $attachment_id, $operation, array $params ) {
        $s = self::get( $attachment_id );
        return $s && isset( $s['operation'], $s['params_hash'] ) && $s['operation'] === $operation && $s['params_hash'] === self::params_hash( $params );
    }

    public static function mark_generated( $attachment_id ) {
        update_post_meta( $attachment_id, self::GENERATED, 1 );
    }

    public static function is_generated( $attachment_id ) {
        return (bool) get_post_meta( $attachment_id, self::GENERATED, true );
    }
}
