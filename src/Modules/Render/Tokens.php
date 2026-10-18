<?php

namespace DavixWP\Modules\Render;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The {tokens} a template can use, and how they are filled in for a site, a post or a media item.
 */
final class Tokens {

    /** @return array<string,string> token => description */
    public static function supported() {
        return array(
            '{site_name}'          => __( 'The site title', 'davix-h2i' ),
            '{site_logo_url}'      => __( 'The site logo address', 'davix-h2i' ),
            '{post_title}'         => __( 'The post title', 'davix-h2i' ),
            '{post_excerpt}'       => __( 'The post excerpt', 'davix-h2i' ),
            '{author_name}'        => __( 'The author name', 'davix-h2i' ),
            '{date}'               => __( 'The post date', 'davix-h2i' ),
            '{featured_image_url}' => __( 'The featured image address', 'davix-h2i' ),
            '{post_content}'       => __( 'The full text of the post', 'davix-h2i' ),
            '{post_url}'           => __( 'The post address', 'davix-h2i' ),
            '{selected_image_url}' => __( 'The chosen image address', 'davix-h2i' ),
            '{attachment_title}'   => __( 'The chosen media title', 'davix-h2i' ),
        );
    }

    /** Values for a context: array( 'post_id' => 12, 'attachment_id' => 5 ). */
    public static function values( array $context = array() ) {
        $logo_id = (int) get_theme_mod( 'custom_logo' );
        $values  = array(
            '{site_name}'     => get_bloginfo( 'name' ),
            '{site_logo_url}' => $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'full' ) : (string) get_site_icon_url(),
        );
        if ( ! empty( $context['post_id'] ) ) {
            $post = get_post( (int) $context['post_id'] );
            if ( $post ) {
                $values['{post_title}']         = get_the_title( $post );
                $values['{post_excerpt}']       = wp_strip_all_tags( get_the_excerpt( $post ) );
                $values['{author_name}']        = get_the_author_meta( 'display_name', $post->post_author );
                $values['{date}']               = get_the_date( '', $post );
                $values['{featured_image_url}'] = (string) get_the_post_thumbnail_url( $post, 'full' );
                $values['{post_url}']           = (string) get_permalink( $post );
                $values['{post_content}']       = wpautop( wp_kses_post( do_blocks( strip_shortcodes( (string) $post->post_content ) ) ) );
            }
        }
        if ( ! empty( $context['attachment_id'] ) ) {
            $values['{selected_image_url}'] = (string) wp_get_attachment_url( (int) $context['attachment_id'] );
            $values['{attachment_title}']   = get_the_title( (int) $context['attachment_id'] );
        }
        foreach ( array_keys( self::supported() ) as $token ) {
            if ( ! isset( $values[ $token ] ) ) {
                $values[ $token ] = '';
            }
        }
        return $values;
    }

    /** Fills tokens into a template. Values are escaped for HTML. */
    public static function expand( $template, array $context = array() ) {
        $raw    = self::values( $context );
        $values = array_map( 'esc_html', $raw );
        $values['{post_content}'] = $raw['{post_content}']; // already cleaned HTML
        // URLs inside src/href/url() must not be HTML-escaped twice; they are plain addresses.
        foreach ( array( '{site_logo_url}', '{featured_image_url}', '{selected_image_url}', '{post_url}' ) as $url_token ) {
            $values[ $url_token ] = esc_url_raw( $raw[ $url_token ] );
        }
        return strtr( (string) $template, $values );
    }
}
