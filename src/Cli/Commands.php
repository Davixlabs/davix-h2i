<?php

namespace DavixWP\Cli;

use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Capabilities\LimitsService;
use DavixWP\Connection\ConnectionTester;
use DavixWP\History\Repository;
use DavixWP\Jobs\Queue;
use DavixWP\Jobs\Worker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manage Davix H2I from the command line.
 */
final class Commands extends BaseCommand {

    /**
     * Tests the saved API key and shows the plan.
     *
     * ## EXAMPLES
     *     wp davix verify
     */
    public function verify() {
        $r = ConnectionTester::test( null );
        if ( ! $r['ok'] ) {
            \WP_CLI::error( $r['message'] );
        }
        \WP_CLI::success( $r['message'] );
    }

    /**
     * Shows what your plan allows.
     *
     * ## OPTIONS
     * [--refresh]
     * : Fetch fresh data first.
     */
    public function limits( $args, $assoc ) {
        if ( isset( $assoc['refresh'] ) ) {
            LimitsService::refresh();
        }
        $plan = CapabilityGate::plan();
        $a    = CapabilityGate::allowance();
        \WP_CLI::log( 'Plan: ' . ( $plan && isset( $plan['name'] ) ? $plan['name'] : '-' ) );
        if ( $a ) {
            \WP_CLI::log( sprintf( 'Used: %s of %s', isset( $a['used'] ) ? $a['used'] : 0, isset( $a['total'] ) && null !== $a['total'] ? $a['total'] : 'unlimited' ) );
        }
        $rows = array();
        foreach ( \DavixWP\Core\Services::get( 'context' )->operations() as $id => $def ) {
            $rows[] = array( 'operation' => $id, 'state' => CapabilityGate::state( $id ), 'cost' => CapabilityGate::cost( $id ) );
        }
        \WP_CLI\Utils\format_items( 'table', $rows, array( 'operation', 'state', 'cost' ) );
    }

    /**
     * Works with the background queue.
     *
     * ## OPTIONS
     * [<action>]
     * : status, run, retry, resume or clear.
     */
    public function queue( $args ) {
        $action = isset( $args[0] ) ? $args[0] : 'status';
        switch ( $action ) {
            case 'run':
                $s = Worker::run( 50 );
                \WP_CLI::success( wp_json_encode( $s ) );
                break;
            case 'retry':
                \WP_CLI::success( Queue::retry_failed() . ' queued again.' );
                break;
            case 'resume':
                Queue::resume();
                \WP_CLI::success( 'Resumed.' );
                break;
            case 'clear':
                \WP_CLI::success( Queue::clear_finished() . ' removed.' );
                break;
            default:
                \WP_CLI::log( wp_json_encode( Queue::counts() ) );
                $p = Queue::paused();
                if ( $p ) {
                    \WP_CLI::warning( 'Paused: ' . $p['reason'] );
                }
        }
    }

    /**
     * Lists recent history.
     *
     * ## OPTIONS
     * [--limit=<n>]
     * : How many rows. Default 20.
     */
    public function history( $args, $assoc ) {
        $res  = Repository::query( array(), 1, isset( $assoc['limit'] ) ? (int) $assoc['limit'] : 20 );
        $rows = array();
        foreach ( $res['items'] as $r ) {
            $rows[] = array( 'id' => $r['id'], 'when' => $r['created_at'], 'action' => $r['operation'], 'file' => $r['object_title'], 'status' => $r['status'] );
        }
        \WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'when', 'action', 'file', 'status' ) );
    }
}
