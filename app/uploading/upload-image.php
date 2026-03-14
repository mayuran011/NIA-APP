<?php
/**
 * Image upload: save to media/images/{id}/, insert nia_images.
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
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['ok' => false, 'error' => 'invalid_type']);
    exit;
}

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.upload.php';
global $db;

$media = media_folder();
$img_dir = $media . 'images';
if (!is_dir($img_dir)) @mkdir($img_dir, 0755, true);

$title = pathinfo($name, PATHINFO_FILENAME);

$pre = $db->prefix();
$db->query(
    "INSERT INTO {$pre}images (user_id, album_id, title, path) VALUES (?, 0, ?, '')",
    [current_user_id(), $title]
);
$id = (int) $db->pdo()->lastInsertId();

$dest_dir = $img_dir . DIRECTORY_SEPARATOR . $id;
if (!is_dir($dest_dir)) @mkdir($dest_dir, 0755, true);
$dest_file = $dest_dir . DIRECTORY_SEPARATOR . 'default.' . $ext;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest_file)) {
    $db->query("DELETE FROM {$pre}images WHERE id = ?", [$id]);
    echo json_encode(['ok' => false, 'error' => 'move_failed']);
    exit;
}

$path = 'images' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'default.' . $ext;
$thumb_url = rtrim(SITE_URL, '/') . '/media/' . str_replace(DIRECTORY_SEPARATOR, '/', $path);
$db->query("UPDATE {$pre}images SET path = ?, thumb = ? WHERE id = ?", [$path, $thumb_url, $id]);

$url = function_exists('view_url') ? view_url($id, $title) : image_url($id, $title);
$image_src = rtrim(SITE_URL, '/') . '/media/' . str_replace(DIRECTORY_SEPARATOR, '/', $path);
echo json_encode(['ok' => true, 'id' => $id, 'url' => $url, 'image_src' => $image_src]);
