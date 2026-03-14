<?php
/**
 * /images and /images/:section — image grid (browse).
 */
if (!defined('in_nia_app')) exit;
$section = trim($GLOBALS['nia_route_section'] ?? '');
if ($section === '') $section = 'browse';
$page_title = 'Images';
$items = get_images(['limit' => 24]);
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4" data-pull-to-refresh>
    <h1 class="nia-title mb-4">Images</h1>
    <div class="nia-video-grid">
        <?php
        foreach ($items as $item) {
            $link = function_exists('view_url') ? view_url($item->id, $item->title ?? '') : (function_exists('image_url') ? image_url($item->id, $item->title ?? '') : url('image/' . (int) $item->id));
            $thumb = !empty($item->thumb) ? $item->thumb : (!empty($item->file_path) ? $item->file_path : '');
            if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
            $channel = isset($item->user_id) ? get_user($item->user_id) : null;
            $chanName = $channel ? ($channel->username ?? $channel->name ?? '') : '';
            ?>
            <a href="<?php echo _e($link); ?>" class="nia-video-card">
                <div class="nia-video-thumb-wrap">
                    <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                </div>
                <div class="nia-video-info">
                    <div class="nia-video-meta">
                        <div class="nia-video-title"><?php echo _e($item->title ?? ''); ?></div>
                        <div class="nia-video-channel"><?php echo _e($chanName); ?></div>
                    </div>
                </div>
            </a>
        <?php } ?>
    </div>
    <?php if (empty($items)) { ?>
    <p class="text-muted">No images yet.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
