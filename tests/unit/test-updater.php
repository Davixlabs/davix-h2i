<?php
/**
 * Self-test of the update code (src/Updates). Standalone: it stubs the few WordPress functions it uses, so it needs neither WordPress nor a database.
 *
 *   php tests/unit/test-updater.php
 */

namespace {
    define( 'ABSPATH', __DIR__ . '/' );
    define( 'DAVIX_H2I_VERSION', '2.1.0' );
    define( 'DAVIX_H2I_BASENAME', 'davix-h2i/davix-h2i.php' );
    define( 'DAVIX_H2I_MIN_PHP', '7.4' );

    $GLOBALS['t'] = array( 'transients' => array(), 'http' => array(), 'filters' => array(), 'deleted' => array(), 'downloads' => array() );

    class WP_Error { public $code; public $message; public function __construct( $code = '', $message = '' ) { $this->code = $code; $this->message = $message; } }
    function is_wp_error( $v ) { return $v instanceof WP_Error; }
    function get_transient( $k ) { return $GLOBALS['t']['transients'][ $k ] ?? false; }
    function set_transient( $k, $v, $ttl = 0 ) { $GLOBALS['t']['transients'][ $k ] = $v; $GLOBALS['t']['ttl'][ $k ] = $ttl; return true; }
    function delete_transient( $k ) { unset( $GLOBALS['t']['transients'][ $k ] ); return true; }
    function add_filter( $tag, $cb, $prio = 10, $args = 1 ) { $GLOBALS['t']['filters'][ $tag ] = $cb; }
    function add_action( $tag, $cb, $prio = 10, $args = 1 ) { $GLOBALS['t']['filters'][ $tag ] = $cb; }
    function __( $s ) { return $s; }
    function esc_html__( $s ) { return htmlspecialchars( $s ); }
    function esc_html( $s ) { return htmlspecialchars( (string) $s ); }
    function wp_kses_post( $s ) { return $s; }
    // http: the test says what each address answers
    function wp_remote_get( $url, $args = array() ) {
        $GLOBALS['t']['requested'][] = $url;
        return $GLOBALS['t']['http'][ $url ] ?? new WP_Error( 'http', 'no answer' );
    }
    function wp_remote_retrieve_response_code( $r ) { return $r['code'] ?? 0; }
    function wp_remote_retrieve_body( $r ) { return $r['body'] ?? ''; }
    function wp_remote_retrieve_header( $r, $h ) { return $r['headers'][ $h ] ?? ''; }
    function download_url( $url, $timeout = 300 ) {
        $GLOBALS['t']['downloads'][] = $url;
        $content = $GLOBALS['t']['zip'][ $url ] ?? null;
        if ( null === $content ) { return new WP_Error( 'download', 'no file' ); }
        $f = tempnam( sys_get_temp_dir(), 'dzip' ); file_put_contents( $f, $content ); return $f;
    }
    function wp_delete_file( $f ) { $GLOBALS['t']['deleted'][] = $f; @unlink( $f ); }

    require __DIR__ . '/../../src/Updates/ReleaseSource.php';
    require __DIR__ . '/../../src/Updates/Updater.php';
    use DavixWP\Updates\ReleaseSource;
    use DavixWP\Updates\Updater;

    $passes = 0; $failures = 0;
    function check( $name, $ok, $detail = '' ) {
        global $passes, $failures;
        if ( $ok ) { $passes++; echo "PASS  $name\n"; } else { $failures++; echo "FAIL  $name" . ( $detail ? " -- $detail" : '' ) . "\n"; }
    }
    function release( $tag, $assets = null, $extra = array() ) {
        $v = ltrim( $tag, 'v' );
        $assets = null === $assets ? array( "davix-h2i-$v.zip", "davix-h2i-$v.zip.sha256" ) : $assets;
        return array_merge( array( 'tag_name' => $tag, 'draft' => false, 'prerelease' => false, 'body' => "What changed\n- a fix", 'published_at' => '2026-10-22T10:00:00Z', 'assets' => array_map( function ( $n ) { return array( 'name' => $n ); }, $assets ) ), $extra );
    }
    function reset_state() { $GLOBALS['t']['transients'] = array(); $GLOBALS['t']['http'] = array(); $GLOBALS['t']['requested'] = array(); $GLOBALS['t']['zip'] = array(); $GLOBALS['t']['downloads'] = array(); $GLOBALS['t']['deleted'] = array(); }
    $api  = 'https://api.github.com/repos/Davixlabs/davix-h2i/releases/latest';
    $page = 'https://github.com/Davixlabs/davix-h2i/releases/latest';

    // ---- reading a release ---------------------------------------------------------------------------------------------------------------
    $r = ReleaseSource::parse( release( 'v2.2.0' ) );
    check( 'a published release with its zip and checksum is understood', is_array( $r ) && '2.2.0' === $r['version'] && 'What changed' === explode( "\n", $r['notes'] )[0] );
    check( 'the download address is built here, from the version', 'https://github.com/Davixlabs/davix-h2i/releases/download/v2.2.0/davix-h2i-2.2.0.zip' === $r['package'] && $r['package'] . '.sha256' === $r['checksum'] );
    check( 'a draft and a pre-release are never offered', null === ReleaseSource::parse( release( 'v2.2.0', null, array( 'draft' => true ) ) ) && null === ReleaseSource::parse( release( 'v2.2.0', null, array( 'prerelease' => true ) ) ) );
    check( 'a release still being built (no checksum yet, or no zip) is not offered yet', null === ReleaseSource::parse( release( 'v2.2.0', array( 'davix-h2i-2.2.0.zip' ) ) ) && null === ReleaseSource::parse( release( 'v2.2.0', array( 'davix-h2i-2.2.0.zip.sha256' ) ) ) && null === ReleaseSource::parse( release( 'v2.2.0', array() ) ) );
    check( 'a zip of another version does not count', null === ReleaseSource::parse( release( 'v2.2.0', array( 'davix-h2i-2.1.0.zip', 'davix-h2i-2.1.0.zip.sha256' ) ) ) );
    check( 'only tags like v2.2.0 count', null === ReleaseSource::parse( release( '2.2.0' ) ) && null === ReleaseSource::parse( release( 'v2.2' ) ) && null === ReleaseSource::parse( release( 'v2.2.0-beta' ) ) && null === ReleaseSource::parse( release( 'latest' ) ) );
    check( 'the page of the latest release names the version through its redirect', '2.3.1' === ReleaseSource::from_location( 'https://github.com/Davixlabs/davix-h2i/releases/tag/v2.3.1' )['version'] );
    check( 'a redirect to anywhere else is ignored', null === ReleaseSource::from_location( 'https://evil.example/Davixlabs/davix-h2i/releases/tag/v9.9.9' ) && null === ReleaseSource::from_location( 'https://github.com/other/repo/releases/tag/v9.9.9' ) && null === ReleaseSource::from_location( '' ) );

    // ---- asking GitHub, caching, falling back ----------------------------------------------------------------------------------------------
    reset_state();
    $GLOBALS['t']['http'][ $api ] = array( 'code' => 200, 'body' => json_encode( release( 'v2.2.0' ) ) );
    check( 'the newest release is read from the API and cached for hours', '2.2.0' === ReleaseSource::latest()['version'] && $GLOBALS['t']['ttl']['davix_h2i_latest_release'] >= 3600 );
    ReleaseSource::latest(); ReleaseSource::latest();
    check( 'the cache is used: GitHub is asked once', 1 === count( $GLOBALS['t']['requested'] ) );
    reset_state();
    $GLOBALS['t']['http'][ $api ]  = array( 'code' => 403, 'body' => '{"message":"API rate limit exceeded"}' );
    $GLOBALS['t']['http'][ $page ] = array( 'code' => 302, 'headers' => array( 'location' => 'https://github.com/Davixlabs/davix-h2i/releases/tag/v2.4.0' ), 'body' => '' );
    check( 'when the API limit is hit the release page is asked instead', '2.4.0' === ReleaseSource::latest()['version'] );
    reset_state();
    check( 'when GitHub cannot be reached nothing is offered, and the failure is remembered for a short time', null === ReleaseSource::latest() && $GLOBALS['t']['ttl']['davix_h2i_latest_release'] <= 3600 );
    $before = count( $GLOBALS['t']['requested'] ); ReleaseSource::latest();
    check( 'a remembered failure is not asked again straight away', count( $GLOBALS['t']['requested'] ) === $before );

    // ---- what WordPress is told ----------------------------------------------------------------------------------------------------------
    reset_state();
    $GLOBALS['t']['http'][ $api ] = array( 'code' => 200, 'body' => json_encode( release( 'v2.2.0' ) ) );
    $offer = Updater::offer( false, array(), DAVIX_H2I_BASENAME, array() );
    check( 'a newer version is offered with its zip', is_array( $offer ) && '2.2.0' === $offer['version'] && 'davix-h2i' === $offer['slug'] && 0 === strpos( $offer['package'], 'https://github.com/Davixlabs/davix-h2i/releases/download/v2.2.0/' ) );
    check( 'another plugin\'s update question is left alone', 'x' === Updater::offer( 'x', array(), 'other/other.php', array() ) );
    reset_state();
    $GLOBALS['t']['http'][ $api ] = array( 'code' => 200, 'body' => json_encode( release( 'v2.1.0' ) ) );
    check( 'the same version is not offered', false === Updater::offer( false, array(), DAVIX_H2I_BASENAME, array() ) );
    reset_state();
    $GLOBALS['t']['http'][ $api ] = array( 'code' => 200, 'body' => json_encode( release( 'v2.0.0' ) ) );
    check( 'an older version is never offered (no going back)', false === Updater::offer( false, array(), DAVIX_H2I_BASENAME, array() ) );
    reset_state();
    $GLOBALS['t']['http'][ $api ] = array( 'code' => 200, 'body' => json_encode( release( 'v2.10.0' ) ) );
    check( 'versions compare as numbers: 2.10.0 is newer than 2.9.0 and than 2.1.0', is_array( Updater::offer( false, array(), DAVIX_H2I_BASENAME, array() ) ) );
    $info = Updater::details( false, 'plugin_information', (object) array( 'slug' => 'davix-h2i' ) );
    check( 'the View details window has the version, the zip and the changelog', is_object( $info ) && '2.10.0' === $info->version && '' !== $info->download_link && false !== strpos( $info->sections['changelog'], 'a fix' ) );
    check( 'the View details window answers only for this plugin', 'x' === Updater::details( 'x', 'plugin_information', (object) array( 'slug' => 'other' ) ) && 'x' === Updater::details( 'x', 'query_plugins', (object) array( 'slug' => 'davix-h2i' ) ) );

    // ---- installing: the zip must match its checksum ----------------------------------------------------------------------------------------
    reset_state();
    $zip = 'PK-pretend-zip-content';
    $url = ReleaseSource::package_url( '2.2.0' );
    $GLOBALS['t']['zip'][ $url ] = $zip;
    $GLOBALS['t']['http'][ $url . '.sha256' ] = array( 'code' => 200, 'body' => hash( 'sha256', $zip ) . "  davix-h2i-2.2.0.zip\n" );
    $file = Updater::verify_download( false, $url, null, array() );
    check( 'a zip that matches its checksum is handed to WordPress', is_string( $file ) && is_file( $file ) );
    reset_state();
    $GLOBALS['t']['zip'][ $url ] = $zip . 'tampered';
    $GLOBALS['t']['http'][ $url . '.sha256' ] = array( 'code' => 200, 'body' => hash( 'sha256', $zip ) . "  davix-h2i-2.2.0.zip\n" );
    $bad = Updater::verify_download( false, $url, null, array() );
    check( 'a zip that does not match is refused and deleted', is_wp_error( $bad ) && 'davix_h2i_checksum' === $bad->code && 1 === count( $GLOBALS['t']['deleted'] ) );
    reset_state();
    $GLOBALS['t']['zip'][ $url ] = $zip;
    $none = Updater::verify_download( false, $url, null, array() );
    check( 'without a readable checksum nothing is installed, and the zip is not even downloaded', is_wp_error( $none ) && 'davix_h2i_checksum_missing' === $none->code && array() === $GLOBALS['t']['downloads'] );
    check( 'a package from another address is not touched here', 'orig' === Updater::verify_download( 'orig', 'https://example.com/other.zip', null, array() ) );
    $odd = Updater::verify_download( false, 'https://github.com/Davixlabs/davix-h2i/releases/download/v2.2.0/other-name.zip', null, array() );
    check( 'an address under this repository that is not the release zip is refused', is_wp_error( $odd ) && 'davix_h2i_package' === $odd->code );

    // ---- wiring ----------------------------------------------------------------------------------------------------------------------------
    Updater::register();
    check( 'the update question goes through update_plugins_github.com (the plugin header says Update URI: github.com)', isset( $GLOBALS['t']['filters']['update_plugins_github.com'] ) );
    $header = file_get_contents( __DIR__ . '/../../davix-h2i.php' );
    check( 'the plugin header carries the Update URI, so wordpress.org is never asked about this plugin', false !== strpos( $header, ' * Update URI: https://github.com/Davixlabs/davix-h2i' ) );
    check( 'the updater is registered at boot', false !== strpos( $header, 'Updater::register()' ) );

    echo "\n$passes passed, $failures failed\n";
    exit( $failures ? 1 : 0 );
}
