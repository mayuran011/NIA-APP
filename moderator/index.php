<?php
/**
 * Admin (moderator) – base path from ADMINCP (e.g. /moderator/).
 * Sections: dashboard, videos, music, images, channels, playlists, users, comments, reports,
 * blog, blogcat, staticpages, homepage, seo, settings, languages, plugins, cache,
 * health, errorlog, ads, youtube, download, vine, activity.
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
require_once ABSPATH . 'nia_config.php';
$mod_dir = ABSPATH . 'moderator' . DIRECTORY_SEPARATOR;
require_once $mod_dir . 'inc' . DIRECTORY_SEPARATOR . 'functions.php';

// If no admin/moderator exists yet, promote the current user to admin (first-user-to-admin)
if (is_logged()) {
    global $db;
    $pre = $db->prefix();
    $count = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}users WHERE group_id IN (1, 2)"));
    if ($count === 0) {
        $uid = (int) ($_SESSION['uid'] ?? 0);
        if ($uid > 0) {
            $db->query("UPDATE {$pre}users SET group_id = 1 WHERE id = ?", [$uid]);
        }
    }
}

if (!is_logged() || !is_moderator()) {
    redirect(url('login'));
}

$section = isset($_GET['section']) ? trim($_GET['section']) : '';
$parts = $section === '' ? [] : explode('/', $section);
$page = $parts[0] ?? '';

$page_file = $mod_dir . 'pages' . DIRECTORY_SEPARATOR . $page . '.php';
if ($page !== '' && preg_match('/^[a-z0-9\-]+$/', $page) && is_file($page_file)) {
    include $page_file;
} else {
    include $mod_dir . 'pages' . DIRECTORY_SEPARATOR . 'dashboard.php';
}
