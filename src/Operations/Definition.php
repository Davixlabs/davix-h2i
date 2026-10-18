<?php
/**
 * An operation definition: everything generic about one Davix H2I endpoint the plugin uses. The menu entries, forms,
 * plan checks, file filters, bulk actions and result handling are all driven by these arrays.
 *
 * Keys: id (contract id), label, group (images|render|ai|pdf|documents), release, input (files|file|text|none),
 * file_field (name of the multipart file field), multiple (bool), accepts (mime prefixes), result (files|text|json|images),
 * store (replace|copy|new|text|none), optimize (bool: never keep a larger result), async (bool), fields (names or specs),
 * defaults (array), surfaces (list), cost_hint (number).
 */

namespace DavixWP\Operations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Definition {

    /** Fields the plugin handles itself (how the answer comes back, file names, request control): never shown as options. */
    const SYSTEM_FIELDS = array( 'validate_only', 'presetId', 'response', 'filename', 'filename_template', 'source_url', 'Idempotency-Key', 'async', 'output_bundle', 'app' );

    public static function normalize( array $d ) {
        $d = array_merge(
            array(
                'label'      => '',
                'group'      => 'images',
                'release'    => '2.0',
                'input'      => 'files',
                'file_field' => 'images',
                'multiple'   => true,
                'accepts'    => array( 'image/' ),
                'result'     => 'files',
                'store'      => 'replace',
                'optimize'   => false,
                'async'      => true,
                'fields'     => array(),
                'defaults'   => array(),
                'surfaces'   => array( 'tab', 'drawer', 'cli' ),
                'icon'       => '',
                'hidden'     => false,
            ),
            $d
        );
        if ( '' === $d['label'] ) {
            $d['label'] = Contract::humanize( substr( $d['id'], (int) strrpos( $d['id'], '.' ) + 1 ) );
        }
        return $d;
    }

    /**
     * The fields of a definition as form-ready specs (names are resolved from the contract; arrays override).
     *
     * @return array<int,array>
     */
    public static function fields( array $def ) {
        $out = array();
        foreach ( $def['fields'] as $item ) {
            $name     = is_array( $item ) ? $item['name'] : $item;
            $override = is_array( $item ) ? $item : array();
            $spec     = Contract::field( $def['id'], $name );
            if ( ! $spec ) {
                $spec = array(
                    'name'     => $name,
                    'type'     => 'text',
                    'label'    => Contract::humanize( $name ),
                    'required' => false,
                    'help'     => '',
                    'default'  => null,
                );
            }
            $spec = array_merge( $spec, $override );
            if ( array_key_exists( $spec['name'], $def['defaults'] ) ) {
                $spec['default'] = $def['defaults'][ $spec['name'] ];
            }
            $out[] = $spec;
        }
        return $out;
    }

    /** Names an operation shows as main options (a property of an object counts for its object). */
    private static function shown_names( array $def ) {
        $have = array();
        foreach ( (array) $def['fields'] as $item ) {
            $name   = is_array( $item ) ? $item['name'] : $item;
            $have[] = $name;
            if ( is_array( $item ) && ! empty( $item['object'] ) ) {
                $have[] = $item['object'];
            }
        }
        return $have;
    }

    /**
     * Every other option the service supports for this operation, so no parameter is left out. New fields in a newer SDK
     * appear here without any change to the plugin. Files are listed separately (see extra_files()).
     *
     * @return array<int,array>
     */
    public static function advanced( array $def ) {
        $have  = self::shown_names( $def );
        $extra = isset( $def['extra'] ) ? array_keys( (array) $def['extra'] ) : array();
        $have  = array_merge( $have, isset( $def['skip'] ) ? (array) $def['skip'] : array() );
        $out   = array();
        foreach ( Contract::request_fields( $def['id'] ) as $field ) {
            $name = $field['name'];
            $type = isset( $field['type'] ) ? $field['type'] : 'string';
            if ( 'file' === $type || in_array( $name, self::SYSTEM_FIELDS, true ) || in_array( $name, $have, true ) || in_array( $name, $extra, true ) || '_url' === substr( $name, -4 ) ) {
                continue;
            }
            $spec = Contract::spec( $field );
            if ( array_key_exists( $spec['name'], $def['defaults'] ) ) {
                $spec['default'] = $def['defaults'][ $spec['name'] ];
            }
            $out[] = $spec;
        }
        return $out;
    }

    /**
     * The extra files an operation can take (a logo, a mask, a second PDF): every file field except the main one.
     *
     * @return array<string,string> field => label
     */
    public static function extra_files( array $def ) {
        $out = isset( $def['extra'] ) ? (array) $def['extra'] : array();
        foreach ( Contract::request_fields( $def['id'] ) as $field ) {
            if ( isset( $field['type'] ) && 'file' === $field['type'] && $field['name'] !== $def['file_field'] && ! isset( $out[ $field['name'] ] ) ) {
                $out[ $field['name'] ] = Contract::humanize( $field['name'] ) . ' ' . __( '(optional)', 'davix-h2i' );
            }
        }
        return $out;
    }

    /** Does a mime type fit what the operation accepts? */
    public static function accepts( array $def, $mime ) {
        if ( 'none' === $def['input'] || 'text' === $def['input'] ) {
            return false;
        }
        foreach ( $def['accepts'] as $prefix ) {
            if ( '' !== $prefix && 0 === strpos( (string) $mime, $prefix ) ) {
                return true;
            }
        }
        return false;
    }
}
