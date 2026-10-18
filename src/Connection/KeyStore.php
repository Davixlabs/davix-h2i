<?php
/**
 * Where the customer's Davix H2I API key lives.
 *
 * Preferred: define( 'DAVIX_H2I_API_KEY', '...' ) in wp-config.php. Otherwise the key is stored encrypted
 * (libsodium secretbox, keyed from the site's salts) in one non-autoloaded option. The key is never printed back, never
 * sent to the browser and never written to a log; only its last four characters are shown.
 */

namespace DavixWP\Connection;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class KeyStore {

    const OPTION = 'davix_h2i_key';

    public static function from_constant() {
        return defined( 'DAVIX_H2I_API_KEY' ) && is_string( DAVIX_H2I_API_KEY ) && '' !== trim( DAVIX_H2I_API_KEY );
    }

    public static function has() {
        return '' !== self::get();
    }

    /** The key in clear text (server side only). */
    public static function get() {
        if ( self::from_constant() ) {
            return trim( DAVIX_H2I_API_KEY );
        }
        $stored = get_option( self::OPTION, '' );
        if ( ! is_string( $stored ) || '' === $stored ) {
            return '';
        }
        return self::decrypt( $stored );
    }

    public static function set( $key ) {
        $key = trim( (string) $key );
        if ( '' === $key || preg_match( '/[\r\n\s]/', $key ) ) {
            return false;
        }
        update_option( self::OPTION, self::encrypt( $key ), false );
        return true;
    }

    public static function clear() {
        delete_option( self::OPTION );
    }

    /** "••••abcd" (or empty when no key is stored). */
    public static function masked() {
        $key = self::get();
        if ( '' === $key ) {
            return '';
        }
        return str_repeat( "\xE2\x80\xA2", 8 ) . substr( $key, -4 );
    }

    /** "constant", "option" or "none". */
    public static function source() {
        if ( self::from_constant() ) {
            return 'constant';
        }
        return '' !== (string) get_option( self::OPTION, '' ) ? 'option' : 'none';
    }

    private static function secret() {
        $salt = function_exists( 'wp_salt' ) ? wp_salt( 'auth' ) : 'davix-h2i';
        return substr( hash( 'sha256', 'davix-h2i|' . $salt, true ), 0, 32 );
    }

    private static function encrypt( $plain ) {
        if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
            return 'b64:' . base64_encode( $plain );
        }
        $nonce  = random_bytes( 24 );
        $cipher = sodium_crypto_secretbox( $plain, $nonce, self::secret() );
        return 'sb1:' . base64_encode( $nonce . $cipher );
    }

    private static function decrypt( $stored ) {
        if ( 0 === strpos( $stored, 'b64:' ) ) {
            $plain = base64_decode( substr( $stored, 4 ), true );
            return false === $plain ? '' : $plain;
        }
        if ( 0 !== strpos( $stored, 'sb1:' ) || ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
            return '';
        }
        $raw = base64_decode( substr( $stored, 4 ), true );
        if ( false === $raw || strlen( $raw ) < 25 ) {
            return '';
        }
        $plain = sodium_crypto_secretbox_open( substr( $raw, 24 ), substr( $raw, 0, 24 ), self::secret() );
        return false === $plain ? '' : $plain;
    }
}
