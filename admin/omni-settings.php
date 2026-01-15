<?php
// Register plugin settings
function omni_plugin_settings_init() {
    register_setting( 'omni_plugin_settings_group', 'omni_plugin_ai_model', 'sanitize_text_field' );
    register_setting( 'omni_plugin_settings_group', 'omni_plugin_model_name', 'sanitize_text_field' );

    add_settings_section( 'omni_general', __( 'General Settings', 'omni-quill' ), 'omni_plugin_section_desc', 'omni-plugin-settings' );

    add_settings_field( 'omni_provider', __( 'AI Provider', 'omni-quill' ), 'omni_plugin_provider_cb', 'omni-plugin-settings', 'omni_general' );
    add_settings_field( 'omni_model_name', __( 'Model Name', 'omni-quill' ), 'omni_plugin_model_name_cb', 'omni-plugin-settings', 'omni_general' );
}
add_action( 'admin_init', 'omni_plugin_settings_init' );

function omni_plugin_add_admin_menu() {
    add_menu_page( __( 'AI Content Gen', 'omni-quill' ), __( 'AI Content Gen', 'omni-quill' ), 'manage_options', 'omni-plugin-settings', 'omni_plugin_settings_page_cb', 'dashicons-superhero', 99 );
}
add_action( 'admin_menu', 'omni_plugin_add_admin_menu' );

function omni_plugin_section_desc() {
    echo '<p>' . esc_html__( 'Configure the Global AI settings below. Note: Each user must set their own API Key in their User Profile.', 'omni-quill' ) . '</p>';
}

function omni_plugin_provider_cb() {
    $value = get_option( 'omni_plugin_ai_model', 'gemini' );
    ?>
    <select name="omni_plugin_ai_model">
        <option value="gemini" <?php selected( $value, 'gemini' ); ?>>Google Gemini</option>
        <option value="chatgpt" <?php selected( $value, 'chatgpt' ); ?>>OpenAI (ChatGPT)</option>
    </select>
    <?php
}

function omni_plugin_model_name_cb() {
    $value = get_option( 'omni_plugin_model_name' );
    ?>
    <input type="text" name="omni_plugin_model_name" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="e.g., gemini-1.5-flash" />
    <p class="description">
        Override the default model ID. Leave empty to use defaults.<br>
        <strong>Note:</strong> API Keys are now managed individually by each user in their "Your Profile" page.
    </p>
    <?php
}

function omni_plugin_settings_page_cb() {
    ?>
    <div class="wrap">
        <h1>AI Content Generator Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'omni_plugin_settings_group' );
            do_settings_sections( 'omni-plugin-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
