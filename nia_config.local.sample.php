<?php
/**
 * Local / environment-specific configuration sample.
 * Copy to nia_config.local.php (gitignored) and set your values.
 * Do not commit nia_config.local.php – it may contain credentials.
 *
 * Production: set environment variables instead (DB_HOST, DB_NAME, DB_USER, DB_PASS, SITE_URL).
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}

// Database
define('DB_HOST',     'localhost');
define('DB_NAME',     'nia_local');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');
define('DB_PREFIX',   'nia_');

// Site URL (no trailing slash)
define('SITE_URL',    'http://localhost/PHPVIBENEWWEBS');
