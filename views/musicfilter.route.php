<?php
/**
 * /musicfilter/:slug or ?category=id — music by channel/category.
 */
if (!defined('in_nia_app')) exit;
$rest = trim($GLOBALS['nia_route_section'] ?? '');
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$page_title = 'Music';
$items = get_videos(['type' => 'music', 'category_id' => $category_id > 0 ? $category_id : null, 'section' => 'browse', 'limit' => 24]);
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Music</h1>
    <div class="nia-video-grid">
        <?php foreach ($items as $item) {
            $link = listen_url($item->id);
            $thumb = !empty($item->thumb) ? $item->thumb : '';
            if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
            $duration = function_exists('nia_duration') ? nia_duration($item->duration ?? 0) : '';
            $channel = isset($item->user_id) ? get_user($item->user_id) : null;
            $chanName = $channel ? ($channel->username ?? $channel->name ?? '') : '';
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
                        <div class="nia-video-channel"><?php echo _e($chanName); ?></div>
                    </div>
                </div>
            </a>
        <?php } ?>
    </div>
    <?php if (empty($items)) { ?><p class="text-muted">No music in this category.</p><?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
