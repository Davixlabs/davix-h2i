<?php
/**
 * A small file logger for the Debug log. Off unless the user switches it on. Keys, passwords and signed URLs are
 * redacted before anything is written.
 */

namespace DavixWP\Support;

use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Logger {

    const MAX_BYTES = 2097152; // 2 MB, then the file is rotated.

    public static function enabled() {
        return (bool) Options::get( 'runtime.log_enabled', false );
    }

    public static function dir() {
        $uploads = wp_upload_dir( null, false );
        return trailingslashit( $uploads['basedir'] ) . 'davix-h2i';
    }

    public static function path() {
        return self::dir() . '/debug.log';
    }

    public static function protect_dir( $dir ) {
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        if ( is_dir( $dir ) ) {
            if ( ! is_file( $dir . '/index.php' ) ) {
                file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
            }
            if ( ! is_file( $dir . '/.htaccess' ) ) {
                file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
            }
        }
    }

    public static function log( $level, $message, array $context = array() ) {
        if ( ! self::enabled() ) {
            return;
        }
        $dir = self::dir();
        self::protect_dir( $dir );
        $file = self::path();
        if ( is_file( $file ) && filesize( $file ) > self::MAX_BYTES ) {
            @rename( $file, $file . '.1' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
        $line = sprintf(
            "[%s] %s %s %s\n",
            gmdate( 'Y-m-d H:i:s' ),
            strtoupper( $level ),
            self::redact_text( (string) $message ),
            $context ? wp_json_encode( self::redact( $context ) ) : ''
        );
        file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
    }

    public static function info( $message, array $context = array() ) {
        self::log( 'info', $message, $context );
    }

    public static function error( $message, array $context = array() ) {
        self::log( 'error', $message, $context );
    }

    public static function debug( $message, array $context = array() ) {
        self::log( 'debug', $message, $context );
    }

    /** The last lines, newest last. */
    public static function tail( $lines = 300 ) {
        $file = self::path();
        if ( ! is_file( $file ) ) {
            return array();
        }
        $all = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        return is_array( $all ) ? array_slice( $all, -1 * max( 1, (int) $lines ) ) : array();
    }

    public static function clear() {
        $file = self::path();
        if ( is_file( $file ) ) {
            file_put_contents( $file, '' );
        }
    }

    public static function redact( array $data ) {
        foreach ( $data as $key => $value ) {
            if ( is_string( $key ) && preg_match( '/key|token|secret|password|authorization/i', $key ) ) {
                $data[ $key ] = '[redacted]';
            } elseif ( is_array( $value ) ) {
                $data[ $key ] = self::redact( $value );
            } elseif ( is_string( $value ) ) {
                $data[ $key ] = self::redact_text( $value );
            }
        }
        return $data;
    }

    public static function redact_text( $text ) {
        $text = preg_replace( '/(sig|signature|token|key)=([^&\s"]+)/i', '$1=[redacted]', $text );
        return preg_replace( '/\bh2i_[A-Za-z0-9_\-]{8,}\b/', 'h2i_[redacted]', (string) $text );
    }
}
