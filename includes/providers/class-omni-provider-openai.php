<?php
class Omni_Provider_OpenAI implements Omni_AI_Provider_Interface {

    private $api_url = 'https://api.openai.com/v1/chat/completions';

    public function generate_text( $prompt, $history, $api_key, $options = [] ) {
        $model = $options['model'] ?? 'gpt-4o';

        // 1. Convert Generic History to OpenAI Format
        // Generic: [['role' => 'user', 'text' => '...']]
        // OpenAI:  [['role' => 'user', 'content' => '...']]

        $messages = [];
        // System Prompt
        $messages[] = [ 'role' => 'system', 'content' => 'You are a helpful WordPress assistant.' ];

        if ( ! empty( $history ) && is_array( $history ) ) {
            foreach ( $history as $turn ) {
                $role = ( $turn['role'] === 'ai' || $turn['role'] === 'model' ) ? 'assistant' : 'user';
                $messages[] = [ 'role' => $role, 'content' => $turn['text'] ];
            }
        }

        $messages[] = [ 'role' => 'user', 'content' => $prompt ];

        $body = [
            'model'    => $model,
            'messages' => $messages
        ];

        // JSON Mode
        if ( ! empty( $options['json_mode'] ) ) {
            $body['response_format'] = [ 'type' => 'json_object' ];
        }

        $response = wp_remote_post( $this->api_url, [
            'body'    => json_encode( $body ),
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'timeout' => 45
        ]);

        if ( is_wp_error( $response ) ) return $response;
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return $data['choices'][0]['message']['content'] ?? new WP_Error( 'openai_error', 'No content returned' );
    }

    public function generate_image( $prompt, $api_key, $options = [] ) {
        // Implementation for DALL-E 3
        return new WP_Error( 'not_implemented', 'DALL-E integration pending.' );
    }
}
