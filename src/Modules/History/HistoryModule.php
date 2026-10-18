<?php

namespace DavixWP\Modules\History;

use DavixWP\Admin\Ui;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\Core\Services;
use DavixWP\History\Repository;
use DavixWP\Modules\History\Rest\Routes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HistoryModule extends Module {

    public function register( Context $c ) {
        $c->add_tab( array(
            'id'     => 'history',
            'label'  => __( 'History', 'davix-h2i' ),
            'icon'   => 'dashicons-backup',
            'group'  => 'manage',
            'order'  => 10,
            'desc'   => __( 'Everything you did with Davix H2I from this site', 'davix-h2i' ),
            'render' => array( $this, 'render' ),
        ) );
        $c->add_rest( array( new Routes(), 'register' ) );
        $c->add_assets( 'davix-h2i-history', array(
            'css'     => array( 'modules/history/history' ),
            'js'      => array( 'modules/history/history' ),
            'screens' => array( 'tab:history' ),
            'data'    => function () {
                $ops = array();
                foreach ( Services::get( 'context' )->operations() as $id => $def ) {
                    $ops[ $id ] = $def['label'];
                }
                return array( 'operations' => $ops, 'canManage' => current_user_can( 'manage_options' ) );
            },
        ) );
    }

    public function render() {
        $stats = Repository::stats();
        $actions = '<button type="button" class="dvs-btn" data-hx-export>' . esc_html__( 'Export CSV', 'davix-h2i' ) . '</button>'
            . ( current_user_can( 'manage_options' ) ? '<button type="button" class="dvs-btn dvs-btn--danger" data-hx-clear>' . esc_html__( 'Clear history', 'davix-h2i' ) . '</button>' : '' );
        Ui::panel_open( __( 'History', 'davix-h2i' ), array( 'actions' => $actions, 'lead' => __( 'What you did through this plugin: library actions, uploads, editor tools and bulk runs. Actions from Studio or the API are not shown here.', 'davix-h2i' ) ) );
        ?>
        <div data-hx-root>
            <div class="dvx-stats" data-hx-stats>
                <?php
                Ui::stat( __( 'Actions', 'davix-h2i' ), number_format_i18n( $stats['total'] ) );
                Ui::stat( __( 'Done', 'davix-h2i' ), number_format_i18n( $stats['success'] ) );
                Ui::stat( __( 'Failed', 'davix-h2i' ), number_format_i18n( $stats['failed'] ) );
                Ui::stat( __( 'Space saved', 'davix-h2i' ), size_format( $stats['saved_bytes'] ) );
                ?>
            </div>
            <div class="dvx-toolbar" style="margin-top:16px">
                <input type="search" class="davix-h2i-input" data-hx-filter="search" placeholder="<?php esc_attr_e( 'Search file, action or request ID', 'davix-h2i' ); ?>" />
                <select class="davix-h2i-select" data-hx-filter="group">
                    <option value=""><?php esc_html_e( 'All kinds', 'davix-h2i' ); ?></option>
                    <option value="images"><?php esc_html_e( 'Images', 'davix-h2i' ); ?></option>
                    <option value="ai"><?php esc_html_e( 'AI', 'davix-h2i' ); ?></option>
                    <option value="pdfs"><?php esc_html_e( 'PDF', 'davix-h2i' ); ?></option>
                    <option value="render"><?php esc_html_e( 'Render', 'davix-h2i' ); ?></option>
                    <option value="documents"><?php esc_html_e( 'Documents', 'davix-h2i' ); ?></option>
                </select>
                <select class="davix-h2i-select" data-hx-filter="status">
                    <option value=""><?php esc_html_e( 'Any result', 'davix-h2i' ); ?></option>
                    <option value="success"><?php esc_html_e( 'Done', 'davix-h2i' ); ?></option>
                    <option value="failed"><?php esc_html_e( 'Failed', 'davix-h2i' ); ?></option>
                    <option value="pending_review"><?php esc_html_e( 'Needs review', 'davix-h2i' ); ?></option>
                    <option value="restored"><?php esc_html_e( 'Restored', 'davix-h2i' ); ?></option>
                </select>
                <select class="davix-h2i-select" data-hx-filter="source">
                    <option value=""><?php esc_html_e( 'Any source', 'davix-h2i' ); ?></option>
                    <option value="tab"><?php esc_html_e( 'Plugin screens', 'davix-h2i' ); ?></option>
                    <option value="drawer"><?php esc_html_e( 'Media Library', 'davix-h2i' ); ?></option>
                    <option value="editor"><?php esc_html_e( 'Editor', 'davix-h2i' ); ?></option>
                    <option value="upload"><?php esc_html_e( 'Uploads', 'davix-h2i' ); ?></option>
                    <option value="publish"><?php esc_html_e( 'Posts published', 'davix-h2i' ); ?></option>
                    <option value="bulk"><?php esc_html_e( 'Bulk runs', 'davix-h2i' ); ?></option>
                    <option value="generator"><?php esc_html_e( 'Generators', 'davix-h2i' ); ?></option>
                </select>
                <input type="date" class="davix-h2i-input" data-hx-filter="from" aria-label="<?php esc_attr_e( 'From', 'davix-h2i' ); ?>" />
                <input type="date" class="davix-h2i-input" data-hx-filter="to" aria-label="<?php esc_attr_e( 'To', 'davix-h2i' ); ?>" />
            </div>
            <div class="dvx-table-wrap">
                <table class="dvx-table">
                    <thead><tr>
                        <th><?php esc_html_e( 'File', 'davix-h2i' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'davix-h2i' ); ?></th>
                        <th><?php esc_html_e( 'Result', 'davix-h2i' ); ?></th>
                        <th class="dvx-num"><?php esc_html_e( 'Size change', 'davix-h2i' ); ?></th>
                        <th><?php esc_html_e( 'By', 'davix-h2i' ); ?></th>
                        <th><?php esc_html_e( 'When', 'davix-h2i' ); ?></th>
                    </tr></thead>
                    <tbody data-hx-body><tr><td colspan="6"><div class="dvs-empty-state"><p><?php esc_html_e( 'Loading…', 'davix-h2i' ); ?></p></div></td></tr></tbody>
                </table>
            </div>
            <div class="dvx-pager"><span data-hx-count></span><div class="dvx-pager__buttons"><button type="button" class="dvs-btn" data-hx-prev><?php esc_html_e( 'Previous', 'davix-h2i' ); ?></button><button type="button" class="dvs-btn" data-hx-next><?php esc_html_e( 'Next', 'davix-h2i' ); ?></button></div></div>
        </div>
        <div class="dvs-drawer-backdrop" data-hx-backdrop hidden></div>
        <aside class="dvs-drawer dvx-side-drawer davix-admin davix-h2i-theme" data-hx-side hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Action details', 'davix-h2i' ); ?>">
            <div class="dvs-drawer-header">
                <div class="dvs-drawer-brand">
                    <h2 class="dvs-drawer-title" data-hx-side-title></h2>
                </div>
                <button type="button" class="dvs-drawer-close" data-hx-close aria-label="<?php esc_attr_e( 'Close', 'davix-h2i' ); ?>">&times;</button>
            </div>
            <div class="dvs-drawer-body dvx-side-drawer__body dvx-detail" data-hx-side-body></div>
            <div class="dvs-drawer-footer dvx-side-drawer__foot" data-hx-side-foot></div>
        </aside>
        <?php
        Ui::panel_close();
    }
}
