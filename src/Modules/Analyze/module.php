<?php
/**
 * Analyze: look at images (quality, savings potential, duplicates) and compare two images.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'analyze',
    'label'      => 'Analyze',
    'release'    => '2.0',
    'order'      => 30,
    'operations' => array( 'images.inspect', 'images.compare' ),
);
