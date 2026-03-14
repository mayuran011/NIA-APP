<?php
/**
 * Upload and processing: mediafolder, tmp-folder; FFmpeg (ffmpeg-cmd, binpath) for transcoding/thumbnails.
 */

if (!defined('in_nia_app')) {
    exit;
}

/** Resolve media folder (option mediafolder or MEDIA_FOLDER). */
function media_folder() {
    $path = get_option('mediafolder', '');
    if ($path !== '' && $path !== null) return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return defined('MEDIA_FOLDER') ? rtrim(MEDIA_FOLDER, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : (ABSPATH . 'media' . DIRECTORY_SEPARATOR);
}

/** Resolve tmp folder (option tmp-folder or TMP_FOLDER). */
function tmp_folder() {
    $path = get_option('tmp-folder', '');
    if ($path !== '' && $path !== null) return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return defined('TMP_FOLDER') ? rtrim(TMP_FOLDER, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : (ABSPATH . 'tmp' . DIRECTORY_SEPARATOR);
}

/** FFmpeg executable path (option binpath + ffmpeg-cmd, or ffmpeg-cmd alone). */
function ffmpeg_path() {
    $cmd = get_option('ffmpeg-cmd', 'ffmpeg');
    $bin = get_option('binpath', '');
    if ($bin !== '' && $bin !== null) {
        $bin = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $bin), DIRECTORY_SEPARATOR);
        return $bin . DIRECTORY_SEPARATOR . $cmd;
    }
    return $cmd;
}

/**
 * Generate thumbnail from video file using FFmpeg. Optional: hide MP4 (store in media, serve only via stream.php).
 * @param string $video_path Full path to video file
 * @param string $thumb_path Full path for output thumbnail (e.g. .jpg)
 * @param int    $at_seconds Seek position in seconds (default 1)
 * @return bool Success
 */
function ffmpeg_thumbnail($video_path, $thumb_path, $at_seconds = 1) {
    if (!is_file($video_path)) return false;
    $ff = ffmpeg_path();
    $at = (int) $at_seconds;
    $out_dir = dirname($thumb_path);
    if (!is_dir($out_dir)) @mkdir($out_dir, 0755, true);
    $cmd = sprintf(
        '%s -ss %d -i %s -vframes 1 -f image2 -y %s 2>&1',
        escapeshellarg($ff),
        $at,
        escapeshellarg($video_path),
        escapeshellarg($thumb_path)
    );
    exec($cmd, $out, $ret);
    return $ret === 0 && is_file($thumb_path);
}

/**
 * Get video duration in seconds via FFmpeg.
 * @return int 0 on failure
 */
function ffmpeg_duration($video_path) {
    if (!is_file($video_path)) return 0;
    $ff = ffmpeg_path();
    $cmd = sprintf('%s -i %s 2>&1', escapeshellarg($ff), escapeshellarg($video_path));
    $out = [];
    exec($cmd, $out, $ret);
    $str = implode(' ', $out);
    if (preg_match('/Duration:\s*(\d+):(\d+):(\d+)/', $str, $m)) {
        return (int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3];
    }
    return 0;
}
