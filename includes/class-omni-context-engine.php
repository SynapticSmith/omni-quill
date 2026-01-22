<?php
/**
 * Class OmniQuill_Context_Engine
 * Handles auxiliary data processing.
 * Maintained for compatibility with the new architecture.
 */
class OmniQuill_Context_Engine {

    public static function get_image_base64( $media_id ) {
        $path = get_attached_file( $media_id );
        if ( ! file_exists( $path ) ) return new WP_Error( 'file_not_found', 'Image file missing.' );

        $type = get_post_mime_type( $media_id );
        $data = file_get_contents( $path );
        return [
            'base64' => base64_encode( $data ),
            'mime'   => $type
        ];
    }
}
