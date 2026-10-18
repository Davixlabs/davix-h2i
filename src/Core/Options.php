<?php
/**
 * Plugin settings. Every module registers its own section (defaults + a sanitizer); a section is one WordPress option
 * (davix_h2i_<section>), never autoloaded. Values are always returned merged over the defaults, so code can rely on every key.
 */

namespace DavixWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Options {

    /** @var array<string,array{defaults:array,sanitize:?callable}> */
    private static $sections = array();

    /** @var array<string,array> */
    private static $cache = array();

    public static function register( $section, array $defaults, $sanitize = null ) {
        self::$sections[ $section ] = array(
            'defaults' => $defaults,
            'sanitize' => $sanitize,
        );
        unset( self::$cache[ $section ] );
    }

    public static function sections() {
        return array_keys( self::$sections );
    }

    public static function option_name( $section ) {
        return 'davix_h2i_' . $section;
    }

    public static function defaults( $section ) {
        return isset( self::$sections[ $section ] ) ? self::$sections[ $section ]['defaults'] : array();
    }

    /** The whole section, merged over its defaults. */
    public static function section( $section ) {
        if ( isset( self::$cache[ $section ] ) ) {
            return self::$cache[ $section ];
        }
        $stored = get_option( self::option_name( $section ), array() );
        if ( ! is_array( $stored ) ) {
            $stored = array();
        }
        self::$cache[ $section ] = self::merge( self::defaults( $section ), $stored );
        return self::$cache[ $section ];
    }

    /** One value by path: "images.output.mode". */
    public static function get( $path, $default = null ) {
        $parts   = explode( '.', $path );
        $section = array_shift( $parts );
        $value   = self::section( $section );
        foreach ( $parts as $part ) {
            if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
                return $default;
            }
            $value = $value[ $part ];
        }
        return $value;
    }

    /** Sanitizes and saves values of a section (merged over what is stored). Returns the saved section. */
    public static function update( $section, array $values ) {
        if ( ! isset( self::$sections[ $section ] ) ) {
            return array();
        }
        $merged = self::merge( self::section( $section ), $values );
        $clean  = self::sanitize( $section, $merged );
        update_option( self::option_name( $section ), $clean, false );
        unset( self::$cache[ $section ] );
        do_action( 'davix_h2i_options_updated', $section, $clean );
        return self::section( $section );
    }

    /** Replaces a section with sanitized values (used by import). */
    public static function replace( $section, array $values ) {
        if ( ! isset( self::$sections[ $section ] ) ) {
            return array();
        }
        $clean = self::sanitize( $section, self::merge( self::defaults( $section ), $values ) );
        update_option( self::option_name( $section ), $clean, false );
        unset( self::$cache[ $section ] );
        return self::section( $section );
    }

    public static function reset( $section ) {
        delete_option( self::option_name( $section ) );
        unset( self::$cache[ $section ] );
        return self::section( $section );
    }

    public static function flush_cache() {
        self::$cache = array();
    }

    public static function sanitize( $section, array $values ) {
        $sanitize = isset( self::$sections[ $section ] ) ? self::$sections[ $section ]['sanitize'] : null;
        if ( is_callable( $sanitize ) ) {
            $values = call_user_func( $sanitize, $values, self::defaults( $section ) );
        }
        return is_array( $values ) ? $values : self::defaults( $section );
    }

    /**
     * Deep merge: associative arrays merge key by key; lists and scalars from $over replace the base.
     */
    public static function merge( array $base, array $over ) {
        foreach ( $over as $key => $value ) {
            if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) && self::is_assoc( $value ) && self::is_assoc( $base[ $key ] ) ) {
                $base[ $key ] = self::merge( $base[ $key ], $value );
            } else {
                $base[ $key ] = $value;
            }
        }
        return $base;
    }

    private static function is_assoc( array $a ) {
        return array() !== $a && array_keys( $a ) !== range( 0, count( $a ) - 1 );
    }
}
