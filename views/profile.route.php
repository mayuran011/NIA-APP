<?php
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$profile_user = null;
if (preg_match('#^([^/]+)/(\d+)#', trim($nia_section, '/'), $m)) {
    $profile_user = get_user((int) $m[2]);
}
$tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'videos';
$tabs = ['videos' => 'Videos', 'music' => 'Music', 'playlists' => 'Playlists', 'likes' => 'Likes', 'about' => 'About'];
if (!isset($tabs[$tab])) $tab = 'videos';

if (!$profile_user) {
    $page_title = 'Profile';
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
    echo '<main class="nia-main container py-4"><h1>Profile not found</h1></main>';
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
    exit;
}

$page_title = $profile_user->name . ' – Profile';
$subs_count = subscriber_count($profile_user->id);
$is_subscribed = is_subscribed_to($profile_user->id);
$profile_url_base = profile_url($profile_user->username, $profile_user->id);

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container py-4">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <img src="<?php echo $profile_user->avatar ? _e($profile_user->avatar) : 'https://ui-avatars.com/api?name=' . urlencode($profile_user->name); ?>" alt="" class="rounded-circle" width="80" height="80">
        <div>
            <h1 class="nia-title h3 mb-1"><?php echo _e($profile_user->name); ?></h1>
            <p class="text-muted small mb-1">@<?php echo _e($profile_user->username); ?> · <?php echo (int) $subs_count; ?> subscribers</p>
            <?php if (current_user_id() !== (int) $profile_user->id) {
                $conv = get_or_create_conversation(current_user_id(), $profile_user->id);
            ?>
            <button type="button" class="btn btn-primary btn-sm nia-subscribe-btn" data-user-id="<?php echo (int) $profile_user->id; ?>" data-subscribed="<?php echo $is_subscribed ? '1' : '0'; ?>">
                <?php echo $is_subscribed ? 'Unsubscribe' : 'Subscribe'; ?>
            </button>
            <?php if ($conv) { ?><a href="<?php echo conversation_url($conv->id); ?>" class="btn btn-outline-secondary btn-sm">Message</a><?php } ?>
            <?php } ?>
        </div>
    </div>
    <ul class="nav nav-tabs border-secondary mb-3">
        <?php foreach ($tabs as $k => $label) {
            $active = $tab === $k ? ' active' : '';
            $href = $profile_url_base . ($k === 'videos' ? '' : '?tab=' . $k);
        ?>
        <li class="nav-item"><a class="nav-link<?php echo $active; ?> text-light border-secondary" href="<?php echo _e($href); ?>"><?php echo _e($label); ?></a></li>
        <?php } ?>
    </ul>
    <div class="nia-profile-tab">
        <?php
        global $db;
        $pre = $db->prefix();
        $chanName = $profile_user->username ?? $profile_user->name ?? '';
        $chanUrl = $profile_url_base;
        $avatar = $profile_user->avatar ?? '';
        if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = rtrim(SITE_URL, '/') . '/' . ltrim($avatar, '/');
        $initial = !empty($profile_user->username) ? strtoupper(substr($profile_user->username, 0, 1)) : '?';

        if ($tab === 'videos' || $tab === 'music' || $tab === 'likes') {
            $items = [];
            $profile_per = 24;
            if ($tab === 'videos') {
                $rows = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? AND type = 'video' AND (private = 0 OR user_id = ?) ORDER BY created_at DESC LIMIT " . ($profile_per + 1), [$profile_user->id, current_user_id()]);
                $has_more_profile = count($rows) > $profile_per;
                if ($has_more_profile) $rows = array_slice($rows, 0, $profile_per);
                foreach ($rows as $r) { $items[] = is_array($r) ? (object) $r : $r; }
            } elseif ($tab === 'music') {
                $rows = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? AND type = 'music' AND (private = 0 OR user_id = ?) ORDER BY created_at DESC LIMIT " . ($profile_per + 1), [$profile_user->id, current_user_id()]);
                $has_more_profile = count($rows) > $profile_per;
                if ($has_more_profile) $rows = array_slice($rows, 0, $profile_per);
                foreach ($rows as $r) { $items[] = is_array($r) ? (object) $r : $r; }
            } else {
                $pl = get_playlist(PLAYLIST_LIKES, $profile_user->id);
                if ($pl) {
                    $pds = get_playlist_items($pl->id, 'video', 24);
                    foreach ($pds as $pd) {
                        $v = get_video($pd->media_id);
                        if ($v) $items[] = $v;
                    }
                }
            }
            if (!empty($items)) {
                $grid_id = 'nia-profile-' . $tab . '-grid';
                $loadmore_type = $tab === 'music' ? 'profile_music' : 'profile_videos';
                echo '<div id="' . _e($grid_id) . '" class="nia-video-grid">';
                foreach ($items as $v) {
                    $link = function_exists('media_play_url') ? media_play_url($v->id, $v->type ?? 'video', $v->title ?? '') : watch_url($v->id);
                    $thumb = !empty($v->thumb) ? $v->thumb : '';
                    if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
                    $duration = function_exists('nia_duration') ? nia_duration($v->duration ?? 0) : '';
                    $timeAgo = function_exists('nia_time_ago') ? nia_time_ago($v->created_at ?? null) : '';
                    $views = (int) ($v->views ?? 0);
                    $viewsStr = $views >= 1000000 ? round($views / 1000000, 1) . 'M' : ($views >= 1000 ? round($views / 1000, 1) . 'K' : $views) . ' views';
                    ?>
                    <a href="<?php echo _e($link); ?>" class="nia-video-card">
                        <div class="nia-video-thumb-wrap">
                            <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                            <?php if ($duration !== '') { ?><span class="nia-video-duration"><?php echo _e($duration); ?></span><?php } ?>
                        </div>
                        <div class="nia-video-info">
                            <?php if ($avatar) { ?><img class="nia-video-avatar" src="<?php echo _e($avatar); ?>" alt=""><?php } else { ?><span class="nia-video-avatar-initial"><?php echo _e($initial); ?></span><?php } ?>
                            <div class="nia-video-meta">
                                <div class="nia-video-title"><?php echo _e($v->title ?? ''); ?></div>
                                <?php if (function_exists('nia_yt_meta_render')) { $yt_meta = nia_yt_meta_render($v, 'cards'); if ($yt_meta !== '') { echo $yt_meta; } } ?>
                                <div class="nia-video-channel-stats"><?php echo _e($chanName); ?><?php if ($chanName !== '' && ($viewsStr !== '' || $timeAgo !== '')) { ?> · <?php } ?><?php if ($viewsStr !== '') { echo _e($viewsStr); } ?><?php if ($viewsStr !== '' && $timeAgo !== '') { ?> · <?php } ?><?php if ($timeAgo !== '') { ?>Added <?php echo _e($timeAgo); ?><?php } ?></div>
                            </div>
                        </div>
                    </a>
                <?php }
                echo '</div>';
                if (($tab === 'videos' || $tab === 'music') && !empty($has_more_profile) && isset($has_more_profile)) {
                    echo '<div class="nia-loadmore-wrap text-center py-4"><button type="button" class="btn btn-outline-primary nia-loadmore-btn d-inline-flex align-items-center gap-2" data-loadmore-type="' . _e($loadmore_type) . '" data-loadmore-user-id="' . (int)$profile_user->id . '" data-loadmore-limit="24" data-loadmore-offset="24" data-loadmore-container="#' . _e($grid_id) . '" aria-label="Load more"><span class="material-icons" style="font-size:1.2rem;">expand_more</span><span class="nia-loadmore-text">Load more</span><span class="nia-loadmore-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span></button></div>';
                }
            } else {
                echo '<p class="text-muted">' . ($tab === 'videos' ? 'No videos.' : ($tab === 'music' ? 'No music.' : 'No likes yet.')) . '</p>';
            }
        } elseif ($tab === 'playlists') {
            $pls = $db->fetchAll("SELECT * FROM {$pre}playlists WHERE user_id = ? AND system_key IS NULL ORDER BY name LIMIT 20", [$profile_user->id]);
            if ($pls) { foreach ($pls as $pl) { $pl = is_array($pl) ? (object) $pl : $pl; ?>
                <div class="mb-2"><a href="<?php echo playlist_url($pl->slug, $pl->id); ?>"><?php echo _e($pl->name); ?></a></div>
            <?php } } else { echo '<p class="text-muted">No playlists.</p>'; }
        } else {
            echo '<p class="text-muted">About @' . _e($profile_user->username) . '.</p>';
        }
        ?>
    </div>
</main>
<script>
(function(){
    document.querySelectorAll('.nia-subscribe-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var uid = this.getAttribute('data-user-id');
            var sub = this.getAttribute('data-subscribed') === '1';
            var fd = new FormData();
            fd.append('user_id', uid);
            fd.append('action', sub ? 'unsubscribe' : 'subscribe');
            fetch('<?php echo url('app/ajax/subscribe.php'); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if(d.ok) {
                        btn.setAttribute('data-subscribed', d.subscribed ? '1' : '0');
                        btn.textContent = d.subscribed ? 'Unsubscribe' : 'Subscribe';
                    }
                });
        });
    });
})();
</script>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
