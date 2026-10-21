<?php
/**
 * Plugin updates from GitHub, shown the normal WordPress way: "There is a new version of Davix H2I available. Update now" on the Plugins page and under
 * Dashboard > Updates, with the "View details" window, and WordPress's own switch for automatic updates.
 *
 * How it stays safe:
 *  - The plugin header carries "Update URI: https://github.com/Davixlabs/davix-h2i". WordPress then never asks wordpress.org about this plugin (nobody can
 *    publish a plugin with the same name there and have it offered here), and asks this class through the update_plugins_github.com filter instead.
 *  - The release comes from ReleaseSource (this repository's published releases only, an address built here).
 *  - Before WordPress installs the zip, its SHA-256 is compared with the checksum file the release workflow published next to it. A zip that does not
 *    match is thrown away and nothing is installed.
 *  - Only a HIGHER version is offered; a site never goes back.
 *
 * @package DavixWP
 */

namespace DavixWP\Updates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Updater {

    const ACTION = 'davix_h2i_check_update';

    public static function register() {
        add_filter( 'update_plugins_github.com', array( __CLASS__, 'offer' ), 10, 4 );
        add_filter( 'plugins_api', array( __CLASS__, 'details' ), 20, 3 );
        add_filter( 'upgrader_pre_download', array( __CLASS__, 'verify_download' ), 10, 4 );
        add_filter( 'plugin_action_links_' . DAVIX_H2I_BASENAME, array( __CLASS__, 'action_links' ) );
        add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'check_now' ) );
        add_action( 'upgrader_process_complete', array( __CLASS__, 'after_upgrade' ), 10, 2 );
    }

    /** WordPress asks (update_plugins_github.com): array = an update is available, false = none. */
    public static function offer( $update, $plugin_data, $plugin_file, $locales = array() ) {
        if ( DAVIX_H2I_BASENAME !== $plugin_file ) {
            return $update;
        }
        $release = ReleaseSource::latest();
        if ( ! $release || ! version_compare( $release['version'], DAVIX_H2I_VERSION, '>' ) ) {
            return false;
        }
        return array(
            'id'           => 'github.com/' . ReleaseSource::REPO,
            'slug'         => 'davix-h2i',
            'version'      => $release['version'],
            'url'          => $release['url'],
            'package'      => $release['package'],
            'requires'     => '6.0',
            'requires_php' => DAVIX_H2I_MIN_PHP,
        );
    }

    /** The "View details" window of the update. */
    public static function details( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || ! is_object( $args ) || ! isset( $args->slug ) || 'davix-h2i' !== $args->slug ) {
            return $result;
        }
        $release = ReleaseSource::latest();
        $version = $release ? $release['version'] : DAVIX_H2I_VERSION;
        $notes   = $release && '' !== $release['notes'] ? wp_kses_post( nl2br( esc_html( $release['notes'] ) ) ) : esc_html__( 'See the release page for what changed.', 'davix-h2i' );
        return (object) array(
            'name'          => 'Davix H2I',
            'slug'          => 'davix-h2i',
            'version'       => $version,
            'author'        => '<a href="https://davix.dev">Davix Labs</a>',
            'homepage'      => 'https://h2i.davixlabs.io/docs/wordpress-plugin/',
            'requires'      => '6.0',
            'requires_php'  => DAVIX_H2I_MIN_PHP,
            'last_updated'  => $release ? $release['published'] : '',
            'download_link' => $release ? $release['package'] : '',
            'sections'      => array(
                'description' => esc_html__( 'Use your Davix H2I account inside WordPress.', 'davix-h2i' ),
                'changelog'   => $notes,
            ),
        );
    }

    /**
     * Before WordPress installs our zip: download it and compare its SHA-256 with the checksum file of the release. Any other package is left alone.
     *
     * @return mixed false/original reply for other packages, a file path when the zip is good, a WP_Error when it is not.
     */
    public static function verify_download( $reply, $package, $upgrader = null, $hook_extra = array() ) {
        if ( ! is_string( $package ) || 0 !== strpos( $package, ReleaseSource::package_prefix() ) ) {
            return $reply;
        }
        if ( ! preg_match( '#/releases/download/v([0-9]+\.[0-9]+\.[0-9]+)/davix-h2i-\1\.zip$#', $package, $m ) || $package !== ReleaseSource::package_url( $m[1] ) ) {
            return new \WP_Error( 'davix_h2i_package', __( 'The update package address is not one of this plugin\'s releases, so nothing was installed.', 'davix-h2i' ) );
        }
        $sum = wp_remote_get( ReleaseSource::checksum_url( $m[1] ), array( 'timeout' => 15, 'headers' => array( 'User-Agent' => 'davix-h2i-wordpress-plugin' ) ) );
        $expected = '';
        if ( ! is_wp_error( $sum ) && 200 === (int) wp_remote_retrieve_response_code( $sum ) && preg_match( '/\b([a-f0-9]{64})\b/i', wp_remote_retrieve_body( $sum ), $h ) ) {
            $expected = strtolower( $h[1] );
        }
        if ( '' === $expected ) {
            return new \WP_Error( 'davix_h2i_checksum_missing', __( 'The checksum of the update could not be read, so nothing was installed. Try again in a few minutes.', 'davix-h2i' ) );
        }
        $file = download_url( $package, 300 );
        if ( is_wp_error( $file ) ) {
            return $file;
        }
        if ( ! hash_equals( $expected, strtolower( (string) hash_file( 'sha256', $file ) ) ) ) {
            if ( function_exists( 'wp_delete_file' ) ) {
                wp_delete_file( $file );
            }
            return new \WP_Error( 'davix_h2i_checksum', __( 'The downloaded update does not match its checksum, so nothing was installed.', 'davix-h2i' ) );
        }
        return $file;
    }

    /** "Check for updates" next to Deactivate on the Plugins page. */
    public static function action_links( $links ) {
        if ( current_user_can( 'update_plugins' ) ) {
            $url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION );
            array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check for updates', 'davix-h2i' ) . '</a>' );
        }
        return $links;
    }

    public static function check_now() {
        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_die( esc_html__( 'You are not allowed to do this.', 'davix-h2i' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( self::ACTION );
        ReleaseSource::forget();
        delete_site_transient( 'update_plugins' ); // WordPress asks again straight away
        wp_update_plugins();
        wp_safe_redirect( admin_url( 'plugins.php' ) );
        exit;
    }

    public static function after_upgrade( $upgrader, $options ) {
        if ( is_array( $options ) && isset( $options['type'], $options['plugins'] ) && 'plugin' === $options['type'] && in_array( DAVIX_H2I_BASENAME, (array) $options['plugins'], true ) ) {
            ReleaseSource::forget();
        }
    }
}
