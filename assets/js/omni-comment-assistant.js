jQuery(document).ready(function($) {
    // Elements
    var $wrapper = $('.omni-comment-assistant-wrapper');
    if ($wrapper.length === 0) return; // Exit if not present

    var $toggleBtn = $('#omni_comment_toggle');
    var $studio = $('#omni_comment_studio');
    var $promptTextarea = $('#omni_comment_prompt');
    var $generateBtn = $('#omni_comment_generate_btn');
    var $loadingIndicator = $('#omni_comment_loading');
    var $modelSelect = $('#omni_comment_model_select');
    var $refreshBtn = $('#omni_comment_refresh_models');
    var $recentList = $('#omni_recent_responses_list');
    var $recentWrapper = $('#omni_recent_responses_wrapper');

    // Target Comment Textarea
    var $targetCommentField = $('#comment');

    // 1. Toggle UI
    $toggleBtn.on('click', function() {
        $studio.slideToggle();
    });

    // 2. Load Models from Cache
    const savedModels = localStorage.getItem('omni_models_cache');
    if(savedModels) populateModelSelect(JSON.parse(savedModels));

    const savedSelection = localStorage.getItem('omni_comment_selected_model');
    if(savedSelection && $modelSelect.find(`option[value="${savedSelection}"]`).length) {
        $modelSelect.val(savedSelection);
    }

    $modelSelect.on('change', function() {
        localStorage.setItem('omni_comment_selected_model', $(this).val());
    });

    // 3. Tool Toggles (UI only, logic handled in generate)
    $('.omni-tool-btn').on('click', function() {
        if($(this).find('input[type="radio"]').length > 0) {
            $('.omni-tool-btn').removeClass('active');
            $(this).addClass('active');
            $(this).find('input').prop('checked', true);
        }
    });

    // 4. Sync Models
    $refreshBtn.on('click', function() {
        var $icon = $(this).find('.dashicons');
        $icon.addClass('spin');
        $(this).prop('disabled', true);

        $.ajax({
            url: omniData.ajax_url,
            type: 'POST',
            data: { action: 'omni_list_models', nonce: omniData.nonce },
            success: function(response) {
                if(response.success) {
                    localStorage.setItem('omni_models_cache', JSON.stringify(response.data));
                    populateModelSelect(response.data);
                } else {
                    alert('Sync Failed: ' + response.data);
                }
            },
            error: function() { alert('Connection error.'); },
            complete: function() { $icon.removeClass('spin'); $refreshBtn.prop('disabled', false); }
        });
    });

    function populateModelSelect(models) {
        $modelSelect.empty();
        const textGroup = $('<optgroup label="Text / Content"></optgroup>');
        models.forEach(m => {
            // Only adding text models for comment assistant
            if(m.type !== 'image') {
                textGroup.append(`<option value="${m.id}">${m.name} (${m.id})</option>`);
            }
        });
        $modelSelect.append(textGroup);
        const saved = localStorage.getItem('omni_comment_selected_model');
        if(saved) $modelSelect.val(saved);
    }

    // 5. Generate Content
    $generateBtn.on('click', function() {
        var prompt = $promptTextarea.val().trim();
        var toolMode = $('input[name="omni_comment_tool_mode"]:checked').val();
        var useWebSearch = $('#omni_comment_tool_web').is(':checked');
        var selectedModel = $modelSelect.val();

        if (prompt === '') { alert('Please enter a prompt.'); return; }

        $generateBtn.prop('disabled', true);
        $loadingIndicator.show();

        $.ajax({
            url: omniData.ajax_url,
            type: 'POST',
            data: {
                action: 'omni_plugin_generate_content',
                prompt: prompt,
                tool_mode: toolMode,
                use_web_search: useWebSearch,
                model: selectedModel,
                nonce: omniData.nonce
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    var content = result.content;

                    // Insert into textarea
                    insertIntoComment(content);

                    // Add to History
                    addToHistory(content);

                    $promptTextarea.val('');
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() { alert('Connection error.'); },
            complete: function() {
                $generateBtn.prop('disabled', false);
                $loadingIndicator.hide();
            }
        });
    });

    function insertIntoComment(text) {
        var currentVal = $targetCommentField.val();
        if (currentVal.trim() !== '') {
            $targetCommentField.val(currentVal + '\n\n' + text);
        } else {
            $targetCommentField.val(text);
        }
    }

    // 6. History Management
    loadHistory();

    function loadHistory() {
        var history = JSON.parse(localStorage.getItem('omni_comment_history') || '[]');
        renderHistory(history);
    }

    function addToHistory(text) {
        var history = JSON.parse(localStorage.getItem('omni_comment_history') || '[]');
        // Prepend new item
        history.unshift({ text: text, timestamp: new Date().toISOString() });
        // Keep last 3
        if (history.length > 3) history = history.slice(0, 3);

        localStorage.setItem('omni_comment_history', JSON.stringify(history));
        renderHistory(history);
    }

    function renderHistory(history) {
        $recentList.empty();
        if (history.length === 0) {
            $recentWrapper.hide();
            return;
        }
        $recentWrapper.show();

        history.forEach((item, index) => {
            var snippet = item.text.substring(0, 60).replace(/\n/g, ' ') + (item.text.length > 60 ? '...' : '');
            var $li = $('<li><a href="#" class="omni-history-item" data-index="' + index + '">' + snippet + '</a></li>');
            $recentList.append($li);
        });

        $('.omni-history-item').on('click', function(e) {
            e.preventDefault();
            var idx = $(this).data('index');
            var hist = JSON.parse(localStorage.getItem('omni_comment_history') || '[]');
            if (hist[idx]) {
                insertIntoComment(hist[idx].text);
            }
        });
    }

});
