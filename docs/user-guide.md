# User guide

## Connect

1. Open **Davix H2I > Account**.
2. Paste the API key from your Customer Portal, choose **Test Connection**, then **Save connection**.
3. Optional: put `define( 'DAVIX_H2I_API_KEY', '...' );` in `wp-config.php` so the key never sits in the database.

The key is stored encrypted, shown only as its last four characters, and never written to the log.

## Where things are

The work on files you already have happens in the **Media Library**. The plugin's own screens hold only what is not tied to one file: rules for new uploads, things you create from nothing, and your account.

| Tab | What it does |
|---|---|
| **Overview** | Your plan and what is left, library health, recent activity, warnings |
| **Images** | **Automatic**: rules that run on every new image (optimize, resize, convert, watermark, remove location data ...) |
| **AI** | **Automatic** (alt text, captions, tags, safety check on new images), **Settings** (language, tone, what happens to the text), **Status** |
| **Create** | Make something new: **Write** (SEO text, summaries, titles, translations ...), **AI images**, **Page to image or PDF**, **Chart**, **QR code** |
| **H2I Render** | **Template** and **Social cards** (a card for every new post), **Tokens** |
| **PDF & Documents** | **Automatic**: compress new PDFs and make page thumbnails |
| **History** | Everything you did through this plugin |
| **Activity** | Background jobs, and AI suggestions waiting for your approval |
| **Library** | Presets and templates saved in your account |
| **Account / Settings / Help** | Connection and plan, performance and privacy, about, support, debug log |

## The Media Library sidebar

In **Media > Library** (list view) each file has a **Davix H2I** link, and the file details have an **Optimize and edit…** button. To work on several files, tick them and choose the bulk action **Open in Davix H2I**.

The sidebar shows a tab for each kind of work that fits what you picked:

| Tab | For | What it does |
|---|---|---|
| **Image** | pictures | Choose steps (Smart Optimization, Format, Resize, Crop, Transform, Effects, Padding, Frame, Background, Watermark) and run them. Several steps become one call |
| **Analyze** | pictures | Quality and savings check, find look-alikes (several files in one go), compare two images (pick two files) |
| **AI** | pictures, documents | Alt text, captions, tags, safety check, summaries, translation, upscale ... |
| **PDF** | PDFs | Compress, page images, merge (several PDFs), split, extract pages, watermark, password |
| **Convert** | documents | Word, Excel, PowerPoint, PDF and more to another format |
| **Render** | pictures | Use the picture inside your template (`{selected_image_url}`) |
| **Site icon** | pictures | All the icon sizes for browsers and phones |
| **History** | one file | What was done to this file, with **Restore original** |

Every action shows its own options and, under **More options**, everything else the service supports. Only the options you change are sent. Where an action can replace or copy the file, you choose **Replace the original (a backup is kept)** or **Save as a new copy**.

## Automatic rules

A rule says: *when a new file of this kind is uploaded, do this*. Press **New rule** on the Images, AI or PDF & Documents tab, choose what to do, which file types, the smallest file, and whether the result replaces the file or is saved as a copy. Click a rule to change it. Rules run in the background after the upload is saved; an upload never waits.

## Plan awareness

Everything comes from your plan. An action your plan does not include is greyed with an **Upgrade** badge (this can be switched off in Settings). A feature the service marks as not fully ready shows **Beta**; one that is unavailable is hidden. Each action shows its cost before you run it.

## Background work

Uploads and bulk runs never wait for the service: they add rows to a queue that WP-Cron works through every minute (or **Run now**). If your allowance runs out the queue pauses and keeps the rest; resume it from Activity when you are ready.

## History

History lists what *this plugin* did: action, file, result, size change, who, when. Click a row to see the settings that were used in plain words, to open the file, or to **Restore original** when a backup exists. Actions made in Studio or with the API directly are never shown here (the Customer Portal History shows them, with the app that made each one). Export to CSV; set how long to keep it under Settings > Privacy & data.

## Undo

The original is kept as a backup when a file is replaced. Restore it from History or from the sidebar's History tab.
