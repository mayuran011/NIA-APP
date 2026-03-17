<?php
/**
 * Noindex: add noindex,nofollow meta to all pages (e.g. for staging/dev).
 * Options: noindex_enabled.
 * Hooks: vibe_head.
 */

if (!defined('in_nia_app')) exit;

add_action('vibe_head', function () {
    if (get_option('noindex_enabled', '0') !== '1') return;
    echo "\n<meta name=\"robots\" content=\"noindex, nofollow\">\n";
}, 5);

add_action('vibe_plugin_settings', function () {
    $enabled = get_option('noindex_enabled', '0');
    echo '<h6 class="mt-3">Noindex</h6>';
    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="noindex_enabled" value="1" ' . ($enabled === '1' ? 'checked' : '') . '><label class="form-check-label">Add <code>noindex, nofollow</code> meta to every page (use on staging/dev only)</label></div>';
});

add_action('vibe_plugin_settings_save', function ($post) {
    update_option('noindex_enabled', isset($post['noindex_enabled']) ? '1' : '0');
});
