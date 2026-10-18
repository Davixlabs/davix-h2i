<?php
/**
 * The settings the core owns: the connection and the runtime behaviour. Modules register their own sections.
 */

namespace DavixWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CoreSettings {

    public static function register() {
        Options::register(
            'connection',
            array(
                'verified_at' => 0,
                'plan_name'   => '',
                'email'       => '',
            ),
            array( __CLASS__, 'sanitize_connection' )
        );
        Options::register(
            'runtime',
            array(
                'timeout'              => 60,
                'retries'              => 2,
                'auto_idempotency'     => true,
                'background'           => true,
                'remote_jobs'          => false,
                'batch'                => 10,
                'history_enabled'      => true,
                'history_retention'    => 90,
                'jobs_retention'       => 14,
                'log_enabled'          => false,
                'uninstall_cleanup'    => false,
                'upgrade_badges'       => true,
                'access'               => array( 'use' => 'upload_files', 'manage' => 'manage_options' ),
            ),
            array( __CLASS__, 'sanitize_runtime' )
        );
    }

    public static function sanitize_connection( array $v, array $defaults ) {
        return array(
            'verified_at' => isset( $v['verified_at'] ) ? (int) $v['verified_at'] : 0,
            'plan_name'   => isset( $v['plan_name'] ) ? sanitize_text_field( $v['plan_name'] ) : '',
            'email'       => isset( $v['email'] ) ? sanitize_email( $v['email'] ) : '',
        );
    }

    public static function sanitize_runtime( array $v, array $defaults ) {
        $caps = array( 'upload_files', 'edit_posts', 'publish_posts', 'manage_options' );
        $use  = isset( $v['access']['use'] ) && in_array( $v['access']['use'], $caps, true ) ? $v['access']['use'] : 'upload_files';
        return array(
            'timeout'           => max( 5, min( 300, (int) $v['timeout'] ) ),
            'retries'           => max( 0, min( 5, (int) $v['retries'] ) ),
            'auto_idempotency'  => ! empty( $v['auto_idempotency'] ),
            'background'        => ! empty( $v['background'] ),
            'remote_jobs'       => ! empty( $v['remote_jobs'] ),
            'batch'             => max( 1, min( 50, (int) $v['batch'] ) ),
            'history_enabled'   => ! empty( $v['history_enabled'] ),
            'history_retention' => max( 0, min( 3650, (int) $v['history_retention'] ) ),
            'jobs_retention'    => max( 1, min( 365, (int) $v['jobs_retention'] ) ),
            'log_enabled'       => ! empty( $v['log_enabled'] ),
            'uninstall_cleanup' => ! empty( $v['uninstall_cleanup'] ),
            'upgrade_badges'    => ! empty( $v['upgrade_badges'] ),
            'access'            => array( 'use' => $use, 'manage' => 'manage_options' ),
        );
    }
}
