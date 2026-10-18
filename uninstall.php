<?php
/**
 * Runs when the plugin is deleted. Everything is removed only if "Remove all plugin data when the plugin is deleted"
 * is on (Settings > Privacy & data). Media files are never deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$davix_h2i_runtime = get_option( 'davix_h2i_runtime', array() );
if ( empty( $davix_h2i_runtime['uninstall_cleanup'] ) ) {
    exit;
}

global $wpdb;

foreach ( array( 'connection', 'runtime', 'images', 'ai', 'pdf', 'render' ) as $davix_h2i_section ) {
    delete_option( 'davix_h2i_' . $davix_h2i_section );
}
foreach ( array( 'davix_h2i_key', 'davix_h2i_version', 'davix_h2i_db_version', 'davix_h2i_limits_at' ) as $davix_h2i_option ) {
    delete_option( $davix_h2i_option );
}
foreach ( array( 'davix_h2i_limits', 'davix_h2i_ai_caps', 'davix_h2i_usage', 'davix_h2i_health', 'davix_h2i_queue_paused', 'davix_h2i_worker_lock' ) as $davix_h2i_transient ) {
    delete_transient( $davix_h2i_transient );
}

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'davix_h2i_jobs' ); // phpcs:ignore WordPress.DB
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'davix_h2i_history' ); // phpcs:ignore WordPress.DB

foreach ( array( '_davix_h2i_state', '_davix_h2i_generated', '_davix_h2i_backup', '_davix_h2i_source', '_davix_h2i_tags', '_davix_h2i_seo', '_davix_h2i_flags' ) as $davix_h2i_meta ) {
    delete_metadata( 'post', 0, $davix_h2i_meta, '', true );
}

wp_clear_scheduled_hook( 'davix_h2i_run_queue' );
wp_clear_scheduled_hook( 'davix_h2i_run_queue_now' );
wp_clear_scheduled_hook( 'davix_h2i_prune' );

// The plugin's own folder in uploads (backups and the debug log).
$davix_h2i_upload = wp_upload_dir( null, false );
$davix_h2i_dir    = trailingslashit( $davix_h2i_upload['basedir'] ) . 'davix-h2i';
if ( is_dir( $davix_h2i_dir ) ) {
    $davix_h2i_items = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $davix_h2i_dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
    foreach ( $davix_h2i_items as $davix_h2i_item ) {
        if ( $davix_h2i_item->isDir() ) {
            rmdir( $davix_h2i_item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        } else {
            wp_delete_file( $davix_h2i_item->getPathname() );
        }
    }
    rmdir( $davix_h2i_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}
