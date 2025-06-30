<?php
/**
 * Plugin Name: MP AI Content Generator
 * Description: Integrates AI content generation into the WordPress gutenberg post editor.
 * Version: 1.0.1
 * Author: Mayank Pandya
 * Requires at least: 5.8
 * License: GPL-2.0+
 * Requires PHP: 7.4
 * Text Domain: mp-ai-content-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
if ( ! defined( 'MP_AI_PLUGIN_DIR' ) ) {
    define( 'MP_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'MP_AI_PLUGIN_URL' ) ) {
    define( 'MP_AI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// --- Include Admin Settings Page ---
$settings_page_path = MP_AI_PLUGIN_DIR . 'admin/settings-page.php';
if ( file_exists( $settings_page_path ) ) {
    require_once $settings_page_path;
} else {
    //error_log( 'Your AI Plugin Error: admin/settings-page.php not found at ' . $settings_page_path );
}

//  Enqueue Scripts and Styles for the Post Editor 
function mp_ai_plugin_enqueue_editor_assets( $hook_suffix ) {
    // Only load assets on post/page edit screens
    if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
        return;
    }

    // Enqueue JavaScript for post editor integration
    wp_enqueue_script(
        'mp-ai-editor-script',
        MP_AI_PLUGIN_URL . 'assets/js/editor-integration.js',
        array( 'jquery' ), // Dependency on jQuery. For Gutenberg, 'wp-element' and 'wp-editor' might be more appropriate.
        '1.0.1', // Version number to help with caching
        true // Load in footer
    );

    // Pass data to JavaScript (e.g., AJAX URL, nonce)
    wp_localize_script(
        'mp-ai-editor-script',
        'mpAiPluginData',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'mp_ai_plugin_nonce' ),
        )
    );

    // Enqueue CSS for editor (optional, for styling your meta box)
    wp_enqueue_style(
        'mp-ai-editor-style',
        MP_AI_PLUGIN_URL . 'assets/css/editor-styles.css',
        array(),
        '1.0.1' // Version number to help with caching
    );
}
add_action( 'admin_enqueue_scripts', 'mp_ai_plugin_enqueue_editor_assets' );

//  Add Custom Meta Box to Post Editor 
function mp_ai_plugin_add_meta_box() {
    // Debugging: Log if this function is actually being called
    //error_log( 'Your AI Plugin: mp_ai_plugin_add_meta_box function called.' );

    add_meta_box(
        'mp_ai_content_box', // ID of the meta box
        __( 'AI Content Generator', 'mp-ai-content-generator' ), // Title of the meta box
        'mp_ai_plugin_meta_box_callback', // Callback function to render the box
        array( 'post', 'page' ), // Post types to display on
        'side', // Context: 'normal', 'side', 'advanced'. 'side' is usually good for small metaboxes.
                // 'categorydiv' is a valid context but sometimes can be tricky if the 'categorydiv' itself is removed or moved by other plugins.
        'default' // Priority: 'high', 'core', 'default', 'low'
    );
}
add_action( 'add_meta_boxes', 'mp_ai_plugin_add_meta_box' );

//  Callback Function to Render the Meta Box Content 
function mp_ai_plugin_meta_box_callback( $post ) {
    // Add a nonce field so we can check it later when the form is submitted
    wp_nonce_field( 'mp_ai_plugin_generate_content', 'mp_ai_plugin_generate_content_nonce' );
    ?>
    <p>
        <label for="mp_ai_prompt">Write your prompt here:</label><br>
        <textarea id="mp_ai_prompt" name="mp_ai_prompt" rows="5" cols="50" class="large-text"></textarea>
    </p>
    <p>
        <button type="button" id="mp_ai_generate_button" class="button button-primary">Generate Content with AI</button>
        <span id="mp_ai_loading_indicator" style="display:none;">Loading...</span>
    </p>
    <?php
}

//  AJAX Handler for Generating Content 
function mp_ai_plugin_generate_content_ajax() {
    // Check nonce for security
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

    // Check nonce for security
    if ( ! wp_verify_nonce( $nonce, 'mp_ai_plugin_nonce' ) ) {
        wp_send_json_error( 'Nonce verification failed.' );
    }

    // Check user capabilities
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'You do not have permission to perform this action.' );
    }

    $raw_prompt = filter_input( INPUT_POST, 'prompt', FILTER_UNSAFE_RAW );
    $prompt = sanitize_textarea_field( wp_unslash( $raw_prompt ) );

    if ( empty( $prompt ) ) {
        wp_send_json_error( 'Prompt cannot be empty.' );
    }

    // Retrieve API key from plugin settings
    $api_key = get_option( 'mp_ai_plugin_api_key' );
    if ( empty( $api_key ) ) {
        wp_send_json_error( 'AI API Key is not set. Please configure in plugin settings.' );
    }

    // Determine which AI model to use (Gemini/ChatGPT) - based on a setting
    $ai_model = get_option( 'mp_ai_plugin_ai_model', 'gemini' ); // Default to Gemini

    $ai_response = '';
    try {
        if ( $ai_model === 'gemini' ) {
            $ai_response = mp_ai_plugin_call_gemini_api( $prompt, $api_key );
        } elseif ( $ai_model === 'chatgpt' ) {
            $ai_response = mp_ai_plugin_call_chatgpt_api( $prompt, $api_key );
        } else {
            throw new Exception( 'Invalid AI model selected.' );
        }
    } catch ( Exception $e ) {
        //error_log( 'Your AI Plugin API Error: ' . $e->getMessage() ); // Log the error for debugging
        wp_send_json_error( 'AI API Error: ' . $e->getMessage() );
    }

    if ( ! empty( $ai_response ) ) {
        wp_send_json_success( $ai_response );
    } else {
        wp_send_json_error( 'Failed to get a response from the AI.' );
    }
}
add_action( 'wp_ajax_mp_ai_plugin_generate_content', 'mp_ai_plugin_generate_content_ajax' );

// --- Function to Call Gemini API ---
function mp_ai_plugin_call_gemini_api( $prompt, $api_key ) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;
    $body = json_encode([
        'contents' => [
            [
                'role' => 'user',
                'parts' => [['text' => $prompt]]
            ]
        ]
    ]);

    $args = array(
        'body'        => $body,
        'headers'     => array( 'Content-Type' => 'application/json' ),
        'method'      => 'POST',
        'timeout'     => 45, // seconds
        'blocking'    => true,
        'data_format' => 'body',
    );

    $response = wp_remote_post( $url, $args );

    if ( is_wp_error( $response ) ) {
        throw new Exception( esc_html( $response->get_error_message() ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    } elseif ( isset( $data['error']['message'] ) ) {
        throw new Exception( esc_html( $data['error']['message'] ) );
    }

    throw new Exception( 'Unexpected Gemini API response structure.' );
}

//  Function to Call ChatGPT API (OpenAI) 
function mp_ai_plugin_call_chatgpt_api( $prompt, $api_key ) {
    $url = "https://api.openai.com/v1/chat/completions";
    $body = json_encode([
        'model' => 'gpt-3.5-turbo', // or 'gpt-4', etc.
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ]);

    $args = array(
        'body'        => $body,
        'headers'     => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'method'      => 'POST',
        'timeout'     => 45, // seconds
        'blocking'    => true,
        'data_format' => 'body',
    );

    $response = wp_remote_post( $url, $args );

    if ( is_wp_error( $response ) ) {
        throw new Exception( esc_html( $response->get_error_message() ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( isset( $data['choices'][0]['message']['content'] ) ) {
        return $data['choices'][0]['message']['content'];
    } elseif ( isset( $data['error']['message'] ) ) {
        throw new Exception( esc_html( $data['error']['message'] ) );
    }

    throw new Exception( 'Unexpected ChatGPT API response structure.' );
}
