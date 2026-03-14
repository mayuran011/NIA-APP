<?php
/**
 * Music list block (card list layout).
 * Expects $items = array of video objects (type = music); optional $title.
 */
if (!defined('in_nia_app')) exit;
if (empty($items)) return;
$block_title = isset($title) ? $title : 'Music';
?>
<section class="vibe-box vibe-box-music mb-4">
    <?php if ($block_title) { ?>
    <h2 class="h5 mb-3"><?php echo _e($block_title); ?></h2>
    <?php } ?>
    <div class="row g-3">
        <?php foreach ($items as $item) {
            $thumb = !empty($item->thumb) ? $item->thumb : url('themes/main/assets/placeholder-music.png');
            $media_type = isset($item->type) && $item->type === 'music' ? 'music' : 'video';
            $link = video_url($item->id, $item->title ?? '');
        ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="<?php echo _e($link); ?>" class="text-decoration-none text-reset">
                <div class="card border-0 shadow-sm h-100 vibe-card-music">
                    <div class="card-img-top position-relative ratio ratio-1x1 bg-dark rounded-top">
                        <img src="<?php echo _e($thumb); ?>" alt="" class="object-fit-cover rounded-top" loading="lazy">
                        <span class="position-absolute bottom-0 end-0 m-1 badge bg-dark opacity-75"><?php echo _e($media_type); ?></span>
                    </div>
                    <div class="card-body p-2">
                        <div class="card-title small text-truncate mb-0"><?php echo _e($item->title ?? ''); ?></div>
                        <?php if (!empty($item->duration)) { ?>
                        <small class="text-muted"><?php echo _e(gmdate('i:s', (int) $item->duration)); ?></small>
                        <?php } ?>
                    </div>
                </div>
            </a>
        </div>
        <?php } ?>
    </div>
</section>
