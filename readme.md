=== AutoCatalog AI — Product Description Generator for WooCommerce ===
Contributors: Mohammed Ali Atif
Tags: woocommerce, ai, product description, seo, openai, claude, gemini, groq, artificial intelligence
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate AI-powered product descriptions, tags, and SEO meta directly inside WooCommerce — using your own API key. Works with Claude, OpenAI, Gemini, and Groq.

== Description ==

**AutoCatalog AI** adds a smart AI writing panel directly inside the WooCommerce product editor. Stop writing product descriptions manually — let AI generate compelling, on-brand content in seconds.

Unlike other AI plugins, AutoCatalog AI is **completely free to install**. You bring your own API key from any supported provider and only pay for what you use. No subscriptions, no hidden fees, no data sent to third-party servers.

= What It Does =

Open any WooCommerce product and you will see the AI panel appear directly below the product title. From there you can generate:

* **Product descriptions** — full HTML-formatted descriptions with paragraphs and bullet points
* **Short descriptions** — punchy 2–3 sentence summaries for the product excerpt field
* **Product tags** — 8–10 relevant tags suggested automatically
* **SEO meta descriptions** — under 160 characters, ready for your SEO plugin

= Tone Control =

Choose from five writing tones to match your brand voice:

* 💼 Professional — clean and business-ready
* 😊 Casual — friendly and approachable
* ✨ Luxury — premium and aspirational
* 🎉 Playful — fun and energetic
* ⚙️ Technical — detailed and precise

= Custom Prompts =

Every generation type has a fully editable prompt template. Use variables like `{product_name}`, `{tone}`, and `{existing_desc}` to build prompts that match your exact needs. Reset to defaults anytime with one click.

= Supported AI Providers =

| Provider | Free Tier | Speed | Best For |
|---|---|---|---|
| Groq | ✅ Yes | ⚡ Ultra fast | Getting started for free |
| Gemini (Google) | ✅ Yes | 🔵 Fast | Free with high limits |
| Claude (Anthropic) | ❌ Paid | 🟠 Fast | Best quality output |
| OpenAI (GPT-4) | ❌ Paid | 🟢 Fast | Popular & reliable |

= Bring Your Own API Key =

Your API key is stored only in your own WordPress database. It is never sent to our servers — API calls go directly from your server to the AI provider. You are always in full control.

= Privacy =

This plugin does not collect any data. No analytics, no tracking, no external calls except the direct API request you initiate by clicking a generate button.

== Installation ==

= Automatic Installation =
1. Go to **Plugins → Add New** in your WordPress admin
2. Search for **AutoCatalog AI**
3. Click **Install Now** then **Activate**
4. Go to **WooCommerce → AI Assistant** to add your API key

= Manual Installation =
1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Activate the plugin
5. Go to **WooCommerce → AI Assistant** to add your API key

= Requirements =
* WordPress 6.0 or higher
* WooCommerce 7.0 or higher
* PHP 7.4 or higher
* An API key from any supported provider (Groq and Gemini have free tiers)

== Frequently Asked Questions ==

= Is this plugin free? =
Yes, completely free to install and use. You only pay for AI API usage, which is charged directly by your chosen provider. Groq and Google Gemini both offer free tiers that are more than enough for most stores.

= Which AI providers are supported? =
Claude (Anthropic), OpenAI (GPT-4), Google Gemini, and Groq. You can switch providers anytime from the settings page.

= Where do I get an API key? =
* **Groq (free):** console.groq.com/keys
* **Gemini (free):** aistudio.google.com/app/apikey
* **Claude:** console.anthropic.com
* **OpenAI:** platform.openai.com/api-keys

= Is my API key safe? =
Yes. Your API key is stored in your WordPress database using the standard `wp_options` table — the same place WooCommerce and other major plugins store their settings. It is never transmitted to anyone except the AI provider you have chosen, and only when you click a generate button.

= Does it work with any WooCommerce theme? =
Yes. The AI panel appears inside the WordPress admin product editor, which is theme-independent.

= Can I customise the AI prompts? =
Yes. Go to **WooCommerce → AI Assistant** and scroll to the Custom Prompts section. You can edit the prompt for each generation type and use variables like `{product_name}` and `{tone}` as placeholders.

= What happens if the AI generates something I don't like? =
Just click the generate button again for a new variation. Nothing is saved automatically — you review the output first and only save when you click the WordPress Update button.

= Will this slow down my website? =
No. The plugin only loads its scripts on the WooCommerce product edit page in the admin area. It adds zero code to your public-facing website.

= Does it require WooCommerce? =
Yes. WooCommerce must be installed and active. The plugin will show a notice and deactivate itself if WooCommerce is not found.

== Screenshots ==

1. The AI panel on the product edit page — generate descriptions with one click
2. Settings page — choose your AI provider and add your API key
3. Custom prompts — edit the AI prompt for each generation type
4. Tone selector — match the writing style to your brand

== Changelog ==

= 1.0.0 =
* Initial release
* Support for Claude (Anthropic), OpenAI, Google Gemini, and Groq
* Generate product descriptions, short descriptions, tags, and SEO meta
* Five tone options: Professional, Casual, Luxury, Playful, Technical
* Custom prompt editor with variable insertion for each generation type
* Friendly error messages for invalid keys, rate limits, and billing issues
* API key visibility toggle and connection test on settings page
* Settings CSS loaded from external file for clean separation

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.