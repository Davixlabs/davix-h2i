<?php
/**
 * Finds the modules in src/Modules and lets each one register itself. A module is a folder with a manifest
 * (module.php returning an array) and a class <Folder>Module extending Core\Module.
 */

namespace DavixWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ModuleLoader {

    /** @var string */
    private $dir;

    public function __construct( $dir ) {
        $this->dir = rtrim( $dir, '/\\' ) . '/';
    }

    /** @return array<string,array> manifests by module id, with 'dir' and 'class' added */
    public function discover() {
        $found = array();
        $dirs  = glob( $this->dir . '*', GLOB_ONLYDIR );
        if ( ! is_array( $dirs ) ) {
            return $found;
        }
        foreach ( $dirs as $path ) {
            $name     = basename( $path );
            $manifest = $path . '/module.php';
            if ( ! is_file( $manifest ) ) {
                continue;
            }
            $data = include $manifest;
            if ( ! is_array( $data ) || empty( $data['id'] ) ) {
                continue;
            }
            $data['dir']   = $path;
            $data['class'] = '\\DavixWP\\Modules\\' . $name . '\\' . $name . 'Module';
            $found[ $data['id'] ] = $data;
        }
        uasort(
            $found,
            function ( $a, $b ) {
                $oa = isset( $a['order'] ) ? $a['order'] : 50;
                $ob = isset( $b['order'] ) ? $b['order'] : 50;
                return $oa - $ob;
            }
        );
        return $found;
    }

    public function load( Context $context ) {
        $manifests = $this->discover();
        /**
         * Lets a site switch modules off: return the ids to load.
         *
         * @param string[] $ids
         */
        $enabled = apply_filters( 'davix_h2i_modules', array_keys( $manifests ) );
        foreach ( $manifests as $id => $manifest ) {
            if ( ! in_array( $id, $enabled, true ) || ! class_exists( $manifest['class'] ) ) {
                continue;
            }
            $module = new $manifest['class']( $manifest );
            $context->add_module( $id, $manifest );
            $module->register( $context );
        }
    }
}
