<?php
/**
 * Where the plugin learns about new versions: the public GitHub releases of this plugin's repository.
 *
 * A release is trusted only in a narrow form: a published (not draft, not pre-release) release whose tag looks like v2.1.0 and that carries the zip and the
 * checksum file the release workflow builds (davix-h2i-2.1.0.zip and davix-h2i-2.1.0.zip.sha256). The download address is never taken from the answer: it is
 * built here from the version, so it can only ever point at this repository's own release files.
 *
 * Asked at most every few hours (the answer is cached); when the GitHub API cannot be used (a busy shared address hits its limit) the page of the latest
 * release is asked instead, which has no limit. A failed check is remembered for a short while so a site is never slowed down by it.
 *
 * @package DavixWP
 */

namespace DavixWP\Updates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ReleaseSource {

    const REPO      = 'Davixlabs/davix-h2i';
    const CACHE_KEY = 'davix_h2i_latest_release';
    const TTL       = 21600; // six hours
    const FAIL_TTL  = 1800;  // a failed check is not repeated for half an hour

    /** The newest release as a short, checked array, or null (nothing newer to offer, or GitHub could not be asked). */
    public static function latest( $force = false ) {
        if ( ! $force ) {
            $cached = get_transient( self::CACHE_KEY );
            if ( is_array( $cached ) ) {
                return isset( $cached['version'] ) ? $cached : null; // an array without a version is a remembered failure
            }
        }
        $release = self::from_api();
        if ( null === $release ) {
            $release = self::from_page();
        }
        set_transient( self::CACHE_KEY, $release ? $release : array( 'failed' => 1 ), $release ? self::TTL : self::FAIL_TTL );
        return $release;
    }

    public static function forget() {
        delete_transient( self::CACHE_KEY );
    }

    /** The address of a release's zip and of its checksum file, built from the version (never copied from an answer). */
    public static function package_url( $version ) {
        return 'https://github.com/' . self::REPO . '/releases/download/v' . $version . '/davix-h2i-' . $version . '.zip';
    }

    public static function checksum_url( $version ) {
        return self::package_url( $version ) . '.sha256';
    }

    public static function package_prefix() {
        return 'https://github.com/' . self::REPO . '/releases/download/';
    }

    private static function from_api() {
        $res = wp_remote_get(
            'https://api.github.com/repos/' . self::REPO . '/releases/latest',
            array( 'timeout' => 8, 'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'davix-h2i-wordpress-plugin' ) )
        );
        if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
            return null;
        }
        $body = json_decode( wp_remote_retrieve_body( $res ), true );
        return is_array( $body ) ? self::parse( $body ) : null;
    }

    private static function from_page() {
        $res = wp_remote_get(
            'https://github.com/' . self::REPO . '/releases/latest',
            array( 'timeout' => 8, 'redirection' => 0, 'headers' => array( 'User-Agent' => 'davix-h2i-wordpress-plugin' ) )
        );
        if ( is_wp_error( $res ) ) {
            return null;
        }
        return self::from_location( (string) wp_remote_retrieve_header( $res, 'location' ) );
    }

    /** Pure: the release as the API describes it, or null when it is not one we would install. */
    public static function parse( array $release ) {
        if ( ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ) {
            return null;
        }
        $version = self::version_of( isset( $release['tag_name'] ) ? (string) $release['tag_name'] : '' );
        if ( null === $version ) {
            return null;
        }
        $names = array();
        foreach ( isset( $release['assets'] ) && is_array( $release['assets'] ) ? $release['assets'] : array() as $asset ) {
            if ( is_array( $asset ) && isset( $asset['name'] ) ) {
                $names[] = (string) $asset['name'];
            }
        }
        // the zip AND its checksum must be attached: a release that is still being built is not offered yet
        if ( ! in_array( 'davix-h2i-' . $version . '.zip', $names, true ) || ! in_array( 'davix-h2i-' . $version . '.zip.sha256', $names, true ) ) {
            return null;
        }
        return array(
            'version'   => $version,
            'url'       => 'https://github.com/' . self::REPO . '/releases/tag/v' . $version,
            'package'   => self::package_url( $version ),
            'checksum'  => self::checksum_url( $version ),
            'notes'     => isset( $release['body'] ) ? (string) $release['body'] : '',
            'published' => isset( $release['published_at'] ) ? (string) $release['published_at'] : '',
        );
    }

    /** Pure: the release named by the address the "latest release" page redirects to (no notes, the files are assumed attached). */
    public static function from_location( $location ) {
        if ( ! preg_match( '#^https://github\.com/' . preg_quote( self::REPO, '#' ) . '/releases/tag/(v[0-9]+\.[0-9]+\.[0-9]+)$#', trim( $location ), $m ) ) {
            return null;
        }
        $version = self::version_of( $m[1] );
        if ( null === $version ) {
            return null;
        }
        return array(
            'version'   => $version,
            'url'       => 'https://github.com/' . self::REPO . '/releases/tag/v' . $version,
            'package'   => self::package_url( $version ),
            'checksum'  => self::checksum_url( $version ),
            'notes'     => '',
            'published' => '',
        );
    }

    /** "v2.1.0" -> "2.1.0"; anything else -> null. */
    public static function version_of( $tag ) {
        return preg_match( '/^v([0-9]+\.[0-9]+\.[0-9]+)$/', $tag, $m ) ? $m[1] : null;
    }
}
