<?php
/**
 * Class OmniQuill_LLM_Gateway
 * Implements the Strategy Pattern context.
 */
class OmniQuill_LLM_Gateway {

    /**
     * Factory method to get the correct provider.
     */
    public static function get_provider_instance( $provider_slug ) {
        switch ( $provider_slug ) {
            case 'gemini':
                return new Omni_Provider_Gemini();
            case 'openai':
                return new Omni_Provider_OpenAI();
            default:
                // Default to Gemini if unknown
                return new Omni_Provider_Gemini();
        }
    }

    /**
     * Main dispatch method.
     */
    public static function dispatch_text( $provider_slug, $prompt, $history, $api_key, $options = [] ) {
        $provider = self::get_provider_instance( $provider_slug );

        if ( ! $provider instanceof Omni_AI_Provider_Interface ) {
            return new WP_Error( 'arch_error', 'Invalid Provider Class' );
        }

        return $provider->generate_text( $prompt, $history, $api_key, $options );
    }
}
