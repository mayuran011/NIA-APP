<?php
/**
 * Nia App – Single entry point.
 * Routing and URL structure: defined here and in nia_config.php, with optional SEO
 * overrides from options (video-seo-url, image-seo-url, profile-seo-url, channel-seo-url,
 * page-seo-url, article-seo-url). See app/router.php for the full route table.
 */

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Setup guard: redirect to setup when hold.json exists or config file is missing
$hold_file = ABSPATH . 'hold.json';
$config_file = ABSPATH . 'nia_config.php';
if (file_exists($hold_file) || !is_readable($config_file)) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $setup_url = $base . '/setup/';
    if (!headers_sent()) {
        header('Location: ' . $setup_url, true, 302);
    }
    exit('Redirect to setup: <a href="' . htmlspecialchars($setup_url) . '">' . htmlspecialchars($setup_url) . '</a>');
}

// Ajax and direct PHP requests skip full bootstrap until config is loaded
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

// Config (defines DB, SITE_URL, ADMINCP; loads bootstrap)
require_once $config_file;

// Route and dispatch
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'router.php';
$path = nia_get_path();
list($route_name, $section) = nia_match_routes($path);
nia_dispatch($route_name, $section);
