<?php

namespace DavixWP\Modules\Images;

use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * What happens to a replaced file: a backup is kept, so History can restore it. There is no screen for this; the choice
 * between replacing and saving a copy is made on each run (and on each automatic rule).
 */
final class Settings {

    public static function register() {
        Options::register(
            'images',
            array( 'output' => array( 'mode' => 'replace', 'suffix' => '_optimized', 'backup' => true ) ),
            array( __CLASS__, 'sanitize' )
        );
    }

    public static function sanitize( array $v, array $d ) {
        $mode = isset( $v['output']['mode'] ) && 'copy' === $v['output']['mode'] ? 'copy' : 'replace';
        return array(
            'output' => array(
                'mode'   => $mode,
                'suffix' => isset( $v['output']['suffix'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $v['output']['suffix'] ) : '_optimized',
                'backup' => ! isset( $v['output']['backup'] ) || ! empty( $v['output']['backup'] ),
            ),
        );
    }
}
