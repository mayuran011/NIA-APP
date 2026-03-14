<?php
/**
 * /videos and /videos/:section — video grid only (browse, featured, most-viewed, top-rated).
 * Playback is on /watch only.
 */
if (!defined('in_nia_app')) exit;
$section = trim($GLOBALS['nia_route_section'] ?? '');
if ($section === '') $section = 'browse';
if (!in_array($section, ['browse', 'featured', 'most-viewed', 'top-rated'], true)) {
    $section = 'browse';
}
$page_title = 'Videos';
$items = get_videos(['type' => 'video', 'section' => $section, 'limit' => 24]);
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4" data-pull-to-refresh>
    <h1 class="nia-title mb-4">Videos</h1>
    <div class="nia-chips mb-3">
        <a class="nia-chip <?php echo $section === 'browse' ? 'active' : ''; ?>" href="<?php echo url('videos'); ?>">Browse</a>
        <a class="nia-chip <?php echo $section === 'featured' ? 'active' : ''; ?>" href="<?php echo url('videos/featured'); ?>">Featured</a>
        <a class="nia-chip <?php echo $section === 'most-viewed' ? 'active' : ''; ?>" href="<?php echo url('videos/most-viewed'); ?>">Most viewed</a>
        <a class="nia-chip <?php echo $section === 'top-rated' ? 'active' : ''; ?>" href="<?php echo url('videos/top-rated'); ?>">Top rated</a>
    </div>
    <?php $videos_grid_size = get_option('videos_grid_size', 'medium'); $videos_grid_class = in_array($videos_grid_size, ['small', 'medium', 'large'], true) ? $videos_grid_size : 'medium'; ?>
    <div class="nia-video-grid nia-grid--<?php echo _e($videos_grid_class); ?>">
        <?php
        foreach ($items as $item) {
            $link = watch_url($item->id);
            $thumb = !empty($item->thumb) ? $item->thumb : '';
            if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
            $duration = function_exists('nia_duration') ? nia_duration($item->duration ?? 0) : '';
            $timeAgo = function_exists('nia_time_ago') ? nia_time_ago($item->created_at ?? null) : '';
            $views = (int) ($item->views ?? 0);
            $viewsStr = $views >= 1000000 ? round($views / 1000000, 1) . 'M' : ($views >= 1000 ? round($views / 1000, 1) . 'K' : $views) . ' views';
            $channel = isset($item->user_id) ? get_user($item->user_id) : null;
            $chanName = $channel ? ($channel->username ?? $channel->name ?? '') : '';
            $avatar = $channel && !empty($channel->avatar) ? $channel->avatar : '';
            if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = rtrim(SITE_URL, '/') . '/' . ltrim($avatar, '/');
            $initial = $channel && !empty($channel->username) ? strtoupper(substr($channel->username, 0, 1)) : '?';
            ?>
            <a href="<?php echo _e($link); ?>" class="nia-video-card">
                <div class="nia-video-thumb-wrap">
                    <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                    <?php if ($duration !== '') { ?><span class="nia-video-duration"><?php echo _e($duration); ?></span><?php } ?>
                </div>
                <div class="nia-video-info">
                    <?php if ($avatar) { ?><img class="nia-video-avatar" src="<?php echo _e($avatar); ?>" alt=""><?php } else { ?><span class="nia-video-avatar-initial"><?php echo _e($initial); ?></span><?php } ?>
                    <div class="nia-video-meta">
                        <div class="nia-video-title"><?php echo _e($item->title ?? ''); ?></div>
                        <?php if (function_exists('nia_yt_meta_render')) { $yt_meta = nia_yt_meta_render($item, 'cards'); if ($yt_meta !== '') { echo $yt_meta; } } ?>
                        <div class="nia-video-channel-stats"><?php echo _e($chanName); ?><?php if ($chanName !== '' && ($viewsStr !== '' || $timeAgo !== '')) { ?> · <?php } ?><?php if ($viewsStr !== '') { echo _e($viewsStr); } ?><?php if ($viewsStr !== '' && $timeAgo !== '') { ?> · <?php } ?><?php if ($timeAgo !== '') { ?>Added <?php echo _e($timeAgo); ?><?php } ?></div>
                    </div>
                </div>
            </a>
        <?php } ?>
    </div>
    <?php if (empty($items)) { ?>
    <p class="text-muted">No videos yet.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
