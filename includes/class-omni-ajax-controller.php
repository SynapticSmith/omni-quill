<?php
/**
 * Class OmniQuill_Ajax_Controller
 * * Handles all AJAX endpoints, verifying nonces and capabilities
 * before routing to the LLM Gateway or Context Engine.
 */
class OmniQuill_Ajax_Controller {

    public function __construct() {
        add_action( 'wp_ajax_omni_plugin_generate_content', array( $this, 'handle_generate_content' ) );
        add_action( 'wp_ajax_omni_list_models', array( $this, 'handle_list_models' ) );
    }

    /**
     * Handle the generation request (Text/Code/Image).
     */
    public function handle_generate_content() {
        check_ajax_referer( 'omni_plugin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permission denied.' );

        $prompt    = sanitize_textarea_field( $_POST['prompt'] ?? '' );
        $tool_mode = sanitize_text_field( $_POST['tool_mode'] ?? 'text' );
        $user_id   = get_current_user_id();
        $api_key   = get_user_meta( $user_id, 'omni_user_api_key', true );

        if ( empty( $prompt ) ) wp_send_json_error( 'Prompt cannot be empty.' );
        if ( empty( $api_key ) ) wp_send_json_error( 'API Key missing in User Profile.' );

        // 1. Build Payload
        $payload = [
            'prompt' => $prompt,
            'model'  => sanitize_text_field( $_POST['model'] ?? '' ),
        ];

        // 2. Add Context (Search/Scraping) if text mode
        if ( $tool_mode !== 'image' ) {
            $context = '';

            // Web Search Augmentation
            if ( filter_var( $_POST['use_web_search'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
                $s_key = get_user_meta( $user_id, 'omni_search_api_key', true );
                $s_cx  = get_user_meta( $user_id, 'omni_search_cx', true );
                if ( $s_key && $s_cx ) {
                    $context .= OmniQuill_Context_Engine::perform_web_search( $prompt, $s_key, $s_cx );
                }
            }

            // URL Scraping
            if ( ! empty( $_POST['ref_url'] ) ) {
                $context .= OmniQuill_Context_Engine::scrape_url( esc_url_raw( $_POST['ref_url'] ) );
            }

            $payload['prompt'] .= $context;

            // Add Image Context (Vision)
            if ( ! empty( $_POST['context_image_id'] ) ) {
                $img_data = OmniQuill_Context_Engine::get_image_base64( intval( $_POST['context_image_id'] ) );
                if ( ! is_wp_error( $img_data ) ) {
                    $payload['image_base64'] = $img_data['base64'];
                    $payload['image_mime']   = $img_data['mime'];
                }
            }
        }

        // 3. Dispatch to Gateway
        $provider = get_option( 'omni_global_provider', 'gemini' );
        $endpoint = ( $tool_mode === 'image' ) ? 'image' : 'text';

        try {
            $result = OmniQuill_LLM_Gateway::dispatch( $provider, $endpoint, $payload, $api_key );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }

            // 4. Handle Result (Upload if image, return text if text)
            if ( $endpoint === 'image' ) {
                $upload = OmniQuill_Context_Engine::upload_generated_image( $result['base64'], $result['mime'], $prompt );
                if ( is_wp_error( $upload ) ) wp_send_json_error( $upload->get_error_message() );

                wp_send_json_success([
                    'type'     => 'image',
                    'media_id' => $upload['id'],
                    'url'      => $upload['url'],
                    'alt'      => $prompt
                ]);
            } else {
                wp_send_json_success([
                    'type'    => ( $tool_mode === 'code' ) ? 'code' : 'text',
                    'content' => $result
                ]);
            }

        } catch ( Exception $e ) {
            wp_send_json_error( 'System Error: ' . $e->getMessage() );
        }
    }

    /**
     * Handle model listing (Keep existing logic wrapped in class)
     */
    public function handle_list_models() {
        check_ajax_referer( 'omni_plugin_nonce', 'nonce' );
        $uid     = get_current_user_id();
        $api_key = get_user_meta( $uid, 'omni_user_api_key', true );

        if ( empty( $api_key ) ) wp_send_json_error( 'Missing API Key' );

        $provider = get_option( 'omni_global_provider', 'gemini' );

        if ( $provider === 'gemini' ) {
            // Basic Gemini listing logic - ideally moved to Gateway, keeping here for brevity
            $url = "https://generativelanguage.googleapis.com/v1beta/models?key=$api_key";
            $res = wp_remote_get( $url );
            if ( is_wp_error( $res ) ) wp_send_json_error( $res->get_error_message() );
            $body = json_decode( wp_remote_retrieve_body( $res ), true );

            $models = [];
            if ( ! empty( $body['models'] ) ) {
                foreach ( $body['models'] as $m ) {
                    $id = str_replace( 'models/', '', $m['name'] );
                    $models[] = [ 'id' => $id, 'name' => $m['displayName'] ?? $id, 'type' => 'text' ];
                }
            }
            wp_send_json_success( $models );
        } else {
            // OpenAI Defaults
            $models = [
                ['id' => 'gpt-4o', 'name' => 'GPT-4o', 'type' => 'text'],
                ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo', 'type' => 'text'],
                ['id' => 'dall-e-3', 'name' => 'DALL-E 3', 'type' => 'image']
            ];
            wp_send_json_success( $models );
        }
    }
}
