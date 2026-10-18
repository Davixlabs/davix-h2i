<?php
/**
 * Read access to the contract that ships with the Davix H2I SDK (vendor/davix-h2i-sdk/resources/contract.json).
 * It needs no API key and no network: forms, limits and tests read endpoint fields from here, so replacing the SDK
 * folder with a newer copy is all that is needed to pick up a new contract.
 */

namespace DavixWP\Operations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Contract {

    /** @var array|null */
    private static $data = null;

    /** @var array<string,array> */
    private static $index = array();

    public static function path() {
        $dir = '';
        if ( class_exists( '\Davix\H2I\Client', false ) ) {
            $ref = new \ReflectionClass( '\Davix\H2I\Client' );
            $dir = dirname( dirname( $ref->getFileName() ) );
        } elseif ( defined( 'DAVIX_H2I_DIR' ) ) {
            $dir = DAVIX_H2I_DIR . 'vendor/davix-h2i-sdk';
        }
        return $dir . '/resources/contract.json';
    }

    public static function load() {
        if ( null === self::$data ) {
            $json       = is_readable( self::path() ) ? file_get_contents( self::path() ) : false;
            $parsed     = $json ? json_decode( $json, true ) : null;
            self::$data = is_array( $parsed ) ? $parsed : array( 'endpoints' => array() );
            foreach ( self::$data['endpoints'] as $endpoint ) {
                self::$index[ $endpoint['id'] ] = $endpoint;
            }
        }
        return self::$data;
    }

    public static function version() {
        $data = self::load();
        return isset( $data['contractVersion'] ) ? (string) $data['contractVersion'] : '';
    }

    public static function has( $id ) {
        self::load();
        return isset( self::$index[ $id ] );
    }

    public static function endpoint( $id ) {
        self::load();
        return isset( self::$index[ $id ] ) ? self::$index[ $id ] : null;
    }

    public static function ids() {
        self::load();
        return array_keys( self::$index );
    }

    /**
     * One request field as a form-ready spec.
     *
     * @return array|null name, type (text|number|switch|select|json|file), label, options, min, max, default, required, help
     */
    public static function field( $id, $name ) {
        $endpoint = self::endpoint( $id );
        if ( ! $endpoint ) {
            return null;
        }
        foreach ( $endpoint['request']['fields'] as $field ) {
            if ( $field['name'] === $name ) {
                return self::spec( $field );
            }
        }
        // "watermark.text": a property of an object field.
        if ( false !== strpos( $name, '.' ) ) {
            $object = substr( $name, 0, strpos( $name, '.' ) );
            foreach ( self::object_fields( $id, $object ) as $spec ) {
                if ( $spec['name'] === $name ) {
                    return $spec;
                }
            }
        }
        return null;
    }

    /** Every request field of an endpoint, as the contract lists them. */
    public static function request_fields( $id ) {
        $endpoint = self::endpoint( $id );
        return $endpoint && isset( $endpoint['request']['fields'] ) ? $endpoint['request']['fields'] : array();
    }

    /**
     * The properties of an object field (the watermark: text, position, opacity ...) as form specs named "watermark.text".
     * The form puts them back together into one object before it is sent.
     *
     * @return array<int,array>
     */
    public static function object_fields( $id, $name ) {
        $out = array();
        foreach ( self::request_fields( $id ) as $field ) {
            if ( $field['name'] !== $name || empty( $field['properties'] ) ) {
                continue;
            }
            foreach ( $field['properties'] as $prop ) {
                $spec           = self::spec( $prop );
                $spec['object'] = $name;
                $spec['key']    = $prop['name'];
                $spec['name']   = $name . '.' . $prop['name'];
                $out[]          = $spec;
            }
        }
        return $out;
    }

    /** True when the endpoint has a request field of that name. */
    public static function has_field( $id, $name ) {
        return null !== self::field( $id, $name );
    }

    public static function spec( array $field ) {
        $type = isset( $field['type'] ) ? $field['type'] : 'string';
        $spec = array(
            'name'     => $field['name'],
            'label'    => self::humanize( $field['name'] ),
            'required' => ! empty( $field['required'] ),
            'help'     => isset( $field['description'] ) ? (string) $field['description'] : '',
            'default'  => array_key_exists( 'default', $field ) ? $field['default'] : null,
        );
        if ( isset( $field['enum'] ) && is_array( $field['enum'] ) ) {
            $spec['type']    = 'select';
            $spec['options'] = array_combine( array_map( 'strval', $field['enum'] ), array_map( 'strval', $field['enum'] ) );
        } elseif ( 'boolean' === $type ) {
            $spec['type'] = 'switch';
        } elseif ( 'integer' === $type || 'number' === $type ) {
            $spec['type'] = 'number';
            $spec['step'] = 'integer' === $type ? '1' : 'any';
        } elseif ( 'object' === $type || 'array' === $type ) {
            $spec['type'] = 'json';
        } elseif ( 'file' === $type ) {
            $spec['type'] = 'file';
        } else {
            $spec['type'] = 'text';
        }
        if ( isset( $field['minimum'] ) ) {
            $spec['min'] = $field['minimum'];
        }
        if ( isset( $field['maximum'] ) ) {
            $spec['max'] = $field['maximum'];
        }
        return $spec;
    }

    public static function humanize( $name ) {
        $name = preg_replace( '/([a-z])([A-Z])/', '$1 $2', (string) $name );
        $name = str_replace( array( '_', '.', '-' ), ' ', $name );
        $name = ucfirst( strtolower( trim( $name ) ) );
        // Words that are written in capitals: Max size KB, Output DPI.
        return preg_replace_callback( '/\b(kb|mb|dpi|gps|exif|icc|url|urls|seo|pdf|ocr|html|css|json|qr|jpeg|png|webp|avif|gif|tiff|svg|ai)\b/i', function ( $m ) {
            return strtoupper( $m[1] );
        }, $name );
    }

    /** Test helper: forget what was loaded. */
    public static function reset() {
        self::$data  = null;
        self::$index = array();
    }
}
