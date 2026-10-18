<?php
/**
 * The generic "pick files, pick an action, set options, run" panel. Every tab that runs operations uses it; it is built from
 * the operation definitions, so a new operation gets a working screen without extra markup.
 */

namespace DavixWP\Admin\Components;

use DavixWP\Admin\Ui;
use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Core\Options;
use DavixWP\Core\Services;
use DavixWP\Operations\Definition;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class OpPanel {

    /**
     * @param string[] $operation_ids Operations shown as cards.
     * @param array    $args title, lead, source, mode_control (bool), files (bool: show the file picker), bulk (bool), accepts_label
     */
    public static function render( array $operation_ids, array $args = array() ) {
        $args    = array_merge(
            array(
                'title'        => __( 'Run an action', 'davix-h2i' ),
                'lead'         => '',
                'source'       => 'tab',
                'mode_control' => true,
                'bulk'         => true,
                'selected'     => '',
            ),
            $args
        );
        $context = Services::get( 'context' );
        $defs    = array();
        foreach ( $operation_ids as $id ) {
            $def = $context->operation( $id );
            if ( $def && ! $def['hidden'] ) {
                $defs[ $id ] = $def;
            }
        }
        if ( ! $defs ) {
            return;
        }
        $selected = $args['selected'] && isset( $defs[ $args['selected'] ] ) ? $args['selected'] : '';
        if ( ! $selected && 1 === count( $defs ) ) {
            $selected = (string) key( $defs );
        }
        $needs_files = false;
        foreach ( $defs as $d ) {
            if ( in_array( $d['input'], array( 'file', 'optional' ), true ) ) {
                $needs_files = true;
            }
        }

        Ui::panel_open( $args['title'], array( 'lead' => $args['lead'] ) );
        echo '<div data-dvx-run data-source="' . esc_attr( $args['source'] ) . '" data-bulk="' . ( $args['bulk'] ? '1' : '0' ) . '">';
        echo '<div class="dvx-run">';

        Ui::card_open( __( '1. Action', 'davix-h2i' ) );
        echo '<select class="davix-h2i-select" data-op-select><option value="">' . esc_html__( 'Choose an action…', 'davix-h2i' ) . '</option>';
        foreach ( $defs as $id => $def ) {
            echo '<option value="' . esc_attr( $id ) . '"' . selected( $id, $selected, false ) . '>' . esc_html( $def['label'] ) . '</option>';
        }
        echo '</select><p class="description" data-op-desc></p>';
        // The buttons stay as hidden carriers of each action's state, cost and plan lock; the dropdown drives them.
        echo '<div class="dvx-op-grid" hidden>';
        foreach ( $defs as $id => $def ) {
            $desc = isset( $def['description'] ) ? $def['description'] : '';
            echo '<button type="button" class="dvx-op' . ( $id === $selected ? ' is-selected' : '' ) . '" data-dvx-op="' . esc_attr( $id ) . '" data-op-pick="' . esc_attr( $id ) . '" data-op-desc-text="' . esc_attr( $desc ) . '">';
            echo '<span class="dvx-op__title"><span>' . esc_html( $def['label'] ) . '</span></span>';
            echo '<span class="dvx-op__cost" data-op-cost="' . esc_attr( $id ) . '"></span>';
            echo '</button>';
        }
        echo '</div>';
        Ui::card_close();

        // Files and options appear only once an action is chosen.
        if ( $needs_files ) {
            echo '<div data-dvx-needs-op hidden>';
            Ui::card_open( __( '2. Files', 'davix-h2i' ), __( 'Choose files from your Media Library.', 'davix-h2i' ) );
            echo '<div class="dvx-toolbar"><button type="button" class="dvs-btn" data-dvx-pick>' . esc_html__( 'Choose from Media Library', 'davix-h2i' ) . '</button>';
            if ( $args['bulk'] ) {
                echo '<select class="davix-h2i-select" data-dvx-scope><option value="">' . esc_html__( 'Or pick by rule…', 'davix-h2i' ) . '</option>'
                    . '<option value="all">' . esc_html__( 'Every matching file', 'davix-h2i' ) . '</option>'
                    . '<option value="not_done">' . esc_html__( 'Files not processed yet', 'davix-h2i' ) . '</option>'
                    . '<option value="missing_alt">' . esc_html__( 'Images without alt text', 'davix-h2i' ) . '</option></select>';
            }
            echo '</div><div class="dvx-picked" data-dvx-picked></div>';
            echo '<p class="description" data-dvx-picked-note></p>';
            Ui::card_close();
            echo '</div>';
        }

        echo '<div data-dvx-needs-op hidden>';
        Ui::card_open( $needs_files ? __( '3. Options', 'davix-h2i' ) : __( '2. Options', 'davix-h2i' ) );
        echo '<p class="description" data-dvx-no-op>' . esc_html__( 'Choose an action to see its options.', 'davix-h2i' ) . '</p>';
        foreach ( $defs as $id => $def ) {
            self::render_form( $def );
        }
        if ( $args['mode_control'] ) {
            echo '<div data-dvx-mode class="dvx-hidden">';
            Ui::table_open();
            Ui::row(
                array(
                    'name'    => 'run.mode',
                    'type'    => 'select',
                    'label'   => __( 'Result', 'davix-h2i' ),
                    'options' => array(
                        ''        => __( 'Use my default', 'davix-h2i' ),
                        'replace' => __( 'Replace the original (a backup is kept)', 'davix-h2i' ),
                        'copy'    => __( 'Save as a new copy', 'davix-h2i' ),
                    ),
                    'value'   => '',
                    'attrs'   => 'data-dvx-mode-select',
                )
            );
            Ui::table_close();
            echo '</div>';
        }
        echo '<div class="dvx-callout dvx-hidden" data-dvx-estimate></div>';
        echo '<div class="dvx-toolbar" style="margin-top:16px"><button type="button" class="dvs-save-button" data-dvx-go disabled>' . esc_html__( 'Run', 'davix-h2i' ) . '</button>';
        echo '<label class="dvs-switch-label" style="display:inline-flex;align-items:center;gap:8px" data-dvx-queue-wrap><label class="dvs-switch"><input type="checkbox" data-dvx-queue ' . checked( (bool) Options::get( 'runtime.background', true ), true, false ) . ' /><span class="dvs-switch-ui" aria-hidden="true"></span></label> ' . esc_html__( 'Run in the background', 'davix-h2i' ) . '</label>';
        echo '</div>';
        Ui::card_close();
        echo '</div>';
        echo '<div class="dvx-results" data-dvx-results aria-live="polite"></div>';
        echo '</div></div>';
        Ui::panel_close();
    }

    public static function render_form( array $def ) {
        $id = $def['id'];
        echo '<div class="dvx-hidden" data-op-form="' . esc_attr( $id ) . '" data-input="' . esc_attr( $def['input'] ) . '" data-batch="' . esc_attr( isset( $def['batch'] ) ? $def['batch'] : 'each' ) . '" data-multiple="' . ( $def['multiple'] ? '1' : '0' ) . '" data-store="' . esc_attr( $def['store'] ) . '" data-accepts="' . esc_attr( implode( ',', $def['accepts'] ) ) . '" data-result="' . esc_attr( $def['result'] ) . '">';
        if ( ! empty( $def['note'] ) ) {
            echo '<p class="description">' . esc_html( $def['note'] ) . '</p>';
        }
        // Extra files: a logo, a mask, a second PDF. Every file the service accepts besides the main one.
        foreach ( Definition::extra_files( $def ) as $field => $label ) {
            echo '<div class="davix-h2i-field-group"><label class="davix-h2i-label">' . esc_html( $label ) . '</label>';
            echo '<div class="dvx-toolbar"><button type="button" class="dvs-btn" data-dvx-pick-extra="' . esc_attr( $field ) . '">' . esc_html__( 'Choose', 'davix-h2i' ) . '</button><span class="dvx-muted" data-extra-label="' . esc_attr( $field ) . '"></span></div></div>';
        }
        $main = self::prepare_rows( Definition::fields( $def ), false );
        if ( $main ) {
            Ui::rows( $main );
        }
        // Everything else the service supports for this action, folded away so beginners are not overwhelmed.
        $more = self::prepare_rows( Definition::advanced( $def ), true );
        if ( $more ) {
            echo '<details class="dvx-advanced"><summary>' . esc_html( sprintf( /* translators: %d: number of options */ _n( 'More options (%d)', 'More options (%d)', count( $more ), 'davix-h2i' ), count( $more ) ) ) . '</summary>';
            echo '<p class="description">' . esc_html__( 'Leave these as they are unless you need them. Only the options you change are sent.', 'davix-h2i' ) . '</p>';
            Ui::rows( $more );
            echo '</details>';
        }
        echo '</div>';
    }

    /**
     * Form-ready rows for a list of field specs. "advanced" rows are only sent when the person changes them from the default.
     *
     * @return array<int,array>
     */
    private static function prepare_rows( array $fields, $advanced ) {
        $rows = array();
        foreach ( $fields as $spec ) {
            if ( 'file' === $spec['type'] ) {
                continue;
            }
            $spec['label'] = isset( $spec['label_override'] ) ? $spec['label_override'] : $spec['label'];
            if ( 'select' === $spec['type'] && null === $spec['default'] && empty( $spec['required'] ) && ! empty( $spec['options'] ) && ! isset( $spec['options'][''] ) ) {
                $spec['options'] = array( '' => __( 'Default', 'davix-h2i' ) ) + $spec['options'];
            }
            $key           = $spec['name'];
            $spec['name']  = 'p.' . $key;
            $spec['value'] = null === $spec['default'] ? '' : $spec['default'];
            $attrs         = ( isset( $spec['attrs'] ) ? $spec['attrs'] : '' ) . ' data-p="' . esc_attr( isset( $spec['object'] ) ? $spec['object'] : $key ) . '" data-ptype="' . esc_attr( $spec['type'] ) . '"';
            if ( isset( $spec['object'] ) ) {
                $attrs .= ' data-pkey="' . esc_attr( $spec['key'] ) . '"';
            }
            if ( $advanced ) {
                $attrs .= ' data-padv="1"';
            }
            if ( $advanced || 'switch' === $spec['type'] ) {
                $attrs .= ' data-pdefault="' . esc_attr( null === $spec['default'] ? '' : ( is_scalar( $spec['default'] ) ? (string) ( true === $spec['default'] ? 'true' : ( false === $spec['default'] ? 'false' : $spec['default'] ) ) : wp_json_encode( $spec['default'] ) ) ) . '"';
            }
            $spec['attrs'] = $attrs;
            $spec['tip']   = ! empty( $spec['help'] ) ? $spec['help'] : '';
            $spec['help']  = '';
            if ( in_array( $spec['type'], array( 'json', 'sizes' ), true ) && '' !== $spec['value'] && ! is_string( $spec['value'] ) ) {
                $spec['value'] = wp_json_encode( $spec['value'] );
            }
            $rows[] = $spec;
        }
        return $rows;
    }
}
