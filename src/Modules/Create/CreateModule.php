<?php

namespace DavixWP\Modules\Create;

use DavixWP\Core\Context;
use DavixWP\Core\Module;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Only the tab. Each module adds its own sub-tab to it by id, so this module knows none of them. */
final class CreateModule extends Module {

    public function register( Context $c ) {
        $c->add_tab(
            array(
                'id'    => 'create',
                'label' => __( 'Create', 'davix-h2i' ),
                'icon'  => 'dashicons-plus-alt2',
                'group' => 'work',
                'order' => 10,
                'desc'  => __( 'Make something new: write text, create images, turn a page into an image or PDF, charts, QR codes', 'davix-h2i' ),
            )
        );
    }
}
