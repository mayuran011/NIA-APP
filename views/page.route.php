<?php
/**
 * Static page template. Expects $page. Used by read.route.php.
 */
if (!defined('in_nia_app') || empty($page)) exit;
?>
<main class="vibe-main container py-4" style="max-width: 720px;">
    <article>
        <h1 class="vibe-title mb-4"><?php echo _e($page->title ?? ''); ?></h1>
        <div class="vibe-page-content"><?php echo $page->content ?? ''; ?></div>
    </article>
</main>
