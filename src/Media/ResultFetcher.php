<?php
/**
 * Downloads a result file the app produced. The SDK only fetches output URLs on the configured host and never sends the
 * API key. When the app reports its own public address (behind a proxy or on another host name) the path is kept and the
 * configured address is used instead, so the same safety rules still apply.
 */

namespace DavixWP\Media;

use DavixWP\Connection\ClientFactory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ResultFetcher {

    const PATH_PATTERN = '#^/(render|image|pdf|ai|data|documents)/[^/\\\\]+$#';

    /**
     * @param array|string $file A result array with "url", or the URL.
     * @param string       $dest Where to save it.
     * @return array path, contentType, size
     */
    public static function fetch( $file, $dest ) {
        $client = ClientFactory::make();
        $url    = is_array( $file ) ? ( isset( $file['url'] ) ? (string) $file['url'] : '' ) : (string) $file;
        $url    = self::normalize( $url );
        return $client->download( $url, array( 'dest' => $dest ) );
    }

    /** Points an output URL at the configured server address (path and query stay as they are). */
    public static function normalize( $url ) {
        $parts = wp_parse_url( $url );
        if ( empty( $parts['path'] ) || ! preg_match( self::PATH_PATTERN, $parts['path'] ) ) {
            return $url;
        }
        $base = ClientFactory::base_url();
        return $base . $parts['path'] . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
    }
}
