<?php

namespace DavixWP\Modules\Activity;

use DavixWP\Admin\Ui;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\History\Repository;
use DavixWP\Jobs\Queue;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ActivityModule extends Module {

    public function register( Context $c ) {
        $c->add_tab( array(
            'id' => 'activity', 'label' => __( 'Activity', 'davix-h2i' ), 'icon' => 'dashicons-update', 'group' => 'manage', 'order' => 20,
            'desc' => __( 'Background work and suggestions waiting for you', 'davix-h2i' ),
        ) );
        $c->add_subtab( 'activity', array( 'id' => 'jobs', 'label' => __( 'Background jobs', 'davix-h2i' ), 'order' => 10, 'render' => array( $this, 'jobs' ) ) );
        $c->add_subtab( 'activity', array( 'id' => 'review', 'label' => __( 'Review', 'davix-h2i' ), 'order' => 20, 'render' => array( $this, 'review' ) ) );
        $c->add_assets( 'davix-h2i-activity', array(
            'css' => array( 'modules/activity/activity' ), 'js' => array( 'modules/activity/activity' ), 'screens' => array( 'tab:activity' ),
        ) );
    }

    public function jobs() {
        $counts = Queue::counts();
        $paused = Queue::paused();
        $actions = '<button type="button" class="dvs-btn" data-ac-run>' . esc_html__( 'Run now', 'davix-h2i' ) . '</button>'
            . '<button type="button" class="dvs-btn" data-ac-retry>' . esc_html__( 'Retry failed', 'davix-h2i' ) . '</button>'
            . '<button type="button" class="dvs-btn" data-ac-clear>' . esc_html__( 'Clear finished', 'davix-h2i' ) . '</button>';
        Ui::panel_open( __( 'Background jobs', 'davix-h2i' ), array( 'actions' => $actions, 'lead' => __( 'Uploads and bulk runs wait here and run in the background, so your site never slows down.', 'davix-h2i' ) ) );
        echo '<div data-ac-root>';
        echo '<div class="dvx-callout is-warning' . ( $paused ? '' : ' dvx-hidden' ) . '" data-ac-paused><p>' . esc_html( $paused && 'quota' === $paused['reason'] ? __( 'The queue is paused because your plan\'s allowance is used up.', 'davix-h2i' ) : __( 'The queue is paused. Check your API key.', 'davix-h2i' ) ) . '</p><div class="dvx-callout__actions"><button type="button" class="dvs-btn" data-ac-resume>' . esc_html__( 'Resume', 'davix-h2i' ) . '</button></div></div>';
        echo '<div class="dvx-stats" data-ac-stats>';
        foreach ( array( 'queued' => __( 'Waiting', 'davix-h2i' ), 'running' => __( 'Running', 'davix-h2i' ), 'done' => __( 'Done', 'davix-h2i' ), 'failed' => __( 'Failed', 'davix-h2i' ) ) as $key => $label ) {
            Ui::stat( $label, number_format_i18n( $counts[ $key ] + ( 'running' === $key ? $counts['waiting_remote'] : 0 ) ) );
        }
        echo '</div>';
        echo '<div class="dvx-toolbar" style="margin-top:16px"><select class="davix-h2i-select" data-ac-status><option value="">' . esc_html__( 'All', 'davix-h2i' ) . '</option><option value="queued">' . esc_html__( 'Waiting', 'davix-h2i' ) . '</option><option value="running">' . esc_html__( 'Running', 'davix-h2i' ) . '</option><option value="waiting_remote">' . esc_html__( 'In the cloud', 'davix-h2i' ) . '</option><option value="done">' . esc_html__( 'Done', 'davix-h2i' ) . '</option><option value="failed">' . esc_html__( 'Failed', 'davix-h2i' ) . '</option><option value="cancelled">' . esc_html__( 'Cancelled', 'davix-h2i' ) . '</option></select></div>';
        echo '<div class="dvx-table-wrap"><table class="dvx-table"><thead><tr><th>' . esc_html__( 'File', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Action', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Status', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Tries', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Updated', 'davix-h2i' ) . '</th><th></th></tr></thead><tbody data-ac-body><tr><td colspan="6"><div class="dvs-empty-state"><p>' . esc_html__( 'Loading…', 'davix-h2i' ) . '</p></div></td></tr></tbody></table></div>';
        echo '</div>';
        Ui::panel_close();
    }

    public function review() {
        $res = Repository::query( array( 'status' => 'pending_review' ), 1, 50, 'ASC' );
        Ui::panel_open( __( 'Review', 'davix-h2i' ), array( 'lead' => __( 'Suggestions from the AI that wait for your approval. Choose "Hold it for my review" in AI settings to use this.', 'davix-h2i' ) ) );
        if ( ! $res['items'] ) {
            Ui::empty_state( __( 'Nothing to review.', 'davix-h2i' ), 'dashicons-yes-alt' );
            Ui::panel_close();
            return;
        }
        echo '<div class="dvx-results" data-ac-review>';
        $context = \DavixWP\Core\Services::get( 'context' );
        foreach ( $res['items'] as $row ) {
            $def  = $context->operation( $row['operation'] );
            $text = isset( $row['result']['pending']['text'] ) ? (array) $row['result']['pending']['text'] : array();
            $thumb = $row['object_id'] ? wp_get_attachment_image_url( $row['object_id'], 'thumbnail' ) : '';
            echo '<div class="dvx-result" data-hid="' . esc_attr( $row['id'] ) . '">';
            if ( $thumb ) {
                echo '<img src="' . esc_url( $thumb ) . '" alt="" />';
            }
            echo '<div class="dvx-result__body"><strong>' . esc_html( $def ? $def['label'] : $row['operation'] ) . '</strong> <span class="dvx-muted">' . esc_html( $row['object_title'] ) . '</span>';
            foreach ( $text as $key => $value ) {
                if ( is_array( $value ) || '' === $value ) {
                    continue;
                }
                echo '<div class="davix-h2i-field-group"><label class="davix-h2i-label">' . esc_html( str_replace( '_', ' ', $key ) ) . '</label><textarea class="davix-h2i-textarea" rows="2" data-ac-field="' . esc_attr( $key ) . '">' . esc_textarea( (string) $value ) . '</textarea></div>';
            }
            echo '<div class="dvx-toolbar"><button type="button" class="dvs-save-button" data-ac-approve>' . esc_html__( 'Apply', 'davix-h2i' ) . '</button><button type="button" class="dvs-btn" data-ac-reject>' . esc_html__( 'Reject', 'davix-h2i' ) . '</button></div></div></div>';
        }
        echo '</div>';
        Ui::panel_close();
    }
}
