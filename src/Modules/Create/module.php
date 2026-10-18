<?php
/**
 * Create: the actions that start from nothing (text, HTML, a description, a link) and make a new file. The other modules add their
 * screens to this tab: AI writing and images, pages to image or PDF, charts, QR codes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'create',
    'label'      => 'Create',
    'release'    => '2.3',
    'order'      => 38,
    'operations' => array(),
);
