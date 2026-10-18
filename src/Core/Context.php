<?php
/**
 * What the core knows about the loaded modules: the operations, tabs, sub-tabs, drawer tabs, settings, REST routes,
 * CLI commands and assets they registered. The core never names a module; it only reads this.
 */

namespace DavixWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Context {

    /** @var array<string,array> */
    private $operations = array();
    /** @var array<string,array> */
    private $tabs = array();
    /** @var array<string,array<string,array>> */
    private $subtabs = array();
    /** @var array<string,array> */
    private $drawer_tabs = array();
    /** @var callable[] */
    private $rest = array();
    /** @var array<string,string> */
    private $cli = array();
    /** @var array<string,array> */
    private $assets = array();
    /** @var array<string,array> */
    private $modules = array();
    /** @var array<string,array> */
    private $auto_actions = array();

    // ------------------------------------------------------------- modules
    public function add_module( $id, array $manifest ) {
        $this->modules[ $id ] = $manifest;
    }

    public function modules() {
        return $this->modules;
    }

    // ---------------------------------------------------------- operations
    /**
     * @param array $definition id (contract id), label, group, input, result, store, fields (names), accepts, ...
     */
    public function add_operation( array $definition ) {
        $definition               = \DavixWP\Operations\Definition::normalize( $definition );
        $this->operations[ $definition['id'] ] = $definition;
    }

    /** @return array<string,array> */
    public function operations() {
        return $this->operations;
    }

    public function operation( $id ) {
        return isset( $this->operations[ $id ] ) ? $this->operations[ $id ] : null;
    }

    // ----------------------------------------------------------- tabs
    /**
     * @param array $tab id, label, icon (dashicon), group (work|manage|account), order, render (callable), capability
     */
    public function add_tab( array $tab ) {
        $tab = array_merge(
            array(
                'group'      => 'work',
                'order'      => 50,
                'icon'       => 'dashicons-admin-generic',
                'capability' => 'upload_files',
                'title'      => '',
                'desc'       => '',
                'doc'        => '',
                'picker'     => false,
            ),
            $tab
        );
        $this->tabs[ $tab['id'] ] = $tab;
    }

    /** @return array<string,array> sorted by group then order */
    public function tabs() {
        $groups = array( 'work' => 0, 'manage' => 1, 'account' => 2 );
        $tabs   = $this->tabs;
        uasort(
            $tabs,
            function ( $a, $b ) use ( $groups ) {
                $ga = isset( $groups[ $a['group'] ] ) ? $groups[ $a['group'] ] : 9;
                $gb = isset( $groups[ $b['group'] ] ) ? $groups[ $b['group'] ] : 9;
                if ( $ga !== $gb ) {
                    return $ga - $gb;
                }
                return $a['order'] - $b['order'];
            }
        );
        return $tabs;
    }

    public function tab( $id ) {
        return isset( $this->tabs[ $id ] ) ? $this->tabs[ $id ] : null;
    }

    /**
     * Adds a sub-tab to a tab. A module may add a sub-tab to a tab owned by another module by id (loose coupling
     * through the registry; it does not import the other module).
     *
     * @param array $subtab id, label, order, render (callable), operations (ids that gate it), beta
     */
    public function add_subtab( $tab_id, array $subtab ) {
        $subtab = array_merge(
            array(
                'order'      => 50,
                'operations' => array(),
                'title'      => '',
                'desc'       => '',
                'save'       => '',
            ),
            $subtab
        );
        $this->subtabs[ $tab_id ][ $subtab['id'] ] = $subtab;
    }

    public function subtabs( $tab_id ) {
        $list = isset( $this->subtabs[ $tab_id ] ) ? $this->subtabs[ $tab_id ] : array();
        uasort(
            $list,
            function ( $a, $b ) {
                return $a['order'] - $b['order'];
            }
        );
        return $list;
    }

    // ----------------------------------------------------------- drawer
    /**
     * A tab of the Media Library drawer.
     *
     * @param array $tab id, label, order, mimes (e.g. array('image/'), array('application/pdf')), render (callable), script handle
     */
    public function add_drawer_tab( array $tab ) {
        $tab = array_merge( array( 'order' => 50, 'mimes' => array(), 'operations' => array() ), $tab );
        $this->drawer_tabs[ $tab['id'] ] = $tab;
    }

    public function drawer_tabs() {
        $tabs = $this->drawer_tabs;
        uasort(
            $tabs,
            function ( $a, $b ) {
                return $a['order'] - $b['order'];
            }
        );
        return $tabs;
    }

    // ------------------------------------------------------------- rest / cli
    /** @param callable $registrar receives the REST namespace string. */
    public function add_rest( $registrar ) {
        $this->rest[] = $registrar;
    }

    public function rest_registrars() {
        return $this->rest;
    }

    public function add_cli( $name, $class ) {
        $this->cli[ $name ] = $class;
    }

    public function cli_commands() {
        return $this->cli;
    }

    // ------------------------------------------------------------- assets
    /**
     * @param string $handle  unique handle, e.g. davix-h2i-images
     * @param array  $asset   css (list of paths under assets/css), js (list under assets/js), deps, screens (tab:<id>, drawer, editor), data (callable)
     */
    public function add_assets( $handle, array $asset ) {
        $this->assets[ $handle ] = array_merge(
            array(
                'css'     => array(),
                'js'      => array(),
                'deps'    => array(),
                'screens' => array(),
                'data'    => null,
            ),
            $asset
        );
    }

    public function assets() {
        return $this->assets;
    }

    // --------------------------------------------------------- automatic actions
    /**
     * An action that can run on every new upload ("Automatic" rules). Usually the id is an operation id; a module may also
     * register a made-up id (for example the image "chips") with its own form and its own way of turning settings into a call.
     *
     * @param array $action id, label, description, group, order, accepts (mime prefixes), types (mime => label shown as options),
     *                      operation (the real operation, when the id is not one), form (callable printing the settings form),
     *                      plan (callable( params, attachment_id ): array( operation, params )|null), skip (callable( attachment_id, params ): bool),
     *                      choose_mode (show the "replace or copy" choice), trigger (upload: a new file; publish: a post is published, then "types" are post types and
     *                      plan( params, post_id ) may return object_id 0), sanitize (callable( params ): array, cleans the action's own settings)
     */
    public function add_auto_action( array $action ) {
        $action = array_merge(
            array(
                'label'       => '',
                'description' => '',
                'group'       => '',
                'order'       => 50,
                'accepts'     => array( 'image/' ),
                'types'       => array(),
                'operation'   => '',
                'form'        => null,
                'plan'        => null,
                'skip'        => null,
                'choose_mode' => false,
                'trigger'     => 'upload',
                'sanitize'    => null,
            ),
            $action
        );
        if ( '' === $action['label'] && ! empty( $action['id'] ) && isset( $this->operations[ $action['id'] ] ) ) {
            $action['label']       = $this->operations[ $action['id'] ]['label'];
            $action['description'] = $action['description'] ? $action['description'] : ( isset( $this->operations[ $action['id'] ]['description'] ) ? $this->operations[ $action['id'] ]['description'] : '' );
        }
        $this->auto_actions[ $action['id'] ] = $action;
    }

    /** @return array<string,array> sorted by order */
    public function auto_actions() {
        $list = $this->auto_actions;
        uasort(
            $list,
            function ( $a, $b ) {
                return $a['order'] - $b['order'];
            }
        );
        return $list;
    }
}
