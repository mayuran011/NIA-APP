<?php
/**
 * PHPVibe – Legacy config (not used by index.php).
 * The site uses nia_config.php as the main config. Keep this only for reference or legacy setups.
 *
 * Groups:
 * - Database: DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET, DB_PREFIX
 * - Site & paths: SITE_URL, ADMINCP (admin base path, e.g. moderator), MEDIA_FOLDER, TMP_FOLDER, CACHE_ENABLED (cache toggle)
 * - Security: COOKIEKEY, SECRETSALT (set unique in production)
 * - OAuth: FB_APP_ID, FB_APP_SECRET (Facebook); GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET (Google). Leave empty to disable.
 * - Payments: PAYPAL_CLIENT_ID, PAYPAL_SECRET (PayPal). Sandbox via option paypal_sandbox.
 * - Mail/SMTP: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, MAIL_FROM. Leave empty to disable.
 * - Loader: requires app/bootstrap.php (sets in_phpvibe, session, DB, options, helpers, plugins).
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
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

// -----------------------------------------------------------------------------
// Database (MySQL)
// -----------------------------------------------------------------------------
define('DB_HOST',     'localhost');
define('DB_NAME',     'test');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');
define('DB_PREFIX',   'vibe_');

// -----------------------------------------------------------------------------
// Site & paths
// -----------------------------------------------------------------------------
define('SITE_URL',    'http://localhost/PHPVIBENEWWEBS');
define('ADMINCP',     'moderator');           // Admin base path, e.g. /moderator/
define('MEDIA_FOLDER', ABSPATH . 'media');
define('TMP_FOLDER',   ABSPATH . 'tmp');
define('CACHE_ENABLED', true);

// -----------------------------------------------------------------------------
// Security (set these in production)
// -----------------------------------------------------------------------------
define('COOKIEKEY',   'change-this-cookie-key');
define('SECRETSALT',  'change-this-secret-salt');

// -----------------------------------------------------------------------------
// Optional: OAuth (leave empty to disable)
// -----------------------------------------------------------------------------
define('FB_APP_ID',     '');
define('FB_APP_SECRET', '');
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');

// -----------------------------------------------------------------------------
// Optional: Payments / Mail
// -----------------------------------------------------------------------------
define('PAYPAL_CLIENT_ID', '');
define('PAYPAL_SECRET',    '');
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM', 'noreply@yoursite.com');

// -----------------------------------------------------------------------------
// Loader
// -----------------------------------------------------------------------------
require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
