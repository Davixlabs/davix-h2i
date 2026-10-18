<?php
// GENERATED from the Davix H2I API contract 3.0.0. Do not edit.
namespace Davix\H2I\Api;

use Davix\H2I\Client;
use Davix\H2I\Response;

/** account */
final class ApiAccount
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/account/activity -- Everything that happened on the customer account, newest first: sign-ins (device, app, address), sign-outs, API key changes, profile changes, privacy requests.
     *
     * @param-field type string one of all|signin|keys|plan|profile|privacy
     * @param-field limit integer min 1 max 100
     * @param-field before integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('account.activity')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function activity(array $params = [], array $options = [])
    {
        return $this->client->call('account.activity', $params, $options);
    }

    /**
     * GET /v1/account/billing -- The customer's billing history and the plans they can move to, read from WordPress (Paid Memberships Pro) with the exact pages to land on.
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('account.billing')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function billing(array $params = [], array $options = [])
    {
        return $this->client->call('account.billing', $params, $options);
    }

    /**
     * GET /v1/account -- The authenticated customer's own real profile (Customer Portal, NEW): name/email/status/creation date from the customers table.
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('account')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('account', $params, $options);
    }

    /**
     * GET /v1/account/privacy -- The customer's open and past WordPress privacy requests (download my data, delete my account).
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('account.privacy')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function privacy(array $params = [], array $options = [])
    {
        return $this->client->call('account.privacy', $params, $options);
    }

    /**
     * POST /v1/account/privacy -- Open a WordPress privacy request: download my data, or delete my account. WordPress emails a confirmation link, then an administrator approves it.
     *
     * @param-field type (required) string one of export|erase
     * @param array $params Request parameters keyed by wire name (see $client->schema('account.privacy.request')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function requestPrivacy(array $params = [], array $options = [])
    {
        return $this->client->call('account.privacy.request', $params, $options);
    }

}

/** ai */
final class ApiAi
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function capabilities()
    {
        return new ApiAiCapabilities($this->client);
    }

    public function content()
    {
        return new ApiAiContent($this->client);
    }

    public function documents()
    {
        return new ApiAiDocuments($this->client);
    }

    public function enhancement()
    {
        return new ApiAiEnhancement($this->client);
    }

    public function extraction()
    {
        return new ApiAiExtraction($this->client);
    }

    public function generation()
    {
        return new ApiAiGeneration($this->client);
    }

    public function images()
    {
        return new ApiAiImages($this->client);
    }

    public function models()
    {
        return new ApiAiModels($this->client);
    }

    public function ocr()
    {
        return new ApiAiOcr($this->client);
    }

    /**
     * POST /v1/ai/analyze -- Analyze (generic task runner)
     *
     * @param-field task (required) string
     * @param-field files file
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.analyze')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function analyze(array $params = [], array $options = [])
    {
        return $this->client->call('ai.analyze', $params, $options);
    }

    /**
     * POST /v1/ai/chat -- Chat
     *
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field messages (required) array
     * @param-field generation object
     * @param-field presetId string
     * @param-field app string
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.chat')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function chat(array $params = [], array $options = [])
    {
        return $this->client->call('ai.chat', $params, $options);
    }

    /**
     * POST /v1/ai/generate -- Generate (image)
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions (required) string
     * @param-field n integer min 1 max 10
     * @param-field aspect_ratio string one of 1:1|16:9|9:16|4:3|3:4|3:2|2:3|21:9
     * @param-field size string one of 512|1K|2K
     * @param-field output_format string one of png|jpeg|webp
     * @param-field negative_prompt string
     * @param-field style string
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function generate(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generate', $params, $options);
    }

}

/** ai.capabilities */
final class ApiAiCapabilities
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/ai/capabilities/{feature} -- Feature capability
     *
     * @param-field feature (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.capabilities.{feature}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('ai.capabilities.{feature}', $params, $options);
    }

    /**
     * GET /v1/ai/capabilities -- AI capabilities
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.capabilities')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('ai.capabilities', $params, $options);
    }

}

/** ai.content */
final class ApiAiContent
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/ai/content/alt-text -- Image alt text
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field tone string one of neutral|friendly|professional|playful|persuasive
     * @param-field max_length integer min 20 max 250
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.content.alt-text')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function altText(array $params = [], array $options = [])
    {
        return $this->client->call('ai.content.alt-text', $params, $options);
    }

    /**
     * POST /v1/ai/content/caption -- Captions
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field tone string one of neutral|friendly|professional|playful|persuasive
     * @param-field max_length integer min 20 max 500
     * @param-field hashtags integer min 0 max 15
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.content.caption')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function caption(array $params = [], array $options = [])
    {
        return $this->client->call('ai.content.caption', $params, $options);
    }

    /**
     * POST /v1/ai/content/document-description -- Document description
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field tone string one of neutral|friendly|professional|playful|persuasive
     * @param-field max_length integer min 50 max 2000
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field output string one of separate|combined
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.content.document-description')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function documentDescription(array $params = [], array $options = [])
    {
        return $this->client->call('ai.content.document-description', $params, $options);
    }

    /**
     * POST /v1/ai/content/product-description -- Product description from images
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field tone string one of neutral|friendly|professional|playful|persuasive
     * @param-field max_length integer min 100 max 3000
     * @param-field product_name string
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.content.product-description')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function productDescription(array $params = [], array $options = [])
    {
        return $this->client->call('ai.content.product-description', $params, $options);
    }

    /**
     * POST /v1/ai/content/seo -- SEO metadata
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field tone string one of neutral|friendly|professional|playful|persuasive
     * @param-field target_keyword string
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.content.seo')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function seo(array $params = [], array $options = [])
    {
        return $this->client->call('ai.content.seo', $params, $options);
    }

    /**
     * POST /v1/ai/content/social-post -- Social post from media
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field tone string one of neutral|friendly|professional|playful|persuasive
     * @param-field platform string one of twitter|linkedin|facebook|instagram|threads|generic
     * @param-field hashtags integer min 0 max 15
     * @param-field call_to_action boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.content.social-post')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function socialPost(array $params = [], array $options = [])
    {
        return $this->client->call('ai.content.social-post', $params, $options);
    }

    /**
     * POST /v1/ai/content/tags -- Tags
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field tone string one of neutral|friendly|professional|playful|persuasive
     * @param-field max integer min 1 max 50
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.content.tags')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function tags(array $params = [], array $options = [])
    {
        return $this->client->call('ai.content.tags', $params, $options);
    }

    /**
     * POST /v1/ai/content/title -- Titles
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field tone string one of neutral|friendly|professional|playful|persuasive
     * @param-field max_length integer min 10 max 200
     * @param-field alternatives integer min 0 max 5
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.content.title')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function title(array $params = [], array $options = [])
    {
        return $this->client->call('ai.content.title', $params, $options);
    }

}

/** ai.documents */
final class ApiAiDocuments
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/ai/documents/action-items -- Extract action items
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field output string one of separate|combined
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.action-items')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function actionItems(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.action-items', $params, $options);
    }

    /**
     * POST /v1/ai/documents/analyze-contract -- Analyze a contract
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field perspective string
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.analyze-contract')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function analyzeContract(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.analyze-contract', $params, $options);
    }

    /**
     * POST /v1/ai/documents/analyze-form -- Analyze forms
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.analyze-form')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function analyzeForm(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.analyze-form', $params, $options);
    }

    /**
     * POST /v1/ai/documents/analyze-risks -- Detect risks and issues
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field domain string
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field output string one of separate|combined
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.analyze-risks')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function analyzeRisks(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.analyze-risks', $params, $options);
    }

    /**
     * POST /v1/ai/documents/analyze-table -- Analyze tables
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field table_hint string
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.analyze-table')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function analyzeTable(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.analyze-table', $params, $options);
    }

    /**
     * POST /v1/ai/documents/classify -- Classify documents
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field labels array
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.classify')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function classify(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.classify', $params, $options);
    }

    /**
     * POST /v1/ai/documents/correct -- Correct grammar and spelling
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field mode string one of grammar|spelling|both
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.correct')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function correct(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.correct', $params, $options);
    }

    /**
     * POST /v1/ai/documents/detect-type -- Detect document type
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.detect-type')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function detectType(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.detect-type', $params, $options);
    }

    /**
     * POST /v1/ai/documents/extract -- Extract key information
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field fields array
     * @param-field include array
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.extract')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function extract(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.extract', $params, $options);
    }

    /**
     * POST /v1/ai/documents/keywords -- Generate keywords
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field max integer min 1 max 50
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.keywords')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function keywords(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.keywords', $params, $options);
    }

    /**
     * POST /v1/ai/documents/metadata -- Generate metadata
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.metadata')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function metadata(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.metadata', $params, $options);
    }

    /**
     * POST /v1/ai/documents/qa -- Document question answering
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field question (required) string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field answer_style string one of concise|detailed
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field output string one of separate|combined
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.qa')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function qa(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.qa', $params, $options);
    }

    /**
     * POST /v1/ai/documents/rewrite -- Rewrite documents
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field style string one of formal|casual|concise|friendly|academic|marketing|simplified
     * @param-field target_length string one of shorter|same|longer
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.rewrite')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function rewrite(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.rewrite', $params, $options);
    }

    /**
     * POST /v1/ai/documents/summarize -- Summarize documents
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field length string one of short|medium|long
     * @param-field style string one of paragraph|bullets
     * @param-field focus string
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field output string one of separate|combined
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.summarize')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function summarize(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.summarize', $params, $options);
    }

    /**
     * POST /v1/ai/documents/title -- Generate a title
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field max_length integer min 10 max 200
     * @param-field alternatives integer min 0 max 5
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field output string one of separate|combined
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.title')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function title(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.title', $params, $options);
    }

    /**
     * POST /v1/ai/documents/translate -- Translate documents
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field target_language (required) string
     * @param-field source_language string
     * @param-field glossary array
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.documents.translate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function translate(array $params = [], array $options = [])
    {
        return $this->client->call('ai.documents.translate', $params, $options);
    }

}

/** ai.enhancement */
final class ApiAiEnhancement
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/ai/enhancement/colorize -- AI colorization
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field strength string one of low|medium|high
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.enhancement.colorize')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function colorize(array $params = [], array $options = [])
    {
        return $this->client->call('ai.enhancement.colorize', $params, $options);
    }

    /**
     * POST /v1/ai/enhancement/deblur -- AI deblurring
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field strength string one of low|medium|high
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.enhancement.deblur')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function deblur(array $params = [], array $options = [])
    {
        return $this->client->call('ai.enhancement.deblur', $params, $options);
    }

    /**
     * POST /v1/ai/enhancement/denoise -- AI denoising
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field strength string one of low|medium|high
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.enhancement.denoise')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function denoise(array $params = [], array $options = [])
    {
        return $this->client->call('ai.enhancement.denoise', $params, $options);
    }

    /**
     * POST /v1/ai/enhancement/face -- Face restoration
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field strength string one of low|medium|high
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.enhancement.face')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function face(array $params = [], array $options = [])
    {
        return $this->client->call('ai.enhancement.face', $params, $options);
    }

    /**
     * POST /v1/ai/enhancement/restore -- AI restoration
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field strength string one of low|medium|high
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.enhancement.restore')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function restore(array $params = [], array $options = [])
    {
        return $this->client->call('ai.enhancement.restore', $params, $options);
    }

    /**
     * POST /v1/ai/enhancement/upscale -- AI upscaling
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field scale number one of 2|4
     * @param-field strength string one of low|medium|high
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.enhancement.upscale')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function upscale(array $params = [], array $options = [])
    {
        return $this->client->call('ai.enhancement.upscale', $params, $options);
    }

}

/** ai.extraction */
final class ApiAiExtraction
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/ai/extraction/bank-statement -- Extract a bank statement
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.extraction.bank-statement')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function bankStatement(array $params = [], array $options = [])
    {
        return $this->client->call('ai.extraction.bank-statement', $params, $options);
    }

    /**
     * POST /v1/ai/extraction/business-card -- Extract a business card
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.extraction.business-card')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function businessCard(array $params = [], array $options = [])
    {
        return $this->client->call('ai.extraction.business-card', $params, $options);
    }

    /**
     * POST /v1/ai/extraction/custom -- Extract with your own JSON Schema
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field json_schema (required) string
     * @param-field strict boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.extraction.custom')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function custom(array $params = [], array $options = [])
    {
        return $this->client->call('ai.extraction.custom', $params, $options);
    }

    /**
     * POST /v1/ai/extraction/id-document -- Extract an ID document
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.extraction.id-document')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function idDocument(array $params = [], array $options = [])
    {
        return $this->client->call('ai.extraction.id-document', $params, $options);
    }

    /**
     * POST /v1/ai/extraction/invoice -- Extract an invoice
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.extraction.invoice')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function invoice(array $params = [], array $options = [])
    {
        return $this->client->call('ai.extraction.invoice', $params, $options);
    }

    /**
     * POST /v1/ai/extraction/purchase-order -- Extract a purchase order
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.extraction.purchase-order')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function purchaseOrder(array $params = [], array $options = [])
    {
        return $this->client->call('ai.extraction.purchase-order', $params, $options);
    }

    /**
     * POST /v1/ai/extraction/receipt -- Extract a receipt
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.extraction.receipt')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function receipt(array $params = [], array $options = [])
    {
        return $this->client->call('ai.extraction.receipt', $params, $options);
    }

    /**
     * POST /v1/ai/extraction/resume -- Extract a resume/CV
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.extraction.resume')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function resume(array $params = [], array $options = [])
    {
        return $this->client->call('ai.extraction.resume', $params, $options);
    }

}

/** ai.generation */
final class ApiAiGeneration
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/ai/generation/background -- Background generation
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions (required) string
     * @param-field n integer min 1 max 10
     * @param-field aspect_ratio string one of 1:1|16:9|9:16|4:3|3:4|3:2|2:3|21:9
     * @param-field size string one of 512|1K|2K
     * @param-field output_format string one of png|jpeg|webp
     * @param-field keep_subject boolean
     * @param-field style string
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.background')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function background(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.background', $params, $options);
    }

    /**
     * POST /v1/ai/generation/edit -- AI image editing
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions (required) string
     * @param-field mode string one of edit|replace-background|remove-object|replace-object|generative-fill
     * @param-field target string
     * @param-field region object
     * @param-field n integer min 1 max 4
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.edit')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function edit(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.edit', $params, $options);
    }

    /**
     * POST /v1/ai/generation/expand -- Outpainting / image expansion
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field top integer min 0 max 2048
     * @param-field right integer min 0 max 2048
     * @param-field bottom integer min 0 max 2048
     * @param-field left integer min 0 max 2048
     * @param-field target_aspect_ratio string one of 1:1|16:9|9:16|4:3|3:4|3:2|2:3|21:9
     * @param-field n integer min 1 max 4
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.expand')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function expand(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.expand', $params, $options);
    }

    /**
     * POST /v1/ai/generation/generate -- Text to image
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions (required) string
     * @param-field n integer min 1 max 10
     * @param-field aspect_ratio string one of 1:1|16:9|9:16|4:3|3:4|3:2|2:3|21:9
     * @param-field size string one of 512|1K|2K
     * @param-field output_format string one of png|jpeg|webp
     * @param-field negative_prompt string
     * @param-field style string
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.generate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function generate(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.generate', $params, $options);
    }

    /**
     * POST /v1/ai/generation/poster -- Poster / banner generation
     *
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field title (required) string
     * @param-field subtitle string
     * @param-field details array
     * @param-field size string one of a4|a3|letter|tabloid|custom
     * @param-field width integer min 256 max 4096
     * @param-field height integer min 256 max 4096
     * @param-field orientation string one of portrait|landscape
     * @param-field text_color string
     * @param-field n integer min 1 max 4
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.poster')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function poster(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.poster', $params, $options);
    }

    /**
     * POST /v1/ai/generation/product -- AI product photography
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field n integer min 1 max 10
     * @param-field aspect_ratio string one of 1:1|16:9|9:16|4:3|3:4|3:2|2:3|21:9
     * @param-field size string one of 512|1K|2K
     * @param-field output_format string one of png|jpeg|webp
     * @param-field scene string
     * @param-field background string one of studio-white|gradient|lifestyle|custom
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.product')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function product(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.product', $params, $options);
    }

    /**
     * POST /v1/ai/generation/remove-background -- AI background removal
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field output string one of cutout|mask
     * @param-field tolerance number min 1 max 100
     * @param-field feather number min 0 max 20
     * @param-field shrink integer min 0 max 10
     * @param-field background_color string
     * @param-field trim boolean
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.remove-background')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function removeBackground(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.remove-background', $params, $options);
    }

    /**
     * POST /v1/ai/generation/social -- Social media image
     *
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field platform string one of instagram-square|instagram-story|twitter|facebook|linkedin|pinterest
     * @param-field headline string
     * @param-field subheadline string
     * @param-field text_position string one of top|center|bottom
     * @param-field text_color string
     * @param-field n integer min 1 max 4
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.social')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function social(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.social', $params, $options);
    }

    /**
     * POST /v1/ai/generation/thumbnail -- Thumbnail generation
     *
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field title (required) string
     * @param-field subtitle string
     * @param-field platform string one of youtube|blog|generic
     * @param-field text_position string one of top|center|bottom
     * @param-field text_color string
     * @param-field n integer min 1 max 4
     * @param-field output_format string one of png|jpeg|webp
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.thumbnail')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function thumbnail(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.thumbnail', $params, $options);
    }

    /**
     * POST /v1/ai/generation/variations -- Image variations
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field instructions string
     * @param-field n integer min 1 max 10
     * @param-field aspect_ratio string one of 1:1|16:9|9:16|4:3|3:4|3:2|2:3|21:9
     * @param-field size string one of 512|1K|2K
     * @param-field output_format string one of png|jpeg|webp
     * @param-field strength string one of low|medium|high
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field seed integer min 0 max 2147483647
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.generation.variations')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function variations(array $params = [], array $options = [])
    {
        return $this->client->call('ai.generation.variations', $params, $options);
    }

}

/** ai.images */
final class ApiAiImages
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/ai/images/caption -- Caption an image
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field max_length integer min 20 max 500
     * @param-field tone string one of neutral|friendly|professional|playful
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.caption')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function caption(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.caption', $params, $options);
    }

    /**
     * POST /v1/ai/images/classify -- Classify an image
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field labels array
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.classify')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function classify(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.classify', $params, $options);
    }

    /**
     * POST /v1/ai/images/describe -- Describe an image
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field detail string one of brief|standard|detailed
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.describe')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function describe(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.describe', $params, $options);
    }

    /**
     * POST /v1/ai/images/extract -- Image to JSON
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field fields array
     * @param-field include array
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.extract')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function extract(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.extract', $params, $options);
    }

    /**
     * POST /v1/ai/images/moderate -- Moderate an image
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field categories array
     * @param-field strictness string one of low|medium|high
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.moderate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function moderate(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.moderate', $params, $options);
    }

    /**
     * POST /v1/ai/images/objects -- Identify objects
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field max integer min 1 max 100
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.objects')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function objects(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.objects', $params, $options);
    }

    /**
     * POST /v1/ai/images/qa -- Visual question answering
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field question (required) string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field answer_style string one of concise|detailed
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.qa')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function qa(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.qa', $params, $options);
    }

    /**
     * POST /v1/ai/images/scene -- Understand a scene
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.scene')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function scene(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.scene', $params, $options);
    }

    /**
     * POST /v1/ai/images/tags -- Tag an image
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field max integer min 1 max 50
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.tags')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function tags(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.tags', $params, $options);
    }

    /**
     * POST /v1/ai/images/translate -- Translate text in an image
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field target_language (required) string
     * @param-field source_language string
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.images.translate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function translate(array $params = [], array $options = [])
    {
        return $this->client->call('ai.images.translate', $params, $options);
    }

}

/** ai.models */
final class ApiAiModels
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/ai/models/{model} -- Model detail
     *
     * @param-field model (required) string
     * @param-field include string one of endpoints
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.models.{model}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('ai.models.{model}', $params, $options);
    }

    /**
     * GET /v1/ai/models -- List models
     *
     * @param-field input_modality string
     * @param-field output_modality string one of text|image|audio
     * @param-field supports string
     * @param-field structured_output boolean
     * @param-field task string
     * @param-field q string
     * @param-field limit integer min 1 max 500
     * @param-field offset integer min 0 max 100000
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.models')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('ai.models', $params, $options);
    }

}

/** ai.ocr */
final class ApiAiOcr
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/ai/ocr/chart -- Chart understanding
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.chart')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function chart(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.chart', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/correct -- OCR correction
     *
     * @param-field files file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field text string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field mode string one of grammar|spelling|both
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.correct')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function correct(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.correct', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/detect-language -- Detect the language of an image or scan
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.detect-language')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function detectLanguage(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.detect-language', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/extract -- Image to structured JSON
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field fields array
     * @param-field include array
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.extract')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function extract(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.extract', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/handwriting -- Handwriting interpretation
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.handwriting')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function handwriting(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.handwriting', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/layout -- Document layout understanding
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.layout')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function layout(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.layout', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/qa -- Visual question answering (documents)
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field question (required) string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field answer_style string one of concise|detailed
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.qa')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function qa(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.qa', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/screenshot -- Screenshot understanding
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.screenshot')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function screenshot(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.screenshot', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/text -- Image to text (OCR)
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field min_confidence number min 0 max 100
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.text')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function text(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.text', $params, $options);
    }

    /**
     * POST /v1/ai/ocr/understand -- Document text understanding
     *
     * @param-field files (required) file
     * @param-field model string
     * @param-field on_unsupported string one of error|ignore
     * @param-field fallback boolean
     * @param-field generation string
     * @param-field instructions string
     * @param-field language string
     * @param-field quality string one of standard|high
     * @param-field strategy string one of auto|local|vision
     * @param-field pages string
     * @param-field grounding string one of strict|flag|off
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('ai.ocr.understand')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function understand(array $params = [], array $options = [])
    {
        return $this->client->call('ai.ocr.understand', $params, $options);
    }

}

/** certificates */
final class ApiCertificates
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/certificates -- Store a signing certificate
     *
     * @param-field name (required) string
     * @param-field certPem (required) string
     * @param-field keyPem (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('certificates.post')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function create(array $params = [], array $options = [])
    {
        return $this->client->call('certificates.post', $params, $options);
    }

    /**
     * DELETE /v1/certificates/{id} -- Delete a stored certificate
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('certificates.{id}.delete')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function delete(array $params = [], array $options = [])
    {
        return $this->client->call('certificates.{id}.delete', $params, $options);
    }

    /**
     * GET /v1/certificates/{id} -- One stored certificate
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('certificates.{id}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('certificates.{id}', $params, $options);
    }

    /**
     * GET /v1/certificates -- List stored certificates
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('certificates')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('certificates', $params, $options);
    }

}

/** data */
final class ApiData
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/data/convert -- Convert between structured-data formats
     *
     * @param-field file (required) file
     * @param-field to (required) string one of xlsx|ods|md|html|xls|csv|json|xml|tsv|ndjson|yaml|toml|sql
     * @param-field sheet string
     * @param-field delimiter string
     * @param-field encoding string
     * @param-field header boolean
     * @param-field flatten boolean
     * @param-field pretty boolean
     * @param-field dateFormat string
     * @param-field infer_types boolean
     * @param-field columns string
     * @param-field filter string
     * @param-field sort string
     * @param-field limit integer min 0 max 200000
     * @param-field formula_safe boolean
     * @param-field rootName string
     * @param-field rowName string
     * @param-field table string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('data.convert')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function convert(array $params = [], array $options = [])
    {
        return $this->client->call('data.convert', $params, $options);
    }

    /**
     * POST /v1/data/diff -- Compare two data files
     *
     * @param-field file (required) file
     * @param-field other (required) file
     * @param-field key string
     * @param-field sheet string
     * @param-field flatten boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field file_url string
     * @param-field other_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('data.diff')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function diff(array $params = [], array $options = [])
    {
        return $this->client->call('data.diff', $params, $options);
    }

    /**
     * POST /v1/data/merge -- Merge several data files into one
     *
     * @param-field files (required) file
     * @param-field to string one of xlsx|ods|md|html|xls|csv|json|xml|tsv|ndjson|yaml|toml|sql
     * @param-field mode string one of append|join
     * @param-field on string
     * @param-field join_type string one of inner|left|right|outer
     * @param-field source_column string
     * @param-field sheet string
     * @param-field delimiter string
     * @param-field encoding string
     * @param-field header boolean
     * @param-field flatten boolean
     * @param-field pretty boolean
     * @param-field dateFormat string
     * @param-field infer_types boolean
     * @param-field formula_safe boolean
     * @param-field rootName string
     * @param-field rowName string
     * @param-field table string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('data.merge')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function merge(array $params = [], array $options = [])
    {
        return $this->client->call('data.merge', $params, $options);
    }

    /**
     * POST /v1/data/transform -- Transform a data file with a list of operations
     *
     * @param-field file (required) file
     * @param-field to string one of xlsx|ods|md|html|xls|csv|json|xml|tsv|ndjson|yaml|toml|sql
     * @param-field operations array
     * @param-field sheet string
     * @param-field delimiter string
     * @param-field encoding string
     * @param-field header boolean
     * @param-field flatten boolean
     * @param-field pretty boolean
     * @param-field dateFormat string
     * @param-field infer_types boolean
     * @param-field columns string
     * @param-field filter string
     * @param-field sort string
     * @param-field limit integer min 0 max 200000
     * @param-field formula_safe boolean
     * @param-field rootName string
     * @param-field rowName string
     * @param-field table string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('data.transform')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function transform(array $params = [], array $options = [])
    {
        return $this->client->call('data.transform', $params, $options);
    }

    /**
     * POST /v1/data/validate -- Validate a data file against a JSON Schema
     *
     * @param-field file (required) file
     * @param-field schema (required) string
     * @param-field target string one of auto|rows|document
     * @param-field sheet string
     * @param-field dateFormat string
     * @param-field header boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('data.validate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function validate(array $params = [], array $options = [])
    {
        return $this->client->call('data.validate', $params, $options);
    }

}

/** documents */
final class ApiDocuments
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/documents/convert -- Convert between PDF and Office/ODF/RTF/EPUB/HTML/TXT/Markdown (LibreOffice in a namespace sandbox; feature-expansion phase, NEW)
     *
     * @param-field file (required) file
     * @param-field to (required) string one of jpeg|png|svg|pdf|pdfa|docx|xlsx|pptx|odt|ods|odp|rtf|epub|txt|md|html|doc|xls|ppt|pps|ppsx|csv
     * @param-field pdfaLevel string one of 1b|2b|3b
     * @param-field optimize boolean
     * @param-field password string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('documents.convert')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function convert(array $params = [], array $options = [])
    {
        return $this->client->call('documents.convert', $params, $options);
    }

    /**
     * POST /v1/documents/mail-merge -- Fill a DOCX / ODT template with data (mail merge)
     *
     * @param-field file file
     * @param-field templateId string
     * @param-field data_file file
     * @param-field records array
     * @param-field data object
     * @param-field to string one of docx|odt|pdf
     * @param-field strict boolean
     * @param-field password string
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('documents.mail-merge')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function mailMerge(array $params = [], array $options = [])
    {
        return $this->client->call('documents.mail-merge', $params, $options);
    }

    /**
     * POST /v1/documents/metadata -- Read, edit or remove the metadata of Word, Excel, PowerPoint and OpenDocument files
     *
     * @param-field file (required) file
     * @param-field mode string one of read|merge|replace|strip|keep
     * @param-field clean string one of all|location|personal
     * @param-field remove array
     * @param-field remove_groups array
     * @param-field set object
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('documents.metadata')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function metadata(array $params = [], array $options = [])
    {
        return $this->client->call('documents.metadata', $params, $options);
    }

    /**
     * POST /v1/documents/preview -- Page thumbnails of a document
     *
     * @param-field file (required) file
     * @param-field password string
     * @param-field pages string
     * @param-field width integer min 1
     * @param-field format string one of png|jpeg|webp
     * @param-field dpi integer min 1
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('documents.preview')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function preview(array $params = [], array $options = [])
    {
        return $this->client->call('documents.preview', $params, $options);
    }

    /**
     * POST /v1/documents/text -- Extract the text of a document
     *
     * @param-field file (required) file
     * @param-field password string
     * @param-field format string one of txt|md|json
     * @param-field output string one of inline|url
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('documents.text')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function text(array $params = [], array $options = [])
    {
        return $this->client->call('documents.text', $params, $options);
    }

}

/** files */
final class ApiFiles
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * DELETE /v1/files/{id} -- Delete one of the customer's processed files
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('files.{id}.delete')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function delete(array $params = [], array $options = [])
    {
        return $this->client->call('files.{id}.delete', $params, $options);
    }

    /**
     * POST /v1/files/fetch-url -- Fetch a public web address and return the file
     *
     * @param-field url (required) string
     * @param-field operation (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('files.fetch-url')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function fetchUrl(array $params = [], array $options = [])
    {
        return $this->client->call('files.fetch-url', $params, $options);
    }

    /**
     * GET /v1/files/{id} -- One of the customer's processed files
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('files.{id}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('files.{id}', $params, $options);
    }

    /**
     * GET /v1/files -- The authenticated customer's processed-file history (Phase 2 execution-platform). Advanced server-side filtering + pagination added in the Customer Portal History pass (2026-09-28).
     *
     * @param-field page integer min 1
     * @param-field limit integer min 1 max 200
     * @param-field status string one of available|expired|all
     * @param-field category string one of image|pdf|render|other
     * @param-field operation string
     * @param-field app string
     * @param-field from string
     * @param-field to string
     * @param-field search string
     * @param-field sort string one of created_at_desc|created_at_asc|size_desc|size_asc
     * @param-field job_id integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('files')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('files', $params, $options);
    }

}

/** formats */
final class ApiFormats
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/formats -- The file formats the API reads and writes, and which endpoint takes each
     *
     * @param-field family string one of image|document|data
     * @param-field direction string one of input|output
     * @param-field endpoint string
     * @param-field status string one of supported|planned|all
     * @param array $params Request parameters keyed by wire name (see $client->schema('formats')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('formats', $params, $options);
    }

}

/** images */
final class ApiImages
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/images/animate -- Build an animated GIF from still frames
     *
     * @param-field images (required) file
     * @param-field delayMs integer min 10 max 60000
     * @param-field loopCount integer min 0
     * @param-field format string one of gif
     * @param-field colorDepth integer min 2 max 8
     * @param-field frameOrder array
     * @param-field keepEveryNthFrame integer min 1
     * @param-field reverse boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.animate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function animate(array $params = [], array $options = [])
    {
        return $this->client->call('images.animate', $params, $options);
    }

    /**
     * POST /v1/images/auto-optimize -- Automatically select the best format/quality
     *
     * @param-field images (required) file
     * @param-field maxSizeKB integer min 1
     * @param-field colorSpace string one of srgb|grayscale|cmyk
     * @param-field keepMetadata boolean
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.auto-optimize')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function autoOptimize(array $params = [], array $options = [])
    {
        return $this->client->call('images.auto-optimize', $params, $options);
    }

    /**
     * POST /v1/images/barcode -- Generate a barcode
     *
     * @param-field symbology (required) string one of code128|code39|ean13|ean8|upca|itf|codabar|datamatrix|pdf417|aztec|qrcode
     * @param-field text (required) string
     * @param-field file_type string one of png|svg|pdf
     * @param-field color string
     * @param-field background string
     * @param-field scale integer min 1 max 10
     * @param-field height integer min 3 max 200
     * @param-field include_text boolean
     * @param-field padding integer min 0 max 100
     * @param-field rotate string one of N|R|L|I
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.barcode')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function barcode(array $params = [], array $options = [])
    {
        return $this->client->call('images.barcode', $params, $options);
    }

    /**
     * POST /v1/images/compare -- Compare two images
     *
     * @param-field imageA (required) file
     * @param-field imageB (required) file
     * @param-field metrics string
     * @param-field threshold number min 0 max 1
     * @param-field include_aa boolean
     * @param-field alpha number min 0 max 1
     * @param-field diff_color string
     * @param-field max_diff_ratio number min 0 max 1
     * @param-field resize_to string one of a|b|none
     * @param-field region object
     * @param-field grid string
     * @param-field diff_image boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field imageA_url string
     * @param-field imageB_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.compare')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function compare(array $params = [], array $options = [])
    {
        return $this->client->call('images.compare', $params, $options);
    }

    /**
     * POST /v1/images/composite -- Composite multiple image/text/color layers onto a base image
     *
     * @param-field images (required) file
     * @param-field layers (required) array
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field background string
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.composite')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function composite(array $params = [], array $options = [])
    {
        return $this->client->call('images.composite', $params, $options);
    }

    /**
     * POST /v1/images/contact-sheet -- Build a photography-style contact sheet of thumbnails (feature-expansion phase, NEW)
     *
     * @param-field images (required) file
     * @param-field cols integer min 1
     * @param-field thumbSize integer min 1
     * @param-field spacing integer min 0 max 500
     * @param-field background string
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field labels string one of none|filename|index
     * @param-field labelFontFamily string
     * @param-field labelFontSize integer min 6 max 100
     * @param-field labelColor string
     * @param-field padding integer min 0 max 500
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.contact-sheet')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contactSheet(array $params = [], array $options = [])
    {
        return $this->client->call('images.contact-sheet', $params, $options);
    }

    /**
     * POST /v1/images/convert -- Convert an image to another format
     *
     * @param-field images (required) file
     * @param-field output_format (required) string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field page integer min 1
     * @param-field pages string
     * @param-field animated boolean
     * @param-field ico_sizes string
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field background string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.convert')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function convert(array $params = [], array $options = [])
    {
        return $this->client->call('images.convert', $params, $options);
    }

    /**
     * POST /v1/images/decode-codes -- Read QR codes and barcodes from images
     *
     * @param-field images (required) file
     * @param-field formats array
     * @param-field try_harder boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.decode-codes')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function decodeCodes(array $params = [], array $options = [])
    {
        return $this->client->call('images.decode-codes', $params, $options);
    }

    /**
     * POST /v1/images/favicon -- Generate a favicon and app-icon pack
     *
     * @param-field images (required) file
     * @param-field include string
     * @param-field sizes string
     * @param-field background string
     * @param-field padding integer min 0 max 40
     * @param-field app_name string
     * @param-field short_name string
     * @param-field theme_color string
     * @param-field background_color string
     * @param-field start_url string
     * @param-field display string one of standalone|minimal-ui|fullscreen|browser
     * @param-field path_prefix string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.favicon')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function favicon(array $params = [], array $options = [])
    {
        return $this->client->call('images.favicon', $params, $options);
    }

    /**
     * POST /v1/images/frames -- Extract individual frames/pages from an animated GIF/WebP or multi-page TIFF (feature-expansion phase, NEW)
     *
     * @param-field images (required) file
     * @param-field frames string
     * @param-field format string one of png|jpeg|webp
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.frames')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function frames(array $params = [], array $options = [])
    {
        return $this->client->call('images.frames', $params, $options);
    }

    /**
     * POST /v1/images/grid -- Arrange multiple images into a grid layout (feature-expansion phase, NEW)
     *
     * @param-field images (required) file
     * @param-field cols integer min 1
     * @param-field cellWidth integer min 1
     * @param-field cellHeight integer min 1
     * @param-field spacing integer min 0 max 500
     * @param-field background string
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field labels string one of none|filename|index
     * @param-field labelFontFamily string
     * @param-field labelFontSize integer min 6 max 100
     * @param-field labelColor string
     * @param-field padding integer min 0 max 500
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.grid')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function grid(array $params = [], array $options = [])
    {
        return $this->client->call('images.grid', $params, $options);
    }

    /**
     * POST /v1/images/inspect -- Heavier, multi-check image analysis
     *
     * @param-field images (required) file
     * @param-field checks (required) array
     * @param-field options object
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.inspect')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function inspect(array $params = [], array $options = [])
    {
        return $this->client->call('images.inspect', $params, $options);
    }

    /**
     * POST /v1/images/metadata -- Read, edit or remove the metadata of images (EXIF, GPS, IPTC, XMP, maker notes ...)
     *
     * @param-field images (required) file
     * @param-field mode string one of read|merge|replace|strip|keep
     * @param-field clean string one of all|location|personal
     * @param-field remove array
     * @param-field remove_groups array
     * @param-field set object
     * @param-field includeRawExif boolean
     * @param-field normalizeOrientation boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.metadata')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function metadata(array $params = [], array $options = [])
    {
        return $this->client->call('images.metadata', $params, $options);
    }

    /**
     * POST /v1/images/ocr -- OCR an image to text (Tesseract; feature-expansion phase, NEW)
     *
     * @param-field images (required) file
     * @param-field languages string
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field clean boolean
     * @param-field output string one of text|pdf|json|tsv|hocr|alto|csv|xlsx
     * @param-field psm integer min 0 max 13
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field tables boolean
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.ocr')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function ocr(array $params = [], array $options = [])
    {
        return $this->client->call('images.ocr', $params, $options);
    }

    /**
     * POST /v1/images/optimize -- Re-encode an image for size/format
     *
     * @param-field images (required) file
     * @param-field format string one of jpeg|png|webp|avif|gif|tiff
     * @param-field quality integer min 1 max 100
     * @param-field targetSizeKB integer min 1
     * @param-field colorSpace string one of srgb|grayscale|cmyk
     * @param-field keepMetadata boolean
     * @param-field progressive boolean
     * @param-field effort integer
     * @param-field chromaSubsampling string one of 4:4:4|4:2:0
     * @param-field lossless boolean
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field maxWidth integer min 1 max 100000
     * @param-field maxHeight integer min 1 max 100000
     * @param-field palette boolean
     * @param-field paletteColours integer min 2 max 256
     * @param-field bitdepth integer min 1 max 16
     * @param-field animated boolean
     * @param-field mozjpeg boolean
     * @param-field smartSubsample boolean
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.optimize')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function optimize(array $params = [], array $options = [])
    {
        return $this->client->call('images.optimize', $params, $options);
    }

    /**
     * POST /v1/images/pdf -- Convert image(s) to a PDF
     *
     * @param-field images (required) file
     * @param-field mode string one of perImage|combined
     * @param-field pageSize string one of a4|letter|auto
     * @param-field orientation string one of portrait|landscape
     * @param-field margin number min 0 max 5000
     * @param-field embedFormat string one of jpeg|jpg|png
     * @param-field embedQuality integer min 20 max 100
     * @param-field imageOptions array
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.pdf')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function pdf(array $params = [], array $options = [])
    {
        return $this->client->call('images.pdf', $params, $options);
    }

    /**
     * POST /v1/images/pipeline -- Run an ordered chain of image operations
     *
     * @param-field images (required) file
     * @param-field operations array
     * @param-field watermarkImage file
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.pipeline')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function pipeline(array $params = [], array $options = [])
    {
        return $this->client->call('images.pipeline', $params, $options);
    }

    /**
     * POST /v1/images/qr -- Generate a QR code
     *
     * @param-field logo file
     * @param-field text (required) string
     * @param-field file_type string one of png|svg|pdf
     * @param-field color string
     * @param-field background string
     * @param-field size integer min 64 max 4096
     * @param-field margin integer min 0 max 16
     * @param-field error_correction string one of L|M|Q|H
     * @param-field transparent boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.qr')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function qr(array $params = [], array $options = [])
    {
        return $this->client->call('images.qr', $params, $options);
    }

    /**
     * POST /v1/images/remove-background -- Remove a plain background
     *
     * @param-field images (required) file
     * @param-field background_color string
     * @param-field tolerance integer min 0 max 100
     * @param-field mode string one of edges|all
     * @param-field feather number min 0 max 20
     * @param-field shrink integer min 0 max 20
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field background string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.remove-background')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function removeBackground(array $params = [], array $options = [])
    {
        return $this->client->call('images.remove-background', $params, $options);
    }

    /**
     * POST /v1/images/resize -- Resize an image
     *
     * @param-field images (required) file
     * @param-field width integer min 1
     * @param-field height integer min 1
     * @param-field resizeMode string one of fit|cover|pad|force
     * @param-field gravity string one of center|north|northeast|east|southeast|south|southwest|west|northwest|entropy|attention
     * @param-field enlarge boolean
     * @param-field trim boolean
     * @param-field trimThreshold integer min 0 max 255
     * @param-field aspectRatio string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field kernel string one of nearest|linear|cubic|mitchell|lanczos2|lanczos3|mks2013|mks2021
     * @param-field focalX number min 0 max 1
     * @param-field focalY number min 0 max 1
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field background string
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.resize')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function resize(array $params = [], array $options = [])
    {
        return $this->client->call('images.resize', $params, $options);
    }

    /**
     * POST /v1/images/slice -- Cut one image into a cols x rows grid of separate output images (feature-expansion phase, NEW)
     *
     * @param-field images (required) file
     * @param-field cols (required) integer min 1
     * @param-field rows (required) integer min 1
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field background string
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.slice')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function slice(array $params = [], array $options = [])
    {
        return $this->client->call('images.slice', $params, $options);
    }

    /**
     * POST /v1/images/sprite-sheet -- Pack multiple images edge-to-edge into one sprite sheet, with a per-image coordinate manifest (feature-expansion phase, NEW)
     *
     * @param-field images (required) file
     * @param-field cols integer min 1
     * @param-field cellWidth integer min 1
     * @param-field cellHeight integer min 1
     * @param-field background string
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field labels string one of none|filename|index
     * @param-field labelFontFamily string
     * @param-field labelFontSize integer min 6 max 100
     * @param-field labelColor string
     * @param-field padding integer min 0 max 500
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.sprite-sheet')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function spriteSheet(array $params = [], array $options = [])
    {
        return $this->client->call('images.sprite-sheet', $params, $options);
    }

    /**
     * POST /v1/images/transform -- Crop/resize/rotate/flip/enhance in one call
     *
     * @param-field images (required) file
     * @param-field crop.x integer
     * @param-field crop.y integer
     * @param-field crop.width integer
     * @param-field crop.height integer
     * @param-field resize.width integer min 1
     * @param-field resize.height integer min 1
     * @param-field resize.fit string one of fit|cover|pad|force
     * @param-field resize.enlarge boolean
     * @param-field resize.gravity string one of center|north|northeast|east|southeast|south|southwest|west|northwest|entropy|attention
     * @param-field rotate.angle integer
     * @param-field flip.axis string one of horizontal|vertical
     * @param-field enhance.blur number min 0 max 500
     * @param-field enhance.sharpen number min 0 max 10
     * @param-field enhance.grayscale boolean
     * @param-field enhance.sepia boolean
     * @param-field enhance.brightness number min 0 max 2
     * @param-field enhance.saturation number min 0 max 2
     * @param-field enhance.contrast number min 0 max 2
     * @param-field enhance.gamma number min 1 max 3
     * @param-field enhance.threshold integer min 0 max 255
     * @param-field enhance.tint string
     * @param-field enhance.negate boolean
     * @param-field enhance.median integer min 1 max 49
     * @param-field enhance.clahe object
     * @param-field enhance.hue number min -360 max 360
     * @param-field enhance.exposure number min -3 max 3
     * @param-field enhance.emboss boolean
     * @param-field enhance.edgeDetect boolean
     * @param-field enhance.noise number max 100
     * @param-field enhance.vignette number max 1
     * @param-field enhance.vignetteColor string
     * @param-field enhance.pixelate integer min 2 max 500
     * @param-field enhance.posterize integer min 2 max 32
     * @param-field enhance.duotoneShadow string
     * @param-field enhance.duotoneHighlight string
     * @param-field colorSpace string one of srgb|grayscale|cmyk
     * @param-field convolve object
     * @param-field affine object
     * @param-field channelExtract string one of red|green|blue|alpha
     * @param-field background.color string
     * @param-field background.blur number min 0 max 200
     * @param-field padding.top integer min 0
     * @param-field padding.right integer min 0
     * @param-field padding.bottom integer min 0
     * @param-field padding.left integer min 0
     * @param-field padding.all integer min 0
     * @param-field padding.color string
     * @param-field border.width integer min 0
     * @param-field border.color string
     * @param-field border.radius integer min 0
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field resize.kernel string one of nearest|linear|cubic|mitchell|lanczos2|lanczos3|mks2013|mks2021
     * @param-field resize.focalX number min 0 max 1
     * @param-field resize.focalY number min 0 max 1
     * @param-field levels.inputBlack integer min 0 max 254
     * @param-field levels.inputWhite integer min 1 max 255
     * @param-field levels.gamma number min 0.1 max 10
     * @param-field levels.outputBlack integer min 0 max 255
     * @param-field levels.outputWhite integer min 0 max 255
     * @param-field curves object
     * @param-field lut object
     * @param-field autoLevel boolean
     * @param-field autoLevelClip number min 0 max 20
     * @param-field denoise integer min 1 max 3
     * @param-field maskImage file
     * @param-field mask.invert boolean
     * @param-field blendImage file
     * @param-field blend.mode string one of over|multiply|screen|overlay|darken|lighten|color-dodge|color-burn|hard-light|soft-light|difference|exclusion|add|saturate
     * @param-field blend.opacity number min 0 max 1
     * @param-field blend.fit string one of cover|contain|fill
     * @param-field blend.gravity string one of center|north|northeast|east|southeast|south|southwest|west|northwest
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field background string
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.transform')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function transform(array $params = [], array $options = [])
    {
        return $this->client->call('images.transform', $params, $options);
    }

    /**
     * POST /v1/images/variants -- Generate N named variants from one input image
     *
     * @param-field images (required) file
     * @param-field variants (required) array
     * @param-field sharedPreprocessing object
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.variants')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function variants(array $params = [], array $options = [])
    {
        return $this->client->call('images.variants', $params, $options);
    }

    /**
     * POST /v1/images/vectorize -- Trace an image into a vector
     *
     * @param-field images (required) file
     * @param-field file_type string one of svg|pdf
     * @param-field threshold number min 0 max 100
     * @param-field despeckle integer min 0 max 100
     * @param-field smoothing number min 0 max 1.34
     * @param-field tolerance number min 0 max 5
     * @param-field invert boolean
     * @param-field color string
     * @param-field background string
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.vectorize')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function vectorize(array $params = [], array $options = [])
    {
        return $this->client->call('images.vectorize', $params, $options);
    }

    /**
     * POST /v1/images/watermark -- Watermark an image with text and/or an image overlay
     *
     * @param-field images (required) file
     * @param-field watermark object
     * @param-field watermarkImage file
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field output_format string one of jpeg|png|webp|gif|avif|tiff|bmp|ico|apng|jxl|jp2
     * @param-field quality integer min 1 max 100
     * @param-field lossless boolean
     * @param-field dpi integer min 1 max 1200
     * @param-field colorspace string one of srgb|grayscale|cmyk
     * @param-field icc_profile string one of srgb|p3|cmyk
     * @param-field metadata string one of strip|keep|set
     * @param-field background string
     * @param-field author string
     * @param-field copyright string
     * @param-field description string
     * @param-field gps_latitude number min -90 max 90
     * @param-field gps_longitude number min -180 max 180
     * @param-field gps_altitude number min -1000 max 100000
     * @param-field exif object
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('images.watermark')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function watermark(array $params = [], array $options = [])
    {
        return $this->client->call('images.watermark', $params, $options);
    }

}

/** jobs */
final class ApiJobs
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/jobs/{id}/cancel -- Cancel a still-queued job (Phase 2 execution-platform, NEW). A job already running/completed cannot be cancelled.
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('jobs.{id}.cancel')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function cancel(array $params = [], array $options = [])
    {
        return $this->client->call('jobs.{id}.cancel', $params, $options);
    }

    /**
     * GET /v1/jobs/{id} -- Poll a job's status/progress/result (Phase 2 execution-platform, NEW)
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('jobs.{id}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('jobs.{id}', $params, $options);
    }

    /**
     * GET /v1/jobs -- List the authenticated customer's async jobs (Phase 2 execution-platform, NEW)
     *
     * @param-field limit integer min 1 max 200
     * @param-field page integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('jobs')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('jobs', $params, $options);
    }

}

/** keys */
final class ApiKeys
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/keys -- Create an additional self-service API key for the authenticated customer (Customer Portal, NEW). Inherits the plan of the key used to authenticate. The new plaintext secret is returned exactly once, in this response.
     *
     * @param-field label string
     * @param-field expires_at string
     * @param-field expires_in_days integer min 1 max 3650
     * @param-field quota_limit integer min 1
     * @param-field ai_limit number min 0
     * @param array $params Request parameters keyed by wire name (see $client->schema('keys.post')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function create(array $params = [], array $options = [])
    {
        return $this->client->call('keys.post', $params, $options);
    }

    /**
     * DELETE /v1/keys/{id} -- Delete one of the customer's own API keys, at any time (expired or not)
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('keys.{id}.delete')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function delete(array $params = [], array $options = [])
    {
        return $this->client->call('keys.{id}.delete', $params, $options);
    }

    /**
     * GET /v1/keys -- List the authenticated customer's own API key(s) (Customer Portal, NEW). Metadata only -- prefix/last4/label/status/plan/dates, never the stored secret.
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('keys')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('keys', $params, $options);
    }

    /**
     * POST /v1/keys/{id}/revoke -- Revoke (disable) the authenticated customer's own API key (Customer Portal, NEW). Irreversible from the customer's side; if this is their only key they will not be able to authenticate again afterwards.
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('keys.{id}.revoke')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function revoke(array $params = [], array $options = [])
    {
        return $this->client->call('keys.{id}.revoke', $params, $options);
    }

    /**
     * POST /v1/keys/{id}/rotate -- Rotate the authenticated customer's own API key (Customer Portal, NEW). Generates a fresh secret for the same key row; the old secret stops working immediately. The new plaintext is returned exactly once, in this response.
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('keys.{id}.rotate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function rotate(array $params = [], array $options = [])
    {
        return $this->client->call('keys.{id}.rotate', $params, $options);
    }

    /**
     * GET /v1/key/verify -- Verify the caller's own API key and return its plan/entitlement state
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('key.verify')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function verify(array $params = [], array $options = [])
    {
        return $this->client->call('key.verify', $params, $options);
    }

}

/** limits */
final class ApiLimits
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/limits -- The limits that apply to the calling key right now, per endpoint
     *
     * @param-field endpoint string
     * @param array $params Request parameters keyed by wire name (see $client->schema('limits')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('limits', $params, $options);
    }

}

/** meta */
final class ApiMeta
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/capabilities/{section}/{feature} -- Runtime capabilities: one feature
     *
     * @param-field section (required) string
     * @param-field feature (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('capabilities.{section}.{feature}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function capabilitiesFeature(array $params = [], array $options = [])
    {
        return $this->client->call('capabilities.{section}.{feature}', $params, $options);
    }

    /**
     * GET /v1/capabilities/{section} -- Runtime capabilities: one section
     *
     * @param-field section (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('capabilities.{section}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function capabilitiesSection(array $params = [], array $options = [])
    {
        return $this->client->call('capabilities.{section}', $params, $options);
    }

    /**
     * GET /v1/contract -- The canonical Davix H2I API contract (machine-readable)
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contract(array $params = [], array $options = [])
    {
        return $this->client->call('contract', $params, $options);
    }

    /**
     * GET /v1/contract/auth -- Contract: authentication, headers, retry and request policy
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.auth')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractAuth(array $params = [], array $options = [])
    {
        return $this->client->call('contract.auth', $params, $options);
    }

    /**
     * GET /v1/contract/categories -- Contract: categories
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.categories')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractCategories(array $params = [], array $options = [])
    {
        return $this->client->call('contract.categories', $params, $options);
    }

    /**
     * GET /v1/contract/categories/{name} -- Contract: one category
     *
     * @param-field name (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.categories.{name}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractCategory(array $params = [], array $options = [])
    {
        return $this->client->call('contract.categories.{name}', $params, $options);
    }

    /**
     * GET /v1/contract/endpoints/{id} -- Contract: one endpoint
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.endpoints.{id}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEndpoint(array $params = [], array $options = [])
    {
        return $this->client->call('contract.endpoints.{id}', $params, $options);
    }

    /**
     * GET /v1/contract/endpoints/{id}/actions -- Contract: the actions of one endpoint
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.endpoints.{id}.actions')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEndpointActions(array $params = [], array $options = [])
    {
        return $this->client->call('contract.endpoints.{id}.actions', $params, $options);
    }

    /**
     * GET /v1/contract/endpoints/{id}/errors -- Contract: the errors of one endpoint
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.endpoints.{id}.errors')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEndpointErrors(array $params = [], array $options = [])
    {
        return $this->client->call('contract.endpoints.{id}.errors', $params, $options);
    }

    /**
     * GET /v1/contract/endpoints/{id}/examples -- Contract: examples for one endpoint
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.endpoints.{id}.examples')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEndpointExamples(array $params = [], array $options = [])
    {
        return $this->client->call('contract.endpoints.{id}.examples', $params, $options);
    }

    /**
     * GET /v1/contract/endpoints/{id}/fields/{name} -- Contract: one parameter of one endpoint
     *
     * @param-field id (required) string
     * @param-field name (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.endpoints.{id}.fields.{name}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEndpointField(array $params = [], array $options = [])
    {
        return $this->client->call('contract.endpoints.{id}.fields.{name}', $params, $options);
    }

    /**
     * GET /v1/contract/endpoints/{id}/fields -- Contract: the parameters of one endpoint
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.endpoints.{id}.fields')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEndpointFields(array $params = [], array $options = [])
    {
        return $this->client->call('contract.endpoints.{id}.fields', $params, $options);
    }

    /**
     * GET /v1/contract/endpoints/{id}/response -- Contract: the response of one endpoint
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.endpoints.{id}.response')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEndpointResponse(array $params = [], array $options = [])
    {
        return $this->client->call('contract.endpoints.{id}.response', $params, $options);
    }

    /**
     * GET /v1/contract/endpoints -- Contract: endpoint index
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.endpoints')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEndpoints(array $params = [], array $options = [])
    {
        return $this->client->call('contract.endpoints', $params, $options);
    }

    /**
     * GET /v1/contract/enums/{name} -- Contract: one enum
     *
     * @param-field name (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.enums.{name}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEnum(array $params = [], array $options = [])
    {
        return $this->client->call('contract.enums.{name}', $params, $options);
    }

    /**
     * GET /v1/contract/enums -- Contract: enums
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.enums')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractEnums(array $params = [], array $options = [])
    {
        return $this->client->call('contract.enums', $params, $options);
    }

    /**
     * GET /v1/contract/errors/{code} -- Contract: one error code
     *
     * @param-field code (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.errors.{code}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractError(array $params = [], array $options = [])
    {
        return $this->client->call('contract.errors.{code}', $params, $options);
    }

    /**
     * GET /v1/contract/errors -- Contract: error codes
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.errors')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractErrors(array $params = [], array $options = [])
    {
        return $this->client->call('contract.errors', $params, $options);
    }

    /**
     * GET /v1/contract/schemas/{name} -- Contract: one shared response schema
     *
     * @param-field name (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.schemas.{name}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractSchema(array $params = [], array $options = [])
    {
        return $this->client->call('contract.schemas.{name}', $params, $options);
    }

    /**
     * GET /v1/contract/schemas -- Contract: shared response schemas
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('contract.schemas')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function contractSchemas(array $params = [], array $options = [])
    {
        return $this->client->call('contract.schemas', $params, $options);
    }

    /**
     * GET /openapi.json -- This OpenAPI specification (Phase E, NEW)
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('openapi.json')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function openapi(array $params = [], array $options = [])
    {
        return $this->client->call('openapi.json', $params, $options);
    }

    /**
     * GET /openapi/categories/{name}.json -- OpenAPI: one category
     *
     * @param-field name (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('openapi.categories.{name}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function openapiCategory(array $params = [], array $options = [])
    {
        return $this->client->call('openapi.categories.{name}', $params, $options);
    }

    /**
     * GET /openapi/endpoints/{id}.json -- OpenAPI: one endpoint
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('openapi.endpoints.{id}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function openapiEndpoint(array $params = [], array $options = [])
    {
        return $this->client->call('openapi.endpoints.{id}', $params, $options);
    }

    /**
     * GET /v1/registry/endpoints/{id} -- Registry: one entry
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('registry.endpoints.{id}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function registryEndpoint(array $params = [], array $options = [])
    {
        return $this->client->call('registry.endpoints.{id}', $params, $options);
    }

}

/** notifications */
final class ApiNotifications
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * DELETE /v1/notifications/{id} -- Delete one of the customer's notifications
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('notifications.{id}.delete')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function delete(array $params = [], array $options = [])
    {
        return $this->client->call('notifications.{id}.delete', $params, $options);
    }

    /**
     * GET /v1/notifications/preferences -- Which notifications you receive
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('notifications.preferences')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function getPreferences(array $params = [], array $options = [])
    {
        return $this->client->call('notifications.preferences', $params, $options);
    }

    /**
     * GET /v1/notifications -- List the authenticated customer's notifications (Phase 2 execution-platform, NEW)
     *
     * @param-field limit integer min 1 max 100
     * @param-field unread boolean
     * @param-field page integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('notifications')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('notifications', $params, $options);
    }

    /**
     * POST /v1/notifications/{id}/read -- Mark one notification as read
     *
     * @param-field id (required) integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('notifications.{id}.read')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function read(array $params = [], array $options = [])
    {
        return $this->client->call('notifications.{id}.read', $params, $options);
    }

    /**
     * POST /v1/notifications/read-all -- Mark every unread notification as read
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('notifications.read-all')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function readAll(array $params = [], array $options = [])
    {
        return $this->client->call('notifications.read-all', $params, $options);
    }

    /**
     * PUT /v1/notifications/preferences -- Switch notification types on or off
     *
     * @param-field quota.warning boolean
     * @param-field job.completed boolean
     * @param-field job.failed boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('notifications.preferences.put')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function updatePreferences(array $params = [], array $options = [])
    {
        return $this->client->call('notifications.preferences.put', $params, $options);
    }

}

/** pdfs */
final class ApiPdfs
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/pdfs/attachments -- List, add, extract, or remove PDF embedded-file attachments (Phase C completion, NEW)
     *
     * @param-field file (required) file
     * @param-field operation (required) string one of list|add|extract|remove
     * @param-field key string
     * @param-field attachment file
     * @param-field filename string
     * @param-field mimetype string
     * @param-field description string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.attachments')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function attachments(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.attachments', $params, $options);
    }

    /**
     * POST /v1/pdfs/bookmarks -- Bookmarks, table of contents and page labels
     *
     * @param-field file (required) file
     * @param-field operation (required) string one of list|set|clear|toc|labels
     * @param-field bookmarks array
     * @param-field labels array
     * @param-field toc_title string
     * @param-field toc_depth integer min 1 max 8
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.bookmarks')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function bookmarks(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.bookmarks', $params, $options);
    }

    /**
     * POST /v1/pdfs/compare -- Compare two PDFs: line-level text diff or per-page visual diff (feature-expansion phase, NEW)
     *
     * @param-field fileA (required) file
     * @param-field fileB (required) file
     * @param-field mode string one of text|visual|pages
     * @param-field pages string
     * @param-field dpi integer min 30 max 300
     * @param-field threshold number min 0 max 1
     * @param-field region object
     * @param-field sideBySide boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field fileA_url string
     * @param-field fileB_url string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.compare')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function compare(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.compare', $params, $options);
    }

    /**
     * POST /v1/pdfs/cover -- Add a cover page
     *
     * @param-field file (required) file
     * @param-field image file
     * @param-field title string
     * @param-field subtitle string
     * @param-field author string
     * @param-field date string
     * @param-field lines array
     * @param-field background string
     * @param-field align string one of left|center|right
     * @param-field size string
     * @param-field fontFamily string
     * @param-field color string
     * @param-field lang string
     * @param-field direction string one of auto|ltr|rtl
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.cover')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function cover(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.cover', $params, $options);
    }

    /**
     * POST /v1/pdfs/extract -- Extract a page range or per-page/per-range documents
     *
     * @param-field file (required) file
     * @param-field pages string
     * @param-field output string one of single|perPage|perRange
     * @param-field prefix string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.extract')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function extract(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.extract', $params, $options);
    }

    /**
     * POST /v1/pdfs/forms -- Fill/flatten/list/create AcroForm fields
     *
     * @param-field file (required) file
     * @param-field data file
     * @param-field operation string one of flatten|fill|fillAndFlatten|listFields|createField|editField|exportData|importData
     * @param-field format string one of json|fdf|xfdf
     * @param-field fields object
     * @param-field editFields array
     * @param-field newFields array
     * @param-field flattenForms boolean
     * @param-field generateAppearances boolean
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.forms')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function forms(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.forms', $params, $options);
    }

    /**
     * POST /v1/pdfs/headers-footers -- Add headers, footers and page numbers
     *
     * @param-field file (required) file
     * @param-field header string
     * @param-field footer string
     * @param-field page_numbers string
     * @param-field numbering string one of arabic|roman|ROMAN|alpha|ALPHA
     * @param-field start_at integer
     * @param-field skip_first boolean
     * @param-field pages string
     * @param-field margin number min 0 max 300
     * @param-field fontFamily string
     * @param-field fontSize integer min 6 max 400
     * @param-field fontWeight string
     * @param-field italic boolean
     * @param-field color string
     * @param-field lang string
     * @param-field direction string one of auto|ltr|rtl
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.headers-footers')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function headersFooters(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.headers-footers', $params, $options);
    }

    /**
     * POST /v1/pdfs/images -- Extract embedded raster images
     *
     * @param-field file (required) file
     * @param-field pages string
     * @param-field format string one of png|jpeg|webp
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.images')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function images(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.images', $params, $options);
    }

    /**
     * POST /v1/pdfs/inspect -- Structure/fonts/signatures/security analysis (Phase C, NEW)
     *
     * @param-field file (required) file
     * @param-field checks array
     * @param-field pdfaFlavour string one of auto|1a|1b|2a|2b|2u|3a|3b|3u
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.inspect')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function inspect(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.inspect', $params, $options);
    }

    /**
     * POST /v1/pdfs/layout -- Crop, resize or impose a PDF as a booklet
     *
     * @param-field file (required) file
     * @param-field operation (required) string one of crop|resize|booklet
     * @param-field pages string
     * @param-field margin number
     * @param-field margins object
     * @param-field box object
     * @param-field permanent boolean
     * @param-field size string
     * @param-field fit string one of contain|cover|stretch|none
     * @param-field orientation string one of auto|portrait|landscape|keep
     * @param-field background string
     * @param-field sheet string
     * @param-field binding string one of left|right
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.layout')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function layout(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.layout', $params, $options);
    }

    /**
     * POST /v1/pdfs/manipulate -- Rotate/delete/reorder/resize/annotate/bookmark/link/stamp pages
     *
     * @param-field file (required) file
     * @param-field stampImages file
     * @param-field operations (required) array
     * @param-field flattenAnnotations string one of all|print|screen
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.manipulate')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function manipulate(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.manipulate', $params, $options);
    }

    /**
     * POST /v1/pdfs/merge -- Merge 2+ PDFs into one
     *
     * @param-field files (required) file
     * @param-field sortByName boolean
     * @param-field mode string one of concatenate|collate
     * @param-field collateSize integer min 1 max 100
     * @param-field blankSeparator boolean
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.merge')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function merge(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.merge', $params, $options);
    }

    /**
     * POST /v1/pdfs/metadata -- Read, edit or remove PDF metadata (document information, XMP ...)
     *
     * @param-field file (required) file
     * @param-field mode string one of read|merge|replace|strip|keep
     * @param-field clean string one of all|location|personal
     * @param-field remove array
     * @param-field remove_groups array
     * @param-field set object
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.metadata')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function metadata(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.metadata', $params, $options);
    }

    /**
     * POST /v1/pdfs/ocr -- OCR scanned PDFs to text and/or a searchable PDF (Tesseract; feature-expansion phase, NEW)
     *
     * @param-field file (required) file
     * @param-field languages string
     * @param-field deskew boolean
     * @param-field denoise boolean
     * @param-field clean boolean
     * @param-field output string one of text|pdf|pdfa|docx|json|tsv|hocr|alto
     * @param-field mode string one of auto|table
     * @param-field tables boolean
     * @param-field force boolean
     * @param-field pages string
     * @param-field dpi integer min 150 max 400
     * @param-field psm integer min 0 max 13
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.ocr')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function ocr(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.ocr', $params, $options);
    }

    /**
     * POST /v1/pdfs/optimize -- Reduce PDF size / repair structure
     *
     * @param-field file (required) file
     * @param-field recompressImages boolean
     * @param-field imageQuality integer min 1 max 100
     * @param-field imageColorSpace string one of srgb|grayscale
     * @param-field repair boolean
     * @param-field profile string one of screen|ebook|printer|prepress
     * @param-field downsample_dpi integer min 36 max 600
     * @param-field grayscale boolean
     * @param-field target_size_kb number min 1
     * @param-field linearize boolean
     * @param-field engine string one of sharp|qpdf|gs
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.optimize')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function optimize(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.optimize', $params, $options);
    }

    /**
     * POST /v1/pdfs/redact -- Secure burn-in redaction: affected pages are rasterized with opaque black boxes and rebuilt in a new PDF -- no text/objects survive underneath (feature-expansion phase, NEW)
     *
     * @param-field file (required) file
     * @param-field permanent (required) boolean
     * @param-field targets array
     * @param-field searchText array
     * @param-field patterns array
     * @param-field caseSensitive boolean
     * @param-field applyRedactAnnotations boolean
     * @param-field dpi integer min 72 max 300
     * @param-field padding number min 0 max 20
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.redact')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function redact(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.redact', $params, $options);
    }

    /**
     * POST /v1/pdfs/security -- Encrypt or decrypt a PDF
     *
     * @param-field file (required) file
     * @param-field operation (required) string one of encrypt|decrypt
     * @param-field userPassword string
     * @param-field ownerPassword string
     * @param-field password string
     * @param-field keyLength integer one of 40|128|256
     * @param-field permissions object
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.security')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function security(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.security', $params, $options);
    }

    /**
     * POST /v1/pdfs/sign -- Add a genuine PKCS#7 digital signature to a PDF, using a caller-supplied certificate/key (feature-expansion phase, NEW)
     *
     * @param-field file (required) file
     * @param-field certificateId integer min 1
     * @param-field certPem string
     * @param-field keyPem string
     * @param-field reason string
     * @param-field signerName string
     * @param-field contactInfo string
     * @param-field location string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.sign')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function sign(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.sign', $params, $options);
    }

    /**
     * POST /v1/pdfs/split -- Split a PDF
     *
     * @param-field file (required) file
     * @param-field mode (required) string one of every|bookmarks|blank|size
     * @param-field every integer min 1
     * @param-field level integer min 1 max 8
     * @param-field max_size_mb number min 0.02
     * @param-field keep_blank boolean
     * @param-field prefix string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.split')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function split(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.split', $params, $options);
    }

    /**
     * POST /v1/pdfs/tables -- Extract tables from a PDF
     *
     * @param-field file (required) file
     * @param-field pages string
     * @param-field format string one of json|csv|xlsx
     * @param-field table integer min 1
     * @param-field min_confidence number min 0 max 1
     * @param-field min_rows integer min 2 max 100
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field output_bundle string one of zip
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.tables')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function tables(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.tables', $params, $options);
    }

    /**
     * POST /v1/pdfs/text -- Extract the PDF text layer (not OCR), optionally as heuristic Markdown or HTML
     *
     * @param-field file (required) file
     * @param-field pages string
     * @param-field layout boolean
     * @param-field format string one of text|markdown|html
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.text')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function text(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.text', $params, $options);
    }

    /**
     * POST /v1/pdfs/to-images -- Rasterize PDF pages to images
     *
     * @param-field file (required) file
     * @param-field pages string
     * @param-field format string one of jpeg|jpg|png|webp
     * @param-field width integer min 1
     * @param-field height integer min 1
     * @param-field dpi integer min 1
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.to-images')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function toImages(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.to-images', $params, $options);
    }

    /**
     * POST /v1/pdfs/verify-signature -- Verify the digital signatures of a PDF
     *
     * @param-field file (required) file
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.verify-signature')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function verifySignature(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.verify-signature', $params, $options);
    }

    /**
     * POST /v1/pdfs/watermark -- Watermark every page of a PDF, or overlay/underlay another PDF onto it
     *
     * @param-field file (required) file
     * @param-field watermarkText string
     * @param-field watermarkImage file
     * @param-field pages string
     * @param-field opacity number min 0 max 1
     * @param-field margin integer min 0 max 5000
     * @param-field position string one of center|top-left|top-right|bottom-left|bottom-right|top|bottom|left|right
     * @param-field fontSize integer min 1 max 400
     * @param-field color string
     * @param-field watermarkScale number min 0.01 max 1
     * @param-field pageNumberTemplate string
     * @param-field mode string one of stamp|overlay|underlay
     * @param-field overlayFile file
     * @param-field to string
     * @param-field from string
     * @param-field repeat string
     * @param-field output_bundle string one of zip
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('pdfs.watermark')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function watermark(array $params = [], array $options = [])
    {
        return $this->client->call('pdfs.watermark', $params, $options);
    }

}

/** presets */
final class ApiPresets
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/presets -- Create a preset
     *
     * @param-field name (required) string
     * @param-field endpoint (required) string
     * @param-field parameters (required) object
     * @param array $params Request parameters keyed by wire name (see $client->schema('presets.post')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function create(array $params = [], array $options = [])
    {
        return $this->client->call('presets.post', $params, $options);
    }

    /**
     * DELETE /v1/presets/{id} -- Delete a preset
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('presets.{id}.delete')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function delete(array $params = [], array $options = [])
    {
        return $this->client->call('presets.{id}.delete', $params, $options);
    }

    /**
     * GET /v1/presets/{id} -- Read a preset
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('presets.{id}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('presets.{id}', $params, $options);
    }

    /**
     * GET /v1/presets -- List presets
     *
     * @param-field endpoint string
     * @param array $params Request parameters keyed by wire name (see $client->schema('presets')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('presets', $params, $options);
    }

    /**
     * PUT /v1/presets/{id} -- Update a preset
     *
     * @param-field id (required) string
     * @param-field name string
     * @param-field parameters object
     * @param array $params Request parameters keyed by wire name (see $client->schema('presets.{id}.put')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function update(array $params = [], array $options = [])
    {
        return $this->client->call('presets.{id}.put', $params, $options);
    }

}

/** registry */
final class ApiRegistry
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/registry -- The one authoritative endpoint registry (Phase 2 execution-platform, NEW): every public endpoint with its full parameter schema, quota bucket and job-eligibility flag. Studio builds its operation sidebar and parameter forms from it.
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('registry')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('registry', $params, $options);
    }

}

/** render */
final class ApiRender
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/render/chart -- Render a chart from data
     *
     * @param-field type string one of bar|line|area|scatter|pie|donut|stacked-bar|grouped-bar|histogram
     * @param-field data array
     * @param-field data_file file
     * @param-field x string
     * @param-field y string
     * @param-field color string
     * @param-field title string
     * @param-field spec object
     * @param-field output string one of svg|png|jpeg|webp|avif|pdf
     * @param-field width integer min 50 max 4000
     * @param-field height integer min 50 max 4000
     * @param-field theme string one of light|dark
     * @param-field scale number min 1 max 4
     * @param-field quality integer min 1 max 100
     * @param-field lang string
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field source_url string
     * @param-field source_urls array
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('render.chart')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function chart(array $params = [], array $options = [])
    {
        return $this->client->call('render.chart', $params, $options);
    }

    /**
     * POST /v1/render/html-to-image -- Render HTML to a PNG/JPEG screenshot
     *
     * @param-field html string
     * @param-field css string
     * @param-field width integer min 1
     * @param-field height integer min 1
     * @param-field format string one of png|jpeg|webp|avif
     * @param-field deviceScaleFactor number min 1 max 3
     * @param-field clip object
     * @param-field waitForSelector string
     * @param-field waitForMilliseconds integer min 0 max 10000
     * @param-field waitUntil string one of load|domcontentloaded|networkidle0|networkidle2
     * @param-field mediaType string one of screen|print
     * @param-field colorScheme string one of light|dark|no-preference
     * @param-field timezone string
     * @param-field locale string
     * @param-field headers object
     * @param-field cookies array
     * @param-field jpegQuality integer min 1 max 100
     * @param-field url string
     * @param-field device string one of iphone-se|iphone-14|iphone-14-pro-max|pixel-7|galaxy-s20|ipad|ipad-pro|android-tablet|laptop|desktop|desktop-4k
     * @param-field userAgent string
     * @param-field blockResources array
     * @param-field waitForFunction string
     * @param-field lang string
     * @param-field fonts array
     * @param-field variables object
     * @param-field templateId string
     * @param-field records array
     * @param-field transparent boolean
     * @param-field fullPage boolean
     * @param-field selector string
     * @param-field scrollTo string
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('render.html-to-image')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function htmlToImage(array $params = [], array $options = [])
    {
        return $this->client->call('render.html-to-image', $params, $options);
    }

    /**
     * POST /v1/render/html-to-pdf -- Render HTML to a PDF via Chromium print-to-PDF
     *
     * @param-field html string
     * @param-field markdown string
     * @param-field text string
     * @param-field css string
     * @param-field width integer min 1
     * @param-field height integer min 1
     * @param-field format string one of A0|A1|A2|A3|A4|A5|A6|Letter|Legal|Tabloid|Ledger
     * @param-field landscape boolean
     * @param-field margin integer
     * @param-field preferCSSPageSize boolean
     * @param-field scale number
     * @param-field printMode boolean
     * @param-field printBackground boolean
     * @param-field deviceScaleFactor number min 1 max 3
     * @param-field waitForSelector string
     * @param-field waitForMilliseconds integer min 0 max 10000
     * @param-field waitUntil string one of load|domcontentloaded|networkidle0|networkidle2
     * @param-field mediaType string one of screen|print
     * @param-field colorScheme string one of light|dark|no-preference
     * @param-field timezone string
     * @param-field locale string
     * @param-field headers object
     * @param-field cookies array
     * @param-field headerTemplate string
     * @param-field footerTemplate string
     * @param-field url string
     * @param-field device string one of iphone-se|iphone-14|iphone-14-pro-max|pixel-7|galaxy-s20|ipad|ipad-pro|android-tablet|laptop|desktop|desktop-4k
     * @param-field userAgent string
     * @param-field blockResources array
     * @param-field waitForFunction string
     * @param-field lang string
     * @param-field fonts array
     * @param-field variables object
     * @param-field templateId string
     * @param-field records array
     * @param-field pageRanges string
     * @param-field outline boolean
     * @param-field tagged boolean
     * @param-field metadata object
     * @param-field pdfa string one of 1b|2b|3b
     * @param-field validate_only boolean
     * @param-field presetId string
     * @param-field response string one of url|binary|base64
     * @param-field filename string
     * @param-field filename_template string
     * @param-field app string
     * @param-field async boolean
     * @param array $params Request parameters keyed by wire name (see $client->schema('render.html-to-pdf')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function htmlToPdf(array $params = [], array $options = [])
    {
        return $this->client->call('render.html-to-pdf', $params, $options);
    }

}

/** subscription */
final class ApiSubscription
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/subscription -- The authenticated customer's own subscription status, plan detail, and current usage (Customer Portal, NEW).
     *
     * @param array $params Request parameters keyed by wire name (see $client->schema('subscription')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('subscription', $params, $options);
    }

}

/** templates */
final class ApiTemplates
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * POST /v1/templates -- Create a template
     *
     * @param-field name (required) string
     * @param-field kind string one of html
     * @param-field description string
     * @param-field html (required) string
     * @param-field css string
     * @param-field variables array
     * @param array $params Request parameters keyed by wire name (see $client->schema('templates.post')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function create(array $params = [], array $options = [])
    {
        return $this->client->call('templates.post', $params, $options);
    }

    /**
     * DELETE /v1/templates/{id} -- Delete a template
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('templates.{id}.delete')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function delete(array $params = [], array $options = [])
    {
        return $this->client->call('templates.{id}.delete', $params, $options);
    }

    /**
     * PUT /v1/templates/{id}/file -- Replace the file of a DOCX / ODT template
     *
     * @param-field id (required) string
     * @param-field file (required) file
     * @param-field variables array
     * @param array $params Request parameters keyed by wire name (see $client->schema('templates.{id}.file.put')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function file(array $params = [], array $options = [])
    {
        return $this->client->call('templates.{id}.file.put', $params, $options);
    }

    /**
     * GET /v1/templates/{id} -- Get a template
     *
     * @param-field id (required) string
     * @param array $params Request parameters keyed by wire name (see $client->schema('templates.{id}')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('templates.{id}', $params, $options);
    }

    /**
     * GET /v1/templates -- List templates
     *
     * @param-field kind string one of html|docx
     * @param array $params Request parameters keyed by wire name (see $client->schema('templates')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function list(array $params = [], array $options = [])
    {
        return $this->client->call('templates', $params, $options);
    }

    /**
     * PUT /v1/templates/{id} -- Update a template
     *
     * @param-field id (required) string
     * @param-field name string
     * @param-field description string
     * @param-field html string
     * @param-field css string
     * @param-field variables array
     * @param array $params Request parameters keyed by wire name (see $client->schema('templates.{id}.put')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function update(array $params = [], array $options = [])
    {
        return $this->client->call('templates.{id}.put', $params, $options);
    }

    /**
     * POST /v1/templates/upload -- Create a DOCX / ODT template
     *
     * @param-field name (required) string
     * @param-field description string
     * @param-field file (required) file
     * @param-field variables array
     * @param array $params Request parameters keyed by wire name (see $client->schema('templates.upload')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function upload(array $params = [], array $options = [])
    {
        return $this->client->call('templates.upload', $params, $options);
    }

}

/** usage */
final class ApiUsage
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * GET /v1/usage/breakdown -- Usage activity behind the usage page: per day, per operation, why requests failed, per key, AI cost and jobs, plus what the plan includes
     *
     * @param-field range string one of period|7d|30d|month
     * @param-field key_id string
     * @param-field key string
     * @param array $params Request parameters keyed by wire name (see $client->schema('usage.breakdown')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function breakdown(array $params = [], array $options = [])
    {
        return $this->client->call('usage.breakdown', $params, $options);
    }

    /**
     * GET /v1/usage/calls -- Request log of your keys (time, status, request id, key, operation, size, and the reason for a failure), optionally of one operation
     *
     * @param-field operation string
     * @param-field key_id string
     * @param-field key string
     * @param-field limit integer min 1 max 200
     * @param-field range string one of period|7d|30d|month
     * @param-field before integer min 1
     * @param-field page integer min 1
     * @param array $params Request parameters keyed by wire name (see $client->schema('usage.calls')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function calls(array $params = [], array $options = [])
    {
        return $this->client->call('usage.calls', $params, $options);
    }

    /**
     * GET /v1/usage -- The authenticated customer's own usage: current-period snapshot, per-category totals, recent period history, and (if they have more than one key) a per-key breakdown (Customer Portal, NEW).
     *
     * @param-field months integer min 1 max 24
     * @param array $params Request parameters keyed by wire name (see $client->schema('usage')).
     * @param array $options idempotencyKey, headers, timeoutMs, maxRetries, retry, validate, strict
     * @return Response
     */
    public function get(array $params = [], array $options = [])
    {
        return $this->client->call('usage', $params, $options);
    }

}
