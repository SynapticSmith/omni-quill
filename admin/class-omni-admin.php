<?php
class OmniQuill_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_scripts' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
        add_action( 'personal_options_update', array( $this, 'save_user_profile' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_profile' ) );
        add_action( 'show_user_profile', array( $this, 'user_profile_fields' ) );
    }

    public function register_scripts() {
        wp_register_script(
            'omni-quill-sidebar',
            OMNI_PLUGIN_URL . 'build/index.js', // This points to the compiled React
            array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose' ),
            OMNI_VERSION,
            true
        );
    }

    public function enqueue_editor_assets() {
        wp_enqueue_script( 'omni-quill-sidebar' );
        wp_localize_script( 'omni-quill-sidebar', 'omniSettings', [
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' )
        ]);
    }

    public function user_profile_fields( $user ) {
        // We do not show the key value for security, only a placeholder if set
        $has_key = ! empty( get_user_meta( $user->ID, 'omni_user_api_key', true ) );
        ?>
        <h3>OmniQuill Pro Settings</h3>
        <table class="form-table">
            <tr>
                <th><label for="omni_user_api_key">API Key</label></th>
                <td>
                    <input type="password" name="omni_user_api_key" id="omni_user_api_key" class="regular-text" placeholder="<?php echo $has_key ? 'Key is set (Hidden)' : 'Enter API Key'; ?>">
                    <p class="description">Keys are encrypted at rest.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_user_profile( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;

        if ( ! empty( $_POST['omni_user_api_key'] ) ) {
            // ENCRYPT BEFORE SAVING
            $encrypted = OmniQuill_Security::encrypt( sanitize_text_field( $_POST['omni_user_api_key'] ) );
            update_user_meta( $user_id, 'omni_user_api_key', $encrypted );
        }
    }
}
