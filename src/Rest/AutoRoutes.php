<?php
/**
 * REST routes of the automatic rules screen: the list of rules and the actions that can be automatic, saving the list, and
 * the settings form of one action (the same form the Media Library sidebar shows).
 */

namespace DavixWP\Rest;

use DavixWP\Admin\Components\OpPanel;
use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Core\AutoRules;
use DavixWP\Core\Context;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AutoRoutes {

    /** @var Context */
    private static $context;

    public static function register( Context $context ) {
        self::$context = $context;
        Api::route( '/auto/rules', 'GET', array( __CLASS__, 'index' ), 'manage' );
        Api::route( '/auto/rules', 'POST', array( __CLASS__, 'save' ), 'manage' );
        Api::route( '/auto/form', 'GET', array( __CLASS__, 'form' ), 'manage' );
    }

    /** What a screen needs to draw the list and the editor. */
    public static function index() {
        $actions = array();
        foreach ( self::$context->auto_actions() as $id => $a ) {
            $store = '';
            $op    = self::$context->operation( $a['operation'] ? $a['operation'] : $id );
            if ( $op ) {
                $store = $op['store'];
            }
            $actions[ $id ] = array(
                'id'          => $id,
                'label'       => $a['label'],
                'description' => $a['description'],
                'group'       => $a['group'],
                'trigger'     => $a['trigger'],
                'types'       => is_callable( $a['types'] ) ? (array) call_user_func( $a['types'] ) : $a['types'],
                'accepts'     => $a['accepts'],
                'choose_mode' => in_array( $store, array( 'replace', 'copy' ), true ) || ! empty( $a['choose_mode'] ),
                'locked'      => $op && ! CapabilityGate::allows( $op['id'] ),
            );
        }
        return array( 'rules' => AutoRules::rules(), 'actions' => $actions );
    }

    public static function save( \WP_REST_Request $r ) {
        $rules = $r->get_param( 'rules' );
        if ( ! is_array( $rules ) ) {
            return Api::error( __( 'The rules are not valid.', 'davix-h2i' ) );
        }
        return array( 'ok' => true, 'rules' => AutoRules::save( $rules ) );
    }

    /** The settings of one action as a form, filled with the rule's own values when one is given. */
    public static function form( \WP_REST_Request $r ) {
        $id     = (string) $r->get_param( 'action' );
        $action = AutoRules::action( $id );
        if ( ! $action ) {
            return Api::error( __( 'Unknown action.', 'davix-h2i' ), 404 );
        }
        $params = array();
        $rule   = (string) $r->get_param( 'rule' );
        foreach ( AutoRules::rules() as $existing ) {
            if ( $existing['id'] === $rule ) {
                $params = $existing['params'];
            }
        }
        ob_start();
        if ( is_callable( $action['form'] ) ) {
            call_user_func( $action['form'], $params );
        } else {
            $def = self::$context->operation( $action['operation'] ? $action['operation'] : $id );
            if ( $def ) {
                OpPanel::render_form( $def );
            }
        }
        return array( 'html' => ob_get_clean(), 'params' => $params, 'virtual' => is_callable( $action['form'] ) );
    }
}
