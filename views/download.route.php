<?php
/**
 * Download video/audio: local → redirect to stream.php; YouTube → yt-dlp (if enabled).
 * Requires can_download_media(); YouTube requires option youtube_download_enabled and yt-dlp on server.
 */
if (!defined('in_nia_app')) exit;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$format = isset($_GET['format']) ? trim($_GET['format']) : '';

if ($id <= 0) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid id');
}

if (!is_logged() || !function_exists('can_download_media') || !can_download_media()) {
    header('HTTP/1.0 403 Forbidden');
    exit('Download not allowed.');
}

$video = get_video($id);
if (!$video) {
    header('HTTP/1.0 404 Not Found');
    exit('Video not found.');
}

$source = $video->source ?? 'local';

if ($source === 'local') {
    $url = rtrim(SITE_URL, '/') . '/stream.php?id=' . $id . '&download=1';
    header('Location: ' . $url, true, 302);
    exit;
}

if ($source !== 'youtube' || (int) get_option('youtube_download_enabled', '0') !== 1) {
    header('HTTP/1.0 403 Forbidden');
    exit('YouTube download is disabled.');
}

$remote = $video->remote_url ?? '';
if (!preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]{11})#', $remote, $m)) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid YouTube URL.');
}
$yt_id = $m[1];

$yt_dlp = get_option('yt_dlp_path', 'yt-dlp');
if ($yt_dlp === '') {
    header('HTTP/1.0 503 Service Unavailable');
    exit('YouTube download not configured (set yt_dlp_path in Admin → Download).');
}

$format_map = [
    'mp4_1080' => 'bestvideo[height<=1080]+bestaudio/best[height<=1080]',
    'mp4_720'  => 'bestvideo[height<=720]+bestaudio/best[height<=720]',
    'mp3_320'  => 'bestaudio',
];
$is_audio = ($format === 'mp3' || $format === 'mp3_320');
$fmt = $format_map[$format] ?? $format_map['mp4_720'];
$ext = $is_audio ? 'mp3' : 'mp4';
$safe_title = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $video->title ?? '');
$safe_title = trim($safe_title) !== '' ? substr($safe_title, 0, 180) : 'download_' . $id;
$filename = $safe_title . '.' . $ext;

$yt_url = 'https://www.youtube.com/watch?v=' . $yt_id;

if ($is_audio) {
    $args = ['-x', '--audio-format', 'mp3', '--postprocessor-args', 'ffmpeg:-b:a 320k', '-o', '-', $yt_url];
} else {
    $args = ['-f', $fmt, '-o', '-', $yt_url];
}
$cmd_array = array_merge([$yt_dlp], $args);

$proc = @proc_open(
    $cmd_array,
    [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
    $pipes,
    null,
    null,
    ['bypass_shell' => true]
);
if (!is_resource($proc)) {
    header('HTTP/1.0 503 Service Unavailable');
    header('Content-Type: text/plain');
    exit('Download failed: could not run yt-dlp. Install yt-dlp and FFmpeg on the server.');
}
fclose($pipes[0]);
$out = $pipes[1];
$err = $pipes[2];
stream_set_blocking($err, false);
stream_set_blocking($out, true);

$first = fread($out, 65536);
$first_len = strlen($first);
$waited = 0;
while ($first_len === 0 && $waited < 15000) {
    $status = proc_get_status($proc);
    if ($status['running'] === false) break;
    usleep(500000);
    $waited += 500000;
    $first = fread($out, 65536);
    $first_len = strlen($first);
}
if ($first_len === 0) {
    $stderr = stream_get_contents($err);
    fclose($out);
    fclose($err);
    proc_close($proc);
    header('HTTP/1.0 503 Service Unavailable');
    header('Content-Type: text/plain; charset=utf-8');
    $msg = 'yt-dlp produced no output. ';
    if (trim($stderr) !== '') $msg .= 'Error: ' . trim($stderr);
    else $msg .= 'Install yt-dlp and FFmpeg on the server (and set yt_dlp_path in Admin → Download if needed).';
    exit($msg);
}

header('Content-Type: ' . ($is_audio ? 'audio/mpeg' : 'video/mp4'));
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache');

echo $first;
if (ob_get_level()) ob_flush();
flush();

while (!feof($out)) {
    $chunk = fread($out, 65536);
    if ($chunk !== false && $chunk !== '') {
        echo $chunk;
        if (ob_get_level()) ob_flush();
        flush();
    }
}
fclose($out);
fclose($err);
proc_close($proc);
