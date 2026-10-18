<?php
/**
 * The Media Library drawer: a side panel that opens on a chosen file (or several) with the actions that fit it.
 * The shell is printed once; its tabs come from the modules, and the generic "Actions" tab lists every operation the
 * file accepts. Forms are loaded on demand so the Media Library stays light.
 */

namespace DavixWP\Admin;

use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Connection\KeyStore;
use DavixWP\Core\Context;
use DavixWP\Core\Options;
use DavixWP\Rest\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Drawer {

    /** @var Context */
    private $context;

    public function __construct( Context $context ) {
        $this->context = $context;
    }

    public function hooks() {
        if ( ! KeyStore::has() ) {
            return;
        }
        add_action( 'admin_footer', array( $this, 'shell' ) );
        add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_field' ), 20, 2 );
        add_filter( 'media_row_actions', array( $this, 'row_action' ), 20, 2 );
        add_filter( 'bulk_actions-upload', array( $this, 'bulk_action' ) );
    }

    public static function register_routes( Context $context ) {
        Api::route(
            '/drawer/form',
            'GET',
            function ( \WP_REST_Request $r ) use ( $context ) {
                $def = $context->operation( (string) $r->get_param( 'operation' ) );
                if ( ! $def ) {
                    return Api::error( __( 'Unknown action.', 'davix-h2i' ), 404 );
                }
                ob_start();
                \DavixWP\Admin\Components\OpPanel::render_form( $def );
                if ( in_array( $def['store'], array( 'replace', 'copy' ), true ) ) {
                    echo '<div class="davix-h2i-field-group"><label class="davix-h2i-label">' . esc_html__( 'Result', 'davix-h2i' ) . '</label><select class="davix-h2i-select" data-dvx-drawer-mode><option value="">' . esc_html__( 'Use my default', 'davix-h2i' ) . '</option><option value="replace">' . esc_html__( 'Replace the original (a backup is kept)', 'davix-h2i' ) . '</option><option value="copy">' . esc_html__( 'Save as a new copy', 'davix-h2i' ) . '</option></select></div>';
                }
                return array( 'html' => ob_get_clean(), 'store' => $def['store'], 'input' => $def['input'], 'label' => $def['label'] );
            }
        );
    }

    private function on_screen() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        return $screen && in_array( $screen->base, array( 'upload', 'post' ), true );
    }

    public function attachment_field( $fields, $post ) {
        if ( ! current_user_can( Shell::use_capability() ) || ! $this->on_screen_or_ajax() ) {
            return $fields;
        }
        $fields['davix_h2i'] = array(
            'label' => __( 'Davix H2I', 'davix-h2i' ),
            'input' => 'html',
            'html'  => '<button type="button" class="button" data-dvx-open-drawer="' . (int) $post->ID . '">' . esc_html__( 'Optimize and edit…', 'davix-h2i' ) . '</button>',
        );
        return $fields;
    }

    private function on_screen_or_ajax() {
        return wp_doing_ajax() || $this->on_screen();
    }

    /** The list's bulk action: opens the sidebar on every ticked file (the script does it, nothing is sent to the server). */
    public function bulk_action( $actions ) {
        if ( current_user_can( Shell::use_capability() ) ) {
            $actions['davix_h2i_open'] = __( 'Open in Davix H2I', 'davix-h2i' );
        }
        return $actions;
    }

    public function row_action( $actions, $post ) {
        if ( current_user_can( Shell::use_capability() ) ) {
            $actions['davix_h2i'] = '<a href="#" data-dvx-open-drawer="' . (int) $post->ID . '">' . esc_html__( 'Davix H2I', 'davix-h2i' ) . '</a>';
        }
        return $actions;
    }

    public function shell() {
        if ( ! $this->on_screen() || ! current_user_can( Shell::use_capability() ) ) {
            return;
        }
        ?>
        <div id="dvx-drawer-backdrop" class="dvs-drawer-backdrop" hidden></div>
        <div id="davix-h2i-modal" class="dvs-drawer davix-admin davix-h2i-modal davix-h2i-theme" role="dialog" aria-modal="true" aria-labelledby="dvx-drawer-title" hidden>
            <div class="dvs-drawer-header davix-h2i-modal-header">
                <div class="dvs-drawer-brand davix-h2i-modal-title">
                    <img class="dvs-drawer-brand-logo" src="<?php echo esc_url( Assets::url( 'image/logo/davix-h2i-logo-primary-color-transparent-128.png' ) ); ?>" alt="" />
                    <h2 class="dvs-drawer-title" id="dvx-drawer-title"><?php esc_html_e( 'Davix H2I', 'davix-h2i' ); ?></h2>
                </div>
                <button type="button" class="dvs-drawer-close davix-h2i-close" data-dvx-drawer-close aria-label="<?php esc_attr_e( 'Close', 'davix-h2i' ); ?>">&times;</button>
            </div>
            <div class="dvs-drawer-tabs davix-h2i-modal-tabs" data-dvx-drawer-tabs></div>
            <div class="dvs-drawer-body davix-h2i-modal-body" data-dvx-drawer-body>
                <?php
                foreach ( $this->context->drawer_tabs() as $tab ) {
                    echo '<template data-dvx-drawer-template="' . esc_attr( $tab['id'] ) . '" data-label="' . esc_attr( $tab['label'] ) . '" data-mimes="' . esc_attr( implode( ',', $tab['mimes'] ) ) . '" data-ops="' . esc_attr( implode( ',', $tab['operations'] ) ) . '" data-kind="' . esc_attr( isset( $tab['kind'] ) ? $tab['kind'] : '' ) . '" data-any="' . ( ! empty( $tab['any'] ) ? '1' : '0' ) . '">';
                    if ( is_callable( $tab['render'] ) ) {
                        call_user_func( $tab['render'] );
                    }
                    echo '</template>';
                }
                ?>
                <div data-dvx-drawer-panel></div>
                <div class="dvx-results" data-dvx-drawer-results aria-live="polite"></div>
            </div>
            <div class="dvs-drawer-footer davix-h2i-drawer-footer">
                <div class="davix-h2i-actions">
                    <button type="button" class="dvs-save-button" data-dvx-drawer-run disabled><?php esc_html_e( 'Run', 'davix-h2i' ); ?></button>
                    <span class="dvx-muted" data-dvx-drawer-cost></span>
                </div>
            </div>
        </div>
        <?php
    }
}
