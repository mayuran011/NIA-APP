<?php
/**
 * Watch page (YouTube-style): main video left, related videos right.
 * URL: /watch/:id (video ID only; all playback happens here).
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$nia_current_media_id = null;
$nia_current_media_type = 'video';
$video = null;
if ($nia_section !== '') {
    if (is_numeric($nia_section)) {
        $video = get_video((int) $nia_section);
    } else {
        // Alphanumeric ID: Check if it's a YouTube ID (11 chars) or similar
        global $db;
        $pre = $db->prefix();
        $video = $db->fetch("SELECT * FROM {$pre}videos WHERE source = 'youtube' AND remote_url LIKE ?", ['%' . $nia_section . '%']);
        
        if (!$video && strlen($nia_section) === 11) {
            // Auto-add from YouTube
            $yt_id = $nia_section;
            $yt_url = 'https://www.youtube.com/watch?v=' . $yt_id;
            
            // Try rich API details first
            $yt_v = function_exists('nia_youtube_video_details') ? nia_youtube_video_details($yt_id) : null;
            $yt_published = null;
            $yt_channel = null;
            if ($yt_v && !empty($yt_v['title'])) {
                $title = $yt_v['title'];
                $desc = $yt_v['description'] ?? '';
                $thumb = $yt_v['thumb'] ?? null;
                $duration = $yt_v['duration'] ?? 0;
                $views = $yt_v['views'] ?? 0;
                $yt_published = $yt_v['published_at'] ?? null;
                $yt_channel = $yt_v['channel_title'] ?? null;
            } else {
                $meta = NiaProviders::fetchMetadata($yt_url);
                $title = $meta['title'] ?? '';
                $desc = $meta['description'] ?? '';
                $thumb = $meta['thumbnail_url'] ?? null;
                $duration = 0;
                $views = 0;
            }

            if ($title !== '') {
                $embed_url = 'https://www.youtube.com/embed/' . $yt_id;
                $embed_code = '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>';
                
                try {
                    $db->query(
                        "INSERT INTO {$pre}videos (user_id, title, description, type, source, remote_url, embed_code, thumb, duration, views, yt_published_at, yt_channel_name) VALUES (?, ?, ?, 'video', 'youtube', ?, ?, ?, ?, ?, ?, ?)",
                        [get_option('admin_user_id', 1), $title, $desc, $yt_url, $embed_code, $thumb, $duration, $views, $yt_published, $yt_channel]
                    );
                } catch (Throwable $e) {
                    $db->query(
                        "INSERT INTO {$pre}videos (user_id, title, description, type, source, remote_url, embed_code, thumb, duration, views) VALUES (?, ?, ?, 'video', 'youtube', ?, ?, ?, ?, ?)",
                        [get_option('admin_user_id', 1), $title, $desc, $yt_url, $embed_code, $thumb, $duration, $views]
                    );
                }
                $new_id = (int) $db->pdo()->lastInsertId();
                if ($new_id > 0) {
                    $video = get_video($new_id);
                    if (function_exists('add_activity')) add_activity(get_option('admin_user_id', 1), 'shared', 'video', $new_id);
                }
            }
        }
    }
    
    if ($video) {
        $nia_current_media_id = (int) $video->id;
        $nia_current_media_type = isset($video->type) && $video->type === 'music' ? 'music' : 'video';
        $GLOBALS['nia_current_media_id'] = $nia_current_media_id;
    }
}
$page_title = $video ? _e($video->title) : 'Watch';
$is_premium_content = $video && !empty($video->premium);
$can_watch = !$video || !$is_premium_content || (function_exists('can_access_premium_content') && can_access_premium_content($video));
$is_nsfw = $video && !empty($video->nsfw);
$nsfw_ok = isset($_GET['nsfw']) && $_GET['nsfw'] === '1' || !empty($_SESSION['nsfw_ok']);
if ($video && $is_nsfw && !$nsfw_ok) {
    if (isset($_POST['nsfw_confirm'])) {
        $_SESSION['nsfw_ok'] = true;
        redirect(watch_url($video->id, $video->title ?? '') . '?nsfw=1');
    }
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
    echo '<main class="nia-main container py-4"><div class="card border-warning"><div class="card-body"><p class="mb-2">This content is marked NSFW.</p><form method="post"><input type="hidden" name="nsfw_confirm" value="1"><button type="submit" class="btn btn-warning btn-sm">Continue</button></form> <a href="' . url() . '" class="btn btn-outline-secondary btn-sm">Leave</a></div></div></main>';
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
    exit;
}
$user_like = $video && is_logged() ? get_user_like(current_user_id(), 'video', $video->id) : 0;
$channel = $video && isset($video->user_id) ? get_user($video->user_id) : null;
$subs_count = $channel && function_exists('subscriber_count') ? subscriber_count($channel->id) : 0;
$is_subscribed = $channel && is_logged() && function_exists('is_subscribed_to') ? is_subscribed_to($channel->id) : false;
$related = $video && function_exists('get_related_videos') ? get_related_videos($video->id, 20) : [];
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-watch nia-main">
    <?php if ($video && $can_watch) {
        $player_opts = ['container_id' => 'nia-video-player', 'autoplay' => true];
        if (function_exists('do_action')) do_action('vibe_before_player', 'nia-video-player', $video, $player_opts);
        $player_html = NiaPlayers::render($video, $player_opts);
        if (function_exists('apply_filters')) $player_html = apply_filters('the_embedded_video', $player_html, $video, array_merge($player_opts, ['placement' => 'video']));
        if (function_exists('do_action')) do_action('vibe_after_player', 'nia-video-player', $video, $player_opts);
        $thumb = !empty($video->thumb) ? $video->thumb : '';
        if (strpos($thumb, 'http') !== 0 && $thumb !== '') { $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/'); }
    ?>
    <div class="nia-watch-layout">
        <div class="nia-watch-main">
            <div class="nia-watch-player"><?php echo $player_html; ?></div>
            <div class="nia-watch-info">
                <h1 class="nia-watch-title"><?php echo _e($video->title); ?></h1>
                <?php
                $yt_cfg = function_exists('nia_yt_meta_display_config') ? nia_yt_meta_display_config() : null;
                $show_yt_meta = $yt_cfg && nia_yt_meta_show_on_watch();
                $yt_channel_name = ($show_yt_meta && !empty($video->source) && $video->source === 'youtube' && !empty($video->yt_channel_name) && $yt_cfg['show_channel']) ? trim($video->yt_channel_name) : '';
                $yt_upload_time = ($show_yt_meta && !empty($video->source) && $video->source === 'youtube' && !empty($video->yt_published_at) && $yt_cfg['show_upload']) ? $video->yt_published_at : null;
                $block_style = ($show_yt_meta && function_exists('nia_yt_meta_block_style')) ? nia_yt_meta_block_style() : '';
                $label_style = ($show_yt_meta && function_exists('nia_yt_meta_label_style')) ? nia_yt_meta_label_style() : '';
                $lbl_ch = $yt_cfg['label_channel'] ?? 'Channel';
                $lbl_up = $yt_cfg['label_upload'] ?? 'Upload';
                ?>
                <div class="nia-watch-meta-row">
                    <?php $video_duration = (int) ($video->duration ?? 0); if ($video_duration > 0 && function_exists('nia_duration')) { ?><span class="nia-watch-duration"><?php echo _e(nia_duration($video_duration)); ?></span><?php } ?>
                    <?php if ($video_duration > 0 && function_exists('nia_duration')) { ?> · <?php } ?><span class="nia-watch-views"><?php echo (int) $video->views; ?> views</span>
                    <?php if (!empty($video->created_at)) { ?> · <span class="nia-watch-date">Added <?php echo function_exists('nia_time_ago') ? nia_time_ago($video->created_at) : date('M j, Y', strtotime($video->created_at)); ?></span><?php } ?>
                    <?php if ($show_yt_meta && ($yt_channel_name !== '' || $yt_upload_time !== null)) { ?> · <span class="nia-watch-yt-inline"<?php if ($block_style !== '') { ?> style="<?php echo _e($block_style); ?>"<?php } ?>>
                        <?php if ($yt_channel_name !== '') { ?><span class="nia-yt-label"<?php if ($label_style !== '') { ?> style="<?php echo _e($label_style); ?>"<?php } ?>><?php echo _e($lbl_ch); ?></span> <?php echo _e($yt_channel_name); ?><?php } ?>
                        <?php if ($yt_channel_name !== '' && $yt_upload_time !== null) { ?> · <?php } ?>
                        <?php if ($yt_upload_time !== null) { ?><span class="nia-yt-label"<?php if ($label_style !== '') { ?> style="<?php echo _e($label_style); ?>"<?php } ?>><?php echo _e($lbl_up); ?></span> <?php echo function_exists('nia_time_ago') ? nia_time_ago($yt_upload_time) : date('M j, Y', strtotime($yt_upload_time)); ?><?php } ?>
                    </span><?php } ?>
                </div>
                <div class="nia-watch-channel-row">
                    <?php if ($channel) {
                        $chan_avatar = !empty($channel->avatar) ? $channel->avatar : '';
                        if ($chan_avatar !== '' && strpos($chan_avatar, 'http') !== 0) $chan_avatar = rtrim(SITE_URL, '/') . '/' . ltrim($chan_avatar, '/');
                        $chan_url = profile_url($channel->username ?? '', $channel->id);
                        $subs_str = $subs_count >= 1000000 ? round($subs_count / 1000000, 1) . 'M' : ($subs_count >= 1000 ? round($subs_count / 1000, 1) . 'K' : $subs_count) . ' subscribers';
                    ?>
                    <a href="<?php echo _e($chan_url); ?>" class="nia-watch-channel">
                        <?php if ($chan_avatar) { ?><img src="<?php echo _e($chan_avatar); ?>" alt="" class="nia-watch-channel-avatar"><?php } else { ?><span class="nia-watch-channel-avatar nia-watch-channel-initial"><?php echo _e(strtoupper(substr($channel->username ?? '?', 0, 1))); ?></span><?php } ?>
                        <div class="nia-watch-channel-meta">
                            <span class="nia-watch-channel-name"><?php echo _e($channel->username ?? $channel->name ?? ''); ?></span>
                            <span class="nia-watch-channel-subs"><?php echo _e($subs_str); ?></span>
                        </div>
                    </a>
                    <?php if (is_logged() && $channel->id != current_user_id()) { ?>
                    <button type="button" class="btn btn-sm <?php echo $is_subscribed ? 'btn-secondary' : 'btn-danger'; ?> nia-subscribe-btn" data-user-id="<?php echo (int) $channel->id; ?>" data-subscribed="<?php echo $is_subscribed ? '1' : '0'; ?>"><?php echo $is_subscribed ? 'Subscribed' : 'Subscribe'; ?></button>
                    <?php } ?>
                    <?php } ?>
                </div>
                <div class="nia-watch-actions">
                    <?php if (is_logged()) { ?>
                    <button type="button" class="nia-watch-action-btn nia-like-btn <?php echo $user_like === 1 ? 'active' : ''; ?>" data-object-type="video" data-object-id="<?php echo (int) $video->id; ?>"><span class="material-icons">thumb_up</span><span class="nia-action-label"><?php echo (int) $video->likes; ?></span></button>
                    <button type="button" class="nia-watch-action-btn nia-dislike-btn <?php echo $user_like === -1 ? 'active' : ''; ?>" data-object-type="video" data-object-id="<?php echo (int) $video->id; ?>"><span class="material-icons">thumb_down</span></button>
                    <?php } else { ?>
                    <span class="nia-watch-action-btn"><span class="material-icons">thumb_up</span><span class="nia-action-label"><?php echo (int) $video->likes; ?></span></span>
                    <?php } ?>
                    <a href="#" class="nia-watch-action-btn nia-share-btn" data-url="<?php echo _e(watch_url($video->id, $video->title ?? '')); ?>" data-title="<?php echo _e($video->title ?? ''); ?>" data-media-id="<?php echo (int) $video->id; ?>"><span class="material-icons">share</span><span class="nia-action-label">Share</span></a>
                    <?php
                    $can_download = function_exists('can_download_media') && can_download_media();
                    $video_source = $video->source ?? 'local';
                    $yt_download_ok = $video_source === 'youtube' && (int) get_option('youtube_download_enabled', '0') === 1;
                    if ($can_download && ($video_source === 'local' || $yt_download_ok)) {
                        $stream_download_url = rtrim(SITE_URL, '/') . '/stream.php?id=' . (int) $video->id . '&download=1';
                    ?>
                    <div class="dropdown d-inline-block">
                        <button type="button" class="nia-watch-action-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span class="material-icons">download</span><span class="nia-action-label">Download</span></button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <?php if ($video_source === 'local') {
                                $is_music = isset($video->type) && $video->type === 'music';
                            ?>
                            <li><a class="dropdown-item" href="<?php echo _e($stream_download_url); ?>" download><?php echo $is_music ? 'MP3 / Audio' : 'MP4 / Video'; ?></a></li>
                            <?php } else { ?>
                            <li><a class="dropdown-item nia-dl-format" href="#" data-id="<?php echo (int) $video->id; ?>" data-format="mp4_1080">MP4 1080p</a></li>
                            <li><a class="dropdown-item nia-dl-format" href="#" data-id="<?php echo (int) $video->id; ?>" data-format="mp4_720">MP4 720p</a></li>
                            <li><a class="dropdown-item nia-dl-format" href="#" data-id="<?php echo (int) $video->id; ?>" data-format="mp3_320">MP3 320kbps</a></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <?php } ?>
                    <a href="<?php echo url('me/later'); ?>" class="nia-watch-action-btn" title="Watch later"><span class="material-icons">playlist_add</span><span class="nia-action-label">Save</span></a>
                    <button type="button" class="nia-watch-action-btn" data-bs-toggle="modal" data-bs-target="#vibeReportModal"><span class="material-icons">more_horiz</span></button>
                </div>
            </div>
            <?php
            $object_type = 'video';
            $object_id = (int) $video->id;
            require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'comments-section.php';
            ?>
        </div>
        <aside class="nia-watch-related">
            <div class="nia-related-header">
                <span class="nia-related-tab active">All</span>
                <?php if ($channel) { ?><span class="nia-related-tab">From <?php echo _e($channel->username ?? ''); ?></span><?php } ?>
            </div>
            <ul class="nia-related-list">
                <?php foreach ($related as $r) {
                    $r_link = function_exists('media_play_url') ? media_play_url($r->id, $r->type ?? 'video', $r->title ?? '') : watch_url($r->id, $r->title ?? '');
                    $r_thumb = !empty($r->thumb) ? $r->thumb : '';
                    if ($r_thumb !== '' && strpos($r_thumb, 'http') !== 0) $r_thumb = rtrim(SITE_URL, '/') . '/' . ltrim($r_thumb, '/');
                    $r_dur = function_exists('nia_duration') ? nia_duration($r->duration ?? 0) : '';
                    $r_views = (int) ($r->views ?? 0);
                    $r_viewsStr = $r_views >= 1000000 ? round($r_views / 1000000, 1) . 'M' : ($r_views >= 1000 ? round($r_views / 1000, 1) . 'K' : $r_views) . ' views';
                    $r_time = function_exists('nia_time_ago') ? nia_time_ago($r->created_at ?? null) : '';
                    $r_ch = isset($r->user_id) ? get_user($r->user_id) : null;
                    $r_chan = $r_ch ? ($r_ch->username ?? $r_ch->name ?? '') : '';
                ?>
                <li class="nia-related-item">
                    <a href="<?php echo _e($r_link); ?>" class="nia-related-link">
                        <div class="nia-related-thumb-wrap">
                            <img src="<?php echo _e($r_thumb ?: ''); ?>" alt="" class="nia-related-thumb" loading="lazy" onerror="this.style.display='none'">
                            <?php if ($r_dur !== '') { ?><span class="nia-related-duration"><?php echo _e($r_dur); ?></span><?php } ?>
                        </div>
                        <div class="nia-related-info">
                            <div class="nia-related-title"><?php echo _e($r->title ?? ''); ?></div>
                            <?php if (function_exists('nia_yt_meta_render')) { $yt_meta = nia_yt_meta_render($r, 'watch'); if ($yt_meta !== '') { echo $yt_meta; } } ?>
                            <div class="nia-related-channel"><?php echo _e($r_chan); ?></div>
                            <div class="nia-related-stats"><?php echo _e($r_viewsStr); ?> · Added <?php echo _e($r_time); ?></div>
                        </div>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </aside>
    </div>
    <script>
    (function(){
        if (!('mediaSession' in navigator)) return;
        navigator.mediaSession.metadata = new MediaMetadata({
            title: <?php echo json_encode($video->title ?? ''); ?>,
            artist: <?php echo json_encode($channel ? ($channel->username ?? '') : get_option('sitename', 'Nia App')); ?>,
            artwork: <?php echo $thumb ? json_encode([['src' => $thumb, 'sizes' => '512x512', 'type' => 'image/jpeg']]) : '[]'; ?>
        });
        var el = document.querySelector('#nia-video-player audio, #nia-video-player video');
        if (el) {
            navigator.mediaSession.setActionHandler('play', function() { el.play(); });
            navigator.mediaSession.setActionHandler('pause', function() { el.pause(); });
            navigator.mediaSession.setActionHandler('stop', function() { el.pause(); el.currentTime = 0; });
            try {
                navigator.mediaSession.setActionHandler('seekbackward', function() { el.currentTime = Math.max(0, el.currentTime - 10); });
                navigator.mediaSession.setActionHandler('seekforward', function() { el.currentTime = Math.min(el.duration, el.currentTime + 10); });
            } catch (e) {}
            el.addEventListener('play', function() { navigator.mediaSession.playbackState = 'playing'; });
            el.addEventListener('pause', function() { navigator.mediaSession.playbackState = 'paused'; });
            var isAudio = el.tagName.toLowerCase() === 'audio';
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) return;
                if (isAudio) return;
                if (el && !el.paused) el.pause();
            });
        }
    })();
    </script>
    <?php } elseif ($video && !$can_watch) { ?>
    <div class="nia-watch-layout">
        <div class="nia-watch-main">
            <div class="card border-warning mb-3"><div class="card-body">This is premium content. <a href="<?php echo url('payment'); ?>">Get Premium</a></div></div>
            <h1 class="nia-watch-title"><?php echo _e($video->title); ?></h1>
        </div>
        <aside class="nia-watch-related">
            <?php $rel = function_exists('get_related_videos') ? get_related_videos($video->id, 20) : []; foreach ($rel as $r) { $r_link = function_exists('media_play_url') ? media_play_url($r->id, $r->type ?? 'video', $r->title ?? '') : watch_url($r->id, $r->title ?? ''); $r_thumb = !empty($r->thumb) ? $r->thumb : ''; if ($r_thumb !== '' && strpos($r_thumb, 'http') !== 0) $r_thumb = rtrim(SITE_URL, '/') . '/' . ltrim($r_thumb, '/'); ?>
            <a href="<?php echo _e($r_link); ?>" class="nia-related-link d-flex gap-2 mb-2"><img src="<?php echo _e($r_thumb); ?>" alt="" width="168" height="94" style="object-fit:cover"><span><?php echo _e($r->title ?? ''); ?></span></a>
            <?php } ?>
        </aside>
    </div>
    <?php } else { ?>
    <div class="nia-watch-main p-4">
        <p class="text-muted">Video not found.</p>
        <a href="<?php echo url(); ?>">Go home</a>
    </div>
    <?php } ?>
</main>
<?php if ($video) { ?>
<div class="modal fade" id="vibeReportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary"><h5 class="modal-title">Report</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="vibeReportForm" data-object-type="video" data-object-id="<?php echo (int) $video->id; ?>">
                    <div class="mb-3"><label class="form-label">Reason</label><select name="reason" class="form-select bg-dark border-secondary text-light" required><option value="spam">Spam</option><option value="copyright">Copyright</option><option value="other">Other</option></select></div>
                    <div class="mb-3"><label class="form-label">Details</label><textarea name="details" class="form-control bg-dark border-secondary text-light" rows="2"></textarea></div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php } ?>
<script>
(function(){
    var likeUrl = '<?php echo url('app/ajax/like.php'); ?>', reportUrl = '<?php echo url('app/ajax/report.php'); ?>';
    (function trackView(){ var u='<?php echo url('app/ajax/track.php'); ?>', id=<?php echo $video && $can_watch ? (int)$video->id : 0; ?>, t='<?php echo ($video && isset($video->type) && $video->type === 'music') ? 'music' : 'video'; ?>'; if(id) fetch(u+'?media_type='+encodeURIComponent(t)+'&media_id='+id,{credentials:'same-origin'}); })();
    document.querySelectorAll('.nia-like-btn').forEach(function(btn){
        btn.addEventListener('click', function(){ var d=this.dataset; var fd=new FormData(); fd.append('object_type',d.objectType); fd.append('object_id',d.objectId); fetch(likeUrl,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){if(j.ok){ var l=btn.querySelector('.nia-action-label'); if(l) l.textContent=j.likes; btn.classList.add('active'); document.querySelectorAll('.nia-dislike-btn').forEach(function(b){ b.classList.remove('active'); }); }}); });
    });
    document.querySelectorAll('.nia-dislike-btn').forEach(function(btn){
        btn.addEventListener('click', function(){ var d=this.dataset; var fd=new FormData(); fd.append('object_type',d.objectType); fd.append('object_id',d.objectId); fetch('<?php echo url('app/ajax/dislike.php'); ?>',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){if(j.ok){ document.querySelectorAll('.nia-like-btn').forEach(function(b){ b.classList.remove('active'); }); btn.classList.add('active'); var l=document.querySelector('.nia-like-btn .nia-action-label'); if(l) l.textContent=j.likes; }}); });
    });
// document.querySelectorAll('.nia-share-btn').forEach(function(btn){
    //     btn.addEventListener('click', function(e){ e.preventDefault(); if (navigator.share) navigator.share({ title: this.dataset.title, url: this.dataset.url }); else { navigator.clipboard.writeText(this.dataset.url); alert('Link copied'); } });
    // });
    var reportForm = document.getElementById('vibeReportForm');
    if (reportForm) reportForm.addEventListener('submit', function(e){ e.preventDefault(); var fd=new FormData(this); fd.append('object_type', this.dataset.objectType); fd.append('object_id', this.dataset.objectId); fetch(reportUrl,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){ if(j.ok){ var m=document.getElementById('vibeReportModal'); if(m) bootstrap.Modal.getInstance(m).hide(); alert('Report submitted.'); } }); });
    document.querySelectorAll('.nia-subscribe-btn').forEach(function(btn){
        btn.addEventListener('click', function(){ var uid=this.dataset.userId; var sub=this.dataset.subscribed==='1'; var fd=new FormData(); fd.append('user_id',uid); fd.append('action', sub?'unsubscribe':'subscribe'); fetch('<?php echo url('app/ajax/subscribe.php'); ?>',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){if(j.ok){ btn.dataset.subscribed=j.subscribed?'1':'0'; btn.textContent=j.subscribed?'Subscribed':'Subscribe'; btn.classList.toggle('btn-danger',!j.subscribed); btn.classList.toggle('btn-secondary',j.subscribed); }}); });
    });
    document.querySelectorAll('.nia-dl-format').forEach(function(a){
        a.addEventListener('click', function(e){ e.preventDefault(); var id=this.dataset.id; var fmt=this.dataset.format; if(id&&fmt) window.location.href='<?php echo url('download'); ?>?id='+id+'&format='+encodeURIComponent(fmt); });
    });
    document.querySelectorAll('.vibe-add-comment').forEach(function(form){
        form.addEventListener('submit', function(e){ e.preventDefault(); var fd=new FormData(); fd.append('object_type', this.dataset.objectType); fd.append('object_id', this.dataset.objectId); fd.append('body', this.querySelector('[name=body]').value); fetch('<?php echo url('app/ajax/addComment.php'); ?>',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){ if(j.ok) location.reload(); }); });
    });
    document.querySelectorAll('.vibe-del-comment').forEach(function(btn){
        btn.addEventListener('click', function(){ if(!confirm('Delete?')) return; var fd=new FormData(); fd.append('comment_id', this.dataset.commentId); fetch('<?php echo url('app/ajax/delComment.php'); ?>',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){ if(j.ok) location.reload(); }); });
    });
})();
</script>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
?>
