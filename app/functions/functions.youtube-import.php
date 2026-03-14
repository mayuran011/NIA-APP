<?php
/**
 * YouTube Importer: API helpers and import sources (channel auto-import, playlist, keyword).
 * Requires YouTube Data API v3 key in option youtube_api_key (Admin → YouTube).
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/** Max videos to fetch per manual Sync run (single channel). User can click Sync again to get more. */
if (!defined('NIA_YOUTUBE_SYNC_MAX_PER_RUN')) {
    define('NIA_YOUTUBE_SYNC_MAX_PER_RUN', 1000);
}

function nia_youtube_api_key() {
    $keys_opt = get_option('youtube_api_keys', '');
    if ($keys_opt !== '') {
        $keys = json_decode($keys_opt, true);
        if (is_array($keys)) {
            foreach ($keys as $k) {
                if (!empty($k['active']) && !empty($k['key']) && empty($k['quota_exceeded'])) {
                    return $k['key'];
                }
            }
            // If all keys in rotation are exhausted or inactive, do not fallback if array exists
            return '';
        }
    }
    return get_option('youtube_api_key', '');
}

function nia_youtube_last_api_error() {
    global $nia_youtube_last_error;
    return isset($nia_youtube_last_error) ? $nia_youtube_last_error : null;
}

function nia_youtube_set_last_api_error($msg) {
    $GLOBALS['nia_youtube_last_error'] = $msg;
}

function nia_youtube_mark_key_quota($invalid_key) {
    if (!$invalid_key) return;
    $keys_opt = get_option('youtube_api_keys', '');
    if ($keys_opt !== '') {
        $keys = json_decode($keys_opt, true);
        $changed = false;
        if (is_array($keys)) {
            foreach ($keys as &$k) {
                if ($k['key'] === $invalid_key) {
                    $k['quota_exceeded'] = 1;
                    $k['errors'] = ($k['errors'] ?? 0) + 1;
                    $changed = true;
                }
            }
            if ($changed) {
                update_option('youtube_api_keys', json_encode($keys));
            }
        }
    }
}

function nia_youtube_api_get($endpoint, $params = [], $retry_count = 0) {
    nia_youtube_set_last_api_error(null);
    $key = nia_youtube_api_key();
    if ($key === '') {
        nia_youtube_set_last_api_error('No available API keys or quota exceeded for all keys.');
        return null;
    }
    $params['key'] = $key;
    $url = 'https://www.googleapis.com/youtube/v3/' . ltrim($endpoint, '/') . '?' . http_build_query($params);
    $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 15]]);
    $json = @file_get_contents($url, false, $ctx);
    $data = $json ? json_decode($json, true) : null;
    if ($data && !empty($data['error'])) {
        $msg = $data['error']['message'] ?? $data['error']['errors'][0]['reason'] ?? 'Unknown API error';
        $code = $data['error']['code'] ?? 0;
        if ($code === 403) {
            $msg = 'API quota exceeded or API key invalid. ' . $msg;
            nia_youtube_mark_key_quota($key);
            // Retry with next key if available, prevent infinite loop (max 10 retries)
            if ($retry_count < 10) {
                return nia_youtube_api_get($endpoint, $params, $retry_count + 1);
            }
        }
        elseif ($code === 400) $msg = 'Bad request. ' . $msg;
        nia_youtube_set_last_api_error($msg);
    }
    return $data;
}

/** Parse playlist ID from YouTube URL. */
function nia_youtube_parse_playlist_id($url) {
    if (preg_match('#[?&]list=([a-zA-Z0-9_-]+)#', $url, $m)) return $m[1];
    return null;
}

/** Parse channel ID from YouTube URL. Supports /channel/UCxxx, /@handle, and /c/name (uses API where needed). */
function nia_youtube_parse_channel_id($url) {
    $url = trim($url);
    if ($url === '') return null;
    // Direct channel ID: youtube.com/channel/UC...
    if (preg_match('#youtube\.com/channel/([a-zA-Z0-9_-]+)#', $url, $m)) return $m[1];
    // @handle: use channels.list forHandle (works for custom URLs) or fallback to search
    if (preg_match('#youtube\.com/@([a-zA-Z0-9_.-]+)#', $url, $m)) {
        $handle = $m[1];
        $at_handle = (strpos($handle, '@') === 0) ? $handle : ('@' . $handle);
        $data = nia_youtube_api_get('channels', ['part' => 'id', 'forHandle' => $at_handle]);
        if ($data && !empty($data['items'][0]['id'])) return $data['items'][0]['id'];
        $search = nia_youtube_api_get('search', ['part' => 'snippet', 'q' => $at_handle, 'type' => 'channel', 'maxResults' => 1]);
        if ($search && !empty($search['items'][0]['snippet']['channelId'])) return $search['items'][0]['snippet']['channelId'];
    }
    // /c/customname: resolve via search
    if (preg_match('#youtube\.com/c/([a-zA-Z0-9_.-]+)#', $url, $m)) {
        $q = str_replace(['-', '_'], ' ', $m[1]);
        $search = nia_youtube_api_get('search', ['part' => 'snippet', 'q' => $q, 'type' => 'channel', 'maxResults' => 1]);
        if ($search && !empty($search['items'][0]['snippet']['channelId'])) return $search['items'][0]['snippet']['channelId'];
    }
    return null;
}

/** Search videos by keyword. Returns array of { id, title, thumb, url }. */
function nia_youtube_search($keyword, $maxResults = 15) {
    $data = nia_youtube_api_get('search', [
        'part' => 'snippet',
        'type' => 'video',
        'q' => $keyword,
        'maxResults' => min(50, (int) $maxResults),
    ]);
    if (empty($data['items'])) return [];
    $out = [];
    foreach ($data['items'] as $item) {
        $id = $item['id']['videoId'] ?? null;
        if (!$id) continue;
        $s = $item['snippet'] ?? [];
        $out[] = [
            'id' => $id,
            'title' => $s['title'] ?? '',
            'thumb' => $s['thumbnails']['medium']['url'] ?? $s['thumbnails']['default']['url'] ?? null,
            'url' => 'https://www.youtube.com/watch?v=' . $id,
        ];
    }
    return $out;
}

/** Get single video details. Returns object { id, title, description, thumb, duration, views }. */
function nia_youtube_video_details($videoId) {
    if (!$videoId) return null;
    $data = nia_youtube_api_get('videos', [
        'part' => 'snippet,contentDetails,statistics',
        'id' => $videoId
    ]);
    if (empty($data['items'][0])) return null;
    $v = $data['items'][0];
    $s = $v['snippet'] ?? [];
    $c = $v['contentDetails'] ?? [];
    $st = $v['statistics'] ?? [];
    
    // Parse ISO 8601 duration (PT#M#S)
    $duration = 0;
    if (!empty($c['duration'])) {
        try {
            $interval = new DateInterval($c['duration']);
            $duration = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (Exception $e) {}
    }

    return [
        'id' => $v['id'],
        'title' => $s['title'] ?? '',
        'description' => $s['description'] ?? '',
        'thumb' => $s['thumbnails']['maxres']['url'] ?? $s['thumbnails']['high']['url'] ?? $s['thumbnails']['medium']['url'] ?? null,
        'duration' => $duration,
        'views' => (int) ($st['viewCount'] ?? 0),
        'published_at' => $s['publishedAt'] ?? null,
        'channel_title' => $s['channelTitle'] ?? null
    ];
}

/** Get playlist items. Returns [ 'items' => [...], 'nextPageToken' => token or null ]. */
function nia_youtube_playlist_items($playlistId, $maxResults = 50, $pageToken = null) {
    $params = [
        'part' => 'snippet',
        'playlistId' => $playlistId,
        'maxResults' => min(50, (int) $maxResults),
    ];
    if ($pageToken !== null && $pageToken !== '') $params['pageToken'] = $pageToken;
    $data = nia_youtube_api_get('playlistItems', $params);
    if (empty($data['items'])) return ['items' => [], 'nextPageToken' => $data['nextPageToken'] ?? null];
    $out = [];
    foreach ($data['items'] as $item) {
        $id = $item['snippet']['resourceId']['videoId'] ?? null;
        if (!$id) continue;
        $s = $item['snippet'];
        $out[] = [
            'id' => $id,
            'title' => $s['title'] ?? '',
            'thumb' => $s['thumbnails']['medium']['url'] ?? $s['thumbnails']['default']['url'] ?? null,
            'url' => 'https://www.youtube.com/watch?v=' . $id,
            'published_at' => $s['publishedAt'] ?? null,
            'channel_title' => $s['channelTitle'] ?? null
        ];
    }
    return ['items' => $out, 'nextPageToken' => $data['nextPageToken'] ?? null];
}

/** Get channel uploads: one page. Returns [ 'items' => [...], 'nextPageToken' => token or null ]. */
function nia_youtube_channel_uploads_page($channelId, $maxResults = 50, $pageToken = null) {
    $data = nia_youtube_api_get('channels', [
        'part' => 'contentDetails',
        'id' => $channelId,
    ]);
    $uploadsId = ($data && isset($data['items'][0]['contentDetails']['relatedPlaylists']['uploads'])) ? $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] : null;
    if (!$uploadsId) return ['items' => [], 'nextPageToken' => null];
    return nia_youtube_playlist_items($uploadsId, $maxResults, $pageToken);
}

/** Get channel uploads (single page, backward compatible). Returns array of video items. */
function nia_youtube_channel_uploads($channelId, $maxResults = 20) {
    $r = nia_youtube_channel_uploads_page($channelId, $maxResults, null);
    return $r['items'] ?? [];
}

/** Get channel title from YouTube API. */
function nia_youtube_channel_title($channelId) {
    if (!$channelId) return null;
    $data = nia_youtube_api_get('channels', [
        'part' => 'snippet',
        'id' => $channelId,
    ]);
    if (empty($data['items'][0]['snippet']['title'])) return null;
    return $data['items'][0]['snippet']['title'];
}

/** Get channel display info from YouTube API (title + total video count). Returns ['title' => string, 'videoCount' => int] or null. */
function nia_youtube_channel_stats($channelId) {
    if (!$channelId) return null;
    $data = nia_youtube_api_get('channels', [
        'part' => 'snippet,statistics',
        'id' => $channelId,
    ]);
    if (empty($data['items'][0])) return null;
    $item = $data['items'][0];
    $title = $item['snippet']['title'] ?? null;
    $videoCount = isset($item['statistics']['videoCount']) ? (int) $item['statistics']['videoCount'] : 0;
    return ['title' => $title, 'videoCount' => $videoCount];
}

/** Add import source (channel/playlist/keyword). Returns source row id or 0 on failure. */
function nia_youtube_add_import_source($user_id, $type, $value, $auto_import = 0) {
    global $db;
    $value = trim($value);
    if ($value === '') return 0;
    $type = in_array($type, ['channel', 'playlist', 'keyword'], true) ? $type : 'channel';
    $auto_import = $type === 'channel' ? (int) $auto_import : 0;
    $pre = $db->prefix();
    $table = $pre . 'youtube_import_sources';
    try {
        $db->query(
            "INSERT INTO `{$table}` (user_id, type, value, auto_import) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE auto_import = VALUES(auto_import), last_imported_at = NULL",
            [(int) $user_id, $type, $value, $auto_import]
        );
        $row = $db->fetch("SELECT id FROM `{$table}` WHERE user_id = ? AND type = ? AND value = ? LIMIT 1", [(int) $user_id, $type, $value]);
        return $row ? (int) $row->id : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

/** Update source channel_name, yt_video_count, and/or total_imported (delta). */
function nia_youtube_update_source_meta($source_db_id, $channel_name = null, $total_delta = null, $yt_video_count = null) {
    global $db;
    $pre = $db->prefix();
    $table = $pre . 'youtube_import_sources';
    $source_db_id = (int) $source_db_id;
    if ($channel_name !== null) {
        $db->query("UPDATE `{$table}` SET channel_name = ? WHERE id = ?", [$channel_name, $source_db_id]);
    }
    if ($total_delta !== null && $total_delta != 0) {
        $db->query("UPDATE `{$table}` SET total_imported = total_imported + ? WHERE id = ?", [(int) $total_delta, $source_db_id]);
    }
    if ($yt_video_count !== null) {
        _nia_youtube_ensure_yt_video_count_column($pre, $table);
        $db->query("UPDATE `{$table}` SET yt_video_count = ? WHERE id = ?", [(int) $yt_video_count, $source_db_id]);
    }
}

/** Ensure yt_video_count column exists (safe to call repeatedly). */
function _nia_youtube_ensure_yt_video_count_column($pre, $table) {
    global $db;
    static $checked = false;
    if ($checked) return;
    try {
        $col = $db->fetch("SHOW COLUMNS FROM `{$table}` LIKE 'yt_video_count'");
        if (!$col) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN yt_video_count INT UNSIGNED DEFAULT NULL COMMENT 'Total videos on YouTube (from API)' AFTER total_imported");
        }
    } catch (Throwable $e) { /* ignore */ }
    $checked = true;
}

/** Refresh channel display data (channel_name, yt_video_count) from YouTube API for a source. Call after add or when displaying. */
function nia_youtube_refresh_source_display($source_db_id) {
    $src = nia_youtube_get_source($source_db_id);
    if (!$src || ($src->type ?? '') !== 'channel' || empty($src->value)) return;
    $stats = nia_youtube_channel_stats($src->value);
    if (!$stats) return;
    $title = !empty($stats['title']) ? $stats['title'] : null;
    $videoCount = isset($stats['videoCount']) ? $stats['videoCount'] : null;
    if ($title !== null || $videoCount !== null) {
        nia_youtube_update_source_meta($source_db_id, $title, null, $videoCount);
    }
}

/** Get source by id (for log page / admin). */
function nia_youtube_get_source($source_db_id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM `" . $pre . "youtube_import_sources` WHERE id = ?", [(int) $source_db_id]);
}

/** Get user's import sources. */
function nia_youtube_get_import_sources($user_id) {
    global $db;
    $pre = $db->prefix();
    $table = $pre . 'youtube_import_sources';
    try {
        return $db->fetchAll("SELECT * FROM `{$table}` WHERE user_id = ? ORDER BY type, value", [(int) $user_id]);
    } catch (Throwable $e) {
        return [];
    }
}

/** All import sources (admin). */
function nia_youtube_get_all_import_sources() {
    global $db;
    $pre = $db->prefix();
    $table = $pre . 'youtube_import_sources';
    try {
        return $db->fetchAll("SELECT * FROM `{$table}` ORDER BY id DESC");
    } catch (Throwable $e) {
        return [];
    }
}

/** Remove import source (user's own). */
function nia_youtube_remove_import_source($user_id, $id) {
    global $db;
    $pre = $db->prefix();
    $table = $pre . 'youtube_import_sources';
    $db->query("DELETE FROM `{$table}` WHERE id = ? AND user_id = ?", [(int) $id, (int) $user_id]);
}

/** Remove import source by id (admin: no user check). */
function nia_youtube_admin_remove_import_source($id) {
    global $db;
    $pre = $db->prefix();
    $table = $pre . 'youtube_import_sources';
    $table_log = $pre . 'youtube_import_log';
    $id = (int) $id;
    $db->query("DELETE FROM `{$table_log}` WHERE source_id = ?", [$id]);
    $db->query("DELETE FROM `{$table}` WHERE id = ?", [$id]);
}

/** Get channels with auto_import=1 for cron. */
function nia_youtube_auto_import_channels() {
    global $db;
    $pre = $db->prefix();
    $table = $pre . 'youtube_import_sources';
    try {
        return $db->fetchAll("SELECT * FROM `{$table}` WHERE auto_import = 1 ORDER BY last_imported_at ASC");
    } catch (Throwable $e) {
        return [];
    }
}

/** 
 * Process a specific source (import videos).
 * Returns number of new videos imported. For channels, processes page-by-page to avoid memory/time limits with large channels (e.g. 4000+ videos).
 */
function nia_youtube_process_source($source_db_id, $maxResults = 25) {
    @set_time_limit(0);
    @ini_set('memory_limit', '256M');
    global $db;
    $pre = $db->prefix();
    $table_sources = $pre . 'youtube_import_sources';
    $table_videos = $pre . 'videos';
    $table_log = $pre . 'youtube_import_log';
    
    $row = $db->fetch("SELECT * FROM {$table_sources} WHERE id = ?", [(int)$source_db_id]);
    if (!$row) return 0;
    
    $source_id = $row->value;
    $user_id = (int)$row->user_id;
    $type = $row->type;
    $imported = 0;
    $max_fetch = (int) $maxResults;
    $unlimited = ($max_fetch <= 0);
    if ($unlimited) $max_fetch = 50;
    $page_size = min(50, $max_fetch);
    if ($unlimited) $page_size = 50;
    
    $process_page = function ($page) use ($db, $table_videos, $table_log, $source_db_id, $user_id, &$imported) {
        $seen = [];
        foreach ($page as $v) {
            $vid = $v['id'] ?? '';
            if ($vid === '' || isset($seen[$vid])) continue;
            $seen[$vid] = true;
            $url = 'https://www.youtube.com/watch?v=' . $vid;
            $exists = $db->fetch("SELECT id FROM {$table_videos} WHERE source = 'youtube' AND (remote_url = ? OR remote_url LIKE ?)", [$url, '%' . $vid . '%']);
            if ($exists) continue;
            $title = $v['title'] ?? 'YouTube video ' . $vid;
            $thumb = $v['thumb'] ?? null;
            $embed_url = 'https://www.youtube.com/embed/' . $vid;
            $embed_code = '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>';
            $yt_published = isset($v['published_at']) ? $v['published_at'] : null;
            $yt_channel = isset($v['channel_title']) ? $v['channel_title'] : null;
            try {
                $db->query(
                    "INSERT INTO {$table_videos} (user_id, title, type, source, remote_url, embed_code, thumb, views, yt_published_at, yt_channel_name) VALUES (?, ?, 'video', 'youtube', ?, ?, ?, 0, ?, ?)",
                    [$user_id, $title, $url, $embed_code, $thumb, $yt_published, $yt_channel]
                );
            } catch (Throwable $e) {
                try {
                    $db->query(
                        "INSERT INTO {$table_videos} (user_id, title, type, source, remote_url, embed_code, thumb, views) VALUES (?, ?, 'video', 'youtube', ?, ?, ?, 0)",
                        [$user_id, $title, $url, $embed_code, $thumb]
                    );
                } catch (Throwable $e2) {
                    continue;
                }
            }
            $new_video_id = (int) $db->pdo()->lastInsertId();
            if ($new_video_id > 0) {
                $imported++;
                try {
                    $db->query("INSERT INTO `{$table_log}` (source_id, video_id, video_title) VALUES (?, ?, ?)", [(int)$source_db_id, $new_video_id, $title]);
                } catch (Throwable $e) {}
                if (function_exists('add_activity')) {
                    add_activity($user_id, 'shared', 'video', $new_video_id);
                }
            }
        }
    };
    
    if ($type === 'channel') {
        // Process one page at a time to avoid memory limit / timeout on channels with 4000+ videos
        $pageToken = null;
        $fetched = 0;
        do {
            $res = nia_youtube_channel_uploads_page($source_id, $page_size, $pageToken);
            $page = $res['items'] ?? [];
            $pageToken = $res['nextPageToken'] ?? null;
            if (!empty($page)) {
                $process_page($page);
                $fetched += count($page);
            }
            if (empty($page)) break;
            if (!$unlimited && $imported >= $max_fetch) break;
        } while ($pageToken !== null);
    } else {
        $res = nia_youtube_playlist_items($source_id, $page_size, null);
        $videos = $res['items'] ?? [];
        if (!empty($videos)) $process_page($videos);
    }
    
    // Update last sync; set total_imported from actual log count
    $db->query("UPDATE {$table_sources} SET last_imported_at = NOW() WHERE id = ?", [(int)$source_db_id]);
    $log_count = $db->fetch("SELECT COUNT(*) AS c FROM `{$table_log}` WHERE source_id = ?", [(int)$source_db_id]);
    $actual_total = $log_count ? (int) $log_count->c : 0;
    $db->query("UPDATE {$table_sources} SET total_imported = ? WHERE id = ?", [$actual_total, (int)$source_db_id]);
    if (($row->type ?? '') === 'channel' && function_exists('nia_youtube_refresh_source_display')) {
        nia_youtube_refresh_source_display($source_db_id);
    }
    return $imported;
}

/** Get import log for a source (newest first). */
function nia_youtube_get_import_log($source_db_id, $limit = 100) {
    global $db;
    $pre = $db->prefix();
    $table_log = $pre . 'youtube_import_log';
    try {
        return $db->fetchAll("SELECT * FROM `{$table_log}` WHERE source_id = ? ORDER BY created_at DESC LIMIT " . (int) $limit, [(int) $source_db_id]);
    } catch (Throwable $e) {
        return [];
    }
}
