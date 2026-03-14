<?php
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if ($action === 'mark_read') {
    mark_notification_read();
    echo json_encode(['ok' => true]);
    exit;
}

$notifications = get_notifications(null, 15);
$html = '';

if (empty($notifications)) {
    $html = '<div class="p-4 text-center text-muted small">No new notifications</div>';
} else {
    foreach ($notifications as $n) {
        $actor_name = !empty($n->actor_name) ? $n->actor_name : 'Someone';
        $actor_avatar = !empty($n->actor_avatar) ? $n->actor_avatar : '';
        if ($actor_avatar !== '' && strpos($actor_avatar, 'http') !== 0) { $actor_avatar = rtrim(SITE_URL, '/') . '/' . ltrim($actor_avatar, '/'); }
        
        $msg = '';
        $icon = 'notifications';
        $link = url('activity');
        
        switch($n->type) {
            case 'like':
                $icon = 'favorite';
                $msg = '<strong>' . _e($actor_name) . '</strong> liked your ' . _e($n->object_type);
                if ($n->object_type === 'video') $link = url('watch/' . $n->object_id);
                break;
            case 'comment':
                $icon = 'chat_bubble';
                $msg = '<strong>' . _e($actor_name) . '</strong> commented on your ' . _e($n->object_type);
                if ($n->object_type === 'video') $link = url('watch/' . $n->object_id);
                break;
            case 'subscribe':
                $icon = 'person_add';
                $msg = '<strong>' . _e($actor_name) . '</strong> subscribed to your channel';
                $link = url('user/' . $n->actor_id);
                break;
            case 'message':
                $icon = 'mail';
                $msg = '<strong>' . _e($actor_name) . '</strong> sent you a message';
                $link = url('conversation/' . $n->object_id);
                break;
            default:
                $msg = _e($n->message);
        }
        
        $unread_class = $n->is_read ? '' : 'bg-dark bg-opacity-25';
        $avatar_html = $actor_avatar ? '<img src="'._e($actor_avatar).'" class="rounded-circle me-3" style="width:36px; height:36px; object-fit:cover;">' : '<div class="rounded-circle me-3 bg-secondary d-flex align-items-center justify-content-center text-white" style="width:36px; height:36px; font-size:14px;">'.strtoupper(substr($actor_name, 0, 1)).'</div>';
        
        $html .= '<a href="'.$link.'" class="list-group-item list-group-item-action border-0 py-3 '.$unread_class.' d-flex align-items-start">';
        $html .= $avatar_html;
        $html .= '<div>';
        $html .= '<p class="mb-0 small text-white-50">' . $msg . '</p>';
        $html .= '<small class="text-muted" style="font-size:0.75rem;">' . (function_exists('time_ago') ? time_ago($n->created_at) : $n->created_at) . '</small>';
        $html .= '</div>';
        $html .= '</a>';
    }
}

echo json_encode(['ok' => true, 'html' => $html, 'unread' => count_unread_notifications()]);
?>
