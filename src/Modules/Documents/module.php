<?php
/**
 * Documents: convert Office files, PDFs and other documents. Adds its screen to the "PDF & Documents" tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'documents',
    'label'      => 'Documents',
    'release'    => '2.2',
    'order'      => 55,
    'operations' => array( 'documents.convert' ),
);
