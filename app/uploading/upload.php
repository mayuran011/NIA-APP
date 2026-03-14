<?php
/**
 * Generic file upload. Saves to tmp-folder or media; returns path for further processing.
 * Storage: mediafolder, tmp-folder; optional hide MP4 (stream only via stream.php).
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

// functions.upload.php loaded by bootstrap

$tmp = tmp_folder();
if (!is_dir($tmp)) @mkdir($tmp, 0755, true);

$name = basename($_FILES['file']['name']);
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$safe = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name) ?: 'upload';
$target = $tmp . uniqid('up_', true) . '_' . $safe;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
    echo json_encode(['ok' => false, 'error' => 'move_failed']);
    exit;
}

$relative = str_replace(ABSPATH, '', $target);
$relative = str_replace('\\', '/', $relative);
echo json_encode(['ok' => true, 'path' => $relative, 'full_path' => $target, 'name' => $name]);
