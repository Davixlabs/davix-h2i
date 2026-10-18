<?php
/**
 * Runs one operation end to end for the plugin: plan check, input file, SDK call, result saved into WordPress, and a
 * History row. Every screen, the queue worker and WP-CLI go through here, which is why History shows only what the plugin did.
 */

namespace DavixWP\Operations;

use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Capabilities\LimitsService;
use DavixWP\Connection\ClientFactory;
use DavixWP\Connection\ErrorMapper;
use DavixWP\Core\Options;
use DavixWP\Core\Services;
use DavixWP\History\Repository;
use DavixWP\Media\AttachmentWriter;
use DavixWP\Media\Selector;
use DavixWP\Media\StateMarker;
use DavixWP\Media\TextWriter;
use DavixWP\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Executor {

    /**
     * @param array $req operation, object_id, object_ids, extra (field => attachment id), params, source, user_id, batch_id,
     *                   mode (replace|copy), async (bool), record (bool), job_id
     * @return array Outcome: ok, status, message, error, outputs, text, applied, attachment_ids, history_id, request_id, remote_job_id
     */
    public static function run( array $req ) {
        $req = self::defaults( $req );
        $def = self::definition( $req['operation'] );
        if ( ! $def ) {
            return self::failure( $req, array( 'code' => 'unknown_operation', 'kind' => 'other', 'message' => __( 'This action is not available.', 'davix-h2i' ), 'request_id' => '', 'fatal' => false, 'retryable' => false, 'retry_after' => 0, 'hint' => '', 'status' => 0 ) );
        }

        if ( ! CapabilityGate::allows( $def['id'] ) ) {
            $locked = CapabilityGate::LOCKED === CapabilityGate::state( $def['id'] );
            return self::failure(
                $req,
                array(
                    'code'        => $locked ? 'endpoint_not_allowed' : 'feature_unavailable',
                    'kind'        => $locked ? 'plan' : 'unavailable',
                    'message'     => $locked ? __( 'Your plan does not include this feature.', 'davix-h2i' ) : __( 'This feature is not available right now.', 'davix-h2i' ),
                    'request_id'  => '',
                    'fatal'       => false,
                    'retryable'   => false,
                    'retry_after' => 0,
                    'hint'        => '',
                    'status'      => 0,
                )
            );
        }

        $files = self::input_files( $def, $req );
        if ( is_array( $files ) && isset( $files['error'] ) ) {
            return self::failure( $req, $files['error'] );
        }

        try {
            $params  = self::build_params( $def, $req, $files );
            $options = array( 'idempotencyKey' => self::idempotency_key( $def, $req, $files, $params ) );
            if ( $req['async'] && ! empty( $def['async'] ) ) {
                $params['async'] = true;
            }
            Logger::info( 'call ' . $def['id'], array( 'object' => $req['object_id'], 'source' => $req['source'] ) );
            try {
                $response = ClientFactory::make()->call( $def['id'], $params, $options );
            } catch ( \Throwable $first ) {
                $mapped = ErrorMapper::map( $first );
                if ( empty( $params['async'] ) || ! in_array( $mapped['code'], array( 'async_not_allowed', 'async_limit_reached', 'async_requires_customer_key' ), true ) ) {
                    throw $first;
                }
                // The plan does not allow a background job right now: run it directly instead.
                unset( $params['async'] );
                $response = ClientFactory::make()->call( $def['id'], $params, $options );
            }
        } catch ( \Throwable $e ) {
            $error = ErrorMapper::map( $e );
            Logger::error( 'failed ' . $def['id'], array( 'code' => $error['code'], 'request_id' => $error['request_id'] ) );
            if ( in_array( $error['kind'], array( 'plan', 'quota', 'unavailable' ), true ) ) {
                LimitsService::flush();
            }
            return self::failure( $req, $error );
        }

        if ( $response->accepted() ) {
            return array(
                'ok'             => true,
                'status'         => 'waiting_remote',
                'message'        => __( 'Processing in the background.', 'davix-h2i' ),
                'error'          => null,
                'outputs'        => array(),
                'text'           => array(),
                'applied'        => array(),
                'attachment_ids' => array(),
                'history_id'     => 0,
                'request_id'     => (string) $response->requestId,
                'remote_job_id'  => (int) $response->jobId(),
            );
        }

        return self::complete( $req, is_array( $response->data ) ? $response->data : array(), (string) $response->requestId );
    }

    /**
     * Saves the result of a finished call (sync response or finished remote job) and records it in History.
     *
     * @param array  $req
     * @param array  $data Response body (or the remote job's `result`).
     */
    public static function complete( array $req, array $data, $request_id = '' ) {
        $req = self::defaults( $req );
        $def = self::definition( $req['operation'] );
        if ( ! $def ) {
            return self::failure( $req, array( 'code' => 'unknown_operation', 'kind' => 'other', 'message' => __( 'This action is not available.', 'davix-h2i' ), 'request_id' => '', 'fatal' => false, 'retryable' => false, 'retry_after' => 0, 'hint' => '', 'status' => 0 ) );
        }
        $info    = $req['object_id'] ? Selector::info( $req['object_id'] ) : null;
        $outcome = array(
            'ok'             => true,
            'status'         => 'success',
            'message'        => '',
            'error'          => null,
            'outputs'        => array(),
            'text'           => array(),
            'applied'        => array(),
            'attachment_ids' => array(),
            'history_id'     => 0,
            'request_id'     => (string) $request_id,
            'remote_job_id'  => 0,
            'bytes_before'   => $info ? $info['size'] : 0,
            'bytes_after'    => 0,
            'data'           => array(),
        );

        $urls = self::collect_files( $data );

        try {
            if ( 'text' === $def['result'] ) {
                $result_key      = isset( $data['result'] ) ? $data['result'] : $data;
                $outcome['text'] = TextWriter::normalize( $def['id'], $result_key );
                if ( 'text' === $def['store'] && $req['object_id'] ) {
                    $policy  = isset( $req['policy'] ) && $req['policy'] ? $req['policy'] : '';
                    $applied = TextWriter::apply( $def['id'], $req['object_id'], $outcome['text'], $policy );
                    $outcome['applied'] = $applied['applied'];
                    if ( $applied['held'] ) {
                        $outcome['status'] = 'pending_review';
                    }
                }
                $outcome['message'] = __( 'Done.', 'davix-h2i' );
            } elseif ( 'json' === $def['result'] ) {
                $outcome['data']    = $data;
                $outcome['message'] = __( 'Done.', 'davix-h2i' );
            } else {
                if ( ! $urls ) {
                    $outcome['data']    = $data;
                    $outcome['message'] = __( 'Done.', 'davix-h2i' );
                } else {
                    self::store_files( $def, $req, $info, $urls, $outcome );
                }
            }
        } catch ( \Throwable $e ) {
            return self::failure( $req, ErrorMapper::map( $e ), $request_id );
        }

        $outcome['history_id'] = self::record( $def, $req, $outcome, $info );
        do_action( 'davix_h2i_operation_finished', $outcome, $req );
        return $outcome;
    }

    // ------------------------------------------------------------ storing
    private static function store_files( array $def, array $req, $info, array $urls, array &$outcome ) {
        if ( ! function_exists( 'wp_tempnam' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $store  = $def['store'];
        $mode   = self::output_mode( $def, $req );

        // Operations that return several files, or that never touch the original, become new media items.
        $many = count( $urls ) > 1 || 'new' === $store || ! $info;

        foreach ( $urls as $index => $file ) {
            $tmp = wp_tempnam( 'davix-h2i' );
            try {
                \DavixWP\Media\ResultFetcher::fetch( $file, $tmp );
            } catch ( \Throwable $e ) {
                @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
                throw $e;
            }
            $size = (int) filesize( $tmp );
            $name = isset( $file['filename'] ) ? $file['filename'] : ( isset( $file['originalName'] ) && $file['originalName'] ? $file['originalName'] : '' );
            $mime = self::guess_mime( $tmp, $file, $name );
            $outcome['bytes_after'] += $size;

            $entry = array(
                'filename'      => $name,
                'mime'          => $mime,
                'size'          => $size,
                'remote_file_id' => isset( $file['file_id'] ) ? (int) $file['file_id'] : 0,
            );

            if ( $many || 'new' === $store ) {
                $saved = AttachmentWriter::import(
                    $tmp,
                    array(
                        'filename'  => self::output_name( $def, $info, $name, $mime, $index ),
                        'mime'      => $mime,
                        'parent'    => $info ? (int) wp_get_post_parent_id( $info['id'] ) : (int) $req['post_id'],
                        'source_id' => $info ? $info['id'] : 0,
                        'state'     => self::state( $def, $req, $info, $size, $outcome['request_id'] ),
                    )
                );
                if ( is_wp_error( $saved ) ) {
                    @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
                    throw new \RuntimeException( $saved->get_error_message() );
                }
                $entry['attachment_id'] = $saved['id'];
                $entry['url']           = $saved['url'];
                $outcome['attachment_ids'][] = $saved['id'];
            } else {
                $format_changes = $info && AttachmentWriter::extension_for( $mime, $name ) !== AttachmentWriter::extension_for( $info['mime'], $info['filename'] );
                if ( 'replace' === $mode && $format_changes ) {
                    $mode = 'copy';
                }
                if ( 'replace' === $mode && ! empty( $def['optimize'] ) && $info && $size >= $info['size'] ) {
                    @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
                    $outcome['bytes_after'] = $info['size'];
                    $outcome['message']     = __( 'Already as small as it can be. The original was kept.', 'davix-h2i' );
                    $entry['skipped']       = true;
                    $entry['attachment_id'] = $info['id'];
                    $outcome['outputs'][]   = $entry;
                    $outcome['attachment_ids'][] = $info['id'];
                    $outcome['status']      = 'success';
                    continue;
                }
                if ( 'replace' === $mode ) {
                    $saved = AttachmentWriter::replace(
                        $info['id'],
                        $tmp,
                        array(
                            'filename' => $name ? $name : $info['filename'],
                            'mime'     => $mime,
                            'state'    => self::state( $def, $req, $info, $size, $outcome['request_id'] ),
                        )
                    );
                    if ( ! is_wp_error( $saved ) ) {
                        $entry['attachment_id'] = $info['id'];
                        $entry['url']           = wp_get_attachment_url( $info['id'] );
                        $outcome['attachment_ids'][] = $info['id'];
                        $entry['backup']        = \DavixWP\Media\BackupStore::has( $info['id'] );
                    }
                } else {
                    $suffix = (string) Options::get( self::settings_group( $def ) . '.output.suffix', '_optimized' );
                    $saved  = AttachmentWriter::copy( $info['id'], $tmp, $suffix, array( 'mime' => $mime, 'filename' => $name, 'state' => self::state( $def, $req, $info, $size, $outcome['request_id'] ) ) );
                    if ( ! is_wp_error( $saved ) ) {
                        $entry['attachment_id'] = $saved['id'];
                        $entry['url']           = $saved['url'];
                        $outcome['attachment_ids'][] = $saved['id'];
                    }
                }
                if ( is_wp_error( $saved ) ) {
                    @unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
                    throw new \RuntimeException( $saved->get_error_message() );
                }
            }
            $outcome['outputs'][] = $entry;
        }
        if ( $req['featured'] && $req['post_id'] && $outcome['attachment_ids'] ) {
            set_post_thumbnail( (int) $req['post_id'], (int) $outcome['attachment_ids'][0] );
        }
        if ( '' === $outcome['message'] ) {
            $outcome['message'] = __( 'Done.', 'davix-h2i' );
        }
    }

    private static function output_name( array $def, $info, $name, $mime, $index ) {
        $ext  = AttachmentWriter::extension_for( $mime, $name );
        $base = $name ? pathinfo( $name, PATHINFO_FILENAME ) : ( $info ? pathinfo( $info['filename'], PATHINFO_FILENAME ) : substr( $def['id'], (int) strrpos( $def['id'], '.' ) + 1 ) );
        return sanitize_file_name( $base . ( $index > 0 && ! $name ? '-' . ( $index + 1 ) : '' ) . '.' . $ext );
    }

    private static function state( array $def, array $req, $info, $size, $request_id ) {
        return array(
            'operation'    => $def['id'],
            'params_hash'  => StateMarker::params_hash( $req['params'] ),
            'bytes_before' => $info ? $info['size'] : 0,
            'bytes_after'  => (int) $size,
            'request_id'   => (string) $request_id,
        );
    }

    private static function output_mode( array $def, array $req ) {
        if ( in_array( $req['mode'], array( 'replace', 'copy' ), true ) ) {
            return $req['mode'];
        }
        if ( 'copy' === $def['store'] ) {
            return 'copy';
        }
        $mode = (string) Options::get( self::settings_group( $def ) . '.output.mode', 'replace' );
        return 'copy' === $mode ? 'copy' : 'replace';
    }

    private static function settings_group( array $def ) {
        return 'pdf' === $def['group'] || 'documents' === $def['group'] ? 'pdf' : ( 'render' === $def['group'] ? 'render' : 'images' );
    }

    // ------------------------------------------------------------ request
    private static function defaults( array $req ) {
        return array_merge(
            array(
                'operation'  => '',
                'object_id'  => 0,
                'object_ids' => array(),
                'extra'      => array(),
                'params'     => array(),
                'source'     => 'library',
                'user_id'    => get_current_user_id(),
                'batch_id'   => '',
                'mode'       => '',
                'async'      => false,
                'record'     => true,
                'job_id'     => 0,
                'policy'     => '',
                'post_id'    => 0,
                'featured'   => false,
            ),
            $req
        );
    }

    private static function definition( $id ) {
        if ( ! Services::has( 'context' ) ) {
            return null;
        }
        return Services::get( 'context' )->operation( $id );
    }

    /**
     * @return array|array{error:array} field => list of file arrays
     */
    private static function input_files( array $def, array $req ) {
        $ids = array();
        if ( $req['object_ids'] ) {
            $ids = array_map( 'intval', (array) $req['object_ids'] );
        } elseif ( $req['object_id'] ) {
            $ids = array( (int) $req['object_id'] );
        }
        $out = array();
        if ( 'none' === $def['input'] && ! $req['extra'] ) {
            return $out;
        }
        if ( 'file' === $def['input'] && ! $ids ) {
            return array( 'error' => self::simple_error( 'missing_file', __( 'Choose a file first.', 'davix-h2i' ) ) );
        }
        if ( ! $def['multiple'] ) {
            $ids = array_slice( $ids, 0, 1 );
        }
        $max = CapabilityGate::max_file_bytes( $def['id'] );
        foreach ( $ids as $id ) {
            $info = Selector::info( $id );
            if ( ! $info || ! $info['exists'] ) {
                return array( 'error' => self::simple_error( 'missing_file', __( 'The file could not be found on this site.', 'davix-h2i' ) ) );
            }
            if ( ! Definition::accepts( $def, $info['mime'] ) ) {
                return array( 'error' => self::simple_error( 'unsupported_file', __( 'This action does not work with this kind of file.', 'davix-h2i' ) ) );
            }
            if ( $max > 0 && $info['size'] > $max ) {
                return array( 'error' => self::simple_error( 'file_too_large', sprintf( /* translators: %s: size */ __( 'This file is larger than your plan allows (%s).', 'davix-h2i' ), size_format( $max ) ) ) );
            }
            $out[ $def['file_field'] ][] = array( 'path' => $info['path'], 'filename' => $info['filename'] );
        }
        foreach ( (array) $req['extra'] as $field => $attachment ) {
            $info = Selector::info( (int) $attachment );
            if ( $info && $info['exists'] ) {
                $out[ $field ][] = array( 'path' => $info['path'], 'filename' => $info['filename'] );
            }
        }
        return $out;
    }

    private static function build_params( array $def, array $req, array $files ) {
        $params = array_merge( $def['defaults'], (array) $req['params'] );
        $params = apply_filters( 'davix_h2i_operation_params', $params, $def['id'], $req );
        $clean  = array();
        // Every call says which app made it; the History of the account shows it next to the result.
        if ( ! isset( $params['app'] ) && Contract::has_field( $def['id'], 'app' ) ) {
            $params['app'] = (string) apply_filters( 'davix_h2i_app_name', 'Davix H2I plugin' );
        }
        $csv = isset( $def['csv'] ) ? (array) $def['csv'] : array();
        foreach ( $params as $key => $value ) {
            if ( null === $value || '' === $value || array() === $value ) {
                continue;
            }
            // Some list fields are read as a comma separated string by the service.
            if ( in_array( $key, $csv, true ) && is_array( $value ) ) {
                $value = implode( ',', array_map( 'strval', $value ) );
            }
            $clean[ $key ] = $value;
        }
        foreach ( $files as $field => $list ) {
            $multi    = true;
            $endpoint = Contract::endpoint( $def['id'] );
            if ( $endpoint ) {
                foreach ( $endpoint['request']['fields'] as $f ) {
                    if ( $f['name'] === $field && isset( $f['file']['multiple'] ) ) {
                        $multi = (bool) $f['file']['multiple'];
                    }
                }
            }
            $clean[ $field ] = $multi ? $list : $list[0];
        }
        return $clean;
    }

    private static function idempotency_key( array $def, array $req, array $files, array $params ) {
        $parts = array( site_url(), $def['id'], $req['object_id'], wp_json_encode( $req['object_ids'] ), floor( time() / 600 ) );
        foreach ( $files as $list ) {
            foreach ( $list as $f ) {
                $parts[] = $f['path'] . '|' . ( is_file( $f['path'] ) ? filesize( $f['path'] ) . '|' . filemtime( $f['path'] ) : '' );
            }
        }
        $copy = $params;
        foreach ( array_keys( $files ) as $field ) {
            unset( $copy[ $field ] );
        }
        $parts[] = wp_json_encode( $copy );
        return 'h2i-wp-' . substr( sha1( implode( '|', $parts ) ), 0, 40 );
    }

    /** Every `url` object in the answer, in order, de-duplicated. */
    public static function collect_files( $data ) {
        $out  = array();
        $seen = array();
        $walk = function ( $value ) use ( &$walk, &$out, &$seen ) {
            if ( ! is_array( $value ) ) {
                return;
            }
            if ( isset( $value['url'] ) && is_string( $value['url'] ) && preg_match( '#/(render|image|pdf|ai|data|documents)/[^/\\\\?]+#', $value['url'] ) ) {
                if ( ! isset( $seen[ $value['url'] ] ) ) {
                    $seen[ $value['url'] ] = true;
                    $out[]                 = $value;
                }
                return;
            }
            foreach ( $value as $child ) {
                $walk( $child );
            }
        };
        $walk( $data );
        return $out;
    }

    private static function guess_mime( $path, array $file, $name ) {
        if ( ! empty( $file['mimeType'] ) ) {
            return (string) $file['mimeType'];
        }
        $format = isset( $file['format'] ) ? strtolower( (string) $file['format'] ) : '';
        $by     = array(
            'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
            'avif' => 'image/avif', 'tiff' => 'image/tiff', 'svg' => 'image/svg+xml', 'pdf' => 'application/pdf', 'bmp' => 'image/bmp', 'ico' => 'image/x-icon',
        );
        if ( isset( $by[ $format ] ) ) {
            return $by[ $format ];
        }
        $detected = function_exists( 'mime_content_type' ) ? mime_content_type( $path ) : '';
        if ( $detected && 'application/octet-stream' !== $detected ) {
            return $detected;
        }
        return AttachmentWriter::mime_of( $path, $name );
    }

    // ------------------------------------------------------------ outcomes
    private static function simple_error( $code, $message ) {
        return array(
            'code'        => $code,
            'kind'        => 'invalid',
            'message'     => $message,
            'hint'        => '',
            'request_id'  => '',
            'status'      => 0,
            'retryable'   => false,
            'retry_after' => 0,
            'fatal'       => false,
        );
    }

    private static function failure( array $req, array $error, $request_id = '' ) {
        $outcome = array(
            'ok'             => false,
            'status'         => 'failed',
            'message'        => $error['message'],
            'error'          => $error,
            'outputs'        => array(),
            'text'           => array(),
            'applied'        => array(),
            'attachment_ids' => array(),
            'history_id'     => 0,
            'request_id'     => $request_id ? $request_id : $error['request_id'],
            'remote_job_id'  => 0,
        );
        // A retry is still pending in the queue; only final failures are written to History.
        if ( ! empty( $error['retryable'] ) && $req['job_id'] ) {
            return $outcome;
        }
        $info                  = $req['object_id'] ? Selector::info( $req['object_id'] ) : null;
        $outcome['history_id'] = self::record( array( 'id' => $req['operation'] ), $req, $outcome, $info );
        do_action( 'davix_h2i_operation_finished', $outcome, $req );
        return $outcome;
    }

    private static function record( array $def, array $req, array $outcome, $info ) {
        if ( empty( $req['record'] ) ) {
            return 0;
        }
        $ids = array();
        foreach ( $outcome['outputs'] as $o ) {
            if ( ! empty( $o['remote_file_id'] ) ) {
                $ids[] = (int) $o['remote_file_id'];
            }
        }
        $status = 'failed';
        if ( $outcome['ok'] ) {
            $status = 'pending_review' === $outcome['status'] ? 'pending_review' : 'success';
        }
        $params = (array) $req['params'];
        foreach ( $params as $k => $v ) {
            if ( is_string( $v ) && strlen( $v ) > 1500 ) {
                $params[ $k ] = substr( $v, 0, 1500 ) . '…';
            }
        }
        $result = array(
            'outputs'  => $outcome['outputs'],
            'text'     => $outcome['text'],
            'applied'  => $outcome['applied'],
            'message'  => $outcome['message'],
            'data'     => isset( $outcome['data'] ) && $outcome['data'] ? self::trim_data( $outcome['data'] ) : array(),
        );
        if ( 'pending_review' === $status ) {
            $result['pending'] = array( 'text' => $outcome['text'] );
        }
        $row = array(
            'user_id'       => (int) $req['user_id'],
            'operation'     => $def['id'],
            'source'        => $req['source'],
            'object_id'     => (int) $req['object_id'],
            'object_title'  => $info ? $info['title'] : '',
            'status'        => $status,
            'request_id'    => (string) $outcome['request_id'],
            'file_ids'      => $ids,
            'params'        => $params,
            'result'        => $result,
            'bytes_before'  => isset( $outcome['bytes_before'] ) ? (int) $outcome['bytes_before'] : ( $info ? $info['size'] : 0 ),
            'bytes_after'   => isset( $outcome['bytes_after'] ) ? (int) $outcome['bytes_after'] : 0,
            'error_code'    => $outcome['error'] ? $outcome['error']['code'] : '',
            'error_message' => $outcome['error'] ? $outcome['error']['message'] : '',
            'batch_id'      => (string) $req['batch_id'],
        );
        return Repository::insert( $row );
    }

    private static function trim_data( $data ) {
        $json = wp_json_encode( $data );
        if ( strlen( (string) $json ) > 60000 ) {
            return array( 'truncated' => true );
        }
        return $data;
    }
}
