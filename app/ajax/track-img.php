<?php
/**
 * View tracking for images.
 * POST/GET: media_id. Increments vibe_images.views; if logged in, adds to History playlist (media_type=image).
 */

define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');

$media_id = isset($_POST['media_id']) ? (int) $_POST['media_id'] : (int) ($_GET['media_id'] ?? 0);

if ($media_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

global $db;
$pre = $db->prefix();

// Support vibe_images.views (add column if missing for existing installs)
$row = $db->fetch("SELECT id FROM {$pre}images WHERE id = ? LIMIT 1", [$media_id]);
if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

try {
    $db->query("UPDATE {$pre}images SET views = views + 1 WHERE id = ?", [$media_id]);
} catch (Throwable $e) {
    // Column may not exist yet
    echo json_encode(['ok' => true]);
    exit;
}

$uid = current_user_id();
if ($uid) {
    require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.media.php';
    ensure_system_playlists($uid);
    $pl = get_playlist(PLAYLIST_HISTORY, $uid);
    if ($pl) {
        $exists = $db->fetch(
            "SELECT id FROM {$pre}playlist_data WHERE playlist_id = ? AND media_id = ? AND media_type = ?",
            [(int) $pl->id, $media_id, 'image']
        );
        if (!$exists) {
            $max = $db->fetch("SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM {$pre}playlist_data WHERE playlist_id = ?", [(int) $pl->id]);
            $db->query(
                "INSERT INTO {$pre}playlist_data (playlist_id, media_id, media_type, sort_order) VALUES (?, ?, 'image', ?)",
                [(int) $pl->id, $media_id, $max ? (int) $max->n : 1]
            );
        }
    }
}

echo json_encode(['ok' => true]);
