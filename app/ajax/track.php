<?php
/**
 * View/play tracking for video and music.
 * POST/GET: media_type=video|music, media_id. Increments vibe_videos.views; if logged in, adds to History playlist.
 */

define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');

$media_type = isset($_POST['media_type']) ? trim($_POST['media_type']) : trim($_GET['media_type'] ?? 'video');
$media_id   = isset($_POST['media_id']) ? (int) $_POST['media_id'] : (int) ($_GET['media_id'] ?? 0);

if (!in_array($media_type, ['video', 'music'], true)) {
    $media_type = 'video';
}
if ($media_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

global $db;
$pre = $db->prefix();

$row = $db->fetch("SELECT id FROM {$pre}videos WHERE id = ? AND type IN ('video', 'music') LIMIT 1", [$media_id]);
if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$db->query("UPDATE {$pre}videos SET views = views + 1 WHERE id = ?", [$media_id]);

$uid = current_user_id();
if ($uid) {
    require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.media.php';
    ensure_system_playlists($uid);
    $pl = get_playlist(PLAYLIST_HISTORY, $uid);
    if ($pl) {
        $exists = $db->fetch(
            "SELECT id FROM {$pre}playlist_data WHERE playlist_id = ? AND media_id = ? AND media_type = ?",
            [(int) $pl->id, $media_id, $media_type]
        );
        if (!$exists) {
            $max = $db->fetch("SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM {$pre}playlist_data WHERE playlist_id = ?", [(int) $pl->id]);
            $db->query(
                "INSERT INTO {$pre}playlist_data (playlist_id, media_id, media_type, sort_order) VALUES (?, ?, ?, ?)",
                [(int) $pl->id, $media_id, $media_type, $max ? (int) $max->n : 1]
            );
        }
    }
    if (function_exists('add_activity')) {
        add_activity($uid, 'watched', $media_type, $media_id);
    }
}

echo json_encode(['ok' => true]);
