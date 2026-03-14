<?php
/**
 * Blog: list of posts (articles).
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$page_title = 'Blog';
$posts = get_posts(['limit' => 20]);
$categories = get_blogcats();

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container py-4">
    <h1 class="nia-title mb-4">Blog</h1>
    <?php if ($categories) { ?>
    <p class="text-muted small mb-3">
        <?php foreach ($categories as $cat) { ?>
        <a href="<?php echo blogcat_url($cat->slug); ?>" class="text-muted"><?php echo _e($cat->name); ?></a>
        <?php echo $cat !== end($categories) ? ' · ' : ''; ?>
        <?php } ?>
    </p>
    <?php } ?>
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
    <p class="text-muted">No posts yet.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
