<?php
/**
 * Render: turn HTML into images and PDFs (social cards), and make charts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'render',
    'label'      => 'H2I Render',
    'release'    => '2.0',
    'order'      => 40,
    'operations' => array( 'render.html-to-image', 'render.html-to-pdf', 'render.chart' ),
);
