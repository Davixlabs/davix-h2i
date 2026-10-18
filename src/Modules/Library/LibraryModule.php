<?php

namespace DavixWP\Modules\Library;

use DavixWP\Admin\Ui;
use DavixWP\Connection\ClientFactory;
use DavixWP\Connection\ErrorMapper;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\Core\Options;
use DavixWP\Rest\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Presets and templates saved in the customer's Davix H2I account. The same ones the Customer Portal and Studio show: whatever is
 * saved here appears there and the other way round. Presets are edited with the same option fields as the action itself.
 */
final class LibraryModule extends Module {

    /** @var Context */
    private $context;

    public function register( Context $c ) {
        $this->context = $c;
        $c->add_tab( array(
            'id' => 'library', 'label' => __( 'Library', 'davix-h2i' ), 'icon' => 'dashicons-portfolio', 'group' => 'manage', 'order' => 30,
            'desc' => __( 'Presets and templates saved in your account', 'davix-h2i' ),
        ) );
        $c->add_subtab( 'library', array( 'id' => 'presets', 'label' => __( 'Presets', 'davix-h2i' ), 'order' => 10, 'render' => array( $this, 'presets' ) ) );
        $c->add_subtab( 'library', array( 'id' => 'templates', 'label' => __( 'Templates', 'davix-h2i' ), 'order' => 20, 'render' => array( $this, 'templates' ) ) );
        $c->add_rest( array( $this, 'routes' ) );
        $c->add_assets( 'davix-h2i-library', array( 'css' => array(), 'js' => array( 'modules/library/library' ), 'screens' => array( 'tab:library' ) ) );
    }

    private function call( $id, array $params = array() ) {
        try {
            return array( 'ok' => true, 'data' => ClientFactory::make()->call( $id, $params )->data );
        } catch ( \Throwable $e ) {
            $m = ErrorMapper::map( $e );
            return array( 'ok' => false, 'message' => $m['message'], 'code' => $m['code'] );
        }
    }

    private static function object_param( \WP_REST_Request $r, $key ) {
        $value = $r->get_param( $key );
        if ( is_string( $value ) ) {
            $value = json_decode( $value, true );
        }
        return is_array( $value ) ? $value : null;
    }

    /** Variables as the service wants them: name, default, required, description. */
    private static function clean_variables( $list ) {
        $out = array();
        foreach ( (array) $list as $v ) {
            if ( ! is_array( $v ) || empty( $v['name'] ) ) {
                continue;
            }
            $row = array( 'name' => sanitize_text_field( $v['name'] ), 'required' => ! empty( $v['required'] ), 'description' => isset( $v['description'] ) ? sanitize_text_field( $v['description'] ) : '' );
            if ( isset( $v['default'] ) && '' !== (string) $v['default'] ) {
                $row['default'] = (string) $v['default'];
            }
            $out[] = $row;
        }
        return $out;
    }

    public function routes() {
        // ---- presets
        Api::route( '/library/presets', 'GET', function () {
            return $this->call( 'presets' );
        } );
        Api::route( '/library/presets', 'POST', function ( \WP_REST_Request $r ) {
            $params = self::object_param( $r, 'parameters' );
            if ( null === $params ) {
                return Api::error( __( 'The settings are not valid.', 'davix-h2i' ) );
            }
            return $this->call( 'presets.post', array( 'name' => sanitize_text_field( $r->get_param( 'name' ) ), 'endpoint' => sanitize_text_field( $r->get_param( 'endpoint' ) ), 'parameters' => (object) $params ) );
        }, 'manage' );
        Api::route( '/library/presets/(?P<id>[A-Za-z0-9_\-]+)', 'PUT', function ( \WP_REST_Request $r ) {
            $params = self::object_param( $r, 'parameters' );
            if ( null === $params ) {
                return Api::error( __( 'The settings are not valid.', 'davix-h2i' ) );
            }
            return $this->call( 'presets.{id}.put', array( 'id' => (string) $r['id'], 'name' => sanitize_text_field( $r->get_param( 'name' ) ), 'parameters' => (object) $params ) );
        }, 'manage' );
        Api::route( '/library/presets/(?P<id>[A-Za-z0-9_\-]+)', 'DELETE', function ( \WP_REST_Request $r ) {
            return $this->call( 'presets.{id}.delete', array( 'id' => (string) $r['id'] ) );
        }, 'manage' );

        // ---- templates
        Api::route( '/library/templates', 'GET', function () {
            return $this->call( 'templates', array( 'kind' => 'html' ) );
        } );
        Api::route( '/library/templates/(?P<id>[A-Za-z0-9_\-]+)', 'GET', function ( \WP_REST_Request $r ) {
            return $this->call( 'templates.{id}', array( 'id' => (string) $r['id'] ) );
        } );
        Api::route( '/library/templates', 'POST', function ( \WP_REST_Request $r ) {
            $body = array(
                'name' => sanitize_text_field( $r->get_param( 'name' ) ),
                'kind' => 'html',
                'html' => (string) $r->get_param( 'html' ),
                'css'  => (string) $r->get_param( 'css' ),
            );
            if ( '' !== (string) $r->get_param( 'description' ) ) {
                $body['description'] = sanitize_text_field( $r->get_param( 'description' ) );
            }
            $vars = self::clean_variables( $r->get_param( 'variables' ) );
            if ( $vars ) {
                $body['variables'] = $vars;
            }
            return $this->call( 'templates.post', $body );
        }, 'manage' );
        Api::route( '/library/templates/(?P<id>[A-Za-z0-9_\-]+)', 'PUT', function ( \WP_REST_Request $r ) {
            return $this->call(
                'templates.{id}.put',
                array(
                    'id'          => (string) $r['id'],
                    'name'        => sanitize_text_field( $r->get_param( 'name' ) ),
                    'description' => sanitize_text_field( (string) $r->get_param( 'description' ) ),
                    'html'        => (string) $r->get_param( 'html' ),
                    'css'         => (string) $r->get_param( 'css' ),
                    'variables'   => self::clean_variables( $r->get_param( 'variables' ) ),
                )
            );
        }, 'manage' );
        Api::route( '/library/templates/(?P<id>[A-Za-z0-9_\-]+)', 'DELETE', function ( \WP_REST_Request $r ) {
            return $this->call( 'templates.{id}.delete', array( 'id' => (string) $r['id'] ) );
        }, 'manage' );
        Api::route( '/library/templates/(?P<id>[A-Za-z0-9_\-]+)/use', 'POST', function ( \WP_REST_Request $r ) {
            $res = $this->call( 'templates.{id}', array( 'id' => (string) $r['id'] ) );
            if ( ! $res['ok'] ) {
                return $res;
            }
            $t = $res['data'];
            Options::update( 'render', array( 'html' => isset( $t['html'] ) ? (string) $t['html'] : '', 'css' => isset( $t['css'] ) ? (string) $t['css'] : '' ) );
            return array( 'ok' => true );
        }, 'manage' );
    }

    // ------------------------------------------------------------------ screens
    private function toolbar( $button_label ) {
        echo '<div class="dvx-toolbar" style="margin-bottom:16px">';
        if ( current_user_can( 'manage_options' ) ) {
            echo '<button type="button" class="dvs-save-button" data-lb-new>' . esc_html( $button_label ) . '</button>';
        }
        echo '<span class="dvx-muted" data-lb-limit></span></div>';
    }

    private function editor_open( $title ) {
        echo '<div class="dvs-drawer-backdrop" data-lb-backdrop hidden></div>';
        echo '<aside class="dvs-drawer dvx-side-drawer dvx-side-drawer--wide davix-admin davix-h2i-theme" data-lb-editor hidden role="dialog" aria-modal="true" aria-label="' . esc_attr( $title ) . '">';
        echo '<div class="dvs-drawer-header"><div class="dvs-drawer-brand"><h2 class="dvs-drawer-title" data-lb-editor-title>' . esc_html( $title ) . '</h2></div>';
        echo '<button type="button" class="dvs-drawer-close" data-lb-close aria-label="' . esc_attr__( 'Close', 'davix-h2i' ) . '">&times;</button></div>';
        echo '<div class="dvs-drawer-body dvx-side-drawer__body" data-lb-editor-body>';
    }

    private function editor_close( $extra_foot = '' ) {
        echo '</div><div class="dvs-drawer-footer dvx-side-drawer__foot">';
        echo '<button type="button" class="dvs-save-button" data-lb-save>' . esc_html__( 'Save', 'davix-h2i' ) . '</button>';
        echo $extra_foot; // phpcs:ignore WordPress.Security.EscapeOutput -- built here
        echo '<button type="button" class="dvs-btn dvs-btn--danger" data-lb-delete hidden>' . esc_html__( 'Delete', 'davix-h2i' ) . '</button>';
        echo '</div></aside>';
    }

    public function presets() {
        $ops = array();
        foreach ( $this->context->operations() as $id => $def ) {
            if ( ! empty( $def['fields'] ) ) {
                $ops[] = array( 'id' => $id, 'label' => $def['label'], 'group' => isset( $def['group'] ) ? $def['group'] : '' );
            }
        }
        Ui::panel_open( __( 'Presets', 'davix-h2i' ), array( 'lead' => __( 'Saved sets of options for an action. They live in your Davix H2I account, so the Customer Portal and Studio show the same presets.', 'davix-h2i' ) ) );
        echo '<div data-lb-root="presets" data-ops="' . esc_attr( wp_json_encode( $ops ) ) . '">';
        $this->toolbar( __( 'New preset', 'davix-h2i' ) );
        echo '<div class="dvx-table-wrap"><table class="dvx-table"><thead><tr><th>' . esc_html__( 'Name', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Action', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Updated', 'davix-h2i' ) . '</th></tr></thead><tbody data-lb-body><tr><td colspan="3"><div class="dvs-empty-state"><p>' . esc_html__( 'Loading…', 'davix-h2i' ) . '</p></div></td></tr></tbody></table></div>';
        $this->editor_open( __( 'Preset', 'davix-h2i' ) );
        echo '<div class="davix-h2i-field-group"><label class="davix-h2i-label" for="lb-name">' . esc_html__( 'Name', 'davix-h2i' ) . '</label><input type="text" id="lb-name" class="davix-h2i-input" maxlength="100" data-lb-field="name" /></div>';
        echo '<div class="davix-h2i-field-group"><label class="davix-h2i-label" for="lb-endpoint">' . esc_html__( 'Action', 'davix-h2i' ) . '</label><select id="lb-endpoint" class="davix-h2i-select" data-lb-field="endpoint"></select><p class="description davix-h2i-helptext">' . esc_html__( 'A preset belongs to one action and cannot be moved to another.', 'davix-h2i' ) . '</p></div>';
        echo '<div data-lb-params></div>';
        $this->editor_close();
        echo '</div>';
        Ui::panel_close();
    }

    public function templates() {
        Ui::panel_open( __( 'Templates', 'davix-h2i' ), array( 'lead' => __( 'HTML documents with {{variables}} that you render later. They live in your account, so the Customer Portal and Studio show the same templates.', 'davix-h2i' ) ) );
        echo '<div data-lb-root="templates">';
        $this->toolbar( __( 'New template', 'davix-h2i' ) );
        echo '<div class="dvx-table-wrap"><table class="dvx-table"><thead><tr><th>' . esc_html__( 'Name', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Variables', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Updated', 'davix-h2i' ) . '</th></tr></thead><tbody data-lb-body><tr><td colspan="3"><div class="dvs-empty-state"><p>' . esc_html__( 'Loading…', 'davix-h2i' ) . '</p></div></td></tr></tbody></table></div>';
        $this->editor_open( __( 'Template', 'davix-h2i' ) );
        echo '<div class="davix-h2i-field-group"><label class="davix-h2i-label" for="lb-name">' . esc_html__( 'Name', 'davix-h2i' ) . '</label><input type="text" id="lb-name" class="davix-h2i-input" maxlength="100" data-lb-field="name" /></div>';
        echo '<div class="davix-h2i-field-group"><label class="davix-h2i-label" for="lb-description">' . esc_html__( 'Description', 'davix-h2i' ) . '</label><input type="text" id="lb-description" class="davix-h2i-input" maxlength="300" data-lb-field="description" /></div>';
        foreach ( array( 'html' => 'HTML', 'css' => 'CSS' ) as $key => $label ) {
            echo '<div class="davix-h2i-field-group davix-h2i-field-row--stacked"><div class="davix-h2i-field-header"><label class="davix-h2i-label" for="lb-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
            echo '<button type="button" class="dvs-icon-btn dvs-icon-btn--ghost" data-dvx-expand="#lb-' . esc_attr( $key ) . '" data-expand-title="' . esc_attr( $label ) . '" aria-label="' . esc_attr__( 'Expand to full-screen editor', 'davix-h2i' ) . '" title="' . esc_attr__( 'Expand to full-screen editor', 'davix-h2i' ) . '"><span class="dashicons dashicons-fullscreen-alt" aria-hidden="true"></span></button></div>';
            echo '<textarea id="lb-' . esc_attr( $key ) . '" rows="10" class="davix-h2i-textarea dvs-code-editor-textarea dvs-scrollbar" spellcheck="false" data-lb-field="' . esc_attr( $key ) . '"></textarea></div>';
        }
        echo '<div class="davix-h2i-field-group"><label class="davix-h2i-label">' . esc_html__( 'Variables', 'davix-h2i' ) . '</label><p class="description davix-h2i-helptext">' . esc_html__( 'Found automatically from the {{names}} in your HTML. Give each one a default value and say if it is required.', 'davix-h2i' ) . '</p><div class="dvx-vars" data-lb-vars></div></div>';
        $this->editor_close( '<button type="button" class="dvs-btn" data-lb-use hidden>' . esc_html__( 'Use in H2I Render', 'davix-h2i' ) . '</button>' );
        echo '</div>';
        Ui::panel_close();
    }
}
