<?php
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}
$object_type = isset($_POST['object_type']) ? trim($_POST['object_type']) : 'video';
$object_id = isset($_POST['object_id']) ? (int) $_POST['object_id'] : 0;
$body = isset($_POST['body']) ? trim($_POST['body']) : '';
$parent_id = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;
if ($object_id <= 0 || $body === '') {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}
$id = add_comment($object_type, $object_id, current_user_id(), $body, $parent_id);
if ($id) {
    $c = get_comment($id);
    echo json_encode(['ok' => true, 'comment_id' => $id, 'comment' => $c]);
} else {
    echo json_encode(['ok' => false, 'error' => 'failed']);
}
