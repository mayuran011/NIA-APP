<?php
/**
 * Search: global (videos/music, pictures, channels, playlists) and dedicated (images, people, playlists).
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/**
 * Global search by keyword and optional type filter.
 * @param string $q     Search query
 * @param string $type videos|music|pictures|channels|playlists|all
 * @param int    $limit
 * @param int    $offset
 * @return array [ 'videos' => [], 'images' => [], 'channels' => [], 'playlists' => [] ]
 */
function search_global($q, $type = 'all', $limit = 24, $offset = 0) {
    global $db;
    $q = trim($q);
    $like = $q !== '' ? '%' . preg_replace('/%|_/', '\\\\$0', $q) . '%' : null;
    $uid = current_user_id();
    $pre = $db->prefix();
    $out = ['videos' => [], 'images' => [], 'channels' => [], 'playlists' => []];

    if ($like === null && $q !== '') {
        return $out;
    }

    $per_type = max(1, (int) $limit);
    $off = (int) $offset;

    if (($type === 'all' || $type === 'videos' || $type === 'music') && $like) {
        $where = "(v.private = 0 OR v.user_id = ?) AND (v.title LIKE ? OR v.description LIKE ?)";
        $params = [$uid, $like, $like];
        if ($type === 'videos') {
            $where .= " AND v.type = 'video'";
        } elseif ($type === 'music') {
            $where .= " AND v.type = 'music'";
        } else {
            $where .= " AND v.type IN ('video', 'music')";
        }
        $out['videos'] = $db->fetchAll(
            "SELECT v.*, u.name AS channel_name, u.username AS channel_username FROM {$pre}videos v LEFT JOIN {$pre}users u ON u.id = v.user_id WHERE {$where} ORDER BY v.created_at DESC LIMIT {$per_type} OFFSET {$off}",
            $params
        );

        // --- Live search (YouTube) ---
        $yt_key = function_exists('nia_youtube_api_key') ? nia_youtube_api_key() : '';
        if ($yt_key && ($type === 'all' || $type === 'videos')) {
            $yt_results = nia_youtube_search($q, 8);
            foreach ($yt_results as $yt) {
                // Check if already in DB to avoid dupes in results
                $exists = false;
                foreach ($out['videos'] as $local) {
                    if (strpos($local->remote_url ?? '', $yt['id']) !== false) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $out['videos'][] = (object) [
                        'id' => $yt['id'], // String ID for YT
                        'title' => $yt['title'],
                        'thumb' => $yt['thumb'],
                        'type' => 'video',
                        'views' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'channel_name' => 'YouTube',
                        'is_remote' => true
                    ];
                }
            }
        }
    }

    if (($type === 'all' || $type === 'pictures') && $like) {
        $out['images'] = $db->fetchAll(
            "SELECT * FROM {$pre}images WHERE title LIKE ? OR tags LIKE ? OR description LIKE ? ORDER BY created_at DESC LIMIT {$per_type} OFFSET {$off}",
            [$like, $like, $like]
        );
    }

    if (($type === 'all' || $type === 'channels') && $like) {
        $out['channels'] = $db->fetchAll(
            "SELECT * FROM {$pre}users WHERE name LIKE ? OR username LIKE ? ORDER BY name LIMIT {$per_type} OFFSET {$off}",
            [$like, $like]
        );
    }

    if (($type === 'all' || $type === 'playlists') && $like) {
        $out['playlists'] = $db->fetchAll(
            "SELECT p.* FROM {$pre}playlists p WHERE p.system_key IS NULL AND (p.name LIKE ? OR p.slug LIKE ?) ORDER BY p.name LIMIT {$per_type} OFFSET {$off}",
            [$like, $like]
        );
    }

    return $out;
}

/**
 * Live search suggestions (from first character); small result sets per type.
 * @param string $q Query (min 1 char)
 * @param int    $per_section Max items per section (default 5)
 * @return array [ 'videos' => [], 'music' => [], 'playlists' => [], 'channels' => [] ]
 */
function search_suggestions($q, $per_section = 5) {
    global $db;
    $q = trim($q);
    if ($q === '') {
        return ['videos' => [], 'music' => [], 'playlists' => [], 'channels' => []];
    }
    $like = '%' . preg_replace('/%|_/', '\\\\$0', $q) . '%';
    $uid = current_user_id();
    $pre = $db->prefix();
    $n = (int) $per_section;

    $videos = $db->fetchAll(
        "SELECT id, title, thumb, type FROM {$pre}videos WHERE (private = 0 OR user_id = ?) AND type = 'video' AND (title LIKE ? OR description LIKE ?) ORDER BY created_at DESC LIMIT {$n}",
        [$uid, $like, $like]
    );
    $music = $db->fetchAll(
        "SELECT id, title, thumb, type FROM {$pre}videos WHERE (private = 0 OR user_id = ?) AND type = 'music' AND (title LIKE ? OR description LIKE ?) ORDER BY created_at DESC LIMIT {$n}",
        [$uid, $like, $like]
    );
    $playlists = $db->fetchAll(
        "SELECT id, name, slug FROM {$pre}playlists WHERE system_key IS NULL AND (name LIKE ? OR slug LIKE ?) LIMIT {$n}",
        [$like, $like]
    );
    $channels = $db->fetchAll(
        "SELECT id, username, name, avatar FROM {$pre}users WHERE name LIKE ? OR username LIKE ? LIMIT {$n}",
        [$like, $like]
    );

    return ['videos' => $videos, 'music' => $music, 'playlists' => $playlists, 'channels' => $channels];
}
