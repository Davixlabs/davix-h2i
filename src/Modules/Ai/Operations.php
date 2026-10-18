<?php

namespace DavixWP\Modules\Ai;

use DavixWP\Core\Context;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Operations {

    public static function register( Context $c ) {
        $img  = array( 'group' => 'ai', 'file_field' => 'files', 'accepts' => array( 'image/' ), 'store' => 'text', 'result' => 'text', 'release' => '2.1', 'async' => true );
        $text = array_merge( $img, array( 'input' => 'optional', 'accepts' => array( 'image/', 'application/pdf', 'text/' ), 'multiple' => false ) );
        $img['multiple'] = false;

        $c->add_operation( array_merge( $img, array( 'id' => 'ai.content.alt-text', 'input' => 'file', 'label' => __( 'Alt text', 'davix-h2i' ), 'description' => __( 'Describe an image for people who cannot see it.', 'davix-h2i' ), 'fields' => array( 'language', 'tone', 'max_length', 'instructions' ) ) ) );
        $c->add_operation( array_merge( $img, array( 'id' => 'ai.content.caption', 'input' => 'file', 'label' => __( 'Caption', 'davix-h2i' ), 'description' => __( 'A short caption, with hashtags if you like.', 'davix-h2i' ), 'fields' => array( 'language', 'tone', 'max_length', 'hashtags', 'instructions' ) ) ) );
        $c->add_operation( array_merge( $img, array( 'id' => 'ai.content.tags', 'input' => 'file', 'label' => __( 'Tags', 'davix-h2i' ), 'description' => __( 'Keywords that describe the image.', 'davix-h2i' ), 'fields' => array( 'language', 'max' ) ) ) );
        $c->add_operation( array_merge( $img, array( 'id' => 'ai.images.moderate', 'input' => 'file', 'csv' => array( 'categories' ), 'label' => __( 'Safety check', 'davix-h2i' ), 'description' => __( 'Check an image for unsafe content.', 'davix-h2i' ), 'fields' => array( 'strictness', 'categories' ) ) ) );
        $c->add_operation( array_merge( $text, array( 'id' => 'ai.content.seo', 'label' => __( 'SEO title and description', 'davix-h2i' ), 'description' => __( 'Search title, description, keywords and slug.', 'davix-h2i' ), 'fields' => array( 'text', 'language', 'tone', 'target_keyword', 'instructions' ) ) ) );
        $c->add_operation( array_merge( $text, array( 'id' => 'ai.documents.summarize', 'label' => __( 'Summary', 'davix-h2i' ), 'description' => __( 'A short summary of a text or document.', 'davix-h2i' ), 'fields' => array( 'text', 'length', 'style', 'focus', 'language' ) ) ) );
        $c->add_operation( array_merge( $text, array( 'id' => 'ai.content.title', 'label' => __( 'Title ideas', 'davix-h2i' ), 'description' => __( 'Headlines for your content.', 'davix-h2i' ), 'release' => '2.2', 'fields' => array( 'text', 'language', 'tone', 'max_length', 'alternatives' ) ) ) );
        $c->add_operation( array_merge( $text, array( 'id' => 'ai.content.social-post', 'label' => __( 'Social post', 'davix-h2i' ), 'description' => __( 'Post text for a social network.', 'davix-h2i' ), 'release' => '2.2', 'fields' => array( 'text', 'platform', 'tone', 'hashtags', 'call_to_action', 'language' ) ) ) );
        $c->add_operation( array_merge( $text, array( 'id' => 'ai.content.product-description', 'label' => __( 'Product description', 'davix-h2i' ), 'description' => __( 'Product text from photos and notes.', 'davix-h2i' ), 'release' => '2.2', 'fields' => array( 'product_name', 'text', 'tone', 'max_length', 'language' ) ) ) );
        $c->add_operation( array_merge( $text, array( 'id' => 'ai.documents.translate', 'label' => __( 'Translate', 'davix-h2i' ), 'description' => __( 'Translate a text into another language.', 'davix-h2i' ), 'release' => '2.2', 'fields' => array( 'text', 'target_language', 'source_language' ) ) ) );
        $c->add_operation( array_merge( $text, array( 'id' => 'ai.documents.correct', 'label' => __( 'Fix grammar and spelling', 'davix-h2i' ), 'description' => __( 'Correct a text and list what changed.', 'davix-h2i' ), 'release' => '2.2', 'fields' => array( 'text', 'mode', 'language' ) ) ) );

        $gen = array( 'group' => 'ai', 'file_field' => 'files', 'multiple' => false, 'accepts' => array( 'image/' ), 'store' => 'new', 'result' => 'files', 'release' => '2.2', 'async' => true );
        $c->add_operation( array_merge( $gen, array( 'id' => 'ai.generation.generate', 'input' => 'optional', 'label' => __( 'Create an image', 'davix-h2i' ), 'description' => __( 'Describe it and get a new image.', 'davix-h2i' ), 'fields' => array( array( 'name' => 'instructions', 'label' => __( 'Describe the image', 'davix-h2i' ) ), 'aspect_ratio', 'size', 'output_format', 'style', 'negative_prompt', 'n' ) ) ) );
        $c->add_operation( array_merge( $gen, array( 'id' => 'ai.generation.background', 'input' => 'optional', 'label' => __( 'New background', 'davix-h2i' ), 'description' => __( 'Keep the subject, change the scene.', 'davix-h2i' ), 'fields' => array( array( 'name' => 'instructions', 'label' => __( 'Describe the new background', 'davix-h2i' ) ), 'keep_subject', 'aspect_ratio', 'output_format' ) ) ) );
        $c->add_operation( array_merge( $gen, array( 'id' => 'ai.generation.product', 'input' => 'file', 'label' => __( 'Product photo', 'davix-h2i' ), 'description' => __( 'Studio-style product photography.', 'davix-h2i' ), 'fields' => array( 'scene', 'background', 'aspect_ratio', 'output_format', 'instructions' ) ) ) );
        $c->add_operation( array_merge( $gen, array( 'id' => 'ai.generation.social', 'input' => 'none', 'accepts' => array(), 'label' => __( 'Social image', 'davix-h2i' ), 'description' => __( 'An image for a social network post.', 'davix-h2i' ), 'fields' => array( 'platform', 'headline', 'subheadline', 'text_position', 'text_color', 'instructions', 'output_format' ) ) ) );
        $c->add_operation( array_merge( $gen, array( 'id' => 'ai.generation.thumbnail', 'input' => 'none', 'accepts' => array(), 'label' => __( 'Thumbnail', 'davix-h2i' ), 'description' => __( 'A thumbnail with a title.', 'davix-h2i' ), 'fields' => array( 'title', 'subtitle', 'platform', 'text_position', 'text_color', 'instructions', 'output_format' ) ) ) );
        $c->add_operation( array_merge( $gen, array( 'id' => 'ai.enhancement.upscale', 'input' => 'file', 'label' => __( 'Upscale', 'davix-h2i' ), 'description' => __( 'Make an image larger and sharper.', 'davix-h2i' ), 'fields' => array( 'scale', 'strength', 'output_format' ) ) ) );
    }
}
