<?php
/**
 * /tag/:slug — content by tag (redirects to show search with tag).
 */
if (!defined('in_nia_app')) exit;
$rest = trim($GLOBALS['nia_route_section'] ?? '');
$tag = $rest !== '' ? $rest : (isset($_GET['tag']) ? trim($_GET['tag']) : '');
if ($tag !== '') {
    redirect(url('show') . '?q=' . rawurlencode($tag) . '&type=all');
}
$page_title = 'Tag';
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Tag</h1>
    <p class="text-muted">Use the search to find content by tag.</p>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
