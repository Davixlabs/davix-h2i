<?php
/**
 * PDF: compress, thumbnails, merge, split, extract pages, watermark, password protection.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'pdf',
    'label'      => 'PDF',
    'release'    => '2.1',
    'order'      => 50,
    'operations' => array( 'pdfs.optimize', 'pdfs.to-images', 'pdfs.merge', 'pdfs.split', 'pdfs.extract', 'pdfs.watermark', 'pdfs.security' ),
);
