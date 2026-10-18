<?php
/**
 * Processes the local queue. Runs from WP-Cron (every minute), from a "run now" button and from WP-CLI. Work is done in
 * small slices within a time budget, honours the plan's refusals, and stops the whole queue when the allowance is used up.
 */

namespace DavixWP\Jobs;

use DavixWP\Capabilities\LimitsService;
use DavixWP\Connection\ClientFactory;
use DavixWP\Connection\ErrorMapper;
use DavixWP\Connection\KeyStore;
use DavixWP\Core\Options;
use DavixWP\Operations\Executor;
use DavixWP\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Worker {

    const MAX_ATTEMPTS = 3;
    const LOCK_KEY     = 'davix_h2i_worker_lock';

    /**
     * @param int $budget Seconds of work per run.
     * @return array{processed:int,done:int,failed:int,requeued:int,paused:string}
     */
    public static function run( $budget = 25 ) {
        $stats = array( 'processed' => 0, 'done' => 0, 'failed' => 0, 'requeued' => 0, 'paused' => '' );
        if ( ! KeyStore::has() ) {
            return $stats;
        }
        if ( get_transient( self::LOCK_KEY ) ) {
            return $stats;
        }
        set_transient( self::LOCK_KEY, 1, 90 );

        $paused = Queue::paused();
        if ( $paused ) {
            delete_transient( self::LOCK_KEY );
            $stats['paused'] = $paused['reason'];
            return $stats;
        }

        $started = time();
        try {
            Queue::recover_stuck();
            self::poll_remote( $stats );

            $limit = max( 1, min( 50, (int) Options::get( 'runtime.batch', 10 ) ) );
            while ( time() - $started < $budget && $stats['processed'] < $limit ) {
                $jobs = Queue::claim( 1 );
                if ( ! $jobs ) {
                    break;
                }
                $stop = self::process( $jobs[0], $stats );
                $stats['processed']++;
                if ( $stop ) {
                    break;
                }
            }
        } finally {
            delete_transient( self::LOCK_KEY );
        }
        // More queued? Come back soon instead of waiting for the next minute.
        $counts = Queue::counts();
        if ( $counts['queued'] > 0 && ! Queue::paused() ) {
            Queue::kick();
        }
        return $stats;
    }

    /** @return bool True when the whole run should stop. */
    private static function process( array $job, array &$stats ) {
        $final   = $job['attempts'] >= self::MAX_ATTEMPTS;
        $remote  = (bool) Options::get( 'runtime.remote_jobs', false );
        $outcome = Executor::run(
            array(
                'operation'  => $job['operation'],
                'object_id'  => $job['object_id'],
                'object_ids' => isset( $job['params']['_object_ids'] ) ? $job['params']['_object_ids'] : array(),
                'extra'      => isset( $job['params']['_extra'] ) ? $job['params']['_extra'] : array(),
                'params'     => self::clean_params( $job['params'] ),
                'source'     => $job['source'],
                'user_id'    => $job['user_id'],
                'batch_id'   => $job['batch_id'],
                'mode'       => isset( $job['params']['_mode'] ) ? $job['params']['_mode'] : '',
                'policy'     => isset( $job['params']['_policy'] ) ? $job['params']['_policy'] : '',
                'post_id'    => isset( $job['params']['_post_id'] ) ? (int) $job['params']['_post_id'] : 0,
                'featured'   => ! empty( $job['params']['_featured'] ),
                'async'      => $remote,
                'job_id'     => $final ? 0 : $job['id'],
            )
        );

        if ( $outcome['ok'] && 'waiting_remote' === $outcome['status'] ) {
            Queue::update( $job['id'], array( 'status' => Queue::WAITING_REMOTE, 'remote_job_id' => $outcome['remote_job_id'], 'request_id' => $outcome['request_id'] ) );
            return false;
        }
        if ( $outcome['ok'] ) {
            Queue::update( $job['id'], array( 'status' => Queue::DONE, 'history_id' => $outcome['history_id'], 'request_id' => $outcome['request_id'], 'error_code' => '', 'error_message' => '' ) );
            $stats['done']++;
            if ( ! empty( $job['params']['_then']['operation'] ) ) {
                // The second step (for example removing location data) works on the file the first one made.
                Queue::enqueue(
                    array(
                        'operation' => $job['params']['_then']['operation'],
                        'object_id' => ! empty( $outcome['attachment_ids'] ) ? (int) $outcome['attachment_ids'][0] : (int) $job['object_id'],
                        'params'    => array_merge( (array) $job['params']['_then']['params'], array( '_mode' => 'replace' ) ),
                        'source'    => $job['source'],
                        'user_id'   => $job['user_id'],
                        'batch_id'  => $job['batch_id'],
                    )
                );
            }
            return false;
        }

        $error = $outcome['error'];
        if ( ! empty( $error['retryable'] ) && ! $final ) {
            $wait = max( (int) $error['retry_after'], 30 * $job['attempts'] );
            Queue::update( $job['id'], array( 'status' => Queue::QUEUED, 'run_after' => time() + $wait, 'error_code' => $error['code'], 'error_message' => $error['message'] ) );
            $stats['requeued']++;
            return 'rate' === $error['kind'];
        }

        Queue::update(
            $job['id'],
            array(
                'status'        => Queue::FAILED,
                'history_id'    => $outcome['history_id'],
                'request_id'    => $outcome['request_id'],
                'error_code'    => $error['code'],
                'error_message' => $error['message'],
            )
        );
        $stats['failed']++;

        if ( ! empty( $error['fatal'] ) ) {
            // Used-up allowance or a bad key: keep the rest of the queue, stop, and say why.
            $reason = 'quota' === $error['kind'] ? 'quota' : 'auth';
            Queue::pause( $reason, 'quota' === $reason ? 3600 : 900 );
            $stats['paused'] = $reason;
            LimitsService::flush();
            Logger::error( 'queue paused', array( 'reason' => $reason ) );
            return true;
        }
        return false;
    }

    /** Asks the app about jobs it is still processing and finishes the ones that are done. */
    private static function poll_remote( array &$stats ) {
        $waiting = Queue::waiting();
        if ( ! $waiting ) {
            return;
        }
        $client = ClientFactory::make();
        foreach ( $waiting as $job ) {
            try {
                $response = $client->call( 'jobs.{id}', array( 'id' => $job['remote_job_id'] ) );
            } catch ( \Throwable $e ) {
                $error = ErrorMapper::map( $e );
                if ( 'invalid' === $error['kind'] || 404 === $error['status'] ) {
                    Queue::update( $job['id'], array( 'status' => Queue::FAILED, 'error_code' => $error['code'], 'error_message' => $error['message'] ) );
                    $stats['failed']++;
                }
                continue;
            }
            $remote = isset( $response->data['job'] ) ? $response->data['job'] : array();
            $status = isset( $remote['status'] ) ? $remote['status'] : '';
            if ( 'completed' === $status ) {
                $outcome = Executor::complete(
                    array(
                        'operation'  => $job['operation'],
                        'object_id'  => $job['object_id'],
                        'object_ids' => isset( $job['params']['_object_ids'] ) ? $job['params']['_object_ids'] : array(),
                        'params'     => self::clean_params( $job['params'] ),
                        'source'     => $job['source'],
                        'user_id'    => $job['user_id'],
                        'batch_id'   => $job['batch_id'],
                        'mode'       => isset( $job['params']['_mode'] ) ? $job['params']['_mode'] : '',
                        'policy'     => isset( $job['params']['_policy'] ) ? $job['params']['_policy'] : '',
                        'post_id'    => isset( $job['params']['_post_id'] ) ? (int) $job['params']['_post_id'] : 0,
                        'featured'   => ! empty( $job['params']['_featured'] ),
                    ),
                    isset( $remote['result'] ) && is_array( $remote['result'] ) ? $remote['result'] : array(),
                    (string) $job['request_id']
                );
                Queue::update( $job['id'], array( 'status' => $outcome['ok'] ? Queue::DONE : Queue::FAILED, 'history_id' => $outcome['history_id'], 'error_message' => $outcome['ok'] ? '' : $outcome['message'] ) );
                $outcome['ok'] ? $stats['done']++ : $stats['failed']++;
            } elseif ( 'failed' === $status || 'cancelled' === $status ) {
                $message = isset( $remote['error_message'] ) && $remote['error_message'] ? $remote['error_message'] : __( 'The job did not finish.', 'davix-h2i' );
                Queue::update( $job['id'], array( 'status' => 'failed' === $status ? Queue::FAILED : Queue::CANCELLED, 'error_message' => $message ) );
                $stats['failed']++;
            }
        }
    }

    /** Params stored on the job minus the internal underscore keys. */
    private static function clean_params( array $params ) {
        foreach ( array_keys( $params ) as $key ) {
            if ( 0 === strpos( (string) $key, '_' ) ) {
                unset( $params[ $key ] );
            }
        }
        return $params;
    }
}
