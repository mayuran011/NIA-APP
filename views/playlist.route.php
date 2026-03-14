<?php
/**
 * /playlist/:name/:id — single playlist page.
 */
if (!defined('in_nia_app')) exit;
$rest = trim($GLOBALS['nia_route_section'] ?? '');
$parts = array_values(array_filter(explode('/', $rest)));
$playlist_id = isset($parts[1]) ? (int) $parts[1] : (isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : 0);
$playlist = $playlist_id > 0 ? get_playlist($playlist_id) : null;
$page_title = $playlist ? _e($playlist->name ?? 'Playlist') : 'Playlist';
$items = [];
if ($playlist) {
    $items = get_playlist_items($playlist->id, $playlist->type ?? 'video', 100);
}
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4"><?php echo $playlist ? _e($playlist->name) : 'Playlist'; ?></h1>
    <?php if ($playlist) { ?>
    <div class="nia-video-grid">
        <?php
        foreach ($items as $row) {
            $mid = (int) ($row->media_id ?? 0);
            if ($mid <= 0) continue;
            $media_type = $playlist->type ?? 'video';
            if ($media_type === 'video' || $media_type === 'music') {
                $item = get_video($mid);
                if (!$item) continue;
                $link = function_exists('media_play_url') ? media_play_url($item->id, $item->type ?? 'video', $item->title ?? '') : watch_url($item->id);
                $thumb = !empty($item->thumb) ? $item->thumb : '';
                if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
                $duration = function_exists('nia_duration') ? nia_duration($item->duration ?? 0) : '';
                ?>
                <a href="<?php echo _e($link); ?>" class="nia-video-card">
                    <div class="nia-video-thumb-wrap">
                        <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                        <?php if ($duration !== '') { ?><span class="nia-video-duration"><?php echo _e($duration); ?></span><?php } ?>
                    </div>
                    <div class="nia-video-info">
                        <div class="nia-video-meta">
                            <div class="nia-video-title"><?php echo _e($item->title ?? ''); ?></div>
                            <?php if (function_exists('nia_yt_meta_render')) { $yt_meta = nia_yt_meta_render($item, 'cards'); if ($yt_meta !== '') { echo $yt_meta; } } ?>
                        </div>
                    </div>
                </a>
            <?php } else {
                $item = get_image($mid);
                if (!$item) continue;
                $link = function_exists('view_url') ? view_url($item->id, $item->title ?? '') : (function_exists('image_url') ? image_url($item->id, $item->title ?? '') : url('image/' . $item->id));
                $thumb = !empty($item->thumb) ? $item->thumb : '';
                if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
                ?>
                <a href="<?php echo _e($link); ?>" class="nia-video-card">
                    <div class="nia-video-thumb-wrap">
                        <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                    </div>
                    <div class="nia-video-info">
                        <div class="nia-video-meta">
                            <div class="nia-video-title"><?php echo _e($item->title ?? ''); ?></div>
                        </div>
                    </div>
                </a>
            <?php }
        } ?>
    </div>
    <?php if (empty($items)) { ?><p class="text-muted">This playlist is empty.</p><?php } ?>
    <?php } else { ?>
    <p class="text-muted">Playlist not found.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
