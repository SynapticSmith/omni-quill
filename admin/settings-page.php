<?php
// Register plugin settings
function mp_ai_plugin_settings_init() {
    register_setting( 'mp_ai_plugin_settings_group', 'mp_ai_plugin_ai_model', 'sanitize_text_field' );
    register_setting( 'mp_ai_plugin_settings_group', 'mp_ai_plugin_model_name', 'sanitize_text_field' );

    add_settings_section( 'mp_ai_general', __( 'General Settings', 'mp-ai-content-generator' ), 'mp_ai_plugin_section_desc', 'mp-ai-plugin-settings' );

    add_settings_field( 'mp_ai_provider', __( 'AI Provider', 'mp-ai-content-generator' ), 'mp_ai_plugin_provider_cb', 'mp-ai-plugin-settings', 'mp_ai_general' );
    add_settings_field( 'mp_ai_model_name', __( 'Model Name', 'mp-ai-content-generator' ), 'mp_ai_plugin_model_name_cb', 'mp-ai-plugin-settings', 'mp_ai_general' );
}
add_action( 'admin_init', 'mp_ai_plugin_settings_init' );

function mp_ai_plugin_add_admin_menu() {
    add_menu_page( __( 'AI Content Gen', 'mp-ai-content-generator' ), __( 'AI Content Gen', 'mp-ai-content-generator' ), 'manage_options', 'mp-ai-plugin-settings', 'mp_ai_plugin_settings_page_cb', 'dashicons-superhero', 99 );
}
add_action( 'admin_menu', 'mp_ai_plugin_add_admin_menu' );

function mp_ai_plugin_section_desc() {
    echo '<p>' . esc_html__( 'Configure the Global AI settings below. Note: Each user must set their own API Key in their User Profile.', 'mp-ai-content-generator' ) . '</p>';
}

function mp_ai_plugin_provider_cb() {
    $value = get_option( 'mp_ai_plugin_ai_model', 'gemini' );
    ?>
    <select name="mp_ai_plugin_ai_model">
        <option value="gemini" <?php selected( $value, 'gemini' ); ?>>Google Gemini</option>
        <option value="chatgpt" <?php selected( $value, 'chatgpt' ); ?>>OpenAI (ChatGPT)</option>
    </select>
    <?php
}

function mp_ai_plugin_model_name_cb() {
    $value = get_option( 'mp_ai_plugin_model_name' );
    ?>
    <input type="text" name="mp_ai_plugin_model_name" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="e.g., gemini-1.5-flash" />
    <p class="description">
        Override the default model ID. Leave empty to use defaults.<br>
        <strong>Note:</strong> API Keys are now managed individually by each user in their "Your Profile" page.
    </p>
    <?php
}

function mp_ai_plugin_settings_page_cb() {
    ?>
    <div class="wrap">
        <h1>AI Content Generator Settings</h1>
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
