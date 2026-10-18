<?php
/**
 * Images: optimize, convert, resize, edit, watermark, privacy, responsive sizes. The chips drive everything.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'images',
    'label'      => 'Images',
    'release'    => '2.0',
    'order'      => 20,
    'operations' => array(
        'images.optimize',
        'images.auto-optimize',
        'images.convert',
        'images.resize',
        'images.transform',
        'images.pipeline',
        'images.watermark',
        'images.remove-background',
        'images.metadata',
        'images.metadata-edit',
        'images.variants',
    ),
);
