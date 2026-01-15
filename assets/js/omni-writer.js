jQuery(document).ready(function($) {
    var $promptTextarea = $('#omni_prompt');
    var $refUrlInput = $('#omni_ref_url');
    var $generateButton = $('#omni_generate_button');
    var $loadingIndicator = $('#omni_loading_indicator');
    var $loadingText = $('#omni_loading_text');
    var $messageArea = $('#omni_message_area');
    var $modelSelect = $('#omni_model_select');
    var $refreshBtn = $('#omni_refresh_models');
    
    // Image Context Elements
    var $imgContextArea = $('#omni_image_context_area');
    var $imgContextId = $('#omni_context_image_id');
    var $imgPreview = $('#omni_context_image_preview');
    var $selectImgBtn = $('#omni_select_image_btn');
    var $clearImgBtn = $('#omni_clear_image_btn');

    // Load saved models
    const savedModels = localStorage.getItem('omni_models_cache');
    if(savedModels) populateModelSelect(JSON.parse(savedModels));
    
    const savedSelection = localStorage.getItem('omni_selected_model');
    if(savedSelection && $modelSelect.find(`option[value="${savedSelection}"]`).length) $modelSelect.val(savedSelection);

    $modelSelect.on('change', function() { localStorage.setItem('omni_selected_model', $(this).val()); });

    // Tool Toggles
    $('.omni-tool-btn').on('click', function() {
        if($(this).find('input[type="radio"]').length > 0) {
            $('.omni-tool-btn').removeClass('active');
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
    $('input[name="omni_tool_mode"]:checked').closest('.omni-tool-btn').click();

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
            url: omniData.ajax_url,
            type: 'POST',
            data: { action: 'omni_list_models', nonce: omniData.nonce },
            success: function(response) {
                if(response.success) {
                    localStorage.setItem('omni_models_cache', JSON.stringify(response.data));
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
        const saved = localStorage.getItem('omni_selected_model');
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
        var toolMode = $('input[name="omni_tool_mode"]:checked').val();
        var useWebSearch = $('#omni_tool_web').is(':checked');
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
            url: omniData.ajax_url,
            type: 'POST',
            data: {
                action: 'omni_plugin_generate_content',
                prompt: prompt,
                ref_url: refUrl,
                tool_mode: toolMode,
                use_web_search: useWebSearch,
                model: selectedModel,
                context_image_id: contextImgId,
                nonce: omniData.nonce
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
