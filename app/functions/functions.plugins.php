<?php
/**
 * Plugin system: add_filter(), add_action(), apply_filters(), do_action().
 *
 * Hooks (examples):
 * - VibePermalinks: filter permalink/URL (e.g. video_url, image_url pass through apply_filters).
 * - the_defaults: filter default option value (get_option uses option_{key}).
 * - option_{key}: filter single option value (applied in get_option).
 * - vibe_header: action after <body> or before main content (output head/nav extras).
 * - vibe_footer: action before </body> (scripts, content).
 * - add-activity: action when activity is added (user_id, action, object_type, object_id, extra).
 * - get-activity: filter activity results array (results, args).
 * - the_embedded_video: filter player HTML (html, video, opts).
 * - vibe_before_player: action before player output (container_id, video, opts). Use for ad slots / IMA (e.g. vjimaads) script.
 * - vibe_after_player: action after player output (container_id, video, opts). Use for ad placement in theme/player.
 * - the_navigation: filter nav items array (items).
 * - vibe_plugin_settings: action in admin Settings; plugins output their options card.
 * - vibe_plugin_settings_save: action on save (receives $_POST).
 * - vibe_head: action in <head> (custom meta/scripts).
 * - vibe_after_player: action (container_id, video, opts) after player on watch/listen.
 */

if (!defined('in_nia_app')) exit;

$vibe_filters = [];
$vibe_actions = [];

function add_filter($tag, $callback, $priority = 10) {
    global $vibe_filters;
    if (!isset($vibe_filters[$tag])) $vibe_filters[$tag] = [];
    $vibe_filters[$tag][] = ['priority' => (int) $priority, 'callback' => $callback];
}

function apply_filters($tag, $value, ...$args) {
    global $vibe_filters;
    if (empty($vibe_filters[$tag])) return $value;
    $list = $vibe_filters[$tag];
    usort($list, function ($a, $b) { return $a['priority'] - $b['priority']; });
    foreach ($list as $item) {
        $value = call_user_func_array($item['callback'], array_merge([$value], $args));
    }
    return $value;
}

function remove_filter($tag, $callback) {
    global $vibe_filters;
    if (empty($vibe_filters[$tag])) return;
    $vibe_filters[$tag] = array_filter($vibe_filters[$tag], function ($item) use ($callback) {
        return $item['callback'] !== $callback;
    });
}

function add_action($tag, $callback, $priority = 10) {
    global $vibe_actions;
    if (!isset($vibe_actions[$tag])) $vibe_actions[$tag] = [];
    $vibe_actions[$tag][] = ['priority' => (int) $priority, 'callback' => $callback];
}

function do_action($tag, ...$args) {
    global $vibe_actions;
    if (empty($vibe_actions[$tag])) return;
    $list = $vibe_actions[$tag];
    usort($list, function ($a, $b) { return $a['priority'] - $b['priority']; });
    foreach ($list as $item) {
        call_user_func_array($item['callback'], $args);
    }
}

function remove_action($tag, $callback) {
    global $vibe_actions;
    if (empty($vibe_actions[$tag])) return;
    $vibe_actions[$tag] = array_filter($vibe_actions[$tag], function ($item) use ($callback) {
        return $item['callback'] !== $callback;
    });
}

/**
 * Load enabled plugins (from option plugins_enabled, comma-separated).
 * Each plugin: app/plugins/{slug}/plugin.php
 */
function vibe_load_plugins() {
    $enabled = get_option('plugins_enabled', '');
    if ($enabled === '') return;
    $dir = ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
    foreach (array_map('trim', explode(',', $enabled)) as $slug) {
        $slug = preg_replace('/[^a-z0-9\-_]/', '', $slug);
        if ($slug === '') continue;
        $file = $dir . $slug . DIRECTORY_SEPARATOR . 'plugin.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
}
