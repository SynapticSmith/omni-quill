<?php
/**
 * Plugin Name: OmniQuill Pro
 * Description: Enterprise-grade Multimodal AI integration for WordPress. Features encrypted security, polymorhpic provider architecture, and native React integration.
 * Version: 2.0.0
 * Author: SynapticSmith
 * Text Domain: omni-quill
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Constants
define( 'OMNI_VERSION', '2.0.0' );
define( 'OMNI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OMNI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Define Encryption Salt (Fallback if not in wp-config.php)
if ( ! defined( 'OMNI_SALT' ) ) {
    define( 'OMNI_SALT', 'replace_this_with_a_long_random_string_in_wp_config' );
}

// Autoload Classes
require_once OMNI_PLUGIN_DIR . 'includes/class-omni-security.php';
require_once OMNI_PLUGIN_DIR . 'includes/interfaces/interface-omni-ai-provider.php';
require_once OMNI_PLUGIN_DIR . 'includes/providers/class-omni-provider-gemini.php';
require_once OMNI_PLUGIN_DIR . 'includes/providers/class-omni-provider-openai.php';
require_once OMNI_PLUGIN_DIR . 'includes/class-omni-llm-gateway.php';
require_once OMNI_PLUGIN_DIR . 'includes/class-omni-context-engine.php'; // (Assuming this file remains from v1)
require_once OMNI_PLUGIN_DIR . 'includes/class-omni-ajax-controller.php';
require_once OMNI_PLUGIN_DIR . 'admin/class-omni-admin.php';

// Initialize the Core Singleton
function omni_quill_init() {
    OmniQuill_Admin::get_instance();
    OmniQuill_Ajax_Controller::get_instance();
}
add_action( 'plugins_loaded', 'omni_quill_init' );
