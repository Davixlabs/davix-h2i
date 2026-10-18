<?php
/**
 * The only place that enqueues CSS and JS. Core files load on every plugin screen; each module's files load only when
 * its tab is open (or on the Media Library / editor for the drawer).
 */

namespace DavixWP\Admin;

use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Connection\KeyStore;
use DavixWP\Core\Context;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Assets {

    /** Loaded in this order: the cascade of the original stylesheet depends on it. */
    const CORE_CSS = array(
        'core/tokens', 'core/shell', 'core/panel', 'core/controls', 'core/fullscreen-editor', 'core/drawer', 'core/buttons',
        'core/components', 'core/chips', 'core/extras', 'core/content', 'core/responsive', 'core/forms-layout', 'core/media-library',
        'core/log-viewer', 'core/data', 'core/notification-center', 'core/connection-test', 'core/dropdown-kit', 'kit/tokens',
    );

    const CORE_JS = array(
        'core/dvx', 'core/notification-center', 'core/tabs', 'core/forms', 'core/chips', 'core/tips', 'core/expand', 'core/plan-gate', 'core/runner', 'core/auto', 'core/jobs', 'core/drawer', 'core/connection-test', 'core/dropdown-kit', 'core/dropdown',
    );

    /** @var Context */
    private $context;

    public function __construct( Context $context ) {
        $this->context = $context;
    }

    public function hooks() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
    }

    public static function url( $relative ) {
        return DAVIX_H2I_URL . 'assets/' . ltrim( $relative, '/' );
    }

    /** The file's own time while debugging (so edits show at once), the plugin version otherwise. */
    private static function ver( $relative ) {
        $file = DAVIX_H2I_DIR . 'assets/' . ltrim( $relative, '/' );
        return defined( 'WP_DEBUG' ) && WP_DEBUG && is_file( $file ) ? DAVIX_H2I_VERSION . '.' . filemtime( $file ) : DAVIX_H2I_VERSION;
    }

    private static function exists( $relative ) {
        return is_file( DAVIX_H2I_DIR . 'assets/' . ltrim( $relative, '/' ) );
    }

    public function enqueue( $hook ) {
        $screens = array();
        $tab     = '';
        if ( 'toplevel_page_davix-h2i' === $hook ) {
            $tab       = Shell::active_tab( $this->context );
            $screens[] = 'tab:' . $tab;
            $screens[] = 'plugin';
        }
        if ( in_array( $hook, array( 'upload.php', 'post.php', 'post-new.php' ), true ) ) {
            $screens[] = 'drawer';
            if ( 'upload.php' !== $hook ) {
                $screens[] = 'editor';
            }
        }
        if ( ! $screens ) {
            return;
        }

        $last = $this->enqueue_core( in_array( 'plugin', $screens, true ), $screens );

        foreach ( $this->context->assets() as $handle => $asset ) {
            if ( ! array_intersect( $asset['screens'], $screens ) ) {
                continue;
            }
            $deps = array_merge( array( $last ), $asset['deps'] );
            foreach ( $asset['css'] as $i => $css ) {
                if ( self::exists( 'css/' . $css . '.css' ) ) {
                    wp_enqueue_style( $handle . '-' . $i, self::url( 'css/' . $css . '.css' ), array( 'davix-h2i-core-css' ), self::ver( 'css/' . $css . '.css' ) );
                }
            }
            foreach ( $asset['js'] as $i => $js ) {
                if ( self::exists( 'js/' . $js . '.js' ) ) {
                    wp_enqueue_script( $handle . '-' . $i, self::url( 'js/' . $js . '.js' ), $deps, self::ver( 'js/' . $js . '.js' ), true );
                    if ( 0 === $i && is_callable( $asset['data'] ) ) {
                        wp_localize_script( $handle . '-' . $i, 'DavixH2I_' . str_replace( '-', '_', $handle ), call_user_func( $asset['data'] ) );
                    }
                }
            }
        }
    }

    /** @return string Handle of the last core script (module scripts depend on it). */
    private function enqueue_core( $plugin_screen, array $screens ) {
        $prev = '';
        foreach ( self::CORE_CSS as $css ) {
            if ( ! self::exists( 'css/' . $css . '.css' ) ) {
                continue;
            }
            $handle = 'davix-h2i-' . str_replace( '/', '-', $css );
            wp_enqueue_style( $handle, self::url( 'css/' . $css . '.css' ), $prev ? array( $prev ) : array(), self::ver( 'css/' . $css . '.css' ) );
            $prev = $handle;
        }
        wp_register_style( 'davix-h2i-core-css', false, $prev ? array( $prev ) : array(), DAVIX_H2I_VERSION );
        wp_enqueue_style( 'davix-h2i-core-css' );
        wp_enqueue_style( 'dashicons' );

        wp_register_script( 'davix-h2i-core', false, array( 'jquery' ), DAVIX_H2I_VERSION, true );
        wp_enqueue_script( 'davix-h2i-core' );
        $prev = 'davix-h2i-core';
        foreach ( self::CORE_JS as $js ) {
            if ( ! self::exists( 'js/' . $js . '.js' ) ) {
                continue;
            }
            $handle = 'davix-h2i-' . str_replace( '/', '-', $js );
            wp_enqueue_script( $handle, self::url( 'js/' . $js . '.js' ), array( $prev ), self::ver( 'js/' . $js . '.js' ), true );
            $prev = $handle;
        }
        $last_js = $prev;
        if ( in_array( 'drawer', $screens, true ) || in_array( 'plugin', $screens, true ) ) {
            wp_enqueue_media();
        }

        $ops    = array_keys( $this->context->operations() );
        $labels = array();
        foreach ( $this->context->operations() as $op_id => $op_def ) {
            $labels[ $op_id ] = $op_def['label'];
        }
        wp_localize_script(
            'davix-h2i-core',
            'DavixH2I',
            array(
                'rest'      => esc_url_raw( rest_url( 'davix-h2i/v2/' ) ),
                'nonce'     => wp_create_nonce( 'wp_rest' ),
                'adminUrl'  => admin_url( 'admin.php?page=davix-h2i' ),
                'logo'      => self::url( 'image/logo/davix-h2i-logo-primary-color-transparent-128.png' ),
                'connected' => KeyStore::has(),
                'gate'      => CapabilityGate::for_js( $ops ),
                'opLabels'  => $labels,
                'showUpgrade' => (bool) \DavixWP\Core\Options::get( 'runtime.upgrade_badges', true ),
                'i18n'      => array(
                    'saved'      => __( 'Settings saved.', 'davix-h2i' ),
                    'saveFailed' => __( 'Could not save settings.', 'davix-h2i' ),
                    'working'    => __( 'Working…', 'davix-h2i' ),
                    'done'       => __( 'Done', 'davix-h2i' ),
                    'failed'     => __( 'Failed', 'davix-h2i' ),
                    'confirm'    => __( 'Are you sure?', 'davix-h2i' ),
                    'upgrade'    => __( 'Upgrade', 'davix-h2i' ),
                    'beta'       => __( 'Beta', 'davix-h2i' ),
                    'noFiles'    => __( 'Choose at least one file first.', 'davix-h2i' ),
                    'chooseFiles' => __( 'Choose from Media Library', 'davix-h2i' ),
                    'units'      => __( 'units', 'davix-h2i' ),
                ),
            )
        );
        return $last_js;
    }
}
