<?php

namespace DavixWP\Modules\Help;

use DavixWP\Admin\Ui;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\Core\Options;
use DavixWP\Jobs\Queue;
use DavixWP\Operations\Contract;
use DavixWP\Rest\Api;
use DavixWP\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HelpModule extends Module {

    const LINKS = array(
        'support' => 'https://h2i.davixlabs.io/support/',
        'docs'    => 'https://h2i.davixlabs.io/docs/wordpress-plugin/',
        'keys'    => 'https://h2i.davixlabs.io/docs/quickstart/get-your-api-key/',
        'trouble' => 'https://h2i.davixlabs.io/docs/wordpress-plugin/logging-and-troubleshooting/',
    );

    public function register( Context $c ) {
        $c->add_tab( array(
            'id' => 'help', 'label' => __( 'Help', 'davix-h2i' ), 'icon' => 'dashicons-editor-help', 'group' => 'account', 'order' => 30,
            'capability' => 'manage_options', 'desc' => __( 'About, support and the debug log', 'davix-h2i' ),
        ) );
        $c->add_subtab( 'help', array( 'id' => 'about', 'label' => __( 'About', 'davix-h2i' ), 'order' => 10, 'render' => array( $this, 'about' ) ) );
        $c->add_subtab( 'help', array( 'id' => 'support', 'label' => __( 'Support', 'davix-h2i' ), 'order' => 20, 'render' => array( $this, 'support' ) ) );
        $c->add_subtab( 'help', array( 'id' => 'debug', 'label' => __( 'Debug log', 'davix-h2i' ), 'order' => 30, 'render' => array( $this, 'debug' ) ) );
        $c->add_rest( array( $this, 'routes' ) );
        $c->add_assets( 'davix-h2i-help', array( 'css' => array( 'modules/help/help' ), 'js' => array( 'modules/help/help' ), 'screens' => array( 'tab:help' ) ) );
    }

    public function routes() {
        Api::route( '/logs', 'GET', function ( \WP_REST_Request $r ) {
            return array( 'enabled' => Logger::enabled(), 'lines' => Logger::tail( min( 1000, max( 10, (int) $r->get_param( 'lines' ) ? (int) $r->get_param( 'lines' ) : 300 ) ) ) );
        }, 'manage' );
        Api::route( '/logs/clear', 'POST', function () {
            Logger::clear();
            return array( 'ok' => true );
        }, 'manage' );
    }

    public function about() {
        Ui::panel_open( __( 'About', 'davix-h2i' ), array( 'lead' => __( 'Use your Davix H2I account inside WordPress.', 'davix-h2i' ) ) );
        Ui::card_open( __( 'Davix H2I for WordPress', 'davix-h2i' ) );
        echo '<p>' . esc_html__( 'Optimize and edit images, make social cards, add alt text and SEO with AI, work with PDFs and keep a history of everything it does. It uses your own Davix H2I plan, so you always see what you can use and what it costs.', 'davix-h2i' ) . '</p>';
        echo '<p class="description">' . esc_html( sprintf( /* translators: 1: plugin, 2: SDK, 3: contract */ __( 'Version %1$s · Davix H2I PHP SDK %2$s · API contract %3$s', 'davix-h2i' ), DAVIX_H2I_VERSION, class_exists( '\Davix\H2I\Client' ) ? \Davix\H2I\Client::SDK_VERSION : '-', Contract::version() ) ) . '</p>';
        Ui::card_close();
        Ui::panel_close();
    }

    public function support() {
        Ui::panel_open( __( 'Support', 'davix-h2i' ), array( 'lead' => __( 'Need a hand? Send us the details below with your question.', 'davix-h2i' ) ) );
        Ui::card_open( __( 'Get help', 'davix-h2i' ) );
        echo '<div class="dvx-toolbar">';
        foreach ( array( 'support' => __( 'Contact support', 'davix-h2i' ), 'docs' => __( 'Documentation', 'davix-h2i' ), 'keys' => __( 'Get your API key', 'davix-h2i' ), 'trouble' => __( 'Troubleshooting', 'davix-h2i' ) ) as $key => $label ) {
            echo '<a class="dvs-btn" target="_blank" rel="noopener noreferrer" href="' . esc_url( self::LINKS[ $key ] ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</div>';
        Ui::card_close();
        Ui::card_open( __( 'System information', 'davix-h2i' ), __( 'Contains no API keys or personal data.', 'davix-h2i' ) );
        echo '<textarea class="davix-h2i-textarea dvs-code-editor-textarea" rows="14" readonly data-hp-sysinfo>' . esc_textarea( $this->sysinfo() ) . '</textarea>';
        echo '<div class="dvx-toolbar" style="margin-top:12px"><button type="button" class="dvs-btn" data-hp-copy>' . esc_html__( 'Copy', 'davix-h2i' ) . '</button></div>';
        Ui::card_close();
        Ui::panel_close();
    }

    private function sysinfo() {
        global $wp_version;
        $counts = Queue::counts();
        $lines  = array(
            'Plugin: ' . DAVIX_H2I_VERSION,
            'SDK: ' . ( class_exists( '\Davix\H2I\Client' ) ? \Davix\H2I\Client::SDK_VERSION : '-' ),
            'Contract: ' . Contract::version(),
            'WordPress: ' . $wp_version . ( is_multisite() ? ' (multisite)' : '' ),
            'PHP: ' . PHP_VERSION,
            'Memory limit: ' . ini_get( 'memory_limit' ),
            'Max upload: ' . size_format( wp_max_upload_size() ),
            'cURL: ' . ( function_exists( 'curl_version' ) ? curl_version()['version'] : 'no' ),
            'Sodium: ' . ( function_exists( 'sodium_crypto_secretbox' ) ? 'yes' : 'no' ),
            'Key stored: ' . \DavixWP\Connection\KeyStore::source(),
            'Server: ' . \DavixWP\Connection\ClientFactory::base_url(),
            'WP-Cron: ' . ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'disabled (needs a system cron)' : 'on' ),
            'Next queue run: ' . ( wp_next_scheduled( 'davix_h2i_run_queue' ) ? gmdate( 'Y-m-d H:i:s', wp_next_scheduled( 'davix_h2i_run_queue' ) ) . ' UTC' : 'not scheduled' ),
            'Queue: ' . wp_json_encode( $counts ),
            'Uploads writable: ' . ( wp_is_writable( wp_upload_dir()['basedir'] ) ? 'yes' : 'no' ),
            'Logging: ' . ( Logger::enabled() ? 'on' : 'off' ),
            'Locale: ' . get_locale(),
        );
        return implode( "\n", $lines );
    }

    public function debug() {
        Ui::form_open( 'runtime' );
        Ui::panel_open( __( 'Debug log', 'davix-h2i' ), array( 'save' => true, 'lead' => __( 'Switch logging on while you troubleshoot. Keys and signed links are never written.', 'davix-h2i' ) ) );
        Ui::card_open( __( 'Logging', 'davix-h2i' ) );
        Ui::rows( array( array( 'name' => 'runtime.log_enabled', 'type' => 'switch', 'label' => __( 'Write a debug log', 'davix-h2i' ) ) ) );
        Ui::card_close();
        Ui::panel_close();
        Ui::form_close( false );

        Ui::panel_open( __( 'Log', 'davix-h2i' ), array( 'actions' => '<button type="button" class="dvs-btn" data-hp-refresh>' . esc_html__( 'Refresh', 'davix-h2i' ) . '</button><button type="button" class="dvs-btn dvs-btn--danger" data-hp-clear>' . esc_html__( 'Clear', 'davix-h2i' ) . '</button>' ) );
        echo '<div class="dvs-log-viewer"><div class="dvs-log-viewport dvs-scrollbar" data-hp-log style="max-height:480px;overflow:auto"></div></div>';
        Ui::panel_close();
    }
}
