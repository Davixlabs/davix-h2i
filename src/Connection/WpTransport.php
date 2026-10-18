<?php
/**
 * The SDK transport that sends requests through WordPress (wp_remote_request). It honours the site's proxy and SSL
 * settings and works on hosts without the PHP curl extension. The SDK builds the (multipart) body as a string.
 */

namespace DavixWP\Connection;

use Davix\H2I\Exceptions\NetworkException;
use Davix\H2I\Exceptions\TimeoutException;
use Davix\H2I\Http\HttpResponse;
use Davix\H2I\Http\TransportInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class WpTransport implements TransportInterface {

    public function send( $method, $url, array $headers, $body, $timeoutMs ) {
        $args = array(
            'method'      => $method,
            'headers'     => $headers,
            'timeout'     => $timeoutMs > 0 ? max( 1, (int) ceil( $timeoutMs / 1000 ) ) : 300,
            'redirection' => 0,
            'sslverify'   => true,
            'decompress'  => true,
        );
        if ( null !== $body ) {
            $args['body'] = $body;
        }

        // Local development hosts may use a self-signed certificate. A server address fixed in wp-config.php is trusted as is.
        $host = (string) wp_parse_url( $url, PHP_URL_HOST );
        if ( defined( 'DAVIX_H2I_BASE_URL' ) && (string) wp_parse_url( DAVIX_H2I_BASE_URL, PHP_URL_HOST ) === $host ) {
            $args['reject_unsafe_urls'] = false;
        }
        if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) || '.localhost' === substr( $host, -10 ) ) {
            $args['sslverify'] = (bool) apply_filters( 'davix_h2i_ssl_verify_local', false );
            $args['reject_unsafe_urls'] = false;
        }

        $response = wp_remote_request( $url, apply_filters( 'davix_h2i_http_args', $args, $url ) );

        if ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
            $code    = (string) $response->get_error_code();
            if ( false !== stripos( $message, 'timed out' ) || false !== stripos( $code, 'timeout' ) || false !== stripos( $message, 'cURL error 28' ) ) {
                throw new TimeoutException( 'Request timed out after ' . (int) $timeoutMs . ' ms.' );
            }
            throw new NetworkException( 'Network error: ' . $message );
        }

        $out = array();
        $raw = wp_remote_retrieve_headers( $response );
        if ( is_object( $raw ) && method_exists( $raw, 'getAll' ) ) {
            $raw = $raw->getAll();
        }
        foreach ( (array) $raw as $name => $value ) {
            $out[ strtolower( (string) $name ) ] = is_array( $value ) ? implode( ', ', $value ) : (string) $value;
        }

        return new HttpResponse( (int) wp_remote_retrieve_response_code( $response ), $out, (string) wp_remote_retrieve_body( $response ) );
    }
}
