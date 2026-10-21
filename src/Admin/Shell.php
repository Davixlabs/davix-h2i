<?php
/**
 * The admin page: header, collapsible sidebar (grouped tabs), sub-tabs and the active tab's content. Only the active tab
 * is rendered; the browser never loads the others.
 */

namespace DavixWP\Admin;

use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Connection\KeyStore;
use DavixWP\Core\Context;
use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Shell {

    const SLUG = 'davix-h2i';

    /** @var Context */
    private $context;

    public function __construct( Context $context ) {
        $this->context = $context;
    }

    public function hooks() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_filter( 'admin_body_class', array( $this, 'body_class' ) );
        add_filter( 'plugin_action_links_' . DAVIX_H2I_BASENAME, array( $this, 'action_links' ) );
        add_action( 'admin_notices', array( $this, 'connect_notice' ) );
    }

    public function menu() {
        add_menu_page(
            __( 'Davix H2I', 'davix-h2i' ),
            __( 'Davix H2I', 'davix-h2i' ),
            self::use_capability(),
            self::SLUG,
            array( $this, 'render' ),
            'none',
            58
        );
    }

    /** The class every canvas rule in shell.css hangs on: it turns the whole WordPress content area into the plugin's own surface. */
    public function body_class( $classes ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && 'toplevel_page_' . self::SLUG === $screen->id ) {
            $classes .= ' davix-h2i-admin-page';
        }
        return $classes;
    }

    public static function use_capability() {
        return (string) Options::get( 'runtime.access.use', 'upload_files' );
    }

    public function action_links( $links ) {
        array_unshift( $links, '<a href="' . esc_url( self::url( 'account' ) ) . '">' . esc_html__( 'Settings', 'davix-h2i' ) . '</a>' );
        return $links;
    }

    public function connect_notice() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( KeyStore::has() || ! $screen || in_array( $screen->id, array( 'toplevel_page_davix-h2i' ), true ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( ! in_array( $screen->base, array( 'plugins', 'upload', 'dashboard' ), true ) ) {
            return;
        }
        echo '<div class="notice notice-info is-dismissible"><p>' . wp_kses(
            sprintf(
                /* translators: %s: link */
                __( 'Davix H2I is almost ready. %s to start optimizing your media.', 'davix-h2i' ),
                '<a href="' . esc_url( self::url( 'account' ) ) . '">' . esc_html__( 'Add your API key', 'davix-h2i' ) . '</a>'
            ),
            array( 'a' => array( 'href' => array() ) )
        ) . '</p></div>';
    }

    public static function url( $tab = '', $subtab = '', array $args = array() ) {
        $query = array( 'page' => self::SLUG );
        if ( $tab ) {
            $query['tab'] = $tab;
        }
        if ( $subtab ) {
            $query['subtab'] = $subtab;
        }
        return add_query_arg( array_merge( $query, $args ), admin_url( 'admin.php' ) );
    }

    public static function active_tab( Context $context ) {
        $tabs = $context->tabs();
        $tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( isset( $tabs[ $tab ] ) ) {
            return $tab;
        }
        if ( ! KeyStore::has() && isset( $tabs['account'] ) ) {
            return 'account';
        }
        return isset( $tabs['overview'] ) ? 'overview' : (string) key( $tabs );
    }

    public static function active_subtab( Context $context, $tab_id ) {
        $subtabs = $context->subtabs( $tab_id );
        $sub     = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( isset( $subtabs[ $sub ] ) ) {
            return $sub;
        }
        return $subtabs ? (string) key( $subtabs ) : '';
    }

    public function render() {
        $tabs = $this->context->tabs();
        $tab  = self::active_tab( $this->context );
        if ( ! isset( $tabs[ $tab ] ) ) {
            echo '<div class="wrap"><p>' . esc_html__( 'Nothing to show.', 'davix-h2i' ) . '</p></div>';
            return;
        }
        $current = $tabs[ $tab ];
        $groups  = array(
            'work'    => __( 'Work', 'davix-h2i' ),
            'manage'  => __( 'Manage', 'davix-h2i' ),
            'account' => __( 'Account', 'davix-h2i' ),
        );
        ?>
        <div class="davix-admin" id="davix-h2i-admin" data-dvx-tab="<?php echo esc_attr( $tab ); ?>">
            <header class="davix-admin-header">
                <div class="davix-admin-header-brand">
                    <img class="davix-admin-header-logo" src="<?php echo esc_url( Assets::url( 'image/logo/davix-h2i-logo-primary-color-transparent-128.png' ) ); ?>" alt="<?php esc_attr_e( 'Davix', 'davix-h2i' ); ?>" />
                    <div class="davix-admin-header-info">
                        <span class="davix-admin-header-title">Davix <span><?php esc_html_e( 'H2I', 'davix-h2i' ); ?></span></span>
                        <span class="davix-admin-header-subtitle"><?php echo esc_html( $current['desc'] ? $current['desc'] : __( 'Your Davix H2I account, inside WordPress', 'davix-h2i' ) ); ?></span>
                    </div>
                </div>
                <div class="davix-admin-header-actions">
                    <?php $plan = CapabilityGate::plan(); ?>
                    <?php if ( $plan && ! empty( $plan['name'] ) ) : ?>
                        <span class="dvs-badge dvs-badge--info davix-admin-header-plan"><?php echo esc_html( $plan['name'] ); ?></span>
                    <?php endif; ?>
                    <a class="davix-admin-header-link" href="<?php echo esc_url( 'https://h2i.davixlabs.io/docs/wordpress-plugin/' ); ?>" target="_blank" rel="noopener noreferrer">
                        <span class="dashicons dashicons-book" aria-hidden="true"></span>
                        <?php esc_html_e( 'Docs', 'davix-h2i' ); ?>
                    </a>
                </div>
            </header>
            <div class="davix-admin-body">
                <aside class="davix-admin-sidebar" id="davix-h2i-sidebar">
                    <div class="davix-admin-sidebar-head">
                        <button type="button" class="davix-admin-sidebar-toggle" aria-label="<?php esc_attr_e( 'Toggle sidebar', 'davix-h2i' ); ?>"><span class="dashicons dashicons-menu"></span></button>
                    </div>
                    <nav class="davix-admin-nav" aria-label="<?php esc_attr_e( 'Davix H2I', 'davix-h2i' ); ?>">
                        <?php
                        $last_group = '';
                        foreach ( $tabs as $id => $item ) {
                            if ( ! current_user_can( $item['capability'] ) ) {
                                continue;
                            }
                            if ( $item['group'] !== $last_group ) {
                                $last_group = $item['group'];
                                echo '<div class="davix-admin-nav-group">' . esc_html( isset( $groups[ $last_group ] ) ? $groups[ $last_group ] : '' ) . '</div>';
                            }
                            printf(
                                '<a class="davix-admin-nav-item %1$s" data-tab="%2$s" href="%3$s"><span class="dashicons %4$s"></span><span class="davix-admin-nav-label">%5$s</span></a>',
                                $id === $tab ? 'is-active' : '',
                                esc_attr( $id ),
                                esc_url( self::url( $id ) ),
                                esc_attr( $item['icon'] ),
                                esc_html( $item['label'] )
                            );
                        }
                        ?>
                    </nav>
                </aside>
                <main class="davix-admin-content">
                    <div class="wrap davix-h2i-settings-screen davix-h2i-settings davix-h2i-theme davix-h2i-settings-container" data-davix-h2i-scope="settings">
                        <h1 class="davix-h2i-visually-hidden"><?php echo esc_html( $current['label'] ); ?></h1>
                        <div id="dvx-notices" aria-live="polite"></div>
                        <?php $this->render_tab( $tab, $current ); ?>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }

    private function render_tab( $tab_id, array $tab ) {
        if ( 'account' !== $tab_id && ! KeyStore::has() ) {
            $this->connect_card();
            return;
        }
        $subtabs = $this->context->subtabs( $tab_id );
        if ( ! $subtabs ) {
            echo '<div class="davix-h2i-tab-panel active" data-davix-h2i-tab="' . esc_attr( $tab_id ) . '">';
            if ( is_callable( $tab['render'] ) ) {
                call_user_func( $tab['render'], $this->context );
            }
            echo '</div>';
            return;
        }
        $active = self::active_subtab( $this->context, $tab_id );
        echo '<div class="davix-h2i-tab-panel active" data-davix-h2i-tab="' . esc_attr( $tab_id ) . '">';
        // A tab with a single screen needs no bar of sub-tabs.
        if ( ! empty( $tab['picker'] ) && count( $subtabs ) > 1 ) {
            echo '<div class="davix-h2i-picker"><label for="davix-h2i-picker-select">' . esc_html__( 'Choose what to do', 'davix-h2i' ) . '</label><select id="davix-h2i-picker-select" data-davix-h2i-picker>';
            foreach ( $subtabs as $id => $sub ) {
                printf( '<option value="%1$s" %2$s>%3$s</option>', esc_url( self::url( $tab_id, $id ) ), selected( $id, $active, false ), esc_html( $sub['label'] ) );
            }
            echo '</select></div>';
            $subtabs_bar = false;
        } else {
            $subtabs_bar = count( $subtabs ) > 1;
        }
        echo $subtabs_bar ? '<nav class="davix-h2i-subtabs nav-tab-wrapper davix-h2i-settings-tabs" aria-label="' . esc_attr( $tab['label'] ) . '">' : '';
        foreach ( $subtabs_bar ? $subtabs : array() as $id => $sub ) {
            $state = $this->subtab_state( $sub );
            printf(
                '<a href="%1$s" class="nav-tab %2$s" data-subtab="%3$s">%4$s%5$s</a>',
                esc_url( self::url( $tab_id, $id ) ),
                $id === $active ? 'nav-tab-active' : '',
                esc_attr( $id ),
                esc_html( $sub['label'] ),
                $state // phpcs:ignore WordPress.Security.EscapeOutput -- Ui::badge escapes
            );
        }
        echo $subtabs_bar ? '</nav>' : '';
        echo '<div class="davix-h2i-subtab-panel active" data-subtab="' . esc_attr( $active ) . '">';
        if ( isset( $subtabs[ $active ] ) && is_callable( $subtabs[ $active ]['render'] ) ) {
            call_user_func( $subtabs[ $active ]['render'], $this->context );
        }
        echo '</div></div>';
    }

    /** An "Upgrade" or "Beta" badge for a sub-tab whose operations are all locked or beta. */
    private function subtab_state( array $sub ) {
        if ( empty( $sub['operations'] ) || ! CapabilityGate::known() ) {
            return '';
        }
        $locked = 0;
        $beta   = 0;
        foreach ( $sub['operations'] as $op ) {
            $state = CapabilityGate::state( $op );
            if ( CapabilityGate::LOCKED === $state || CapabilityGate::UNAVAILABLE === $state ) {
                $locked++;
            } elseif ( CapabilityGate::BETA === $state ) {
                $beta++;
            }
        }
        if ( $locked === count( $sub['operations'] ) && Options::get( 'runtime.upgrade_badges', true ) ) {
            return ' ' . Ui::badge( __( 'Upgrade', 'davix-h2i' ), 'warning' );
        }
        if ( $beta ) {
            return ' ' . Ui::badge( __( 'Beta', 'davix-h2i' ), 'info' );
        }
        return '';
    }

    private function connect_card() {
        echo '<div class="davix-h2i-tab-panel active">';
        Ui::panel_open( __( 'Connect your Davix H2I account', 'davix-h2i' ), array( 'lead' => __( 'Add your API key to start. You can create one in your Dashboard.', 'davix-h2i' ) ) );
        Ui::empty_state(
            __( 'This site is not connected yet.', 'davix-h2i' ),
            'dashicons-admin-network',
            '<a class="dvs-save-button" href="' . esc_url( self::url( 'account' ) ) . '">' . esc_html__( 'Add API key', 'davix-h2i' ) . '</a>'
        );
        Ui::panel_close();
        echo '</div>';
    }
}
