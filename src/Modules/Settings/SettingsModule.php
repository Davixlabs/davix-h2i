<?php

namespace DavixWP\Modules\Settings;

use DavixWP\Admin\Ui;
use DavixWP\Core\Context;
use DavixWP\Core\Options;
use DavixWP\Core\Module;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SettingsModule extends Module {

    public function register( Context $c ) {
        $c->add_tab( array(
            'id' => 'settings', 'label' => __( 'Settings', 'davix-h2i' ), 'icon' => 'dashicons-admin-generic', 'group' => 'account', 'order' => 20,
            'capability' => 'manage_options', 'desc' => __( 'Speed, reliability, privacy and data', 'davix-h2i' ),
        ) );
        $c->add_subtab( 'settings', array( 'id' => 'performance', 'label' => __( 'Performance', 'davix-h2i' ), 'order' => 10, 'render' => array( $this, 'performance' ) ) );
        $c->add_subtab( 'settings', array( 'id' => 'privacy', 'label' => __( 'Privacy & data', 'davix-h2i' ), 'order' => 20, 'render' => array( $this, 'privacy' ) ) );
        $c->add_subtab( 'settings', array( 'id' => 'transfer', 'label' => __( 'Import / Export', 'davix-h2i' ), 'order' => 30, 'render' => array( $this, 'transfer' ) ) );
        $c->add_assets( 'davix-h2i-settings', array(
            'css' => array( 'modules/settings/settings' ), 'js' => array( 'modules/settings/settings' ), 'screens' => array( 'tab:settings' ),
        ) );
    }

    public function performance() {
        Ui::form_open( 'runtime' );
        Ui::panel_open( __( 'Performance', 'davix-h2i' ), array( 'save' => true, 'lead' => __( 'How the plugin talks to Davix H2I and how it works through your queue.', 'davix-h2i' ) ) );
        Ui::card_open( __( 'Requests', 'davix-h2i' ) );
        Ui::rows( array(
            array( 'name' => 'runtime.timeout', 'type' => 'number', 'min' => 5, 'max' => 300, 'label' => __( 'Timeout (seconds)', 'davix-h2i' ), 'help' => __( 'How long to wait for one answer. Large files and AI can take longer.', 'davix-h2i' ) ),
            array( 'name' => 'runtime.retries', 'type' => 'number', 'min' => 0, 'max' => 5, 'label' => __( 'Retries', 'davix-h2i' ), 'help' => __( 'Only temporary problems are retried, and never twice for the same file.', 'davix-h2i' ) ),
            array( 'name' => 'runtime.auto_idempotency', 'type' => 'switch', 'label' => __( 'Protect against double charges', 'davix-h2i' ), 'help' => __( 'Sends a unique request key so a repeated request is never counted twice.', 'davix-h2i' ) ),
        ) );
        Ui::card_close();
        Ui::card_open( __( 'Background work', 'davix-h2i' ) );
        Ui::rows( array(
            array( 'name' => 'runtime.background', 'type' => 'switch', 'label' => __( 'Run in the background by default', 'davix-h2i' ), 'help' => __( 'The default for the "Run in the background" switch on action screens.', 'davix-h2i' ) ),
            array( 'name' => 'runtime.batch', 'type' => 'number', 'min' => 1, 'max' => 50, 'label' => __( 'Jobs per minute', 'davix-h2i' ), 'help' => __( 'How many queued files are handled each time the queue runs.', 'davix-h2i' ) ),
            array( 'name' => 'runtime.remote_jobs', 'type' => 'switch', 'label' => __( 'Let Davix H2I process large jobs in the cloud', 'davix-h2i' ), 'help' => __( 'Uses your plan\'s background jobs when it includes them. Otherwise jobs run normally.', 'davix-h2i' ) ),
        ) );
        Ui::card_close();
        Ui::panel_close();
        Ui::form_close();
    }

    public function privacy() {
        Ui::form_open( 'runtime' );
        Ui::panel_open( __( 'Privacy & data', 'davix-h2i' ), array( 'save' => true, 'lead' => __( 'What the plugin keeps on this site, and who can use it.', 'davix-h2i' ) ) );
        Ui::card_open( __( 'What is sent', 'davix-h2i' ), __( 'Files are sent to your Davix H2I service only when an action runs on them. AI features are off on upload until you turn them on in AI settings.', 'davix-h2i' ) );
        echo '<p class="description">' . esc_html__( 'Your API key is stored encrypted and is never shown or logged.', 'davix-h2i' ) . '</p>';
        Ui::card_close();
        Ui::card_open( __( 'History', 'davix-h2i' ) );
        Ui::rows( array(
            array( 'name' => 'runtime.history_enabled', 'type' => 'switch', 'label' => __( 'Keep a history of actions', 'davix-h2i' ) ),
            array( 'name' => 'runtime.history_retention', 'type' => 'number', 'min' => 0, 'max' => 3650, 'label' => __( 'Keep history for (days)', 'davix-h2i' ), 'help' => __( '0 keeps it until you clear it.', 'davix-h2i' ) ),
            array( 'name' => 'runtime.jobs_retention', 'type' => 'number', 'min' => 1, 'max' => 365, 'label' => __( 'Keep finished jobs for (days)', 'davix-h2i' ) ),
        ) );
        Ui::card_close();
        Ui::card_open( __( 'Access', 'davix-h2i' ) );
        Ui::rows( array(
            array( 'name' => 'runtime.access.use', 'type' => 'select', 'label' => __( 'Who can use the plugin', 'davix-h2i' ), 'options' => array(
                'upload_files'   => __( 'Anyone who can upload files', 'davix-h2i' ),
                'edit_posts'     => __( 'Anyone who can edit posts', 'davix-h2i' ),
                'publish_posts'  => __( 'Anyone who can publish posts', 'davix-h2i' ),
                'manage_options' => __( 'Administrators only', 'davix-h2i' ),
            ), 'help' => __( 'Connection and settings always need an administrator.', 'davix-h2i' ) ),
        ) );
        Ui::card_close();
        Ui::card_open( __( 'Plan badges and cleanup', 'davix-h2i' ) );
        Ui::rows( array(
            array( 'name' => 'runtime.upgrade_badges', 'type' => 'switch', 'label' => __( 'Show "Upgrade" on features outside my plan', 'davix-h2i' ) ),
            array( 'name' => 'runtime.uninstall_cleanup', 'type' => 'switch', 'label' => __( 'Remove all plugin data when the plugin is deleted', 'davix-h2i' ), 'help' => __( 'Settings, history, jobs and backups. Your media files are never deleted.', 'davix-h2i' ) ),
        ) );
        Ui::card_close();
        Ui::panel_close();
        Ui::form_close();
    }

    /** Friendly names for the settings sections that can be moved to another site. */
    public static function section_labels() {
        $names = array(
            'images'  => array( __( 'Images', 'davix-h2i' ), __( 'Chips, sizes, output, auto-optimize and watermark.', 'davix-h2i' ) ),
            'ai'      => array( __( 'AI', 'davix-h2i' ), __( 'Writing rules, review policy and upload behaviour.', 'davix-h2i' ) ),
            'pdf'     => array( __( 'PDF & Documents', 'davix-h2i' ), __( 'Default PDF options and upload rules.', 'davix-h2i' ) ),
            'render'  => array( __( 'H2I Render', 'davix-h2i' ), __( 'Your template, social card and defaults.', 'davix-h2i' ) ),
            'auto'    => array( __( 'Automatic rules', 'davix-h2i' ), __( 'What runs by itself when a new file is uploaded.', 'davix-h2i' ) ),
            'runtime' => array( __( 'Performance & privacy', 'davix-h2i' ), __( 'Timeouts, queue, history and who can use the plugin.', 'davix-h2i' ) ),
        );
        $out = array();
        foreach ( Options::sections() as $section ) {
            if ( 'connection' === $section ) {
                continue;
            }
            $out[ $section ] = isset( $names[ $section ] ) ? $names[ $section ] : array( ucfirst( $section ), '' );
        }
        return $out;
    }

    public function transfer() {
        $sections = self::section_labels();
        Ui::panel_open( __( 'Import / Export', 'davix-h2i' ), array( 'lead' => __( 'Move your plugin settings to another site, keep a backup, or start again. Your API key and server address are never included.', 'davix-h2i' ) ) );
        echo '<div class="dvx-grid-2" data-st-transfer>';

        // ---- Export
        echo '<div>';
        Ui::card_open( __( 'Export settings', 'davix-h2i' ), __( 'Download a file with the settings you choose.', 'davix-h2i' ) );
        echo '<div class="dvx-pick" data-st-export-list>';
        foreach ( $sections as $id => $info ) {
            echo '<label class="dvx-pick__item"><input type="checkbox" value="' . esc_attr( $id ) . '" checked /><span class="dvx-pick__text"><strong>' . esc_html( $info[0] ) . '</strong><small>' . esc_html( $info[1] ) . '</small></span></label>';
        }
        echo '</div>';
        echo '<div class="dvx-toolbar" style="margin-top:16px"><button type="button" class="dvs-save-button" data-st-export><span class="dashicons dashicons-download" aria-hidden="true"></span> ' . esc_html__( 'Download settings file', 'davix-h2i' ) . '</button></div>';
        Ui::card_close();
        echo '</div>';

        // ---- Import
        echo '<div>';
        Ui::card_open( __( 'Import settings', 'davix-h2i' ), __( 'Load a settings file from another site. It replaces the sections you tick.', 'davix-h2i' ) );
        echo '<label class="dvx-drop" data-st-drop><input type="file" accept=".json,application/json" data-st-file hidden />';
        echo '<span class="dashicons dashicons-upload dvx-drop__icon" aria-hidden="true"></span>';
        echo '<strong data-st-drop-title>' . esc_html__( 'Drop a settings file here', 'davix-h2i' ) . '</strong>';
        echo '<small data-st-drop-sub>' . esc_html__( 'or click to choose a .json file', 'davix-h2i' ) . '</small></label>';
        echo '<div class="dvx-pick dvx-hidden" data-st-import-list></div>';
        echo '<div class="dvx-callout dvx-hidden" data-st-import-note></div>';
        echo '<div class="dvx-toolbar" style="margin-top:16px"><button type="button" class="dvs-save-button" data-st-import disabled>' . esc_html__( 'Import selected settings', 'davix-h2i' ) . '</button></div>';
        Ui::card_close();
        echo '</div>';
        echo '</div>';

        // ---- Reset
        Ui::card_open( __( 'Start again', 'davix-h2i' ), __( 'Put every setting back to what it was when you first installed the plugin. Your API key, history and saved media are not touched.', 'davix-h2i' ) );
        echo '<div class="dvx-toolbar"><button type="button" class="dvs-btn dvs-btn--danger" data-st-reset data-sections="' . esc_attr( implode( ',', array_keys( $sections ) ) ) . '">' . esc_html__( 'Reset all settings', 'davix-h2i' ) . '</button></div>';
        Ui::card_close();
        Ui::panel_close();
    }
}
