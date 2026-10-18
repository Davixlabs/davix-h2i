<?php
/**
 * Activity: the background queue (Jobs) and AI suggestions waiting for approval (Review).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'activity',
    'label'      => 'Activity',
    'release'    => '2.0',
    'order'      => 65,
    'operations' => array(),
);
