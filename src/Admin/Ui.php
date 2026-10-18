<?php
/**
 * The plugin's design components as PHP: panel, card, setting rows, form, badges. They print exactly the markup and class
 * names the existing stylesheet already styles (dvs-panel, dvs-settings-card, dvs-switch, davix-h2i-select, ...).
 */

namespace DavixWP\Admin;

use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Ui {

    /** @var int */
    private static $uid = 0;

    public static function uid( $prefix = 'dvx' ) {
        self::$uid++;
        return $prefix . '-' . self::$uid;
    }

    // ---------------------------------------------------------------- panel
    /**
     * @param string $title
     * @param array  $args actions (html string, already escaped), lead (text), save (bool: show the Save button in the header)
     */
    public static function panel_open( $title, array $args = array() ) {
        $args = array_merge( array( 'actions' => '', 'lead' => '', 'save' => false ), $args );
        echo '<div class="dvs-panel dvs-panel-shell">';
        echo '<div class="dvs-panel-header dvs-panel-header-main"><div class="dvs-panel-title-wrap"><h2 class="dvs-panel-title">' . esc_html( $title ) . '</h2></div>';
        echo '<div class="dvs-header-actions">';
        echo $args['actions']; // phpcs:ignore WordPress.Security.EscapeOutput -- built by Ui helpers
        if ( $args['save'] ) {
            echo '<button type="submit" class="dvs-save-button">' . esc_html__( 'Save Settings', 'davix-h2i' ) . '</button>';
        }
        echo '</div></div><div class="dvs-settings-stack">';
        if ( '' !== $args['lead'] ) {
            echo '<p class="dvs-panel-lead">' . esc_html( $args['lead'] ) . '</p>';
        }
    }

    public static function panel_close() {
        echo '</div></div>';
    }

    public static function card_open( $title, $desc = '', $extra_class = '' ) {
        echo '<section class="dvs-settings-card ' . esc_attr( $extra_class ) . '"><div class="dvs-settings-card-head"><h3>' . esc_html( $title ) . '</h3>';
        if ( '' !== $desc ) {
            echo '<p>' . esc_html( $desc ) . '</p>';
        }
        echo '</div><div class="dvs-settings-card-body">';
    }

    public static function card_close() {
        echo '</div></section>';
    }

    // ---------------------------------------------------------------- forms
    /**
     * Opens a settings form that the page script saves through the REST API.
     *
     * @param string $section Options section the fields belong to (e.g. "images").
     */
    public static function form_open( $section, $id = '' ) {
        echo '<form class="davix-h2i-settings-form" method="post" action="#" data-dvx-form data-section="' . esc_attr( $section ) . '"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>';
    }

    public static function form_close( $reset = true ) {
        echo '<div class="dvs-settings-bottom-save davix-h2i-settings-actions">';
        echo '<button type="submit" class="dvs-save-button davix-h2i-save-button">' . esc_html__( 'Save Settings', 'davix-h2i' ) . '</button>';
        if ( $reset ) {
            echo '<button type="button" class="dvs-btn davix-h2i-default-button" data-dvx-reset>' . esc_html__( 'Restore Defaults', 'davix-h2i' ) . '</button>';
        }
        echo '</div></form>';
    }

    public static function table_open() {
        echo '<table class="form-table davix-h2i-settings-table" role="presentation"><tbody>';
    }

    public static function table_close() {
        echo '</tbody></table>';
    }

    /**
     * Prints a list of setting rows from specs.
     *
     * @param array[] $specs  name (dotted path like "images.output.mode"), type, label, help, options, min, max, step, placeholder, default, requires
     */
    public static function rows( array $specs ) {
        self::table_open();
        foreach ( $specs as $spec ) {
            self::row( $spec );
        }
        self::table_close();
    }

    /** One setting row. The value is read from Options by the dotted name unless 'value' is given. */
    public static function row( array $spec ) {
        $spec  = array_merge(
            array(
                'type'        => 'text',
                'label'       => '',
                'help'        => '',
                'tip'         => '',
                'options'     => array(),
                'placeholder' => '',
                'class'       => '',
                'attrs'       => '',
                'readonly'    => false,
            ),
            $spec
        );
        $name  = $spec['name'];
        $value = array_key_exists( 'value', $spec ) ? $spec['value'] : Options::get( $name, isset( $spec['default'] ) ? $spec['default'] : '' );
        $id    = 'dvx-' . preg_replace( '/[^a-z0-9]+/i', '-', $name );
        $field = self::field_name( $name );
        $is_switch = 'switch' === $spec['type'];

        if ( in_array( $spec['type'], array( 'textarea', 'json', 'code' ), true ) ) {
            // Long text: the label sits on top with the expand button on the same line, the field stretches below it.
            echo '<tr class="davix-h2i-field-row--stacked ' . esc_attr( $spec['class'] ) . '"' . ( ! empty( $spec['requires'] ) ? ' data-dvx-requires="' . esc_attr( $spec['requires'] ) . '"' : '' ) . '><td colspan="2">';
            echo '<div class="davix-h2i-field-header"><label class="davix-h2i-label" for="' . esc_attr( $id ) . '">' . esc_html( $spec['label'] );
            self::tip( $spec['tip'] ? $spec['tip'] : '' );
            echo '</label><button type="button" class="dvs-icon-btn dvs-icon-btn--ghost" data-dvx-expand="#' . esc_attr( $id ) . '" data-expand-title="' . esc_attr( $spec['label'] ) . '" aria-label="' . esc_attr__( 'Expand to full-screen editor', 'davix-h2i' ) . '" title="' . esc_attr__( 'Expand to full-screen editor', 'davix-h2i' ) . '"><span class="dashicons dashicons-fullscreen-alt" aria-hidden="true"></span></button></div>';
            self::control( $spec['type'], $id, $field, $value, $spec );
            if ( '' !== $spec['help'] ) {
                echo '<p class="description davix-h2i-helptext">' . esc_html( $spec['help'] ) . '</p>';
            }
            echo '</td></tr>';
            return;
        }

        echo '<tr class="' . esc_attr( ( $is_switch ? 'davix-h2i-field-row--checkbox ' : '' ) . $spec['class'] ) . '"' . ( ! empty( $spec['requires'] ) ? ' data-dvx-requires="' . esc_attr( $spec['requires'] ) . '"' : '' ) . '>';
        echo '<th scope="row"><label class="davix-h2i-label" for="' . esc_attr( $id ) . '">' . esc_html( $spec['label'] );
        self::tip( $spec['tip'] ? $spec['tip'] : '' );
        echo '</label></th><td>';
        self::control( $spec['type'], $id, $field, $value, $spec );
        if ( '' !== $spec['help'] ) {
            echo '<p class="description davix-h2i-helptext">' . esc_html( $spec['help'] ) . '</p>';
        }
        echo '</td></tr>';
    }

    /** The bare control (no label). */
    public static function control( $type, $id, $field, $value, array $spec = array() ) {
        $spec  = array_merge( array( 'placeholder' => '', 'attrs' => '', 'options' => array(), 'readonly' => false ), $spec );
        $attrs = $spec['attrs'];
        $ro    = ! empty( $spec['readonly'] ) ? ' readonly' : '';
        switch ( $type ) {
            case 'switch':
                echo '<input type="hidden" name="' . esc_attr( $field ) . '" value="0" />';
                echo '<label class="dvs-switch" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" value="1" class="davix-h2i-checkbox" ' . checked( ! empty( $value ), true, false ) . ' ' . $attrs . ' /><span class="dvs-switch-ui" aria-hidden="true"></span></label>'; // phpcs:ignore WordPress.Security.EscapeOutput
                break;
            case 'select':
                echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" class="davix-h2i-select" ' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
                foreach ( (array) $spec['options'] as $option_value => $label ) {
                    echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( (string) $value, (string) $option_value, false ) . '>' . esc_html( $label ) . '</option>';
                }
                echo '</select>';
                break;
            case 'number':
                echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '" class="davix-h2i-input"'
                    . ( isset( $spec['min'] ) ? ' min="' . esc_attr( $spec['min'] ) . '"' : '' )
                    . ( isset( $spec['max'] ) ? ' max="' . esc_attr( $spec['max'] ) . '"' : '' )
                    . ( isset( $spec['step'] ) ? ' step="' . esc_attr( $spec['step'] ) . '"' : '' )
                    . ( '' !== $spec['placeholder'] ? ' placeholder="' . esc_attr( $spec['placeholder'] ) . '"' : '' )
                    . $ro . ' ' . $attrs . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput
                break;
            case 'textarea':
            case 'json':
                if ( 'json' === $type && ! is_string( $value ) ) {
                    $value = $value ? wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) : '';
                }
                echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" rows="' . (int) ( isset( $spec['rows'] ) ? $spec['rows'] : 4 ) . '" class="davix-h2i-textarea dvs-scrollbar"'
                    . ( '' !== $spec['placeholder'] ? ' placeholder="' . esc_attr( $spec['placeholder'] ) . '"' : '' )
                    . $ro . ' ' . $attrs . '>' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
                break;
            case 'code':
                echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" rows="' . (int) ( isset( $spec['rows'] ) ? $spec['rows'] : 8 ) . '" class="davix-h2i-textarea dvs-code-editor-textarea dvs-scrollbar" spellcheck="false" ' . $attrs
                    . ( '' !== $spec['placeholder'] ? ' placeholder="' . esc_attr( $spec['placeholder'] ) . '"' : '' ) . '>' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
                break;
            case 'password':
                echo '<input type="password" id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" value="" class="davix-h2i-input" autocomplete="new-password"' . ( '' !== $spec['placeholder'] ? ' placeholder="' . esc_attr( $spec['placeholder'] ) . '"' : '' ) . ' ' . $attrs . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput
                break;
            case 'media':
                // One file from the Media Library (a logo). The field holds the file's id.
                $att   = (int) $value;
                $title = $att ? get_the_title( $att ) : '';
                echo '<div class="dvx-media-pick" data-dvx-media>';
                echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" value="' . ( $att ? (int) $att : '' ) . '" />';
                echo '<button type="button" class="dvs-btn" data-dvx-media-pick>' . esc_html__( 'Choose from Media Library', 'davix-h2i' ) . '</button>';
                echo '<span class="dvx-muted" data-dvx-media-label>' . esc_html( $title ) . '</span>';
                echo '<button type="button" class="dvs-btn" data-dvx-media-clear' . ( $att ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'davix-h2i' ) . '</button>';
                echo '</div>';
                break;
            case 'sizes':
                // A table of image sizes (name, width, height, fit, format, quality). A hidden textarea keeps them as JSON.
                $rows = is_array( $value ) ? $value : ( is_string( $value ) && '' !== $value ? (array) json_decode( $value, true ) : array() );
                echo '<div class="dvx-sizes" data-dvx-sizes>';
                echo '<textarea hidden id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" ' . $attrs . '>' . esc_textarea( wp_json_encode( array_values( $rows ) ) ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
                echo '<div class="dvx-table-wrap"><table class="dvx-table"><thead><tr>';
                foreach ( array( __( 'Name', 'davix-h2i' ), __( 'Width', 'davix-h2i' ), __( 'Height', 'davix-h2i' ), __( 'Fit', 'davix-h2i' ), __( 'Format', 'davix-h2i' ), __( 'Quality', 'davix-h2i' ), '' ) as $h ) {
                    echo '<th>' . esc_html( $h ) . '</th>';
                }
                echo '</tr></thead><tbody data-dvx-sizes-body></tbody></table></div>';
                echo '<button type="button" class="dvs-btn" data-dvx-sizes-add>' . esc_html__( 'Add a size', 'davix-h2i' ) . '</button>';
                echo '</div>';
                break;
            case 'color':
                echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '" class="davix-h2i-input" placeholder="#000000" ' . $attrs . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput
                break;
            case 'checkboxes':
                $selected = is_array( $value ) ? $value : array();
                echo '<div class="davix-h2i-checkbox-list">';
                foreach ( (array) $spec['options'] as $option_value => $label ) {
                    $cid = $id . '-' . sanitize_key( $option_value );
                    echo '<label class="davix-h2i-checkbox-item" for="' . esc_attr( $cid ) . '"><input type="checkbox" id="' . esc_attr( $cid ) . '" name="' . esc_attr( $field ) . '[]" value="' . esc_attr( $option_value ) . '" ' . checked( in_array( (string) $option_value, array_map( 'strval', $selected ), true ), true, false ) . ' /> ' . esc_html( $label ) . '</label>';
                }
                echo '</div>';
                break;
            default:
                echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $field ) . '" value="' . esc_attr( is_scalar( $value ) ? $value : '' ) . '" class="davix-h2i-input"'
                    . ( '' !== $spec['placeholder'] ? ' placeholder="' . esc_attr( $spec['placeholder'] ) . '"' : '' )
                    . $ro . ' ' . $attrs . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
    }

    /** "images.output.mode" -> "images[output][mode]". */
    public static function field_name( $path ) {
        $parts = explode( '.', $path );
        $first = array_shift( $parts );
        return $first . ( $parts ? '[' . implode( '][', $parts ) . ']' : '' );
    }

    /** The "?" help bubble next to a label. */
    public static function tip( $text ) {
        if ( '' === (string) $text ) {
            return;
        }
        echo ' <button type="button" class="dvs-icon-btn davix-h2i-tooltip-icon" data-dvx-tip="' . esc_attr( $text ) . '" aria-label="' . esc_attr__( 'More info', 'davix-h2i' ) . '"><span aria-hidden="true">?</span></button>';
    }

    // ---------------------------------------------------------------- bits
    /** @param string $tone success|warning|danger|info */
    public static function badge( $text, $tone = 'info' ) {
        return '<span class="dvs-badge dvs-badge--' . esc_attr( $tone ) . '">' . esc_html( $text ) . '</span>';
    }

    public static function status_badge( $status ) {
        $map = array(
            'success'        => array( __( 'Done', 'davix-h2i' ), 'success' ),
            'failed'         => array( __( 'Failed', 'davix-h2i' ), 'danger' ),
            'pending_review' => array( __( 'Needs review', 'davix-h2i' ), 'warning' ),
            'restored'       => array( __( 'Restored', 'davix-h2i' ), 'info' ),
            'rejected'       => array( __( 'Rejected', 'davix-h2i' ), 'danger' ),
            'queued'         => array( __( 'Waiting', 'davix-h2i' ), 'info' ),
            'running'        => array( __( 'Running', 'davix-h2i' ), 'info' ),
            'waiting_remote' => array( __( 'In the cloud', 'davix-h2i' ), 'info' ),
            'done'           => array( __( 'Done', 'davix-h2i' ), 'success' ),
            'cancelled'      => array( __( 'Cancelled', 'davix-h2i' ), 'warning' ),
        );
        $item = isset( $map[ $status ] ) ? $map[ $status ] : array( $status, 'info' );
        return self::badge( $item[0], $item[1] );
    }

    public static function empty_state( $message, $icon = 'dashicons-info-outline', $action_html = '' ) {
        echo '<div class="dvs-empty-state"><span class="dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span><p>' . esc_html( $message ) . '</p>';
        if ( $action_html ) {
            echo '<div class="dvs-empty-state__action">' . $action_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
        echo '</div>';
    }

    public static function stat( $label, $value, $note = '' ) {
        echo '<div class="dvx-stat"><span class="dvx-stat__label">' . esc_html( $label ) . '</span><strong class="dvx-stat__value">' . esc_html( $value ) . '</strong>';
        if ( '' !== $note ) {
            echo '<span class="dvx-stat__note">' . esc_html( $note ) . '</span>';
        }
        echo '</div>';
    }

    /** A button. $kind: primary | secondary | ghost | danger */
    public static function button( $label, array $attrs = array(), $kind = 'secondary' ) {
        $class = 'primary' === $kind ? 'dvs-save-button' : ( 'danger' === $kind ? 'dvs-btn dvs-btn--danger' : 'dvs-btn' );
        $html  = '';
        foreach ( $attrs as $k => $v ) {
            $html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
        }
        return '<button type="button" class="' . esc_attr( $class ) . '"' . $html . '>' . esc_html( $label ) . '</button>';
    }

    /** Inline progress bar. */
    public static function bar( $percent, $label = '' ) {
        $percent = max( 0, min( 100, (float) $percent ) );
        return '<div class="dvx-bar" role="progressbar" aria-valuenow="' . esc_attr( round( $percent ) ) . '" aria-valuemin="0" aria-valuemax="100"><span style="width:' . esc_attr( $percent ) . '%"></span></div>' . ( $label ? '<span class="dvx-bar__label">' . esc_html( $label ) . '</span>' : '' );
    }
}
