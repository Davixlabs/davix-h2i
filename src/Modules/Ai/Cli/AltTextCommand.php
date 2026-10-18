<?php

namespace DavixWP\Modules\Ai\Cli;

use DavixWP\Cli\BaseCommand;
use DavixWP\Core\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Write alt text for images with AI.
 */
final class AltTextCommand extends BaseCommand {

    /**
     * Writes alt text.
     *
     * ## OPTIONS
     * <ids>
     * : Attachment ids (comma separated) or "all" (only images without alt text are used).
     * [--queue]
     * : Add to the background queue.
     */
    public function __invoke( $args, $assoc ) {
        $def = Services::get( 'context' )->operation( 'ai.content.alt-text' );
        $ids = $this->resolve_ids( $args[0], $def );
        if ( 'all' === $args[0] ) {
            $ids = array_values( array_filter( $ids, function ( $id ) {
                return '' === (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
            } ) );
        }
        if ( ! $ids ) {
            \WP_CLI::error( 'No images found.' );
        }
        $this->run_many( 'ai.content.alt-text', $ids, array(), isset( $assoc['queue'] ) );
    }
}
