<?php
/**
 * Plugin Name: OmniQuill
 * Description: Multimodal LLM Integration Layer. Orchestrates text, vision, and code generation within the Block Editor via Gemini and OpenAI APIs.
 * Version: 1.0.2
 * Author: SynapticSmith
 * Text Domain: omni-quill
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Constants
define( 'OMNI_VERSION', '1.0.2' );
define( 'OMNI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OMNI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload Classes
require_once OMNI_PLUGIN_DIR . 'includes/class-omni-core.php';
require_once OMNI_PLUGIN_DIR . 'includes/class-omni-llm-gateway.php';
require_once OMNI_PLUGIN_DIR . 'includes/class-omni-context-engine.php';
require_once OMNI_PLUGIN_DIR . 'includes/class-omni-ajax-controller.php';
require_once OMNI_PLUGIN_DIR . 'admin/class-omni-admin.php';

// Initialize the Core Singleton
function omni_quill_init() {
    OmniQuill_Core::get_instance();
}
add_action( 'plugins_loaded', 'omni_quill_init' );
