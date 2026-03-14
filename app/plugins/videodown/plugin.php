<?php
/**
 * Videodown: add download video/audio link on single video/music page.
 * Options: videodown_enabled, videodown_label.
 * Admin: Settings → Plugin settings (vibe_plugin_settings).
 */

if (!defined('in_nia_app')) exit;

add_filter('the_embedded_video', function ($html, $video) {
    if (get_option('videodown_enabled', '1') !== '1') return $html;
    if (!$video || empty($video->id)) return $html;
    if (get_option('download_allowed', '0') !== '1' || !function_exists('can_download_media') || !can_download_media()) return $html;
    $source = $video->source ?? 'local';
    if ($source !== 'local') return $html;
    $url = rtrim(SITE_URL, '/') . '/stream.php?id=' . (int) $video->id . '&download=1';
    $label = _e(get_option('videodown_label', 'Download'));
    return $html . '<p class="mb-2 small mt-2"><a href="' . $url . '" class="btn btn-outline-secondary btn-sm" download>' . $label . '</a></p>';
}, 10, 2);

add_action('vibe_plugin_settings', function () {
    $enabled = get_option('videodown_enabled', '1');
    $label = get_option('videodown_label', 'Download');
    echo '<h6 class="mt-3">Videodown (download video/audio)</h6>';
    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="videodown_enabled" value="1" ' . ($enabled === '1' ? 'checked' : '') . '><label class="form-check-label">Enable download link on video/music page</label></div>';
    echo '<div class="mb-3"><label class="form-label">Button label</label><input type="text" class="form-control" name="videodown_label" value="' . _e($label) . '"></div>';
});

add_action('vibe_plugin_settings_save', function ($post) {
    update_option('videodown_enabled', isset($post['videodown_enabled']) ? '1' : '0');
    if (isset($post['videodown_label'])) {
        update_option('videodown_label', trim($post['videodown_label']) ?: 'Download');
    }
});
