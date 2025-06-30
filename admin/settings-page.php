<?php
// Register plugin settings
function mp_ai_plugin_settings_init() {
    register_setting(
        'mp_ai_plugin_settings_group', // Option group
        'mp_ai_plugin_api_key',      // Option name for API key
        'sanitize_text_field'          // Sanitize callback
    );
    register_setting(
        'mp_ai_plugin_settings_group', // Option group
        'mp_ai_plugin_ai_model',     // Option name for AI model selection
        'sanitize_text_field'          // Sanitize callback
    );

    add_settings_section(
        'mp_ai_plugin_general_settings_section', // Section ID
        __( 'General Settings', 'mp-ai-content-generator' ), // Section title
        'mp_ai_plugin_general_settings_section_callback', // Callback function to render section description
        'mp-ai-plugin-settings' // Page slug
    );

    add_settings_field(
        'mp_ai_plugin_api_key_field', // Field ID
        __( 'AI API Key', 'mp-ai-content-generator' ), // Field title
        'mp_ai_plugin_api_key_callback', // Callback function to render field
        'mp-ai-plugin-settings', // Page slug
        'mp_ai_plugin_general_settings_section' // Section ID
    );

    add_settings_field(
        'mp_ai_plugin_ai_model_field', // Field ID
        __( 'Select AI Model', 'mp-ai-content-generator' ), // Field title
        'mp_ai_plugin_ai_model_callback', // Callback function to render field
        'mp-ai-plugin-settings', // Page slug
        'mp_ai_plugin_general_settings_section' // Section ID
    );
}
add_action( 'admin_init', 'mp_ai_plugin_settings_init' );

// Add settings page to the admin menu as a top-level item
function mp_ai_plugin_add_admin_menu() {
    add_menu_page(
        __( 'AI Content Generator Settings', 'mp-ai-content-generator' ), // Page title
        __( 'AI Content Gen', 'mp-ai-content-generator' ), // Menu title
        'manage_options', // Capability required to access
        'mp-ai-plugin-settings', // Menu slug (unique identifier)
        'mp_ai_plugin_settings_page_callback', // Callback function to render page
        'dashicons-superhero', // Icon URL or dashicon class (e.g., 'dashicons-admin-post', 'dashicons-admin-tools')
        99 // Position in the menu. Lower number means higher up.
    );
}
add_action( 'admin_menu', 'mp_ai_plugin_add_admin_menu' );

// Callback for section description
function mp_ai_plugin_general_settings_section_callback() {
    echo '<p>' . esc_html__( 'Enter your AI API key and select your preferred model.', 'mp-ai-content-generator' ) . '</p>';
}

// Callback for API Key field
function mp_ai_plugin_api_key_callback() {
    $api_key = get_option( 'mp_ai_plugin_api_key' );
    ?>
    <input type="text" name="mp_ai_plugin_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" placeholder="Enter your Gemini or OpenAI API Key"/>
    <p class="description">
        <?php esc_html_e( 'Your API key for Gemini AI or ChatGPT (OpenAI).', 'mp-ai-content-generator' ); ?><br/>
        <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'Get your Google Gemini AI API key here', 'mp-ai-content-generator' ); ?>
        </a><br/>
        <a href="https://platform.openai.com/account/api-keys" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'Get your OpenAI API key here', 'mp-ai-content-generator' ); ?>
        </a>
    </p>
    <?php
}

// Callback for AI Model selection field
function mp_ai_plugin_ai_model_callback() {
    $ai_model = get_option( 'mp_ai_plugin_ai_model', 'gemini' );
    ?>
    <select name="mp_ai_plugin_ai_model">
        <option value="gemini" <?php selected( $ai_model, 'gemini' ); ?>><?php esc_html_e( 'Google Gemini AI', 'mp-ai-content-generator' ); ?></option>
        <option value="chatgpt" <?php selected( $ai_model, 'chatgpt' ); ?>><?php esc_html_e( 'OpenAI ChatGPT', 'mp-ai-content-generator' ); ?></option>
    </select>
    <p class="description"><?php esc_html_e( 'Choose which AI model to use for content generation.', 'mp-ai-content-generator' ); ?></p>
    <?php
}

// Callback for the settings page content
function mp_ai_plugin_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Your AI Content Generator Settings', 'mp-ai-content-generator' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'mp_ai_plugin_settings_group' );
            do_settings_sections( 'mp-ai-plugin-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}