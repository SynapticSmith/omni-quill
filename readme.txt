=== MP AI Content Generator ===
Contributors: mayankbpandya
Tags: AI, Gemini, ChatGPT, Web Scraping, Image Generation, Multi-modal
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
​Multi-modal AI assistant: Generate Text, Code, Images, and Research directly in Gutenberg. Now supports Image Analysis (Vision).
​== Description ==
​Turn your WordPress editor into a powerful AI Content Studio. The "MP AI Content Generator" has evolved. It now seamlessly integrates multi-modal capabilities from Google Gemini and OpenAI (ChatGPT/DALL-E) directly into your WordPress post and page editor.
​Version 1.0.1.1 - "The Neural Studio Update"
This massive update transforms the plugin from a simple text writer into a comprehensive research and creation tool.
​👁️ Vision (Image Analysis): Select an image from your Media Library and ask the AI to describe it, write a caption, or use it as context for a blog post.
​🎨 Image Generation: Create stunning visuals using DALL-E 3 or Imagen 3 and automatically save them to your Media Library.
​🌍 Web Research: Perform live Google Searches to fetch up-to-date facts and cite sources.
​🕸️ Web Scraping: Paste a URL, and the AI will read the content to use as reference material.
​🔄 Model Sync: One-click sync button to fetch the latest models available to your API key (fixes "Model Not Found" errors).
​Features:
​Text & Code Generation: Dedicated modes for writing articles or generating clean, formatted code snippets.
​Native Blocks: Automatically converts AI output into core/heading, core/image, core/code, and core/list blocks.
​Secure: API Keys are now managed individually by each user in their Profile, ensuring privacy and separate quotas.
​Site Awareness: The AI detects your site's categories to provide better context and suggestions.
​== Installation ==
​Upload the plugin files to the /wp-content/plugins/ directory.
​Activate the plugin through the 'Plugins' screen in WordPress.
​Critical Step: Go to Users > Your Profile. Scroll down to "AI Content Generator Settings" and enter your API Key.
​Open a Post, find the "AI Content Studio" sidebar.
​First Run: Click the "Sync" icon (🔄) next to the model dropdown to load the latest available models for your key.
​== Changelog ==
​= 1.0.1.1 - The Neural Studio Update =
This update aggregates multiple feature variations, listed here chronologically from foundation to final polish:
​Phase 1: Security & Architecture
​Security: Deprecated Global API Key storage. API Keys are now strictly user-specific, stored in user_meta.
​Access: Added "AI Content Generator Settings" section to the WordPress "Your Profile" page.
​Backend: Updated AJAX handlers to retrieve credentials securely via get_current_user_id().
​Phase 2: UI Overhaul
​UI: Completely redesigned the meta box into a "Content Studio" sidebar.
​UI: Introduced a graphical Toolbar replacing the simple form layout.
​UX: Added detailed loading indicators (e.g., "Thinking...", "Searching Web...").
​Phase 3: Dynamic Model Syncing
​Feature: Replaced hardcoded model IDs with dynamic fetching.
​UI: Added a "Sync" (Refresh) button with spinning animation.
​JS: Implemented localStorage caching to reduce API calls and support new models (e.g., Gemini 1.5 Pro) instantly.
​Phase 4: Web Capabilities
​Feature: Integrated Google Custom Search JSON API for live web results.
​Feature: Added "Web Scraping" module to read external URLs via DOMDocument for context.
​UI: Added "Web Search" toggle and "Reference URL" input fields.
​Phase 5: Advanced Formatting & Code
​Feature: Added specific "Code Mode" to the toolbar.
​Editor: Added JS support to insert core/code blocks.
​Enhancement: Upgraded Markdown parser to support ### (H3), bullet lists, and italics.
​Phase 6: Image Generation
​Feature: Added "Image Generation" mode (Text-to-Image).
​API: Connected to Google Imagen and OpenAI DALL-E 3.
​System: Automatic handling of base64 responses to save generated images as PNGs in the Media Library.
​Phase 7: Vision (The Final Touch)
​Feature: Added "Vision" capabilities (Image-to-Text).
​UI: Added "Image Context" area allowing selection from the Media Library.
​API: Updated generation functions to send image payloads for analysis.
​= 1.0.1 =
​Fix: Resolved issue where content was not inserting into Gutenberg editor due to rawHandler parsing.
​Enhancement: Implemented direct creation of core/heading and core/paragraph blocks.
​Enhancement: Added basic Markdown conversion.
​= 1.0.0 =
​Initial release.=== MP AI Content Generator ===
Contributors: mayankbpandya
Tags: AI, Gemini, ChatGPT, Web Scraping, Image Generation, Multi-modal
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
​Multi-modal AI assistant: Generate Text, Code, Images, and Research directly in Gutenberg. Now supports Image Analysis (Vision).
​== Description ==
​Turn your WordPress editor into a powerful AI Content Studio. The "MP AI Content Generator" has evolved. It now seamlessly integrates multi-modal capabilities from Google Gemini and OpenAI (ChatGPT/DALL-E) directly into your WordPress post and page editor.
​Version 1.0.1.1 - "The Neural Studio Update"
This massive update transforms the plugin from a simple text writer into a comprehensive research and creation tool.
​👁️ Vision (Image Analysis): Select an image from your Media Library and ask the AI to describe it, write a caption, or use it as context for a blog post.
​🎨 Image Generation: Create stunning visuals using DALL-E 3 or Imagen 3 and automatically save them to your Media Library.
​🌍 Web Research: Perform live Google Searches to fetch up-to-date facts and cite sources.
​🕸️ Web Scraping: Paste a URL, and the AI will read the content to use as reference material.
​🔄 Model Sync: One-click sync button to fetch the latest models available to your API key (fixes "Model Not Found" errors).
​Features:
​Text & Code Generation: Dedicated modes for writing articles or generating clean, formatted code snippets.
​Native Blocks: Automatically converts AI output into core/heading, core/image, core/code, and core/list blocks.
​Secure: API Keys are now managed individually by each user in their Profile, ensuring privacy and separate quotas.
​Site Awareness: The AI detects your site's categories to provide better context and suggestions.
​== Installation ==
​Upload the plugin files to the /wp-content/plugins/ directory.
​Activate the plugin through the 'Plugins' screen in WordPress.
​Critical Step: Go to Users > Your Profile. Scroll down to "AI Content Generator Settings" and enter your API Key.
​Open a Post, find the "AI Content Studio" sidebar.
​First Run: Click the "Sync" icon (🔄) next to the model dropdown to load the latest available models for your key.
​== Changelog ==
​= 1.0.1.1 - The Neural Studio Update =
This update aggregates multiple feature variations, listed here chronologically from foundation to final polish:
​Phase 1: Security & Architecture
​Security: Deprecated Global API Key storage. API Keys are now strictly user-specific, stored in user_meta.
​Access: Added "AI Content Generator Settings" section to the WordPress "Your Profile" page.
​Backend: Updated AJAX handlers to retrieve credentials securely via get_current_user_id().
​Phase 2: UI Overhaul
​UI: Completely redesigned the meta box into a "Content Studio" sidebar.
​UI: Introduced a graphical Toolbar replacing the simple form layout.
​UX: Added detailed loading indicators (e.g., "Thinking...", "Searching Web...").
​Phase 3: Dynamic Model Syncing
​Feature: Replaced hardcoded model IDs with dynamic fetching.
​UI: Added a "Sync" (Refresh) button with spinning animation.
​JS: Implemented localStorage caching to reduce API calls and support new models (e.g., Gemini 1.5 Pro) instantly.
​Phase 4: Web Capabilities
​Feature: Integrated Google Custom Search JSON API for live web results.
​Feature: Added "Web Scraping" module to read external URLs via DOMDocument for context.
​UI: Added "Web Search" toggle and "Reference URL" input fields.
​Phase 5: Advanced Formatting & Code
​Feature: Added specific "Code Mode" to the toolbar.
​Editor: Added JS support to insert core/code blocks.
​Enhancement: Upgraded Markdown parser to support ### (H3), bullet lists, and italics.
​Phase 6: Image Generation
​Feature: Added "Image Generation" mode (Text-to-Image).
​API: Connected to Google Imagen and OpenAI DALL-E 3.
​System: Automatic handling of base64 responses to save generated images as PNGs in the Media Library.
​Phase 7: Vision (The Final Touch)
​Feature: Added "Vision" capabilities (Image-to-Text).
​UI: Added "Image Context" area allowing selection from the Media Library.
​API: Updated generation functions to send image payloads for analysis.
​= 1.0.1 =
​Fix: Resolved issue where content was not inserting into Gutenberg editor due to rawHandler parsing.
​Enhancement: Implemented direct creation of core/heading and core/paragraph blocks.
​Enhancement: Added basic Markdown conversion.
​= 1.0.0 =
​Initial release.
