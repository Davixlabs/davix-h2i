<?php
/**
 * Writes AI text results into WordPress (alt text, caption, title, description, tags, SEO), following the user's
 * policy: apply automatically, only fill empty fields, or hold the suggestion for review in History.
 */

namespace DavixWP\Media;

use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class TextWriter {

    /**
     * Reduces an AI result to plain, flat text fields the UI can show.
     *
     * @param string $operation
     * @param mixed  $result    The envelope's `result`.
     * @return array<string,mixed>
     */
    public static function normalize( $operation, $result ) {
        if ( ! is_array( $result ) ) {
            return array( 'text' => is_scalar( $result ) ? (string) $result : '' );
        }
        $out = array();
        switch ( $operation ) {
            case 'ai.content.alt-text':
                $out = array(
                    'alt_text'         => isset( $result['alt_text'] ) ? (string) $result['alt_text'] : '',
                    'long_description' => isset( $result['long_description'] ) ? (string) $result['long_description'] : '',
                );
                break;
            case 'ai.content.caption':
                $out = array(
                    'caption'  => isset( $result['caption'] ) ? (string) $result['caption'] : '',
                    'hashtags' => isset( $result['hashtags'] ) ? array_map( 'strval', (array) $result['hashtags'] ) : array(),
                );
                break;
            case 'ai.content.tags':
                $tags = array();
                foreach ( isset( $result['tags'] ) ? (array) $result['tags'] : array() as $t ) {
                    $tags[] = is_array( $t ) && isset( $t['tag'] ) ? (string) $t['tag'] : (string) $t;
                }
                $out = array( 'tags' => $tags );
                break;
            case 'ai.content.seo':
                $out = array(
                    'title'            => isset( $result['title'] ) ? (string) $result['title'] : '',
                    'meta_description' => isset( $result['meta_description'] ) ? (string) $result['meta_description'] : '',
                    'keywords'         => isset( $result['keywords'] ) ? array_map( 'strval', (array) $result['keywords'] ) : array(),
                    'slug'             => isset( $result['slug'] ) ? (string) $result['slug'] : '',
                );
                break;
            case 'ai.content.title':
                $out = array(
                    'title'        => isset( $result['title'] ) ? (string) $result['title'] : '',
                    'alternatives' => isset( $result['alternatives'] ) ? array_map( 'strval', (array) $result['alternatives'] ) : array(),
                );
                break;
            case 'ai.content.social-post':
                $out = array(
                    'post_text' => isset( $result['post_text'] ) ? (string) $result['post_text'] : '',
                    'hashtags'  => isset( $result['hashtags'] ) ? array_map( 'strval', (array) $result['hashtags'] ) : array(),
                );
                break;
            case 'ai.content.product-description':
                $out = array(
                    'name'              => isset( $result['name'] ) ? (string) $result['name'] : '',
                    'short_description' => isset( $result['short_description'] ) ? (string) $result['short_description'] : '',
                    'long_description'  => isset( $result['long_description'] ) ? (string) $result['long_description'] : '',
                    'features'          => isset( $result['features'] ) ? array_map( 'strval', (array) $result['features'] ) : array(),
                );
                break;
            case 'ai.documents.summarize':
                $out = array(
                    'summary'    => isset( $result['summary'] ) ? (string) $result['summary'] : '',
                    'key_points' => isset( $result['key_points'] ) ? array_map( 'strval', (array) $result['key_points'] ) : array(),
                );
                break;
            case 'ai.documents.translate':
                $out = array(
                    'translated_text'          => isset( $result['translated_text'] ) ? (string) $result['translated_text'] : '',
                    'detected_source_language' => isset( $result['detected_source_language'] ) ? (string) $result['detected_source_language'] : '',
                );
                break;
            case 'ai.documents.correct':
                $out = array(
                    'corrected_text' => isset( $result['corrected_text'] ) ? (string) $result['corrected_text'] : '',
                    'corrections'    => isset( $result['corrections'] ) ? (array) $result['corrections'] : array(),
                );
                break;
            case 'ai.images.moderate':
                $out = array(
                    'safe'       => ! empty( $result['safe'] ),
                    'summary'    => isset( $result['summary'] ) ? (string) $result['summary'] : '',
                    'categories' => isset( $result['categories'] ) ? (array) $result['categories'] : array(),
                );
                break;
            default:
                $out = $result;
        }
        return $out;
    }

    /**
     * Applies normalized text to an attachment according to the policy.
     *
     * @param string $operation
     * @param int    $attachment_id
     * @param array  $text   Normalized text.
     * @param string $policy auto | empty | review | off
     * @return array{applied:array<string,mixed>,held:bool}
     */
    public static function apply( $operation, $attachment_id, array $text, $policy = '' ) {
        $policy = $policy ? $policy : (string) Options::get( 'ai.apply.policy', 'empty' );
        $result = array( 'applied' => array(), 'held' => false );
        if ( ! $attachment_id || 'off' === $policy ) {
            return $result;
        }
        if ( 'review' === $policy ) {
            $result['held'] = true;
            return $result;
        }
        $overwrite = ( 'auto' === $policy );
        $post      = get_post( $attachment_id );
        if ( ! $post ) {
            return $result;
        }

        switch ( $operation ) {
            case 'ai.content.alt-text':
                $current = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                if ( '' !== $text['alt_text'] && ( $overwrite || '' === $current ) ) {
                    update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $text['alt_text'] ) );
                    $result['applied']['alt'] = $text['alt_text'];
                }
                if ( ! empty( $text['long_description'] ) && ( $overwrite || '' === trim( $post->post_content ) ) ) {
                    wp_update_post( array( 'ID' => $attachment_id, 'post_content' => wp_kses_post( $text['long_description'] ) ) );
                    $result['applied']['description'] = $text['long_description'];
                }
                break;
            case 'ai.content.caption':
                if ( '' !== $text['caption'] && ( $overwrite || '' === trim( $post->post_excerpt ) ) ) {
                    wp_update_post( array( 'ID' => $attachment_id, 'post_excerpt' => sanitize_text_field( $text['caption'] ) ) );
                    $result['applied']['caption'] = $text['caption'];
                }
                break;
            case 'ai.content.title':
                if ( '' !== $text['title'] && $overwrite ) {
                    wp_update_post( array( 'ID' => $attachment_id, 'post_title' => sanitize_text_field( $text['title'] ) ) );
                    $result['applied']['title'] = $text['title'];
                }
                break;
            case 'ai.content.tags':
                if ( ! empty( $text['tags'] ) && ( $overwrite || ! get_post_meta( $attachment_id, '_davix_h2i_tags', true ) ) ) {
                    update_post_meta( $attachment_id, '_davix_h2i_tags', array_map( 'sanitize_text_field', $text['tags'] ) );
                    $result['applied']['tags'] = $text['tags'];
                }
                break;
            case 'ai.content.seo':
                update_post_meta( $attachment_id, '_davix_h2i_seo', $text );
                $result['applied']['seo'] = true;
                break;
            case 'ai.documents.summarize':
                if ( '' !== $text['summary'] ) {
                    update_post_meta( $attachment_id, '_davix_h2i_summary', array( 'summary' => $text['summary'], 'key_points' => $text['key_points'] ) );
                    $result['applied']['summary'] = $text['summary'];
                    if ( $overwrite || '' === trim( $post->post_content ) ) {
                        wp_update_post( array( 'ID' => $attachment_id, 'post_content' => wp_kses_post( $text['summary'] ) ) );
                    }
                }
                break;
            case 'ai.images.moderate':
                update_post_meta( $attachment_id, '_davix_h2i_flags', array( 'safe' => ! empty( $text['safe'] ), 'summary' => $text['summary'], 'time' => time() ) );
                $result['applied']['flags'] = true;
                break;
            default:
                break;
        }
        return $result;
    }

    /** Applies a held suggestion the user approved (always overwrites). */
    public static function apply_approved( $operation, $attachment_id, array $text ) {
        return self::apply( $operation, $attachment_id, $text, 'auto' );
    }
}
