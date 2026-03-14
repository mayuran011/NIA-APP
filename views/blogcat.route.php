<?php
/**
 * Blog category: list posts in category (blogcat).
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$slug = trim($nia_section, '/');
$category = $slug !== '' ? get_blogcat_by_slug($slug) : null;

if (!$category) {
    $page_title = 'Category';
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
    echo '<main class="nia-main container py-4"><h1>Category not found</h1></main>';
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
    exit;
}

$posts = get_posts(['category_id' => $category->id, 'limit' => 20]);
$page_title = _e($category->name) . ' – Blog';

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container py-4">
    <p class="small text-muted mb-2"><a href="<?php echo url('blog'); ?>" class="text-muted">Blog</a></p>
    <h1 class="nia-title mb-4"><?php echo _e($category->name); ?></h1>
    <?php if (!empty($category->description)) { ?><p class="text-muted"><?php echo _e($category->description); ?></p><?php } ?>
    <?php if ($posts) { ?>
    <ul class="list-unstyled">
        <?php foreach ($posts as $p) { ?>
        <li class="mb-4 pb-3 border-bottom border-secondary">
            <a href="<?php echo article_url($p->slug ?? '', $p->id); ?>" class="text-decoration-none text-light">
                <h2 class="h5"><?php echo _e($p->title ?? ''); ?></h2>
            </a>
            <?php if (!empty($p->excerpt)) { ?><p class="text-muted small mb-1"><?php echo _e($p->excerpt); ?></p><?php } ?>
            <span class="small text-muted"><?php echo _e(date('F j, Y', strtotime($p->created_at ?? 'now'))); ?></span>
        </li>
        <?php } ?>
    </ul>
    <?php } else { ?>
    <p class="text-muted">No posts in this category.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
