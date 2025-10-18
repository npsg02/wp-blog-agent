/* WP Blog Agent Admin JavaScript */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Store current provider selection across tabs
        let currentProvider = $('#ai_provider').val();
        
        // Function to toggle fields based on provider (works across tabs)
        function toggleProviderFields(provider) {
            currentProvider = provider;
            
            // API Credentials tab fields
            if (provider === 'openai') {
                $('#openai_api_key').closest('tr').show();
                $('#openai_base_url').closest('tr').show();
                $('#gemini_api_key').closest('tr').hide();
                $('#gemini_image_api_key').closest('tr').hide();
                $('#ollama_base_url').closest('tr').hide();
            } else if (provider === 'gemini') {
                $('#openai_api_key').closest('tr').hide();
                $('#openai_base_url').closest('tr').hide();
                $('#gemini_api_key').closest('tr').show();
                $('#gemini_image_api_key').closest('tr').show();
                $('#ollama_base_url').closest('tr').hide();
            } else if (provider === 'ollama') {
                $('#openai_api_key').closest('tr').hide();
                $('#openai_base_url').closest('tr').hide();
                $('#gemini_api_key').closest('tr').hide();
                $('#gemini_image_api_key').closest('tr').hide();
                $('#ollama_base_url').closest('tr').show();
            }
            
            // General Settings tab fields
            if (provider === 'openai') {
                $('#openai_model').closest('tr').show();
                $('#openai_max_tokens').closest('tr').show();
                $('#openai_system_prompt').closest('tr').show();
                $('#gemini_model').closest('tr').hide();
                $('#gemini_max_tokens').closest('tr').hide();
                $('#gemini_system_prompt').closest('tr').hide();
                $('#ollama_model').closest('tr').hide();
                $('#ollama_system_prompt').closest('tr').hide();
            } else if (provider === 'gemini') {
                $('#openai_model').closest('tr').hide();
                $('#openai_max_tokens').closest('tr').hide();
                $('#openai_system_prompt').closest('tr').hide();
                $('#gemini_model').closest('tr').show();
                $('#gemini_max_tokens').closest('tr').show();
                $('#gemini_system_prompt').closest('tr').show();
                $('#ollama_model').closest('tr').hide();
                $('#ollama_system_prompt').closest('tr').hide();
            } else if (provider === 'ollama') {
                $('#openai_model').closest('tr').hide();
                $('#openai_max_tokens').closest('tr').hide();
                $('#openai_system_prompt').closest('tr').hide();
                $('#gemini_model').closest('tr').hide();
                $('#gemini_max_tokens').closest('tr').hide();
                $('#gemini_system_prompt').closest('tr').hide();
                $('#ollama_model').closest('tr').show();
                $('#ollama_system_prompt').closest('tr').show();
            }
        }
        
        // Toggle API key fields based on selected provider
        $('#ai_provider').on('change', function() {
            toggleProviderFields($(this).val());
        }).trigger('change');
        
        // Toggle schedule frequency based on enabled status
        $('#schedule_enabled').on('change', function() {
            const enabled = $(this).val();
            
            if (enabled === 'yes') {
                $('#schedule_frequency').closest('tr').show();
            } else {
                $('#schedule_frequency').closest('tr').hide();
            }
        }).trigger('change');
        
        // Form validation for API Credentials
        $('form').on('submit', function(e) {
            const $form = $(this);
            const isApiForm = $form.find('input[name="wp_blog_agent_api_settings_nonce"]').length > 0;
            
            if (isApiForm) {
                const provider = $('#ai_provider').val();
                let apiKey = '';
                
                if (provider === 'openai') {
                    apiKey = $('#openai_api_key').val();
                } else if (provider === 'gemini') {
                    apiKey = $('#gemini_api_key').val();
                }
                // Ollama doesn't require an API key
                
                if (!apiKey && provider !== 'ollama') {
                    e.preventDefault();
                    alert('Please enter an API key for the selected AI provider.');
                    return false;
                }
            }
        });
        
        // Auto-dismiss notices after 5 seconds
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);
        
        // Handle Generate SEO button click
        $(document).on('click', 'button.wp-blog-agent-generate-seo', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const postId = $button.data('post-id');
            const originalText = $button.text();
            
            // Disable button and show loading state
            $button.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: wpBlogAgent.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_blog_agent_generate_seo',
                    post_id: postId,
                    nonce: wpBlogAgent.seoNonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('SEO meta generated successfully!\n\nDescription: ' + response.data.description + '\n\nFocus Keyword: ' + response.data.keyword);
                        $button.prop('disabled', false).text(originalText);
                    } else {
                        alert('Error: ' + response.data.message);
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error generating SEO meta: ' + error);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle Generate Image button click
        $(document).on('click', 'button.wp-blog-agent-generate-image', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const postId = $button.data('post-id');
            const originalText = $button.text();
            
            // Disable button and show loading state
            $button.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: wpBlogAgent.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_blog_agent_generate_post_image',
                    post_id: postId,
                    nonce: wpBlogAgent.imageNonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Featured image generated successfully!');
                        $button.prop('disabled', false).text(originalText);
                        // Optionally reload the page to show the new image
                        // location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error generating image: ' + error);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
        
    });
    
})(jQuery);
