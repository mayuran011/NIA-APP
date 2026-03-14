<?php
/**
 * PWA web app manifest: name, start_url, display, icons, theme_color, background_color.
 * Serve as application/manifest+json (link from head as site.webmanifest or this file).
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
}
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$base = rtrim(SITE_URL, '/');
$name = get_option('sitename', 'Nia App');
$short_name = mb_strlen($name) > 12 ? mb_substr($name, 0, 12) . '…' : $name;
$theme = get_option('theme_color', '#0f0f12');
$bg = get_option('background_color', '#0f0f12');

$manifest = [
    'name'             => $name,
    'short_name'       => $short_name,
    'start_url'        => $base . '/',
    'display'          => 'standalone',
    'icons'            => [
        ['src' => $base . '/app/favicos/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $base . '/app/favicos/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
    ],
    'theme_color'      => $theme,
    'background_color' => $bg,
];

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
