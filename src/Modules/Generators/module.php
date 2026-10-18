<?php
/**
 * Generators: QR codes and the site icon / favicon pack.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'generators',
    'label'      => 'Generators',
    'release'    => '2.2',
    'order'      => 60,
    'operations' => array( 'images.qr', 'images.favicon' ),
);
