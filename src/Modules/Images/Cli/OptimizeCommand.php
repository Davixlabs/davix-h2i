<?php

namespace DavixWP\Modules\Images\Cli;

use DavixWP\Cli\BaseCommand;
use DavixWP\Core\Services;
use DavixWP\Modules\Images\Chips;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Optimize images from the command line.
 */
final class OptimizeCommand extends BaseCommand {

    /**
     * Optimizes images.
     *
     * ## OPTIONS
     * <ids>
     * : Attachment ids (comma separated) or "all".
     * [--chips=<chips>]
     * : Comma separated steps: smart, format, resize, crop, transform, effects, padding, frame, background, watermark. Default: smart.
     * [--fields=<json>]
     * : Settings of the steps as JSON, for example '{"resize":{"width":1200},"format":{"format":"webp"}}'.
     * [--queue]
     * : Add to the background queue instead of running now.
     *
     * ## EXAMPLES
     *     wp davix optimize 12,15
     *     wp davix optimize all --queue
     *     wp davix optimize 12 --chips=resize,format --fields='{"resize":{"width":1200},"format":{"format":"webp"}}'
     */
    public function __invoke( $args, $assoc ) {
        $chips  = isset( $assoc['chips'] ) ? array_filter( array_map( 'trim', explode( ',', (string) $assoc['chips'] ) ) ) : array( 'smart' );
        $fields = isset( $assoc['fields'] ) ? json_decode( (string) $assoc['fields'], true ) : array();
        if ( ! is_array( $fields ) ) {
            \WP_CLI::error( '--fields must be valid JSON.' );
        }
        $plan = Chips::build( $chips, $fields );
        if ( ! $plan ) {
            \WP_CLI::error( 'Choose at least one valid step with --chips.' );
        }
        $def = Services::get( 'context' )->operation( $plan['operation'] );
        $ids = $this->resolve_ids( $args[0], $def );
        if ( ! $ids ) {
            \WP_CLI::error( 'No images found.' );
        }
        $this->run_many( $plan['operation'], $ids, $plan['params'], isset( $assoc['queue'] ) );
    }
}
