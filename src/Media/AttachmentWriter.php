<?php
/**
 * Turns a result file into WordPress media: replaces the file of an attachment (with an optional backup), saves it
 * as a copy next to the original, or imports it as a brand new item.
 */

namespace DavixWP\Media;

use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AttachmentWriter {

    /**
     * Replaces the file of an attachment.
     *
     * @param int    $id
     * @param string $source Path of the new file.
     * @param array  $opts   backup (bool), keep_source (bool: copy instead of move), filename, mime, marker (bool), state (array)
     * @return array|\WP_Error path, size, mime
     */
    public static function replace( $id, $source, array $opts = array() ) {
        $opts = array_merge(
            array(
                'backup'      => (bool) Options::get( 'images.output.backup', true ),
                'keep_source' => false,
                'filename'    => '',
                'mime'        => '',
                'marker'      => true,
                'state'       => array(),
            ),
            $opts
        );
        $current = get_attached_file( $id );
        if ( ! $current || ! is_file( $source ) ) {
            return new \WP_Error( 'davix_h2i_missing_file', __( 'The file could not be found.', 'davix-h2i' ) );
        }

        Guard::enter();
        try {
            if ( $opts['backup'] ) {
                BackupStore::save( $id );
            }

            $dir  = dirname( $current );
            $mime = $opts['mime'] ? $opts['mime'] : self::mime_of( $source, $opts['filename'] );
            $name = $opts['filename'] ? $opts['filename'] : wp_basename( $current );
            $ext_new = self::extension_for( $mime, $name );
            $base    = pathinfo( $name, PATHINFO_FILENAME );
            $target_name = $base . '.' . $ext_new;
            $same_name   = ( wp_basename( $current ) === $target_name );

            self::delete_generated_sizes( $id );
            if ( ! $same_name && is_file( $current ) ) {
                wp_delete_file( $current );
            }
            $target = $same_name ? $current : trailingslashit( $dir ) . wp_unique_filename( $dir, $target_name );

            if ( $opts['keep_source'] ) {
                $moved = @copy( $source, $target ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            } else {
                $moved = @rename( $source, $target ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
                if ( ! $moved ) {
                    $moved = @copy( $source, $target ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
                    if ( $moved ) {
                        @unlink( $source ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
                    }
                }
            }
            if ( ! $moved ) {
                return new \WP_Error( 'davix_h2i_write_failed', __( 'The new file could not be saved in the uploads folder.', 'davix-h2i' ) );
            }

            update_attached_file( $id, $target );
            if ( get_post_mime_type( $id ) !== $mime ) {
                wp_update_post( array( 'ID' => $id, 'post_mime_type' => $mime ) );
            }
            self::regenerate( $id, $target );

            if ( $opts['marker'] && $opts['state'] ) {
                StateMarker::set( $id, $opts['state'] );
            }
            return array(
                'path' => $target,
                'size' => (int) filesize( $target ),
                'mime' => $mime,
            );
        } finally {
            Guard::leave();
        }
    }

    /**
     * Saves the result as a new media item next to the original.
     *
     * @return array|\WP_Error id, path, size, mime
     */
    public static function copy( $id, $source, $suffix, array $opts = array() ) {
        $current = get_attached_file( $id );
        $name    = $current ? pathinfo( $current, PATHINFO_FILENAME ) : 'file';
        $mime    = isset( $opts['mime'] ) && $opts['mime'] ? $opts['mime'] : self::mime_of( $source, isset( $opts['filename'] ) ? $opts['filename'] : '' );
        $ext     = self::extension_for( $mime, isset( $opts['filename'] ) ? $opts['filename'] : '' );
        $title   = get_the_title( $id );
        $opts['filename'] = $name . $suffix . '.' . $ext;
        $opts['parent']   = (int) wp_get_post_parent_id( $id );
        $opts['title']    = $title ? $title . $suffix : $name . $suffix;
        $opts['source_id'] = (int) $id;
        return self::import( $source, $opts );
    }

    /**
     * Imports a file as a new media item.
     *
     * @param string $source
     * @param array  $opts filename, mime, title, parent, source_id, keep_source, state
     * @return array|\WP_Error
     */
    public static function import( $source, array $opts = array() ) {
        if ( ! is_file( $source ) ) {
            return new \WP_Error( 'davix_h2i_missing_file', __( 'The file could not be found.', 'davix-h2i' ) );
        }
        $upload = wp_upload_dir();
        if ( ! empty( $upload['error'] ) ) {
            return new \WP_Error( 'davix_h2i_upload_dir', $upload['error'] );
        }
        $mime = ! empty( $opts['mime'] ) ? $opts['mime'] : self::mime_of( $source, isset( $opts['filename'] ) ? $opts['filename'] : '' );
        $name = ! empty( $opts['filename'] ) ? $opts['filename'] : 'davix-h2i-' . substr( md5( $source ), 0, 8 ) . '.' . self::extension_for( $mime, '' );
        $name = sanitize_file_name( $name );

        Guard::enter();
        try {
            $target = trailingslashit( $upload['path'] ) . wp_unique_filename( $upload['path'], $name );
            $ok     = ! empty( $opts['keep_source'] ) ? @copy( $source, $target ) : @rename( $source, $target ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            if ( ! $ok && ! empty( $opts['keep_source'] ) ) {
                return new \WP_Error( 'davix_h2i_write_failed', __( 'The new file could not be saved in the uploads folder.', 'davix-h2i' ) );
            }
            if ( ! $ok ) {
                $ok = @copy( $source, $target ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
                if ( $ok ) {
                    @unlink( $source ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
                }
            }
            if ( ! $ok ) {
                return new \WP_Error( 'davix_h2i_write_failed', __( 'The new file could not be saved in the uploads folder.', 'davix-h2i' ) );
            }
            $post_id = wp_insert_attachment(
                array(
                    'post_mime_type' => $mime,
                    'post_title'     => isset( $opts['title'] ) && $opts['title'] ? $opts['title'] : pathinfo( $name, PATHINFO_FILENAME ),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                ),
                $target,
                isset( $opts['parent'] ) ? (int) $opts['parent'] : 0,
                true
            );
            if ( is_wp_error( $post_id ) ) {
                return $post_id;
            }
            self::regenerate( $post_id, $target );
            StateMarker::mark_generated( $post_id );
            if ( ! empty( $opts['source_id'] ) ) {
                update_post_meta( $post_id, '_davix_h2i_source', (int) $opts['source_id'] );
            }
            if ( ! empty( $opts['state'] ) ) {
                StateMarker::set( $post_id, $opts['state'] );
            }
            return array(
                'id'   => (int) $post_id,
                'path' => $target,
                'size' => (int) filesize( $target ),
                'mime' => $mime,
                'url'  => wp_get_attachment_url( $post_id ),
            );
        } finally {
            Guard::leave();
        }
    }

    /** Rebuilds the thumbnails and metadata of a file. */
    public static function regenerate( $id, $path ) {
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $meta = wp_generate_attachment_metadata( $id, $path );
        if ( is_array( $meta ) ) {
            wp_update_attachment_metadata( $id, $meta );
        }
    }

    private static function delete_generated_sizes( $id ) {
        $meta = wp_get_attachment_metadata( $id );
        $file = get_attached_file( $id );
        if ( ! $file || empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) {
            return;
        }
        $dir = dirname( $file );
        foreach ( $meta['sizes'] as $size ) {
            if ( ! empty( $size['file'] ) ) {
                $path = $dir . '/' . $size['file'];
                if ( is_file( $path ) && $path !== $file ) {
                    wp_delete_file( $path );
                }
            }
        }
    }

    public static function mime_of( $path, $filename = '' ) {
        $name = $filename ? $filename : wp_basename( $path );
        $type = wp_check_filetype( $name );
        if ( ! empty( $type['type'] ) ) {
            return $type['type'];
        }
        if ( function_exists( 'mime_content_type' ) && is_file( $path ) ) {
            $detected = mime_content_type( $path );
            if ( $detected ) {
                return $detected;
            }
        }
        return 'application/octet-stream';
    }

    public static function extension_for( $mime, $fallback_name = '' ) {
        $map = array(
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/gif'       => 'gif',
            'image/avif'      => 'avif',
            'image/tiff'      => 'tif',
            'image/bmp'       => 'bmp',
            'image/svg+xml'   => 'svg',
            'image/x-icon'    => 'ico',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'application/rtf' => 'rtf',
            'text/rtf'        => 'rtf',
        );
        if ( isset( $map[ $mime ] ) ) {
            return $map[ $mime ];
        }
        $ext = strtolower( pathinfo( (string) $fallback_name, PATHINFO_EXTENSION ) );
        return '' !== $ext ? $ext : 'bin';
    }
}
