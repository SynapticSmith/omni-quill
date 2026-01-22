<?php
/**
 * Interface AI_Provider
 * The contract that all AI vendors must adhere to.
 */
interface Omni_AI_Provider_Interface {

    /**
     * Generate text based on a prompt and history.
     *
     * @param string $prompt The user's current prompt.
     * @param array $history Previous chat turns [['role' => 'user', 'text' => '...'], ...].
     * @param string $api_key The decrypted API key.
     * @param array $options Model, temperature, etc.
     * @return string|WP_Error
     */
    public function generate_text( $prompt, $history, $api_key, $options = [] );

    /**
     * Generate an image.
     *
     * @param string $prompt
     * @param string $api_key
     * @param array $options
     * @return array|WP_Error Returns ['url' => ..., 'base64' => ...]
     */
    public function generate_image( $prompt, $api_key, $options = [] );
}
