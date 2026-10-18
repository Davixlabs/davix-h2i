<?php
/**
 * Integration tests that run inside WordPress, with a fake Davix H2I client (no network).
 *
 *   wp eval-file wp-content/plugins/davix-h2i/tests/integration/run.php
 *
 * They check the parts that do not need a live service: chip pipelines, the key store, option sanitizers, the result
 * pipeline (replace, copy, new media, backup, restore), AI text handling, the queue and its error paths, History, and the
 * plan gate. The live service is covered by tests/e2e/sweep.php.
 */

use DavixWP\Capabilities\CapabilityGate;
use DavixWP\Capabilities\LimitsService;
use DavixWP\Connection\ClientFactory;
use DavixWP\Connection\ErrorMapper;
use DavixWP\Connection\KeyStore;
use DavixWP\Core\Options;
use DavixWP\Core\Services;
use DavixWP\History\Repository;
use DavixWP\Jobs\Queue;
use DavixWP\Jobs\Worker;
use DavixWP\Media\AttachmentWriter;
use DavixWP\Media\BackupStore;
use DavixWP\Media\TextWriter;
use DavixWP\Modules\Images\Chips;
use DavixWP\Operations\Contract;
use DavixWP\Operations\Executor;

wp_set_current_user( 1 );

// Keep the site's real key and history: the test restores them at the end.
// The stored (still encrypted) key is also backed up in an option. A run that is killed half way leaves that backup behind and
// the next run puts the real key back first, so a test can never leave a fake key on the site.
if ( false !== get_option( 'davix_h2i_key_test_backup' ) ) {
    update_option( KeyStore::OPTION, get_option( 'davix_h2i_key_test_backup' ), false );
    delete_option( 'davix_h2i_key_test_backup' );
}
$dvx_saved_raw = get_option( KeyStore::OPTION );
if ( false !== $dvx_saved_raw ) {
    update_option( 'davix_h2i_key_test_backup', $dvx_saved_raw, false );
}
$dvx_saved_key = KeyStore::get();
register_shutdown_function( function () use ( $dvx_saved_raw ) {
    // Even if the run dies half way, the site gets its own key back.
    false !== $dvx_saved_raw ? update_option( KeyStore::OPTION, $dvx_saved_raw, false ) : delete_option( KeyStore::OPTION );
    delete_option( 'davix_h2i_key_test_backup' );
} );
$dvx_history_start = (int) $GLOBALS['wpdb']->get_var( 'SELECT COALESCE(MAX(id),0) FROM ' . \DavixWP\Core\Installer::history_table() ); // phpcs:ignore
$dvx_saved_options = array( 'images' => get_option( 'davix_h2i_images' ), 'runtime' => get_option( 'davix_h2i_runtime' ), 'ai' => get_option( 'davix_h2i_ai' ), 'auto' => get_option( 'davix_h2i_auto' ), 'pdf' => get_option( 'davix_h2i_pdf' ) );
$dvx_saved_flag    = get_option( 'davix_h2i_auto_migrated' );

$GLOBALS['dvx_pass'] = 0;
$GLOBALS['dvx_fail'] = 0;
function dvx_ok( $cond, $name ) {
    if ( $cond ) {
        $GLOBALS['dvx_pass']++;
    } else {
        $GLOBALS['dvx_fail']++;
        echo "FAIL: $name\n";
    }
}

/** A stand-in for \Davix\H2I\Client: returns canned answers and writes a small file for downloads. */
class Dvx_Fake_Client {
    public $calls = array();
    public $answers = array();
    public $baseUrl = 'http://fake.test';
    public function call( $id, array $params = array(), array $options = array() ) {
        $this->calls[] = array( $id, $params, $options );
        $answer = isset( $this->answers[ $id ] ) ? $this->answers[ $id ] : array( 200, array( 'results' => array() ) );
        if ( $answer instanceof \Throwable ) {
            throw $answer;
        }
        return new \Davix\H2I\Response( $answer[0], array(), $answer[1], 'req-test', null, 1 );
    }
    public function download( $url, array $options = array() ) {
        file_put_contents( $options['dest'], isset( $this->answers['__bytes'] ) ? $this->answers['__bytes'] : 'x' );
        return array( 'path' => $options['dest'], 'contentType' => 'image/jpeg', 'size' => 1 );
    }
}

function dvx_make_jpeg( $w = 800, $h = 600, $quality = 95 ) {
    $im = imagecreatetruecolor( $w, $h );
    for ( $i = 0; $i < 30; $i++ ) {
        imagefilledellipse( $im, rand( 0, $w ), rand( 0, $h ), rand( 40, 300 ), rand( 40, 300 ), imagecolorallocate( $im, rand( 0, 255 ), rand( 0, 255 ), rand( 0, 255 ) ) );
    }
    $path = sys_get_temp_dir() . '/dvx-' . wp_generate_password( 6, false ) . '.jpg';
    imagejpeg( $im, $path, $quality );
    return $path;
}

function dvx_small_jpeg_bytes() {
    $im = imagecreatetruecolor( 800, 600 );
    imagefill( $im, 0, 0, imagecolorallocate( $im, 10, 20, 30 ) );
    ob_start();
    imagejpeg( $im, null, 40 );
    return ob_get_clean();
}

// ---------------------------------------------------------------- contract and operations
$ctx = Services::get( 'context' );
dvx_ok( count( $ctx->operations() ) === 43, 'exactly 43 operations are registered (' . count( $ctx->operations() ) . ')' );
foreach ( $ctx->operations() as $id => $def ) {
    dvx_ok( Contract::has( $def['endpoint'] ), "operation $id (endpoint {$def['endpoint']}) exists in the SDK contract" );
    foreach ( \DavixWP\Operations\Definition::fields( $def ) as $spec ) {
        if ( 'file' === $spec['type'] ) {
            continue;
        }
        dvx_ok( Contract::has_field( $def['endpoint'], $spec['name'] ) || 'p' === $spec['name'] || is_callable( $def['translate'] ), "$id field {$spec['name']} exists in the contract" );
    }
    // Nothing the service supports is left out: every request field is a main option, a "More options" field, an extra file or one the plugin handles itself.
    $shown = array();
    foreach ( \DavixWP\Operations\Definition::fields( $def ) as $spec ) {
        $shown[] = $spec['name'];
        if ( ! empty( $spec['object'] ) ) {
            $shown[] = $spec['object'];
        }
    }
    foreach ( \DavixWP\Operations\Definition::advanced( $def ) as $spec ) {
        $shown[] = $spec['name'];
    }
    $shown = array_merge( $shown, array_keys( \DavixWP\Operations\Definition::extra_files( $def ) ), \DavixWP\Operations\Definition::SYSTEM_FIELDS, isset( $def['skip'] ) ? (array) $def['skip'] : array(), array( $def['file_field'] ) );
    $left_out = array();
    foreach ( Contract::request_fields( $id ) as $cf ) {
        if ( ! in_array( $cf['name'], $shown, true ) && '_url' !== substr( $cf['name'], -4 ) ) {
            $left_out[] = $cf['name'];
        }
    }
    dvx_ok( ! $left_out, "$id: every supported parameter can be set" . ( $left_out ? ' (missing: ' . implode( ', ', $left_out ) . ')' : '' ) );
    if ( ! empty( $def['file_field'] ) && 'none' !== $def['input'] ) {
        dvx_ok( Contract::has_field( $def['endpoint'], $def['file_field'] ), "$id file field {$def['file_field']} exists" );
    }
}

// ---------------------------------------------------------------- chips
$p = Chips::build( array( 'smart' ), array( 'smart' => array( 'maxSizeKB' => '300' ) ) );
dvx_ok( 'images.auto-optimize' === $p['operation'] && 300 === $p['params']['maxSizeKB'], 'one smart chip uses auto-optimize' );
$p = Chips::build( array( 'format' ), array( 'format' => array( 'format' => 'webp', 'quality' => '80' ) ) );
dvx_ok( 'images.convert' === $p['operation'] && 'webp' === $p['params']['output_format'] && 80 === $p['params']['quality'], 'one format chip uses convert' );
$p = Chips::build( array( 'resize' ), array( 'resize' => array( 'width' => '1200', 'fit' => 'cover' ) ) );
dvx_ok( 'images.resize' === $p['operation'] && 'cover' === $p['params']['resizeMode'], 'one resize chip uses resize with resizeMode' );
$p = Chips::build( array( 'crop' ), array( 'crop' => array( 'x' => '1', 'y' => '2', 'width' => '3', 'height' => '4' ) ) );
dvx_ok( 'images.transform' === $p['operation'] && 3 === $p['params']['crop.width'], 'one crop chip uses transform crop.*' );
$p = Chips::build( array( 'watermark' ), array( 'watermark' => array( 'text' => 'Hi' ) ) );
dvx_ok( 'images.watermark' === $p['operation'] && 'Hi' === $p['params']['watermark']['text'], 'one watermark chip uses watermark' );
$p = Chips::build( array( 'smart', 'resize', 'padding' ), array( 'smart' => array(), 'resize' => array( 'width' => '800' ), 'padding' => array( 'all' => '10' ) ) );
dvx_ok( 'images.pipeline' === $p['operation'], 'several chips use the pipeline' );
$ops = array_column( $p['params']['operations'], 'op' );
dvx_ok( array( 'resize', 'padding', 'optimize' ) === $ops, 'encoding steps go last in the pipeline (' . implode( ',', $ops ) . ')' );
dvx_ok( null === Chips::build( array(), array() ), 'no chips means no call' );
$p = Chips::build( array( 'padding' ), array( 'padding' => array( 'all' => '5' ) ) );
dvx_ok( 'images.pipeline' === $p['operation'] && 'padding' === $p['params']['operations'][0]['op'], 'a chip without its own endpoint becomes a one step pipeline' );

// ---------------------------------------------------------------- key store
KeyStore::set( 'h2i_testkey_1234567890abcd' );
dvx_ok( 'h2i_testkey_1234567890abcd' === KeyStore::get(), 'key store round trip' );
dvx_ok( false === strpos( (string) get_option( KeyStore::OPTION ), 'testkey' ), 'key is not stored in clear text' );
dvx_ok( 'abcd' === substr( KeyStore::masked(), -4 ) && false === strpos( KeyStore::masked(), 'testkey' ), 'key is shown masked' );
dvx_ok( false === KeyStore::set( "bad key\nwith newline" ), 'a key with whitespace is refused' );

// ---------------------------------------------------------------- options
$saved = Options::update( 'images', array( 'output' => array( 'mode' => 'evil', 'suffix' => '../x<script>', 'backup' => '1' ) ) );
dvx_ok( 'replace' === $saved['output']['mode'] && '__xscript' !== $saved['output']['suffix'] && false === strpos( $saved['output']['suffix'], '<' ), 'images options are sanitized' );
$saved = Options::update( 'runtime', array( 'timeout' => '9999', 'retries' => '-3', 'access' => array( 'use' => 'nonsense' ) ) );
dvx_ok( 300 === $saved['timeout'] && 0 === $saved['retries'] && 'upload_files' === $saved['access']['use'], 'runtime options are clamped' );
Options::reset( 'images' );
Options::reset( 'runtime' );

// ---------------------------------------------------------------- error mapper
$e = new \Davix\H2I\Exceptions\QuotaException( 'x', array( 'code' => 'monthly_quota_exceeded', 'status' => 429, 'requestId' => 'r1' ) );
$m = ErrorMapper::map( $e );
dvx_ok( 'quota' === $m['kind'] && $m['fatal'] && 'r1' === $m['request_id'], 'quota errors are fatal for the queue' );
$m = ErrorMapper::map( new \Davix\H2I\Exceptions\RateLimitException( 'x', array( 'code' => 'rate_limit_exceeded', 'status' => 429, 'retryable' => true, 'retryAfterMs' => 2500 ) ) );
dvx_ok( 'rate' === $m['kind'] && $m['retryable'] && 3 === $m['retry_after'], 'rate limits are retryable with a wait' );
$m = ErrorMapper::map( new \Davix\H2I\Exceptions\NetworkException( 'down' ) );
dvx_ok( 'network' === $m['kind'] && $m['retryable'], 'network errors are retryable' );
$m = ErrorMapper::map( new \Davix\H2I\Exceptions\ApiException( 'bad', array( 'code' => 'invalid_parameter', 'status' => 400, 'details' => array( 'field' => 'quality' ) ) ) );
dvx_ok( false !== strpos( $m['message'], 'quality' ), 'invalid parameter names the field' );

// ---------------------------------------------------------------- plan gate
LimitsService::set_snapshot(
    array(
        'plan'      => array( 'name' => 'Test' ),
        'allowance' => array( 'total' => 10, 'used' => 10, 'overage' => 'block' ),
        'endpoints' => array(
            array( 'id' => 'images.optimize', 'allowed' => true, 'cost_per_call' => 0.5, 'files' => array( 'per_request' => 4, 'max_file_size_bytes' => 1000 ) ),
            array( 'id' => 'images.resize', 'allowed' => false ),
        ),
    ),
    array( 'features_by_family' => array( 'content' => array( array( 'id' => 'content.alt-text', 'readiness' => 'experimental' ), array( 'id' => 'content.title', 'readiness' => 'feature-unavailable' ) ) ) )
);
dvx_ok( CapabilityGate::allows( 'images.optimize' ) && 0.5 === CapabilityGate::cost( 'images.optimize' ), 'gate: allowed with fractional cost' );
dvx_ok( ! CapabilityGate::allows( 'images.resize' ) && CapabilityGate::LOCKED === CapabilityGate::state( 'images.resize' ), 'gate: locked operation' );
dvx_ok( 4 === CapabilityGate::batch_size( 'images.optimize' ) && 1000 === CapabilityGate::max_file_bytes( 'images.optimize' ), 'gate: per request and file limits' );
dvx_ok( CapabilityGate::BETA === CapabilityGate::state( 'ai.content.alt-text' ), 'gate: experimental AI is Beta' );
dvx_ok( ! CapabilityGate::allows( 'ai.content.title' ), 'gate: unavailable AI is not offered' );
dvx_ok( CapabilityGate::exhausted(), 'gate: used up allowance is reported' );
$out = Executor::run( array( 'operation' => 'images.resize', 'object_id' => 0 ) );
dvx_ok( ! $out['ok'] && 'endpoint_not_allowed' === $out['error']['code'], 'a locked operation never reaches the network' );
LimitsService::set_snapshot( null, null );

// ---------------------------------------------------------------- result pipeline with a fake client
$fake = new Dvx_Fake_Client();
ClientFactory::set_override( $fake );
$jpeg = dvx_make_jpeg( 1200, 900, 98 );
$att  = AttachmentWriter::import( $jpeg, array( 'filename' => 'dvx-int.jpg', 'mime' => 'image/jpeg', 'keep_source' => true ) );
$id   = $att['id'];
$orig = filesize( get_attached_file( $id ) );

$fake->answers['__bytes']             = dvx_small_jpeg_bytes();
$fake->answers['images.optimize'] = array( 200, array( 'results' => array( array( 'url' => 'http://fake.test/image/out1.jpg', 'format' => 'jpeg', 'sizeBytes' => 10, 'width' => 800, 'height' => 600, 'file_id' => 77 ) ) ) );
$o = Executor::run( array( 'operation' => 'images.optimize', 'object_id' => $id, 'params' => array( 'quality' => 60 ), 'source' => 'tab' ) );
dvx_ok( $o['ok'] && 'success' === $o['status'], 'optimize succeeds' );
$last_call = end( $fake->calls );
dvx_ok( 'Davix H2I plugin' === $last_call[1]['app'], 'every call names the app that made it (shown in the History of the account)' );
add_filter( 'davix_h2i_app_name', function () {
    return 'My site';
} );
Executor::run( array( 'operation' => 'images.optimize', 'object_id' => $id, 'params' => array( 'quality' => 61 ), 'source' => 'tab' ) );
$last_call = end( $fake->calls );
dvx_ok( 'My site' === $last_call[1]['app'], 'a site can change the app name with the davix_h2i_app_name filter' );
remove_all_filters( 'davix_h2i_app_name' );
dvx_ok( filesize( get_attached_file( $id ) ) < $orig, 'optimize replaced the file with the smaller result' );
dvx_ok( BackupStore::has( $id ), 'optimize kept a backup of the original' );
dvx_ok( $o['history_id'] > 0 && 'success' === Repository::find( $o['history_id'] )['status'], 'optimize wrote a History row' );
$row = Repository::find( $o['history_id'] );
dvx_ok( array( 77 ) === $row['file_ids'] && 'tab' === $row['source'] && $row['bytes_before'] === $orig, 'History keeps file id, source and sizes' );
dvx_ok( 'h2i-wp-' === substr( $fake->calls[0][2]['idempotencyKey'], 0, 7 ), 'calls carry an idempotency key' );
dvx_ok( isset( $fake->calls[0][1]['images'][0]['path'] ), 'the file is sent as a path (SDK reads it)' );

// restore
dvx_ok( BackupStore::restore( $id ) && filesize( get_attached_file( $id ) ) === $orig, 'restore puts the original back' );
dvx_ok( ! BackupStore::has( $id ), 'restore removes the backup' );

// never keep a larger result
$fake->answers['__bytes'] = str_repeat( 'x', $orig + 500 );
$o = Executor::run( array( 'operation' => 'images.optimize', 'object_id' => $id, 'source' => 'tab' ) );
dvx_ok( $o['ok'] && filesize( get_attached_file( $id ) ) === $orig && ! empty( $o['outputs'][0]['skipped'] ), 'a larger optimize result is skipped and the original kept' );

// copy mode
$fake->answers['__bytes'] = dvx_small_jpeg_bytes();
$o = Executor::run( array( 'operation' => 'images.optimize', 'object_id' => $id, 'mode' => 'copy', 'source' => 'tab' ) );
dvx_ok( $o['ok'] && $o['attachment_ids'][0] !== $id && 'attachment' === get_post_type( $o['attachment_ids'][0] ), 'copy mode creates a new media item' );
dvx_ok( (int) get_post_meta( $o['attachment_ids'][0], '_davix_h2i_source', true ) === $id, 'the copy remembers its source' );

// text results
$fake->answers['ai.content.alt-text'] = array( 200, array( 'result' => array( 'alt_text' => 'A colorful abstract picture', 'long_description' => 'Many colored circles.' ) ) );
Options::update( 'ai', array( 'apply' => array( 'policy' => 'empty' ) ) );
$o = Executor::run( array( 'operation' => 'ai.content.alt-text', 'object_id' => $id, 'source' => 'tab' ) );
LimitsService::set_snapshot( null, null );
dvx_ok( $o['ok'] && 'A colorful abstract picture' === $o['text']['alt_text'], 'alt text is read from the AI envelope' );
dvx_ok( 'A colorful abstract picture' === get_post_meta( $id, '_wp_attachment_image_alt', true ), 'alt text is written when the field is empty' );
$fake->answers['ai.content.alt-text'] = array( 200, array( 'result' => array( 'alt_text' => 'Second try', 'long_description' => null ) ) );
$o = Executor::run( array( 'operation' => 'ai.content.alt-text', 'object_id' => $id, 'source' => 'tab' ) );
dvx_ok( 'A colorful abstract picture' === get_post_meta( $id, '_wp_attachment_image_alt', true ), 'policy "empty" does not overwrite existing alt text' );
$o = Executor::run( array( 'operation' => 'ai.content.alt-text', 'object_id' => $id, 'policy' => 'review', 'source' => 'tab' ) );
dvx_ok( 'pending_review' === $o['status'] && 'A colorful abstract picture' === get_post_meta( $id, '_wp_attachment_image_alt', true ), 'review policy holds the suggestion' );
$hist = Repository::find( $o['history_id'] );
dvx_ok( 'pending_review' === $hist['status'] && 'Second try' === $hist['result']['pending']['text']['alt_text'], 'a held suggestion is kept in History' );
TextWriter::apply_approved( 'ai.content.alt-text', $id, $hist['result']['pending']['text'] );
dvx_ok( 'Second try' === get_post_meta( $id, '_wp_attachment_image_alt', true ), 'approving applies the text' );
$n = TextWriter::normalize( 'ai.content.tags', array( 'tags' => array( array( 'tag' => 'cat', 'confidence' => 'high' ), array( 'tag' => 'dog' ) ) ) );
dvx_ok( array( 'cat', 'dog' ) === $n['tags'], 'tags are flattened' );
$n = TextWriter::normalize( 'ai.content.seo', array( 'title' => 'T', 'meta_description' => 'D', 'keywords' => array( 'a' ), 'slug' => 's' ) );
dvx_ok( 'T' === $n['title'] && 'D' === $n['meta_description'], 'SEO text is normalized' );
$n = TextWriter::normalize( 'ai.images.moderate', array( 'safe' => false, 'summary' => 's', 'categories' => array() ) );
dvx_ok( false === $n['safe'], 'moderation result is normalized' );

// generation: result images are imported as new media
$fake->answers['__bytes'] = dvx_small_jpeg_bytes();
$fake->answers['ai.generation.generate'] = array( 200, array( 'result' => array( 'images' => array( array( 'url' => 'http://fake.test/ai/g1.jpg', 'width' => 8, 'height' => 8, 'format' => 'jpeg', 'size_bytes' => 5 ) ), 'model_notes' => null ) ) );
$o = Executor::run( array( 'operation' => 'ai.generation.generate', 'params' => array( 'instructions' => 'an apple' ), 'source' => 'generator' ) );
dvx_ok( $o['ok'] && 1 === count( $o['attachment_ids'] ) && 'attachment' === get_post_type( $o['attachment_ids'][0] ), 'a generated image becomes a media item' );
dvx_ok( \DavixWP\Media\StateMarker::is_generated( $o['attachment_ids'][0] ), 'generated media is marked as generated' );

// errors and the queue
$fake->answers['images.optimize'] = new \Davix\H2I\Exceptions\QuotaException( 'used up', array( 'code' => 'monthly_quota_exceeded', 'status' => 429 ) );
$before = Queue::counts();
$j1 = Queue::enqueue( array( 'operation' => 'images.optimize', 'object_id' => $id, 'params' => array(), 'source' => 'bulk', 'batch_id' => 'btest' ) );
$j2 = Queue::enqueue( array( 'operation' => 'images.optimize', 'object_id' => $id, 'params' => array(), 'source' => 'bulk', 'batch_id' => 'btest' ) );
dvx_ok( $j1 > 0 && $j2 > $j1, 'jobs can be queued' );
$stats = Worker::run( 20 );
dvx_ok( 'quota' === $stats['paused'], 'a used-up allowance pauses the queue' );
$job1 = Queue::find( $j1 );
$job2 = Queue::find( $j2 );
dvx_ok( 'failed' === $job1['status'] && 'queued' === $job2['status'], 'the failing job fails and the rest stay queued' );
dvx_ok( null !== Queue::paused(), 'the pause is recorded' );
Queue::resume();
$fake->answers['images.optimize'] = new \Davix\H2I\Exceptions\RateLimitException( 'slow', array( 'code' => 'rate_limit_exceeded', 'status' => 429, 'retryable' => true, 'retryAfterMs' => 1000 ) );
$stats = Worker::run( 20 );
$job2 = Queue::find( $j2 );
dvx_ok( 'queued' === $job2['status'] && $job2['run_after'] > time() - 1, 'a rate limit puts the job back with a wait' );
Queue::cancel( $j2 );
dvx_ok( 'cancelled' === Queue::find( $j2 )['status'], 'a queued job can be cancelled' );
Queue::resume();
Queue::retry_failed( 'btest' );
dvx_ok( 'queued' === Queue::find( $j1 )['status'], 'failed jobs can be retried' );

// ---------------------------------------------------------------- automatic rules: uploads only enqueue
use DavixWP\Core\AutoRules;
$dvx_upload = function ( $name, $w = 600, $h = 400 ) {
    $new = AttachmentWriter::import( dvx_make_jpeg( $w, $h ), array( 'filename' => $name, 'mime' => 'image/jpeg' ) );
    // import runs under the guard, so simulate a real upload hook call outside it
    apply_filters( 'wp_generate_attachment_metadata', array(), $new['id'], 'create' );
    return $new['id'];
};
dvx_ok( count( AutoRules::action_ids() ) >= 7 && in_array( 'images.chips', AutoRules::action_ids(), true ), 'the modules register their automatic actions' );
AutoRules::save( array( array( 'action' => 'images.chips', 'enabled' => true, 'generated' => true, 'types' => array( 'image/jpeg' ), 'params' => array( 'chips' => array( 'smart' ), 'fields' => array() ) ) ) );
$calls_before  = count( $fake->calls );
$queued_before = Queue::counts()['queued'];
$up = $dvx_upload( 'dvx-up.jpg' );
dvx_ok( count( $fake->calls ) === $calls_before, 'an upload never calls the service inline' );
dvx_ok( Queue::counts()['queued'] === $queued_before + 1, 'an upload queues exactly one automatic job' );
// rules that do not apply queue nothing
AutoRules::save( array( array( 'action' => 'images.chips', 'enabled' => false, 'types' => array( 'image/jpeg' ), 'params' => array( 'chips' => array( 'smart' ) ) ) ) );
$q = Queue::counts()['queued'];
$dvx_upload( 'dvx-off.jpg' );
dvx_ok( Queue::counts()['queued'] === $q, 'a rule that is switched off does nothing' );
AutoRules::save( array( array( 'action' => 'images.chips', 'enabled' => true, 'types' => array( 'image/png' ), 'params' => array( 'chips' => array( 'smart' ) ) ) ) );
$dvx_upload( 'dvx-type.jpg' );
dvx_ok( Queue::counts()['queued'] === $q, 'a rule for another file type does nothing' );
AutoRules::save( array( array( 'action' => 'images.chips', 'enabled' => true, 'types' => array(), 'min_kb' => 5000, 'params' => array( 'chips' => array( 'smart' ) ) ) ) );
$dvx_upload( 'dvx-small.jpg' );
dvx_ok( Queue::counts()['queued'] === $q, 'a rule with a smallest file size skips smaller files' );
// order and the result mode: privacy first, then the chips; the rule's result is passed on
AutoRules::save( array(
    array( 'action' => 'images.chips', 'enabled' => true, 'generated' => true, 'mode' => 'copy', 'params' => array( 'chips' => array( 'privacy', 'resize', 'watermark' ), 'fields' => array( 'privacy' => array( 'strip_gps' => '1' ), 'resize' => array( 'width' => '800' ), 'watermark' => array( 'text' => 'x' ) ) ) ),
) );
$q = Queue::counts()['queued'];
$up = $dvx_upload( 'dvx-two.jpg' );
$jobs = array_values( array_filter( Queue::query( array(), 1, 10 )['items'], function ( $j ) use ( $up ) { return (int) $j['object_id'] === $up; } ) );
usort( $jobs, function ( $x, $y ) { return $x['id'] - $y['id']; } );
dvx_ok( 1 === count( $jobs ) && 'images.pipeline' === $jobs[0]['operation'], 'all the chosen pixel steps are one pipeline call' );
$params = Queue::find( $jobs[0]['id'] )['params'];
dvx_ok( 'images.metadata-edit' === $params['_then']['operation'] && true === $params['_then']['params']['strip_gps'], 'the privacy chip becomes a second step on the result' );
dvx_ok( 'copy' === $params['_mode'] && 800 === $params['operations'][0]['width'], 'the rule\'s result mode and settings reach the job' );
// a rule that runs when a post is published
AutoRules::save( array( array( 'action' => 'render.social-card', 'enabled' => true, 'types' => array( 'post', 'nope' ), 'params' => array( 'html' => '<h1>{post_title}</h1><script>x()</script>', 'width' => 99999, 'set_featured' => '1' ) ) ) );
$rules = AutoRules::rules();
dvx_ok( array( 'post' ) === $rules[0]['types'] && 5000 === $rules[0]['params']['width'] && false === strpos( $rules[0]['params']['html'], '<script' ) && true === $rules[0]['params']['set_featured'], 'a publish rule keeps post types and cleans its own settings' );
$q = Queue::counts()['queued'];
$pid = wp_insert_post( array( 'post_title' => 'Card test', 'post_status' => 'publish', 'post_type' => 'post' ) );
$card = array_values( array_filter( Queue::query( array(), 1, 20 )['items'], function ( $j ) { return 'render.html-to-image' === $j['operation'] && 'publish' === $j['source']; } ) );
dvx_ok( Queue::counts()['queued'] === $q + 1 && $card && false !== strpos( Queue::find( $card[0]['id'] )['params']['html'], 'Card test' ), 'publishing a post queues the card with its tokens filled in' );
wp_delete_post( $pid, true );
AutoRules::save( array() );
$plan = \DavixWP\Modules\Images\Chips::build( array( 'privacy' ), array( 'privacy' => array( 'strip_gps' => '1' ) ) );
dvx_ok( 'images.metadata-edit' === $plan['operation'], 'the privacy chip alone is one metadata call' );
// a new image placed into the rule's HTML
AutoRules::save( array( array( 'action' => 'render.image-card', 'enabled' => true, 'generated' => true, 'params' => array( 'width' => 800 ) ) ) );
$up = $dvx_upload( 'dvx-frame.jpg' );
$jobs = array_values( array_filter( Queue::query( array(), 1, 20 )['items'], function ( $j ) use ( $up ) { return (int) $j['object_id'] === $up && 'render.html-to-image' === $j['operation']; } ) );
dvx_ok( 1 === count( $jobs ), 'an uploaded image queues the HTML render with the image as input' );
$jp = Queue::find( $jobs[0]['id'] )['params'];
$filled = apply_filters( 'davix_h2i_operation_params', array( 'html' => $jp['html'] ), 'render.html-to-image', array( 'object_id' => $up ) );
dvx_ok( false !== strpos( $filled['html'], wp_get_attachment_url( $up ) ) && 800 === $jp['width'], 'the uploaded image address is filled into the HTML when the job runs' );
AutoRules::save( array() );
// the wider set of automatic actions
$ids = array_keys( \DavixWP\Core\Plugin::instance()->context()->auto_actions() );
foreach ( array( 'ai.content.seo', 'ai.documents.summarize', 'ai.content.title', 'pdfs.watermark', 'documents.convert', 'render.post-pdf', 'render.social-card' ) as $want ) {
    dvx_ok( in_array( $want, $ids, true ), 'automatic action exists: ' . $want );
}
// bad rules are dropped by the sanitizer
$saved = AutoRules::save( array( array( 'action' => 'no.such.action' ), array( 'action' => 'images.chips', 'types' => array( 'image/jpeg', '<script>' ), 'min_kb' => -5, 'mode' => 'weird' ) ) );
dvx_ok( 1 === count( $saved ) && array( 'image/jpeg' ) === $saved[0]['types'] && 0 === $saved[0]['min_kb'] && '' === $saved[0]['mode'], 'rules are cleaned: unknown actions, bad types, negative sizes and modes' );
// the older per-module switches become rules, once
delete_option( 'davix_h2i_auto_migrated' );
AutoRules::save( array() );
update_option( 'davix_h2i_images', array( 'auto' => array( 'enabled' => true, 'chips' => array( 'smart', 'resize' ), 'fields' => array( 'resize' => array( 'width' => '1200' ) ), 'types' => array( 'image/jpeg' ), 'min_kb' => 50, 'apply_to_generated' => false ), 'privacy' => array( 'strip_gps' => true ), 'watermark' => array( 'on_upload' => true, 'text' => 'Mine' ), 'output' => array( 'mode' => 'copy' ) ) );
update_option( 'davix_h2i_ai', array( 'upload' => array( 'alt' => true, 'moderate' => false ) ) );
update_option( 'davix_h2i_pdf', array( 'optimize' => array( 'on_upload' => true, 'profile' => 'screen' ), 'thumbnails' => array( 'on_upload' => false ) ) );
AutoRules::maybe_migrate();
$names = array_map( function ( $r ) { return $r['action']; }, AutoRules::rules() );
dvx_ok( array( 'images.chips', 'ai.content.alt-text', 'pdfs.optimize' ) === $names, 'older switches become rules (' . implode( ',', $names ) . ')' );
$chips_rule = AutoRules::rules()[0];
dvx_ok( in_array( 'watermark', $chips_rule['params']['chips'], true ) && 'copy' === $chips_rule['mode'] && 50 === $chips_rule['min_kb'] && ! $chips_rule['generated'], 'the optimize rule keeps its chips, the watermark, the size and the result mode' );
dvx_ok( ! isset( get_option( 'davix_h2i_images' )['auto'] ) && ! isset( get_option( 'davix_h2i_ai' )['upload'] ), 'the older switches are removed after the move' );
Options::reset( 'auto' );
Options::reset( 'images' );

// history filters and the "plugin only" rule
$rows = Repository::query( array( 'group' => 'ai' ), 1, 50 );
dvx_ok( $rows['total'] >= 3 && 0 === count( array_filter( $rows['items'], function ( $r ) { return 0 !== strpos( $r['operation'], 'ai.' ); } ) ), 'History group filter works' );
dvx_ok( Repository::stats()['total'] >= 5, 'History stats count rows' );
$csv_rows = Repository::export_rows( array(), 10 );
dvx_ok( ! empty( $csv_rows ), 'History can be exported' );

// cleanup
foreach ( get_posts( array( 'post_type' => 'attachment', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_davix_h2i_generated' ) ) as $gid ) { // phpcs:ignore
    wp_delete_attachment( $gid, true );
}
wp_delete_attachment( $id, true );
Queue::clear_finished();
global $wpdb;
$wpdb->query( 'DELETE FROM ' . \DavixWP\Core\Installer::jobs_table() ); // phpcs:ignore
ClientFactory::reset();
$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . \DavixWP\Core\Installer::history_table() . ' WHERE id > %d', $dvx_history_start ) ); // phpcs:ignore
foreach ( $dvx_saved_options as $section => $value ) {
    false === $value ? delete_option( 'davix_h2i_' . $section ) : update_option( 'davix_h2i_' . $section, $value, false );
}
false === $dvx_saved_flag ? delete_option( 'davix_h2i_auto_migrated' ) : update_option( 'davix_h2i_auto_migrated', $dvx_saved_flag, false );
Options::flush_cache();
// ---------------------------------------------------------------- plain-language settings, watermark logo
$sum = \DavixWP\Support\ParamSummary::summarize( 'images.optimize', array( 'keepMetadata' => true, 'format' => 'webp', 'quality' => 80, '_mode' => 'copy' ), $ctx->operation( 'images.optimize' ) );
$sum_text = wp_json_encode( $sum );
dvx_ok( false !== strpos( $sum_text, '"Yes"' ) && false !== strpos( $sum_text, 'Saved as a new copy' ), 'history settings: yes/no and the result are written for a person' );
$sum_plain = implode( ' | ', array_map( function ( $r ) {
    return $r['label'] . ': ' . $r['value'];
}, $sum ) );
dvx_ok( false === strpos( $sum_plain, '_mode' ) && false === strpos( $sum_plain, '{' ) && false === strpos( $sum_plain, 'true' ), 'history settings: no internal names and no JSON (' . $sum_plain . ')' );
$wm = \DavixWP\Support\ParamSummary::summarize( 'images.watermark', array( 'watermark' => array( 'text' => 'Hi', 'tile' => true, 'opacity' => 0.5 ) ), $ctx->operation( 'images.watermark' ) );
dvx_ok( count( $wm ) === 3 && 'Yes' === $wm[1]['value'], 'history settings: a group of settings becomes one readable line each' );
$plan = Chips::build( array( 'watermark' ), array( 'watermark' => array( 'text' => 'x', 'image' => '12' ) ) );
dvx_ok( 12 === $plan['params']['_extra']['watermarkImage'] && ! isset( $plan['params']['watermark']['image'] ), 'watermark chip: the logo travels as the watermarkImage file, not as a setting' );
$plan = Chips::build( array( 'resize', 'watermark' ), array( 'resize' => array( 'width' => '800' ), 'watermark' => array( 'image' => '12', 'scale' => '0.3' ) ) );
dvx_ok( 'images.pipeline' === $plan['operation'] && 12 === $plan['params']['_extra']['watermarkImage'], 'watermark chip: the logo also works inside several edits' );

false !== $dvx_saved_raw ? update_option( KeyStore::OPTION, $dvx_saved_raw, false ) : delete_option( KeyStore::OPTION );
delete_option( 'davix_h2i_key_test_backup' );

echo "\n{$GLOBALS['dvx_pass']} passed, {$GLOBALS['dvx_fail']} failed\n";
if ( $GLOBALS['dvx_fail'] ) {
    exit( 1 );
}
