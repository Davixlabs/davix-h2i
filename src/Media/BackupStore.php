<?php
/**
 * Keeps a copy of the original file before the plugin replaces it, so any change can be undone from History.
 */

namespace DavixWP\Media;

use DavixWP\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class BackupStore {

    const META = '_davix_h2i_backup';

    public static function dir() {
        $uploads = wp_upload_dir( null, false );
        return trailingslashit( $uploads['basedir'] ) . 'davix-h2i/backups';
    }

    /** Copies the current file of an attachment into the backup folder (once: an existing backup is never overwritten). */
    public static function save( $attachment_id ) {
        $existing = get_post_meta( $attachment_id, self::META, true );
        if ( is_array( $existing ) && ! empty( $existing['path'] ) && is_file( $existing['path'] ) ) {
            return $existing;
        }
        $source = get_attached_file( $attachment_id );
        if ( ! $source || ! is_file( $source ) ) {
            return null;
        }
        $dir = self::dir() . '/' . (int) $attachment_id;
        Logger::protect_dir( dirname( self::dir() ) );
        Logger::protect_dir( self::dir() );
        wp_mkdir_p( $dir );
        $target = $dir . '/' . wp_basename( $source );
        if ( ! @copy( $source, $target ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
            Logger::error( 'Backup failed', array( 'attachment' => $attachment_id ) );
            return null;
        }
        $backup = array(
            'path'     => $target,
            'filename' => wp_basename( $source ),
            'mime'     => (string) get_post_mime_type( $attachment_id ),
            'size'     => (int) filesize( $target ),
            'time'     => time(),
        );
        update_post_meta( $attachment_id, self::META, $backup );
        return $backup;
    }

    public static function has( $attachment_id ) {
        $b = get_post_meta( $attachment_id, self::META, true );
        return is_array( $b ) && ! empty( $b['path'] ) && is_file( $b['path'] );
    }

    /** Puts the original back. Returns true when done. */
    public static function restore( $attachment_id ) {
        $b = get_post_meta( $attachment_id, self::META, true );
        if ( ! is_array( $b ) || empty( $b['path'] ) || ! is_file( $b['path'] ) ) {
            return false;
        }
        $result = AttachmentWriter::replace( $attachment_id, $b['path'], array( 'backup' => false, 'keep_source' => true, 'filename' => $b['filename'], 'mime' => $b['mime'], 'marker' => false ) );
        if ( is_wp_error( $result ) ) {
            return false;
        }
        delete_post_meta( $attachment_id, StateMarker::META );
        delete_post_meta( $attachment_id, self::META );
        @unlink( $b['path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
        return true;
    }

    public static function delete( $attachment_id ) {
        $b = get_post_meta( $attachment_id, self::META, true );
        if ( is_array( $b ) && ! empty( $b['path'] ) && is_file( $b['path'] ) ) {
            @unlink( $b['path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
            @rmdir( dirname( $b['path'] ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
        }
        delete_post_meta( $attachment_id, self::META );
    }
}
