<?php
/**
 * Streaming: hidden MP4 URLs via stream.php; quality switching for multi-quality uploads.
 */

if (!defined('in_nia_app')) {
    exit;
}

/**
 * URL to stream a video/music file (proxied via stream.php so direct path is hidden).
 *
 * @param int    $id      Video id
 * @param string $quality Optional quality key (e.g. 720, 480, 360 or 'default')
 * @return string
 */
function stream_url($id, $quality = '') {
    $base = url('stream.php') . '?id=' . (int) $id;
    if ($quality !== '' && $quality !== null) {
        $base .= '&quality=' . rawurlencode((string) $quality);
    }
    return $base;
}
