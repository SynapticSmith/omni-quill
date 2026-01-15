# MP AI Content Generator (Multimodal AI Studio)

**Version:** 1.0.1.1  
**Requires PHP:** 7.4+  
**Requires WP:** 5.8+  

## 📖 Overview

MP AI Content Generator is a high-performance WordPress plugin that integrates multimodal AI capabilities directly into the WordPress Block Editor (Gutenberg). It allows creators to generate text, code, and images using **Google Gemini** or **OpenAI** models. 

Unlike standard wrappers, this plugin features **multimodal context awareness** (analyzing images from your library), **real-time web search augmentation**, and **native Gutenberg block generation**.

Part 1: Full Code Inspection
This inspection maps the architecture, logic flow, and specific functionalities of the MP AI Content Generator plugin.
1. File Structure & Organization
The plugin follows a standard WordPress plugin structure:
 * Root: Contains the main plugin file (mp-ai-content-generator.php) which handles initialization, backend logic, and API integrations.
 * Admin: Contains settings-page.php for global configuration.
 * Assets: Separates concerns into css/editor-styles.css (UI styling) and js/editor-integration.js (frontend logic and Block Editor integration).
2. Core Backend Logic (mp-ai-content-generator.php)
This file is the engine of the plugin.
 * Initialization:
   * Defines constants for directory paths and URLs.
   * Enqueues the block editor scripts (mp-ai-editor-script) and styles, passing a nonce and AJAX URL via wp_localize_script for secure frontend-backend communication.
   * Adds a custom Meta Box ("AI Content Studio") to the Post and Page editors.
 * User Configuration (Profile-Based):
   * Unlike many plugins that use global keys, this plugin attaches sensitive API keys (Google Gemini, OpenAI, Google Search) to the User Profile. This allows multiple authors to use their own quotas.
 * AJAX Endpoint: Model Listing (mp_ai_list_models):
   * Dynamically fetches models from Google's generativelanguage API using the user's API key.
   * Filters models based on capabilities (generateContent for text, predict/generateImage for images).
   * Hardcodes common OpenAI models (GPT-4, DALL-E) if the provider is not Gemini.
 * AJAX Endpoint: Content Generation (mp_ai_plugin_generate_content):
   * Security: Validates nonce and checks if the user has edit_posts capabilities.
   * Mode Handling:
     * Image Mode: Calls provider-specific image functions. If successful, it uploads the base64 image to the WP Media Library and returns the attachment metadata.
     * Text/Code Mode: Builds a prompt context.
       * Web Search: If enabled, queries Google Custom Search and appends snippets to the prompt.
       * Scraping: Fetches a reference URL, strips scripts/styles, and appends content.
       * Vision (Multimodal): If an image is selected in the UI, it fetches the base64 data and mime type to send to the model (Gemini or GPT-4o).
       * System Instructions: Varies instructions based on whether the user wants "Code" (developer persona) or "Standard Text" (Markdown formatting).
3. Admin & Settings (admin/settings-page.php)
 * Global Provider Selection: Allows an administrator to set the default AI Provider (Gemini or OpenAI).
 * Model Override: Provides a field to manually override the model ID string.
 * Menu Registration: Adds a top-level menu item "AI Content Gen".
4. Frontend Logic (assets/js/editor-integration.js)
 * State Management: Uses localStorage to cache the list of available models and the user's last selected model to reduce API calls.
 * Media Integration: Uses wp.media frames to allow users to select an image from the library to use as context for the AI (e.g., "Describe this image").
 * Block Editor (Gutenberg) Integration:
   * Markdown Parsing: Contains a custom function convertMarkdownToHtml that transforms AI text (Markdown) into HTML tags.
   * Block Insertion: Dynamically creates WordPress blocks based on the AI output:
     * core/image for generated images.
     * core/code for code snippets.
     * core/heading, core/list, and core/paragraph for standard text.
Part 2: Technical README Documentation


---

## 🚀 Key Features

* **Multimodal AI Support:** Send both text prompts and images to models like Gemini 1.5 Flash or GPT-4o for analysis and captioning.
* **Native Block Generation:** Automatically converts AI responses into native WordPress blocks (Headings, Paragraphs, Lists, Code, Images).
* **Dynamic Model Syncing:** Fetches the latest available models directly from the API, ensuring you always have access to the newest versions.
* **Web Search Augmentation:** Enhances AI accuracy by injecting real-time Google Search results into the prompt context.
* **Context Scraping:** Scrapes content from external URLs to use as reference material for content generation.
* **Image Generation:** Generates images (DALL-E 3 or Imagen) and automatically uploads them to the WordPress Media Library.

---

## 🛠 Technical Architecture

### Directory Structure

synapticsmith-wp-multimodal-ai-studio/
├── mp-ai-content-generator.php       # Core plugin logic & API handlers
├── admin/
│   └── settings-page.php             # Global settings (Provider selection)
└── assets/
    ├── css/
    │   └── editor-styles.css         # Editor UI styling
    └── js/
        └── editor-integration.js     # Block Editor integration & State

Data Flow
 * User Input: The user interacts with the "AI Content Studio" meta box in the editor.
 * Context Assembly (JS): The JS collects the prompt, tool mode (Text/Image/Code), and optional context (reference URL or Image ID).
 * AJAX Request: Data is sent to wp_ajax_mp_ai_plugin_generate_content.
 * Backend Processing (PHP):
   * Authentication: Verifies Nonce and User Capabilities.
   * Augmentation: Fetches external data (Google Search/URL Scrape) if requested.
   * API Dispatch: Calls mp_ai_call_gemini_text or mp_ai_call_openai_text.
 * Block Construction (JS): The raw Markdown response is parsed client-side and converted into wp.blocks.createBlock commands.
⚙️ Configuration & Setup
1. Global Settings
Navigate to Settings > AI Content Gen.
 * AI Provider: Select between Google Gemini (default) or OpenAI.
 * Model Name: Optionally override the default model ID string.
2. User API Keys
Security Note: API keys are stored per-user in the WordPress User Profile. This allows multi-author teams to manage their own quotas.
Navigate to Users > Profile and scroll to "AI Content Generator Settings".
 * AI Model API Key: Enter your Google Gemini API Key or OpenAI Secret Key.
 * Google Search API Key: (Optional) Key for Custom Search JSON API.
 * Search Engine ID (CX): (Optional) Your Google Custom Search Engine ID.
🔌 API Integration Details
Google Gemini Integration
 * Text/Multimodal: Uses the v1beta/models/{model}:generateContent endpoint.
   * Supports inlineData for base64 image transmission.
 * Image Generation: Uses v1beta/models/{model}:predict (Vertex AI style) or falls back to specific generation methods.
OpenAI Integration
 * Text/Vision: Uses v1/chat/completions.
   * Supports image_url payload for vision tasks.
 * Image Generation: Uses v1/images/generations (DALL-E 3) requesting b64_json response format.
Web & Scraper
 * Search: Google Custom Search JSON API (googleapis.com/customsearch/v1).
 * Scraper: Uses DOMDocument to load HTML from a URL, stripping <script> and <style> tags to extract clean text context.
💻 Frontend & Block Logic
The editor-integration.js file handles the bridge between the raw AI text and WordPress Blocks.
Markdown to Blocks
The system uses a custom parser convertMarkdownToHtml to segment the AI response:
 * ## H2 → core/heading (Level 2)
 * ### H3 → core/heading (Level 3)
 * - List items → core/list
 * Standard text → core/paragraph
Image Context
When a user selects an image for analysis:
 * The Media Library frame opens via wp.media.
 * The Attachment ID is stored in #mp_ai_context_image_id.
 * On generation, the PHP backend retrieves the file path via get_attached_file, converts it to Base64, and sends it to the vision-capable model.
🛡 Security
 * Nonce Verification: All AJAX requests are protected with check_ajax_referer('mp_ai_plugin_nonce').
 * Capability Checks: Restricted to users with edit_posts capability.
 * Input Sanitization: Uses sanitize_textarea_field, sanitize_text_field, and esc_url_raw on all incoming POST data [cite: 45-47].
 * [cite_start]Output Escaping: Attributes are escaped using esc_attr and URLs with esc_url.
🐛 Troubleshooting
| Issue | Possible Cause | Solution |
|---|---|---|
| "Missing API Key" | User Profile field is empty. | Go to Users > Profile and add your API key. |
| Image Generation Fails | Incorrect Model / API permissions. | Ensure your API key has access to Imagen (Google) or DALL-E (OpenAI). |
| Model List Empty | Sync failure. | Click the "Refresh" (Sync) icon next to the model dropdown to fetch fresh models. |
| "Refused to connect" | CORS or Server Config. | Ensure your server allows outgoing requests to googleapis.com or api.openai.com. |

