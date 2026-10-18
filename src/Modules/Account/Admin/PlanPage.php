<?php

namespace DavixWP\Modules\Account\Admin;

use DavixWP\Admin\Ui;
use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Capabilities\LimitsService;
use DavixWP\Connection\ClientFactory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PlanPage {

    public function render() {
        $snap  = LimitsService::snapshot();
        $limits = $snap['limits'];

        $refresh = '<button type="button" class="dvs-btn" data-dvx-refresh-limits>' . esc_html__( 'Refresh', 'davix-h2i' ) . '</button>';
        Ui::panel_open( __( 'Plan & usage', 'davix-h2i' ), array( 'actions' => $refresh, 'lead' => __( 'What your plan includes and how much of it you have used this period.', 'davix-h2i' ) ) );

        if ( ! $limits ) {
            Ui::empty_state( $snap['error'] ? $snap['error'] : __( 'Plan details are not available yet. Test your connection first.', 'davix-h2i' ), 'dashicons-warning' );
            Ui::panel_close();
            return;
        }

        $plan      = CapabilityGate::plan();
        $allowance = CapabilityGate::allowance();
        $usage     = $this->usage();

        echo '<div class="dvx-stats">';
        Ui::stat( __( 'Plan', 'davix-h2i' ), $plan && ! empty( $plan['name'] ) ? $plan['name'] : '-' );
        if ( $allowance && isset( $allowance['total'] ) ) {
            $unit = isset( $allowance['unit'] ) ? $allowance['unit'] : 'files';
            Ui::stat( __( 'Used this period', 'davix-h2i' ), (string) $this->num( isset( $allowance['used'] ) ? $allowance['used'] : 0 ), null === $allowance['total'] ? $unit : sprintf( /* translators: 1: total, 2: unit */ __( 'of %1$s %2$s', 'davix-h2i' ), $this->num( $allowance['total'] ), $unit ) );
            if ( null !== $allowance['total'] ) {
                $left = max( 0, (float) $allowance['total'] - (float) ( isset( $allowance['used'] ) ? $allowance['used'] : 0 ) );
                Ui::stat( __( 'Left', 'davix-h2i' ), (string) $this->num( $left ), isset( $allowance['overage'] ) && 'allow' === $allowance['overage'] ? __( 'You can continue past it', 'davix-h2i' ) : '' );
            }
        }
        if ( ! empty( $usage['period_end'] ) ) {
            Ui::stat( __( 'Renews', 'davix-h2i' ), mysql2date( get_option( 'date_format' ), $usage['period_end'] ) );
        }
        echo '</div>';

        if ( $allowance && isset( $allowance['total'] ) && $allowance['total'] ) {
            $pct = 100 * (float) ( isset( $allowance['used'] ) ? $allowance['used'] : 0 ) / (float) $allowance['total'];
            echo '<div style="margin:16px 0">' . Ui::bar( $pct ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }

        if ( ! empty( $allowance['operations'] ) ) {
            Ui::card_open( __( 'By kind of work', 'davix-h2i' ) );
            echo '<div class="dvx-table-wrap"><table class="dvx-table"><thead><tr><th>' . esc_html__( 'Work', 'davix-h2i' ) . '</th><th class="dvx-num">' . esc_html__( 'Used', 'davix-h2i' ) . '</th><th class="dvx-num">' . esc_html__( 'Limit', 'davix-h2i' ) . '</th><th></th></tr></thead><tbody>';
            foreach ( $allowance['operations'] as $op ) {
                $enabled = ! isset( $op['enabled'] ) || $op['enabled'];
                echo '<tr><td>' . esc_html( isset( $op['label'] ) ? $op['label'] : $op['id'] ) . '</td>';
                echo '<td class="dvx-num">' . esc_html( $this->num( isset( $op['used'] ) ? $op['used'] : 0 ) ) . '</td>';
                echo '<td class="dvx-num">' . esc_html( isset( $op['limit'] ) && null !== $op['limit'] ? $this->num( $op['limit'] ) : ( isset( $op['mode'] ) && 'shared' === $op['mode'] ? __( 'Shared', 'davix-h2i' ) : __( 'None', 'davix-h2i' ) ) ) . '</td>';
                echo '<td>' . ( $enabled ? Ui::badge( __( 'Included', 'davix-h2i' ), 'success' ) : Ui::badge( __( 'Not included', 'davix-h2i' ), 'warning' ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
            }
            echo '</tbody></table></div>';
            Ui::card_close();
        }

        Ui::card_open( __( 'What this plugin can use', 'davix-h2i' ), __( 'Each action and what one call costs on your plan.', 'davix-h2i' ) );
        echo '<div class="dvx-table-wrap"><table class="dvx-table"><thead><tr><th>' . esc_html__( 'Action', 'davix-h2i' ) . '</th><th class="dvx-num">' . esc_html__( 'Cost per call', 'davix-h2i' ) . '</th><th class="dvx-num">' . esc_html__( 'Files per request', 'davix-h2i' ) . '</th><th></th></tr></thead><tbody>';
        $context = \DavixWP\Core\Services::get( 'context' );
        foreach ( $context->operations() as $def ) {
            $state = CapabilityGate::state( $def['id'] );
            echo '<tr><td>' . esc_html( $def['label'] ) . ' <span class="dvx-muted">' . esc_html( $def['id'] ) . '</span></td>';
            echo '<td class="dvx-num">' . esc_html( $this->num( CapabilityGate::cost( $def['id'] ) ) ) . '</td>';
            $pr = CapabilityGate::limit( $def['id'], 'files.per_request' );
            echo '<td class="dvx-num">' . esc_html( null === $pr ? '-' : $pr ) . '</td><td>';
            echo CapabilityGate::LOCKED === $state ? Ui::badge( __( 'Not included', 'davix-h2i' ), 'warning' ) : ( CapabilityGate::UNAVAILABLE === $state ? Ui::badge( __( 'Unavailable', 'davix-h2i' ), 'danger' ) : ( CapabilityGate::BETA === $state ? Ui::badge( __( 'Beta', 'davix-h2i' ), 'info' ) : Ui::badge( __( 'Included', 'davix-h2i' ), 'success' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
        Ui::card_close();
        Ui::panel_close();
    }

    private function num( $n ) {
        $n = (float) $n;
        return floor( $n ) === $n ? number_format_i18n( $n ) : number_format_i18n( $n, 2 );
    }

    /** The current period from GET /v1/usage (cached a minute). */
    private function usage() {
        $cached = get_transient( 'davix_h2i_usage' );
        if ( is_array( $cached ) ) {
            return $cached;
        }
        $out = array();
        try {
            $data = ClientFactory::make()->call( 'usage', array( 'months' => 1 ) )->data;
            $out  = isset( $data['current'] ) && is_array( $data['current'] ) ? $data['current'] : array();
        } catch ( \Throwable $e ) {
            $out = array();
        }
        set_transient( 'davix_h2i_usage', $out, 60 );
        return $out;
    }
}
