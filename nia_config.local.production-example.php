<?php
/**
 * PRODUCTION EXAMPLE – copy this to nia_config.local.php on the server and set DB_PASS.
 * Do not commit nia_config.local.php. Delete this example file from the server after use.
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}

// Database (remote MySQL – replace YOUR_DB_PASSWORD with the real password)
define('DB_HOST',     'vps3323197.trouble-free.net');
define('DB_NAME',     'mayu_NIAAPP');
define('DB_USER',     'mayu_NIAAPP');
define('DB_PASS',     'YOUR_DB_PASSWORD');
define('DB_CHARSET',  'utf8mb4');
define('DB_PREFIX',   'nia_');

// Site URL (no trailing slash)
define('SITE_URL',    'https://vdo.nia.yt');
