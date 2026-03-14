<?php
/**
 * Optional full-page cache for guests.
 * Skip for logged-in users or ?action= requests.
 * Use: at start of request, if !nia_fullcache_should_skip() try nia_fullcache_get($path);
 *      if hit, output and exit; after rendering, nia_fullcache_set($path, $html).
 */
if (!defined('in_nia_app')) exit;

/** @var string|null */
$nia_fullcache_dir = null;

function nia_fullcache_dir() {
    global $nia_fullcache_dir;
    if ($nia_fullcache_dir === null) {
        $base = defined('TMP_FOLDER') ? TMP_FOLDER : (ABSPATH . 'tmp');
        $nia_fullcache_dir = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'fullcache';
        if (!is_dir($nia_fullcache_dir)) {
            @mkdir($nia_fullcache_dir, 0755, true);
        }
    }
    return $nia_fullcache_dir;
}

function nia_fullcache_should_skip() {
    if (!empty($_SESSION['uid'])) return true;
    if (isset($_GET['action'])) return true;
    return false;
}

/**
 * Cache key from path (GET vars excluded for stability; use path only).
 * @param string $path Normalized path, e.g. from nia_get_path()
 * @return string
 */
function nia_fullcache_key($path) {
    $path = preg_replace('#[^a-zA-Z0-9/_\-]#', '_', $path);
    return $path ?: '_home';
}

/**
 * Get cached HTML for path. Returns null if miss or disabled (CACHE_ENABLED / cache_enabled).
 * @param string $path
 * @param int $ttl Seconds; 0 = use option cache_ttl
 * @return string|null
 */
function nia_fullcache_get($path, $ttl = 0) {
    if (!(defined('CACHE_ENABLED') && CACHE_ENABLED) && !get_option('cache_enabled', 0)) {
        return null;
    }
    $key = nia_fullcache_key($path);
    $file = nia_fullcache_dir() . DIRECTORY_SEPARATOR . md5($key) . '.html';
    if (!is_file($file)) return null;
    if ($ttl <= 0) $ttl = (int) get_option('cache_ttl', 3600);
    if ($ttl > 0 && filemtime($file) + $ttl < time()) {
        @unlink($file);
        return null;
    }
    $html = @file_get_contents($file);
    return $html !== false ? $html : null;
}

/**
 * Store HTML for path.
 * @param string $path
 * @param string $html
 */
function nia_fullcache_set($path, $html) {
    if (!(defined('CACHE_ENABLED') && CACHE_ENABLED) && !get_option('cache_enabled', 0)) {
        return;
    }
    $key = nia_fullcache_key($path);
    $file = nia_fullcache_dir() . DIRECTORY_SEPARATOR . md5($key) . '.html';
    @file_put_contents($file, $html, LOCK_EX);
}
