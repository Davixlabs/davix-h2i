<?php
/**
 * Builds the SDK client from the stored key and the connection and runtime settings. One client per request.
 */

namespace DavixWP\Connection;

use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ClientFactory {

    /** @var \Davix\H2I\Client|null */
    private static $client = null;

    /** @var object|null Replaces the client (tests). */
    private static $override = null;

    /** The hard-coded server address (see DAVIX_H2I_SERVER_URL in davix-h2i.php). */
    public static function base_url() {
        return untrailingslashit( apply_filters( 'davix_h2i_base_url', DAVIX_H2I_SERVER_URL ) );
    }

    /**
     * @param string|null $key_override Test a key that is not saved yet.
     * @return \Davix\H2I\Client
     * @throws \Davix\H2I\Exceptions\ConfigException When there is no key.
     */
    public static function make( $key_override = null ) {
        if ( null === $key_override && self::$override ) {
            return self::$override;
        }
        if ( null === $key_override && self::$client ) {
            return self::$client;
        }
        $key = null !== $key_override ? $key_override : KeyStore::get();
        $url = self::base_url();

        $client = new \Davix\H2I\Client(
            array(
                'apiKey'             => $key,
                'baseUrl'            => $url,
                'authScheme'         => 'x-api-key',
                'timeoutMs'          => max( 5, (int) Options::get( 'runtime.timeout', 60 ) ) * 1000,
                'maxRetries'         => max( 0, min( 5, (int) Options::get( 'runtime.retries', 2 ) ) ),
                'autoIdempotencyKey' => (bool) Options::get( 'runtime.auto_idempotency', true ),
                'allowInsecure'      => 'http' === wp_parse_url( $url, PHP_URL_SCHEME ),
                'transport'          => new WpTransport(),
                'userAgentSuffix'    => 'wordpress-plugin/' . ( defined( 'DAVIX_H2I_VERSION' ) ? DAVIX_H2I_VERSION : '0' ),
            )
        );
        if ( null === $key_override ) {
            self::$client = $client;
        }
        return $client;
    }

    public static function set_override( $client ) {
        self::$override = $client;
    }

    public static function reset() {
        self::$client   = null;
        self::$override = null;
    }
}
