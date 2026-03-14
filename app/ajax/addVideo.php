<?php
/**
 * Add video/music by remote URL or embed code. Provider detection (YouTube, Vimeo, etc.); fetch metadata.
 */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged()) {
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

$url = isset($_POST['url']) ? trim($_POST['url']) : (isset($_POST['embed_code']) ? trim($_POST['embed_code']) : '');
$type = isset($_POST['type']) ? trim($_POST['type']) : 'video';
if (!in_array($type, ['video', 'music'], true)) $type = 'video';

if ($url === '') {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

$detect = NiaProviders::detect($url);
$source = $detect['source'];
$embed_url = $detect['embed_url'] ?? null;
$remote_url = $detect['url'] ?? $url;

$title = '';
$thumb = null;
$yt_published = null;
$yt_channel = null;
$duration = 0;
if (in_array($source, ['youtube', 'vimeo', 'dailymotion', 'twitch', 'facebook', 'vine'], true) && $remote_url && preg_match('#^https?://#', $remote_url)) {
    if ($source === 'youtube' && !empty($detect['id']) && function_exists('nia_youtube_video_details')) {
        $yt_v = nia_youtube_video_details($detect['id']);
        if ($yt_v) {
            $title = $yt_v['title'] ?? '';
            $thumb = $yt_v['thumb'] ?? null;
            $yt_published = $yt_v['published_at'] ?? null;
            $yt_channel = $yt_v['channel_title'] ?? null;
            $duration = (int) ($yt_v['duration'] ?? 0);
        }
    }
    if ($title === '') {
        $meta = NiaProviders::fetchMetadata($remote_url);
        $title = $meta['title'] ?? '';
        $thumb = $meta['thumbnail_url'] ?? $thumb;
    }
}
if ($title === '') $title = NiaProviders::getProviderName($source) . ' video';

global $db;
$pre = $db->prefix();
$slug = preg_replace('/[^a-z0-9\-]/i', '-', $title) ?: 'video-' . time();
try {
    $db->query(
        "INSERT INTO {$pre}videos (user_id, title, type, source, remote_url, embed_code, thumb, duration, yt_published_at, yt_channel_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            current_user_id(),
            $title,
            $type,
            $source,
            $remote_url,
            $embed_url ? '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>' : null,
            $thumb,
            $duration,
            $yt_published,
            $yt_channel,
        ]
    );
} catch (Throwable $e) {
    $db->query(
        "INSERT INTO {$pre}videos (user_id, title, type, source, remote_url, embed_code, thumb, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [
            current_user_id(),
            $title,
            $type,
            $source,
            $remote_url,
            $embed_url ? '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>' : null,
            $thumb,
            $duration,
        ]
    );
}
$id = (int) $db->pdo()->lastInsertId();

if (function_exists('add_activity')) {
    add_activity(current_user_id(), 'shared', $type, $id);
}

echo json_encode(['ok' => true, 'id' => $id, 'url' => video_url($id, $slug), 'title' => $title]);
