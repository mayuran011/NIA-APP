<?php
/**
 * Music carousel/loop (horizontal scroll).
 * Expects $items = array of video objects (type = music); optional $title.
 */
if (!defined('in_nia_app')) exit;
if (empty($items)) return;
$block_title = isset($title) ? $title : 'Music';
?>
<section class="vibe-box vibe-box-music-carousel mb-4">
    <?php if ($block_title) { ?>
    <h2 class="h5 mb-3"><?php echo _e($block_title); ?></h2>
    <?php } ?>
    <div class="vibe-music-carousel overflow-auto pb-2" style="display: flex; gap: 1rem; scroll-snap-type: x mandatory;">
        <?php foreach ($items as $item) {
            $thumb = !empty($item->thumb) ? $item->thumb : url('themes/main/assets/placeholder-music.png');
            $link = video_url($item->id, $item->title ?? '');
        ?>
        <a href="<?php echo _e($link); ?>" class="text-decoration-none text-reset flex-shrink-0" style="scroll-snap-align: start; width: 160px;">
            <div class="card border-0 shadow-sm vibe-card-music">
                <div class="card-img-top position-relative ratio ratio-1x1 bg-dark rounded">
                    <img src="<?php echo _e($thumb); ?>" alt="" class="object-fit-cover rounded" loading="lazy">
                </div>
                <div class="card-body p-2">
                    <div class="card-title small text-truncate mb-0"><?php echo _e($item->title ?? ''); ?></div>
                </div>
            </div>
        </a>
        <?php } ?>
    </div>
</section>
