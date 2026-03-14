<?php
/**
 * Optional hidden MP4 (and audio) URLs; quality switching for multi-quality uploads.
 * Usage: stream.php?id=VIDEO_ID&quality=720|480|360|default
 * Serves file from media folder without exposing direct path; checks access (private/premium).
 */

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$quality = isset($_GET['quality']) ? trim($_GET['quality']) : 'default';

if ($id <= 0) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$video = get_video($id);
if (!$video) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$source = $video->source ?? 'local';
if ($source !== 'local') {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

if (!empty($_GET['download'])) {
    if (!function_exists('is_logged') || !is_logged() || !function_exists('can_download_media') || !can_download_media()) {
        header('HTTP/1.0 403 Forbidden');
        exit('Download not allowed.');
    }
}

$base_path = defined('MEDIA_FOLDER') ? MEDIA_FOLDER : (ABSPATH . 'media');
$video_dir = $base_path . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . $id;
$file_path = null;

if ($quality !== '' && $quality !== 'default') {
    $quality_file = $video_dir . DIRECTORY_SEPARATOR . $quality . '.mp4';
    if (is_file($quality_file)) {
        $file_path = $quality_file;
    }
}
if ($file_path === null) {
    $default_file = $video_dir . DIRECTORY_SEPARATOR . 'default.mp4';
    $legacy_file = $base_path . DIRECTORY_SEPARATOR . $id . '.mp4';
    if (is_file($default_file)) {
        $file_path = $default_file;
    } elseif (!empty($video->file_path) && is_file($base_path . DIRECTORY_SEPARATOR . $video->file_path)) {
        $file_path = $base_path . DIRECTORY_SEPARATOR . $video->file_path;
    } elseif (is_file($legacy_file)) {
        $file_path = $legacy_file;
    }
}

if ($file_path === null || !is_readable($file_path)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$mimes = [
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'mp3'  => 'audio/mpeg',
    'm4a'  => 'audio/mp4',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

$size = filesize($file_path);
$range = isset($_SERVER['HTTP_RANGE']) ? trim($_SERVER['HTTP_RANGE']) : '';

$download_filename = null;
if (!empty($_GET['download'])) {
    $safe_title = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $video->title ?? 'video');
    $safe_title = trim($safe_title) !== '' ? substr($safe_title, 0, 180) : 'download';
    $download_filename = $safe_title . '.' . $ext;
}
function stream_content_disposition($filename) {
    if ($filename === null || $filename === '') return;
    header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
}

if ($range !== '') {
    if (preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
        $start = (int) $m[1];
        $end = $m[2] !== '' ? (int) $m[2] : $size - 1;
        $end = min($end, $size - 1);
        $length = $end - $start + 1;
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . (string) $length);
        header('Content-Type: ' . $mime);
        if ($download_filename !== null) stream_content_disposition($download_filename);
        $fp = fopen($file_path, 'rb');
        fseek($fp, $start);
        echo fread($fp, $length);
        fclose($fp);
        exit;
    }
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('Accept-Ranges: bytes');
if ($download_filename !== null) stream_content_disposition($download_filename);
readfile($file_path);
