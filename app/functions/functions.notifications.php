<?php
/**
 * Notifications: nia_notifications table.
 * add_notification(), get_notifications(), mark_notification_read().
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/**
 * Add a notification for a user.
 * @param int    $user_id    Receiver user id
 * @param int    $actor_id   Actor user id (who triggered it)
 * @param string $type       like, comment, subscribe, mention, info
 * @param int    $object_id  ID of the video, comment, etc.
 * @param string $object_type video, comment, user, playlist
 * @param string $message    Optional display message
 */
function add_notification($user_id, $actor_id, $type, $object_id = 0, $object_type = '', $message = '') {
    global $db;
    $user_id = (int) $user_id;
    $actor_id = (int) $actor_id;
    if ($user_id <= 0 || ($user_id === $actor_id && $type !== 'info')) return false;

    $pre = $db->prefix();
    try {
        $db->query(
            "INSERT INTO {$pre}notifications (user_id, actor_id, type, object_id, object_type, message) VALUES (?, ?, ?, ?, ?, ?)",
            [$user_id, $actor_id, $type, (int) $object_id, $object_type, $message]
        );
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Get recent notifications for a user.
 * @param int|null $user_id Default current user.
 * @param int      $limit
 * @param bool     $unread_only
 */
function get_notifications($user_id = null, $limit = 10, $unread_only = false) {
    global $db;
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return [];

    $pre = $db->prefix();
    $where = "n.user_id = ?";
    $params = [$user_id];
    if ($unread_only) {
        $where .= " AND n.is_read = 0";
    }

    $sql = "SELECT n.*, u.username AS actor_name, u.avatar AS actor_avatar
            FROM {$pre}notifications n
            LEFT JOIN {$pre}users u ON u.id = n.actor_id
            WHERE {$where}
            ORDER BY n.created_at DESC
            LIMIT " . (int) $limit;
    
    return $db->fetchAll($sql, $params);
}

/**
 * Count unread notifications.
 */
function count_unread_notifications($user_id = null) {
    global $db;
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return 0;
    $pre = $db->prefix();
    $row = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}notifications WHERE user_id = ? AND is_read = 0", [$user_id]);
    return $row ? (int) $row->c : 0;
}

/**
 * Mark a notification (or all) as read.
 */
function mark_notification_read($id = null, $user_id = null) {
    global $db;
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return false;
    $pre = $db->prefix();
    if ($id !== null) {
        $db->query("UPDATE {$pre}notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [(int) $id, $user_id]);
    } else {
        $db->query("UPDATE {$pre}notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", [$user_id]);
    }
    return true;
}
