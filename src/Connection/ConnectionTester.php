<?php
/**
 * Checks a key against the app (GET /v1/key/verify) and keeps a small snapshot of who is connected.
 */

namespace DavixWP\Connection;

use DavixWP\Capabilities\LimitsService;
use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ConnectionTester {

    /**
     * @param string|null $key A key typed in the field but not saved yet; null tests the saved key.
     * @return array{ok:bool,message:string,plan:string,email:string,key_type:string,request_id:string,code:string}
     */
    public static function test( $key = null ) {
        $result = array(
            'ok'         => false,
            'message'    => '',
            'plan'       => '',
            'email'      => '',
            'key_type'   => '',
            'request_id' => '',
            'code'       => '',
        );
        $key = null === $key || '' === trim( (string) $key ) ? null : trim( (string) $key );
        if ( null === $key && ! KeyStore::has() ) {
            $result['code']    = 'missing_key';
            $result['message'] = __( 'Enter an API key first.', 'davix-h2i' );
            return $result;
        }

        try {
            $client   = ClientFactory::make( $key );
            $response = $client->call( 'key.verify', array(), array( 'retry' => false ) );
            $data     = is_array( $response->data ) ? $response->data : array();
            $type     = isset( $data['key_type'] ) ? (string) $data['key_type'] : '';

            if ( 'customer' !== $type ) {
                $result['code']    = 'wrong_key_type';
                $result['message'] = __( 'This plugin needs a customer API key from your Customer Portal.', 'davix-h2i' );
                return $result;
            }

            $result['ok']         = true;
            $result['key_type']   = $type;
            $result['plan']       = isset( $data['plan_name'] ) ? (string) $data['plan_name'] : '';
            $result['email']      = isset( $data['customer_email'] ) ? (string) $data['customer_email'] : '';
            $result['request_id'] = (string) $response->requestId;
            $result['message']    = '' !== $result['plan']
                /* translators: %s: plan name */
                ? sprintf( __( 'Connected. Plan: %s.', 'davix-h2i' ), $result['plan'] )
                : __( 'Connected.', 'davix-h2i' );

            if ( null === $key ) {
                Options::update(
                    'connection',
                    array(
                        'verified_at' => time(),
                        'plan_name'   => $result['plan'],
                        'email'       => $result['email'],
                    )
                );
                LimitsService::refresh();
            }
        } catch ( \Throwable $e ) {
            $mapped               = ErrorMapper::map( $e );
            $result['code']       = $mapped['code'];
            $result['message']    = $mapped['message'];
            $result['request_id'] = $mapped['request_id'];
        }
        return $result;
    }
}
