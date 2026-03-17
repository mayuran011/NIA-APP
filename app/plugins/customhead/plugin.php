<?php
/**
 * Custom Head: inject custom HTML into <head> (meta tags, scripts, styles).
 * Options: customhead_enabled, customhead_code.
 * Hooks: vibe_header runs after <body>; we need a head hook. Use add_action on a hook that runs in head.
 * Core does not have vibe_head; we add output at vibe_header with a flag to output in head.
 * Alternative: filter or action in theme header. Check tpl.header.php - no head action. So we inject via vibe_header
 * but that's after body. So we need to add do_action('vibe_head') in theme head and then this plugin can use it.
 */

if (!defined('in_nia_app')) exit;

// Add head hook in theme if not present: we'll document that themes should support vibe_head. For themes that don't,
// we can output a placeholder script that document.write's into head (fragile). Better: add vibe_head to theme.
// Check theme header for where to add it.
// I'll add do_action('vibe_head') to the theme header so this plugin works.
// So first add the hook point in tpl.header.php, then this plugin uses it.

add_action('vibe_head', function () {
    if (get_option('customhead_enabled', '0') !== '1') return;
    $code = get_option('customhead_code', '');
    if ($code === '') return;
    echo "\n" . $code . "\n";
}, 10);

add_action('vibe_plugin_settings', function () {
    $enabled = get_option('customhead_enabled', '0');
    $code = get_option('customhead_code', '');
    echo '<h6 class="mt-3">Custom Head</h6>';
    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="customhead_enabled" value="1" ' . ($enabled === '1' ? 'checked' : '') . '><label class="form-check-label">Inject custom HTML into &lt;head&gt;</label></div>';
    echo '<div class="mb-3"><label class="form-label">HTML / Scripts (meta, link, script)</label><textarea class="form-control font-monospace small" name="customhead_code" rows="6" placeholder="e.g. &lt;meta name=\"custom\" content=\"value\"&gt;&#10;&lt;script src=\"...\"&gt;&lt;/script&gt;">' . _e($code) . '</textarea><small class="form-text text-muted">Runs in &lt;head&gt;. Use for analytics, meta tags, preconnect.</small></div>';
});

add_action('vibe_plugin_settings_save', function ($post) {
    update_option('customhead_enabled', isset($post['customhead_enabled']) ? '1' : '0');
    if (isset($post['customhead_code'])) {
        update_option('customhead_code', trim($post['customhead_code']));
    }
});
