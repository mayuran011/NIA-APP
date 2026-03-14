<?php
/**
 * /read/:name/:id – single article (post) or static page. Dispatches to post or page template.
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$section = trim($nia_section, '/');
$id = null;
if (preg_match('#/(\d+)$#', $section, $m)) {
    $id = (int) $m[1];
} elseif (is_numeric($section)) {
    $id = (int) $section;
}

$post = $id ? get_post($id) : null;
$page = null;
if ($post) {
    $page_title = _e($post->title ?? 'Blog');
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
    require ABSPATH . 'views' . DIRECTORY_SEPARATOR . 'post.route.php';
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
    exit;
}

$page = $id ? get_page($id) : null;
if ($page) {
    $page_title = _e($page->title ?? 'Page');
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
    require ABSPATH . 'views' . DIRECTORY_SEPARATOR . 'page.route.php';
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
    exit;
}

$page_title = 'Not found';
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
echo '<main class="nia-main container py-4"><h1>Not found</h1><p class="text-muted">Blog post or page not found.</p></main>';
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
