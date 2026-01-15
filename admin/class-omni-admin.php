<?php
/**
* Class OmniQuill_Admin
* * Manages the Settings Page, Meta Box registration,
* and Enqueuing of Editor Assets.
*/
class OmniQuill_Admin {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'settings_init' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'show_user_profile', array( $this, 'user_profile_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'user_profile_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_user_profile' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_profile' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) return;

        wp_enqueue_media();
        wp_enqueue_script(
            'omni-writer-script',
            OMNI_PLUGIN_URL . 'assets/js/omni-writer.js',
            array( 'jquery', 'wp-blocks', 'wp-element', 'wp-editor', 'wp-data' ),
            OMNI_VERSION,
            true
        );
        wp_localize_script( 'omni-writer-script', 'omniData', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'omni_plugin_nonce' )
        ]);
        wp_enqueue_style( 'omni-writer-style', OMNI_PLUGIN_URL . 'assets/css/omni-styles.css', [], OMNI_VERSION );
    }

    public function settings_init() {
        register_setting( 'omni_plugin_settings_group', 'omni_global_provider', 'sanitize_text_field' );
        register_setting( 'omni_plugin_settings_group', 'omni_global_model_name', 'sanitize_text_field' );

        add_settings_section( 'omni_general', 'General Settings', function() {
            echo '<p>Configure Global AI settings.</p>';
        }, 'omni-plugin-settings' );

        add_settings_field( 'omni_provider', 'AI Provider', array( $this, 'provider_cb' ), 'omni-plugin-settings', 'omni_general' );
    }

    public function provider_cb() {
        $val = get_option( 'omni_global_provider', 'gemini' );
        echo '<select name="omni_global_provider">
            <option value="gemini" ' . selected( $val, 'gemini', false ) . '>Google Gemini</option>
            <option value="openai" ' . selected( $val, 'openai', false ) . '>OpenAI</option>
        </select>';
    }

    public function add_admin_menu() {
        add_menu_page( 'OmniQuill', 'OmniQuill', 'manage_options', 'omni-plugin-settings', array( $this, 'settings_page_html' ), 'dashicons-superhero', 99 );
    }

    public function settings_page_html() {
        echo '<div class="wrap"><h1>OmniQuill Settings</h1><form method="post" action="options.php">';
        settings_fields( 'omni_plugin_settings_group' );
        do_settings_sections( 'omni-plugin-settings' );
        submit_button();
        echo '</form></div>';
    }

    public function add_meta_box() {
        add_meta_box( 'omni_content_box', 'OmniQuill Studio', array( $this, 'render_meta_box' ), [ 'post', 'page' ], 'side', 'default' );
    }

    public function render_meta_box( $post ) {
        $uid = get_current_user_id();
        $has_key = ! empty( get_user_meta( $uid, 'omni_user_api_key', true ) );
        $has_search = ! empty( get_user_meta( $uid, 'omni_search_api_key', true ) );

        ?>
        <div class="omni-controls">
            <?php if ( ! $has_key ) : ?>
                <div class="omni-alert"><strong>Setup:</strong> Add API Key in <a href="<?php echo esc_url( get_edit_profile_url( $uid ) . '#omni_user_api_key' ); ?>" target="_blank">Profile</a>.</div>
            <?php endif; ?>

            <!-- Model Selector Row with Refresh Button -->
            <div class="omni-row" style="margin-bottom:10px; display:flex; gap:5px; align-items:center;">
                <select id="omni_model_select" style="flex-grow:1; max-width:85%; font-size:12px;" <?php disabled( !$has_key ); ?>>
                    <optgroup label="Defaults">
                        <option value="gemini-1.5-flash" selected>Gemini 1.5 Flash</option>
                    </optgroup>
                </select>
                <button type="button" id="omni_refresh_models" class="button" title="Sync Models" <?php disabled( !$has_key ); ?>>
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>

            <!-- Toolbar -->
            <div class="omni-toolbar">
                <label title="Web Search" class="omni-tool-btn <?php echo $has_search ? '' : 'disabled'; ?>">
                    <input type="checkbox" id="omni_tool_web" <?php disabled(!$has_search); ?>>
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

            <textarea id="omni_prompt" name="omni_prompt" rows="4" placeholder="<?php esc_attr_e('Prompt...', 'omni-quill'); ?>" <?php disabled( !$has_key ); ?>></textarea>

            <!-- Image Context Area -->
            <div id="omni_image_context_area" style="margin-top:5px; padding:5px; border:1px dashed #ccc; border-radius:4px; text-align:center; display:none;">
                <input type="hidden" id="omni_context_image_id" name="omni_context_image_id" value="">
                <div id="omni_context_image_preview" style="display:none; margin-bottom:5px;"></div>
                <button type="button" id="omni_select_image_btn" class="button button-small">Select Image Context</button>
                <button type="button" id="omni_clear_image_btn" class="button button-small" style="display:none; color:#a00;">Remove</button>
            </div>

            <input type="url" id="omni_ref_url" placeholder="Reference URL (Optional)" style="width:100%; margin-top:5px;" <?php disabled( !$has_key ); ?>>

            <button type="button" id="omni_generate_button" class="button button-primary button-large" <?php disabled( !$has_key ); ?>>Generate</button>

            <div id="omni_loading_indicator" style="display:none;">
                <span class="spinner is-active"></span> <span id="omni_loading_text">Thinking...</span>
            </div>
            <div id="omni_message_area" style="display:none;"></div>
        </div>
        <?php
    }

    public function user_profile_fields( $user ) {
        ?>
        <h3>OmniQuill Settings</h3>
        <table class="form-table">
            <tr><th><label for="omni_user_api_key">API Key</label></th><td><input type="password" name="omni_user_api_key" id="omni_user_api_key" value="<?php echo esc_attr( get_user_meta( $user->ID, 'omni_user_api_key', true ) ); ?>" class="regular-text"><p class="description">Gemini or OpenAI API Key.</p></td></tr>
            <tr><th><label for="omni_search_api_key">Search API Key</label></th><td><input type="password" name="omni_search_api_key" id="omni_search_api_key" value="<?php echo esc_attr( get_user_meta( $user->ID, 'omni_search_api_key', true ) ); ?>" class="regular-text"></td></tr>
            <tr><th><label for="omni_search_cx">Search Engine ID</label></th><td><input type="text" name="omni_search_cx" id="omni_search_cx" value="<?php echo esc_attr( get_user_meta( $user->ID, 'omni_search_cx', true ) ); ?>" class="regular-text"></td></tr>
        </table>
        <?php
    }

    public function save_user_profile( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        $fields = ['omni_user_api_key', 'omni_search_api_key', 'omni_search_cx'];
        foreach ( $fields as $f ) {
            if ( isset( $_POST[$f] ) ) update_user_meta( $user_id, $f, sanitize_text_field( $_POST[$f] ) );
        }
    }
}
