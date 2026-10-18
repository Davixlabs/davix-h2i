<?php

namespace DavixWP\Modules\Ai;

use DavixWP\Admin\Components\AutoPanel;
use DavixWP\Admin\Components\OpPanel;
use DavixWP\Admin\Ui;
use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Capabilities\LimitsService;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AiModule extends Module {

    const TONES = array( '' => '', 'neutral' => 'Neutral', 'friendly' => 'Friendly', 'professional' => 'Professional', 'playful' => 'Playful', 'persuasive' => 'Persuasive' );

    public function register( Context $c ) {
        Options::register(
            'ai',
            array(
                'language' => '',
                'tone'     => '',
                'apply'    => array( 'policy' => 'empty' ),
            ),
            array( __CLASS__, 'sanitize' )
        );
        Operations::register( $c );
        add_filter( 'davix_h2i_operation_params', array( $this, 'default_params' ), 10, 2 );

        $c->add_tab( array(
            'id' => 'ai', 'label' => __( 'AI', 'davix-h2i' ), 'icon' => 'dashicons-superhero', 'group' => 'work', 'order' => 35,
            'desc' => __( 'How the AI writes, rules for new uploads, and what is ready', 'davix-h2i' ),
        ) );
        $media = array( 'ai.content.alt-text', 'ai.content.caption', 'ai.content.tags', 'ai.images.moderate' );
        $text  = array( 'ai.content.seo', 'ai.documents.summarize', 'ai.content.title', 'ai.content.social-post', 'ai.content.product-description', 'ai.documents.translate', 'ai.documents.correct' );
        $make  = array( 'ai.generation.generate', 'ai.generation.background', 'ai.generation.social', 'ai.generation.thumbnail' );

        // On this tab: the rules for new uploads, the settings, the status. The work itself is in the Media Library sidebar and in Create.
        $auto = array_merge( $media, array( 'ai.content.title', 'ai.content.seo', 'ai.documents.summarize' ) );
        $c->add_subtab( 'ai', array( 'id' => 'auto', 'label' => __( 'Automatic', 'davix-h2i' ), 'order' => 10, 'operations' => $auto,
            'render' => function () use ( $auto ) {
                AutoPanel::render( $auto, array( 'lead' => __( 'Rules that run on every new file, in the background. Your files are only sent when you add a rule. To work on files you already have, open one in the Media Library.', 'davix-h2i' ) ) );
            } ) );
        $c->add_subtab( 'ai', array( 'id' => 'settings', 'label' => __( 'Settings', 'davix-h2i' ), 'order' => 20, 'render' => array( $this, 'settings' ) ) );
        $c->add_subtab( 'ai', array( 'id' => 'status', 'label' => __( 'Status', 'davix-h2i' ), 'order' => 30, 'render' => array( $this, 'status' ) ) );

        // Create: writing help and new images.
        $c->add_subtab( 'create', array( 'id' => 'write', 'label' => __( 'Write', 'davix-h2i' ), 'order' => 10, 'operations' => $text,
            'render' => function () use ( $text ) {
                OpPanel::render( $text, array( 'title' => __( 'Writing help', 'davix-h2i' ), 'lead' => __( 'Paste a text (or choose a file) and get SEO text, summaries, translations and more.', 'davix-h2i' ), 'mode_control' => false, 'bulk' => false, 'selected' => 'ai.content.seo', 'source' => 'tab' ) );
            } ) );
        $c->add_subtab( 'create', array( 'id' => 'ai-images', 'label' => __( 'AI images', 'davix-h2i' ), 'order' => 20, 'operations' => $make,
            'render' => function () use ( $make ) {
                OpPanel::render( $make, array( 'title' => __( 'Create images', 'davix-h2i' ), 'lead' => __( 'Describe it and get a new image. It is added to your Media Library.', 'davix-h2i' ), 'mode_control' => false, 'bulk' => false, 'selected' => 'ai.generation.generate', 'source' => 'generator' ) );
            } ) );

        // Rules for new uploads: the text the AI writes follows the setting "When the AI writes text".
        $policy_plan = function ( $op ) {
            return function ( $params ) use ( $op ) {
                return array( 'operation' => $op, 'params' => array_merge( (array) $params, array( '_policy' => Options::get( 'ai.apply.policy', 'empty' ) ) ) );
            };
        };
        $images = array( 'image/jpeg' => 'JPEG', 'image/png' => 'PNG', 'image/webp' => 'WebP', 'image/gif' => 'GIF', 'image/avif' => 'AVIF' );
        $c->add_auto_action( array( 'id' => 'ai.content.alt-text', 'group' => 'ai', 'order' => 30, 'accepts' => array( 'image/' ), 'types' => $images,
            'plan' => $policy_plan( 'ai.content.alt-text' ),
            'skip' => function ( $id ) {
                return '' !== (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
            } ) );
        $c->add_auto_action( array( 'id' => 'ai.content.caption', 'group' => 'ai', 'order' => 31, 'accepts' => array( 'image/' ), 'types' => $images, 'plan' => $policy_plan( 'ai.content.caption' ) ) );
        $c->add_auto_action( array( 'id' => 'ai.content.tags', 'group' => 'ai', 'order' => 32, 'accepts' => array( 'image/' ), 'types' => $images, 'plan' => $policy_plan( 'ai.content.tags' ) ) );
        $with_docs = $images + array( 'application/pdf' => 'PDF', 'text/plain' => 'Text' );
        $c->add_auto_action( array( 'id' => 'ai.content.title', 'label' => __( 'Image title', 'davix-h2i' ), 'description' => __( 'Give new images a clear title.', 'davix-h2i' ), 'group' => 'ai', 'order' => 34, 'accepts' => array( 'image/' ), 'types' => $images,
            'plan' => function ( $params ) {
                // A title is only changed when "When the AI writes text" is set to replace; the file name is kept otherwise.
                return array( 'operation' => 'ai.content.title', 'params' => array_merge( (array) $params, array( '_policy' => Options::get( 'ai.apply.policy', 'empty' ) ) ) );
            } ) );
        $c->add_auto_action( array( 'id' => 'ai.content.seo', 'description' => __( 'Write a search title, description and keywords for new images and PDFs.', 'davix-h2i' ), 'group' => 'ai', 'order' => 35, 'accepts' => array( 'image/', 'application/pdf' ), 'types' => $images + array( 'application/pdf' => 'PDF' ),
            'plan' => $policy_plan( 'ai.content.seo' ) ) );
        $c->add_auto_action( array( 'id' => 'ai.documents.summarize', 'label' => __( 'Summary of a document', 'davix-h2i' ), 'description' => __( 'Write a short summary of every new PDF or text file.', 'davix-h2i' ), 'group' => 'ai', 'order' => 36, 'accepts' => array( 'application/pdf', 'text/' ), 'types' => array( 'application/pdf' => 'PDF', 'text/plain' => 'Text' ),
            'plan' => $policy_plan( 'ai.documents.summarize' ) ) );
        $c->add_auto_action( array( 'id' => 'ai.images.moderate', 'description' => __( 'Check new images for unsafe content.', 'davix-h2i' ), 'group' => 'ai', 'order' => 33, 'accepts' => array( 'image/' ), 'types' => $images,
            'plan' => function ( $params ) {
                return array( 'operation' => 'ai.images.moderate', 'params' => array_merge( (array) $params, array( '_policy' => 'auto' ) ) );
            } ) );

        // The Media Library sidebar: every AI action that fits the chosen files.
        $c->add_drawer_tab( array( 'id' => 'ai', 'label' => __( 'AI', 'davix-h2i' ), 'order' => 30, 'kind' => 'ops', 'mimes' => array(), 'operations' => array_merge( $media, $text, array( 'ai.generation.product', 'ai.enhancement.upscale' ) ) ) );

        $c->add_cli( 'alt-text', Cli\AltTextCommand::class );
        $c->add_assets(
            'davix-h2i-editor',
            array(
                'css'     => array(),
                'js'      => array( 'editor/sidebar' ),
                'deps'    => array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ),
                'screens' => array( 'editor' ),
            )
        );
    }

    /** Language and tone from the settings, when the call does not set them. */
    public function default_params( $params, $operation ) {
        if ( 0 !== strpos( (string) $operation, 'ai.' ) ) {
            return $params;
        }
        $o = Options::section( 'ai' );
        foreach ( array( 'language', 'tone' ) as $key ) {
            if ( ! isset( $params[ $key ] ) && '' !== $o[ $key ] && \DavixWP\Operations\Contract::has_field( $operation, $key ) ) {
                $params[ $key ] = $o[ $key ];
            }
        }
        return $params;
    }

    public function settings() {
        Ui::form_open( 'ai' );
        Ui::panel_open( __( 'AI settings', 'davix-h2i' ), array( 'save' => true, 'lead' => __( 'How the AI writes, and what happens to its text.', 'davix-h2i' ) ) );
        Ui::card_open( __( 'Writing', 'davix-h2i' ) );
        Ui::rows( array(
            array( 'name' => 'ai.language', 'label' => __( 'Language', 'davix-h2i' ), 'placeholder' => 'en', 'help' => __( 'A language code like en, fr or de. Empty uses the language of the content.', 'davix-h2i' ) ),
            array( 'name' => 'ai.tone', 'type' => 'select', 'label' => __( 'Tone', 'davix-h2i' ), 'options' => array_merge( array( '' => __( 'Default', 'davix-h2i' ) ), array_slice( self::TONES, 1 ) ) ),
        ) );
        Ui::card_close();
        Ui::card_open( __( 'What happens to the text', 'davix-h2i' ), __( 'This applies to alt text, captions, titles and tags.', 'davix-h2i' ) );
        Ui::rows( array(
            array( 'name' => 'ai.apply.policy', 'type' => 'select', 'label' => __( 'When the AI writes text', 'davix-h2i' ), 'options' => array(
                'empty'  => __( 'Fill it in only if the field is empty', 'davix-h2i' ),
                'auto'   => __( 'Always replace what is there', 'davix-h2i' ),
                'review' => __( 'Hold it for my review (Activity > Review)', 'davix-h2i' ),
                'off'    => __( 'Only show it, never save it', 'davix-h2i' ),
            ) ),
        ) );
        Ui::card_close();
        Ui::panel_close();
        Ui::form_close();
    }

    public function status() {
        Ui::panel_open( __( 'AI status', 'davix-h2i' ), array( 'lead' => __( 'Which AI features are ready right now. This comes straight from your Davix H2I service.', 'davix-h2i' ) ) );
        $snap = LimitsService::snapshot();
        if ( empty( $snap['ai']['features_by_family'] ) ) {
            Ui::empty_state( __( 'AI status is not available yet.', 'davix-h2i' ) );
            Ui::panel_close();
            return;
        }
        $mine = $this->manifest['operations'];
        echo '<div class="dvx-table-wrap"><table class="dvx-table"><thead><tr><th>' . esc_html__( 'Feature', 'davix-h2i' ) . '</th><th>' . esc_html__( 'In your plan', 'davix-h2i' ) . '</th><th>' . esc_html__( 'Readiness', 'davix-h2i' ) . '</th></tr></thead><tbody>';
        $context = \DavixWP\Core\Services::get( 'context' );
        foreach ( $mine as $id ) {
            $def   = $context->operation( $id );
            $state = CapabilityGate::state( $id );
            $ready = CapabilityGate::readiness( $id );
            echo '<tr><td>' . esc_html( $def ? $def['label'] : $id ) . '</td><td>' . ( CapabilityGate::LOCKED === $state ? Ui::badge( __( 'Not included', 'davix-h2i' ), 'warning' ) : Ui::badge( __( 'Included', 'davix-h2i' ), 'success' ) ) . '</td><td>'; // phpcs:ignore WordPress.Security.EscapeOutput
            if ( 'production-ready' === $ready ) {
                echo Ui::badge( __( 'Ready', 'davix-h2i' ), 'success' ); // phpcs:ignore WordPress.Security.EscapeOutput
            } elseif ( 'feature-unavailable' === $ready ) {
                echo Ui::badge( __( 'Unavailable', 'davix-h2i' ), 'danger' ); // phpcs:ignore WordPress.Security.EscapeOutput
            } elseif ( $ready ) {
                echo Ui::badge( __( 'Beta', 'davix-h2i' ), 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput
            } else {
                echo '-';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
        Ui::panel_close();
    }

    public static function sanitize( array $v, array $d ) {
        $policies = array( 'empty', 'auto', 'review', 'off' );
        return array(
            'language' => isset( $v['language'] ) ? preg_replace( '/[^A-Za-z\-]/', '', (string) $v['language'] ) : '',
            'tone'     => isset( $v['tone'] ) && isset( self::TONES[ $v['tone'] ] ) ? $v['tone'] : '',
            'apply'    => array( 'policy' => isset( $v['apply']['policy'] ) && in_array( $v['apply']['policy'], $policies, true ) ? $v['apply']['policy'] : 'empty' ),
        );
    }
}
