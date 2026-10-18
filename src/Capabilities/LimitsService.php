<?php
/**
 * The plan-aware data: what the key's plan allows (GET /v1/limits) and how ready each AI feature is
 * (GET /v1/ai/capabilities). Cached in transients; refreshed on connect, on a refusal, or by the user.
 */

namespace DavixWP\Capabilities;

use DavixWP\Connection\ClientFactory;
use DavixWP\Connection\ErrorMapper;
use DavixWP\Connection\KeyStore;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class LimitsService {

    const LIMITS_KEY = 'davix_h2i_limits';
    const AI_KEY     = 'davix_h2i_ai_caps';
    const TTL        = 300;

    /** @var array|null */
    private static $memo = null;

    /**
     * @return array{limits:?array,ai:?array,fetched_at:int,error:string}
     */
    public static function snapshot( $force = false ) {
        if ( ! $force && null !== self::$memo ) {
            return self::$memo;
        }
        $limits = $force ? false : get_transient( self::LIMITS_KEY );
        $ai     = $force ? false : get_transient( self::AI_KEY );
        $error  = '';

        if ( ( false === $limits || false === $ai ) && KeyStore::has() ) {
            try {
                $client = ClientFactory::make();
                if ( false === $limits ) {
                    $limits = $client->call( 'limits' )->data;
                    set_transient( self::LIMITS_KEY, $limits, self::TTL );
                }
            } catch ( \Throwable $e ) {
                $mapped = ErrorMapper::map( $e );
                $error  = $mapped['message'];
                $limits = false;
            }
            try {
                if ( false === $ai ) {
                    $ai = ClientFactory::make()->call( 'ai.capabilities' )->data;
                    set_transient( self::AI_KEY, $ai, self::TTL );
                }
            } catch ( \Throwable $e ) {
                $ai = false;
            }
        }

        self::$memo = array(
            'limits'     => is_array( $limits ) ? $limits : null,
            'ai'         => is_array( $ai ) ? $ai : null,
            'fetched_at' => (int) get_option( 'davix_h2i_limits_at', 0 ),
            'error'      => $error,
        );
        if ( is_array( $limits ) && ( $force || ! self::$memo['fetched_at'] ) ) {
            update_option( 'davix_h2i_limits_at', time(), false );
            self::$memo['fetched_at'] = time();
        }
        return self::$memo;
    }

    public static function refresh() {
        self::flush();
        return self::snapshot( true );
    }

    public static function flush() {
        delete_transient( self::LIMITS_KEY );
        delete_transient( self::AI_KEY );
        self::$memo = null;
    }

    /** Test helper: put a snapshot in place. */
    public static function set_snapshot( array $limits = null, array $ai = null ) {
        self::$memo = array(
            'limits'     => $limits,
            'ai'         => $ai,
            'fetched_at' => time(),
            'error'      => '',
        );
    }
}
