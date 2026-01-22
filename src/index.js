/**
 * OmniQuill React Application
 * Requires compilation via @wordpress/scripts
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/edit-post';
import { PanelBody, Button, TextareaControl, ToggleControl, Spinner, SelectControl } from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { moreVertical } from '@wordpress/icons';

// --- CUSTOM PARSER (Vector 4: Structured Parsing) ---
// Breaks raw text into Block Objects without Regex fragility
const parseContentToBlocks = (text) => {
    const blocks = [];
    const lines = text.split('\n');
    let buffer = [];
    let state = 'text'; // text, code, list

    const flushBuffer = () => {
        if (buffer.length === 0) return;
        const content = buffer.join('\n');

        if (state === 'code') {
            blocks.push(createBlock('core/code', { content: content }));
        } else if (state === 'list') {
             // Simple list handling
             blocks.push(createBlock('core/list', { values: content }));
        } else {
            // Check for heading in buffer
            if (content.startsWith('### ')) {
                 blocks.push(createBlock('core/heading', { level: 3, content: content.replace('### ', '') }));
            } else if (content.startsWith('## ')) {
                 blocks.push(createBlock('core/heading', { level: 2, content: content.replace('## ', '') }));
            } else {
                 blocks.push(createBlock('core/paragraph', { content: content }));
            }
        }
        buffer = [];
    };

    lines.forEach(line => {
        const trimmed = line.trim();

        if (trimmed.startsWith('```')) {
            if (state === 'code') {
                flushBuffer();
                state = 'text';
            } else {
                flushBuffer();
                state = 'code';
            }
            return; // Skip the backtick line
        }

        if (state !== 'code' && (trimmed.startsWith('- ') || trimmed.startsWith('* '))) {
            if (state !== 'list') flushBuffer();
            state = 'list';
            buffer.push(line.replace(/^[-*]\s+/, '<li>') + '</li>'); // Quick HTML conversion for core/list
            return;
        }

        if (state === 'list' && !trimmed.startsWith('- ') && !trimmed.startsWith('* ')) {
            flushBuffer();
            state = 'text';
        }

        buffer.push(line);
    });
    flushBuffer(); // Final flush

    return blocks;
};


const OmniQuillSidebar = () => {
    // State
    const [prompt, setPrompt] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [history, setHistory] = useState([]); // Vector 5: Session History
    const [useMemory, setUseMemory] = useState(true); // Toggle
    const [model, setModel] = useState('gemini-1.5-flash');

    const { insertBlocks } = useDispatch('core/block-editor');

    const handleGenerate = async () => {
        if (!prompt) return;

        setIsLoading(true);
        const currentPrompt = prompt;
        setPrompt(''); // Clear input immediately

        // Optimistic UI update
        const newTurn = { role: 'user', text: currentPrompt };
        const tempHistory = [...history, newTurn];
        setHistory(tempHistory);

        try {
            const response = await fetch(omniSettings.ajaxUrl + '?action=omni_generate_v2', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': omniSettings.nonce
                },
                body: JSON.stringify({
                    prompt: currentPrompt,
                    history: useMemory ? history : [],
                    useMemory: useMemory,
                    model: model
                })
            });

            const data = await response.json();

            if (data.success) {
                const aiText = data.data.content;

                // Add AI response to history
                setHistory([...tempHistory, { role: 'ai', text: aiText }]);

                // Parse and Insert Blocks (Vector 4)
                const parsedBlocks = parseContentToBlocks(aiText);
                insertBlocks(parsedBlocks);
            } else {
                // Error handling
                setHistory([...tempHistory, { role: 'system', text: 'Error: ' + data.data }]);
            }

        } catch (error) {
            console.error(error);
        } finally {
            setIsLoading(false);
        }
    };

    const clearHistory = () => setHistory([]);

    return (
        <PluginSidebar name="omni-quill-sidebar" title="OmniQuill Pro" icon={moreVertical}>
            <PanelBody>
                <div className="omni-controls">
                    <SelectControl
                        label="Model"
                        value={model}
                        options={[
                            { label: 'Gemini 1.5 Flash', value: 'gemini-1.5-flash' },
                            { label: 'GPT-4o', value: 'gpt-4o' }
                        ]}
                        onChange={setModel}
                    />

                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
                        <ToggleControl
                            label="Context Memory"
                            checked={useMemory}
                            onChange={setUseMemory}
                            help={useMemory ? "AI remembers previous chat." : "Each prompt is new."}
                        />
                        <Button isSmall isDestructive variant="tertiary" onClick={clearHistory}>
                            Clear Chat
                        </Button>
                    </div>

                    <div className="omni-chat-window" style={{
                        maxHeight: '300px',
                        overflowY: 'auto',
                        background: '#f0f0f1',
                        padding: '10px',
                        borderRadius: '4px',
                        marginBottom: '10px',
                        display: history.length ? 'block' : 'none'
                    }}>
                        {history.map((turn, i) => (
                            <div key={i} style={{
                                marginBottom: '8px',
                                textAlign: turn.role === 'user' ? 'right' : 'left'
                            }}>
                                <span style={{
                                    display: 'inline-block',
                                    padding: '6px 10px',
                                    borderRadius: '12px',
                                    fontSize: '12px',
                                    background: turn.role === 'user' ? '#2271b1' : '#fff',
                                    color: turn.role === 'user' ? '#fff' : '#333',
                                    border: turn.role === 'ai' ? '1px solid #ddd' : 'none'
                                }}>
                                    {turn.text.substring(0, 100) + (turn.text.length > 100 ? '...' : '')}
                                </span>
                            </div>
                        ))}
                    </div>

                    <TextareaControl
                        label="Prompt"
                        value={prompt}
                        onChange={setPrompt}
                        rows={4}
                        placeholder="Describe the content you want..."
                    />

                    <Button
                        isPrimary
                        isBusy={isLoading}
                        onClick={handleGenerate}
                        disabled={isLoading || !prompt}
                        style={{ width: '100%', justifyContent: 'center' }}
                    >
                        {isLoading ? 'Generating...' : 'Generate Content'}
                    </Button>
                </div>
            </PanelBody>
        </PluginSidebar>
    );
};

registerPlugin('omni-quill-pro', {
    icon: 'superhero',
    render: OmniQuillSidebar,
});
