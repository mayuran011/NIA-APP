<?php
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

$conversation_id = (int) ($_POST['conversation_id'] ?? 0);
$body = trim($_POST['body'] ?? '');
$user_id = current_user_id();

if ($conversation_id <= 0 || $body === '') {
    echo json_encode(['ok' => false, 'error' => 'missing_data']);
    exit;
}

if (send_conversation_message($conversation_id, $user_id, $body)) {
    // Notify recipient
    $conv = get_conversation($conversation_id, $user_id);
    if ($conv) {
        add_notification($conv->other_user_id, $user_id, 'message', $conversation_id, 'conversation');
    }
    
    echo json_encode([
        'ok' => true, 
        'msg' => [
            'body' => $body,
            'time' => date('M j, g:i A'),
            'me' => true,
            'sender' => current_user()->name ?? current_user()->username
        ]
    ]);
} else {
    echo json_encode(['ok' => false, 'error' => 'send_failed']);
}
?>
