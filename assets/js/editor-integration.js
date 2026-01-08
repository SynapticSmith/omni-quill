jQuery(document).ready(function($) {
    var $promptTextarea = $('#mp_ai_prompt');
    var $refUrlInput = $('#mp_ai_ref_url');
    var $generateButton = $('#mp_ai_generate_button');
    var $loadingIndicator = $('#mp_ai_loading_indicator');
    var $loadingText = $('#mp_ai_loading_text');
    var $messageArea = $('#mp_ai_message_area');
    var $modelSelect = $('#mp_ai_model_select');
    var $refreshBtn = $('#mp_ai_refresh_models');
    
    // Image Context Elements
    var $imgContextArea = $('#mp_ai_image_context_area');
    var $imgContextId = $('#mp_ai_context_image_id');
    var $imgPreview = $('#mp_ai_context_image_preview');
    var $selectImgBtn = $('#mp_ai_select_image_btn');
    var $clearImgBtn = $('#mp_ai_clear_image_btn');

    // Load saved models
    const savedModels = localStorage.getItem('mp_ai_models_cache');
    if(savedModels) populateModelSelect(JSON.parse(savedModels));
    
    const savedSelection = localStorage.getItem('mp_ai_selected_model');
    if(savedSelection && $modelSelect.find(`option[value="${savedSelection}"]`).length) $modelSelect.val(savedSelection);

    $modelSelect.on('change', function() { localStorage.setItem('mp_ai_selected_model', $(this).val()); });

    // Tool Toggles
    $('.mp-tool-btn').on('click', function() {
        if($(this).find('input[type="radio"]').length > 0) {
            $('.mp-tool-btn').removeClass('active');
            $(this).addClass('active');
            
            // Toggle Image Context visibility (Hide if generating image)
            if($(this).find('input').val() === 'image') {
                $imgContextArea.hide();
            } else {
                $imgContextArea.show();
            }
        }
        $(this).find('input').prop('checked', true);
    });
    
    // Trigger initial visibility check
    $('input[name="mp_tool_mode"]:checked').closest('.mp-tool-btn').click();

    // --- WP MEDIA UPLOADER ---
    var file_frame;
    $selectImgBtn.on('click', function(event) {
        event.preventDefault();
        if (file_frame) { file_frame.open(); return; }
        
        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select Image for AI Context',
            button: { text: 'Use as Context' },
            multiple: false
        });

        file_frame.on('select', function() {
            var attachment = file_frame.state().get('selection').first().toJSON();
            $imgContextId.val(attachment.id);
            $imgPreview.html(`<img src="${attachment.url}" style="max-width:100%; height:auto; border-radius:4px;">`).show();
            $selectImgBtn.hide();
            $clearImgBtn.show();
        });

        file_frame.open();
    });

    $clearImgBtn.on('click', function() {
        $imgContextId.val('');
        $imgPreview.empty().hide();
        $selectImgBtn.show();
        $clearImgBtn.hide();
    });

    // --- SYNC MODELS ---
    $refreshBtn.on('click', function() {
        var $icon = $(this).find('.dashicons');
        $icon.addClass('spin');
        $(this).prop('disabled', true);

        $.ajax({
            url: mpAiPluginData.ajax_url,
            type: 'POST',
            data: { action: 'mp_ai_list_models', nonce: mpAiPluginData.nonce },
            success: function(response) {
                if(response.success) {
                    localStorage.setItem('mp_ai_models_cache', JSON.stringify(response.data));
                    populateModelSelect(response.data);
                    showMessage('Models synced successfully!', 'success');
                } else {
                    showMessage('Sync Failed: ' + response.data, 'error');
                }
            },
            error: function() { showMessage('Connection error.', 'error'); },
            complete: function() { $icon.removeClass('spin'); $refreshBtn.prop('disabled', false); }
        });
    });

    function populateModelSelect(models) {
        $modelSelect.empty();
        const textGroup = $('<optgroup label="Text / Content"></optgroup>');
        const imageGroup = $('<optgroup label="Image / Vision"></optgroup>');
        models.forEach(m => {
            const option = `<option value="${m.id}">${m.name} (${m.id})</option>`;
            if(m.type === 'image') imageGroup.append(option);
            else textGroup.append(option);
        });
        $modelSelect.append(textGroup).append(imageGroup);
        const saved = localStorage.getItem('mp_ai_selected_model');
        if(saved) $modelSelect.val(saved);
    }

    function showMessage(msg, type) {
        var color = type === 'error' ? '#d63638' : '#00a32a';
        $messageArea.html('<p style="color:' + color + '; margin: 5px 0;">' + msg + '</p>').show();
    }
    
    function clearMessage() { $messageArea.hide().empty(); }

    function convertMarkdownToHtml(markdownText) {
        let html = markdownText
            .replace(/^###\s*(.*)$/gm, '<h3>$1</h3>')
            .replace(/^##\s*(.*)$/gm, '<h2>$1</h2>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>');
        const segments = html.split(/\n\s*\n/);
        let blocks = [];
        segments.forEach(seg => {
            seg = seg.trim();
            if(!seg) return;
            if(seg.startsWith('<h2') || seg.startsWith('<h3')) blocks.push(seg);
            else if(/^[\t ]*[-*][\t ]+/m.test(seg)) {
                let list = '<ul>' + seg.split('\n').map(l => {
                    return /^[\t ]*[-*][\t ]+/.test(l) ? `<li>${l.replace(/^[\t ]*[-*][\t ]+/, '')}</li>` : '';
                }).join('') + '</ul>';
                blocks.push(list);
            } else {
                blocks.push(`<p>${seg}</p>`);
            }
        });
        return blocks.join('');
    }

    $generateButton.on('click', function() {
        clearMessage();
        var prompt = $promptTextarea.val().trim();
        var refUrl = $refUrlInput.val().trim();
        var toolMode = $('input[name="mp_tool_mode"]:checked').val();
        var useWebSearch = $('#mp_tool_web').is(':checked');
        var selectedModel = $modelSelect.val();
        var contextImgId = $imgContextId.val();

        if (prompt === '') { showMessage('Please enter a prompt.', 'error'); return; }

        $generateButton.prop('disabled', true);
        $loadingIndicator.css('display', 'inline-flex');
        
        let status = 'Thinking...';
        if(toolMode === 'image') status = 'Generating Image...';
        else if(contextImgId) status = 'Analyzing Image...';
        else if(useWebSearch) status = 'Searching Web...';
        $loadingText.text(status);

        $.ajax({
            url: mpAiPluginData.ajax_url,
            type: 'POST',
            data: {
                action: 'mp_ai_plugin_generate_content',
                prompt: prompt,
                ref_url: refUrl,
                tool_mode: toolMode,
                use_web_search: useWebSearch,
                model: selectedModel,
                context_image_id: contextImgId,
                nonce: mpAiPluginData.nonce
            },
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    if (typeof wp !== 'undefined' && wp.blocks && wp.data.select('core/block-editor')) {
                        const { createBlock } = wp.blocks;
                        const { insertBlocks } = wp.data.dispatch('core/block-editor');
                        
                        if (result.type === 'image') {
                            const imgBlock = createBlock('core/image', { id: result.media_id, url: result.url, alt: result.alt });
                            insertBlocks(imgBlock);
                            showMessage('Image inserted successfully!', 'success');
                        } else if (result.type === 'code') {
                            const codeBlock = createBlock('core/code', { content: result.content });
                            insertBlocks(codeBlock);
                        } else {
                            const html = convertMarkdownToHtml(result.content);
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = html;
                            const blocks = [];
                            Array.from(tempDiv.children).forEach(child => {
                                if (child.tagName === 'H2') blocks.push(createBlock('core/heading', { level: 2, content: child.innerHTML }));
                                else if (child.tagName === 'H3') blocks.push(createBlock('core/heading', { level: 3, content: child.innerHTML }));
                                else if (child.tagName === 'UL') blocks.push(createBlock('core/list', { ordered: false, values: child.innerHTML }));
                                else blocks.push(createBlock('core/paragraph', { content: child.innerHTML }));
                            });
                            insertBlocks(blocks);
                        }
                    } else {
                        $('#content').val($('#content').val() + '\n\n' + (result.content || result.url));
                    }
                    $promptTextarea.val(''); 
                } else {
                    showMessage('Error: ' + response.data, 'error');
                }
            },
            error: function() { showMessage('Connection error.', 'error'); },
            complete: function() {
                $generateButton.prop('disabled', false);
                $loadingIndicator.hide();
            }
        });
    });
});
