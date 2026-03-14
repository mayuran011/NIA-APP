<?php
/**
 * Image search: dedicated route and backend.
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['vibe_route_section'] ?? '';
$q = isset($_GET['q']) ? trim($_GET['q']) : (string) $nia_section;
global $db;
$pre = $db->prefix();
$images = [];
if ($q !== '') {
    $like = '%' . preg_replace('/%|_/', '\\\\$0', $q) . '%';
    $images = $db->fetchAll(
        "SELECT * FROM {$pre}images WHERE title LIKE ? OR tags LIKE ? OR description LIKE ? ORDER BY created_at DESC LIMIT 48",
        [$like, $like, $like]
    );
}
$page_title = $q !== '' ? 'Image search: ' . _e($q) : 'Image search';

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Image search</h1>
    <form method="get" action="<?php echo url('imgsearch'); ?>" class="mb-4">
        <div class="row g-2">
            <div class="col-auto flex-grow-1">
                <input type="text" class="form-control bg-dark border-secondary text-light" name="q" value="<?php echo _e($q); ?>" placeholder="Search pictures by title or tags...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>
    <?php if ($q !== '') { ?>
    <?php if ($images) { ?>
    <div class="row g-3">
        <?php foreach ($images as $img) { ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="<?php echo function_exists('view_url') ? view_url($img->id, $img->title ?? '') : image_url($img->id, $img->title ?? ''); ?>" class="text-decoration-none text-reset">
                <div class="card border-0 shadow-sm h-100">
                    <div class="ratio ratio-1x1 bg-dark rounded-top">
                        <img src="<?php echo $img->thumb ? _e($img->thumb) : ''; ?>" alt="" class="object-fit-cover rounded-top" loading="lazy" onerror="this.style.display='none'">
                    </div>
                    <div class="card-body p-2">
                        <div class="card-title small text-truncate mb-0"><?php echo _e($img->title ?? ''); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <?php } ?>
    </div>
    <?php } else { ?>
    <p class="text-muted">No images found for “<?php echo _e($q); ?>”.</p>
    <?php } ?>
    <?php } else { ?>
    <p class="text-muted">Enter a keyword to search pictures by title or tags.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
