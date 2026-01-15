<?php
/**
 * Class OmniQuill_LLM_Gateway
 * * Service class responsible for strictly typed API interactions
 * with Google Gemini and OpenAI.
 */
class OmniQuill_LLM_Gateway {

    /**
     * Route the request to the correct provider.
     *
     * @param string $provider 'gemini' or 'openai'
     * @param string $endpoint 'text' or 'image'
     * @param array  $payload  Normalized payload data
     * @param string $api_key  User API Key
     * @return array|WP_Error
     */
    public static function dispatch( $provider, $endpoint, $payload, $api_key ) {
        if ( 'gemini' === $provider ) {
            return ( $endpoint === 'image' )
                ? self::gemini_predict_image( $payload, $api_key )
                : self::gemini_generate_text( $payload, $api_key );
        } elseif ( 'openai' === $provider || 'chatgpt' === $provider ) {
             return ( $endpoint === 'image' )
                ? self::openai_generate_image( $payload, $api_key )
                : self::openai_generate_text( $payload, $api_key );
        }
        return new WP_Error( 'invalid_provider', 'Unknown AI Provider selected.' );
    }

    // --- GEMINI METHODS ---

    private static function gemini_generate_text( $payload, $key ) {
        $model = $payload['model'] ?: 'gemini-1.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $parts = [[ 'text' => $payload['prompt'] ]];

        // Multimodal: Append Image Data if present
        if ( ! empty( $payload['image_base64'] ) ) {
            array_unshift( $parts, [
                'inlineData' => [
                    'mimeType' => $payload['image_mime'],
                    'data'     => $payload['image_base64']
                ]
            ]);
        }

        $body = [ 'contents' => [[ 'role' => 'user', 'parts' => $parts ]] ];

        $response = wp_remote_post( $url, [
            'body'    => json_encode( $body ),
            'headers' => [ 'Content-Type' => 'application/json' ],
            'timeout' => 60
        ]);

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return $data['candidates'][0]['content']['parts'][0]['text']
            ?? new WP_Error( 'gemini_api_error', $data['error']['message'] ?? 'Unknown Gemini Error' );
    }

    private static function gemini_predict_image( $payload, $key ) {
        $model = ( strpos( $payload['model'], 'imagen' ) !== false ) ? $payload['model'] : 'imagen-3.0-generate-001';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:predict?key={$key}";

        $body = [
            'instances'  => [[ 'prompt' => $payload['prompt'] ]],
            'parameters' => [ 'sampleCount' => 1 ]
        ];

        $response = wp_remote_post( $url, [
            'body'    => json_encode( $body ),
            'headers' => [ 'Content-Type' => 'application/json' ],
            'timeout' => 60
        ]);

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['predictions'][0]['bytesBase64Encoded'] ) ) {
            return [
                'base64' => $data['predictions'][0]['bytesBase64Encoded'],
                'mime'   => 'image/png'
            ];
        }
        return new WP_Error( 'gemini_image_error', $data['error']['message'] ?? 'Failed to generate image.' );
    }

    // --- OPENAI METHODS ---

    private static function openai_generate_text( $payload, $key ) {
        $model = $payload['model'] ?: 'gpt-4o';
        $url = "https://api.openai.com/v1/chat/completions";

        $content = [[ 'type' => 'text', 'text' => $payload['prompt'] ]];

        if ( ! empty( $payload['image_base64'] ) ) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$payload['image_mime']};base64,{$payload['image_base64']}"
                ]
            ];
        }

        $body = [
            'model'    => $model,
            'messages' => [[ 'role' => 'user', 'content' => $content ]]
        ];

        $response = wp_remote_post( $url, [
            'body'    => json_encode( $body ),
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer {$key}"
            ],
            'timeout' => 60
        ]);

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return $data['choices'][0]['message']['content']
            ?? new WP_Error( 'openai_api_error', $data['error']['message'] ?? 'Unknown OpenAI Error' );
    }

    private static function openai_generate_image( $payload, $key ) {
        $model = ( strpos( $payload['model'], 'dall-e' ) !== false ) ? $payload['model'] : 'dall-e-3';
        $url = "https://api.openai.com/v1/images/generations";

        $body = [
            'model'           => $model,
            'prompt'          => $payload['prompt'],
            'n'               => 1,
            'size'            => '1024x1024',
            'response_format' => 'b64_json'
        ];

        $response = wp_remote_post( $url, [
            'body'    => json_encode( $body ),
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer {$key}"
            ],
            'timeout' => 60
        ]);

        if ( is_wp_error( $response ) ) return $response;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['data'][0]['b64_json'] ) ) {
            return [
                'base64' => $data['data'][0]['b64_json'],
                'mime'   => 'image/png'
            ];
        }
        return new WP_Error( 'openai_image_error', $data['error']['message'] ?? 'Unknown Image Error' );
    }
}
