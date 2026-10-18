<?php
/**
 * PSR-4 autoloader for the DavixWP\ namespace (src/). The Davix H2I SDK in vendor/ has its own loader.
 */

namespace DavixWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Autoloader {

    private const PREFIX = 'DavixWP\\';

    /** @var string */
    private static $base = '';

    public static function register( $base_dir ) {
        self::$base = rtrim( $base_dir, '/\\' ) . '/';
        spl_autoload_register( array( __CLASS__, 'load' ) );
    }

    public static function load( $class ) {
        if ( strpos( $class, self::PREFIX ) !== 0 ) {
            return;
        }
        $relative = substr( $class, strlen( self::PREFIX ) );
        $file     = self::$base . str_replace( '\\', '/', $relative ) . '.php';
        if ( is_file( $file ) ) {
            require_once $file;
        }
    }
}
