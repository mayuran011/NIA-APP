<?php
/**
 * Public API endpoint(s) for external access.
 * GET /api/videos, /api/music, /api/images, /api/video/:id, /api/image/:id, /api/search?q=
 * Returns JSON.
 */
if (!defined('in_nia_app')) exit;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('Access-Control-Allow-Origin: *');

$nia_section = $GLOBALS['nia_route_section'] ?? '';
$parts = $nia_section !== '' ? explode('/', trim($nia_section, '/')) : [];
$resource = $parts[0] ?? '';
$id = isset($parts[1]) ? (int) $parts[1] : 0;

$limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$site_url = rtrim(SITE_URL, '/');

function api_video_item($v) {
    global $site_url;
    $thumb = $v->thumb ?? null;
    if ($thumb !== null && $thumb !== '' && strpos($thumb, 'http') !== 0) {
        $thumb = $site_url . '/' . ltrim($thumb, '/');
    }
    $channel = '';
    if (!empty($v->channel_name) || !empty($v->channel_username)) {
        $channel = trim($v->channel_name ?? '') ?: ($v->channel_username ?? '');
    }
    $out = [
        'id' => (int) $v->id,
        'title' => $v->title ?? '',
        'description' => $v->description ?? '',
        'type' => $v->type ?? 'video',
        'thumb' => $thumb,
        'views' => (int) ($v->views ?? 0),
        'likes' => (int) ($v->likes ?? 0),
        'url' => video_url($v->id, $v->title ?? ''),
        'created_at' => $v->created_at ?? null,
    ];
    if ($channel !== '') {
        $out['channel'] = $channel;
    }
    return $out;
}

function api_image_item($img) {
    global $site_url;
    $thumb = $img->thumb ?? $img->path ?? null;
    if ($thumb !== null && $thumb !== '' && strpos($thumb, 'http') !== 0) {
        $thumb = $site_url . '/' . ltrim($thumb, '/');
    }
    return [
        'id' => (int) $img->id,
        'title' => $img->title ?? '',
        'description' => $img->description ?? '',
        'thumb' => $thumb,
        'url' => function_exists('view_url') ? view_url($img->id, $img->title ?? '') : image_url($img->id, $img->title ?? ''),
        'created_at' => $img->created_at ?? null,
    ];
}

try {
    if ($resource === 'videos' || $resource === 'video') {
        if ($resource === 'video' && $id > 0) {
            $video = get_video($id);
            if (!$video) {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'not_found']);
                exit;
            }
            echo json_encode(['item' => api_video_item($video)]);
            exit;
        }
        $list = get_videos(['type' => 'video', 'limit' => $limit, 'offset' => $offset]);
        $out = array_map('api_video_item', $list);
        echo json_encode(['items' => $out, 'count' => count($out)]);
        exit;
    }

    if ($resource === 'music') {
        $list = get_videos(['type' => 'music', 'limit' => $limit, 'offset' => $offset]);
        $out = array_map('api_video_item', $list);
        echo json_encode(['items' => $out, 'count' => count($out)]);
        exit;
    }

    if ($resource === 'images' || $resource === 'image') {
        if ($resource === 'image' && $id > 0) {
            $img = get_image($id);
            if (!$img) {
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'not_found']);
                exit;
            }
            echo json_encode(['item' => api_image_item($img)]);
            exit;
        }
        $list = get_images(['limit' => $limit, 'offset' => $offset]);
        $out = array_map('api_image_item', $list);
        echo json_encode(['items' => $out, 'count' => count($out)]);
        exit;
    }

    if ($resource === 'search') {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
        if (!in_array($type, ['all', 'videos', 'music', 'pictures', 'channels', 'playlists'], true)) {
            $type = 'all';
        }
        if ($q === '') {
            echo json_encode(['items' => [], 'videos' => [], 'images' => [], 'channels' => [], 'playlists' => []]);
            exit;
        }
        $results = search_global($q, $type, 20, 0);
        $videos = array_map('api_video_item', $results['videos'] ?? []);
        $images = array_map('api_image_item', $results['images'] ?? []);
        $channels = [];
        foreach ($results['channels'] ?? [] as $c) {
            $channels[] = [
                'id' => (int) $c->id,
                'name' => $c->name ?? '',
                'username' => $c->username ?? '',
                'url' => profile_url($c->username ?? '', $c->id),
            ];
        }
        $playlists = [];
        foreach ($results['playlists'] ?? [] as $pl) {
            $playlists[] = [
                'id' => (int) $pl->id,
                'name' => $pl->name ?? '',
                'slug' => $pl->slug ?? '',
                'url' => playlist_url($pl->slug ?? '', $pl->id),
            ];
        }
        echo json_encode([
            'q' => $q,
            'videos' => $videos,
            'images' => $images,
            'channels' => $channels,
            'playlists' => $playlists,
        ]);
        exit;
    }

    header('HTTP/1.0 400 Bad Request');
    echo json_encode([
        'error' => 'invalid_resource',
        'message' => 'Use /api/videos, /api/music, /api/images, /api/video/:id, /api/image/:id, or /api/search?q=',
    ]);
} catch (Throwable $e) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['error' => 'server_error']);
}
