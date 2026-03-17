<?php
/**
 * Load more content – returns HTML fragment and has_more for infinite scroll / Load more button.
 * GET: type (videos|music|users|playlists|profile_videos|profile_music), offset, limit, section (videos/music), user_id (profile).
 */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

header('Content-Type: application/json; charset=utf-8');

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$limit = min(48, max(1, (int) ($_GET['limit'] ?? 24)));
$section = isset($_GET['section']) ? trim($_GET['section']) : 'browse';
$user_id = (int) ($_GET['user_id'] ?? 0);

$site_url = rtrim(SITE_URL, '/');
$out = ['ok' => false, 'html' => '', 'has_more' => false, 'next_offset' => $offset];

if (!in_array($type, ['videos', 'music', 'users', 'playlists', 'profile_videos', 'profile_music', 'studio'], true)) {
    echo json_encode($out);
    exit;
}

global $db;
$pre = $db->prefix();
$uid = current_user_id();

// Studio: must be logged in
if ($type === 'studio' && $uid <= 0) {
    echo json_encode($out);
    exit;
}

// ---------- VIDEOS ----------
if ($type === 'videos') {
    if (!in_array($section, ['browse', 'featured', 'most-viewed', 'top-rated'], true)) $section = 'browse';
    $items = get_videos(['type' => 'video', 'section' => $section, 'limit' => $limit, 'offset' => $offset]);
    $items = is_array($items) ? $items : [];
    $has_more = count($items) >= $limit;
    ob_start();
    foreach ($items as $item) {
        $link = watch_url($item->id);
        $thumb = !empty($item->thumb) ? $item->thumb : '';
        if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
        $duration = function_exists('nia_duration') ? nia_duration($item->duration ?? 0) : '';
        $timeAgo = function_exists('nia_time_ago') ? nia_time_ago($item->created_at ?? null) : '';
        $views = (int) ($item->views ?? 0);
        $viewsStr = $views >= 1000000 ? round($views / 1000000, 1) . 'M' : ($views >= 1000 ? round($views / 1000, 1) . 'K' : $views) . ' views';
        $channel = isset($item->user_id) ? get_user($item->user_id) : null;
        $chanName = $channel ? ($channel->username ?? $channel->name ?? '') : '';
        $avatar = $channel && !empty($channel->avatar) ? $channel->avatar : '';
        if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = $site_url . '/' . ltrim($avatar, '/');
        $initial = $channel && !empty($channel->username) ? strtoupper(substr($channel->username, 0, 1)) : '?';
        ?>
        <a href="<?php echo _e($link); ?>" class="nia-video-card">
            <div class="nia-video-thumb-wrap">
                <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                <?php if ($duration !== '') { ?><span class="nia-video-duration"><?php echo _e($duration); ?></span><?php } ?>
            </div>
            <div class="nia-video-info">
                <?php if ($avatar) { ?><img class="nia-video-avatar" src="<?php echo _e($avatar); ?>" alt=""><?php } else { ?><span class="nia-video-avatar-initial"><?php echo _e($initial); ?></span><?php } ?>
                <div class="nia-video-meta">
                    <div class="nia-video-title"><?php echo _e($item->title ?? ''); ?></div>
                    <?php if (function_exists('nia_yt_meta_render')) { $yt_meta = nia_yt_meta_render($item, 'cards'); if ($yt_meta !== '') { echo $yt_meta; } } ?>
                    <div class="nia-video-channel-stats"><?php echo _e($chanName); ?><?php if ($chanName !== '' && ($viewsStr !== '' || $timeAgo !== '')) { ?> · <?php } ?><?php if ($viewsStr !== '') { echo _e($viewsStr); } ?><?php if ($viewsStr !== '' && $timeAgo !== '') { ?> · <?php } ?><?php if ($timeAgo !== '') { ?>Added <?php echo _e($timeAgo); ?><?php } ?></div>
                </div>
            </div>
        </a>
        <?php
    }
    $out = ['ok' => true, 'html' => ob_get_clean(), 'has_more' => $has_more, 'next_offset' => $offset + count($items)];
    echo json_encode($out);
    exit;
}

// ---------- MUSIC ----------
if ($type === 'music') {
    if (!in_array($section, ['browse', 'featured', 'most-viewed', 'top-rated'], true)) $section = 'browse';
    $items = get_videos(['type' => 'music', 'section' => $section, 'limit' => $limit, 'offset' => $offset]);
    $items = is_array($items) ? $items : [];
    $has_more = count($items) >= $limit;
    ob_start();
    foreach ($items as $item) {
        $link = function_exists('media_play_url') ? media_play_url($item->id, 'music', $item->title ?? '') : listen_url($item->id);
        $thumb = !empty($item->thumb) ? $item->thumb : '';
        if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
        $duration = function_exists('nia_duration') ? nia_duration($item->duration ?? 0) : '';
        $timeAgo = function_exists('nia_time_ago') ? nia_time_ago($item->created_at ?? null) : '';
        $views = (int) ($item->views ?? 0);
        $viewsStr = $views >= 1000000 ? round($views / 1000000, 1) . 'M' : ($views >= 1000 ? round($views / 1000, 1) . 'K' : $views) . ' views';
        $channel = isset($item->user_id) ? get_user($item->user_id) : null;
        $chanName = $channel ? ($channel->username ?? $channel->name ?? '') : '';
        $avatar = $channel && !empty($channel->avatar) ? $channel->avatar : '';
        if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = $site_url . '/' . ltrim($avatar, '/');
        $initial = $channel && !empty($channel->username) ? strtoupper(substr($channel->username, 0, 1)) : '?';
        ?>
        <a href="<?php echo _e($link); ?>" class="nia-video-card">
            <div class="nia-video-thumb-wrap">
                <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                <?php if ($duration !== '') { ?><span class="nia-video-duration"><?php echo _e($duration); ?></span><?php } ?>
            </div>
            <div class="nia-video-info">
                <?php if ($avatar) { ?><img class="nia-video-avatar" src="<?php echo _e($avatar); ?>" alt=""><?php } else { ?><span class="nia-video-avatar-initial"><?php echo _e($initial); ?></span><?php } ?>
                <div class="nia-video-meta">
                    <div class="nia-video-title"><?php echo _e($item->title ?? ''); ?></div>
                    <div class="nia-video-channel-stats"><?php echo _e($chanName); ?><?php if ($chanName !== '' && $viewsStr !== '') { ?> · <?php } ?><?php echo _e($viewsStr); ?><?php if ($timeAgo !== '') { ?> · <?php echo _e($timeAgo); ?><?php } ?></div>
                </div>
            </div>
        </a>
        <?php
    }
    $out = ['ok' => true, 'html' => ob_get_clean(), 'has_more' => $has_more, 'next_offset' => $offset + count($items)];
    echo json_encode($out);
    exit;
}

// ---------- USERS ----------
if ($type === 'users') {
    $users = $db->fetchAll("SELECT id, username, name, avatar, created_at FROM {$pre}users WHERE group_id > 0 ORDER BY created_at DESC LIMIT " . ($limit + 1) . " OFFSET " . $offset);
    $users = is_array($users) ? $users : [];
    $has_more = count($users) > $limit;
    if ($has_more) array_pop($users);
    ob_start();
    foreach ($users as $u) {
        $user = is_object($u) ? $u : (object) $u;
        $profile = profile_url($user->username ?? '', $user->id ?? 0);
        $avatar = !empty($user->avatar) ? $user->avatar : '';
        if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = $site_url . '/' . ltrim($avatar, '/');
        $name = $user->name ?? $user->username ?? '-';
        ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?php echo _e($profile); ?>" class="card text-decoration-none h-100 text-center">
                <div class="card-body">
                    <?php if ($avatar) { ?><img src="<?php echo _e($avatar); ?>" alt="" class="rounded-circle" style="width:64px;height:64px;object-fit:cover"><?php } else { ?><span class="nia-video-avatar-initial d-inline-block rounded-circle" style="width:64px;height:64px;line-height:64px;font-size:1.5rem"><?php echo _e(strtoupper(substr($name, 0, 1))); ?></span><?php } ?>
                    <div class="mt-2 fw-medium"><?php echo _e($name); ?></div>
                    <small class="text-muted">@<?php echo _e($user->username ?? ''); ?></small>
                </div>
            </a>
        </div>
        <?php
    }
    $out = ['ok' => true, 'html' => ob_get_clean(), 'has_more' => $has_more, 'next_offset' => $offset + count($users)];
    echo json_encode($out);
    exit;
}

// ---------- PLAYLISTS ----------
if ($type === 'playlists') {
    $playlists = $db->fetchAll("SELECT p.*, u.username FROM {$pre}playlists p LEFT JOIN {$pre}users u ON u.id = p.user_id WHERE (p.system_key IS NULL OR p.system_key = '') ORDER BY p.created_at DESC LIMIT " . ($limit + 1) . " OFFSET " . $offset);
    $playlists = is_array($playlists) ? $playlists : [];
    $has_more = count($playlists) > $limit;
    if ($has_more) array_pop($playlists);
    ob_start();
    foreach ($playlists as $p) {
        $pl = is_object($p) ? $p : (object) $p;
        $url = function_exists('playlist_url') ? playlist_url($pl->name ?? 'playlist', $pl->id ?? 0) : url('playlist/' . ($pl->slug ?? '') . '/' . (int) ($pl->id ?? 0));
        ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?php echo _e($url); ?>" class="card text-decoration-none h-100">
                <div class="card-body">
                    <span class="material-icons text-muted">playlist_play</span>
                    <div class="mt-2 fw-medium"><?php echo _e($pl->name ?? 'Playlist'); ?></div>
                    <small class="text-muted"><?php echo _e($pl->username ?? '-'); ?></small>
                </div>
            </a>
        </div>
        <?php
    }
    $out = ['ok' => true, 'html' => ob_get_clean(), 'has_more' => $has_more, 'next_offset' => $offset + count($playlists)];
    echo json_encode($out);
    exit;
}

// ---------- PROFILE VIDEOS / MUSIC ----------
if (($type === 'profile_videos' || $type === 'profile_music') && $user_id > 0) {
    $media_type = $type === 'profile_music' ? 'music' : 'video';
    $sql = "SELECT * FROM {$pre}videos WHERE user_id = ? AND type = ? AND (private = 0 OR user_id = ?)";
    $params = [$user_id, $media_type, $uid];
    $sql .= " ORDER BY created_at DESC LIMIT " . ($limit + 1) . " OFFSET " . $offset;
    $rows = $db->fetchAll($sql, $params);
    $rows = is_array($rows) ? $rows : [];
    $has_more = count($rows) > $limit;
    if ($has_more) array_pop($rows);
    $profile_user = get_user($user_id);
    $chanName = $profile_user ? ($profile_user->username ?? $profile_user->name ?? '') : '';
    $avatar = $profile_user && !empty($profile_user->avatar) ? $profile_user->avatar : '';
    if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = $site_url . '/' . ltrim($avatar, '/');
    $initial = $profile_user && !empty($profile_user->username) ? strtoupper(substr($profile_user->username, 0, 1)) : '?';
    ob_start();
    foreach ($rows as $r) {
        $v = is_array($r) ? (object) $r : $r;
        $link = function_exists('media_play_url') ? media_play_url($v->id, $v->type ?? 'video', $v->title ?? '') : watch_url($v->id);
        $thumb = !empty($v->thumb) ? $v->thumb : '';
        if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
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
        <?php
    }
    $out = ['ok' => true, 'html' => ob_get_clean(), 'has_more' => $has_more, 'next_offset' => $offset + count($rows)];
    echo json_encode($out);
    exit;
}

// ---------- STUDIO (dashboard: current user's content) ----------
if ($type === 'studio') {
    $rows = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? ORDER BY created_at DESC LIMIT " . ($limit + 1) . " OFFSET " . $offset, [$uid]);
    $rows = is_array($rows) ? $rows : [];
    $has_more = count($rows) > $limit;
    if ($has_more) array_pop($rows);
    ob_start();
    foreach ($rows as $m) {
        $m = is_array($m) ? (object) $m : $m;
        $thumb = !empty($m->thumb) ? $m->thumb : '';
        if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
        $play_url = function_exists('media_play_url') ? media_play_url($m->id, $m->type ?? 'video', $m->title ?? '') : watch_url($m->id);
        $media_type = isset($m->type) && $m->type === 'music' ? 'music' : 'video';
        $edit_url = (function_exists('is_moderator') && is_moderator() && defined('ADMINCP')) ? ($media_type === 'music' ? url(ADMINCP . '/music/edit/' . $m->id) : url(ADMINCP . '/videos/edit/' . $m->id)) : '';
        $created_ts = !empty($m->created_at) ? strtotime($m->created_at) : 0;
        $views_num = (int)($m->views ?? 0);
        $title_sort = strtolower($m->title ?? '');
        ?>
        <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xl-2 studio-card" data-title="<?php echo _e($title_sort); ?>" data-type="<?php echo _e($media_type); ?>" data-created="<?php echo $created_ts; ?>" data-views="<?php echo $views_num; ?>" data-sort-title="<?php echo _e($title_sort); ?>">
            <div class="card studio-item-card bg-dark border-secondary h-100 rounded-3 overflow-hidden shadow-sm">
                <a href="<?php echo _e($play_url); ?>" class="text-decoration-none text-reset d-block">
                    <div class="position-relative studio-thumb" style="aspect-ratio:16/9;">
                        <?php if ($thumb) { ?><img src="<?php echo _e($thumb); ?>" class="card-img-top w-100 h-100" alt="" style="object-fit:cover;" loading="lazy" onerror="this.style.display='none'; var n=this.nextElementSibling; if(n) n.classList.remove('d-none');"><div class="w-100 h-100 bg-secondary position-absolute top-0 start-0 d-none d-flex align-items-center justify-content-center"><span class="material-icons text-dark"><?php echo $media_type === 'music' ? 'music_note' : 'videocam'; ?></span></div><?php } else { ?><div class="w-100 h-100 bg-secondary d-flex align-items-center justify-content-center"><span class="material-icons text-dark"><?php echo $media_type === 'music' ? 'music_note' : 'videocam'; ?></span></div><?php } ?>
                        <span class="position-absolute bottom-0 start-0 m-1 badge bg-dark bg-opacity-90 small"><?php echo $media_type === 'music' ? 'Music' : 'Video'; ?></span>
                        <span class="position-absolute top-0 end-0 m-1 small text-muted"><?php echo $views_num; ?> <span class="material-icons align-middle" style="font-size:0.9rem;">visibility</span></span>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="small text-truncate fw-semibold" title="<?php echo _e($m->title ?? ''); ?>"><?php echo _e($m->title ?? '—'); ?></div>
                        <div class="small text-muted d-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:0.85rem;">schedule</span>
                            <?php echo $created_ts ? date('M j, Y', $created_ts) : '—'; ?>
                        </div>
                        <?php if (isset($m->likes) && (int)$m->likes > 0) { ?><div class="small text-muted d-flex align-items-center gap-1 mt-1"><span class="material-icons" style="font-size:0.85rem;">thumb_up</span><?php echo (int)$m->likes; ?></div><?php } ?>
                    </div>
                </a>
                <div class="card-footer bg-transparent border-secondary p-2 d-flex flex-wrap gap-1 align-items-center">
                    <a href="<?php echo _e($play_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1" title="Play"><span class="material-icons" style="font-size:1.1rem;">play_circle</span></a>
                    <?php if ($edit_url !== '') { ?><a href="<?php echo _e($edit_url); ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Edit"><span class="material-icons" style="font-size:1.1rem;">edit</span></a><?php } ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this permanently?');">
                        <input type="hidden" name="action" value="delete_media">
                        <input type="hidden" name="media_id" value="<?php echo (int)$m->id; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" title="Delete"><span class="material-icons" style="font-size:1.1rem;">delete</span></button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    $out = ['ok' => true, 'html' => ob_get_clean(), 'has_more' => $has_more, 'next_offset' => $offset + count($rows)];
    echo json_encode($out);
    exit;
}

echo json_encode($out);
