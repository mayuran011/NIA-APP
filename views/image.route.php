<?php
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$vibe_current_media_id = null;
$vibe_current_media_type = 'image';
$image = null;
if (preg_match('#^(\d+)#', $nia_section, $m)) {
    $image = get_image((int) $m[1]);
    if ($image) {
        $vibe_current_media_id = (int) $image->id;
        $vibe_current_media_type = 'image';
    }
}
$page_title = $image ? _e($image->title) : 'Image';
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <?php if ($image) { ?>
    <h1 class="nia-title"><?php echo _e($image->title); ?></h1>
    <p class="text-muted">Single image page. Add to playlist in header.</p>
    <?php } else { ?>
    <h1 class="nia-title">Image</h1>
    <p class="text-muted">Section: <?php echo _e($nia_section); ?></p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
