<?php
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
header('Content-Type: application/json; charset=utf-8');
$object_type = isset($_POST['object_type']) ? trim($_POST['object_type']) : 'video';
$object_id = (int) ($_POST['object_id'] ?? 0);
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$details = isset($_POST['details']) ? trim($_POST['details']) : '';
if ($object_id <= 0 || $reason === '') {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}
$allowed = ['spam', 'copyright', 'harassment', 'violence', 'other'];
if (!in_array($reason, $allowed, true)) $reason = 'other';
$uid = current_user_id();
add_report($uid ?: 0, $object_type, $object_id, $reason, $details);
echo json_encode(['ok' => true]);
