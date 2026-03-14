<?php
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
header('Content-Type: application/json; charset=utf-8');
if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}
$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int) ($_GET['user_id'] ?? 0);
$action = isset($_POST['action']) ? trim($_POST['action']) : trim($_GET['action'] ?? '');
if ($user_id <= 0 || $user_id === current_user_id()) {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}
if ($action === 'subscribe') {
    subscribe_to($user_id);
    echo json_encode(['ok' => true, 'subscribed' => true]);
} elseif ($action === 'unsubscribe') {
    unsubscribe_from($user_id);
    echo json_encode(['ok' => true, 'subscribed' => false]);
} else {
    echo json_encode(['ok' => true, 'subscribed' => is_subscribed_to($user_id)]);
}
