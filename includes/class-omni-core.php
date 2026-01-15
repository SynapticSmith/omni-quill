<?php
/**
* Class OmniQuill_Core
* * Main singleton that initializes the plugin components.
*/
class OmniQuill_Core {

    private static $instance = null;
    public $admin;
    public $ajax_controller;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        // Initialize Sub-components
        $this->admin = new OmniQuill_Admin();
        $this->ajax_controller = new OmniQuill_Ajax_Controller();
    }

    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    public function load_textdomain() {
        // Since this file is in /includes, we need to go up one level to find /languages
        load_plugin_textdomain( 'omni-quill', false, dirname( plugin_basename( __DIR__ ) ) . '/languages' );
    }
}
