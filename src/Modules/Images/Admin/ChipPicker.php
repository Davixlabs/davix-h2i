<?php

namespace DavixWP\Modules\Images\Admin;

use DavixWP\Admin\Ui;
use DavixWP\Modules\Images\Chips;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The chip picker markup (the existing design): a select that adds chips, the chip row, and one card of fields per chip.
 */
final class ChipPicker {

    /**
     * @param string   $group  unique group id (used by the script)
     * @param string   $path   options path of the group, e.g. "images.defaults" (chips go to <path>.chips, fields to <path>.fields)
     * @param string[] $chips  selected chips
     * @param array    $values field values by chip
     */
    public static function render( $group, $path, array $chips, array $values ) {
        $defs  = Chips::definitions();
        $name  = Ui::field_name( $path . '.chips' );
        $label = __( 'Add optimization chip', 'davix-h2i' );
        $sid   = 'dvx-chip-select-' . $group;

        echo '<div class="davix-h2i-chip-picker" data-chip-group="' . esc_attr( $group ) . '">';
        echo '<label class="davix-h2i-label" for="' . esc_attr( $sid ) . '">' . esc_html( $label ) . '</label>';
        echo '<select id="' . esc_attr( $sid ) . '" class="davix-h2i-chip-select davix-h2i-select" data-chip-select="' . esc_attr( $group ) . '"><option value="">' . esc_html__( 'Select a chip', 'davix-h2i' ) . '</option>';
        foreach ( $defs as $key => $def ) {
            echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $def['label'] ) . '</option>';
        }
        echo '</select>';
        echo '<div class="davix-h2i-chip-row" data-chip-row="' . esc_attr( $group ) . '" data-input-name="' . esc_attr( $name ) . '" aria-live="polite">';
        echo '<input type="hidden" name="' . esc_attr( $name ) . '[]" value="" />';
        foreach ( $chips as $key ) {
            if ( ! isset( $defs[ $key ] ) ) {
                continue;
            }
            echo '<span class="davix-h2i-chip" data-chip="' . esc_attr( $key ) . '"><span class="davix-h2i-chip-label">' . esc_html( $defs[ $key ]['label'] ) . '</span>'
                . '<button type="button" aria-label="' . esc_attr( sprintf( /* translators: %s: chip */ __( 'Remove %s', 'davix-h2i' ), $defs[ $key ]['label'] ) ) . '">×</button>'
                . '<input type="hidden" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $key ) . '" /></span>';
        }
        echo '</div></div>';

        echo '<div class="davix-h2i-chip-fields-group" data-chip-group="' . esc_attr( $group ) . '">';
        foreach ( $defs as $key => $def ) {
            echo '<div class="davix-h2i-chip-fields davix-h2i-card" data-chip="' . esc_attr( $key ) . '" style="display:none">';
            echo '<h4 class="davix-h2i-card-title">' . esc_html( $def['label'] ) . '</h4>';
            foreach ( $def['fields'] as $field ) {
                $fid   = 'dvx-' . $group . '-' . $key . '-' . $field['name'];
                $fname = Ui::field_name( $path . '.fields.' . $key . '.' . $field['name'] );
                $value = isset( $values[ $key ][ $field['name'] ] ) ? $values[ $key ][ $field['name'] ] : '';
                echo '<div class="davix-h2i-chip-field-row ' . ( 'switch' === $field['type'] ? 'is-checkbox' : '' ) . '"><label class="davix-h2i-chip-field-label davix-h2i-label" for="' . esc_attr( $fid ) . '">' . esc_html( $field['label'] ) . '</label><div class="davix-h2i-chip-field-control">';
                Ui::control( $field['type'], $fid, $fname, $value, $field );
                echo '</div></div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}
