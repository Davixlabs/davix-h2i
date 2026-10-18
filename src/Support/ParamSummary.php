<?php
/**
 * The settings an action was run with, as plain sentences for a person: "Keep metadata: Yes", "Format: WebP", "Step 2: Resize,
 * Width 1600". The History panel shows this instead of the raw settings.
 */

namespace DavixWP\Support;

use DavixWP\Operations\Contract;
use DavixWP\Operations\Definition;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ParamSummary {

    /** Shown longer texts (HTML, CSS, prompts) only up to this many characters. */
    const TEXT_LIMIT = 160;

    /**
     * @param string     $operation Operation id, for example images.resize.
     * @param array      $params    The settings as they were sent.
     * @param array|null $def       The operation definition, when known.
     * @return array<int,array{label:string,value:string}>
     */
    public static function summarize( $operation, array $params, $def = null ) {
        $specs = array();
        if ( is_array( $def ) ) {
            foreach ( Definition::fields( $def ) as $spec ) {
                $specs[ $spec['name'] ] = $spec;
            }
        }
        $rows = array();
        foreach ( $params as $key => $value ) {
            $key = (string) $key;
            if ( '_mode' === $key ) {
                $rows[] = array(
                    'label' => __( 'Result', 'davix-h2i' ),
                    'value' => 'copy' === $value ? __( 'Saved as a new copy', 'davix-h2i' ) : __( 'Replaced the original', 'davix-h2i' ),
                );
                continue;
            }
            if ( '' === $key || '_' === $key[0] || 'app' === $key ) {
                continue;
            }
            $spec = isset( $specs[ $key ] ) ? $specs[ $key ] : Contract::field( $operation, $key );
            self::add( $rows, self::label( $key, $spec ), self::decode( $value ), $spec, $key );
        }
        return $rows;
    }

    private static function label( $key, $spec ) {
        if ( is_array( $spec ) && ! empty( $spec['label'] ) ) {
            return (string) $spec['label'];
        }
        return Contract::humanize( $key );
    }

    private static function decode( $value ) {
        if ( is_string( $value ) ) {
            $t = trim( $value );
            if ( '' !== $t && ( '{' === $t[0] || '[' === $t[0] ) ) {
                $d = json_decode( $t, true );
                if ( null !== $d ) {
                    return $d;
                }
            }
        }
        return $value;
    }

    private static function is_list( array $a ) {
        return array_keys( $a ) === range( 0, count( $a ) - 1 );
    }

    private static function add( array &$rows, $label, $value, $spec, $key ) {
        if ( null === $value || '' === $value || array() === $value ) {
            return;
        }
        if ( is_array( $value ) ) {
            if ( self::is_list( $value ) ) {
                $all_scalar = true;
                foreach ( $value as $item ) {
                    if ( is_array( $item ) ) {
                        $all_scalar = false;
                    }
                }
                if ( $all_scalar ) {
                    $rows[] = array( 'label' => $label, 'value' => implode( ', ', array_map( function ( $v ) use ( $spec ) {
                        return self::scalar( $v, $spec, '' );
                    }, $value ) ) );
                    return;
                }
                // A list of steps or sizes: one line each.
                foreach ( $value as $i => $item ) {
                    if ( ! is_array( $item ) ) {
                        continue;
                    }
                    $name = '';
                    foreach ( array( 'op', 'name' ) as $n ) {
                        if ( isset( $item[ $n ] ) && is_string( $item[ $n ] ) ) {
                            $name = Contract::humanize( $item[ $n ] );
                            unset( $item[ $n ] );
                            break;
                        }
                    }
                    $rows[] = array(
                        'label' => sprintf( /* translators: 1: list name, 2: number */ __( '%1$s %2$d', 'davix-h2i' ), $label, $i + 1 ) . ( '' !== $name ? ': ' . $name : '' ),
                        'value' => self::inline( $item ),
                    );
                }
                return;
            }
            // A group of settings (for example the watermark): one line each, under the group's name.
            foreach ( $value as $k => $v ) {
                self::add( $rows, $label . ': ' . Contract::humanize( (string) $k ), self::decode( $v ), null, (string) $k );
            }
            return;
        }
        $rows[] = array( 'label' => $label, 'value' => self::scalar( $value, $spec, $key ) );
    }

    /** "Width: 1600, Fit: Fit inside" for a small group of settings. */
    private static function inline( array $item ) {
        $parts = array();
        foreach ( $item as $k => $v ) {
            $v = self::decode( $v );
            if ( null === $v || '' === $v || array() === $v ) {
                continue;
            }
            $parts[] = Contract::humanize( (string) $k ) . ': ' . ( is_array( $v ) ? self::inline( $v ) : self::scalar( $v, null, (string) $k ) );
        }
        return implode( ', ', $parts );
    }

    private static function scalar( $value, $spec, $key ) {
        if ( is_bool( $value ) || in_array( $value, array( 'true', 'false' ), true ) || ( is_array( $spec ) && isset( $spec['type'] ) && 'switch' === $spec['type'] && in_array( (string) $value, array( '0', '1' ), true ) ) ) {
            $on = true === $value || 'true' === $value || '1' === (string) $value || 1 === $value;
            return $on ? __( 'Yes', 'davix-h2i' ) : __( 'No', 'davix-h2i' );
        }
        $text = (string) $value;
        if ( is_array( $spec ) && ! empty( $spec['options'] ) && isset( $spec['options'][ $text ] ) ) {
            $text = (string) $spec['options'][ $text ];
        }
        if ( strlen( $text ) > self::TEXT_LIMIT ) {
            $text = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, self::TEXT_LIMIT ) : substr( $text, 0, self::TEXT_LIMIT );
            $text .= '…';
        }
        return $text;
    }
}
