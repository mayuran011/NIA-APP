<?php
/**
 * Bootstrap: DB, options, session, and guards.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('in_nia_app', true);

// Log all website errors to a writable error.log (visible in moderator → Error log)
$tmp_base = defined('TMP_FOLDER') ? rtrim(TMP_FOLDER, DIRECTORY_SEPARATOR) : (ABSPATH . 'tmp');
$app_error_log = $tmp_base . DIRECTORY_SEPARATOR . 'error.log';
if (!is_dir($tmp_base)) {
    @mkdir($tmp_base, 0755, true);
}
if (is_dir($tmp_base) && is_writable($tmp_base)) {
    if (!file_exists($app_error_log)) {
        @touch($app_error_log);
    }
    if (is_writable($app_error_log) || (!file_exists($app_error_log) && is_writable($tmp_base))) {
        @ini_set('log_errors', '1');
        @ini_set('error_log', $app_error_log);
    }
}
error_reporting(E_ALL);
ini_set('display_errors', defined('WP_DEBUG') && WP_DEBUG ? '1' : '0');

// Session (after headers ready; start only when needed)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB connection (global for options/helpers)
require_once ABSPATH . 'lib' . DIRECTORY_SEPARATOR . 'class.db.php';
global $db;
$db = NiaDB::instance();

// Options (from vibe_options; cached in memory)
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.options.php';
init_options();

// Helpers, stream, media, plugin system
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.helpers.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.language.php';
init_lang();
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.stream.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.upload.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.media.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.search.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.users.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.premium.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.auth.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.subscriptions.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.activity.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.conversations.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.blog.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.engagement.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.youtube-import.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.notifications.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.membership.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.plugins.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class.providers.php';
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class.players.php';

vibe_load_plugins();

/**
 * Global Maintenance Mode Check
 */
if (get_option('maintenance_mode', '0') === '1') {
    // Only check if NOT in the admin area
    $current_path = $_SERVER['REQUEST_URI'] ?? '';
    $admin_path = '/' . ADMINCP;
    
    // Check if path starts with admin path
    $is_admin_area = (strpos($current_path, $admin_path) !== false);
    
    if (!$is_admin_area && !is_admin()) {
        // Show maintenance page
        $msg = get_option('maintenance_message', 'Our site is currently undergoing scheduled maintenance. We will be back soon!');
        
        // Premium Maintenance Page
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 3600');
        
        $sitename = get_option('sitename', 'Nia App');
        $theme_color = get_option('theme_color', '#0f0f12');
        $bg_color = get_option('background_color', '#0f0f12');
        
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | {$sitename}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: {$bg_color};
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        .icon {
            font-size: 80px;
            color: #3699ff;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 0%, #a2a3b7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #a2a3b7;
            margin-bottom: 30px;
        }
        .social {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .social a {
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            transition: color 0.3s;
        }
        .social a:hover {
            color: #3699ff;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }
    </style>
</head>
<body>
    <div class="container">
        <span class="material-icons icon">construction</span>
        <h1>Back Shortly!</h1>
        <p>{$msg}</p>
        <div class="social">
            <span>&copy; {$sitename}</span>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }
}
