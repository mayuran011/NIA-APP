<?php
/**
 * Add to playlist / Watch later / History / Likes.
 * POST: action = later | history | likes | playlist, media_id, media_type (video|image), [playlist_id]
 */

define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : (isset($_GET['action']) ? trim($_GET['action']) : '');
$media_id = isset($_POST['media_id']) ? (int) $_POST['media_id'] : (int) ($_GET['media_id'] ?? 0);
$media_type = isset($_POST['media_type']) ? trim($_POST['media_type']) : trim($_GET['media_type'] ?? 'video');
$playlist_id = isset($_POST['playlist_id']) ? (int) $_POST['playlist_id'] : (int) ($_GET['playlist_id'] ?? 0);

if (!in_array($media_type, ['video', 'image'], true)) {
    $media_type = 'video';
}
if ($media_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_media']);
    exit;
}

global $db;
$pre = $db->prefix();
$uid = current_user_id();

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.media.php';

$target_playlist_id = null;
$system_key = null;

if ($action === 'later') {
    $system_key = PLAYLIST_LATER;
} elseif ($action === 'history') {
    $system_key = PLAYLIST_HISTORY;
} elseif ($action === 'likes') {
    $system_key = PLAYLIST_LIKES;
} elseif ($action === 'playlist' && $playlist_id > 0) {
    $pl = get_playlist($playlist_id, $uid);
    if (!$pl || (int) $pl->user_id !== $uid) {
        echo json_encode(['ok' => false, 'error' => 'invalid_playlist']);
        exit;
    }
    $target_playlist_id = (int) $pl->id;
} else {
    echo json_encode(['ok' => false, 'error' => 'invalid_action']);
    exit;
}

if ($system_key) {
    ensure_system_playlists($uid);
    $pl = get_playlist($system_key, $uid);
    $target_playlist_id = $pl ? (int) $pl->id : null;
}

if (!$target_playlist_id) {
    echo json_encode(['ok' => false, 'error' => 'playlist_not_found']);
    exit;
}

// Check if already in playlist
$exists = $db->fetch(
    "SELECT id FROM {$pre}playlist_data WHERE playlist_id = ? AND media_id = ? AND media_type = ?",
    [$target_playlist_id, $media_id, $media_type]
);
if ($exists) {
    echo json_encode(['ok' => true, 'already' => true]);
    exit;
}

$max_order = $db->fetch("SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM {$pre}playlist_data WHERE playlist_id = ?", [$target_playlist_id]);
$order = $max_order ? (int) $max_order->n : 1;
$db->query(
    "INSERT INTO {$pre}playlist_data (playlist_id, media_id, media_type, sort_order) VALUES (?, ?, ?, ?)",
    [$target_playlist_id, $media_id, $media_type, $order]
);

if (function_exists('add_activity')) {
    if ($action === 'likes') {
        add_activity($uid, 'liked', $media_type, $media_id);
    } elseif ($action === 'later') {
        add_activity($uid, 'playlist_add', $media_type, $media_id, 'Watch later');
    }
}

echo json_encode(['ok' => true, 'already' => false]);
