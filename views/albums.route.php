<?php
/**
 * /albums — image albums/galleries listing (stub: uses images if no albums table).
 */
if (!defined('in_nia_app')) exit;
$page_title = 'Albums';
global $db;
$pre = $db->prefix();
$has_albums = $db->fetch("SHOW TABLES LIKE '{$pre}albums'");
$items = [];
if ($has_albums) {
    $items = $db->fetchAll("SELECT * FROM {$pre}albums ORDER BY created_at DESC LIMIT 24");
}
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Albums</h1>
    <?php if (!empty($items)) { ?>
    <div class="row g-3">
        <?php foreach ($items as $a) {
            $al = is_object($a) ? $a : (object) $a;
            $url = url('album/' . (int) ($al->id ?? 0));
            ?>
            <div class="col-6 col-md-4"><a href="<?php echo _e($url); ?>" class="card text-decoration-none h-100"><div class="card-body"><?php echo _e($al->name ?? $al->title ?? 'Album'); ?></div></a></div>
        <?php } ?>
    </div>
    <?php } else { ?>
    <p class="text-muted">No albums yet. <a href="<?php echo url('images'); ?>">Browse images</a>.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
