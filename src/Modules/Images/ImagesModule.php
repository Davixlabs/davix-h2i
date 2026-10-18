<?php

namespace DavixWP\Modules\Images;

use DavixWP\Admin\Components\AutoPanel;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\Modules\Images\Admin\ChipPicker;
use DavixWP\Modules\Images\Drawer\ImageTab;
use DavixWP\Modules\Images\Rest\Routes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Images: optimize, convert, resize, edit, watermark, privacy, responsive sizes. Everything is run from the Media Library
 * sidebar (the Image tab holds the chips); this tab only holds the rules that run on every new upload.
 */
final class ImagesModule extends Module {

    const AUTO_ACTIONS = array( 'images.chips' );

    public function register( Context $c ) {
        Settings::register();
        Operations::register( $c );

        $c->add_tab(
            array(
                'id'    => 'images',
                'label' => __( 'Images', 'davix-h2i' ),
                'icon'  => 'dashicons-format-image',
                'group' => 'work',
                'order' => 20,
                'desc'  => __( 'Optimize and edit new images automatically', 'davix-h2i' ),
            )
        );
        $c->add_subtab(
            'images',
            array(
                'id'         => 'auto',
                'label'      => __( 'Automatic', 'davix-h2i' ),
                'order'      => 10,
                'operations' => array( 'images.auto-optimize' ),
                'render'     => function () {
                    AutoPanel::render(
                        self::AUTO_ACTIONS,
                        array( 'lead' => __( 'Rules that run on every new image, in the background. Uploads never wait for them. To work on images you already have, open one in the Media Library.', 'davix-h2i' ) )
                    );
                },
            )
        );
        $image_types = array( 'image/jpeg' => 'JPEG', 'image/png' => 'PNG', 'image/webp' => 'WebP', 'image/gif' => 'GIF', 'image/avif' => 'AVIF' );
        $c->add_auto_action(
            array(
                'id'          => 'images.chips',
                'label'       => __( 'Optimize and edit', 'davix-h2i' ),
                'description' => __( 'Smart optimization, format, resize, crop, effects, watermark and more, in one step.', 'davix-h2i' ),
                'group'       => 'images',
                'order'       => 20,
                'accepts'     => array( 'image/' ),
                'types'       => $image_types,
                'choose_mode' => true,
                'form'        => function ( $params ) {
                    $chips  = isset( $params['chips'] ) && is_array( $params['chips'] ) ? $params['chips'] : array( 'smart' );
                    $fields = isset( $params['fields'] ) && is_array( $params['fields'] ) ? $params['fields'] : array();
                    echo '<div class="davix-h2i-tab-section">';
                    ChipPicker::render( 'auto-rule', 'auto', $chips, $fields );
                    echo '</div>';
                },
                'plan'        => function ( $params ) {
                    return Chips::build( isset( $params['chips'] ) ? (array) $params['chips'] : array(), isset( $params['fields'] ) ? (array) $params['fields'] : array() );
                },
            )
        );
        $c->add_assets(
            'davix-h2i-images',
            array(
                'css'     => array( 'modules/images/images' ),
                'js'      => array( 'modules/images/images' ),
                'screens' => array( 'tab:images' ),
            )
        );

        $c->add_drawer_tab(
            array(
                'id'     => 'image',
                'label'  => __( 'Image', 'davix-h2i' ),
                'order'  => 10,
                'mimes'  => array( 'image/' ),
                'render' => array( ImageTab::class, 'render' ),
            )
        );
        $c->add_drawer_tab(
            array(
                'id'         => 'image-tools',
                'label'      => __( 'More image tools', 'davix-h2i' ),
                'order'      => 11,
                'kind'       => 'ops',
                'mimes'      => array( 'image/' ),
                'operations' => array( 'images.remove-background', 'images.variants', 'images.metadata' ),
            )
        );
        $c->add_assets(
            'davix-h2i-images-drawer',
            array(
                'css'     => array(),
                'js'      => array( 'modules/images/drawer' ),
                'screens' => array( 'drawer' ),
            )
        );
        $c->add_rest( array( new Routes(), 'register' ) );
        $c->add_cli( 'optimize', Cli\OptimizeCommand::class );
    }
}
