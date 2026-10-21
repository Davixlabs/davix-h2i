=== Davix H2I ===
Contributors: davixlabs
Tags: image optimization, media library, pdf, alt text, ai
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use your Davix H2I account inside WordPress: optimize and edit images, make social cards, write alt text and SEO with AI, work with PDFs, and keep a history of everything it does.

== Description ==

Davix H2I for WordPress connects your site to your own Davix H2I account with your customer API key. Every action uses your plan, so you always see what you can use and what it costs.

* **Images**: optimize, convert, resize, crop, rotate, effects, watermark, remove a plain background, edit metadata, create responsive sizes. Chips let you combine steps.
* **Automatic rules** for new uploads (optimize, resize, watermark, remove location data, alt text, compress PDFs), run in the background. Uploads never wait.
* **AI**: alt text, captions, tags, SEO text, safety checks, summaries, translation, grammar and image creation (the features your plan includes and that are ready).
* **PDF and documents**: compress, thumbnails, merge, split, extract pages, watermark, password protection, convert.
* **H2I Render**: HTML to image or PDF, social cards for new posts, charts.
* **Create**: write text, create images, turn a page into an image or PDF, charts and QR codes.
* **Media Library sidebar**: every action that fits the files you pick, with all the options the service supports.
* **History**: a list of everything you did through this plugin, with the original one click away.
* **Background jobs**, bulk runs with a cost estimate, and a debug log.
* **WP-CLI**: `wp davix verify`, `wp davix optimize`, `wp davix queue`, and more.

An API key from your Davix H2I Dashboard is required.

== External Services ==

This plugin sends the files and text you choose to your Davix H2I service to be processed, and receives the result.

Service provider: Davix Labs (Davix H2I). Terms and privacy: https://h2i.davixlabs.io/

What is sent, and when:

* The file or text of an action, only when you run it, or when you switch on an automatic rule (for example an automatic rule for optimizing or AI on upload).
* Your API key, in a request header, to identify your account.
* Nothing is sent for browsing the plugin screens, except plan and usage questions (no files).

AI features are off for new uploads until you switch them on in AI > Settings.

== Installation ==

1. Upload the plugin and activate it.
2. Open Davix H2I > Account, paste your API key and choose Test Connection.
3. Save the connection.

Optional: define `DAVIX_H2I_API_KEY` in wp-config.php so the key is not stored in the database.

== Frequently Asked Questions ==

= Where do I get an API key? =
In your Davix H2I Dashboard under API keys.

= Does it slow down uploads? =
No. Uploads only add work to a background queue.

= Can I undo a change? =
Yes. By default the original is kept as a backup. Open History and choose Restore original.

= What does it cost? =
Each action shows its cost in the units of your plan before you run it.

= How do I update the plugin? =
When a new version is out, the Plugins page shows "There is a new version of Davix H2I available. Update now". Click it. "Check for updates" next to Deactivate asks right away. You can also switch on automatic updates for it there.

== Changelog ==

= 2.1.0 =
* New: updates. When a new version is published, WordPress shows "Update now" on the Plugins page, with the usual View details window and the automatic-updates switch. Each update is checked against its published checksum before it is installed.
* Changed: the account wording now says Dashboard.

= 2.0.0 =
* Rebuilt from the ground up on the Davix H2I PHP SDK.
* New: plan-aware screens, background queue, History, AI features, PDF tools, generators, WP-CLI.
