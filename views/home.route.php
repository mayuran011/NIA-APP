<?php
/**
 * Home: category chips, video grid. Sectioned blocks from admin homepage_boxes.
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$modview = 'home';
$page_title = get_option('sitename', 'Home');
$boxes = get_option('homepage_boxes', '[]');
$boxes = is_string($boxes) ? json_decode($boxes, true) : $boxes;
if (!is_array($boxes)) $boxes = [];

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>

<main class="nia-main nia-home" data-pull-to-refresh>
    <div class="nia-chips">
        <a class="nia-chip active" href="<?php echo url(); ?>">All</a>
        <a class="nia-chip" href="<?php echo url('videos/browse'); ?>">Videos</a>
        <a class="nia-chip" href="<?php echo url('music/browse'); ?>">Music</a>
        <a class="nia-chip" href="<?php echo url('images/browse'); ?>">Images</a>
        <a class="nia-chip" href="<?php echo url('category'); ?>">Channels</a>
        <a class="nia-chip" href="<?php echo url('lists'); ?>">Playlists</a>
        <a class="nia-chip" href="<?php echo url('videos/featured'); ?>">Featured</a>
        <a class="nia-chip" href="<?php echo url('show'); ?>">Explore</a>
    </div>

    <section class="nia-boxes" data-section="home">
        <?php
        foreach ($boxes as $box) {
            $type = isset($box['type']) ? $box['type'] : 'video';
            $title = isset($box['title']) ? trim($box['title']) : ucfirst($type);
            $source = isset($box['source']) ? $box['source'] : 'browse';
            $ids = isset($box['ids']) && is_array($box['ids']) ? $box['ids'] : [];
            $limit = isset($box['limit']) ? (int) $box['limit'] : 12;
            $grid_size = isset($box['grid_size']) && in_array($box['grid_size'], ['small', 'medium', 'large'], true) ? $box['grid_size'] : 'medium';

            if ($type === 'html') {
                $content = isset($box['content']) ? $box['content'] : '';
                if ($content !== '') {
                    echo '<div class="nia-section-block"><div class="card border-0 bg-transparent"><div class="card-body">';
                    if ($title !== '') echo '<h2 class="h6 text-muted mb-3">' . _e($title) . '</h2>';
                    echo $content;
                    echo '</div></div></div>';
                }
                continue;
            }

            $items = [];
            if ($type === 'video' || $type === 'music') {
                if ($source === 'ids' && !empty($ids)) {
                    global $db;
                    $pre = $db->prefix();
                    $idList = array_slice(array_map('intval', $ids), 0, $limit);
                    if (!empty($idList)) {
                        $inList = implode(',', $idList);
                        $uid = current_user_id();
                        $items = $db->fetchAll("SELECT * FROM {$pre}videos WHERE id IN ($inList) AND (private = 0 OR user_id = ?) AND type = ? ORDER BY FIELD(id, $inList)", [$uid, $type]);
                    }
                } else {
                    $section = $source === 'featured' ? 'featured' : 'browse';
                    $items = get_videos(['type' => $type, 'section' => $section, 'limit' => $limit]);
                }
            } elseif ($type === 'image') {
                $items = get_images(['limit' => $limit]);
            } elseif ($type === 'channel') {
                $chanType = isset($box['channel_type']) ? $box['channel_type'] : 'video';
                $items = get_channels($chanType, 0);
            } elseif ($type === 'playlist' && $source === 'ids' && count($ids) > 0) {
                $pl = get_playlist((int) $ids[0]);
                if ($pl) {
                    $itemIds = get_playlist_items($pl->id, 'video', $limit);
                    foreach ($itemIds as $row) {
                        $v = get_video($row->media_id);
                        if ($v) $items[] = $v;
                    }
                }
            }

            if (empty($items)) continue;

            if ($type === 'video' || $type === 'music') {
                echo '<div class="nia-section-block">';
                if ($title !== '') echo '<h2 class="nia-section-title">' . _e($title) . '</h2>';
                echo '<div class="nia-video-grid nia-grid--' . _e($grid_size) . '">';
                foreach ($items as $item) {
                    $link = function_exists('media_play_url') ? media_play_url($item->id, $item->type ?? 'video', $item->title ?? '') : watch_url($item->id, $item->title ?? '');
                    $thumb = !empty($item->thumb) ? $item->thumb : '';
                    if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
                    $duration = function_exists('nia_duration') ? nia_duration($item->duration ?? 0) : '';
                    $timeAgo = function_exists('nia_time_ago') ? nia_time_ago($item->created_at ?? null) : '';
                    $views = (int) ($item->views ?? 0);
                    $viewsStr = $views >= 1000000 ? round($views / 1000000, 1) . 'M' : ($views >= 1000 ? round($views / 1000, 1) . 'K' : $views) . ' views';
                    $channel = isset($item->user_id) ? get_user($item->user_id) : null;
                    $chanName = $channel ? ($channel->username ?? $channel->name ?? '') : '';
                    $chanUrl = $channel ? profile_url($channel->username ?? '', $channel->id ?? 0) : '#';
                    $avatar = $channel && !empty($channel->avatar) ? $channel->avatar : '';
                    if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = rtrim(SITE_URL, '/') . '/' . ltrim($avatar, '/');
                    $initial = $channel && !empty($channel->username) ? strtoupper(substr($channel->username, 0, 1)) : '?';
                    echo '<a href="' . _e($link) . '" class="nia-video-card">';
                    echo '<div class="nia-video-thumb-wrap">';
                    echo '<img class="nia-video-thumb" src="' . _e($thumb ?: '') . '" alt="" loading="lazy" onerror="this.style.display=\'none\'">';
                    if ($duration !== '') echo '<span class="nia-video-duration">' . _e($duration) . '</span>';
                    echo '</div>';
                    echo '<div class="nia-video-info">';
                    if ($avatar) echo '<img class="nia-video-avatar" src="' . _e($avatar) . '" alt="">'; else echo '<span class="nia-video-avatar-initial">' . _e($initial) . '</span>';
                    echo '<div class="nia-video-meta">';
                    echo '<div class="nia-video-title">' . _e($item->title ?? '') . '</div>';
                    if (function_exists('nia_yt_meta_render')) { $yt_meta = nia_yt_meta_render($item, 'home'); if ($yt_meta !== '') echo $yt_meta; }
                    echo '<div class="nia-video-channel-stats">' . _e($chanName) . ($chanName !== '' && ($viewsStr !== '' || $timeAgo !== '') ? ' · ' : '') . _e($viewsStr) . ($viewsStr !== '' && $timeAgo !== '' ? ' · ' : '') . ($timeAgo !== '' ? 'Added ' . _e($timeAgo) : '') . '</div>';
                    echo '</div></div></a>';
                }
                echo '</div></div>';
            } elseif ($type === 'image') {
                echo '<div class="nia-section-block"><h2 class="nia-section-title">' . _e($title) . '</h2><div class="nia-video-grid nia-grid--' . _e($grid_size) . '">';
                foreach ($items as $item) {
                    $link = function_exists('view_url') ? view_url($item->id, $item->title ?? '') : image_url($item->id, $item->title ?? '');
                    $thumb = !empty($item->thumb) ? $item->thumb : ($item->path ?? '');
                    if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
                    echo '<a href="' . _e($link) . '" class="nia-video-card">';
                    echo '<div class="nia-video-thumb-wrap"><img class="nia-video-thumb" src="' . _e($thumb ?: '') . '" alt="" loading="lazy" onerror="this.style.display=\'none\'"></div>';
                    echo '<div class="nia-video-info"><span class="nia-video-avatar-initial">?</span><div class="nia-video-meta"><div class="nia-video-title">' . _e($item->title ?? '') . '</div></div></div></a>';
                }
                echo '</div></div>';
            } elseif ($type === 'channel') {
                echo '<div class="nia-section-block"><h2 class="nia-section-title">' . _e($title) . '</h2><div class="nia-video-grid nia-grid--' . _e($grid_size) . '">';
                foreach ($items as $item) {
                    $chanType = isset($item->type) ? $item->type : 'video';
                    $base = $chanType === 'music' ? 'musicfilter' : ($chanType === 'image' ? 'imagefilter' : (get_option('channel-seo-url') ?: 'category'));
                    $link = url($base . '/' . $item->slug);
                    $thumb = !empty($item->thumb) ? $item->thumb : '';
                    if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
                    echo '<a href="' . _e($link) . '" class="nia-video-card">';
                    echo '<div class="nia-video-thumb-wrap"><img class="nia-video-thumb" src="' . _e($thumb ?: '') . '" alt="" loading="lazy" onerror="this.style.display=\'none\'"></div>';
                    echo '<div class="nia-video-info">';
                    if ($thumb) echo '<img class="nia-video-avatar" src="' . _e($thumb) . '" alt="">'; else echo '<span class="nia-video-avatar-initial">' . _e(strtoupper(substr($item->name ?? '?', 0, 1))) . '</span>';
                    echo '<div class="nia-video-meta"><div class="nia-video-title">' . _e($item->name ?? '') . '</div></div></div></a>';
                }
                echo '</div></div>';
            }
        }
        ?>
        <?php if (empty($boxes)) { ?>
        <div class="nia-section-block">
            <div class="card border-0 bg-transparent">
                <div class="card-body text-center py-5">
                    <p class="text-muted mb-0">Welcome. Configure homepage boxes in <a href="<?php echo is_moderator() ? url((defined('ADMINCP') ? ADMINCP : 'moderator') . '/homepage') : url('dashboard'); ?>" class="text-primary"><?php echo is_moderator() ? 'Admin → Homepage' : 'Dashboard'; ?></a>.</p>
                </div>
            </div>
        </div>
        <?php } ?>
    </section>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
?>
