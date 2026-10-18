<?php
/**
 * A recursion guard: while the plugin itself rewrites an attachment, upload hooks must not queue new work for it.
 */

namespace DavixWP\Media;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Guard {

    /** @var int */
    private static $depth = 0;

    public static function enter() {
        self::$depth++;
    }

    public static function leave() {
        self::$depth = max( 0, self::$depth - 1 );
    }

    public static function active() {
        return self::$depth > 0;
    }
}
