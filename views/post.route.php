<?php
/**
 * Single article (post) template. Expects $post. Used by read.route.php.
 */
if (!defined('in_nia_app') || empty($post)) exit;
?>
<main class="vibe-main container py-4" style="max-width: 720px;">
    <p class="small text-muted mb-2"><a href="<?php echo url('blog'); ?>" class="text-muted">Blog</a></p>
    <article>
        <h1 class="vibe-title mb-3"><?php echo _e($post->title ?? ''); ?></h1>
        <p class="text-muted small mb-3"><?php echo _e(date('F j, Y', strtotime($post->created_at ?? 'now'))); ?></p>
        <div class="vibe-post-content"><?php echo $post->content ?? ''; ?></div>
    </article>
</main>
