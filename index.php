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

// Config (defines DB, SITE_URL, ADMINCP; loads bootstrap) – catch so 500 is logged
try {
    require_once $config_file;
    require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'router.php';
    $path = nia_get_path();
    list($route_name, $section) = nia_match_routes($path);
    nia_dispatch($route_name, $section);
} catch (Throwable $e) {
    $tmp = ABSPATH . 'tmp';
    if (!is_dir($tmp)) {
        @mkdir($tmp, 0755, true);
    }
    $log = $tmp . DIRECTORY_SEPARATOR . 'error.log';
    @file_put_contents($log, date('c') . ' ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", LOCK_EX | FILE_APPEND);
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/html; charset=utf-8');
    }
    if (ini_get('display_errors')) {
        echo '<h1>Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Site temporarily unavailable</h1><p>Check <code>tmp/error.log</code> on the server for details.</p>';
    }
    exit;
}
