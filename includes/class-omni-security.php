<?php
/**
 * Class OmniQuill_Security
 * Handles Encryption at Rest using OpenSSL.
 */
class OmniQuill_Security {

    private static $cipher = 'aes-256-cbc';

    /**
     * Encrypts a string.
     * Appends the IV to the encrypted string, separated by '::'.
     */
    public static function encrypt( $value ) {
        if ( empty( $value ) ) return $value;

        $iv_length = openssl_cipher_iv_length( self::$cipher );
        $iv = openssl_random_pseudo_bytes( $iv_length );

        $encrypted = openssl_encrypt( $value, self::$cipher, OMNI_SALT, 0, $iv );

        return base64_encode( $encrypted . '::' . $iv );
    }

    /**
     * Decrypts a string.
     * Expects format base64(encrypted::iv).
     */
    public static function decrypt( $value ) {
        if ( empty( $value ) ) return $value;

        // Check if it looks encrypted (contains :: inside base64)
        $decoded = base64_decode( $value );
        if ( strpos( $decoded, '::' ) === false ) {
            return $value; // Return as-is (backward compatibility for unencrypted keys)
        }

        list( $encrypted_data, $iv ) = explode( '::', $decoded, 2 );

        return openssl_decrypt( $encrypted_data, self::$cipher, OMNI_SALT, 0, $iv );
    }
}
