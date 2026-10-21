<?php
/**
 * Plugin Name: Davix H2I
 * Plugin URI: https://h2i.davixlabs.io/docs/wordpress-plugin/
 * Description: Use your Davix H2I account inside WordPress: optimize and edit images, make social cards, add alt text and SEO with AI, work with PDFs, and keep a history of everything it does.
 * Version: 2.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Davix Labs
 * Author URI: https://davix.dev
 * Update URI: https://github.com/Davixlabs/davix-h2i
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: davix-h2i
 * Domain Path: /languages
 *
 * This file only boots the plugin. Everything else lives in src/ (PHP, one folder per feature under src/Modules),
 * assets/ (every CSS, JS and image file) and vendor/ (the Davix H2I PHP SDK, exactly as the app publishes it).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DAVIX_H2I_VERSION', '2.1.0' );
define( 'DAVIX_H2I_FILE', __FILE__ );
define( 'DAVIX_H2I_DIR', plugin_dir_path( __FILE__ ) );
define( 'DAVIX_H2I_URL', plugin_dir_url( __FILE__ ) );
define( 'DAVIX_H2I_BASENAME', plugin_basename( __FILE__ ) );
define( 'DAVIX_H2I_MIN_PHP', '7.4' );
// The Davix H2I server this plugin talks to. Hard-coded on purpose. (A site can still point it elsewhere with DAVIX_H2I_SERVER_URL in
// wp-config.php, or the davix_h2i_base_url filter; a local test setup does.)
if ( ! defined( 'DAVIX_H2I_SERVER_URL' ) ) {
    define( 'DAVIX_H2I_SERVER_URL', 'https://app.h2i.davixlabs.io' );
}

if ( version_compare( PHP_VERSION, DAVIX_H2I_MIN_PHP, '<' ) ) {
    add_action(
        'admin_notices',
        static function () {
            echo '<div class="notice notice-error"><p>' . esc_html(
                sprintf(
                    /* translators: 1: required PHP version, 2: current PHP version */
                    __( 'Davix H2I needs PHP %1$s or newer. This site runs PHP %2$s, so the plugin is switched off.', 'davix-h2i' ),
                    DAVIX_H2I_MIN_PHP,
                    PHP_VERSION
                )
            ) . '</p></div>';
        }
    );
    return;
}

require_once DAVIX_H2I_DIR . 'src/Autoloader.php';
\DavixWP\Autoloader::register( DAVIX_H2I_DIR . 'src/' );

// The Davix H2I PHP SDK, unmodified (vendor/davix-h2i-sdk). If another plugin already loaded the SDK, that copy is used.
if ( ! class_exists( '\Davix\H2I\Client', false ) ) {
    require_once DAVIX_H2I_DIR . 'vendor/davix-h2i-sdk/autoload.php';
}

register_activation_hook( DAVIX_H2I_FILE, array( '\DavixWP\Core\Installer', 'activate' ) );
register_deactivation_hook( DAVIX_H2I_FILE, array( '\DavixWP\Core\Installer', 'deactivate' ) );

add_action(
    'plugins_loaded',
    static function () {
        \DavixWP\Updates\Updater::register(); // new versions are offered on the Plugins page (see src/Updates)
        \DavixWP\Core\Plugin::instance()->boot();
    }
);
