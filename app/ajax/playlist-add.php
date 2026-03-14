<?php
/**
 * Create playlist or add item to playlist.
 * POST: action = create | add, [name], [playlist_id], media_id, media_type (video|image)
 */

define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : (isset($_GET['action']) ? trim($_GET['action']) : '');
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$playlist_id = isset($_POST['playlist_id']) ? (int) $_POST['playlist_id'] : 0;
$media_id = isset($_POST['media_id']) ? (int) $_POST['media_id'] : 0;
$media_type = isset($_POST['media_type']) ? trim($_POST['media_type']) : 'video';
if (!in_array($media_type, ['video', 'image'], true)) {
    $media_type = 'video';
}

global $db;
$pre = $db->prefix();
$uid = current_user_id();

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.media.php';

if ($action === 'create') {
    if ($name === '') {
        echo json_encode(['ok' => false, 'error' => 'name_required']);
        exit;
    }
    $slug = preg_replace('/[^a-z0-9\-]/i', '-', $name);
    $slug = trim($slug, '-') ?: 'playlist-' . time();
    $db->query(
        "INSERT INTO {$pre}playlists (user_id, name, slug, type) VALUES (?, ?, ?, ?)",
        [$uid, $name, $slug, $media_type]
    );
    $id = (int) $db->pdo()->lastInsertId();
    echo json_encode(['ok' => true, 'playlist_id' => $id, 'slug' => $slug]);
    exit;
}

if ($action === 'add' && $playlist_id > 0 && $media_id > 0) {
    $pl = get_playlist($playlist_id, $uid);
    if (!$pl || (int) $pl->user_id !== $uid || $pl->system_key !== null) {
        echo json_encode(['ok' => false, 'error' => 'invalid_playlist']);
        exit;
    }
    $exists = $db->fetch(
        "SELECT id FROM {$pre}playlist_data WHERE playlist_id = ? AND media_id = ? AND media_type = ?",
        [$playlist_id, $media_id, $media_type]
    );
    if ($exists) {
        echo json_encode(['ok' => true, 'already' => true]);
        exit;
    }
    $max = $db->fetch("SELECT COALESCE(MAX(sort_order), 0) + 1 AS n FROM {$pre}playlist_data WHERE playlist_id = ?", [$playlist_id]);
    $order = $max ? (int) $max->n : 1;
    $db->query(
        "INSERT INTO {$pre}playlist_data (playlist_id, media_id, media_type, sort_order) VALUES (?, ?, ?, ?)",
        [$playlist_id, $media_id, $media_type, $order]
    );
    echo json_encode(['ok' => true, 'already' => false]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'invalid_action']);
