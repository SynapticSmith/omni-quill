<?php
/**
 * Plugin Name: OmniQuill
 * Description: The universal magical quill for WordPress. Multimodal AI content creation anywhere.
 * Version: 1.0.1.1
 * OriginAuthor: Mayank Pandya 
 * Requires at least: 5.8
 * License: GPL-2.0+
 * Requires PHP: 7.4
 * Text Domain: omni-quill
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'OMNI_PLUGIN_DIR' ) ) {
    define( 'OMNI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'OMNI_PLUGIN_URL' ) ) {
    define( 'OMNI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

$settings_page_path = OMNI_PLUGIN_DIR . 'admin/omni-settings.php';
if ( file_exists( $settings_page_path ) ) {
    require_once $settings_page_path;
}

// --- Enqueue Assets ---
function omni_plugin_enqueue_editor_assets( $hook_suffix ) {
    if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
        return;
    }

    // Enqueue Media Uploader scripts
    wp_enqueue_media();

    wp_enqueue_script(
        'omni-writer-script',
        OMNI_PLUGIN_URL . 'assets/js/omni-writer.js',
        array( 'jquery', 'wp-blocks', 'wp-element', 'wp-editor', 'wp-data' ),
        '1.6.0',
        true
    );

    wp_localize_script(
        'omni-writer-script',
        'omniData',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'omni_plugin_nonce' ),
        )
    );

    wp_enqueue_style(
        'omni-writer-style',
        OMNI_PLUGIN_URL . 'assets/css/omni-styles.css',
        array(),
        '1.6.0'
    );
}
add_action( 'admin_enqueue_scripts', 'omni_plugin_enqueue_editor_assets' );

// --- User Profile ---
function omni_plugin_user_profile_fields( $user ) {
    ?>
    <h3><?php esc_html_e( 'AI Content Generator Settings', 'omni-quill' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="omni_user_api_key">AI Model API Key</label></th>
            <td>
                <input type="password" name="omni_user_api_key" id="omni_user_api_key" value="<?php echo esc_attr( get_the_author_meta( 'omni_user_api_key', $user->ID ) ); ?>" class="regular-text" />
                <p class="description">Google Gemini or OpenAI API Key.</p>
            </td>
        </tr>
        <tr>
            <th><label for="omni_search_api_key">Google Search API Key</label></th>
            <td>
                <input type="password" name="omni_search_api_key" id="omni_search_api_key" value="<?php echo esc_attr( get_the_author_meta( 'omni_search_api_key', $user->ID ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="omni_search_cx">Search Engine ID (CX)</label></th>
            <td>
                <input type="text" name="omni_search_cx" id="omni_search_cx" value="<?php echo esc_attr( get_the_author_meta( 'omni_search_cx', $user->ID ) ); ?>" class="regular-text" />
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'omni_plugin_user_profile_fields' );
add_action( 'edit_user_profile', 'omni_plugin_user_profile_fields' );

function omni_plugin_save_user_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) return false;
    $fields = ['omni_user_api_key', 'omni_search_api_key', 'omni_search_cx'];
    foreach($fields as $f) {
        if ( isset( $_POST[$f] ) ) update_user_meta( $user_id, $f, sanitize_text_field( $_POST[$f] ) );
    }
}
add_action( 'personal_options_update', 'omni_plugin_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'omni_plugin_save_user_profile_fields' );

// --- Meta Box ---
function omni_plugin_add_meta_box() {
    add_meta_box( 'omni_content_box', __( 'OmniQuill Studio', 'omni-quill' ), 'omni_plugin_meta_box_callback', array( 'post', 'page' ), 'side', 'default' );
}
add_action( 'add_meta_boxes', 'omni_plugin_add_meta_box' );

function omni_plugin_meta_box_callback( $post ) {
    wp_nonce_field( 'omni_plugin_generate_content', 'omni_plugin_generate_content_nonce' );
    $uid = get_current_user_id();
    $has_ai_key = !empty(get_user_meta( $uid, 'omni_user_api_key', true ));
    $has_search_key = !empty(get_user_meta( $uid, 'omni_search_api_key', true )) && !empty(get_user_meta( $uid, 'omni_search_cx', true ));
    ?>
    <div class="omni-controls">
        <?php if ( !$has_ai_key ) : ?>
            <div class="omni-alert"><strong>Setup:</strong> Add API Key in <a href="<?php echo esc_url( get_edit_profile_url( $uid ) . '#omni_user_api_key' ); ?>" target="_blank">Profile</a>.</div>
        <?php endif; ?>

        <!-- Model Selector -->
        <div class="omni-row" style="margin-bottom:10px; display:flex; gap:5px; align-items:center;">
            <select id="omni_model_select" style="flex-grow:1; max-width:85%; font-size:12px;" <?php disabled( !$has_ai_key ); ?>>
                <optgroup label="Defaults">
                    <option value="gemini-1.5-flash" selected>Gemini 1.5 Flash</option>
                </optgroup>
            </select>
            <button type="button" id="omni_refresh_models" class="button" title="Sync Models" <?php disabled( !$has_ai_key ); ?>>
                <span class="dashicons dashicons-update"></span>
            </button>
        </div>

        <!-- Toolbar -->
        <div class="omni-toolbar">
            <label title="Web Search" class="omni-tool-btn <?php echo $has_search_key ? '' : 'disabled'; ?>">
                <input type="checkbox" id="omni_tool_web" <?php disabled(!$has_search_key); ?>>
                <span class="dashicons dashicons-search"></span>
            </label>
            <label title="Generate Image" class="omni-tool-btn">
                <input type="radio" name="omni_tool_mode" value="image">
                <span class="dashicons dashicons-format-image"></span>
            </label>
            <label title="Code Mode" class="omni-tool-btn">
                <input type="radio" name="omni_tool_mode" value="code">
                <span class="dashicons dashicons-editor-code"></span>
            </label>
            <label title="Standard Text" class="omni-tool-btn active">
                <input type="radio" name="omni_tool_mode" value="text" checked>
                <span class="dashicons dashicons-text"></span>
            </label>
        </div>

        <textarea id="omni_prompt" name="omni_prompt" rows="4" placeholder="<?php esc_attr_e('Prompt...', 'omni-quill'); ?>" <?php disabled( !$has_ai_key ); ?>></textarea>
        
        <!-- NEW: Image Upload Area -->
        <div id="omni_image_context_area" style="margin-top:5px; padding:5px; border:1px dashed #ccc; border-radius:4px; text-align:center; display:none;">
            <input type="hidden" id="omni_context_image_id" name="omni_context_image_id" value="">
            <div id="omni_context_image_preview" style="display:none; margin-bottom:5px;"></div>
            <button type="button" id="omni_select_image_btn" class="button button-small">Select Image Context</button>
            <button type="button" id="omni_clear_image_btn" class="button button-small" style="display:none; color:#a00;">Remove</button>
        </div>

        <input type="url" id="omni_ref_url" placeholder="Reference URL (Optional)" style="width:100%; margin-top:5px;" <?php disabled( !$has_ai_key ); ?>>

        <button type="button" id="omni_generate_button" class="button button-primary button-large" <?php disabled( !$has_ai_key ); ?>>Generate</button>
        
        <div id="omni_loading_indicator" style="display:none;">
            <span class="spinner is-active"></span> <span id="omni_loading_text">Thinking...</span>
        </div>
        <div id="omni_message_area" style="display:none;"></div>
    </div>
    <?php
}

// --- AJAX: List Models ---
function omni_plugin_list_models_ajax() {
    check_ajax_referer( 'omni_plugin_nonce', 'nonce' );
    $uid = get_current_user_id();
    $api_key = get_user_meta( $uid, 'omni_user_api_key', true );
    $provider = get_option( 'omni_global_provider', 'gemini' );

    if ( empty( $api_key ) ) wp_send_json_error( 'Missing API Key' );

    $models = [];
    if ( $provider === 'gemini' ) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=$api_key";
        $res = wp_remote_get($url, ['timeout' => 15]);
        if ( is_wp_error( $res ) ) wp_send_json_error( $res->get_error_message() );
        
        $body = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( !empty($body['models']) ) {
            foreach ( $body['models'] as $m ) {
                $methods = $m['supportedGenerationMethods'] ?? [];
                // Check if it supports generation or image creation
                $can_image = in_array('predict', $methods) || in_array('generateImage', $methods) || strpos($m['name'], 'imagen') !== false;
                $can_text = in_array('generateContent', $methods);
                $id = str_replace('models/', '', $m['name']);
                
                // Only add models that can actually do something useful
                if ($can_image || $can_text) {
                    $models[] = [
                        'id' => $id,
                        'name' => $m['displayName'] ?? $id,
                        'type' => $can_image ? 'image' : 'text'
                    ];
                }
            }
        }
    } else {
        $common_openai = ['gpt-3.5-turbo', 'gpt-4', 'gpt-4o', 'dall-e-3', 'dall-e-2'];
        foreach($common_openai as $m) $models[] = ['id' => $m, 'name' => ucfirst($m), 'type' => strpos($m, 'dall-e') !== false ? 'image' : 'text'];
    }
    wp_send_json_success( $models );
}
add_action( 'wp_ajax_omni_list_models', 'omni_plugin_list_models_ajax' );

// --- AJAX: Generate ---
function omni_plugin_generate_content_ajax() {
    check_ajax_referer( 'omni_plugin_nonce', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permission denied.' );

    $prompt = sanitize_textarea_field( $_POST['prompt'] ?? '' );
    $ref_url = esc_url_raw( $_POST['ref_url'] ?? '' );
    $tool_mode = sanitize_text_field( $_POST['tool_mode'] ?? 'text' );
    $selected_model = sanitize_text_field( $_POST['model'] ?? '' );
    $use_web_search = filter_var( $_POST['use_web_search'] ?? false, FILTER_VALIDATE_BOOLEAN );
    $image_context_id = intval( $_POST['context_image_id'] ?? 0 );

    if ( empty( $prompt ) ) wp_send_json_error( 'Prompt empty.' );

    $uid = get_current_user_id();
    $api_key = get_user_meta( $uid, 'omni_user_api_key', true );
    if ( empty( $api_key ) ) wp_send_json_error( 'Missing AI API Key.' );

    $ai_provider = get_option( 'omni_global_provider', 'gemini' );

    // --- MODE 1: IMAGE GENERATION (Text -> Image) ---
    if ( $tool_mode === 'image' ) {
        try {
            $img_data = null;
            if ( $ai_provider === 'gemini' ) {
                // FALLBACK FIX: If model is invalid or not selected, default to a safe bet.
                $model = (strpos($selected_model, 'imagen') !== false) ? $selected_model : 'imagen-3.0-generate-001';
                $img_data = omni_generate_image_gemini( $prompt, $api_key, $model );
            } else {
                $model = (strpos($selected_model, 'dall-e') !== false) ? $selected_model : 'dall-e-3';
                $img_data = omni_generate_image_openai( $prompt, $api_key, $model );
            }
            
            $upload_result = omni_upload_base64_image( $img_data['base64'], $img_data['mime'], $prompt );
            if ( is_wp_error( $upload_result ) ) throw new Exception( $upload_result->get_error_message() );

            wp_send_json_success([
                'type' => 'image',
                'media_id' => $upload_result['id'],
                'url' => $upload_result['url'],
                'alt' => $prompt
            ]);
        } catch ( Exception $e ) {
            wp_send_json_error( 'Image Gen Error: ' . $e->getMessage() );
        }
        return;
    }

    // --- MODE 2: TEXT/CODE (Text+Image -> Text) ---
    $context = "";
    
    // Web Search
    if ( $use_web_search ) {
        $s_key = get_user_meta( $uid, 'omni_search_api_key', true );
        $s_cx = get_user_meta( $uid, 'omni_search_cx', true );
        if ( $s_key && $s_cx ) {
            $search_res = omni_google_search( $prompt, $s_key, $s_cx );
            if ( ! is_wp_error( $search_res ) ) $context .= "\n\n[WEB SEARCH RESULTS]:\n" . $search_res;
        }
    }

    // URL Scraper
    if ( ! empty( $ref_url ) ) {
        $scraped = omni_scrape_url( $ref_url );
        if ( ! is_wp_error( $scraped ) ) $context .= "\n\nhttps://www.merriam-webster.com/dictionary/context:\n" . $scraped['content'];
    }

    $cats = get_categories(['hide_empty'=>false]);
    $cat_names = wp_list_pluck($cats, 'name');
    $context .= "\n\n[SITE INFO]: Categories: " . implode(', ', $cat_names);

    $sys_instruction = ($tool_mode === 'code') 
        ? "You are an expert developer. Provide ONLY the code requested." 
        : "Format response in Markdown (## H2, ### H3, **Bold**, - Lists).";

    $final_prompt = $prompt . $context . "\n\n[INSTRUCTION]: " . $sys_instruction;

    // Fetch Image Context if present
    $image_base64 = null;
    $image_mime = null;
    if ( $image_context_id > 0 ) {
        $img_info = omni_get_image_base64( $image_context_id );
        if ( ! is_wp_error( $img_info ) ) {
            $image_base64 = $img_info['base64'];
            $image_mime = $img_info['mime'];
        }
    }

    try {
        $response_text = "";
        if ( $ai_provider === 'gemini' ) {
            // Ensure model handles vision if image provided, usually 1.5-flash or 1.5-pro
            $model = !empty($selected_model) ? $selected_model : 'gemini-1.5-flash';
            $response_text = omni_call_gemini_text( $final_prompt, $api_key, $model, $image_base64, $image_mime );
        } else {
            $model = !empty($selected_model) ? $selected_model : 'gpt-4o'; // GPT-4o supports vision
            $response_text = omni_call_openai_text( $final_prompt, $api_key, $model, $image_base64, $image_mime );
        }
        
        wp_send_json_success([
            'type' => $tool_mode === 'code' ? 'code' : 'text',
            'content' => $response_text
        ]);

    } catch ( Exception $e ) {
        wp_send_json_error( $e->getMessage() );
    }
}
add_action( 'wp_ajax_omni_plugin_generate_content', 'omni_plugin_generate_content_ajax' );

// --- HELPER FUNCTIONS ---

function omni_get_image_base64( $media_id ) {
    $path = get_attached_file( $media_id );
    if ( ! file_exists( $path ) ) return new WP_Error( 'file_not_found', 'Image file missing.' );
    
    $type = get_post_mime_type( $media_id );
    $data = file_get_contents( $path );
    return [
        'base64' => base64_encode( $data ),
        'mime'   => $type
    ];
}

function omni_call_gemini_text($prompt, $key, $model, $img_base64 = null, $img_mime = null) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$key";
    
    $parts = [['text' => $prompt]];
    
    // Add Image to Payload if present
    if ( $img_base64 && $img_mime ) {
        array_unshift($parts, [
            'inlineData' => [
                'mimeType' => $img_mime,
                'data'     => $img_base64
            ]
        ]);
    }

    $body = json_encode(['contents' => [['role' => 'user', 'parts' => $parts]]]);
    $res = wp_remote_post($url, ['body' => $body, 'headers' => ['Content-Type'=>'application/json'], 'timeout'=>60]);
    if(is_wp_error($res)) throw new Exception($res->get_error_message());
    $d = json_decode(wp_remote_retrieve_body($res), true);
    return $d['candidates'][0]['content']['parts'][0]['text'] ?? 'Error: ' . ($d['error']['message'] ?? 'Unknown');
}

function omni_call_openai_text($prompt, $key, $model, $img_base64 = null, $img_mime = null) {
    $url = "https://api.openai.com/v1/chat/completions";
    
    $content = [['type' => 'text', 'text' => $prompt]];
    
    if ( $img_base64 && $img_mime ) {
        $content[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => "data:$img_mime;base64,$img_base64"
            ]
        ];
    }

    $body = json_encode(['model' => $model, 'messages' => [['role' => 'user', 'content' => $content]]]);
    $res = wp_remote_post($url, ['body' => $body, 'headers' => ['Content-Type'=>'application/json', 'Authorization'=>"Bearer $key"], 'timeout'=>60]);
    if(is_wp_error($res)) throw new Exception($res->get_error_message());
    $d = json_decode(wp_remote_retrieve_body($res), true);
    return $d['choices'][0]['message']['content'] ?? 'Error';
}

// ... (Existing Google Search, Image Gen, Upload functions remain same) ...
function omni_google_search($query, $key, $cx) {
    $url = "https://www.googleapis.com/customsearch/v1?key=$key&cx=$cx&q=" . urlencode($query);
    $res = wp_remote_get($url);
    if(is_wp_error($res)) return $res;
    $body = json_decode(wp_remote_retrieve_body($res), true);
    if(!isset($body['items'])) return "No results.";
    $out = "";
    foreach(array_slice($body['items'], 0, 4) as $item) $out .= "- " . $item['title'] . ": " . $item['snippet'] . "\n";
    return $out;
}

function omni_generate_image_gemini($prompt, $key, $model) {
    // Force predict endpoint
    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:predict?key=" . $key;
    $body = ['instances' => [['prompt' => $prompt]], 'parameters' => ['sampleCount' => 1]];
    $res = wp_remote_post($url, ['body' => json_encode($body), 'headers' => ['Content-Type'=>'application/json'], 'timeout' => 60]);
    
    if(is_wp_error($res)) throw new Exception($res->get_error_message());
    $data = json_decode(wp_remote_retrieve_body($res), true);
    
    if(isset($data['predictions'][0]['bytesBase64Encoded'])) {
        return ['base64' => $data['predictions'][0]['bytesBase64Encoded'], 'mime' => 'image/png'];
    }
    if(isset($data['error']['message'])) throw new Exception($data['error']['message']);
    throw new Exception("Model $model failed.");
}

function omni_generate_image_openai($prompt, $key, $model) {
    $url = "https://api.openai.com/v1/images/generations";
    $body = ['model' => $model, 'prompt' => $prompt, 'n' => 1, 'size' => '1024x1024', 'response_format' => 'b64_json'];
    $res = wp_remote_post($url, ['body' => json_encode($body), 'headers' => ['Content-Type'=>'application/json'], 'Authorization'=>"Bearer $key"], 'timeout' => 60]);
    if(is_wp_error($res)) throw new Exception($res->get_error_message());
    $data = json_decode(wp_remote_retrieve_body($res), true);
    if(isset($data['data'][0]['b64_json'])) return ['base64' => $data['data'][0]['b64_json'], 'mime' => 'image/png'];
    throw new Exception($data['error']['message'] ?? 'Unknown Image Error');
}

function omni_upload_base64_image($base64, $mime, $prompt) {
    $binary = base64_decode($base64);
    $upload_dir = wp_upload_dir();
    $filename = 'ai-gen-' . time() . '.png';
    $file_path = $upload_dir['path'] . '/' . $filename;
    file_put_contents($file_path, $binary);
    $attachment = ['post_mime_type' => $mime, 'post_title' => $prompt, 'post_content' => '', 'post_status' => 'inherit'];
    $attach_id = wp_insert_attachment($attachment, $file_path);
    if(is_wp_error($attach_id)) return $attach_id;
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);
    return ['id' => $attach_id, 'url' => wp_get_attachment_url($attach_id)];
}

function omni_scrape_url($url) {
    $res = wp_remote_get($url, ['timeout'=>15]);
    if(is_wp_error($res) || empty(wp_remote_retrieve_body($res))) return new WP_Error('err', 'Failed');
    $dom = new DOMDocument(); @$dom->loadHTML(wp_remote_retrieve_body($res));
    foreach($dom->getElementsByTagName('script') as $s) $s->parentNode->removeChild($s);
    foreach($dom->getElementsByTagName('style') as $s) $s->parentNode->removeChild($s);
    return ['title'=>'Ref', 'content'=>trim(substr($dom->textContent, 0, 5000))];
}
