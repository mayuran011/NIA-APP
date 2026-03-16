<?php
/**
 * Global helpers: URL, escape, redirect.
 * URL builders use SEO options when set (video-seo-url, profile-seo-url, etc.).
 */

if (!defined('in_nia_app')) {
    exit;
}

function _e($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function url($path = '') {
    $path = ltrim((string) $path, '/');
    return rtrim(SITE_URL, '/') . ($path !== '' ? '/' . $path : '');
}

/** Video/watch URL: all playback is on /watch. URL uses ID only (no title in path). Filter: video_url. */
function video_url($id, $name = '') {
    $out = watch_url($id);
    if (function_exists('apply_filters')) {
        $out = apply_filters('video_url', $out, $id, $name);
    }
    return $out;
}

/** Watch page URL: /watch/:id (ID only, no title in link). */
function watch_url($id, $name = '') {
    return url('watch/' . $id);
}

/** Listen page URL: /listen/:id (music playback). */
function listen_url($id, $name = '') {
    return url('listen/' . $id);
}

/** View page URL: /view/:id (single image view). */
function view_url($id, $name = '') {
    return url('view/' . (int) $id);
}

/** Play URL for video/music: watch for video, listen for music. */
function media_play_url($id, $type, $title = '') {
    return (isset($type) && $type === 'music') ? listen_url($id) : watch_url($id, $title);
}

/** Base path for image single page. Uses image-seo-url option if set. Filter: image_url. */
function image_url($id, $name = '') {
    $slug = get_option('image-seo-url');
    $base = ($slug !== '' && $slug !== null) ? $slug : 'image';
    $name = $name !== '' ? '/' . preg_replace('/[^a-z0-9\-]/i', '-', trim($name)) : '';
    $out = url($base . '/' . (int) $id . $name);
    if (function_exists('apply_filters')) {
        $out = apply_filters('image_url', $out, $id, $name);
    }
    return $out;
}

/** User profile (channel). Uses profile-seo-url option if set. Format: /profile/:name/:id/ */
function profile_url($name, $id) {
    $slug = get_option('profile-seo-url');
    $base = ($slug !== '' && $slug !== null) ? $slug : 'profile';
    $name = preg_replace('/[^a-z0-9\-_]/i', '-', trim($name));
    return url($base . '/' . $name . '/' . (int) $id . '/');
}

/** Static page. Uses page-seo-url option if set. Format: /read/:name/:id */
function page_url($name, $id) {
    $slug = get_option('page-seo-url');
    $base = ($slug !== '' && $slug !== null) ? $slug : 'read';
    $name = preg_replace('/[^a-z0-9\-]/i', '-', trim($name));
    return url($base . '/' . $name . '/' . (int) $id);
}

/** Blog article. Uses article-seo-url option if set. Format: /read/:name/:id */
function article_url($name, $id) {
    $slug = get_option('article-seo-url');
    $base = ($slug !== '' && $slug !== null) ? $slug : 'read';
    $name = preg_replace('/[^a-z0-9\-]/i', '-', trim($name));
    return url($base . '/' . $name . '/' . (int) $id);
}

/** Playlist. Format: /playlist/:name/:id/ */
function playlist_url($name, $id) {
    $name = preg_replace('/[^a-z0-9\-_]/i', '-', trim($name));
    return url('playlist/' . $name . '/' . (int) $id . '/');
}

/** Conversation (messaging). Format: /conversation/:id */
function conversation_url($id) {
    return url('conversation/' . (int) $id);
}

function redirect($url, $code = 302) {
    if (!headers_sent()) {
        header('Location: ' . $url, true, $code);
        exit;
    }
}

function is_logged() {
    return !empty($_SESSION['uid']);
}

function current_user_id() {
    return isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
}

/** Format duration in seconds as M:SS or H:MM:SS for display. */
function nia_duration($seconds) {
    $s = (int) $seconds;
    if ($s < 60) return '0:' . str_pad((string) $s, 2, '0', STR_PAD_LEFT);
    $m = (int) floor($s / 60);
    $s = $s % 60;
    if ($m < 60) return $m . ':' . str_pad((string) $s, 2, '0', STR_PAD_LEFT);
    $h = (int) floor($m / 60);
    $m = $m % 60;
    return $h . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $s, 2, '0', STR_PAD_LEFT);
}

/** Relative time for video "time ago" (e.g. "2 hours ago"). */
function nia_time_ago($datetime) {
    if (empty($datetime)) return '';
    $t = is_numeric($datetime) ? (int) $datetime : strtotime($datetime);
    if ($t <= 0) return '';
    $diff = time() - $t;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) === 1 ? '' : 's') . ' ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' day' . (floor($diff / 86400) === 1 ? '' : 's') . ' ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' month' . (floor($diff / 2592000) === 1 ? '' : 's') . ' ago';
    return floor($diff / 31536000) . ' year' . (floor($diff / 31536000) === 1 ? '' : 's') . ' ago';
}

/**
 * YouTube meta display config (Channel / Upload under title). Controlled from Admin → YouTube.
 * @return array{enabled:bool, show_channel:bool, show_upload:bool, label_channel:string, label_upload:string, font_size:string, color:string, style:string, show_watch:bool, show_cards:bool, show_home:bool}
 */
function nia_yt_meta_display_config() {
    static $config = null;
    if ($config !== null) return $config;
    $config = [
        'enabled'       => get_option('yt_meta_enabled', '1') === '1',
        'show_channel'  => get_option('yt_meta_channel_enabled', '1') === '1',
        'show_upload'   => get_option('yt_meta_upload_enabled', '1') === '1',
        'label_channel' => get_option('yt_meta_label_channel', 'Channel'),
        'label_upload'  => get_option('yt_meta_label_upload', 'Upload'),
        'font_size'     => get_option('yt_meta_font_size', '0.5'),
        'color'         => trim((string) get_option('yt_meta_color', '')),
        'style'         => get_option('yt_meta_style', 'uppercase'),
        'show_watch'    => get_option('yt_meta_show_watch', '1') === '1',
        'show_cards'    => get_option('yt_meta_show_cards', '1') === '1',
        'show_home'     => get_option('yt_meta_show_home', '1') === '1',
    ];
    return $config;
}

/** Inline style for the YouTube meta block (very small text, not title color). */
function nia_yt_meta_block_style() {
    $c = nia_yt_meta_display_config();
    $parts = [];
    $parts[] = 'font-size:' . (float) $c['font_size'] . 'rem';
    if ($c['color'] !== '') $parts[] = 'color:' . $c['color'];
    if ($c['style'] === 'uppercase') $parts[] = 'text-transform:uppercase';
    if ($c['style'] === 'italic') $parts[] = 'font-style:italic';
    return implode(';', $parts);
}

/** Inline style for the label part (Channel / Upload). */
function nia_yt_meta_label_style() {
    $c = nia_yt_meta_display_config();
    $size = max(0.35, (float) $c['font_size'] - 0.05);
    $parts = ['font-size:' . $size . 'rem', 'font-weight:700'];
    if ($c['color'] !== '') $parts[] = 'color:' . $c['color'];
    if ($c['style'] === 'uppercase') $parts[] = 'text-transform:uppercase';
    if ($c['style'] === 'italic') $parts[] = 'font-style:italic';
    return implode(';', $parts);
}

/** Whether to show YouTube meta on watch page. */
function nia_yt_meta_show_on_watch() {
    $c = nia_yt_meta_display_config();
    return $c['enabled'] && $c['show_watch'];
}

/** Whether to show YouTube meta on video cards/grid (and home). */
function nia_yt_meta_show_on_cards() {
    $c = nia_yt_meta_display_config();
    return $c['enabled'] && $c['show_cards'];
}

/** Whether to show YouTube meta on home page. */
function nia_yt_meta_show_on_home() {
    $c = nia_yt_meta_display_config();
    return $c['enabled'] && $c['show_home'];
}

/**
 * Render YouTube meta line HTML for a video item. Context: 'watch' | 'cards' | 'home'.
 * $item must have source, yt_channel_name, yt_published_at (optional).
 * @param object $item Video object
 * @param string $context 'watch', 'cards', or 'home'
 * @return string HTML or empty
 */
function nia_yt_meta_render($item, $context = 'cards') {
    if (!function_exists('nia_yt_meta_display_config')) return '';
    $c = nia_yt_meta_display_config();
    if (!$c['enabled']) return '';
    if ($context === 'watch' && !$c['show_watch']) return '';
    if ($context === 'cards' && !$c['show_cards']) return '';
    if ($context === 'home' && !$c['show_home']) return '';
    if (empty($item->source) || $item->source !== 'youtube') return '';
    $show_ch = $c['show_channel'] && !empty($item->yt_channel_name);
    $show_up = $c['show_upload'] && !empty($item->yt_published_at);
    if (!$show_ch && !$show_up) return '';
    $bs = nia_yt_meta_block_style();
    $ls = nia_yt_meta_label_style();
    $out = '<div class="nia-video-yt-meta"' . ($bs !== '' ? ' style="' . _e($bs) . '"' : '') . '>';
    if ($show_ch) $out .= '<span class="nia-yt-label"' . ($ls !== '' ? ' style="' . _e($ls) . '"' : '') . '>' . _e($c['label_channel']) . '</span> ' . _e($item->yt_channel_name);
    if ($show_ch && $show_up) $out .= ' · ';
    if ($show_up) $out .= '<span class="nia-yt-label"' . ($ls !== '' ? ' style="' . _e($ls) . '"' : '') . '>' . _e($c['label_upload']) . '</span> ' . (function_exists('nia_time_ago') ? nia_time_ago($item->yt_published_at) : date('M j, Y', strtotime($item->yt_published_at)));
    $out .= '</div>';
    return $out;
}

/**
 * Render Bootstrap 5 pagination with icons. Responsive: fewer numbers on small screens.
 * @param int    $current_page  Current page (1-based)
 * @param int    $total_pages   Total pages
 * @param string $base_url      Base URL (e.g. url('videos') or url('videos/featured'))
 * @param string $query_param   Query param for page (default 'page')
 * @param int    $max_visible   Max page numbers to show around current (default 3)
 */
function nia_pagination($current_page, $total_pages, $base_url, $query_param = 'page', $max_visible = 3) {
    if ($total_pages <= 1) return;
    $current_page = max(1, min($current_page, $total_pages));
    $sep = strpos($base_url, '?') !== false ? '&' : '?';
    $href = function($p) use ($base_url, $sep, $query_param) {
        return $base_url . $sep . $query_param . '=' . $p;
    };
    $aria = function($p) { return 'Page ' . $p; };
    echo '<nav class="nia-pagination-wrap mt-4 mb-3" aria-label="Pagination">';
    echo '<ul class="pagination pagination-lg justify-content-center flex-wrap mb-0">';

    echo '<li class="page-item' . ($current_page <= 1 ? ' disabled' : '') . '">';
    echo '<a class="page-link d-inline-flex align-items-center gap-1" href="' . ($current_page > 1 ? _e($href(1)) : '#') . '" aria-label="First"' . ($current_page <= 1 ? ' tabindex="-1"' : '') . '><span class="material-icons" style="font-size:1.2rem;">first_page</span><span class="d-none d-sm-inline ms-1">First</span></a></li>';

    echo '<li class="page-item' . ($current_page <= 1 ? ' disabled' : '') . '">';
    echo '<a class="page-link d-inline-flex align-items-center" href="' . ($current_page > 1 ? _e($href($current_page - 1)) : '#') . '" aria-label="Previous"' . ($current_page <= 1 ? ' tabindex="-1"' : '') . '><span class="material-icons" style="font-size:1.2rem;">chevron_left</span></a></li>';

    $from = max(1, $current_page - $max_visible);
    $to = min($total_pages, $current_page + $max_visible);
    if ($from > 2) {
        echo '<li class="page-item"><a class="page-link" href="' . _e($href(1)) . '" aria-label="' . $aria(1) . '">1</a></li>';
        if ($from > 3) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for ($i = $from; $i <= $to; $i++) {
        $active = $i === $current_page;
        echo '<li class="page-item' . ($active ? ' active' : '') . '">';
        echo '<a class="page-link" href="' . ($active ? '#' : _e($href($i))) . '" aria-label="' . $aria($i) . '"' . ($active ? ' aria-current="page"' : '') . '>' . $i . '</a></li>';
    }
    if ($to < $total_pages - 1) {
        if ($to < $total_pages - 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        echo '<li class="page-item"><a class="page-link" href="' . _e($href($total_pages)) . '" aria-label="' . $aria($total_pages) . '">' . $total_pages . '</a></li>';
    }

    echo '<li class="page-item' . ($current_page >= $total_pages ? ' disabled' : '') . '">';
    echo '<a class="page-link d-inline-flex align-items-center" href="' . ($current_page < $total_pages ? _e($href($current_page + 1)) : '#') . '" aria-label="Next"' . ($current_page >= $total_pages ? ' tabindex="-1"' : '') . '><span class="material-icons" style="font-size:1.2rem;">chevron_right</span></a></li>';

    echo '<li class="page-item' . ($current_page >= $total_pages ? ' disabled' : '') . '">';
    echo '<a class="page-link d-inline-flex align-items-center gap-1" href="' . ($current_page < $total_pages ? _e($href($total_pages)) : '#') . '" aria-label="Last"' . ($current_page >= $total_pages ? ' tabindex="-1"' : '') . '><span class="d-none d-sm-inline me-1">Last</span><span class="material-icons" style="font-size:1.2rem;">last_page</span></a></li>';

    echo '</ul></nav>';
}
