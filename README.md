# Nia App

**Video Sharing CMS** — upload, FFmpeg, YouTube importer, images and music, video ads, channels.  
**Nia App : video sharing and multimedia.**

Single entry point, clean URLs, Bootstrap 5 UI, Material Icons, theme and plugin system.

**In short:** A video/music/image sharing platform with users, profiles, playlists (Watch later, History), search, embed, payments/premium, messaging, blog/pages, full admin, PWA and background audio, and a plugin/hook system—all driven by the routes and options above.

---

## Features

| Feature | Description |
|--------|--------------|
| **Video & music** | Upload, remote URL, embed (YouTube, Vimeo, etc.); FFmpeg thumbnails/duration; multiple players (VideoJS, JW, FlowPlayer, jPlayer); subtitles (SRT/VTT). |
| **Images** | Upload, galleries/albums, tags; image search; view tracking. |
| **Channels** | Categories/channels for video, music, image; hierarchical; browse and filters. |
| **Upload & processing** | Generic upload, MP3/music upload, FFmpeg thumbnails; share by URL with provider detection. |
| **YouTube importer** | Admin → YouTube: API key, import from YouTube. |
| **Video ads** | VAST and IMA (e.g. vjimaads); preroll, midroll, postroll; ad placement hooks in theme/player. |
| **Users & profiles** | Register, login, profiles, subscriptions; Watch later, History, Likes, playlists; dashboard. |
| **Payments & premium** | PayPal; premium content; premium hub. |
| **Messaging** | Private conversations (inbox, threads). |
| **Blog & pages** | Articles, categories; static pages; SEO-friendly URLs. |
| **Search** | Global search (videos, music, images, channels, playlists); live suggestions; dedicated image/people/playlist search. |
| **Embed & API** | Embed player (iframe); RSS feeds; public JSON API (videos, music, images, search). |
| **PWA & background audio** | Web manifest, service worker; Media Session API for lock-screen/background playback. |
| **Multi-language** | Languages in options; init_lang(), _lang(), current_lang(); RTL; language switcher in sidebar. |
| **Full admin** | Moderator panel: dashboard, videos/music/images, channels, playlists, users, comments, reports, ads, homepage builder, SEO, settings, languages, plugins, cache, YouTube, activity. |
| **Plugins & hooks** | add_action/add_filter; vibe_header, vibe_footer, the_embedded_video, vibe_before_player, vibe_after_player, etc. |

## Systems

- **Config & security**: vibe_config.php (DB, SITE_URL, ADMINCP, OAuth, PayPal, SMTP); in_nia_app guard; session/cookie auth; moderator checks; optional reCAPTCHA; setup guard (hold.json).
- **Routing & options**: Single entry, router, SEO overrides; options in DB (autoload cache); get_option/update_option.
- **Full-page cache**: lib/fullcache.php (guests; skip logged-in / ?action=).
- **Query cache**: $db->fetchCached() for heavy reads (tmp/querycache).
- **Tracking**: track.php (video/music views + history); track-img.php (image views).
- **Bootstrap & icons**: Bootstrap 5 (CDN), Material Icons; responsive layout, sidebar, cards, nav.

## Requirements

- PHP 7.4+ (PDO MySQL)
- Apache with `mod_rewrite` (or equivalent)
- MySQL/MariaDB

## Setup

1. **Database**  
   Create database `test` (or set `DB_NAME` in `vibe_config.php`). User: `root`, no password by default (XAMPP).

2. **Config**  
   Edit `vibe_config.php`:
   - `SITE_URL` – base URL (e.g. `http://localhost/PHPVIBENEWWEBS`)
   - `DB_*` – host, name, user, password
   - `COOKIEKEY`, `SECRETSALT` – set unique values in production
   - OAuth (FB_*, GOOGLE_*) / PayPal (PAYPAL_CLIENT_ID, PAYPAL_SECRET) / Mail (SMTP_*, MAIL_FROM) – optional
   - `CACHE_ENABLED` – cache toggle

3. **Install tables**  
   Run the installer in **/setup/** (or import `lib/schema.sql`) so `nia_options` and other tables exist. Create the first admin user via setup. Options are autoloaded for `get_option()`.

4. **Setup guard**  
   If **hold.json** exists in the project root, or the config file is missing, all requests redirect to **/setup/** until setup is complete. Remove or rename `hold.json` after installation.

5. **Web root**  
   Point the document root to this directory. Ensure `.htaccess` is allowed (AllowOverride).

## Structure

| Path | Purpose |
|------|--------|
| `index.php` | Entry; routing via `app/router.php` |
| `vibe_config.php` | DB, SITE_URL, ADMINCP, security, optional services |
| `app/bootstrap.php` | DB, options, session, helpers, plugins |
| `app/router.php` | Path → route name + section → dispatch to `views/*.route.php` |
| `app/functions/` | options, helpers, plugins (hooks) |
| `app/ajax/` | Ajax endpoints (e.g. livesearch) |
| `app/classes/` | Players, providers, etc. |
| `lib/` | DB class, fullcache |
| `views/` | Route views (home, video, show, login, embed, …) |
| `themes/main/` | Header, footer, nia.css |
| `moderator/` | Admin (ADMINCP path): index.php, inc/, pages/ (dashboard, videos, music, images, channels, playlists, users, comments, reports, ads, homepage, seo, settings, languages, plugins, cache, youtube, vine, activity) |
| `setup/` | Install DB, create admin, config check; hold.json or missing config → redirect here |
| `app/uploading/` | upload.php (generic), upload-mp3.php, upload-ffmpeg.php |
| `media/`, `tmp/` | Media folder and temp (options: mediafolder, tmp-folder) |

## Routes (summary)

- `/` – Home  
- `/video/:id/:name`, `/image/:id/:name` – Single media  
- `/profile/:name/:id` – User profile  
- `/videos/:section`, `/music/:section`, `/images/:section` – Lists  
- `/playlist/:name/:id`, `/lists/:section`, `/show` – Playlists, search  
- `/me/:section`, `/dashboard/:section` – User library, dashboard  
- `/login`, `/register` – Auth  
- `/embed/:section`, `/feed/:section`, `/api/:section` – Embed; RSS (feed, feed/video, feed/music, feed/images); API (JSON: /api/videos, /api/music, /api/images, /api/video/:id, /api/image/:id, /api/search?q=)  
- `/{ADMINCP}/` – Admin (e.g. `/moderator/`): dashboard, videos/music/images, channels, playlists, users, comments, reports, ads, homepage, SEO, settings, languages, plugins, cache, YouTube, Vine, activity log

## Upload and processing

- **Storage**: Options **mediafolder**, **tmp-folder**; **functions.upload.php**: `media_folder()`, `tmp_folder()`, `ffmpeg_path()` (options **ffmpeg-cmd** / **binpath**); `ffmpeg_thumbnail()`, `ffmpeg_duration()` for transcoding/thumbnails.
- **Upload**: `app/uploading/upload.php` (generic), `upload-mp3.php` (music), `upload-ffmpeg.php` (thumbnails). **Remote/embed**: `app/ajax/addVideo.php` (POST url/embed_code + type); **Share by URL**: `/share` – paste URL, detect provider (YouTube, Vimeo, etc.), fetch metadata, preview, then add to content. Optional: serve MP4 only via `stream.php` (stream-only, no direct link).

## Security

- **Guards**: App code checks `in_nia_app` (set in bootstrap); session holds user id (`$_SESSION['uid']`). Admin area (**moderator/**) requires `is_logged()` and `is_moderator()`.
- **Optional reCAPTCHA**: Use **app/ajax/recaptchalib.php** and `recaptcha_verify($response)` where needed; set option `recaptcha_secret_key` or define `RECAPTCHA_SECRET_KEY` to enable.

## Other systems

- **Multi-language**: Options default_language, languages_enabled, rtl_languages. Use `init_lang()`, `_lang($key)`, `current_lang()`, `is_rtl()`; language files in `lang/{code}.php`; switcher in sidebar.
- **Full-page cache**: `lib/fullcache.php` – `vibe_fullcache_should_skip()`, `vibe_fullcache_get($path)`, `vibe_fullcache_set($path, $html)`; skipped for logged-in or `?action=`.
- **Query cache**: `$db->fetchCached($key, $ttl, $callback)` in `lib/class.db.php` for heavy reads (disk cache in tmp/querycache).
- **Tracking**: `app/ajax/track.php` (video/music: views + history); `app/ajax/track-img.php` (image views). Call from player/page for stats.
- **Subtitle**: Set `vibe_videos.subtitle_url` (VTT/SRT); VideoJS shows `<track>` when present.
- **Video ads**: Admin → Ads: VAST URL or IMA tag URL (e.g. vjimaads), placement (preroll, midroll, postroll). Ad placement hooks **vibe_before_player** and **vibe_after_player** in theme/player; **the_embedded_video** filter for IMA integration.

## Development

- **Database**: Tables use prefix **vibe_** (options, users, users_groups, users_friends, videos, images, channels, playlists, playlist_data, likes, activity, comments, conversation, conversation_data, blogcat, posts, pages, payments, etc.). Schema: `lib/schema.sql`.
- **Options**: Stored in **nia_options**; autoload=1 rows cached at bootstrap. Use `get_option('key', 'default')`; admin uses `update_option()`. Options control theme, players, upload, SEO, social login, premium, homepage (homepage_boxes), plugins, cache, ads, languages; optional bpp, thumb sizes.
- **Hooks**: `add_action('vibe_footer', fn() => …)`, `do_action('vibe_footer')`; same pattern for filters.
- **Theme**: Edit `themes/main/nia.css` and tpl files; design tokens in CSS.

---

Database (default): **localhost**, **test**, **root**, no password.
