<?php
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}
$object_type = isset($_POST['object_type']) ? trim($_POST['object_type']) : (isset($_GET['object_type']) ? trim($_GET['object_type']) : 'video');
$object_id = isset($_POST['object_id']) ? (int) $_POST['object_id'] : (int) ($_GET['object_id'] ?? 0);
if ($object_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}
set_like(current_user_id(), $object_type, $object_id, 1);
$likes = 0;
if ($object_type === 'video') {
    $v = get_video($object_id);
    if ($v) $likes = (int) $v->likes;
}
echo json_encode(['ok' => true, 'value' => 1, 'likes' => $likes]);
