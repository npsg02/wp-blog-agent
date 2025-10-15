<?php
/**
 * Gemini Image Generation API Integration
 */
class WP_Blog_Agent_Image_Generator {
    
    private $api_key;
    private $model_id;
    
    public function __construct() {
        $this->api_key = get_option('wp_blog_agent_gemini_image_api_key', '');
        $this->model_id = 'models/imagen-3.0-generate-001';
    }
    
    /**
     * Generate image using Gemini Imagen API
     * 
     * @param string $prompt The image generation prompt
     * @param array $params Additional parameters (aspectRatio, imageSize, sampleCount)
     * @return array|WP_Error Array of image data or WP_Error on failure
     */
    public function generate_image($prompt, $params = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Gemini Image API key is not configured.');
        }
        
        // Default parameters
        $defaults = array(
            'aspectRatio' => '16:9',
            'imageSize' => '1K',
            'sampleCount' => 1,
            'outputMimeType' => 'image/jpeg',
            'personGeneration' => 'ALLOW_ALL'
        );
        
        $params = wp_parse_args($params, $defaults);
        
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/' . $this->model_id . ':predict';
        $url = $api_url . '?key=' . $this->api_key;
        
        $request_body = array(
            'instances' => array(
                array('prompt' => $prompt)
            ),
            'parameters' => $params
        );
        
        // Log request
        WP_Blog_Agent_Logger::info('Gemini Image API Request', array(
            'url' => $api_url,
            'model' => $this->model_id,
            'prompt' => substr($prompt, 0, 100),
            'params' => $params
        ));
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_body),
            'timeout' => 120, // Image generation may take longer
        ));
        
        if (is_wp_error($response)) {
            WP_Blog_Agent_Logger::error('Gemini Image API Error', array(
                'error' => $response->get_error_message()
            ));
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log response
        WP_Blog_Agent_Logger::info('Gemini Image API Response', array(
            'status_code' => $status_code,
            'has_predictions' => isset($body['predictions']),
            'predictions_count' => isset($body['predictions']) ? count($body['predictions']) : 0
        ));
        
        // Handle HTTP errors
        if ($status_code !== 200) {
            $error_message = 'Gemini Image API returned status code ' . $status_code;
            if (isset($body['error']['message'])) {
                $error_message .= ': ' . $body['error']['message'];
            } elseif (isset($body['error'])) {
                $error_message .= ': ' . (is_string($body['error']) ? $body['error'] : json_encode($body['error']));
            }
            WP_Blog_Agent_Logger::error('Gemini Image API HTTP Error', array(
                'status_code' => $status_code,
                'body' => $body
            ));
            return new WP_Error('gemini_image_http_error', $error_message);
        }
        
        // Validate response structure
        if (!isset($body['predictions']) || !is_array($body['predictions']) || empty($body['predictions'])) {
            WP_Blog_Agent_Logger::error('Gemini Image API Missing Predictions', array('body' => $body));
            return new WP_Error('invalid_response', 'Invalid response from Gemini Image API: No predictions returned.');
        }
        
        $images = array();
        foreach ($body['predictions'] as $prediction) {
            if (isset($prediction['bytesBase64Encoded']) && !empty($prediction['bytesBase64Encoded'])) {
                $images[] = array(
                    'base64' => $prediction['bytesBase64Encoded'],
                    'mime_type' => $params['outputMimeType']
                );
            }
        }
        
        if (empty($images)) {
            WP_Blog_Agent_Logger::error('Gemini Image API No Valid Images', array('predictions' => $body['predictions']));
            return new WP_Error('no_images', 'No valid images returned from Gemini Image API.');
        }
        
        WP_Blog_Agent_Logger::info('Images Generated Successfully', array(
            'count' => count($images)
        ));
        
        return $images;
    }
    
    /**
     * Upload image to WordPress media library
     * 
     * @param string $base64_data Base64 encoded image data
     * @param string $filename Filename for the image
     * @param string $mime_type MIME type of the image
     * @param int $post_id Optional post ID to attach the image to
     * @return int|WP_Error Attachment ID or WP_Error on failure
     */
    public function upload_to_media_library($base64_data, $filename, $mime_type = 'image/jpeg', $post_id = 0) {
        // Decode base64 data
        $image_data = base64_decode($base64_data);
        
        if ($image_data === false) {
            return new WP_Error('decode_error', 'Failed to decode base64 image data.');
        }
        
        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();
        
        // Generate unique filename
        $file_ext = $mime_type === 'image/jpeg' ? 'jpg' : 'png';
        $file_name = sanitize_file_name($filename) . '_' . time() . '.' . $file_ext;
        $file_path = $upload_dir['path'] . '/' . $file_name;
        
        // Save image data to file
        $saved = file_put_contents($file_path, $image_data);
        
        if ($saved === false) {
            return new WP_Error('save_error', 'Failed to save image file.');
        }
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $mime_type,
            'post_title' => sanitize_text_field($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
        
        if (is_wp_error($attach_id)) {
            return $attach_id;
        }
        
        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        WP_Blog_Agent_Logger::info('Image Uploaded to Media Library', array(
            'attachment_id' => $attach_id,
            'filename' => $file_name
        ));
        
        return $attach_id;
    }
    
    /**
     * Generate and save image for a blog post
     * 
     * @param string $prompt Image generation prompt
     * @param int $post_id Optional post ID to attach the image to
     * @param array $params Additional generation parameters
     * @return int|WP_Error Attachment ID or WP_Error on failure
     */
    public function generate_and_save($prompt, $post_id = 0, $params = array()) {
        // Generate image
        $images = $this->generate_image($prompt, $params);
        
        if (is_wp_error($images)) {
            return $images;
        }
        
        // Use the first generated image
        $image = $images[0];
        
        // Create filename from prompt
        $filename = sanitize_title(substr($prompt, 0, 50));
        if (empty($filename)) {
            $filename = 'generated-image';
        }
        
        // Upload to media library
        $attachment_id = $this->upload_to_media_library(
            $image['base64'],
            $filename,
            $image['mime_type'],
            $post_id
        );
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Set as featured image if post_id is provided
        if ($post_id > 0) {
            set_post_thumbnail($post_id, $attachment_id);
            WP_Blog_Agent_Logger::info('Image Set as Featured Image', array(
                'post_id' => $post_id,
                'attachment_id' => $attachment_id
            ));
        }
        
        return $attachment_id;
    }
}
