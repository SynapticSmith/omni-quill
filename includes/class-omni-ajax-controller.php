<?php
class OmniQuill_Ajax_Controller {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_omni_generate_v2', array( $this, 'handle_generation' ) );
    }

    public function handle_generation() {
        check_ajax_referer( 'wp_rest', 'nonce' ); // Using WP REST nonce for React compatibility usually

        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permission denied' );

        // 1. Get Data
        $input = json_decode( file_get_contents('php://input'), true );
        $prompt = sanitize_text_field( $input['prompt'] ?? '' );
        $history = isset($input['history']) ? $input['history'] : [];
        $use_memory = isset($input['useMemory']) ? (bool)$input['useMemory'] : false;

        // If memory is off, clear history
        if ( ! $use_memory ) {
            $history = [];
        }

        // 2. Get User Config & Decrypt Key
        $user_id = get_current_user_id();
        $api_key_enc = get_user_meta( $user_id, 'omni_user_api_key', true );
        $api_key = OmniQuill_Security::decrypt( $api_key_enc );
        $provider = get_option( 'omni_global_provider', 'gemini' );

        if ( empty( $api_key ) ) wp_send_json_error( 'API Key missing or invalid.' );

        // 3. Dispatch
        $response = OmniQuill_LLM_Gateway::dispatch_text(
            $provider,
            $prompt,
            $history,
            $api_key,
            [ 'model' => $input['model'] ?? '' ]
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        wp_send_json_success( [ 'content' => $response ] );
    }
}
