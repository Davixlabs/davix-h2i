<?php

namespace DavixWP\Modules\Generators;

use DavixWP\Admin\Components\OpPanel;
use DavixWP\Core\Context;
use DavixWP\Core\Module;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class GeneratorsModule extends Module {

    public function register( Context $c ) {
        $c->add_operation(
            array(
                'id'          => 'images.qr',
                'label'       => __( 'QR code', 'davix-h2i' ),
                'description' => __( 'A QR code for a link or text, with an optional logo.', 'davix-h2i' ),
                'group'       => 'images',
                'release'     => '2.2',
                'input'       => 'none',
                'file_field'  => 'logo',
                'accepts'     => array(),
                'store'       => 'new',
                'extra'       => array( 'logo' => __( 'Logo in the middle (optional)', 'davix-h2i' ) ),
                'fields'      => array(
                    array( 'name' => 'text', 'label' => __( 'Link or text', 'davix-h2i' ), 'default' => home_url( '/' ) ),
                    'file_type',
                    'size',
                    'color',
                    'background',
                    'margin',
                    'error_correction',
                    'transparent',
                ),
            )
        );
        $c->add_operation(
            array(
                'id'          => 'images.favicon',
                'label'       => __( 'Site icon pack', 'davix-h2i' ),
                'description' => __( 'All the icon sizes for browsers and phones from one image.', 'davix-h2i' ),
                'group'       => 'images',
                'release'     => '2.2',
                'file_field'  => 'images',
                'multiple'    => false,
                'accepts'     => array( 'image/' ),
                'store'       => 'new',
                'fields'      => array( 'sizes', 'background', 'padding', 'app_name', 'short_name', 'theme_color' ),
                'defaults'    => array( 'app_name' => get_bloginfo( 'name' ), 'short_name' => mb_substr( get_bloginfo( 'name' ), 0, 12 ) ),
            )
        );

        $c->add_subtab(
            'create',
            array(
                'id'         => 'qr',
                'label'      => __( 'QR code', 'davix-h2i' ),
                'order'      => 50,
                'operations' => array( 'images.qr' ),
                'render'     => function () {
                    OpPanel::render( array( 'images.qr' ), array( 'title' => __( 'QR code', 'davix-h2i' ), 'lead' => __( 'A QR code for a link or text, with an optional logo. It is added to your Media Library.', 'davix-h2i' ), 'mode_control' => false, 'bulk' => false, 'selected' => 'images.qr', 'source' => 'generator' ) );
                },
            )
        );
        $c->add_drawer_tab(
            array(
                'id'         => 'icons',
                'label'      => __( 'Site icon', 'davix-h2i' ),
                'order'      => 70,
                'kind'       => 'ops',
                'mimes'      => array( 'image/' ),
                'operations' => array( 'images.favicon' ),
            )
        );
    }
}
