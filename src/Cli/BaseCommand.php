<?php
/**
 * WP-CLI helpers shared by the plugin's commands. Commands are registered by the core (connection, queue, history) and by
 * modules (for example `wp davix optimize`), all under the `davix` namespace.
 */

namespace DavixWP\Cli;

use DavixWP\Jobs\Queue;
use DavixWP\Media\Selector;
use DavixWP\Operations\Executor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class BaseCommand {

    /** Resolves "12,15" or "all" to attachment ids that accept the operation. */
    protected function resolve_ids( $ids, array $def ) {
        if ( 'all' === $ids ) {
            $mimes = array_map(
                function ( $p ) {
                    return rtrim( $p, '/' );
                },
                $def['accepts']
            );
            return get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => $mimes, 'posts_per_page' => -1, 'fields' => 'ids' ) );
        }
        return array_values( array_filter( array_map( 'intval', explode( ',', (string) $ids ) ) ) );
    }

    /**
     * Runs an operation for many attachments, now or through the queue.
     *
     * @param array $params Operation parameters.
     */
    protected function run_many( $operation, array $ids, array $params, $queue ) {
        if ( $queue ) {
            $batch = Queue::new_batch_id();
            foreach ( $ids as $id ) {
                Queue::enqueue( array( 'operation' => $operation, 'object_id' => $id, 'params' => $params, 'source' => 'cli', 'batch_id' => $batch ) );
            }
            \WP_CLI::success( sprintf( '%d queued (batch %s). Run "wp davix queue run" to process them.', count( $ids ), $batch ) );
            return;
        }
        $ok = 0;
        $failed = 0;
        foreach ( $ids as $id ) {
            $info = Selector::info( $id );
            $out  = Executor::run( array( 'operation' => $operation, 'object_id' => $id, 'params' => $params, 'source' => 'cli', 'user_id' => 0 ) );
            if ( $out['ok'] ) {
                $ok++;
                \WP_CLI::log( sprintf( '#%d %s: %s', $id, $info ? $info['filename'] : '', $out['message'] ) );
            } else {
                $failed++;
                \WP_CLI::warning( sprintf( '#%d: %s', $id, $out['message'] ) );
                if ( ! empty( $out['error']['fatal'] ) ) {
                    \WP_CLI::error( 'Stopping: ' . $out['message'] );
                }
            }
        }
        \WP_CLI::success( sprintf( '%d done, %d failed.', $ok, $failed ) );
    }
}
