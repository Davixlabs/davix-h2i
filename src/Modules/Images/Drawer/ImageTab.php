<?php

namespace DavixWP\Modules\Images\Drawer;

use DavixWP\Admin\Ui;
use DavixWP\Core\Options;
use DavixWP\Modules\Images\Admin\ChipPicker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** The "Image" tab of the Media Library drawer: the chip picker and the output choice. */
final class ImageTab {

    public static function render() {
        echo '<div class="davix-h2i-tab-section">';
        ChipPicker::render( 'drawer-image', 'drawer', array( 'smart' ), array() );
        echo '<div class="davix-h2i-output-options"><div class="davix-h2i-field-group"><label class="davix-h2i-label">' . esc_html__( 'Output mode', 'davix-h2i' ) . '</label>';
        Ui::control( 'select', 'dvx-drawer-image-mode', 'drawer[mode]', (string) Options::get( 'images.output.mode', 'replace' ), array( 'options' => array( 'replace' => __( 'Replace original (a backup is kept)', 'davix-h2i' ), 'copy' => __( 'Save new copy', 'davix-h2i' ) ) ) );
        echo '</div></div></div>';
    }
}
