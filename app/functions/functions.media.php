<?php
/**
 * Content types and media helpers.
 * Videos & music: vibe_videos (type = video | music); thumb, duration, views, likes,
 * category, NSFW, featured, private, premium; sources via class.providers.php.
 * Images: vibe_images; galleries/albums, thumbnails, tags.
 * Channels: vibe_channels (hierarchical); browse and filtering.
 * Playlists: user + system [likes], [history], [later]; add/remove via addto.php, playlist-add.php.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

// System playlist identifiers (used in UI and ajax)
const PLAYLIST_LIKES   = '[likes]';
const PLAYLIST_HISTORY = '[history]';
const PLAYLIST_LATER   = '[later]';

/**
 * @return object|null
 */
function get_video($id, $check_private = true) {
    global $db;
    $id = (int) $id;
    $pre = $db->prefix();
    $sql = "SELECT * FROM {$pre}videos WHERE id = ? AND (private = 0 OR user_id = ?) LIMIT 1";
    $uid = $check_private ? current_user_id() : 0;
    return $db->fetch($sql, [$id, $uid]);
}

/**
 * @return array
 */
function get_videos($args = []) {
    global $db;
    $pre = $db->prefix();
    $type = isset($args['type']) ? $args['type'] : 'video';
    $category_id = isset($args['category_id']) ? (int) $args['category_id'] : null;
    $section = isset($args['section']) ? $args['section'] : 'browse';
    $limit = isset($args['limit']) ? (int) $args['limit'] : 24;
    $offset = isset($args['offset']) ? (int) $args['offset'] : 0;
    $uid = current_user_id();

    $where = ["v.type IN ('video', 'music')", "(v.private = 0 OR v.user_id = ?)"];
    $params = [$uid];

    if ($type === 'video' || $type === 'music') {
        $where[] = "v.type = ?";
        $params[] = $type;
    }
    if ($category_id !== null && $category_id > 0) {
        $where[] = "v.category_id = ?";
        $params[] = $category_id;
    }
    switch ($section) {
        case 'featured':
            $where[] = "v.featured = 1";
            break;
        case 'most-viewed':
            $order = "v.views DESC";
            break;
        case 'top-rated':
            $order = "v.likes DESC";
            break;
        default:
            $order = "v.created_at DESC";
    }
    $order = $order ?? "v.created_at DESC";
    $params[] = $limit;
    $params[] = $offset;
    $sql = "SELECT v.* FROM {$pre}videos v WHERE " . implode(' AND ', $where) . " ORDER BY {$order} LIMIT ? OFFSET ?";
    return $db->fetchAll($sql, $params);
}

/**
 * Total count for same filters as get_videos (for pagination).
 * @return int
 */
function get_videos_count($args = []) {
    global $db;
    $pre = $db->prefix();
    $type = isset($args['type']) ? $args['type'] : 'video';
    $category_id = isset($args['category_id']) ? (int) $args['category_id'] : null;
    $section = isset($args['section']) ? $args['section'] : 'browse';
    $uid = current_user_id();

    $where = ["v.type IN ('video', 'music')", "(v.private = 0 OR v.user_id = ?)"];
    $params = [$uid];
    if ($type === 'video' || $type === 'music') {
        $where[] = "v.type = ?";
        $params[] = $type;
    }
    if ($category_id !== null && $category_id > 0) {
        $where[] = "v.category_id = ?";
        $params[] = $category_id;
    }
    if ($section === 'featured') {
        $where[] = "v.featured = 1";
    }
    $row = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos v WHERE " . implode(' AND ', $where), $params);
    return isset($row->c) ? (int) $row->c : 0;
}

/**
 * Related videos for watch page: same category or channel, exclude current. Order by views then created_at.
 * @return array
 */
function get_related_videos($video_id, $limit = 20) {
    global $db;
    $video_id = (int) $video_id;
    $limit = (int) $limit;
    if ($video_id <= 0) return [];
    $pre = $db->prefix();
    $uid = current_user_id();
    $video = $db->fetch("SELECT category_id, user_id, type FROM {$pre}videos WHERE id = ? AND (private = 0 OR user_id = ?) LIMIT 1", [$video_id, $uid]);
    if (!$video) return [];
    $cat = (int) $video->category_id;
    $channel = (int) $video->user_id;
    $type = $video->type ?? 'video';
    if ($cat > 0) {
        $related = $db->fetchAll(
            "SELECT v.* FROM {$pre}videos v WHERE v.id != ? AND (v.private = 0 OR v.user_id = ?) AND v.type = ? AND v.category_id = ? ORDER BY v.views DESC, v.created_at DESC LIMIT ?",
            [$video_id, $uid, $type, $cat, $limit]
        );
    } else {
        $related = $db->fetchAll(
            "SELECT v.* FROM {$pre}videos v WHERE v.id != ? AND (v.private = 0 OR v.user_id = ?) AND v.type = ? AND v.user_id = ? ORDER BY v.views DESC, v.created_at DESC LIMIT ?",
            [$video_id, $uid, $type, $channel, $limit]
        );
    }
    if (count($related) < $limit) {
        $have_ids = array_merge([$video_id], array_map(function ($r) { return (int) $r->id; }, $related));
        $ph = implode(',', array_fill(0, count($have_ids), '?'));
        $more = $db->fetchAll(
            "SELECT v.* FROM {$pre}videos v WHERE v.id NOT IN ($ph) AND (v.private = 0 OR v.user_id = ?) AND v.type = ? ORDER BY v.created_at DESC LIMIT ?",
            array_merge($have_ids, [$uid, $type, $limit - count($related)])
        );
        $related = array_merge($related, $more);
    }
    return $related;
}

/**
 * @return object|null
 */
function get_image($id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}images WHERE id = ? LIMIT 1", [(int) $id]);
}

/**
 * @return array
 */
function get_images($args = []) {
    global $db;
    $pre = $db->prefix();
    $album_id = isset($args['album_id']) ? (int) $args['album_id'] : null;
    $limit = isset($args['limit']) ? (int) $args['limit'] : 24;
    $offset = isset($args['offset']) ? (int) $args['offset'] : 0;
    $where = "1=1";
    $params = [];
    if ($album_id !== null && $album_id > 0) {
        $where .= " AND album_id = ?";
        $params[] = $album_id;
    }
    $params[] = $limit;
    $params[] = $offset;
    $sql = "SELECT * FROM {$pre}images WHERE {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    return $db->fetchAll($sql, $params);
}

/**
 * @return object|null
 */
function get_channel($id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}channels WHERE id = ? LIMIT 1", [(int) $id]);
}

/**
 * @return array
 */
function get_channels($type = 'video', $parent_id = 0) {
    global $db;
    $pre = $db->prefix();
    return $db->fetchAll("SELECT * FROM {$pre}channels WHERE type = ? AND parent_id = ? ORDER BY sort_order, name", [$type, (int) $parent_id]);
}

/**
 * Get playlist by id or by user + system_key ([likes], [history], [later]).
 * @return object|null
 */
function get_playlist($id_or_system_key, $user_id = null) {
    global $db;
    $pre = $db->prefix();
    $user_id = $user_id ?? current_user_id();
    if (is_numeric($id_or_system_key)) {
        return $db->fetch("SELECT * FROM {$pre}playlists WHERE id = ? LIMIT 1", [(int) $id_or_system_key]);
    }
    $key = (string) $id_or_system_key;
    if (in_array($key, [PLAYLIST_LIKES, PLAYLIST_HISTORY, PLAYLIST_LATER], true)) {
        return $db->fetch("SELECT * FROM {$pre}playlists WHERE user_id = ? AND system_key = ? LIMIT 1", [$user_id, $key]);
    }
    return null;
}

/**
 * @return array
 */
function get_playlist_items($playlist_id, $media_type = 'video', $limit = 100) {
    global $db;
    $pre = $db->prefix();
    return $db->fetchAll(
        "SELECT pd.*, pd.media_id FROM {$pre}playlist_data pd WHERE pd.playlist_id = ? AND pd.media_type = ? ORDER BY pd.sort_order, pd.added_at DESC LIMIT ?",
        [(int) $playlist_id, $media_type, (int) $limit]
    );
}

/**
 * Ensure system playlists exist for user (Likes, History, Watch later).
 */
function ensure_system_playlists($user_id) {
    global $db;
    $pre = $db->prefix();
    $systems = [
        PLAYLIST_LIKES   => 'Likes',
        PLAYLIST_HISTORY => 'History',
        PLAYLIST_LATER   => 'Watch later',
    ];
    foreach ($systems as $key => $name) {
        $exists = $db->fetch("SELECT id FROM {$pre}playlists WHERE user_id = ? AND system_key = ?", [$user_id, $key]);
        if (!$exists) {
            $db->query(
                "INSERT INTO {$pre}playlists (user_id, name, slug, type, system_key) VALUES (?, ?, ?, 'video', ?)",
                [$user_id, $name, str_replace(['[', ']'], '', $key), $key]
            );
        }
    }
}
