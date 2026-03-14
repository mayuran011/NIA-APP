<?php
/**
 * /album/:id — single image album/gallery.
 */
if (!defined('in_nia_app')) exit;
$rest = trim($GLOBALS['nia_route_section'] ?? '');
$id = (int) (preg_replace('/^(\d+).*$/', '$1', $rest) ?: 0);
$page_title = 'Album';
$items = [];
if ($id > 0) {
    $items = get_images(['album_id' => $id, 'limit' => 48]);
}
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Album</h1>
    <div class="nia-video-grid">
        <?php foreach ($items as $item) {
            $it = is_object($item) ? $item : (object) $item;
            $link = function_exists('view_url') ? view_url($it->id, $it->title ?? '') : (function_exists('image_url') ? image_url($it->id, $it->title ?? '') : url('image/' . (int) $it->id));
            $thumb = !empty($it->thumb) ? $it->thumb : (!empty($it->file_path) ? $it->file_path : '');
            if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
            ?>
            <a href="<?php echo _e($link); ?>" class="nia-video-card">
                <div class="nia-video-thumb-wrap">
                    <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                </div>
                <div class="nia-video-info"><div class="nia-video-meta"><div class="nia-video-title"><?php echo _e($it->title ?? ''); ?></div></div></div>
            </a>
        <?php } ?>
    </div>
    <?php if (empty($items)) { ?><p class="text-muted">No images in this album.</p><?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
