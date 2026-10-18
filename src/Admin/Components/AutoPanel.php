<?php
/**
 * The "Automatic" screen of a module: its rules in a table (a row opens a side panel), a "New rule" button at the top.
 * The script (assets/js/core/auto.js) loads and saves the rules through the REST API.
 */

namespace DavixWP\Admin\Components;

use DavixWP\Admin\Ui;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AutoPanel {

    /**
     * @param string[] $action_ids The automatic actions this screen manages.
     * @param array    $args       title, lead
     */
    public static function render( array $action_ids, array $args = array() ) {
        $args = array_merge(
            array(
                'title' => __( 'Automatic', 'davix-h2i' ),
                'lead'  => __( 'Rules that run on every new upload, in the background. Uploads never wait for them.', 'davix-h2i' ),
                'trigger' => __( 'Run on new uploads', 'davix-h2i' ),
            ),
            $args
        );
        $new = '<button type="button" class="dvs-save-button" data-auto-new>' . esc_html__( 'New rule', 'davix-h2i' ) . '</button>';
        Ui::panel_open( $args['title'], array( 'actions' => $new, 'lead' => $args['lead'] ) );
        echo '<div data-auto-root data-actions="' . esc_attr( wp_json_encode( array_values( $action_ids ) ) ) . '"' . ( 1 === count( $action_ids ) ? ' data-single' : '' ) . '>';
        echo '<div class="dvx-table-wrap"><table class="dvx-table"><thead><tr><th>' . esc_html__( 'What it does', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Applies to', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Status', 'davix-h2i' ) . '</th></tr></thead>';
        echo '<tbody data-auto-body><tr><td colspan="3"><div class="dvs-empty-state"><p>' . esc_html__( 'Loading…', 'davix-h2i' ) . '</p></div></td></tr></tbody></table></div>';

        echo '<div class="dvs-drawer-backdrop" data-auto-backdrop hidden></div>';
        echo '<aside class="dvs-drawer dvx-side-drawer dvx-side-drawer--wide davix-admin davix-h2i-theme" data-auto-editor hidden role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Automatic rule', 'davix-h2i' ) . '">';
        echo '<div class="dvs-drawer-header"><div class="dvs-drawer-brand"><h2 class="dvs-drawer-title" data-auto-title></h2></div>';
        echo '<button type="button" class="dvs-drawer-close" data-auto-close aria-label="' . esc_attr__( 'Close', 'davix-h2i' ) . '">&times;</button></div>';
        echo '<div class="dvs-drawer-body dvx-side-drawer__body">';
        echo '<div class="davix-h2i-field-group" data-auto-actionbox><label class="davix-h2i-label" for="dvx-auto-action">' . esc_html__( 'What to do', 'davix-h2i' ) . '</label><select id="dvx-auto-action" class="davix-h2i-select" data-auto-action></select><p class="description" data-auto-desc></p></div>';
        echo '<div class="davix-h2i-field-group dvx-auto-row"><label class="davix-h2i-label" for="dvx-auto-enabled">' . esc_html( $args['trigger'] ) . '</label>';
        Ui::control( 'switch', 'dvx-auto-enabled', 'auto_enabled', 1, array( 'attrs' => 'data-auto-enabled' ) );
        echo '</div>';
        echo '<div class="davix-h2i-field-group" data-auto-typesbox><label class="davix-h2i-label">' . esc_html__( 'Applies to', 'davix-h2i' ) . '</label><div class="davix-h2i-checkbox-list" data-auto-types></div><p class="description">' . esc_html__( 'Leave all unticked to use every type this action supports.', 'davix-h2i' ) . '</p></div>';
        echo '<div class="davix-h2i-field-group" data-auto-uploadonly><label class="davix-h2i-label" for="dvx-auto-min">' . esc_html__( 'Smallest file (KB)', 'davix-h2i' ) . '</label><input type="number" min="0" id="dvx-auto-min" class="davix-h2i-input" data-auto-min placeholder="0" /><p class="description">' . esc_html__( 'Skip files smaller than this. 0 means every file.', 'davix-h2i' ) . '</p></div>';
        echo '<div class="davix-h2i-field-group dvx-auto-row" data-auto-uploadonly><label class="davix-h2i-label" for="dvx-auto-generated">' . esc_html__( 'Also files this plugin made', 'davix-h2i' ) . '</label>';
        Ui::control( 'switch', 'dvx-auto-generated', 'auto_generated', 0, array( 'attrs' => 'data-auto-generated' ) );
        echo '</div>';
        echo '<div class="davix-h2i-field-group" data-auto-modebox><label class="davix-h2i-label" for="dvx-auto-mode">' . esc_html__( 'Result', 'davix-h2i' ) . '</label><select id="dvx-auto-mode" class="davix-h2i-select" data-auto-mode><option value="">' . esc_html__( 'Replace the original (a backup is kept)', 'davix-h2i' ) . '</option><option value="copy">' . esc_html__( 'Save as a new copy', 'davix-h2i' ) . '</option></select></div>';
        echo '<h3 class="dvx-auto-subtitle">' . esc_html__( 'Settings', 'davix-h2i' ) . '</h3><div data-auto-form></div>';
        echo '</div><div class="dvs-drawer-footer dvx-side-drawer__foot">';
        echo '<button type="button" class="dvs-save-button" data-auto-save>' . esc_html__( 'Save', 'davix-h2i' ) . '</button>';
        echo '<button type="button" class="dvs-btn dvs-btn--danger" data-auto-delete hidden>' . esc_html__( 'Delete', 'davix-h2i' ) . '</button>';
        echo '</div></aside></div>';
        Ui::panel_close();
    }
}
