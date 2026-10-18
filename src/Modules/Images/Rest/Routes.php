<?php

namespace DavixWP\Modules\Images\Rest;

use DavixWP\Jobs\Queue;
use DavixWP\Modules\Images\Chips;
use DavixWP\Operations\Executor;
use DavixWP\Rest\Api;
use DavixWP\Rest\CoreRoutes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Routes {

    public function register() {
        Api::route( '/images/run-chips', 'POST', array( $this, 'run_chips' ) );
    }

    /** Runs the chosen chips on the chosen images: one chip uses its endpoint, several use one pipeline call. */
    public function run_chips( \WP_REST_Request $r ) {
        $ids    = array_values( array_unique( array_filter( array_map( 'intval', (array) $r->get_param( 'ids' ) ) ) ) );
        $chips  = (array) $r->get_param( 'chips' );
        $fields = (array) $r->get_param( 'fields' );
        $mode   = (string) $r->get_param( 'mode' );
        if ( ! $ids ) {
            return Api::error( __( 'Choose at least one file first.', 'davix-h2i' ) );
        }
        $plan = Chips::build( $chips, $fields );
        if ( ! $plan ) {
            return Api::error( __( 'Add at least one chip first.', 'davix-h2i' ) );
        }
        foreach ( $ids as $id ) {
            if ( ! Api::can_edit_attachment( $id ) ) {
                return Api::error( __( 'You are not allowed to change this file.', 'davix-h2i' ), 403, 'rest_forbidden' );
            }
        }
        // A few files run now so you see the result; more are queued so the screen never hangs.
        if ( count( $ids ) > 3 || $r->get_param( 'queue' ) ) {
            $batch = Queue::new_batch_id();
            foreach ( $ids as $id ) {
                Queue::enqueue( array( 'operation' => $plan['operation'], 'object_id' => $id, 'params' => array_merge( $plan['params'], array( '_mode' => $mode ), ! empty( $plan['then'] ) ? array( '_then' => $plan['then'] ) : array() ), 'source' => 'drawer', 'batch_id' => $batch ) );
            }
            return array( 'queued' => count( $ids ), 'batch_id' => $batch );
        }
        // A chosen logo is a file (_extra), not a setting.
        $extra  = isset( $plan['params']['_extra'] ) ? (array) $plan['params']['_extra'] : array();
        $params = $plan['params'];
        unset( $params['_extra'] );
        $out = array();
        foreach ( $ids as $id ) {
            $outcome = Executor::run( array( 'operation' => $plan['operation'], 'object_id' => $id, 'params' => $params, 'extra' => $extra, 'mode' => $mode, 'source' => 'drawer' ) );
            if ( ! empty( $outcome['ok'] ) && ! empty( $plan['then'] ) ) {
                $next    = ! empty( $outcome['attachment_ids'] ) ? (int) $outcome['attachment_ids'][0] : $id;
                $outcome = Executor::run( array( 'operation' => $plan['then']['operation'], 'object_id' => $next, 'params' => $plan['then']['params'], 'mode' => 'replace', 'source' => 'drawer' ) );
            }
            $out[]   = array_merge( CoreRoutes::present( $outcome ), array( 'object_id' => $id, 'title' => get_the_title( $id ) ) );
        }
        return array( 'results' => $out, 'operation' => $plan['operation'] );
    }
}
