<?php
/** Comments section for video/image. Expects $object_type, $object_id. */
if (!defined('in_nia_app') || empty($object_type) || empty($object_id)) return;
$comments = get_comments($object_type, $object_id, 0, 30);
$add_url = url('app/ajax/addComment.php');
$more_url = url('app/ajax/morecoms.php');
$like_comment_url = url('app/ajax/likeComment.php');
$del_comment_url = url('app/ajax/delComment.php');
?>
<section class="vibe-comments mt-4">
    <h2 class="h6 mb-3">Comments</h2>
    <?php if (is_logged()) { ?>
    <form class="vibe-add-comment mb-3" data-object-type="<?php echo _e($object_type); ?>" data-object-id="<?php echo (int) $object_id; ?>">
        <textarea name="body" class="form-control bg-dark border-secondary text-light mb-2" rows="2" placeholder="Add a comment..." maxlength="2000"></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Comment</button>
    </form>
    <?php } else { ?>
    <p class="text-muted small"><a href="<?php echo url('login'); ?>">Login</a> to comment.</p>
    <?php } ?>
    <div class="vibe-comments-list">
        <?php foreach ($comments as $c) {
            $is_own = is_logged() && (int) $c->user_id === current_user_id();
            $replies = get_comments($object_type, $object_id, $c->id, 20);
        ?>
        <div class="vibe-comment border-bottom border-secondary py-2 mb-2" data-comment-id="<?php echo (int) $c->id; ?>">
            <div class="small fw-medium"><?php echo _e($c->author_name ?? $c->author_username ?? 'User'); ?></div>
            <div class="small"><?php echo nl2br(_e($c->body)); ?></div>
            <div class="small text-muted">
                <?php echo _e(date('M j, g:i A', strtotime($c->created_at))); ?>
                <?php if (is_logged()) { ?>
                · <button type="button" class="btn btn-link btn-sm p-0 vibe-like-comment" data-comment-id="<?php echo (int) $c->id; ?>">Like (<?php echo (int) $c->likes_count; ?>)</button>
                · <button type="button" class="btn btn-link btn-sm p-0 vibe-reply-btn" data-parent-id="<?php echo (int) $c->id; ?>">Reply</button>
                <?php if ($is_own || is_moderator()) { ?> · <button type="button" class="btn btn-link btn-sm p-0 text-danger vibe-del-comment" data-comment-id="<?php echo (int) $c->id; ?>">Delete</button><?php } ?>
                <?php } ?>
            </div>
            <div class="vibe-reply-form ms-3 mt-1 d-none" data-parent-id="<?php echo (int) $c->id; ?>">
                <textarea class="form-control form-control-sm bg-dark border-secondary text-light mb-1" rows="1" placeholder="Write a reply..."></textarea>
                <button type="button" class="btn btn-primary btn-sm vibe-reply-submit">Reply</button>
            </div>
            <?php if ($replies) { ?>
            <div class="ms-3 mt-2 vibe-replies">
                <?php foreach ($replies as $r) {
                    $r_own = is_logged() && (int) $r->user_id === current_user_id();
                ?>
                <div class="vibe-comment py-1 small" data-comment-id="<?php echo (int) $r->id; ?>">
                    <span class="fw-medium"><?php echo _e($r->author_name ?? $r->author_username ?? 'User'); ?></span>: <?php echo nl2br(_e($r->body)); ?>
                    <span class="text-muted"><?php echo _e(date('M j, g:i', strtotime($r->created_at))); ?></span>
                    <?php if (is_logged() && ($r_own || is_moderator())) { ?><button type="button" class="btn btn-link btn-sm p-0 text-danger vibe-del-comment" data-comment-id="<?php echo (int) $r->id; ?>">Delete</button><?php } ?>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    <?php if (empty($comments)) { ?>
    <p class="text-muted small">No comments yet.</p>
    <?php } ?>
</section>
