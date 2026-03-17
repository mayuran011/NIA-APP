<?php
/**
 * Nia App – Main configuration
 *
 * Database and SITE_URL are read from the environment or from nia_config.local.php (gitignored).
 * Do not commit production credentials. Set env vars (DB_HOST, DB_NAME, DB_USER, DB_PASS, SITE_URL)
 * or use nia_config.local.php for local dev (copy from nia_config.local.sample.php).
 *
 * Other groups: Security (COOKIEKEY, SECRETSALT), OAuth, Payments, Mail – set in production.
 * Loader: requires app/bootstrap.php.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}

// Optional local override (gitignored). Define DB_HOST, DB_NAME, DB_USER, DB_PASS, SITE_URL there.
$local_config = ABSPATH . 'nia_config.local.php';
if (is_file($local_config)) {
    require_once $local_config;
}

// Helper: read env (getenv or $_ENV for host compatibility)
$env = function ($key, $default = '') {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    return (isset($_ENV[$key]) && $_ENV[$key] !== '') ? $_ENV[$key] : $default;
};

// -----------------------------------------------------------------------------
// Database – from environment or local config (no credentials in this file)
// -----------------------------------------------------------------------------
if (!defined('DB_HOST'))     define('DB_HOST',     $env('DB_HOST', 'localhost'));
if (!defined('DB_NAME'))     define('DB_NAME',     $env('DB_NAME', 'nia_local'));
if (!defined('DB_USER'))     define('DB_USER',     $env('DB_USER', 'root'));
if (!defined('DB_PASS'))     define('DB_PASS',     $env('DB_PASS', ''));
if (!defined('DB_CHARSET'))  define('DB_CHARSET',  $env('DB_CHARSET', 'utf8mb4'));
if (!defined('DB_PREFIX'))   define('DB_PREFIX',   $env('DB_PREFIX', 'nia_'));

// -----------------------------------------------------------------------------
// Site & paths – SITE_URL from environment or local config
// -----------------------------------------------------------------------------
if (!defined('SITE_URL')) {
    $site_url = $env('SITE_URL', '');
    if ($site_url === '') {
        $site_url = 'http://localhost';
        if (!empty($_SERVER['HTTP_HOST'])) {
            $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $base = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
            if ($base !== '' && $base !== '/' && $base !== '\\') {
                $site_url .= rtrim(str_replace('\\', '/', $base), '/');
            }
        }
    }
    define('SITE_URL', is_string($site_url) ? rtrim($site_url, '/') : 'http://localhost');
}

// Setup guard: when hold.json exists, redirect to setup (any entry point)
if (file_exists(ABSPATH . 'hold.json')) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $setup_url = $base . '/setup/';
    if (!headers_sent()) {
        header('Location: ' . $setup_url, true, 302);
    }
    exit;
}

if (!defined('ADMINCP'))      define('ADMINCP',     'moderator');
if (!defined('MEDIA_FOLDER')) define('MEDIA_FOLDER', ABSPATH . 'media');
if (!defined('TMP_FOLDER'))   define('TMP_FOLDER',   ABSPATH . 'tmp');
if (!defined('CACHE_ENABLED')) define('CACHE_ENABLED', true);

// -----------------------------------------------------------------------------
// Security (set these in production via env or local config)
// -----------------------------------------------------------------------------
if (!defined('COOKIEKEY'))   define('COOKIEKEY',   getenv('COOKIEKEY') ?: 'change-this-cookie-key');
if (!defined('SECRETSALT'))  define('SECRETSALT',  getenv('SECRETSALT') ?: 'change-this-secret-salt');

// -----------------------------------------------------------------------------
// Optional: OAuth (leave empty to disable)
// -----------------------------------------------------------------------------
if (!defined('FB_APP_ID'))     define('FB_APP_ID',     getenv('FB_APP_ID') ?: '');
if (!defined('FB_APP_SECRET')) define('FB_APP_SECRET', getenv('FB_APP_SECRET') ?: '');
if (!defined('GOOGLE_CLIENT_ID')) define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

// -----------------------------------------------------------------------------
// Optional: Payments / Mail
// -----------------------------------------------------------------------------
if (!defined('PAYPAL_CLIENT_ID')) define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: '');
if (!defined('PAYPAL_SECRET'))    define('PAYPAL_SECRET',    getenv('PAYPAL_SECRET') ?: '');
if (!defined('SMTP_HOST')) define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
if (!defined('SMTP_PORT')) define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
if (!defined('SMTP_USER')) define('SMTP_USER', getenv('SMTP_USER') ?: '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
if (!defined('MAIL_FROM')) define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@yoursite.com');

// -----------------------------------------------------------------------------
// Loader
// -----------------------------------------------------------------------------
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
