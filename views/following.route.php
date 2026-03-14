<?php
/**
 * Following feed (/following/): content from subscribed channels.
 */
if (!defined('in_nia_app')) exit;

if (!is_logged()) {
    redirect(url('login') . '?redirect=' . urlencode(url('following')));
}

$page_title = 'Following';
$items = get_followed_media(current_user_id(), 48);

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="nia-title h4 mb-0 d-flex align-items-center">
            <span class="material-icons text-primary me-2">subscriptions</span>
            Following
        </h1>
        <div class="text-muted small"><?php echo count($items); ?> recent videos</div>
    </div>

    <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <div class="mb-4">
                <span class="material-icons text-muted" style="font-size: 5rem;">person_add_disabled</span>
            </div>
            <h2 class="h5 fw-bold">No content here yet</h2>
            <p class="text-muted mb-4">Subscribe to channels to see their latest videos and music in this feed.</p>
            <a href="<?php echo url(); ?>" class="btn btn-primary rounded-pill px-4">Browse Channels</a>
        </div>
    <?php else: ?>
        <div class="nia-video-grid">
            <?php foreach ($items as $v):
                $v_url = url('watch/' . $v->id . '/' . (isset($v->title) ? slug($v->title) : 'video'));
                $chan_url = url('user/' . $v->user_id);
                $avatar = !empty($v->channel_avatar) ? $v->channel_avatar : '';
                if ($avatar !== '' && strpos($avatar, 'http') !== 0) { $avatar = rtrim(SITE_URL, '/') . '/' . ltrim($avatar, '/'); }
                $initial = !empty($v->channel_name) ? strtoupper(substr($v->channel_name, 0, 1)) : (!empty($v->channel_username) ? strtoupper(substr($v->channel_username, 0, 1)) : '?');
            ?>
            <div class="nia-video-card">
                <a class="nia-video-thumb-link" href="<?php echo $v_url; ?>">
                    <img class="nia-video-thumb" src="<?php echo _e($v->thumb); ?>" alt="" loading="lazy">
                    <?php if (!empty($v->duration) && function_exists('nia_duration')): $fd = nia_duration($v->duration); if ($fd !== ''): ?>
                        <span class="nia-video-duration"><?php echo _e($fd); ?></span>
                    <?php endif; endif; ?>
                </a>
                <div class="nia-video-info d-flex gap-3 p-2">
                    <a href="<?php echo $chan_url; ?>" class="flex-shrink-0">
                        <?php if ($avatar): ?>
                            <img src="<?php echo _e($avatar); ?>" alt="" class="nia-video-avatar rounded-circle">
                        <?php else: ?>
                            <span class="nia-video-avatar-initial rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white"><?php echo $initial; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="min-width-0">
                        <h3 class="nia-video-title mb-1"><a href="<?php echo $v_url; ?>" class="text-decoration-none text-reset"><?php echo _e($v->title); ?></a></h3>
                        <?php if (function_exists('nia_yt_meta_render')) { $yt_meta = nia_yt_meta_render($v, 'cards'); if ($yt_meta !== '') { echo $yt_meta; } } ?>
                        <div class="nia-video-channel-stats small text-muted">
                            <a href="<?php echo $chan_url; ?>" class="text-decoration-none text-reset"><?php echo _e($v->channel_name ?? $v->channel_username); ?></a><?php echo ' · ' . number_format($v->views); ?> views · Added <?php echo (function_exists('time_ago') ? time_ago($v->created_at) : $v->created_at); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
?>
