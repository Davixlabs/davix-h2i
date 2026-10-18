<?php
/**
 * REST routes owned by the core: connection, plan data, settings, running operations, bulk runs and the job queue.
 * Features add their own routes from their modules.
 */

namespace DavixWP\Rest;

use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Capabilities\LimitsService;
use DavixWP\Connection\ConnectionTester;
use DavixWP\Connection\KeyStore;
use DavixWP\Core\Context;
use DavixWP\Core\Options;
use DavixWP\Jobs\Queue;
use DavixWP\Jobs\Worker;
use DavixWP\Media\Selector;
use DavixWP\Operations\Definition;
use DavixWP\Operations\Executor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CoreRoutes {

    /** @var Context */
    private static $context;

    public static function register( Context $context ) {
        self::$context = $context;

        // ---- connection
        Api::route( '/connection/test', 'POST', array( __CLASS__, 'connection_test' ), 'manage' );
        Api::route( '/connection/save', 'POST', array( __CLASS__, 'connection_save' ), 'manage' );
        Api::route( '/connection/remove', 'POST', array( __CLASS__, 'connection_remove' ), 'manage' );
        Api::route( '/limits', 'GET', array( __CLASS__, 'limits' ) );
        Api::route( '/limits/refresh', 'POST', array( __CLASS__, 'limits_refresh' ) );

        // ---- settings
        Api::route( '/settings/export', 'GET', array( __CLASS__, 'settings_export' ), 'manage' );
        Api::route( '/settings/import', 'POST', array( __CLASS__, 'settings_import' ), 'manage' );
        Api::route( '/settings/(?P<section>[a-z_]+)', 'GET', array( __CLASS__, 'settings_get' ), 'manage' );
        Api::route( '/settings/(?P<section>[a-z_]+)', 'POST', array( __CLASS__, 'settings_save' ), 'manage' );
        Api::route( '/settings/(?P<section>[a-z_]+)/reset', 'POST', array( __CLASS__, 'settings_reset' ), 'manage' );

        // ---- operations
        Api::route( '/run', 'POST', array( __CLASS__, 'run' ) );
        Api::route( '/estimate', 'POST', array( __CLASS__, 'estimate' ) );
        Api::route( '/bulk', 'POST', array( __CLASS__, 'bulk' ) );
        Api::route( '/bulk/scope', 'GET', array( __CLASS__, 'bulk_scope' ) );
        Api::route( '/media/info', 'GET', array( __CLASS__, 'media_info' ) );

        // ---- jobs
        Api::route( '/jobs', 'GET', array( __CLASS__, 'jobs' ) );
        Api::route( '/jobs/summary', 'GET', array( __CLASS__, 'jobs_summary' ) );
        Api::route( '/jobs/run-now', 'POST', array( __CLASS__, 'jobs_run_now' ) );
        Api::route( '/jobs/retry', 'POST', array( __CLASS__, 'jobs_retry' ) );
        Api::route( '/jobs/resume', 'POST', array( __CLASS__, 'jobs_resume' ) );
        Api::route( '/jobs/clear', 'POST', array( __CLASS__, 'jobs_clear' ), 'manage' );
        Api::route( '/jobs/cancel', 'POST', array( __CLASS__, 'jobs_cancel' ) );
    }

    // ------------------------------------------------------------- connection
    public static function connection_test( \WP_REST_Request $r ) {
        $key = (string) ( $r->get_param( 'raw_key' ) ? $r->get_param( 'raw_key' ) : $r->get_param( 'key' ) );
        return ConnectionTester::test( '' !== trim( $key ) ? $key : null );
    }

    public static function connection_save( \WP_REST_Request $r ) {
        $key = trim( (string) $r->get_param( 'key' ) );
        if ( '' !== $key ) {
            $test = ConnectionTester::test( $key );
            if ( ! $test['ok'] ) {
                return $test;
            }
            if ( ! KeyStore::set( $key ) ) {
                return Api::error( __( 'That key has characters that are not allowed.', 'davix-h2i' ) );
            }
            \DavixWP\Connection\ClientFactory::reset();
            LimitsService::flush();
        }
        $result = ConnectionTester::test( null );
        $result['masked'] = KeyStore::masked();
        return $result;
    }

    public static function connection_remove() {
        KeyStore::clear();
        LimitsService::flush();
        \DavixWP\Connection\ClientFactory::reset();
        Options::update( 'connection', array( 'verified_at' => 0, 'plan_name' => '', 'email' => '' ) );
        return array( 'ok' => true );
    }

    public static function limits() {
        $snap = LimitsService::snapshot();
        return array(
            'known'      => CapabilityGate::known(),
            'plan'       => CapabilityGate::plan(),
            'allowance'  => CapabilityGate::allowance(),
            'fetched_at' => $snap['fetched_at'],
            'error'      => $snap['error'],
            'gate'       => CapabilityGate::for_js( array_keys( self::$context->operations() ) ),
        );
    }

    public static function limits_refresh() {
        LimitsService::refresh();
        Queue::resume();
        return self::limits();
    }

    // --------------------------------------------------------------- settings
    public static function settings_get( \WP_REST_Request $r ) {
        $section = (string) $r['section'];
        if ( ! in_array( $section, Options::sections(), true ) ) {
            return Api::error( __( 'Unknown settings section.', 'davix-h2i' ), 404 );
        }
        return Options::section( $section );
    }

    public static function settings_save( \WP_REST_Request $r ) {
        $section = (string) $r['section'];
        if ( ! in_array( $section, Options::sections(), true ) ) {
            return Api::error( __( 'Unknown settings section.', 'davix-h2i' ), 404 );
        }
        $values = $r->get_json_params();
        if ( ! is_array( $values ) ) {
            $values = array();
        }
        if ( isset( $values[ $section ] ) && is_array( $values[ $section ] ) ) {
            $values = $values[ $section ];
        }
        $saved = Options::update( $section, wp_unslash( $values ) );
        \DavixWP\Connection\ClientFactory::reset();
        return array( 'ok' => true, 'settings' => $saved );
    }

    public static function settings_reset( \WP_REST_Request $r ) {
        $section = (string) $r['section'];
        if ( ! in_array( $section, Options::sections(), true ) ) {
            return Api::error( __( 'Unknown settings section.', 'davix-h2i' ), 404 );
        }
        return array( 'ok' => true, 'settings' => Options::reset( $section ) );
    }

    public static function settings_export( \WP_REST_Request $r ) {
        $only = array_filter( array_map( 'sanitize_key', explode( ',', (string) $r->get_param( 'sections' ) ) ) );
        $out = array( 'plugin' => 'davix-h2i', 'version' => DAVIX_H2I_VERSION, 'exported' => gmdate( 'c' ), 'sections' => array() );
        foreach ( Options::sections() as $section ) {
            if ( 'connection' === $section || ( $only && ! in_array( $section, $only, true ) ) ) {
                continue; // the key and server address never leave the site
            }
            $out['sections'][ $section ] = Options::section( $section );
        }
        return $out;
    }

    public static function settings_import( \WP_REST_Request $r ) {
        $data = $r->get_json_params();
        if ( empty( $data['sections'] ) || ! is_array( $data['sections'] ) || ( isset( $data['plugin'] ) && 'davix-h2i' !== $data['plugin'] ) ) {
            return Api::error( __( 'This is not a Davix H2I settings file.', 'davix-h2i' ) );
        }
        $done = array();
        $only = array_filter( array_map( 'sanitize_key', (array) $r->get_param( 'only' ) ) );
        foreach ( $data['sections'] as $section => $values ) {
            if ( 'connection' === $section || ( $only && ! in_array( $section, $only, true ) ) || ! is_array( $values ) || ! in_array( $section, Options::sections(), true ) ) {
                continue;
            }
            Options::replace( $section, $values );
            $done[] = $section;
        }
        return array( 'ok' => true, 'imported' => $done );
    }

    // ------------------------------------------------------------- operations
    private static function check_object( \WP_REST_Request $r ) {
        $ids = array();
        if ( $r->get_param( 'object_id' ) ) {
            $ids[] = (int) $r->get_param( 'object_id' );
        }
        foreach ( (array) $r->get_param( 'object_ids' ) as $id ) {
            $ids[] = (int) $id;
        }
        foreach ( array_unique( array_filter( $ids ) ) as $id ) {
            if ( ! Api::can_edit_attachment( $id ) ) {
                return Api::error( __( 'You are not allowed to change this file.', 'davix-h2i' ), 403, 'rest_forbidden' );
            }
        }
        $extra = (array) $r->get_param( 'extra' );
        foreach ( $extra as $id ) {
            if ( ! current_user_can( 'read_post', (int) $id ) ) {
                return Api::error( __( 'You are not allowed to use this file.', 'davix-h2i' ), 403, 'rest_forbidden' );
            }
        }
        return true;
    }

    /** Runs one operation now (a single file, or no file) and answers with the outcome. */
    public static function run( \WP_REST_Request $r ) {
        $op = (string) $r->get_param( 'operation' );
        if ( ! self::$context->operation( $op ) ) {
            return Api::error( __( 'Unknown action.', 'davix-h2i' ), 404 );
        }
        $ok = self::check_object( $r );
        if ( is_wp_error( $ok ) ) {
            return $ok;
        }
        $params = $r->get_param( 'params' );
        $req    = array(
            'operation'  => $op,
            'object_id'  => (int) $r->get_param( 'object_id' ),
            'object_ids' => array_map( 'intval', (array) $r->get_param( 'object_ids' ) ),
            'extra'      => array_map( 'intval', (array) $r->get_param( 'extra' ) ),
            'params'     => is_array( $params ) ? $params : array(),
            'source'     => self::source( (string) $r->get_param( 'source' ) ),
            'mode'       => (string) $r->get_param( 'mode' ),
            'policy'     => (string) $r->get_param( 'policy' ),
        );
        if ( $r->get_param( 'queue' ) ) {
            $job = Queue::enqueue(
                array(
                    'operation' => $op,
                    'object_id' => $req['object_id'],
                    'params'    => array_merge( $req['params'], array( '_mode' => $req['mode'], '_policy' => $req['policy'], '_object_ids' => $req['object_ids'], '_extra' => $req['extra'] ) ),
                    'source'    => $req['source'],
                )
            );
            return array( 'ok' => (bool) $job, 'queued' => true, 'job_id' => $job );
        }
        $outcome = Executor::run( $req );
        $outcome = self::present( $outcome );
        return $outcome;
    }

    private static function source( $source ) {
        return in_array( $source, array( 'library', 'drawer', 'editor', 'tab', 'tools', 'generator', 'upload', 'bulk', 'cli' ), true ) ? $source : 'tab';
    }

    /** Adds thumbnails and plain URLs the browser can show. */
    public static function present( array $outcome ) {
        foreach ( $outcome['outputs'] as &$out ) {
            if ( ! empty( $out['attachment_id'] ) ) {
                $out['edit_url'] = get_edit_post_link( $out['attachment_id'], 'raw' );
                $thumb           = wp_get_attachment_image_url( $out['attachment_id'], 'thumbnail' );
                if ( $thumb ) {
                    $out['thumb'] = $thumb;
                }
            }
        }
        unset( $out );
        if ( isset( $outcome['data'] ) && strlen( (string) wp_json_encode( $outcome['data'] ) ) > 60000 ) {
            unset( $outcome['data'] );
        }
        return $outcome;
    }

    public static function estimate( \WP_REST_Request $r ) {
        $op    = (string) $r->get_param( 'operation' );
        $def   = self::$context->operation( $op );
        $count = max( 1, (int) $r->get_param( 'count' ) );
        if ( ! $def ) {
            return Api::error( __( 'Unknown action.', 'davix-h2i' ), 404 );
        }
        $cost      = CapabilityGate::cost( $op );
        $allowance = CapabilityGate::allowance();
        $left      = null;
        if ( $allowance && isset( $allowance['total'], $allowance['used'] ) && null !== $allowance['total'] ) {
            $left = max( 0, (float) $allowance['total'] - (float) $allowance['used'] );
        }
        $units = $cost * $count;
        return array(
            'operation' => $op,
            'count'     => $count,
            'cost_each' => $cost,
            'units'     => $units,
            'left'      => $left,
            'fits'      => null === $left || $units <= $left || ( isset( $allowance['overage'] ) && 'allow' === $allowance['overage'] ),
            'state'     => CapabilityGate::state( $op ),
        );
    }

    /** Queues one job per file for a bulk run. */
    public static function bulk( \WP_REST_Request $r ) {
        $op  = (string) $r->get_param( 'operation' );
        $def = self::$context->operation( $op );
        if ( ! $def ) {
            return Api::error( __( 'Unknown action.', 'davix-h2i' ), 404 );
        }
        if ( ! CapabilityGate::allows( $op ) ) {
            return Api::error( __( 'Your plan does not include this feature.', 'davix-h2i' ), 403, 'endpoint_not_allowed' );
        }
        $ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $r->get_param( 'ids' ) ) ) ) );
        if ( ! $ids ) {
            return Api::error( __( 'Choose at least one file first.', 'davix-h2i' ) );
        }
        $params = $r->get_param( 'params' );
        $params = is_array( $params ) ? $params : array();
        $batch  = Queue::new_batch_id();
        $queued = 0;
        $skipped = 0;
        foreach ( array_slice( $ids, 0, 5000 ) as $id ) {
            if ( ! Api::can_edit_attachment( $id ) ) {
                $skipped++;
                continue;
            }
            $info = Selector::info( $id );
            if ( ! $info || ! Definition::accepts( $def, $info['mime'] ) ) {
                $skipped++;
                continue;
            }
            Queue::enqueue(
                array(
                    'operation' => $op,
                    'object_id' => $id,
                    'params'    => array_merge( $params, array( '_mode' => (string) $r->get_param( 'mode' ), '_policy' => (string) $r->get_param( 'policy' ) ) ),
                    'source'    => 'bulk',
                    'batch_id'  => $batch,
                )
            );
            $queued++;
        }
        return array( 'ok' => true, 'batch_id' => $batch, 'queued' => $queued, 'skipped' => $skipped, 'units' => $queued * CapabilityGate::cost( $op ) );
    }

    /** Which files a bulk run would touch: all of a kind, those not done yet, or those without alt text. */
    public static function bulk_scope( \WP_REST_Request $r ) {
        $op  = (string) $r->get_param( 'operation' );
        $def = self::$context->operation( $op );
        if ( ! $def ) {
            return Api::error( __( 'Unknown action.', 'davix-h2i' ), 404 );
        }
        $filter = (string) $r->get_param( 'filter' );
        $args   = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 5000,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        );
        $mimes = array();
        foreach ( $def['accepts'] as $prefix ) {
            $mimes[] = rtrim( $prefix, '/' ) === $prefix ? $prefix : $prefix;
        }
        $args['post_mime_type'] = array_map(
            function ( $p ) {
                return '/' === substr( $p, -1 ) ? rtrim( $p, '/' ) : $p;
            },
            $mimes
        );
        $meta = array();
        if ( 'missing_alt' === $filter ) {
            $meta[] = array( 'relation' => 'OR', array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ), array( 'key' => '_wp_attachment_image_alt', 'value' => '' ) );
        } elseif ( 'not_done' === $filter ) {
            $meta[] = array( 'key' => '_davix_h2i_state', 'compare' => 'NOT EXISTS' );
        }
        if ( $meta ) {
            $args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery
        }
        if ( $r->get_param( 'from' ) ) {
            $args['date_query'] = array( array( 'after' => sanitize_text_field( $r->get_param( 'from' ) ), 'inclusive' => true ) );
        }
        $ids = get_posts( $args );
        $ids = array_values( array_filter( $ids, array( 'DavixWP\Rest\Api', 'can_edit_attachment' ) ) );
        return array(
            'ids'   => $ids,
            'count' => count( $ids ),
            'units' => count( $ids ) * CapabilityGate::cost( $op ),
        );
    }

    public static function media_info( \WP_REST_Request $r ) {
        $ids = array_filter( array_map( 'intval', explode( ',', (string) $r->get_param( 'ids' ) ) ) );
        $out = array();
        $defs = self::$context->operations();
        foreach ( array_slice( $ids, 0, 200 ) as $id ) {
            $info = Selector::info( $id );
            if ( ! $info || ! current_user_can( 'read_post', $id ) ) {
                continue;
            }
            $thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
            $out[] = array(
                'id'       => $id,
                'title'    => $info['title'],
                'filename' => $info['filename'],
                'mime'     => $info['mime'],
                'size'     => $info['size'],
                'thumb'    => $thumb ? $thumb : '',
                'ops'      => Selector::operations_for( $id, $defs ),
            );
        }
        return array( 'items' => $out );
    }

    // ------------------------------------------------------------------- jobs
    public static function jobs( \WP_REST_Request $r ) {
        $q = Queue::query(
            array(
                'status'   => (string) $r->get_param( 'status' ),
                'batch_id' => (string) $r->get_param( 'batch_id' ),
            ),
            (int) $r->get_param( 'page' ),
            (int) ( $r->get_param( 'per_page' ) ? $r->get_param( 'per_page' ) : 25 )
        );
        foreach ( $q['items'] as &$job ) {
            $job['title'] = $job['object_id'] ? get_the_title( $job['object_id'] ) : '';
            unset( $job['params'] );
        }
        return $q;
    }

    public static function jobs_summary( \WP_REST_Request $r ) {
        $paused = Queue::paused();
        return array( 'counts' => Queue::counts( (string) $r->get_param( 'batch_id' ) ), 'paused' => $paused ? $paused['reason'] : '' );
    }

    public static function jobs_run_now() {
        $stats = Worker::run( 20 );
        return array( 'stats' => $stats, 'counts' => Queue::counts() );
    }

    public static function jobs_retry( \WP_REST_Request $r ) {
        return array( 'retried' => Queue::retry_failed( (string) $r->get_param( 'batch_id' ) ) );
    }

    public static function jobs_resume() {
        Queue::resume();
        Queue::kick();
        return array( 'ok' => true );
    }

    public static function jobs_clear() {
        return array( 'cleared' => Queue::clear_finished() );
    }

    public static function jobs_cancel( \WP_REST_Request $r ) {
        if ( $r->get_param( 'batch_id' ) ) {
            return array( 'cancelled' => Queue::cancel_batch( (string) $r->get_param( 'batch_id' ) ) );
        }
        return array( 'cancelled' => Queue::cancel( (int) $r->get_param( 'id' ) ) );
    }
}
