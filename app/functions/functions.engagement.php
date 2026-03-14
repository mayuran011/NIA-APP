<?php
/**
 * Engagement: likes/dislikes (vibe_likes), comments (threaded), reports.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/** Get user's like value for object (1, -1, or 0). */
function get_user_like($user_id, $object_type, $object_id) {
    global $db;
    $pre = $db->prefix();
    $row = $db->fetch("SELECT value FROM {$pre}likes WHERE user_id = ? AND object_type = ? AND object_id = ?", [(int) $user_id, $object_type, (int) $object_id]);
    return $row ? (int) $row->value : 0;
}

/** Set like (1) or dislike (-1); updates vibe_videos.likes if object_type=video. */
function set_like($user_id, $object_type, $object_id, $value) {
    global $db;
    $user_id = (int) $user_id;
    $object_id = (int) $object_id;
    $value = $value === -1 ? -1 : 1;
    if ($user_id <= 0) return false;
    $pre = $db->prefix();
    $prev = get_user_like($user_id, $object_type, $object_id);
    $db->query(
        "INSERT INTO {$pre}likes (user_id, object_type, object_id, value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)",
        [$user_id, $object_type, $object_id, $value]
    );
    if ($object_type === 'video') {
        $sum = $db->fetch("SELECT COALESCE(SUM(value), 0) AS s FROM {$pre}likes WHERE object_type = 'video' AND object_id = ?", [$object_id]);
        $db->query("UPDATE {$pre}videos SET likes = ? WHERE id = ?", [max(0, (int) ($sum->s ?? 0)), $object_id]);
        
        // Notification for like
        if ($value === 1 && $prev !== 1) {
            $video = get_video($object_id, false);
            if ($video && (int) $video->user_id !== $user_id) {
                add_notification($video->user_id, $user_id, 'like', $object_id, 'video');
            }
        }
    }
    return true;
}

/** Get comments for object (top-level or by parent), with author. */
function get_comments($object_type, $object_id, $parent_id = 0, $limit = 50, $offset = 0) {
    global $db;
    $pre = $db->prefix();
    return $db->fetchAll(
        "SELECT c.*, u.name AS author_name, u.username AS author_username FROM {$pre}comments c LEFT JOIN {$pre}users u ON u.id = c.user_id WHERE c.object_type = ? AND c.object_id = ? AND c.parent_id = ? ORDER BY c.created_at ASC LIMIT ? OFFSET ?",
        [$object_type, (int) $object_id, (int) $parent_id, (int) $limit, (int) $offset]
    );
}

/** Add comment; returns new comment id or 0. */
function add_comment($object_type, $object_id, $user_id, $body, $parent_id = 0) {
    global $db;
    $user_id = (int) $user_id;
    $object_id = (int) $object_id;
    $parent_id = (int) $parent_id;
    $body = trim($body);
    if ($user_id <= 0 || $body === '') return 0;
    $pre = $db->prefix();
    $db->query("INSERT INTO {$pre}comments (parent_id, object_type, object_id, user_id, body) VALUES (?, ?, ?, ?, ?)", [$parent_id, $object_type, $object_id, $user_id, $body]);
    $comment_id = (int) $db->pdo()->lastInsertId();
    
    // Notification for comment
    if ($comment_id > 0) {
        if ($object_type === 'video') {
            $video = get_video($object_id, false);
            if ($video && (int) $video->user_id !== $user_id) {
                add_notification($video->user_id, $user_id, 'comment', $object_id, 'video');
            }
        }
    }
    
    return $comment_id;
}

/** Get single comment. */
function get_comment($id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT c.*, u.name AS author_name, u.username AS author_username FROM {$pre}comments c LEFT JOIN {$pre}users u ON u.id = c.user_id WHERE c.id = ?", [(int) $id]);
}

/** Like comment (toggle). Returns new likes_count. */
function like_comment($comment_id, $user_id) {
    $comment_id = (int) $comment_id;
    $user_id = (int) $user_id;
    if ($user_id <= 0) return 0;
    $pre = $db->prefix();
    $row = $db->fetch("SELECT id FROM {$pre}comment_likes WHERE comment_id = ? AND user_id = ?", [$comment_id, $user_id]);
    if ($row) {
        $db->query("DELETE FROM {$pre}comment_likes WHERE comment_id = ? AND user_id = ?", [$comment_id, $user_id]);
        $db->query("UPDATE {$pre}comments SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?", [$comment_id]);
    } else {
        $db->query("INSERT IGNORE INTO {$pre}comment_likes (comment_id, user_id) VALUES (?, ?)", [$comment_id, $user_id]);
        $db->query("UPDATE {$pre}comments SET likes_count = likes_count + 1 WHERE id = ?", [$comment_id]);
    }
    $c = $db->fetch("SELECT likes_count FROM {$pre}comments WHERE id = ?", [$comment_id]);
    return $c ? (int) $c->likes_count : 0;
}

/** Delete comment (only own or moderator). */
function delete_comment($comment_id, $user_id) {
    global $db;
    $comment_id = (int) $comment_id;
    $user_id = (int) $user_id;
    $pre = $db->prefix();
    $c = $db->fetch("SELECT user_id FROM {$pre}comments WHERE id = ?", [$comment_id]);
    if (!$c) return false;
    if ((int) $c->user_id !== $user_id && !is_moderator()) return false;
    $db->query("DELETE FROM {$pre}comment_likes WHERE comment_id = ?", [$comment_id]);
    $db->query("DELETE FROM {$pre}comments WHERE id = ? OR parent_id = ?", [$comment_id, $comment_id]);
    return true;
}

/** Submit report. */
function add_report($user_id, $object_type, $object_id, $reason, $details = '') {
    global $db;
    $user_id = (int) $user_id;
    $object_id = (int) $object_id;
    $reason = trim($reason);
    if ($object_id <= 0 || $reason === '') return false;
    $pre = $db->prefix();
    $db->query("INSERT INTO {$pre}reports (user_id, object_type, object_id, reason, details) VALUES (?, ?, ?, ?, ?)", [$user_id, $object_type, $object_id, $reason, trim($details)]);
    return true;
}
