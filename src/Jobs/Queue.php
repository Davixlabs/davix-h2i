<?php
/**
 * The local job queue. Uploads and bulk runs only add rows here; a cron worker does the network work later, so a page
 * request or an upload never waits for the Davix H2I app.
 */

namespace DavixWP\Jobs;

use DavixWP\Core\Installer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Queue {

    const QUEUED         = 'queued';
    const RUNNING        = 'running';
    const WAITING_REMOTE = 'waiting_remote';
    const DONE           = 'done';
    const FAILED         = 'failed';
    const CANCELLED      = 'cancelled';

    const PAUSE_KEY = 'davix_h2i_queue_paused';

    public static function new_batch_id() {
        return 'b' . substr( md5( uniqid( '', true ) ), 0, 12 );
    }

    /**
     * @param array $job operation, object_id, params, source, batch_id, user_id, run_after
     * @return int Job id (0 on failure).
     */
    public static function enqueue( array $job ) {
        global $wpdb;
        $now = current_time( 'mysql', true );
        $row = array(
            'batch_id'   => isset( $job['batch_id'] ) ? (string) $job['batch_id'] : '',
            'user_id'    => isset( $job['user_id'] ) ? (int) $job['user_id'] : get_current_user_id(),
            'object_id'  => isset( $job['object_id'] ) ? (int) $job['object_id'] : 0,
            'operation'  => (string) $job['operation'],
            'source'     => isset( $job['source'] ) ? (string) $job['source'] : 'library',
            'params'     => wp_json_encode( isset( $job['params'] ) ? $job['params'] : array() ),
            'status'     => self::QUEUED,
            'run_after'  => isset( $job['run_after'] ) ? (int) $job['run_after'] : 0,
            'created_at' => $now,
            'updated_at' => $now,
        );
        $ok = $wpdb->insert( Installer::jobs_table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ( ! $ok ) {
            return 0;
        }
        self::kick();
        return (int) $wpdb->insert_id;
    }

    /** Ask WP-Cron to run the queue soon, without waiting for the next minute. */
    public static function kick() {
        if ( ! wp_next_scheduled( 'davix_h2i_run_queue_now' ) ) {
            wp_schedule_single_event( time() + 3, 'davix_h2i_run_queue_now' );
        }
    }

    public static function find( $id ) {
        global $wpdb;
        $table = Installer::jobs_table();
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB
        return $row ? self::decode( $row ) : null;
    }

    public static function update( $id, array $fields ) {
        global $wpdb;
        $fields['updated_at'] = current_time( 'mysql', true );
        if ( isset( $fields['params'] ) && is_array( $fields['params'] ) ) {
            $fields['params'] = wp_json_encode( $fields['params'] );
        }
        return false !== $wpdb->update( Installer::jobs_table(), $fields, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB
    }

    /**
     * Takes up to $limit queued jobs and marks them running (atomically, so two workers never take the same job).
     *
     * @return array[]
     */
    public static function claim( $limit = 1 ) {
        global $wpdb;
        $table = Installer::jobs_table();
        $ids   = (array) $wpdb->get_col( // phpcs:ignore WordPress.DB
            $wpdb->prepare( "SELECT id FROM {$table} WHERE status = %s AND run_after <= %d ORDER BY id ASC LIMIT %d", self::QUEUED, time(), (int) $limit )
        );
        $claimed = array();
        foreach ( $ids as $id ) {
            $updated = $wpdb->query( // phpcs:ignore WordPress.DB
                $wpdb->prepare( "UPDATE {$table} SET status = %s, attempts = attempts + 1, updated_at = %s WHERE id = %d AND status = %s", self::RUNNING, current_time( 'mysql', true ), (int) $id, self::QUEUED )
            );
            if ( $updated ) {
                $job = self::find( $id );
                if ( $job ) {
                    $claimed[] = $job;
                }
            }
        }
        return $claimed;
    }

    /** Jobs waiting for the app to finish them. */
    public static function waiting( $limit = 20 ) {
        global $wpdb;
        $table = Installer::jobs_table();
        $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id ASC LIMIT %d", self::WAITING_REMOTE, (int) $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB
        return array_map( array( __CLASS__, 'decode' ), (array) $rows );
    }

    /** Puts a job that was running for too long back in the queue (a worker that died). */
    public static function recover_stuck( $older_than = 600 ) {
        global $wpdb;
        $table  = Installer::jobs_table();
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - (int) $older_than );
        return (int) $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s WHERE status = %s AND updated_at < %s", self::QUEUED, self::RUNNING, $cutoff ) ); // phpcs:ignore WordPress.DB
    }

    /**
     * @param array $filters status, batch_id, object_id, source
     * @return array{items:array,total:int,page:int,per_page:int}
     */
    public static function query( array $filters = array(), $page = 1, $per_page = 25 ) {
        global $wpdb;
        $table = Installer::jobs_table();
        $parts = array();
        $args  = array();
        foreach ( array( 'status' => '%s', 'batch_id' => '%s', 'object_id' => '%d', 'source' => '%s', 'operation' => '%s' ) as $key => $format ) {
            if ( ! empty( $filters[ $key ] ) ) {
                $parts[] = "{$key} = {$format}";
                $args[]  = $filters[ $key ];
            }
        }
        $where    = $parts ? 'WHERE ' . implode( ' AND ', $parts ) : '';
        $page     = max( 1, (int) $page );
        $per_page = max( 1, min( 200, (int) $per_page ) );
        $total    = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", $args ) ) : $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" ) ); // phpcs:ignore WordPress.DB
        $rows     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d", array_merge( $args, array( $per_page, ( $page - 1 ) * $per_page ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB

        return array(
            'items'    => array_map( array( __CLASS__, 'decode' ), (array) $rows ),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
        );
    }

    /** @return array<string,int> */
    public static function counts( $batch_id = '' ) {
        global $wpdb;
        $table = Installer::jobs_table();
        $sql   = "SELECT status, COUNT(*) AS n FROM {$table}" . ( $batch_id ? $wpdb->prepare( ' WHERE batch_id = %s', $batch_id ) : '' ) . ' GROUP BY status'; // phpcs:ignore WordPress.DB
        $out   = array( 'queued' => 0, 'running' => 0, 'waiting_remote' => 0, 'done' => 0, 'failed' => 0, 'cancelled' => 0 );
        foreach ( (array) $wpdb->get_results( $sql, ARRAY_A ) as $row ) { // phpcs:ignore WordPress.DB
            $out[ $row['status'] ] = (int) $row['n'];
        }
        $out['total'] = array_sum( $out );
        return $out;
    }

    public static function cancel( $id ) {
        global $wpdb;
        $table = Installer::jobs_table();
        return (int) $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, updated_at = %s WHERE id = %d AND status IN (%s, %s)", self::CANCELLED, current_time( 'mysql', true ), (int) $id, self::QUEUED, self::WAITING_REMOTE ) ); // phpcs:ignore WordPress.DB
    }

    public static function cancel_batch( $batch_id ) {
        global $wpdb;
        $table = Installer::jobs_table();
        return (int) $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, updated_at = %s WHERE batch_id = %s AND status = %s", self::CANCELLED, current_time( 'mysql', true ), $batch_id, self::QUEUED ) ); // phpcs:ignore WordPress.DB
    }

    public static function retry_failed( $batch_id = '' ) {
        global $wpdb;
        $table = Installer::jobs_table();
        $sql   = $wpdb->prepare( "UPDATE {$table} SET status = %s, run_after = 0, error_code = '', error_message = '', updated_at = %s WHERE status = %s", self::QUEUED, current_time( 'mysql', true ), self::FAILED ); // phpcs:ignore WordPress.DB
        if ( $batch_id ) {
            $sql .= $wpdb->prepare( ' AND batch_id = %s', $batch_id ); // phpcs:ignore WordPress.DB
        }
        $n = (int) $wpdb->query( $sql ); // phpcs:ignore WordPress.DB
        if ( $n ) {
            self::kick();
        }
        return $n;
    }

    public static function clear_finished() {
        global $wpdb;
        $table = Installer::jobs_table();
        return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE status IN (%s, %s, %s)", self::DONE, self::FAILED, self::CANCELLED ) ); // phpcs:ignore WordPress.DB
    }

    public static function prune( $days = 14 ) {
        global $wpdb;
        $table  = Installer::jobs_table();
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - max( 1, (int) $days ) * DAY_IN_SECONDS );
        return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE status IN (%s, %s, %s) AND updated_at < %s", self::DONE, self::FAILED, self::CANCELLED, $cutoff ) ); // phpcs:ignore WordPress.DB
    }

    // ----------------------------------------------------------------- pause
    public static function pause( $reason, $seconds = 3600 ) {
        set_transient( self::PAUSE_KEY, array( 'reason' => (string) $reason, 'at' => time() ), (int) $seconds );
    }

    public static function resume() {
        delete_transient( self::PAUSE_KEY );
    }

    /** @return array|null reason, at */
    public static function paused() {
        $p = get_transient( self::PAUSE_KEY );
        return is_array( $p ) ? $p : null;
    }

    private static function decode( array $row ) {
        $params        = isset( $row['params'] ) && '' !== $row['params'] ? json_decode( $row['params'], true ) : array();
        $row['params'] = is_array( $params ) ? $params : array();
        $row['id']     = (int) $row['id'];
        $row['object_id']      = (int) $row['object_id'];
        $row['user_id']        = (int) $row['user_id'];
        $row['attempts']       = (int) $row['attempts'];
        $row['remote_job_id']  = isset( $row['remote_job_id'] ) ? (int) $row['remote_job_id'] : 0;
        $row['history_id']     = (int) $row['history_id'];
        return $row;
    }
}
