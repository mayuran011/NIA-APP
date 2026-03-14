<?php
/**
 * Activity (buzz): liked, disliked, added to playlist, watched, shared, subscribed.
 * vibe_activity; shown on dashboard/activity and "What's new" feed.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/**
 * Add activity row.
 * @param int    $user_id    Actor user id
 * @param string $action     liked, disliked, playlist_add, watched, shared, subscribed
 * @param string $object_type video, music, image, user, playlist
 * @param int    $object_id
 * @param string $extra      Optional JSON or string
 */
function add_activity($user_id, $action, $object_type = 'video', $object_id = 0, $extra = null) {
    global $db;
    if (function_exists('do_action')) {
        do_action('add-activity', (int) $user_id, $action, $object_type, (int) $object_id, $extra);
    }
    $pre = $db->prefix();
    $db->query(
        "INSERT INTO {$pre}activity (user_id, action, object_type, object_id, extra) VALUES (?, ?, ?, ?, ?)",
        [(int) $user_id, $action, $object_type, (int) $object_id, $extra]
    );
}

/**
 * Get activity feed (e.g. for dashboard/activity).
 * @param array $args limit, offset, user_id (filter by actor), action
 * @return array
 */
function get_activity($args = []) {
    global $db;
    $pre = $db->prefix();
    $limit = isset($args['limit']) ? (int) $args['limit'] : 30;
    $offset = isset($args['offset']) ? (int) $args['offset'] : 0;
    $user_id = isset($args['user_id']) ? (int) $args['user_id'] : null;
    $action = isset($args['action']) ? trim($args['action']) : null;

    $where = '1=1';
    $params = [];
    if ($user_id > 0) {
        $where .= ' AND a.user_id = ?';
        $params[] = $user_id;
    }
    if ($action !== null && $action !== '') {
        $where .= ' AND a.action = ?';
        $params[] = $action;
    }
    $params[] = $limit;
    $params[] = $offset;

    $sql = "SELECT a.*, u.username, u.name AS user_name, u.avatar AS user_avatar
            FROM {$pre}activity a
            LEFT JOIN {$pre}users u ON u.id = a.user_id
            WHERE {$where}
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?";
    $results = $db->fetchAll($sql, $params);
    if (function_exists('apply_filters')) {
        $results = apply_filters('get-activity', $results, $args);
    }
    return $results;
}
