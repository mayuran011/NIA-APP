<?php
/**
 * Process upload with FFmpeg: generate thumbnail, optional transcode.
 * Called with path or video id; uses options ffmpeg-cmd, binpath.
 */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.upload.php';

$video_id = isset($_POST['video_id']) ? (int) $_POST['video_id'] : (int) ($_GET['video_id'] ?? 0);
$path = isset($_POST['path']) ? trim($_POST['path']) : '';

$video_path = null;
$thumb_path = null;
$media = media_folder();

if ($video_id > 0) {
    $v = get_video($video_id);
    if (!$v || (int) $v->user_id !== current_user_id()) {
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
    if (empty($v->file_path)) {
        echo json_encode(['ok' => false, 'error' => 'no_file']);
        exit;
    }
    $video_path = $media . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $v->file_path);
    $thumb_path = $media . 'videos' . DIRECTORY_SEPARATOR . $video_id . DIRECTORY_SEPARATOR . 'thumb.jpg';
} elseif ($path !== '') {
    $video_path = ABSPATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $thumb_path = tmp_folder() . uniqid('thumb_', true) . '.jpg';
}

if (!($video_path && is_file($video_path))) {
    echo json_encode(['ok' => false, 'error' => 'file_not_found']);
    exit;
}

$ok = ffmpeg_thumbnail($video_path, $thumb_path, 1);
if (!$ok) {
    echo json_encode(['ok' => false, 'error' => 'ffmpeg_failed']);
    exit;
}

if ($video_id > 0) {
    $thumb_url = rtrim(SITE_URL, '/') . '/media/videos/' . $video_id . '/thumb.jpg';
    global $db;
    $pre = $db->prefix();
    $db->query("UPDATE {$pre}videos SET thumb = ? WHERE id = ?", [$thumb_url, $video_id]);
    echo json_encode(['ok' => true, 'thumb' => $thumb_url]);
} else {
    echo json_encode(['ok' => true, 'thumb_path' => $thumb_path]);
}
