<?php
/**
 * A tiny service locator: shared objects are registered once (lazily) and fetched by id.
 */

namespace DavixWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Services {

    /** @var array<string,callable> */
    private static $factories = array();

    /** @var array<string,mixed> */
    private static $instances = array();

    public static function set( $id, callable $factory ) {
        self::$factories[ $id ] = $factory;
        unset( self::$instances[ $id ] );
    }

    public static function has( $id ) {
        return isset( self::$factories[ $id ] ) || isset( self::$instances[ $id ] );
    }

    public static function get( $id ) {
        if ( ! array_key_exists( $id, self::$instances ) ) {
            if ( ! isset( self::$factories[ $id ] ) ) {
                throw new \RuntimeException( 'Unknown service: ' . $id );
            }
            self::$instances[ $id ] = call_user_func( self::$factories[ $id ] );
        }
        return self::$instances[ $id ];
    }

    /** Puts a ready object in place (tests, or an object built earlier). */
    public static function instance( $id, $object ) {
        self::$instances[ $id ] = $object;
    }

    public static function reset() {
        self::$factories = array();
        self::$instances = array();
    }
}
