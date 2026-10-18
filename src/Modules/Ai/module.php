<?php
/**
 * AI: alt text, captions, tags, SEO, safety checks, summaries, translation, grammar and image creation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    'id'         => 'ai',
    'label'      => 'AI',
    'release'    => '2.1',
    'order'      => 35,
    'operations' => array(
        'ai.content.alt-text',
        'ai.content.caption',
        'ai.content.tags',
        'ai.content.seo',
        'ai.images.moderate',
        'ai.documents.summarize',
        'ai.content.title',
        'ai.content.social-post',
        'ai.content.product-description',
        'ai.documents.translate',
        'ai.documents.correct',
        'ai.generation.generate',
        'ai.generation.background',
        'ai.generation.product',
        'ai.generation.social',
        'ai.generation.thumbnail',
        'ai.enhancement.upscale',
    ),
);
