<?php
/**
 * Base class of every feature module (src/Modules/<Name>/<Name>Module.php).
 *
 * A module is self-contained: it may use the core services (Options, Services, Connection, Capabilities, Jobs, Media,
 * History, Admin components) but never another module. It describes itself to the core through the Context.
 */

namespace DavixWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Module {

    /** @var array The module manifest (module.php) */
    protected $manifest = array();

    public function __construct( array $manifest = array() ) {
        $this->manifest = $manifest;
    }

    public function id() {
        return isset( $this->manifest['id'] ) ? (string) $this->manifest['id'] : '';
    }

    public function manifest() {
        return $this->manifest;
    }

    /** Registers operations, tabs, settings, REST routes, CLI commands, drawer tabs, assets and hooks. */
    abstract public function register( Context $context );
}
