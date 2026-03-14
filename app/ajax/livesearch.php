<?php
/**
 * Live search from first character: suggestions for videos, music, playlists, channels.
 */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') {
    echo json_encode(['query' => '', 'videos' => [], 'music' => [], 'playlists' => [], 'channels' => []]);
    exit;
}

$suggestions = search_suggestions($q, 5);

$videos = [];
foreach ($suggestions['videos'] as $v) {
    $videos[] = ['id' => (int) $v->id, 'title' => $v->title ?? '', 'thumb' => $v->thumb ?? null, 'url' => video_url($v->id, $v->title ?? '')];
}
$music = [];
foreach ($suggestions['music'] as $m) {
    $music[] = ['id' => (int) $m->id, 'title' => $m->title ?? '', 'thumb' => $m->thumb ?? null, 'url' => video_url($m->id, $m->title ?? '')];
}
$playlists = [];
foreach ($suggestions['playlists'] as $p) {
    $playlists[] = ['id' => (int) $p->id, 'name' => $p->name ?? '', 'slug' => $p->slug ?? '', 'url' => playlist_url($p->slug ?? '', $p->id)];
}
$channels = [];
foreach ($suggestions['channels'] as $c) {
    $channels[] = ['id' => (int) $c->id, 'name' => $c->name ?? '', 'username' => $c->username ?? '', 'avatar' => $c->avatar ?? null, 'url' => profile_url($c->username ?? '', $c->id)];
}

echo json_encode([
    'query' => $q,
    'videos' => $videos,
    'music' => $music,
    'playlists' => $playlists,
    'channels' => $channels,
]);
