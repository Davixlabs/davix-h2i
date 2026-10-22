<?php
/**
 * Boots the plugin: settings, modules, admin shell, assets, REST, cron and WP-CLI.
 */

namespace DavixWP\Core;

use DavixWP\Admin\Assets;
use DavixWP\Admin\Shell;
use DavixWP\Jobs\Queue;
use DavixWP\Jobs\Worker;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Plugin {

    /** @var Plugin|null */
    private static $instance = null;

    /** @var bool */
    private $booted = false;

    /** @var Context */
    private $context;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function context() {
        return $this->context;
    }

    public function boot() {
        if ( $this->booted ) {
            return;
        }
        $this->booted  = true;
        $this->context = new Context();
        Services::instance( 'context', $this->context );

        CoreSettings::register();
        AutoRules::register_settings();
        AutoRules::hooks();

        // The modules write translated labels while they register, and WordPress wants translations only from init on (earlier, WP 6.7+ reports
        // "translation loading triggered too early", and on a site that shows notices the output breaks the sign-in cookie). So they load first thing at init.
        add_action( 'init', array( $this, 'load_modules' ), 0 );
        add_action( 'init', array( $this, 'init' ) );
        add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval
        add_action( 'davix_h2i_run_queue', array( $this, 'run_queue' ) );
        add_action( 'davix_h2i_run_queue_now', array( $this, 'run_queue' ) );
        add_action( 'davix_h2i_prune', array( $this, 'prune' ) );
        add_action( 'rest_api_init', array( $this, 'rest' ) );

        $shell = new Shell( $this->context );
        $shell->hooks();
        $assets = new Assets( $this->context );
        $assets->hooks();
        ( new \DavixWP\Admin\Drawer( $this->context ) )->hooks();
    }

    /** Loads every module (init, priority 0). Their commands for WP-CLI are added here too, once they are known. */
    public function load_modules() {
        $loader = new ModuleLoader( DAVIX_H2I_DIR . 'src/Modules' );
        $loader->load( $this->context );

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'davix', '\DavixWP\Cli\Commands' );
            foreach ( $this->context->cli_commands() as $name => $class ) {
                \WP_CLI::add_command( 'davix ' . $name, $class );
            }
        }
    }

    public function init() {
        Installer::maybe_upgrade();
        load_plugin_textdomain( 'davix-h2i', false, dirname( DAVIX_H2I_BASENAME ) . '/languages' );
    }

    public function cron_schedules( $schedules ) {
        $schedules['davix_h2i_minute'] = array(
            'interval' => 60,
            'display'  => __( 'Every minute (Davix H2I)', 'davix-h2i' ),
        );
        return $schedules;
    }

    public function run_queue() {
        Worker::run( 25 );
    }

    public function prune() {
        \DavixWP\History\Repository::prune( (int) Options::get( 'runtime.history_retention', 90 ) );
        Queue::prune( (int) Options::get( 'runtime.jobs_retention', 14 ) );
    }

    public function rest() {
        \DavixWP\Rest\CoreRoutes::register( $this->context );
        \DavixWP\Rest\AutoRoutes::register( $this->context );
        \DavixWP\Admin\Drawer::register_routes( $this->context );
        foreach ( $this->context->rest_registrars() as $registrar ) {
            call_user_func( $registrar, \DavixWP\Rest\Api::NAMESPACE_V2 );
        }
    }
}
