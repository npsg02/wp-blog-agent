/* WP Blog Agent Admin JavaScript */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Toggle API key fields based on selected provider
        $('#ai_provider').on('change', function() {
            const provider = $(this).val();
            
            if (provider === 'openai') {
                $('#openai_api_key').closest('tr').show();
                $('#gemini_api_key').closest('tr').hide();
            } else if (provider === 'gemini') {
                $('#openai_api_key').closest('tr').hide();
                $('#gemini_api_key').closest('tr').show();
            }
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
        
        // Form validation
        $('form').on('submit', function(e) {
            const provider = $('#ai_provider').val();
            let apiKey = '';
            
            if (provider === 'openai') {
                apiKey = $('#openai_api_key').val();
            } else if (provider === 'gemini') {
                apiKey = $('#gemini_api_key').val();
            }
            
            if (!apiKey && $('#schedule_enabled').val() === 'yes') {
                e.preventDefault();
                alert('Please enter an API key for the selected AI provider before enabling scheduling.');
                return false;
            }
        });
        
        // Auto-dismiss notices after 5 seconds
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut();
        }, 5000);
        
    });
    
})(jQuery);
