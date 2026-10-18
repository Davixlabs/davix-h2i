<?php

namespace DavixWP\Modules\Account;

use DavixWP\Core\Context;
use DavixWP\Core\Module;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AccountModule extends Module {

    public function register( Context $c ) {
        $c->add_tab(
            array(
                'id'         => 'account',
                'label'      => __( 'Account', 'davix-h2i' ),
                'icon'       => 'dashicons-admin-users',
                'group'      => 'account',
                'order'      => 10,
                'capability' => 'manage_options',
                'desc'       => __( 'Connect your Davix H2I account and see your plan', 'davix-h2i' ),
            )
        );
        $c->add_subtab(
            'account',
            array(
                'id'     => 'connection',
                'label'  => __( 'Connection', 'davix-h2i' ),
                'order'  => 10,
                'render' => array( new Admin\ConnectionPage(), 'render' ),
            )
        );
        $c->add_subtab(
            'account',
            array(
                'id'     => 'plan',
                'label'  => __( 'Plan & usage', 'davix-h2i' ),
                'order'  => 20,
                'render' => array( new Admin\PlanPage(), 'render' ),
            )
        );
        $c->add_assets(
            'davix-h2i-account',
            array(
                'css'     => array( 'modules/account/account' ),
                'js'      => array( 'modules/account/account' ),
                'screens' => array( 'tab:account' ),
            )
        );
    }
}
