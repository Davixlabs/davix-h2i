<?php

namespace DavixWP\Modules\Dashboard;

use DavixWP\Admin\Shell;
use DavixWP\Admin\Ui;
use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\Core\Options;
use DavixWP\History\Repository;
use DavixWP\Jobs\Queue;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class DashboardModule extends Module {

    public function register( Context $c ) {
        $c->add_tab( array(
            'id' => 'overview', 'label' => __( 'Overview', 'davix-h2i' ), 'icon' => 'dashicons-dashboard', 'group' => 'work', 'order' => 5,
            'desc' => __( 'Your plan, your library and what is happening', 'davix-h2i' ), 'render' => array( $this, 'render' ),
        ) );
        $c->add_assets( 'davix-h2i-dashboard', array( 'css' => array(), 'js' => array(), 'screens' => array( 'tab:overview' ) ) );
    }

    /** Counts of the library, cached for ten minutes. */
    private function health() {
        $cached = get_transient( 'davix_h2i_health' );
        if ( is_array( $cached ) ) {
            return $cached;
        }
        $count = function ( array $args ) {
            $q = new \WP_Query( array_merge( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => false ), $args ) );
            return (int) $q->found_posts;
        };
        $health = array(
            'images'     => $count( array( 'post_mime_type' => 'image' ) ),
            'optimized'  => $count( array( 'post_mime_type' => 'image', 'meta_query' => array( array( 'key' => '_davix_h2i_state', 'compare' => 'EXISTS' ) ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
            'missing_alt' => $count( array( 'post_mime_type' => 'image', 'meta_query' => array( 'relation' => 'OR', array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ), array( 'key' => '_wp_attachment_image_alt', 'value' => '' ) ) ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
            'pdfs'       => $count( array( 'post_mime_type' => 'application/pdf' ) ),
        );
        set_transient( 'davix_h2i_health', $health, 600 );
        return $health;
    }

    public function render() {
        $allowance = CapabilityGate::allowance();
        $plan      = CapabilityGate::plan();
        $health    = $this->health();
        $counts    = Queue::counts();
        $paused    = Queue::paused();
        $conn      = Options::section( 'connection' );

        Ui::panel_open( __( 'Overview', 'davix-h2i' ), array( 'lead' => __( 'Your plan, your library and what is happening in the background.', 'davix-h2i' ) ) );

        if ( $paused ) {
            echo '<div class="dvx-callout is-warning"><p>' . esc_html( 'quota' === $paused['reason'] ? __( 'Background work is paused because your plan\'s allowance is used up.', 'davix-h2i' ) : __( 'Background work is paused. Check your API key.', 'davix-h2i' ) ) . '</p><div class="dvx-callout__actions"><a class="dvs-btn" href="' . esc_url( Shell::url( 'activity' ) ) . '">' . esc_html__( 'Open Activity', 'davix-h2i' ) . '</a></div></div>';
        } elseif ( $counts['failed'] > 0 ) {
            echo '<div class="dvx-callout is-danger"><p>' . esc_html( sprintf( /* translators: %d: count */ _n( '%d background job failed.', '%d background jobs failed.', $counts['failed'], 'davix-h2i' ), $counts['failed'] ) ) . '</p><div class="dvx-callout__actions"><a class="dvs-btn" href="' . esc_url( Shell::url( 'activity' ) ) . '">' . esc_html__( 'See what happened', 'davix-h2i' ) . '</a></div></div>';
        }

        echo '<div class="dvx-grid-2" style="margin-top:16px"><div>';
        Ui::card_open( __( 'Your plan', 'davix-h2i' ) );
        echo '<div class="dvx-stats">';
        Ui::stat( __( 'Plan', 'davix-h2i' ), $plan && ! empty( $plan['name'] ) ? $plan['name'] : ( $conn['plan_name'] ? $conn['plan_name'] : '-' ) );
        if ( $allowance && isset( $allowance['total'] ) ) {
            $used = (float) ( isset( $allowance['used'] ) ? $allowance['used'] : 0 );
            Ui::stat( __( 'Used this period', 'davix-h2i' ), number_format_i18n( $used, floor( $used ) === $used ? 0 : 2 ), null === $allowance['total'] ? '' : sprintf( /* translators: %s: total */ __( 'of %s', 'davix-h2i' ), number_format_i18n( $allowance['total'] ) ) );
        }
        echo '</div>';
        if ( $allowance && ! empty( $allowance['total'] ) ) {
            $pct = 100 * (float) ( isset( $allowance['used'] ) ? $allowance['used'] : 0 ) / (float) $allowance['total'];
            echo '<div style="margin-top:12px">' . Ui::bar( $pct ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
        echo '<p><a href="' . esc_url( Shell::url( 'account', 'plan' ) ) . '">' . esc_html__( 'See everything your plan includes', 'davix-h2i' ) . '</a></p>';
        Ui::card_close();

        Ui::card_open( __( 'Quick actions', 'davix-h2i' ) );
        echo '<div class="dvx-toolbar">';
        echo '<a class="dvs-btn" href="' . esc_url( admin_url( 'upload.php?mode=list&post_mime_type=image' ) ) . '">' . esc_html__( 'Optimize images', 'davix-h2i' ) . '</a>';
        echo '<a class="dvs-btn" href="' . esc_url( Shell::url( 'ai', 'auto' ) ) . '">' . esc_html__( 'Write alt text automatically', 'davix-h2i' ) . '</a>';
        echo '<a class="dvs-btn" href="' . esc_url( Shell::url( 'create', 'page' ) ) . '">' . esc_html__( 'Create a social card', 'davix-h2i' ) . '</a>';
        echo '<a class="dvs-btn" href="' . esc_url( admin_url( 'upload.php?mode=list&post_mime_type=application/pdf' ) ) . '">' . esc_html__( 'Compress PDFs', 'davix-h2i' ) . '</a>';
        echo '</div>';
        Ui::card_close();
        echo '</div><div>';

        Ui::card_open( __( 'Your library', 'davix-h2i' ) );
        echo '<div class="dvx-stats">';
        Ui::stat( __( 'Images', 'davix-h2i' ), number_format_i18n( $health['images'] ) );
        Ui::stat( __( 'Processed by Davix H2I', 'davix-h2i' ), number_format_i18n( $health['optimized'] ) );
        Ui::stat( __( 'Without alt text', 'davix-h2i' ), number_format_i18n( $health['missing_alt'] ) );
        Ui::stat( __( 'PDFs', 'davix-h2i' ), number_format_i18n( $health['pdfs'] ) );
        echo '</div>';
        Ui::card_close();

        Ui::card_open( __( 'Recent activity', 'davix-h2i' ) );
        $recent = Repository::query( Api_scope(), 1, 5 );
        if ( ! $recent['items'] ) {
            Ui::empty_state( __( 'Nothing yet.', 'davix-h2i' ), 'dashicons-backup' );
        } else {
            $context = \DavixWP\Core\Services::get( 'context' );
            echo '<div class="dvx-table-wrap"><table class="dvx-table"><tbody>';
            foreach ( $recent['items'] as $row ) {
                $def = $context->operation( $row['operation'] );
                echo '<tr><td>' . esc_html( $def ? $def['label'] : $row['operation'] ) . '<div class="dvx-muted">' . esc_html( $row['object_title'] ) . '</div></td><td>' . Ui::status_badge( $row['status'] ) . '</td><td class="dvx-muted">' . esc_html( human_time_diff( strtotime( $row['created_at'] . ' UTC' ), time() ) ) . ' ' . esc_html__( 'ago', 'davix-h2i' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
            }
            echo '</tbody></table></div><p><a href="' . esc_url( Shell::url( 'history' ) ) . '">' . esc_html__( 'Open History', 'davix-h2i' ) . '</a></p>';
        }
        Ui::card_close();
        echo '</div></div>';
        Ui::panel_close();
    }
}

/** People without the manage capability only see their own actions. */
function Api_scope() {
    return current_user_can( 'manage_options' ) ? array() : array( 'user_id' => get_current_user_id() );
}
