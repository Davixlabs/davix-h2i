<?php

namespace DavixWP\Modules\Documents;

use DavixWP\Core\Context;
use DavixWP\Core\Module;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class DocumentsModule extends Module {

    public function register( Context $c ) {
        $c->add_operation(
            array(
                'id'          => 'documents.convert',
                'label'       => __( 'Convert a document', 'davix-h2i' ),
                'description' => __( 'Word, Excel, PowerPoint, PDF and more, to another format.', 'davix-h2i' ),
                'group'       => 'documents',
                'release'     => '2.2',
                'file_field'  => 'file',
                'multiple'    => false,
                'accepts'     => array( 'application/pdf', 'application/vnd.', 'application/msword', 'application/rtf', 'text/rtf', 'text/plain', 'text/csv', 'image/' ),
                'store'       => 'new',
                'fields'      => array( 'to', 'pdfaLevel', 'optimize', 'password' ),
                'defaults'    => array( 'to' => 'pdf' ),
            )
        );
        $c->add_auto_action(
            array(
                'id'          => 'documents.convert',
                'description' => __( 'Turn new Word, Excel, PowerPoint or text files into PDFs (or another format) as they arrive.', 'davix-h2i' ),
                'group'       => 'documents',
                'order'       => 50,
                'accepts'     => array( 'application/vnd.', 'application/msword', 'application/rtf', 'text/rtf', 'text/plain', 'text/csv' ),
                'types'       => array(
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'Word (.docx)',
                    'application/msword'                                                        => 'Word (.doc)',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'Excel (.xlsx)',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint (.pptx)',
                    'application/rtf'                                                           => 'RTF',
                    'text/plain'                                                                => 'Text',
                    'text/csv'                                                                  => 'CSV',
                ),
            )
        );
        // The screen lives inside the PDF & Documents tab, which the PDF module owns. The tab is found by id.
        $c->add_drawer_tab(
            array(
                'id'         => 'documents',
                'label'      => __( 'Convert', 'davix-h2i' ),
                'order'      => 50,
                'kind'       => 'ops',
                'mimes'      => array(),
                'operations' => array( 'documents.convert' ),
            )
        );
    }
}
