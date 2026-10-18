<?php
/**
 * The plugin's own history: one row for everything the plugin did with the Davix H2I app on this site
 * (library actions, uploads, editor panels, generators, bulk runs). Studio and direct API calls never appear here
 * because only the plugin writes this table.
 */

namespace DavixWP\History;

use DavixWP\Core\Installer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Repository {

    const STATUSES = array( 'success', 'failed', 'pending_review', 'restored', 'rejected' );

    /** @return int The new row id (0 on failure). */
    public static function insert( array $row ) {
        global $wpdb;
        $row = array_merge(
            array(
                'user_id'       => get_current_user_id(),
                'created_at'    => current_time( 'mysql', true ),
                'operation'     => '',
                'source'        => 'library',
                'object_id'     => 0,
                'object_title'  => '',
                'status'        => 'success',
                'request_id'    => '',
                'file_ids'      => array(),
                'params'        => array(),
                'result'        => array(),
                'bytes_before'  => 0,
                'bytes_after'   => 0,
                'error_code'    => '',
                'error_message' => '',
                'batch_id'      => '',
            ),
            $row
        );
        foreach ( array( 'file_ids', 'params', 'result' ) as $json_key ) {
            if ( is_array( $row[ $json_key ] ) ) {
                $row[ $json_key ] = wp_json_encode( $row[ $json_key ] );
            }
        }
        $ok = $wpdb->insert( Installer::history_table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $ok ? (int) $wpdb->insert_id : 0;
    }

    public static function update( $id, array $fields ) {
        global $wpdb;
        foreach ( array( 'file_ids', 'params', 'result' ) as $json_key ) {
            if ( isset( $fields[ $json_key ] ) && is_array( $fields[ $json_key ] ) ) {
                $fields[ $json_key ] = wp_json_encode( $fields[ $json_key ] );
            }
        }
        return false !== $wpdb->update( Installer::history_table(), $fields, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    public static function find( $id ) {
        global $wpdb;
        $table = Installer::history_table();
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB
        return $row ? self::decode( $row ) : null;
    }

    /**
     * @param array $filters operation, group, status, source, object_id, search, from, to, user_id
     * @return array{items:array,total:int,page:int,per_page:int}
     */
    public static function query( array $filters = array(), $page = 1, $per_page = 20, $order = 'DESC' ) {
        global $wpdb;
        $table = Installer::history_table();
        list( $where, $args ) = self::where( $filters );
        $page     = max( 1, (int) $page );
        $per_page = max( 1, min( 200, (int) $per_page ) );
        $offset   = ( $page - 1 ) * $per_page;
        $order    = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

        $sql_total = "SELECT COUNT(*) FROM {$table} {$where}";
        $total     = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $sql_total, $args ) ) : $wpdb->get_var( $sql_total ) ); // phpcs:ignore WordPress.DB

        $sql  = "SELECT * FROM {$table} {$where} ORDER BY created_at {$order}, id {$order} LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $args, array( $per_page, $offset ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB

        return array(
            'items'    => array_map( array( __CLASS__, 'decode' ), (array) $rows ),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $per_page,
        );
    }

    /** Every row matching the filters, for the CSV export (capped). */
    public static function export_rows( array $filters = array(), $limit = 5000 ) {
        $result = self::query( $filters, 1, min( 5000, (int) $limit ) );
        return $result['items'];
    }

    /** Counts per status for the stat cards. */
    public static function stats( array $filters = array() ) {
        global $wpdb;
        $table = Installer::history_table();
        list( $where, $args ) = self::where( $filters );
        $sql  = "SELECT status, COUNT(*) AS n, COALESCE(SUM(CASE WHEN bytes_after > 0 AND bytes_before > bytes_after THEN bytes_before - bytes_after ELSE 0 END),0) AS saved FROM {$table} {$where} GROUP BY status";
        $rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB
        $out  = array( 'total' => 0, 'success' => 0, 'failed' => 0, 'pending_review' => 0, 'saved_bytes' => 0 );
        foreach ( (array) $rows as $row ) {
            $out['total']      += (int) $row['n'];
            $out[ $row['status'] ] = (int) $row['n'];
            if ( 'success' === $row['status'] ) {
                $out['saved_bytes'] += (int) $row['saved'];
            }
        }
        return $out;
    }

    public static function delete( $id ) {
        global $wpdb;
        return (bool) $wpdb->delete( Installer::history_table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB
    }

    public static function clear() {
        global $wpdb;
        $table = Installer::history_table();
        return (int) $wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore WordPress.DB
    }

    /** Removes rows older than the retention window (0 days = keep everything). */
    public static function prune( $days ) {
        global $wpdb;
        $days = (int) $days;
        if ( $days <= 0 ) {
            return 0;
        }
        $table  = Installer::history_table();
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
        return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB
    }

    /** Distinct operations used, for the filter dropdown. */
    public static function operations_used() {
        global $wpdb;
        $table = Installer::history_table();
        return (array) $wpdb->get_col( "SELECT DISTINCT operation FROM {$table} ORDER BY operation" ); // phpcs:ignore WordPress.DB
    }

    private static function where( array $f ) {
        global $wpdb;
        $parts = array();
        $args  = array();
        if ( ! empty( $f['operation'] ) ) {
            $parts[] = 'operation = %s';
            $args[]  = $f['operation'];
        }
        if ( ! empty( $f['group'] ) ) {
            $parts[] = 'operation LIKE %s';
            $args[]  = $wpdb->esc_like( $f['group'] ) . '.%';
        }
        if ( ! empty( $f['status'] ) ) {
            $parts[] = 'status = %s';
            $args[]  = $f['status'];
        }
        if ( ! empty( $f['source'] ) ) {
            $parts[] = 'source = %s';
            $args[]  = $f['source'];
        }
        if ( ! empty( $f['object_id'] ) ) {
            $parts[] = 'object_id = %d';
            $args[]  = (int) $f['object_id'];
        }
        if ( ! empty( $f['user_id'] ) ) {
            $parts[] = 'user_id = %d';
            $args[]  = (int) $f['user_id'];
        }
        if ( ! empty( $f['from'] ) ) {
            $parts[] = 'created_at >= %s';
            $args[]  = gmdate( 'Y-m-d 00:00:00', strtotime( $f['from'] ) );
        }
        if ( ! empty( $f['to'] ) ) {
            $parts[] = 'created_at <= %s';
            $args[]  = gmdate( 'Y-m-d 23:59:59', strtotime( $f['to'] ) );
        }
        if ( ! empty( $f['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $f['search'] ) . '%';
            $parts[] = '(object_title LIKE %s OR request_id LIKE %s OR operation LIKE %s OR error_message LIKE %s)';
            array_push( $args, $like, $like, $like, $like );
        }
        return array( $parts ? 'WHERE ' . implode( ' AND ', $parts ) : '', $args );
    }

    private static function decode( array $row ) {
        foreach ( array( 'file_ids', 'params', 'result' ) as $json_key ) {
            $decoded         = isset( $row[ $json_key ] ) && '' !== $row[ $json_key ] ? json_decode( $row[ $json_key ], true ) : null;
            $row[ $json_key ] = is_array( $decoded ) ? $decoded : array();
        }
        $row['id']           = (int) $row['id'];
        $row['user_id']      = (int) $row['user_id'];
        $row['object_id']    = (int) $row['object_id'];
        $row['bytes_before'] = (int) $row['bytes_before'];
        $row['bytes_after']  = (int) $row['bytes_after'];
        return $row;
    }
}
