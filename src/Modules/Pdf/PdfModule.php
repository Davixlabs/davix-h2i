<?php

namespace DavixWP\Modules\Pdf;

use DavixWP\Admin\Components\AutoPanel;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PdfModule extends Module {

    public function register( Context $c ) {
        Options::register(
            'pdf',
            array(
                'output' => array( 'mode' => 'replace', 'suffix' => '_optimized', 'backup' => true ),
            ),
            array( __CLASS__, 'sanitize' )
        );

        $pdf = array( 'group' => 'pdf', 'file_field' => 'file', 'multiple' => false, 'accepts' => array( 'application/pdf' ), 'release' => '2.1' );
        $c->add_operation( array_merge( $pdf, array(
            'id' => 'pdfs.optimize', 'label' => __( 'Compress PDF', 'davix-h2i' ), 'description' => __( 'Make PDFs smaller for faster downloads.', 'davix-h2i' ),
            'store' => 'replace', 'optimize' => true,
            'fields' => array( 'profile', 'imageQuality', 'downsample_dpi', 'grayscale', 'target_size_kb', 'linearize' ),
        ) ) );
        $c->add_operation( array_merge( $pdf, array(
            'id' => 'pdfs.to-images', 'label' => __( 'PDF to images', 'davix-h2i' ), 'description' => __( 'Thumbnails and page images.', 'davix-h2i' ),
            'store' => 'new', 'fields' => array( 'pages', 'format', 'width', 'dpi' ),
        ) ) );
        $c->add_operation( array_merge( $pdf, array(
            'id' => 'pdfs.merge', 'label' => __( 'Merge PDFs', 'davix-h2i' ), 'description' => __( 'Combine several PDFs into one.', 'davix-h2i' ),
            'file_field' => 'files', 'multiple' => true, 'batch' => 'all', 'store' => 'new', 'release' => '2.2',
            'fields' => array( 'sortByName', 'mode', 'blankSeparator' ),
        ) ) );
        $c->add_operation( array_merge( $pdf, array(
            'id' => 'pdfs.split', 'label' => __( 'Split a PDF', 'davix-h2i' ), 'description' => __( 'Cut a PDF into several files.', 'davix-h2i' ),
            'store' => 'new', 'release' => '2.2', 'fields' => array( 'mode', 'every', 'max_size_mb', 'prefix' ), 'defaults' => array( 'mode' => 'every', 'every' => 1 ),
        ) ) );
        $c->add_operation( array_merge( $pdf, array(
            'id' => 'pdfs.extract', 'label' => __( 'Extract pages', 'davix-h2i' ), 'description' => __( 'Keep only the pages you choose.', 'davix-h2i' ),
            'store' => 'new', 'release' => '2.2', 'fields' => array( 'pages', 'output', 'prefix' ),
        ) ) );
        $c->add_operation( array_merge( $pdf, array(
            'id' => 'pdfs.watermark', 'label' => __( 'Watermark a PDF', 'davix-h2i' ), 'description' => __( 'Stamp text on every page.', 'davix-h2i' ),
            'store' => 'copy', 'release' => '2.2', 'fields' => array( 'watermarkText', 'pages', 'opacity', 'position', 'fontSize', 'color' ),
            'defaults' => array( 'watermarkText' => get_bloginfo( 'name' ) ),
        ) ) );
        $c->add_operation( array_merge( $pdf, array(
            'id' => 'pdfs.security', 'label' => __( 'Protect or unlock', 'davix-h2i' ), 'description' => __( 'Add or remove a password.', 'davix-h2i' ),
            'store' => 'copy', 'release' => '2.2', 'fields' => array( 'operation', 'userPassword', 'ownerPassword', 'password', 'keyLength' ),
            'defaults' => array( 'operation' => 'encrypt' ),
        ) ) );

        $c->add_tab(
            array(
                'id'    => 'pdf',
                'label' => __( 'PDF & Documents', 'davix-h2i' ),
                'icon'  => 'dashicons-media-document',
                'group' => 'work',
                'order' => 50,
                'desc'  => __( 'Compress new PDFs and create thumbnails automatically', 'davix-h2i' ),
            )
        );
        $c->add_subtab(
            'pdf',
            array(
                'id'         => 'auto',
                'label'      => __( 'Automatic', 'davix-h2i' ),
                'order'      => 10,
                'operations' => array( 'pdfs.optimize', 'pdfs.to-images', 'pdfs.watermark' ),
                'render'     => function ( $context ) {
                    // Every automatic action of the PDF and document family, whichever module registered it.
                    $ids = array();
                    foreach ( $context->auto_actions() as $id => $action ) {
                        if ( in_array( $action['group'], array( 'pdf', 'documents' ), true ) ) {
                            $ids[] = $id;
                        }
                    }
                    AutoPanel::render( $ids, array( 'lead' => __( 'Rules that run on every new PDF or document, in the background. To work on files you already have (merge, split, protect), open one in the Media Library.', 'davix-h2i' ) ) );
                },
            )
        );
        $c->add_auto_action(
            array(
                'id'      => 'pdfs.optimize',
                'description' => __( 'Make new PDFs smaller for faster downloads.', 'davix-h2i' ),
                'group'   => 'pdf',
                'order'   => 20,
                'accepts' => array( 'application/pdf' ),
                'types'   => array( 'application/pdf' => 'PDF' ),
            )
        );
        $c->add_auto_action(
            array(
                'id'      => 'pdfs.to-images',
                'description' => __( 'Make a picture of the first page (or the pages you choose) of every new PDF.', 'davix-h2i' ),
                'group'   => 'pdf',
                'order'   => 30,
                'accepts' => array( 'application/pdf' ),
                'types'   => array( 'application/pdf' => 'PDF' ),
            )
        );
        $c->add_auto_action(
            array(
                'id'          => 'pdfs.watermark',
                'description' => __( 'Stamp your text on every page of new PDFs. A copy is saved; the original is kept.', 'davix-h2i' ),
                'group'       => 'pdf',
                'order'       => 40,
                'accepts'     => array( 'application/pdf' ),
                'types'       => array( 'application/pdf' => 'PDF' ),
            )
        );
        $c->add_drawer_tab(
            array(
                'id'         => 'pdf',
                'label'      => __( 'PDF', 'davix-h2i' ),
                'order'      => 40,
                'kind'       => 'ops',
                'mimes'      => array( 'application/pdf' ),
                'operations' => $this->manifest['operations'],
            )
        );
    }

    public static function sanitize( array $v, array $d ) {
        $mode = isset( $v['output']['mode'] ) && 'copy' === $v['output']['mode'] ? 'copy' : 'replace';
        return array(
            'output' => array(
                'mode'   => $mode,
                'suffix' => isset( $v['output']['suffix'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $v['output']['suffix'] ) : '_optimized',
                'backup' => ! isset( $v['output']['backup'] ) || ! empty( $v['output']['backup'] ),
            ),
        );
    }
}
