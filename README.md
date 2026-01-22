# OmniQuill Pro (v2.0.0)

**Multimodal AI integration for WordPress.**

OmniQuill Pro replaces standard AI wrappers with a deeply integrated, polymorphic architecture. It features a native React Sidebar within Gutenberg, encrypted per-user API key storage, and a "Strategy Pattern" gateway that switches seamlessly between Google Gemini and OpenAI.

## 🚀 Key Features

* **Native Gutenberg Integration:** Built with React and `@wordpress/scripts`. [span_0](start_span)The AI lives in the editor sidebar[span_0](end_span).
* **[span_1](start_span)[span_2](start_span)Direct Block Generation:** Automatically parses AI output into native WordPress blocks (`core/heading`, `core/list`, `core/code`) rather than raw text[span_1](end_span)[span_2](end_span).
* **[span_3](start_span)[span_4](start_span)Polymorphic AI Provider:** Switch instantly between **Gemini 1.5 Flash** (default) and **GPT-4o**[span_3](end_span)[span_4](end_span).
* **[span_5](start_span)[span_6](start_span)Context Memory:** Toggleable session history allows the AI to remember previous chat turns within the editing session[span_5](end_span)[span_6](end_span).
* **[span_7](start_span)[span_8](start_span)Enterprise Security:** API keys are stored in User Profiles (not global settings) and are **encrypted at rest** using OpenSSL (`aes-256-cbc`)[span_7](end_span)[span_8](end_span).

## 🛠 Installation & Build

Because OmniQuill Pro uses a React frontend, it must be built before use.

### Prerequisites
* PHP 7.4+
* Node.js & NPM (for building assets)

### Development Setup
1.  Clone the repository.
2.  Install dependencies:
    ```bash
    npm install
    ```
3.  Build the React assets:
    ```bash
    npm run build
    ```
    *[span_9](start_span)This compiles `src/index.js` into `build/index.js` which is required by the PHP backend[span_9](end_span).*

## ⚙️ Configuration

### 1. Security Salt (Optional but Recommended)
By default, the plugin uses a fallback salt. For production security, define a custom salt in your `wp-config.php`:
```php
define( 'OMNI_SALT', 'your-unique-random-long-string-here' );
