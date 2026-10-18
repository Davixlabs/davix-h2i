<?php
/**
 * The only questions UI and workers ask about the plan: is this operation included, what does it cost, how many files
 * per request, is the AI feature ready. Everything is read from the data the app reports, never hard-coded.
 */

namespace DavixWP\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CapabilityGate {

    const OK          = 'ok';
    const LOCKED      = 'locked';
    const UNAVAILABLE = 'unavailable';
    const BETA        = 'beta';
    const UNKNOWN     = 'unknown';

    /** The endpoint entry for an operation id, from /v1/limits. */
    public static function entry( $operation_id ) {
        $snap = LimitsService::snapshot();
        if ( empty( $snap['limits']['endpoints'] ) ) {
            return null;
        }
        foreach ( $snap['limits']['endpoints'] as $entry ) {
            if ( isset( $entry['id'] ) && $entry['id'] === $operation_id ) {
                return $entry;
            }
        }
        return null;
    }

    /** True while the plan data is available (a key is saved and /v1/limits answered). */
    public static function known() {
        $snap = LimitsService::snapshot();
        return ! empty( $snap['limits'] );
    }

    /** ok | locked | unavailable | beta | unknown */
    public static function state( $operation_id ) {
        if ( ! self::known() ) {
            return self::UNKNOWN;
        }
        $entry = self::entry( $operation_id );
        if ( $entry && isset( $entry['allowed'] ) && false === $entry['allowed'] ) {
            return self::LOCKED;
        }
        $ready = self::readiness( $operation_id );
        if ( 'feature-unavailable' === $ready ) {
            return self::UNAVAILABLE;
        }
        if ( null !== $ready && 'production-ready' !== $ready ) {
            return self::BETA;
        }
        return self::OK;
    }

    /** May the operation be offered and run? (beta features are allowed; locked and unavailable are not). */
    public static function allows( $operation_id ) {
        $state = self::state( $operation_id );
        return self::LOCKED !== $state && self::UNAVAILABLE !== $state;
    }

    /** Units one call costs (1 when the plan does not say). */
    public static function cost( $operation_id ) {
        $entry = self::entry( $operation_id );
        return $entry && isset( $entry['cost_per_call'] ) ? (float) $entry['cost_per_call'] : 1.0;
    }

    /** A nested value from the endpoint entry: limit( 'images.resize', 'files.per_request' ). */
    public static function limit( $operation_id, $path, $default = null ) {
        $value = self::entry( $operation_id );
        foreach ( explode( '.', $path ) as $part ) {
            if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
                return $default;
            }
            $value = $value[ $part ];
        }
        return null === $value ? $default : $value;
    }

    /** Largest single file the plan accepts for the operation, in bytes (0 = no limit known). */
    public static function max_file_bytes( $operation_id ) {
        return (int) self::limit( $operation_id, 'files.max_file_size_bytes', 0 );
    }

    /** Files one request may carry (the plan's number, never a constant). */
    public static function batch_size( $operation_id ) {
        $n = (int) self::limit( $operation_id, 'files.per_request', 0 );
        return $n > 0 ? $n : 1;
    }

    /** production-ready | experimental | unbenchmarked | feature-unavailable | null (not an AI feature / unknown) */
    public static function readiness( $operation_id ) {
        if ( 0 !== strpos( $operation_id, 'ai.' ) ) {
            return null;
        }
        $snap = LimitsService::snapshot();
        if ( empty( $snap['ai']['features_by_family'] ) ) {
            return null;
        }
        $wanted = substr( $operation_id, 3 );
        foreach ( $snap['ai']['features_by_family'] as $features ) {
            foreach ( (array) $features as $feature ) {
                if ( isset( $feature['id'] ) && $feature['id'] === $wanted ) {
                    return isset( $feature['readiness'] ) ? (string) $feature['readiness'] : null;
                }
            }
        }
        return null;
    }

    /** What is left of the plan this period. */
    public static function allowance() {
        $snap = LimitsService::snapshot();
        return isset( $snap['limits']['allowance'] ) && is_array( $snap['limits']['allowance'] ) ? $snap['limits']['allowance'] : null;
    }

    public static function plan() {
        $snap = LimitsService::snapshot();
        return isset( $snap['limits']['plan'] ) && is_array( $snap['limits']['plan'] ) ? $snap['limits']['plan'] : null;
    }

    /** True when the allowance is already used up and the plan blocks further use. */
    public static function exhausted() {
        $a = self::allowance();
        if ( ! $a || ! isset( $a['total'] ) || null === $a['total'] ) {
            return false;
        }
        $over_ok = isset( $a['overage'] ) && 'allow' === $a['overage'];
        return ! $over_ok && isset( $a['used'] ) && (float) $a['used'] >= (float) $a['total'];
    }

    /**
     * What the browser needs to grey out controls: operation id => state, cost and batch size.
     *
     * @param string[] $operation_ids
     */
    public static function for_js( array $operation_ids ) {
        $out = array();
        foreach ( $operation_ids as $id ) {
            $out[ $id ] = array(
                'state' => self::state( $id ),
                'cost'  => self::cost( $id ),
                'batch' => self::batch_size( $id ),
                'maxMb' => round( self::max_file_bytes( $id ) / 1048576, 1 ),
            );
        }
        return $out;
    }
}
