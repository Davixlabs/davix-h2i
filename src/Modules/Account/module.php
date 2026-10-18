<?php
/**
 * Account: connect the site with a customer API key, and see the plan and what is left of it.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'account',
    'label'      => 'Account',
    'release'    => '2.0',
    'order'      => 10,
    'operations' => array(),
);
