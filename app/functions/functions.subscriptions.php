<?php
/**
 * Subscriptions: vibe_users_friends (subscribe/unsubscribe); subscriber count on profile.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/** @return int Subscriber count for user (people who subscribed TO this user). */
function subscriber_count($user_id) {
    global $db;
    $pre = $db->prefix();
    $row = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}users_friends WHERE friend_id = ?", [(int) $user_id]);
    return $row ? (int) $row->c : 0;
}

/** @return bool Whether current user is subscribed to $channel_user_id. */
function is_subscribed_to($channel_user_id) {
    global $db;
    $uid = current_user_id();
    if ($uid <= 0) return false;
    $pre = $db->prefix();
    $row = $db->fetch("SELECT id FROM {$pre}users_friends WHERE user_id = ? AND friend_id = ?", [$uid, (int) $channel_user_id]);
    return (bool) $row;
}

/** Subscribe current user to channel_user_id. */
function subscribe_to($channel_user_id) {
    global $db;
    $uid = current_user_id();
    if ($uid <= 0 || $channel_user_id == $uid) return false;
    $pre = $db->prefix();
    try {
        $db->query("INSERT IGNORE INTO {$pre}users_friends (user_id, friend_id) VALUES (?, ?)", [$uid, (int) $channel_user_id]);
        add_activity($uid, 'subscribed', 'user', $channel_user_id);
        add_notification($channel_user_id, $uid, 'subscribe', $uid, 'user');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** Unsubscribe current user from channel_user_id. */
function unsubscribe_from($channel_user_id) {
    global $db;
    $uid = current_user_id();
    if ($uid <= 0) return false;
    $pre = $db->prefix();
    $db->query("DELETE FROM {$pre}users_friends WHERE user_id = ? AND friend_id = ?", [$uid, (int) $channel_user_id]);
    return true;
}

/**
 * Get media from followed channels.
 * @param int|null $user_id
 * @param int      $limit
 */
function get_followed_media($user_id = null, $limit = 24) {
    global $db;
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return [];
    
    $pre = $db->prefix();
    $sql = "SELECT v.*, u.name as channel_name, u.avatar as channel_avatar, u.username as channel_username
            FROM {$pre}videos v
            JOIN {$pre}users_friends f ON f.friend_id = v.user_id
            JOIN {$pre}users u ON u.id = v.user_id
            WHERE f.user_id = ? AND (v.private = 0 OR v.user_id = ?)
            ORDER BY v.created_at DESC
            LIMIT " . (int) $limit;
            
    return $db->fetchAll($sql, [$user_id, $user_id]);
}
