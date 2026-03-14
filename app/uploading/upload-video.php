<?php
/**
 * Video file upload: save to media/videos/{id}/, insert nia_videos (type=video, source=local).
 * Optional: FFmpeg for thumbnail and duration.
 */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'upload_failed']);
    exit;
}

$name = $_FILES['file']['name'];
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$allowed = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'];
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['ok' => false, 'error' => 'invalid_type']);
    exit;
}

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.upload.php';
global $db;

$media = media_folder();
$vid_dir = $media . 'videos';
if (!is_dir($vid_dir)) @mkdir($vid_dir, 0755, true);

$tmp = tmp_folder();
$tmp_file = $tmp . uniqid('vid_', true) . '_' . preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name);
if (!move_uploaded_file($_FILES['file']['tmp_name'], $tmp_file)) {
    echo json_encode(['ok' => false, 'error' => 'move_failed']);
    exit;
}

$title = pathinfo($name, PATHINFO_FILENAME);
$duration = 0;
if (function_exists('ffmpeg_duration')) {
    $duration = ffmpeg_duration($tmp_file);
}

$pre = $db->prefix();
$db->query(
    "INSERT INTO {$pre}videos (user_id, title, type, source, file_path, duration) VALUES (?, ?, 'video', 'local', '', ?)",
    [current_user_id(), $title, $duration]
);
$id = (int) $db->pdo()->lastInsertId();

$dest_dir = $vid_dir . DIRECTORY_SEPARATOR . $id;
if (!is_dir($dest_dir)) @mkdir($dest_dir, 0755, true);
$dest_file = $dest_dir . DIRECTORY_SEPARATOR . 'default.' . $ext;
rename($tmp_file, $dest_file);

$file_path = 'videos' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'default.' . $ext;
$db->query("UPDATE {$pre}videos SET file_path = ? WHERE id = ?", [$file_path, $id]);

$thumb_url = null;
if (function_exists('ffmpeg_thumbnail')) {
    $thumb_path = $dest_dir . DIRECTORY_SEPARATOR . 'thumb.jpg';
    if (ffmpeg_thumbnail($dest_file, $thumb_path, 1)) {
        $thumb_url = rtrim(SITE_URL, '/') . '/media/videos/' . $id . '/thumb.jpg';
        $db->query("UPDATE {$pre}videos SET thumb = ? WHERE id = ?", [$thumb_url, $id]);
    }
}

if (function_exists('add_activity')) {
    add_activity(current_user_id(), 'shared', 'video', $id);
}

$url = watch_url($id, $title);
echo json_encode(['ok' => true, 'id' => $id, 'url' => $url, 'duration' => $duration]);
