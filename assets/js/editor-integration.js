// assets/js/editor-integration.js
jQuery(document).ready(function($) {
    var $promptTextarea = $('#mp_ai_prompt');
    var $generateButton = $('#mp_ai_generate_button');
    var $loadingIndicator = $('#mp_ai_loading_indicator');

    // Function to convert basic Markdown to HTML
    function convertMarkdownToHtml(markdownText) {
        let htmlContent = markdownText;

        // 1. Convert ## Heading to <h2>Heading</h2>
        // This regex looks for '## ' at the start of a line and captures the rest of the line.
        htmlContent = htmlContent.replace(/^##\s*(.*)$/gm, '<h2>$1</h2>');

        // 2. Convert **bold text** to <strong>bold text</strong>
        // This regex looks for ** followed by any characters (non-greedy) and another **.
        htmlContent = htmlContent.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // 3. Convert double newlines into distinct HTML blocks, ensuring proper paragraphing.
        // Split the content into potential block segments using double newlines
        const segments = htmlContent.split(/\n\s*\n/);

        let finalHtmlBlocks = [];
        segments.forEach(segment => {
            segment = segment.trim();
            if (segment.length === 0) {
                return; // Skip empty segments that result from multiple newlines
            }

            // If a segment already starts with an <h2> tag, use it as is.
            if (segment.startsWith('<h2')) {
                finalHtmlBlocks.push(segment);
            } else {
                // Otherwise, wrap the segment in a <p> tag.
                // This ensures plain text lines become paragraphs.
                finalHtmlBlocks.push(`<p>${segment}</p>`);
            }
        });

        // Join the processed blocks with newlines.
        htmlContent = finalHtmlBlocks.join('\n');

        // Remove any potentially introduced empty paragraph tags that might occur if AI had some odd spacing
        htmlContent = htmlContent.replace(/<p>\s*<\/p>/g, '');

        //console.log('Converted HTML for direct parsing:', htmlContent); // Log the HTML before parsing it locally
        return htmlContent;
    }


    $generateButton.on('click', function() {
        var prompt = $promptTextarea.val().trim();

        if (prompt === '') {
            // Using a simple alert for now, consider a more styled message box for production.
            alert('Please enter a prompt.');
            return;
        }

        $generateButton.prop('disabled', true);
        $loadingIndicator.show();

        $.ajax({
            url: mpAiPluginData.ajax_url, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'mp_ai_plugin_generate_content', // Hook defined in PHP
                prompt: prompt,
                nonce: mpAiPluginData.nonce // Nonce for security
            },
            success: function(response) {
                //console.log('AI Response (AJAX success):', response); // Log the full response for debugging

                if (response.success) {
                    var aiContent = response.data; // The AI-generated content
                    //console.log('AI Content received (raw):', aiContent);

                    // Convert AI content from basic Markdown to HTML
                    const htmlForParsing = convertMarkdownToHtml(aiContent); // Use a new variable name
                    //console.log('HTML for local parsing:', htmlForParsing);

                    // Check if Gutenberg is active and fully ready
                    if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined' && typeof wp.data.dispatch !== 'undefined' && typeof wp.blocks !== 'undefined' && wp.data.select('core/block-editor')) {
                        console.log('Gutenberg editor detected and seems ready.');
                        try {
                            const blocksToInsert = [];
                            // Create a temporary DOM element to parse the HTML string
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = htmlForParsing;

                            // Iterate through child nodes to identify headings and paragraphs
                            Array.from(tempDiv.children).forEach(child => {
                                if (child.tagName === 'H2') {
                                    // Create a core/heading block with level 2
                                    blocksToInsert.push(wp.blocks.createBlock('core/heading', {
                                        level: 2,
                                        content: child.innerHTML // Get innerHTML to preserve bold tags within heading
                                    }));
                                } else if (child.tagName === 'P') {
                                    // Create a core/paragraph block
                                    blocksToInsert.push(wp.blocks.createBlock('core/paragraph', {
                                        content: child.innerHTML // Get innerHTML to preserve bold tags within paragraph
                                    }));
                                }
                                // You can add more tag types here (e.g., ul, ol, etc.) if needed
                            });

                            //console.log('Blocks created explicitly:', blocksToInsert); // Log the created blocks

                            if (blocksToInsert.length > 0) {
                                // Add a small delay before dispatching to avoid race conditions
                                setTimeout(() => {
                                    const { getSelectedBlockClientId, getBlockCount } = wp.data.select('core/block-editor');
                                    const selectedBlock = getSelectedBlockClientId();
                                    // Insert after selected block, or at the end if nothing is selected
                                    const insertIndex = selectedBlock ? wp.data.select('core/block-editor').getBlockIndex(selectedBlock) + 1 : getBlockCount();

                                    //console.log('Attempting to insert blocks at index:', insertIndex);
                                    wp.data.dispatch('core/block-editor').insertBlocks(blocksToInsert, insertIndex);
                                    //console.log('Content insertion dispatched to Gutenberg. Blocks should appear now.');

                                    // Optional: Select the first inserted block for better UX
                                    // if (blocksToInsert[0] && blocksToInsert[0].clientId) {
                                    //     wp.data.dispatch('core/block-editor').selectBlock(blocksToInsert[0].clientId);
                                    //     console.log('First inserted block selected.');
                                    // }

                                }, 50); // Small delay (e.g., 50ms)
                            } else {
                                //console.warn('Gutenberg: Explicit block creation produced no blocks. Content might be empty or in an unsupported format.');
                                alert('AI generated content was empty or could not be processed, Please try again.');
                            }
                        } catch (e) {
                            //console.error('Gutenberg insertion error:', e);
                            alert('Error inserting content, Please try again.');
                        }
                    } else {
                        //console.warn('Gutenberg editor not detected or not fully ready. Falling back to direct textarea append.');
                        // Fallback for plain HTML textarea if Gutenberg is truly not active
                        var $contentEditor = $('#content'); // Default WP content textarea ID
                        // For textarea fallback, use the raw AI content, not the HTML version.
                        $contentEditor.val($contentEditor.val() + '\n\n' + aiContent);
                    }
                    $promptTextarea.val(''); // Clear the prompt after successful generation
                } else {
                    // Display error message from the AJAX response
                    //alert('Error: ' + response.data);
                    //console.error('Server Error:', response.data);
                }
            },
            error: function(xhr, status, error) {
                // Log and display generic AJAX error
                //console.error('AJAX Error:', status, error, xhr);
                //alert('AJAX Error: Could not connect to the server or retrieve a response.');
            },
            complete: function() {
                // Re-enable button and hide loading indicator regardless of success or failure
                $generateButton.prop('disabled', false);
                $loadingIndicator.hide();
            }
        });
    });
});
