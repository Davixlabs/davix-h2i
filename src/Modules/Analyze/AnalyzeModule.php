<?php

namespace DavixWP\Modules\Analyze;

use DavixWP\Core\Context;
use DavixWP\Core\Module;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AnalyzeModule extends Module {

    public function register( Context $c ) {
        $c->add_operation(
            array(
                'id'          => 'images.inspect',
                'label'       => __( 'Analyze images', 'davix-h2i' ),
                'description' => __( 'Quality, how much you could save, and which images look alike.', 'davix-h2i' ),
                'group'       => 'images',
                'file_field'  => 'images',
                'accepts'     => array( 'image/' ),
                'store'       => 'none',
                'result'      => 'json',
                'batch'       => 'all',
                'csv'         => array( 'checks' ),
                'fields'      => array(
                    array( 'name' => 'checks', 'type' => 'json', 'label' => __( 'Checks', 'davix-h2i' ), 'default' => array( 'dimensions', 'quality', 'efficiency', 'hash', 'optimization' ), 'help' => __( 'Which checks to run. Add "similarity" to find look-alikes across the chosen images.', 'davix-h2i' ) ),
                    array( 'name' => 'options', 'type' => 'json', 'label' => __( 'Options', 'davix-h2i' ), 'default' => array( 'hashType' => 'phash', 'similarityThreshold' => 8 ) ),
                ),
            )
        );
        $c->add_operation(
            array(
                'id'          => 'images.compare',
                'label'       => __( 'Compare two images', 'davix-h2i' ),
                'description' => __( 'See how different two images are.', 'davix-h2i' ),
                'group'       => 'images',
                'file_field'  => 'imageA',
                'multiple'    => false,
                'accepts'     => array( 'image/' ),
                'store'       => 'none',
                'result'      => 'json',
                'batch'       => 'pair',
                'extra'       => array( 'imageB' => __( 'Second image', 'davix-h2i' ) ),
                'fields'      => array( 'metrics', 'threshold', 'diff_image' ),
            )
        );

        // Analyzing runs from the Media Library sidebar, on the files you select.
        $c->add_drawer_tab(
            array(
                'id'         => 'analyze',
                'label'      => __( 'Analyze', 'davix-h2i' ),
                'order'      => 20,
                'kind'       => 'ops',
                'mimes'      => array( 'image/' ),
                'operations' => array( 'images.inspect', 'images.compare' ),
            )
        );
    }
}
