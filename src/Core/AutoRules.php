<?php
/**
 * Automatic rules: "when a new file of this kind is uploaded, run this action with these settings". One list for the whole
 * plugin, one engine, one hook. Modules only say which actions can be automatic (Context::add_auto_action); the core never
 * names a module.
 *
 * A rule: id, action, enabled, types (mime types), min_kb, generated (also files this plugin made), mode (replace|copy|""),
 * params (the action's own settings).
 */

namespace DavixWP\Core;

use DavixWP\Connection\KeyStore;
use DavixWP\Jobs\Queue;
use DavixWP\Media\Guard;
use DavixWP\Media\StateMarker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AutoRules {

    const SECTION = 'auto';
    const MAX     = 40;

    public static function register_settings() {
        Options::register( self::SECTION, array( 'rules' => array() ), array( __CLASS__, 'sanitize' ) );
    }

    public static function hooks() {
        add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'on_metadata' ), 20, 2 );
        add_action( 'transition_post_status', array( __CLASS__, 'on_publish' ), 20, 3 );
        add_action( 'init', array( __CLASS__, 'maybe_migrate' ), 20 );
    }

    // ------------------------------------------------------------------ storage
    /** @return array<int,array> */
    public static function rules() {
        $rules = Options::get( self::SECTION . '.rules', array() );
        return is_array( $rules ) ? array_values( $rules ) : array();
    }

    /** Replaces the whole list with sanitized rules and returns what was saved. */
    public static function save( array $rules ) {
        $saved = Options::update( self::SECTION, array( 'rules' => $rules ) );
        return isset( $saved['rules'] ) ? $saved['rules'] : array();
    }

    public static function new_id() {
        return 'r' . substr( md5( uniqid( '', true ) ), 0, 8 );
    }

    public static function sanitize( array $v, array $d ) {
        $out   = array();
        $seen  = array();
        $known = self::action_ids();
        foreach ( array_slice( isset( $v['rules'] ) && is_array( $v['rules'] ) ? $v['rules'] : array(), 0, self::MAX ) as $r ) {
            if ( ! is_array( $r ) || empty( $r['action'] ) ) {
                continue;
            }
            $action = (string) preg_replace( '/[^A-Za-z0-9._\-]/', '', (string) $r['action'] );
            if ( '' === $action || ( $known && ! in_array( $action, $known, true ) ) ) {
                continue;
            }
            $id = isset( $r['id'] ) ? sanitize_key( $r['id'] ) : '';
            if ( '' === $id || isset( $seen[ $id ] ) ) {
                $id = self::new_id();
            }
            $seen[ $id ] = true;
            $def         = self::action( $action );
            $on_publish  = $def && 'publish' === $def['trigger'];
            $types       = array();
            foreach ( (array) ( isset( $r['types'] ) ? $r['types'] : array() ) as $t ) {
                $t = strtolower( trim( (string) $t ) );
                if ( $on_publish ) {
                    $t = sanitize_key( $t );
                    if ( '' !== $t && ( ! function_exists( 'post_type_exists' ) || post_type_exists( $t ) ) ) {
                        $types[] = $t;
                    }
                } elseif ( preg_match( '#^[a-z0-9.+\-]+/[a-z0-9.+\-*]*$#', $t ) ) {
                    $types[] = $t;
                }
            }
            $params = isset( $r['params'] ) && is_array( $r['params'] ) ? $r['params'] : array();
            $mode  = isset( $r['mode'] ) && in_array( $r['mode'], array( 'replace', 'copy' ), true ) ? $r['mode'] : '';
            $out[] = array(
                'id'        => $id,
                'action'    => $action,
                'enabled'   => ! isset( $r['enabled'] ) || ! empty( $r['enabled'] ),
                'types'     => array_values( array_unique( $types ) ),
                'min_kb'    => max( 0, (int) ( isset( $r['min_kb'] ) ? $r['min_kb'] : 0 ) ),
                'generated' => ! empty( $r['generated'] ),
                'mode'      => $mode,
                'params'    => $def && is_callable( $def['sanitize'] ) ? (array) call_user_func( $def['sanitize'], $params ) : self::clean_params( $params ),
            );
        }
        return array( 'rules' => $out );
    }

    private static function clean_params( array $params, $depth = 0 ) {
        $out = array();
        foreach ( $params as $k => $v ) {
            $key = is_int( $k ) ? $k : (string) preg_replace( '/[^A-Za-z0-9_.\-]/', '', (string) $k );
            if ( '' === (string) $key ) {
                continue;
            }
            if ( is_array( $v ) ) {
                if ( $depth < 4 ) {
                    $out[ $key ] = self::clean_params( $v, $depth + 1 );
                }
            } elseif ( is_bool( $v ) || is_int( $v ) || is_float( $v ) ) {
                $out[ $key ] = $v;
            } elseif ( is_scalar( $v ) ) {
                $out[ $key ] = sanitize_textarea_field( (string) $v );
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ actions
    private static function context() {
        return Services::has( 'context' ) ? Services::get( 'context' ) : null;
    }

    /** @return string[] */
    public static function action_ids() {
        $c = self::context();
        return $c ? array_keys( $c->auto_actions() ) : array();
    }

    public static function action( $id ) {
        $c = self::context();
        $a = $c ? $c->auto_actions() : array();
        return isset( $a[ $id ] ) ? $a[ $id ] : null;
    }

    // ------------------------------------------------------------------ engine
    /** New uploads only add rows to the queue: nothing here talks to the network, so an upload never waits. */
    public static function on_metadata( $metadata, $attachment_id ) {
        if ( Guard::active() || ! KeyStore::has() ) {
            return $metadata;
        }
        $mime = (string) get_post_mime_type( $attachment_id );
        if ( '' === $mime ) {
            return $metadata;
        }
        $generated = StateMarker::is_generated( $attachment_id );
        $file      = get_attached_file( $attachment_id );
        $size_kb   = $file && is_file( $file ) ? filesize( $file ) / 1024 : 0;
        $user      = get_current_user_id();
        $todo      = array();
        foreach ( self::rules() as $i => $rule ) {
            $action = self::action( $rule['action'] );
            if ( ! $action || empty( $rule['enabled'] ) || 'upload' !== $action['trigger'] ) {
                continue;
            }
            if ( $generated && empty( $rule['generated'] ) ) {
                continue;
            }
            if ( ! self::mime_matches( $rule, $action, $mime ) || $size_kb < (int) $rule['min_kb'] ) {
                continue;
            }
            if ( is_callable( $action['skip'] ) && call_user_func( $action['skip'], $attachment_id, $rule['params'] ) ) {
                continue;
            }
            $plan = is_callable( $action['plan'] )
                ? call_user_func( $action['plan'], $rule['params'], $attachment_id )
                : array( 'operation' => $rule['action'], 'params' => $rule['params'] );
            if ( ! $plan || empty( $plan['operation'] ) ) {
                continue;
            }
            $todo[] = array( 'order' => (int) $action['order'] * 100 + $i, 'rule' => $rule, 'plan' => $plan );
        }
        usort( $todo, function ( $a, $b ) {
            return $a['order'] - $b['order'];
        } );
        foreach ( $todo as $t ) {
            $params = isset( $t['plan']['params'] ) ? (array) $t['plan']['params'] : array();
            if ( ! empty( $t['plan']['then'] ) ) {
                $params['_then'] = $t['plan']['then']; // a second step on the result (for example removing location data)
            }
            if ( StateMarker::is_done( $attachment_id, $t['plan']['operation'], self::without_internal( $params ) ) ) {
                continue;
            }
            if ( '' !== $t['rule']['mode'] ) {
                $params['_mode'] = $t['rule']['mode'];
            }
            Queue::enqueue(
                array(
                    'operation' => $t['plan']['operation'],
                    'object_id' => (int) $attachment_id,
                    'params'    => $params,
                    'source'    => 'upload',
                    'user_id'   => $user,
                )
            );
        }
        return $metadata;
    }

    /** Publishing a post: the rules that run on publish (a social card ...) add their jobs to the queue. Nothing waits on the network here. */
    public static function on_publish( $new, $old, $post ) {
        if ( 'publish' !== $new || 'publish' === $old || ! KeyStore::has() || ! $post ) {
            return;
        }
        foreach ( self::rules() as $rule ) {
            $action = self::action( $rule['action'] );
            if ( ! $action || empty( $rule['enabled'] ) || 'publish' !== $action['trigger'] ) {
                continue;
            }
            $types = ! empty( $rule['types'] ) ? $rule['types'] : array( 'post' );
            if ( ! in_array( $post->post_type, $types, true ) || ! is_callable( $action['plan'] ) ) {
                continue;
            }
            $plan = call_user_func( $action['plan'], $rule['params'], (int) $post->ID );
            if ( ! $plan || empty( $plan['operation'] ) ) {
                continue;
            }
            Queue::enqueue(
                array(
                    'operation' => $plan['operation'],
                    'object_id' => isset( $plan['object_id'] ) ? (int) $plan['object_id'] : 0,
                    'params'    => isset( $plan['params'] ) ? (array) $plan['params'] : array(),
                    'source'    => 'publish',
                    'user_id'   => (int) $post->post_author,
                )
            );
        }
    }

    private static function without_internal( array $params ) {
        foreach ( array_keys( $params ) as $k ) {
            if ( is_string( $k ) && '_' === substr( $k, 0, 1 ) ) {
                unset( $params[ $k ] );
            }
        }
        return $params;
    }

    public static function mime_matches( array $rule, array $action, $mime ) {
        if ( ! empty( $rule['types'] ) ) {
            return in_array( $mime, $rule['types'], true );
        }
        foreach ( (array) $action['accepts'] as $prefix ) {
            if ( '' !== $prefix && 0 === strpos( $mime, $prefix ) ) {
                return true;
            }
        }
        return false;
    }

    // --------------------------------------------------------- legacy settings
    /**
     * Earlier versions had a separate upload switch in every module (optimize images, strip location, watermark, alt text,
     * compress PDFs ...). They become rules, once, and the old values are removed.
     */
    public static function maybe_migrate() {
        if ( get_option( 'davix_h2i_auto_migrated' ) ) {
            return;
        }
        $rules  = array();
        $images = get_option( 'davix_h2i_images' );
        $ai     = get_option( 'davix_h2i_ai' );
        $pdf    = get_option( 'davix_h2i_pdf' );
        $mode   = is_array( $images ) && isset( $images['output']['mode'] ) && 'copy' === $images['output']['mode'] ? 'copy' : 'replace';

        if ( is_array( $images ) ) {
            $a = isset( $images['auto'] ) && is_array( $images['auto'] ) ? $images['auto'] : array();
            $w = isset( $images['watermark'] ) && is_array( $images['watermark'] ) ? $images['watermark'] : array();
            $chips  = ! empty( $a['enabled'] ) && ! empty( $a['chips'] ) ? array_values( (array) $a['chips'] ) : array();
            $fields = isset( $a['fields'] ) && is_array( $a['fields'] ) ? $a['fields'] : array();
            $p = isset( $images['privacy'] ) && is_array( $images['privacy'] ) ? $images['privacy'] : array();
            if ( ! empty( $p['strip_gps'] ) || ! empty( $p['author'] ) || ! empty( $p['copyright'] ) ) {
                array_unshift( $chips, 'privacy' );
                $fields['privacy'] = array_filter( array( 'strip_gps' => ! empty( $p['strip_gps'] ), 'author' => isset( $p['author'] ) ? (string) $p['author'] : '', 'copyright' => isset( $p['copyright'] ) ? (string) $p['copyright'] : '' ), function ( $x ) {
                    return '' !== $x && false !== $x;
                } );
            }
            if ( ! empty( $w['on_upload'] ) ) {
                $chips[] = 'watermark';
                $fields['watermark'] = array_filter(
                    array(
                        'text'     => isset( $w['text'] ) ? $w['text'] : '',
                        'position' => isset( $w['position'] ) ? $w['position'] : '',
                        'opacity'  => isset( $w['opacity'] ) ? $w['opacity'] : '',
                        'margin'   => isset( $w['margin'] ) ? $w['margin'] : '',
                        'fontSize' => isset( $w['font_size'] ) ? $w['font_size'] : '',
                        'color'    => isset( $w['color'] ) ? $w['color'] : '',
                    ),
                    function ( $x ) {
                        return '' !== $x && null !== $x;
                    }
                );
            }
            if ( $chips ) {
                $rules[] = array(
                    'action'    => 'images.chips',
                    'enabled'   => true,
                    'types'     => ! empty( $a['types'] ) ? array_values( (array) $a['types'] ) : array( 'image/jpeg', 'image/png', 'image/webp' ),
                    'min_kb'    => isset( $a['min_kb'] ) ? (int) $a['min_kb'] : 0,
                    'generated' => ! isset( $a['apply_to_generated'] ) || ! empty( $a['apply_to_generated'] ),
                    'mode'      => $mode,
                    'params'    => array( 'chips' => array_values( array_unique( array_filter( $chips ) ) ), 'fields' => $fields ),
                );
            }
        }
        $render = get_option( 'davix_h2i_render' );
        if ( is_array( $render ) && ! empty( $render['social']['enabled'] ) ) {
            $rules[] = array(
                'action'  => 'render.social-card',
                'enabled' => true,
                'types'   => ! empty( $render['social']['post_types'] ) ? array_values( (array) $render['social']['post_types'] ) : array( 'post' ),
                'params'  => array_filter( array( 'html' => isset( $render['html'] ) ? (string) $render['html'] : '', 'css' => isset( $render['css'] ) ? (string) $render['css'] : '', 'width' => isset( $render['width'] ) ? (int) $render['width'] : 0, 'height' => isset( $render['height'] ) ? (int) $render['height'] : 0, 'format' => isset( $render['format'] ) ? (string) $render['format'] : '', 'set_featured' => ! empty( $render['social']['set_featured'] ) ) ),
            );
        }
        if ( is_array( $ai ) && isset( $ai['upload'] ) && is_array( $ai['upload'] ) ) {
            if ( ! empty( $ai['upload']['alt'] ) ) {
                $rules[] = array( 'action' => 'ai.content.alt-text', 'enabled' => true, 'types' => array(), 'params' => array() );
            }
            if ( ! empty( $ai['upload']['moderate'] ) ) {
                $rules[] = array( 'action' => 'ai.images.moderate', 'enabled' => true, 'types' => array(), 'params' => array() );
            }
        }
        if ( is_array( $pdf ) ) {
            $o = isset( $pdf['output']['mode'] ) && 'copy' === $pdf['output']['mode'] ? 'copy' : 'replace';
            if ( ! empty( $pdf['optimize']['on_upload'] ) ) {
                $rules[] = array( 'action' => 'pdfs.optimize', 'enabled' => true, 'types' => array( 'application/pdf' ), 'mode' => $o, 'params' => array( 'profile' => isset( $pdf['optimize']['profile'] ) ? (string) $pdf['optimize']['profile'] : 'ebook' ) );
            }
            if ( ! empty( $pdf['thumbnails']['on_upload'] ) ) {
                $rules[] = array(
                    'action'  => 'pdfs.to-images',
                    'enabled' => true,
                    'types'   => array( 'application/pdf' ),
                    'params'  => array_filter(
                        array(
                            'pages'  => isset( $pdf['thumbnails']['pages'] ) ? (string) $pdf['thumbnails']['pages'] : '1',
                            'format' => isset( $pdf['thumbnails']['format'] ) ? (string) $pdf['thumbnails']['format'] : 'jpeg',
                            'width'  => isset( $pdf['thumbnails']['width'] ) ? (int) $pdf['thumbnails']['width'] : 0,
                        )
                    ),
                );
            }
        }
        if ( $rules && ! self::rules() ) {
            // Settings are read as saved: the actions are registered by now (init), so the rules pass the sanitizer.
            self::save( $rules );
        }
        // The old switches are gone from every module.
        foreach ( array( 'davix_h2i_images' => array( 'auto', 'privacy', 'watermark', 'defaults', 'sizes' ), 'davix_h2i_ai' => array( 'upload' ), 'davix_h2i_pdf' => array( 'optimize', 'thumbnails' ), 'davix_h2i_render' => array( 'html', 'css', 'width', 'height', 'format', 'social' ) ) as $option => $keys ) {
            $raw = get_option( $option );
            if ( is_array( $raw ) ) {
                foreach ( $keys as $k ) {
                    unset( $raw[ $k ] );
                }
                update_option( $option, $raw, false );
            }
        }
        update_option( 'davix_h2i_auto_migrated', 1, false );
        Options::flush_cache();
    }
}
