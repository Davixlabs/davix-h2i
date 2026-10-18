<?php

namespace DavixWP\Modules\History\Rest;

use DavixWP\Connection\ClientFactory;
use DavixWP\Core\Services;
use DavixWP\History\Repository;
use DavixWP\Media\BackupStore;
use DavixWP\Media\Selector;
use DavixWP\Media\TextWriter;
use DavixWP\Rest\Api;
use DavixWP\Support\ParamSummary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Routes {

    public function register() {
        Api::route( '/history', 'GET', array( $this, 'index' ) );
        Api::route( '/history/export', 'GET', array( $this, 'export' ) );
        Api::route( '/history/clear', 'POST', array( $this, 'clear' ), 'manage' );
        Api::route( '/history/(?P<id>\d+)', 'GET', array( $this, 'show' ) );
        Api::route( '/history/(?P<id>\d+)', 'DELETE', array( $this, 'delete' ), 'manage' );
        Api::route( '/history/(?P<id>\d+)/restore', 'POST', array( $this, 'restore' ) );
        Api::route( '/history/(?P<id>\d+)/approve', 'POST', array( $this, 'approve' ) );
        Api::route( '/history/(?P<id>\d+)/reject', 'POST', array( $this, 'reject' ) );
    }

    /** People without the manage capability only see their own actions. */
    private function scope( \WP_REST_Request $r ) {
        $filters = array();
        foreach ( array( 'operation', 'group', 'status', 'source', 'search', 'from', 'to' ) as $key ) {
            $value = $r->get_param( $key );
            if ( null !== $value && '' !== $value ) {
                $filters[ $key ] = sanitize_text_field( $value );
            }
        }
        if ( ! Api::can( 'manage' ) ) {
            $filters['user_id'] = get_current_user_id();
        }
        return $filters;
    }

    private function present( array $row ) {
        $context = Services::get( 'context' );
        $def     = $context->operation( $row['operation'] );
        $user    = $row['user_id'] ? get_userdata( $row['user_id'] ) : null;
        $thumb   = '';
        if ( $row['object_id'] ) {
            $thumb = self::preview( $row['object_id'] )['thumb'];
        }
        // Actions that start from nothing (a chart, a QR code ...) show the file they made.
        if ( '' === $thumb && ! empty( $row['result']['outputs'] ) && is_array( $row['result']['outputs'] ) ) {
            foreach ( $row['result']['outputs'] as $output ) {
                $thumb = ! empty( $output['attachment_id'] ) ? self::preview( $output['attachment_id'] )['thumb'] : '';
                if ( '' !== $thumb ) {
                    break;
                }
            }
        }
        $row['label']     = $def ? $def['label'] : $row['operation'];
        $row['user_name'] = $user ? $user->display_name : '';
        $row['thumb']     = $thumb;
        $row['when']      = mysql2date( 'Y-m-d H:i', get_date_from_gmt( $row['created_at'] ) );
        $row['ago']       = human_time_diff( strtotime( $row['created_at'] . ' UTC' ), time() );
        $row['can_restore'] = 'success' === $row['status'] && $row['object_id'] && BackupStore::has( $row['object_id'] );
        $row['edit_url']  = $row['object_id'] ? get_edit_post_link( $row['object_id'], 'raw' ) : '';
        return $row;
    }

    /**
     * How a file looks right now. The address saved with a past result can be out of date (the file was replaced, renamed or
     * deleted), so it is always looked up again from the Media Library.
     *
     * @return array{alive:bool,thumb:string,url:string,edit_url:string}
     */
    private static function preview( $attachment_id ) {
        $attachment_id = (int) $attachment_id;
        $out           = array( 'alive' => false, 'thumb' => '', 'url' => '', 'edit_url' => '' );
        if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
            return $out;
        }
        $out['alive']    = true;
        $out['url']      = (string) wp_get_attachment_url( $attachment_id );
        $thumb           = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
        if ( ! $thumb && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
            $thumb = $out['url'];
        }
        $out['thumb']    = $thumb ? (string) $thumb : '';
        $out['edit_url'] = (string) get_edit_post_link( $attachment_id, 'raw' );
        return $out;
    }

    private function allowed( $row ) {
        return $row && ( Api::can( 'manage' ) || (int) $row['user_id'] === get_current_user_id() );
    }

    public function index( \WP_REST_Request $r ) {
        $filters = $this->scope( $r );
        $page    = max( 1, (int) $r->get_param( 'page' ) );
        $result  = Repository::query( $filters, $page, (int) ( $r->get_param( 'per_page' ) ? $r->get_param( 'per_page' ) : 20 ) );
        $result['items'] = array_map( array( $this, 'present' ), $result['items'] );
        $result['stats'] = Repository::stats( $filters );
        return $result;
    }

    public function show( \WP_REST_Request $r ) {
        $row = Repository::find( (int) $r['id'] );
        if ( ! $this->allowed( $row ) ) {
            return Api::error( __( 'Not found.', 'davix-h2i' ), 404, 'rest_not_found' );
        }
        $row = $this->present( $row );
        // What the app still has for these results (expiry), looked up one by one, never more than a few.
        $remote = array();
        foreach ( array_slice( (array) $row['file_ids'], 0, 5 ) as $file_id ) {
            try {
                $data     = ClientFactory::make()->call( 'files.{id}', array( 'id' => (int) $file_id ) )->data;
                $remote[] = array(
                    'id'         => (int) $file_id,
                    'status'     => isset( $data['status'] ) ? $data['status'] : '',
                    'expires_at' => isset( $data['expires_at'] ) ? $data['expires_at'] : null,
                    'size_bytes' => isset( $data['size_bytes'] ) ? $data['size_bytes'] : null,
                );
            } catch ( \Throwable $e ) {
                $remote[] = array( 'id' => (int) $file_id, 'status' => 'gone', 'expires_at' => null );
            }
        }
        $row['remote'] = $remote;
        if ( ! empty( $row['result']['outputs'] ) && is_array( $row['result']['outputs'] ) ) {
            foreach ( $row['result']['outputs'] as $i => $output ) {
                if ( empty( $output['attachment_id'] ) ) {
                    continue;
                }
                $now = self::preview( $output['attachment_id'] );
                $row['result']['outputs'][ $i ] = array_merge( $output, array( 'gone' => ! $now['alive'], 'thumb' => $now['thumb'], 'url' => $now['alive'] ? $now['url'] : '', 'edit_url' => $now['edit_url'] ) );
            }
        }
        $row['file_gone'] = $row['object_id'] && ! self::preview( $row['object_id'] )['alive'];
        // The same facts as sentences, for the side panel: nothing in it is raw settings or codes.
        $def             = Services::get( 'context' )->operation( $row['operation'] );
        $row['settings'] = ParamSummary::summarize( $row['operation'], (array) $row['params'], $def );
        $row['kept']     = array();
        foreach ( $remote as $i => $file ) {
            $gone = in_array( $file['status'], array( 'gone', 'expired', 'deleted' ), true );
            $text = $gone ? __( 'No', 'davix-h2i' ) : __( 'Yes', 'davix-h2i' );
            if ( ! $gone && ! empty( $file['expires_at'] ) ) {
                $text .= ' — ' . sprintf( /* translators: %s: date */ __( 'until %s', 'davix-h2i' ), mysql2date( get_option( 'date_format' ), get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $file['expires_at'] ) ) ) ) );
            }
            $row['kept'][] = array( 'label' => count( $remote ) > 1 ? sprintf( /* translators: %d: number */ __( 'Result file %d', 'davix-h2i' ), $i + 1 ) : __( 'Result file', 'davix-h2i' ), 'value' => $text );
        }
        return $row;
    }

    public function export( \WP_REST_Request $r ) {
        $rows = Repository::export_rows( $this->scope( $r ) );
        $out  = array( array( 'id', 'date_utc', 'user', 'action', 'source', 'file', 'file_id', 'status', 'request_id', 'bytes_before', 'bytes_after', 'error' ) );
        foreach ( $rows as $row ) {
            $user  = $row['user_id'] ? get_userdata( $row['user_id'] ) : null;
            $out[] = array( $row['id'], $row['created_at'], $user ? $user->user_login : '', $row['operation'], $row['source'], $row['object_title'], $row['object_id'], $row['status'], $row['request_id'], $row['bytes_before'], $row['bytes_after'], $row['error_message'] );
        }
        $csv = '';
        foreach ( $out as $line ) {
            $csv .= implode( ',', array_map( array( $this, 'csv_cell' ), $line ) ) . "\n";
        }
        return array( 'filename' => 'davix-h2i-history-' . gmdate( 'Y-m-d' ) . '.csv', 'csv' => $csv );
    }

    private function csv_cell( $value ) {
        $value = (string) $value;
        if ( preg_match( '/^[=+\-@\t\r]/', $value ) ) {
            $value = "'" . $value; // spreadsheet formula injection
        }
        return '"' . str_replace( '"', '""', $value ) . '"';
    }

    public function clear() {
        return array( 'cleared' => Repository::clear() );
    }

    public function delete( \WP_REST_Request $r ) {
        return array( 'deleted' => Repository::delete( (int) $r['id'] ) );
    }

    public function restore( \WP_REST_Request $r ) {
        $row = Repository::find( (int) $r['id'] );
        if ( ! $this->allowed( $row ) || ! $row['object_id'] || ! Api::can_edit_attachment( $row['object_id'] ) ) {
            return Api::error( __( 'You cannot restore this file.', 'davix-h2i' ), 403, 'rest_forbidden' );
        }
        if ( ! BackupStore::restore( $row['object_id'] ) ) {
            return Api::error( __( 'The original is no longer available.', 'davix-h2i' ), 410 );
        }
        Repository::update( $row['id'], array( 'status' => 'restored' ) );
        return array( 'ok' => true, 'message' => __( 'The original was put back.', 'davix-h2i' ) );
    }

    public function approve( \WP_REST_Request $r ) {
        $row = Repository::find( (int) $r['id'] );
        if ( ! $this->allowed( $row ) || 'pending_review' !== $row['status'] || ! Api::can_edit_attachment( $row['object_id'] ) ) {
            return Api::error( __( 'This suggestion cannot be applied.', 'davix-h2i' ), 403, 'rest_forbidden' );
        }
        $text = isset( $row['result']['pending']['text'] ) ? (array) $row['result']['pending']['text'] : array();
        $edit = $r->get_param( 'text' );
        if ( is_array( $edit ) ) {
            foreach ( $edit as $k => $v ) {
                if ( isset( $text[ $k ] ) && is_string( $v ) ) {
                    $text[ $k ] = sanitize_textarea_field( $v );
                }
            }
        }
        $done = TextWriter::apply_approved( $row['operation'], $row['object_id'], $text );
        $result            = $row['result'];
        $result['applied'] = $done['applied'];
        $result['text']    = $text;
        unset( $result['pending'] );
        Repository::update( $row['id'], array( 'status' => 'success', 'result' => $result ) );
        return array( 'ok' => true, 'applied' => $done['applied'] );
    }

    public function reject( \WP_REST_Request $r ) {
        $row = Repository::find( (int) $r['id'] );
        if ( ! $this->allowed( $row ) || 'pending_review' !== $row['status'] ) {
            return Api::error( __( 'This suggestion cannot be changed.', 'davix-h2i' ), 403, 'rest_forbidden' );
        }
        Repository::update( $row['id'], array( 'status' => 'rejected' ) );
        return array( 'ok' => true );
    }
}
