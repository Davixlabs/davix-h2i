<?php
/**
 * Activation, deactivation and upgrades: the two plugin tables and the cron events.
 */

namespace DavixWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Installer {

    const DB_VERSION_OPTION = 'davix_h2i_db_version';
    const DB_VERSION        = '2';

    public static function jobs_table() {
        global $wpdb;
        return $wpdb->prefix . 'davix_h2i_jobs';
    }

    public static function history_table() {
        global $wpdb;
        return $wpdb->prefix . 'davix_h2i_history';
    }

    public static function activate() {
        self::create_tables();
        self::schedule();
        update_option( 'davix_h2i_version', DAVIX_H2I_VERSION, false );
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'davix_h2i_run_queue' );
        wp_clear_scheduled_hook( 'davix_h2i_prune' );
    }

    /** Runs on every load; only does work when the stored version differs. */
    public static function maybe_upgrade() {
        if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
            self::create_tables();
        }
        self::schedule();
        if ( get_option( 'davix_h2i_version' ) !== DAVIX_H2I_VERSION ) {
            update_option( 'davix_h2i_version', DAVIX_H2I_VERSION, false );
        }
    }

    public static function schedule() {
        if ( ! wp_next_scheduled( 'davix_h2i_run_queue' ) ) {
            wp_schedule_event( time() + 60, 'davix_h2i_minute', 'davix_h2i_run_queue' );
        }
        if ( ! wp_next_scheduled( 'davix_h2i_prune' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'davix_h2i_prune' );
        }
    }

    public static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $jobs    = self::jobs_table();
        $history = self::history_table();

        dbDelta(
            "CREATE TABLE {$jobs} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                batch_id varchar(40) NOT NULL DEFAULT '',
                user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                object_id bigint(20) unsigned NOT NULL DEFAULT 0,
                operation varchar(80) NOT NULL DEFAULT '',
                source varchar(20) NOT NULL DEFAULT '',
                params longtext NULL,
                status varchar(20) NOT NULL DEFAULT 'queued',
                attempts smallint(5) unsigned NOT NULL DEFAULT 0,
                remote_job_id bigint(20) unsigned NULL,
                request_id varchar(64) NOT NULL DEFAULT '',
                error_code varchar(64) NOT NULL DEFAULT '',
                error_message text NULL,
                history_id bigint(20) unsigned NOT NULL DEFAULT 0,
                run_after int(10) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY status (status),
                KEY batch_id (batch_id),
                KEY object_id (object_id)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$history} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                operation varchar(80) NOT NULL DEFAULT '',
                source varchar(20) NOT NULL DEFAULT '',
                object_id bigint(20) unsigned NOT NULL DEFAULT 0,
                object_title varchar(255) NOT NULL DEFAULT '',
                status varchar(20) NOT NULL DEFAULT 'success',
                request_id varchar(64) NOT NULL DEFAULT '',
                file_ids text NULL,
                params longtext NULL,
                result longtext NULL,
                bytes_before bigint(20) unsigned NOT NULL DEFAULT 0,
                bytes_after bigint(20) unsigned NOT NULL DEFAULT 0,
                error_code varchar(64) NOT NULL DEFAULT '',
                error_message text NULL,
                batch_id varchar(40) NOT NULL DEFAULT '',
                PRIMARY KEY  (id),
                KEY created_at (created_at),
                KEY operation (operation),
                KEY status (status),
                KEY object_id (object_id)
            ) {$charset};"
        );

        update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
    }

    public static function drop_tables() {
        global $wpdb;
        $wpdb->query( 'DROP TABLE IF EXISTS ' . self::jobs_table() ); // phpcs:ignore WordPress.DB.PreparedSQL
        $wpdb->query( 'DROP TABLE IF EXISTS ' . self::history_table() ); // phpcs:ignore WordPress.DB.PreparedSQL
        delete_option( self::DB_VERSION_OPTION );
    }
}
