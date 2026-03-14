<?php
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}
$comment_id = (int) ($_POST['comment_id'] ?? $_GET['comment_id'] ?? 0);
if ($comment_id <= 0) {
    echo json_encode(['ok' => false]);
    exit;
}
$ok = delete_comment($comment_id, current_user_id());
echo json_encode(['ok' => $ok]);
