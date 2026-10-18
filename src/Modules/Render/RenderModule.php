<?php

namespace DavixWP\Modules\Render;

use DavixWP\Admin\Components\AutoPanel;
use DavixWP\Admin\Components\OpPanel;
use DavixWP\Admin\Ui;
use DavixWP\Connection\KeyStore;
use DavixWP\Core\Context;
use DavixWP\Core\Module;
use DavixWP\Core\Options;
use DavixWP\Jobs\Queue;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class RenderModule extends Module {

    const DEFAULT_HTML = '<div class="card"><div class="site">{site_name}</div><h1>{post_title}</h1><p>{post_excerpt}</p></div>';
    const DEFAULT_CSS  = 'body{margin:0;font-family:system-ui,sans-serif}.card{width:100%;height:100%;box-sizing:border-box;padding:64px;background:linear-gradient(135deg,#0b0d10,#1e242e);color:#fff;display:flex;flex-direction:column;justify-content:center}.site{color:#4da3ff;font-weight:700;letter-spacing:.08em;text-transform:uppercase}h1{font-size:64px;margin:16px 0}p{font-size:28px;color:#9ca3af;margin:0}';

    public function register( Context $c ) {
        Options::register(
            'render',
            array(
                'output'  => array( 'mode' => 'copy', 'suffix' => '_card' ),
                'pdf'     => array( 'format' => 'A4', 'landscape' => false, 'printBackground' => true, 'margin' => 24 ),
            ),
            array( __CLASS__, 'sanitize' )
        );

        $r = Options::section( 'render' );
        $c->add_operation( array(
            'id' => 'render.html-to-image', 'label' => __( 'HTML to image', 'davix-h2i' ), 'description' => __( 'Turn a template into an image, such as a social card.', 'davix-h2i' ),
            'group' => 'render', 'input' => 'none', 'store' => 'new', 'accepts' => array(),
            'fields' => array(
                array( 'name' => 'html', 'type' => 'code', 'label' => 'HTML', 'default' => self::DEFAULT_HTML ),
                array( 'name' => 'css', 'type' => 'code', 'label' => 'CSS', 'default' => self::DEFAULT_CSS ),
                'width', 'height', 'format', 'deviceScaleFactor', 'transparent', 'fullPage', 'waitForSelector',
            ),
            'defaults' => array( 'width' => 1200, 'height' => 630, 'format' => 'png' ),
        ) );
        $c->add_operation( array(
            'id' => 'render.html-to-pdf', 'label' => __( 'HTML to PDF', 'davix-h2i' ), 'description' => __( 'Turn a page or template into a PDF.', 'davix-h2i' ),
            'group' => 'render', 'input' => 'none', 'store' => 'new', 'accepts' => array(), 'release' => '2.2',
            'fields' => array(
                array( 'name' => 'html', 'type' => 'code', 'label' => 'HTML', 'default' => '' ),
                array( 'name' => 'css', 'type' => 'code', 'label' => 'CSS', 'default' => '' ),
                'url', 'format', 'landscape', 'printBackground', 'margin',
            ),
            'defaults' => array( 'format' => $r['pdf']['format'], 'landscape' => (bool) $r['pdf']['landscape'], 'printBackground' => (bool) $r['pdf']['printBackground'], 'margin' => (int) $r['pdf']['margin'] ),
        ) );
        $c->add_operation( array(
            'id' => 'render.chart', 'label' => __( 'Chart', 'davix-h2i' ), 'description' => __( 'A chart from your data.', 'davix-h2i' ),
            'group' => 'render', 'input' => 'none', 'store' => 'new', 'accepts' => array(), 'release' => '2.2',
            'fields' => array(
                'type',
                array( 'name' => 'data', 'type' => 'json', 'label' => __( 'Data', 'davix-h2i' ), 'default' => array( array( 'label' => 'A', 'value' => 3 ), array( 'label' => 'B', 'value' => 5 ), array( 'label' => 'C', 'value' => 2 ) ), 'help' => __( 'A list of rows.', 'davix-h2i' ) ),
                'x', 'y', 'title', 'output', 'width', 'height', 'theme',
            ),
            'defaults' => array( 'type' => 'bar', 'x' => 'label', 'y' => 'value', 'output' => 'png' ),
        ) );

        $c->add_tab( array(
            'id' => 'render', 'label' => __( 'H2I Render', 'davix-h2i' ), 'icon' => 'dashicons-layout', 'group' => 'work', 'order' => 40,
            'desc' => __( 'Social cards, HTML to image or PDF, charts', 'davix-h2i' ),
        ) );
        // Create: a page or template to an image or PDF, and charts.
        $c->add_subtab( 'create', array(
            'id' => 'page', 'label' => __( 'Page to image or PDF', 'davix-h2i' ), 'order' => 30, 'operations' => array( 'render.html-to-image', 'render.html-to-pdf' ),
            'render' => function () {
                OpPanel::render( array( 'render.html-to-image', 'render.html-to-pdf' ), array( 'title' => __( 'Turn HTML into an image or PDF', 'davix-h2i' ), 'lead' => __( 'The result is added to your Media Library. Use {tokens} such as {post_title} or {selected_image_url} in the HTML.', 'davix-h2i' ), 'mode_control' => false, 'bulk' => false, 'source' => 'tab', 'selected' => 'render.html-to-image' ) );
            },
        ) );
        $c->add_subtab( 'create', array(
            'id' => 'chart', 'label' => __( 'Chart', 'davix-h2i' ), 'order' => 40, 'operations' => array( 'render.chart' ),
            'render' => function () {
                OpPanel::render( array( 'render.chart' ), array( 'title' => __( 'Make a chart', 'davix-h2i' ), 'lead' => __( 'The result is added to your Media Library.', 'davix-h2i' ), 'mode_control' => false, 'bulk' => false, 'source' => 'tab', 'selected' => 'render.chart' ) );
            },
        ) );
        // The Media Library sidebar: use the chosen image inside your template ({selected_image_url}).
        $c->add_drawer_tab( array( 'id' => 'render', 'label' => __( 'Render', 'davix-h2i' ), 'order' => 60, 'kind' => 'ops', 'any' => true, 'mimes' => array( 'image/' ), 'operations' => array( 'render.html-to-image' ) ) );
        $c->add_subtab( 'render', array(
            'id' => 'auto', 'label' => __( 'Automatic', 'davix-h2i' ), 'order' => 10, 'operations' => array( 'render.html-to-image' ),
            'render' => function () {
                AutoPanel::render( array( 'render.social-card', 'render.post-pdf', 'render.image-card' ), array( 'lead' => __( 'Rules that run in the background when a post is published or an image is uploaded. To make something by hand, use Create.', 'davix-h2i' ), 'trigger' => __( 'Rule is on', 'davix-h2i' ) ) );
            },
        ) );
        $c->add_auto_action( array(
            'id' => 'render.social-card', 'operation' => 'render.html-to-image', 'label' => __( 'Social card for a published post', 'davix-h2i' ),
            'description' => __( 'Makes a card image from your template and can use it as the featured image.', 'davix-h2i' ), 'group' => 'render', 'order' => 10,
            'trigger' => 'publish', 'types' => array( __CLASS__, 'post_types' ),
            'form' => array( __CLASS__, 'card_form' ), 'sanitize' => array( __CLASS__, 'clean_card' ), 'plan' => array( __CLASS__, 'card_plan' ),
        ) );
        $c->add_auto_action( array(
            'id' => 'render.image-card', 'operation' => 'render.html-to-image', 'label' => __( 'Place a new image in your HTML', 'davix-h2i' ),
            'description' => __( 'Uses each uploaded image as {selected_image_url} in your own HTML (a frame, a banner, a share card) and saves the result as a new image.', 'davix-h2i' ),
            'group' => 'render', 'order' => 30, 'accepts' => array( 'image/' ),
            'types' => array( 'image/jpeg' => 'JPEG', 'image/png' => 'PNG', 'image/webp' => 'WebP', 'image/gif' => 'GIF', 'image/avif' => 'AVIF' ),
            'form' => array( __CLASS__, 'image_form' ), 'sanitize' => array( __CLASS__, 'clean_image' ), 'plan' => array( __CLASS__, 'image_plan' ),
        ) );
        $c->add_auto_action( array(
            'id' => 'render.post-pdf', 'operation' => 'render.html-to-pdf', 'label' => __( 'PDF copy of a published post', 'davix-h2i' ),
            'description' => __( 'Saves every published post as a PDF in your Media Library, laid out with your own template.', 'davix-h2i' ), 'group' => 'render', 'order' => 20,
            'trigger' => 'publish', 'types' => array( __CLASS__, 'post_types' ),
            'form' => array( __CLASS__, 'pdf_form' ), 'sanitize' => array( __CLASS__, 'clean_pdf' ), 'plan' => array( __CLASS__, 'pdf_plan' ),
        ) );
        add_filter( 'davix_h2i_operation_params', array( $this, 'expand_tokens' ), 5, 3 );
        $c->add_rest( array( $this, 'routes' ) );
    }

    /** In a template run from the sidebar, {selected_image_url} and the other tokens are filled from the chosen image. */
    public function expand_tokens( $params, $operation, $req = array() ) {
        if ( ! in_array( (string) $operation, array( 'render.html-to-image', 'render.html-to-pdf' ), true ) || ! is_array( $req ) || empty( $req['object_id'] ) ) {
            return $params;
        }
        foreach ( array( 'html', 'css' ) as $key ) {
            if ( isset( $params[ $key ] ) && is_string( $params[ $key ] ) ) {
                $params[ $key ] = Tokens::expand( $params[ $key ], array( 'attachment_id' => (int) $req['object_id'] ) );
            }
        }
        return $params;
    }

    public function routes() {
        \DavixWP\Rest\Api::route(
            '/render/social-card',
            'POST',
            function ( \WP_REST_Request $r ) {
                $post = get_post( (int) $r->get_param( 'post_id' ) );
                if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) {
                    return \DavixWP\Rest\Api::error( __( 'You cannot do this for this post.', 'davix-h2i' ), 403, 'rest_forbidden' );
                }
                $rule = array();
                foreach ( \DavixWP\Core\AutoRules::rules() as $candidate ) {
                    if ( 'render.social-card' === $candidate['action'] ) {
                        $rule = $candidate['params'];
                        break;
                    }
                }
                $plan                        = self::card_plan( $rule, $post->ID );
                $plan['params']['_featured'] = ! has_post_thumbnail( $post );
                $job                         = Queue::enqueue( array( 'operation' => $plan['operation'], 'object_id' => 0, 'params' => $plan['params'], 'source' => 'editor' ) );
                return array( 'ok' => (bool) $job );
            }
        );
    }

    /** The public post types a card can be made for. */
    public static function post_types() {
        $types = array();
        foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
            if ( 'attachment' !== $pt->name ) {
                $types[ $pt->name ] = $pt->labels->name;
            }
        }
        return $types;
    }

    /** The settings of a social card rule, as a form in the rule's side panel. */
    public static function card_form( $params ) {
        $p = array_merge( array( 'html' => self::DEFAULT_HTML, 'css' => self::DEFAULT_CSS, 'width' => 1200, 'height' => 630, 'format' => 'png', 'set_featured' => false ), is_array( $params ) ? $params : array() );
        echo '<div data-auto-plain>';
        Ui::table_open();
        Ui::row( array( 'name' => 'html', 'type' => 'code', 'label' => 'HTML', 'rows' => 8, 'value' => $p['html'], 'attrs' => 'data-vp="html"' ) );
        Ui::row( array( 'name' => 'css', 'type' => 'code', 'label' => 'CSS', 'rows' => 8, 'value' => $p['css'], 'attrs' => 'data-vp="css"' ) );
        Ui::row( array( 'name' => 'width', 'type' => 'number', 'min' => 1, 'label' => __( 'Width', 'davix-h2i' ), 'value' => $p['width'], 'attrs' => 'data-vp="width"' ) );
        Ui::row( array( 'name' => 'height', 'type' => 'number', 'min' => 1, 'label' => __( 'Height', 'davix-h2i' ), 'value' => $p['height'], 'attrs' => 'data-vp="height"' ) );
        Ui::row( array( 'name' => 'format', 'type' => 'select', 'label' => __( 'Format', 'davix-h2i' ), 'options' => array( 'png' => 'PNG', 'jpeg' => 'JPEG', 'webp' => 'WebP' ), 'value' => $p['format'], 'attrs' => 'data-vp="format"' ) );
        Ui::row( array( 'name' => 'set_featured', 'type' => 'switch', 'label' => __( 'Use the card as the featured image when there is none', 'davix-h2i' ), 'value' => ! empty( $p['set_featured'] ) ? 1 : 0, 'attrs' => 'data-vp="set_featured" data-vp-type="switch"' ) );
        Ui::table_close();
        echo '<h4>' . esc_html__( 'Tokens you can use', 'davix-h2i' ) . '</h4><div class="dvx-table-wrap"><table class="dvx-table"><tbody>';
        foreach ( Tokens::supported() as $token => $desc ) {
            echo '<tr><td><span class="davix-h2i-token-pill">' . esc_html( $token ) . '</span></td><td>' . esc_html( $desc ) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    public static function clean_card( array $v ) {
        return array(
            'html'         => isset( $v['html'] ) && '' !== trim( (string) $v['html'] ) ? (string) wp_kses_post_deep_html( $v['html'] ) : self::DEFAULT_HTML,
            'css'          => isset( $v['css'] ) ? wp_strip_all_tags( (string) $v['css'] ) : self::DEFAULT_CSS,
            'width'        => max( 1, min( 5000, (int) ( isset( $v['width'] ) ? $v['width'] : 1200 ) ) ),
            'height'       => max( 1, min( 5000, (int) ( isset( $v['height'] ) ? $v['height'] : 630 ) ) ),
            'format'       => isset( $v['format'] ) && in_array( $v['format'], array( 'png', 'jpeg', 'webp' ), true ) ? $v['format'] : 'png',
            'set_featured' => ! empty( $v['set_featured'] ),
        );
    }

    const IMAGE_HTML = '<div class="frame"><img src="{selected_image_url}" alt=""><p>{attachment_title}</p></div>';
    const IMAGE_CSS  = 'body{margin:0;font-family:system-ui,sans-serif}.frame{width:100%;height:100%;box-sizing:border-box;padding:32px;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center}.frame img{max-width:100%;max-height:85%;object-fit:contain}.frame p{margin:16px 0 0;font-size:24px;color:#333}';

    /** A new image placed into the rule's HTML. The image address is filled in when the job runs. */
    public static function image_form( $params ) {
        $p = array_merge( array( 'html' => self::IMAGE_HTML, 'css' => self::IMAGE_CSS, 'width' => 1200, 'height' => 630, 'format' => 'png' ), is_array( $params ) ? $params : array() );
        echo '<div data-auto-plain>';
        Ui::table_open();
        Ui::row( array( 'name' => 'html', 'type' => 'code', 'label' => 'HTML', 'rows' => 8, 'value' => $p['html'], 'attrs' => 'data-vp="html"' ) );
        Ui::row( array( 'name' => 'css', 'type' => 'code', 'label' => 'CSS', 'rows' => 8, 'value' => $p['css'], 'attrs' => 'data-vp="css"' ) );
        Ui::row( array( 'name' => 'width', 'type' => 'number', 'min' => 1, 'label' => __( 'Width', 'davix-h2i' ), 'value' => $p['width'], 'attrs' => 'data-vp="width"' ) );
        Ui::row( array( 'name' => 'height', 'type' => 'number', 'min' => 1, 'label' => __( 'Height', 'davix-h2i' ), 'value' => $p['height'], 'attrs' => 'data-vp="height"' ) );
        Ui::row( array( 'name' => 'format', 'type' => 'select', 'label' => __( 'Format', 'davix-h2i' ), 'options' => array( 'png' => 'PNG', 'jpeg' => 'JPEG', 'webp' => 'WebP' ), 'value' => $p['format'], 'attrs' => 'data-vp="format"' ) );
        Ui::table_close();
        echo '<p class="description">' . esc_html__( 'Put {selected_image_url} where the uploaded image goes. {attachment_title} and {site_name} also work. The service must be able to reach your site to load the image.', 'davix-h2i' ) . '</p></div>';
    }

    public static function clean_image( array $v ) {
        $c = self::clean_card( $v );
        unset( $c['set_featured'] );
        if ( ! isset( $v['html'] ) || '' === trim( (string) $v['html'] ) ) {
            $c['html'] = self::IMAGE_HTML;
        }
        if ( ! isset( $v['css'] ) ) {
            $c['css'] = self::IMAGE_CSS;
        }
        return $c;
    }

    public static function image_plan( array $params ) {
        $p = self::clean_image( $params );
        return array( 'operation' => 'render.html-to-image', 'params' => array( 'html' => $p['html'], 'css' => $p['css'], 'width' => $p['width'], 'height' => $p['height'], 'format' => $p['format'] ) );
    }

    const PDF_HTML = '<h1>{post_title}</h1><p><em>{author_name} - {date}</em></p>{post_content}';
    const PDF_CSS  = 'body{font-family:Georgia,serif;line-height:1.5;color:#111}h1{font-size:30px}img{max-width:100%;height:auto}';

    public static function pdf_form( $params ) {
        $p = array_merge( array( 'html' => self::PDF_HTML, 'css' => self::PDF_CSS, 'format' => 'A4', 'landscape' => false ), is_array( $params ) ? $params : array() );
        echo '<div data-auto-plain>';
        Ui::table_open();
        Ui::row( array( 'name' => 'html', 'type' => 'code', 'label' => 'HTML', 'rows' => 8, 'value' => $p['html'], 'attrs' => 'data-vp="html"' ) );
        Ui::row( array( 'name' => 'css', 'type' => 'code', 'label' => 'CSS', 'rows' => 6, 'value' => $p['css'], 'attrs' => 'data-vp="css"' ) );
        Ui::row( array( 'name' => 'format', 'type' => 'select', 'label' => __( 'Page size', 'davix-h2i' ), 'options' => array( 'A4' => 'A4', 'Letter' => 'Letter', 'A3' => 'A3', 'Legal' => 'Legal' ), 'value' => $p['format'], 'attrs' => 'data-vp="format"' ) );
        Ui::row( array( 'name' => 'landscape', 'type' => 'switch', 'label' => __( 'Landscape', 'davix-h2i' ), 'value' => ! empty( $p['landscape'] ) ? 1 : 0, 'attrs' => 'data-vp="landscape" data-vp-type="switch"' ) );
        Ui::table_close();
        echo '</div>';
    }

    public static function clean_pdf( array $v ) {
        return array(
            'html'      => isset( $v['html'] ) && '' !== trim( (string) $v['html'] ) ? (string) wp_kses_post_deep_html( $v['html'] ) : self::PDF_HTML,
            'css'       => isset( $v['css'] ) ? wp_strip_all_tags( (string) $v['css'] ) : self::PDF_CSS,
            'format'    => isset( $v['format'] ) && in_array( $v['format'], array( 'A4', 'Letter', 'A3', 'Legal' ), true ) ? $v['format'] : 'A4',
            'landscape' => ! empty( $v['landscape'] ),
        );
    }

    public static function pdf_plan( array $params, $post_id ) {
        $p = self::clean_pdf( $params );
        return array(
            'operation' => 'render.html-to-pdf',
            'object_id' => 0,
            'params'    => array(
                'html'      => Tokens::expand( $p['html'], array( 'post_id' => (int) $post_id ) ),
                'css'       => $p['css'],
                'format'    => $p['format'],
                'landscape' => $p['landscape'],
                'printBackground' => true,
            ),
        );
    }

    /** The call a card rule makes for one post. */
    public static function card_plan( array $params, $post_id ) {
        $p    = self::clean_card( $params );
        $post = get_post( (int) $post_id );
        return array(
            'operation' => 'render.html-to-image',
            'object_id' => 0,
            'params'    => array(
                'html'      => Tokens::expand( $p['html'], array( 'post_id' => (int) $post_id ) ),
                'css'       => $p['css'],
                'width'     => $p['width'],
                'height'    => $p['height'],
                'format'    => $p['format'],
                '_post_id'  => (int) $post_id,
                '_featured' => $p['set_featured'] && $post && ! has_post_thumbnail( $post ),
            ),
        );
    }
}

if ( ! function_exists( __NAMESPACE__ . '\\wp_kses_post_deep_html' ) ) {
    /** Template HTML is rendered by the Davix H2I service in its own sandbox; only script-like content is removed here. */
    function wp_kses_post_deep_html( $html ) {
        $html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) $html );
        return preg_replace( '/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html );
    }
}
