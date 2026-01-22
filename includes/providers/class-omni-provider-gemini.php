<?php
class Omni_Provider_Gemini implements Omni_AI_Provider_Interface {

    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function generate_text( $prompt, $history, $api_key, $options = [] ) {
        $model = $options['model'] ?? 'gemini-1.5-flash';
        $url = $this->api_url . "{$model}:generateContent?key={$api_key}";

        // 1. Convert Generic History to Gemini Format
        // Generic: [['role' => 'user', 'text' => '...'], ['role' => 'model', 'text' => '...']]
        // Gemini:  [['role' => 'user', 'parts' => [['text' => '...']]]]

        $contents = [];
        if ( ! empty( $history ) && is_array( $history ) ) {
            foreach ( $history as $turn ) {
                $role = ( $turn['role'] === 'ai' || $turn['role'] === 'model' ) ? 'model' : 'user';
                $contents[] = [
                    'role'  => $role,
                    'parts' => [[ 'text' => $turn['text'] ]]
                ];
            }
        }

        // Add current prompt
        $contents[] = [
            'role' => 'user',
            'parts' => [[ 'text' => $prompt ]]
        ];

        // 2. Make Request
        $body = [ 'contents' => $contents ];

        // JSON Mode (if requested)
        if ( ! empty( $options['json_mode'] ) ) {
            $body['generationConfig'] = [ 'responseMimeType' => 'application/json' ];
        }

        $response = wp_remote_post( $url, [
            'body'    => json_encode( $body ),
            'headers' => [ 'Content-Type' => 'application/json' ],
            'timeout' => 45
        ]);

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return new WP_Error( 'gemini_error', $data['error']['message'] ?? 'Unknown Gemini Error' );
    }

    public function generate_image( $prompt, $api_key, $options = [] ) {
        // Implementation for Imagen (omitted for brevity, follows similar pattern)
        return new WP_Error( 'not_implemented', 'Imagen integration pending.' );
    }
}
