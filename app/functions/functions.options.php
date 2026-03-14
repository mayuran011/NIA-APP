<?php
/**
 * Options from vibe_options; stored in DB, cached in memory at bootstrap (autoload=1).
 *
 * get_option($key, $default) – returns value; filterable via option_{key} and the_defaults.
 * update_option($key, $value, $autoload) – for admin/setup; updates cache when autoload=1.
 *
 * Option categories (examples):
 * - Theme: sitename, site_description, logo_url, favicon_url, theme_color, background_color, dark_mode
 * - Players: choosen-player, jwkey, player-logo, remote-player, youtube-player
 * - Upload: mediafolder, tmp-folder, ffmpeg-cmd, binpath
 * - SEO: video-seo-url, image-seo-url, profile-seo-url, channel-seo-url, page-seo-url, article-seo-url, meta_description, meta_keywords
 * - Social login: fb_app_id, fb_app_secret, google_client_id, google_client_secret
 * - Premium: allowpremium, price, currency, paypal_client_id, paypal_secret, paypal_sandbox
 * - Homepage: homepage_boxes (JSON)
 * - Plugins: plugins_enabled | Cache: cache_enabled, cache_ttl | Ads: ads_enabled, ad_vast_url, ad_placement
 * - Languages: default_language, languages_enabled | Optional: bpp (items per page), thumb_width, thumb_height
 */

if (!defined('in_nia_app')) {
    exit;
}

$vibe_options_cache = [];

function init_options() {
    global $vibe_options_cache, $db;
    $table = $db->prefix() . 'options';
    try {
        $rows = $db->fetchAll("SELECT name, value FROM `{$table}` WHERE autoload = 1");
        foreach ($rows as $row) {
            $vibe_options_cache[$row->name] = $row->value;
        }
    } catch (Throwable $e) {
        $vibe_options_cache = [];
    }
}

function get_option($key, $default = null) {
    global $vibe_options_cache;
    $value = array_key_exists($key, $vibe_options_cache) ? $vibe_options_cache[$key] : $default;
    if (!array_key_exists($key, $vibe_options_cache) && function_exists('apply_filters')) {
        $value = apply_filters('the_defaults', $value, $key);
    }
    if (function_exists('apply_filters')) {
        $value = apply_filters('option_' . $key, $value);
    }
    return $value;
}

function get_options() {
    global $vibe_options_cache;
    return $vibe_options_cache;
}

/**
 * Update or insert option; updates in-memory cache. For admin/setup.
 * @param string $key
 * @param string|null $value
 * @param int $autoload 1 = load at bootstrap, 0 = no
 */
function update_option($key, $value, $autoload = 1) {
    global $db, $vibe_options_cache;
    $key = trim($key);
    if ($key === '') return false;
    $table = $db->prefix() . 'options';
    $exists = $db->fetch("SELECT id FROM `{$table}` WHERE name = ?", [$key]);
    $val = $value === null ? '' : (string) $value;
    $autoload = (int) $autoload;
    if ($exists) {
        $db->query("UPDATE `{$table}` SET value = ?, autoload = ? WHERE name = ?", [$val, $autoload, $key]);
    } else {
        $db->query("INSERT INTO `{$table}` (name, value, autoload) VALUES (?, ?, ?)", [$key, $val, $autoload]);
    }
    if ($autoload) {
        $vibe_options_cache[$key] = $val;
    } else {
        unset($vibe_options_cache[$key]);
    }
    return true;
}
