<?php
/**
 * /music and /music/:section — music browse page. Sections from Music Page Builder (music_page_boxes).
 */
if (!defined('in_nia_app')) exit;
$route_section = trim($GLOBALS['nia_route_section'] ?? '');
if ($route_section === '') $route_section = 'browse';
if (!in_array($route_section, ['browse', 'featured', 'most-viewed', 'top-rated'], true)) {
    $route_section = 'browse';
}
$page_title = 'Music';
$modview = 'music';

$boxes = get_option('music_page_boxes', '[]');
$boxes = is_string($boxes) ? json_decode($boxes, true) : $boxes;
if (!is_array($boxes)) $boxes = [];

// Default sections when builder has not been configured yet
if (empty($boxes)) {
    $boxes = [
        ['type' => 'music', 'title' => 'Listen again', 'source' => 'browse', 'limit' => 10, 'ids' => [], 'display' => 'carousel'],
        ['type' => 'library', 'title' => 'From your library', 'source' => 'browse', 'limit' => 12, 'ids' => [], 'display' => 'carousel'],
        ['type' => 'music', 'title' => 'Quick picks', 'source' => 'most-viewed', 'limit' => 12, 'ids' => [], 'display' => 'list'],
        ['type' => 'music', 'title' => 'New releases', 'source' => 'browse', 'limit' => 12, 'ids' => [], 'display' => 'large'],
        ['type' => 'music', 'title' => 'Long listens', 'source' => 'browse', 'limit' => 9, 'ids' => [], 'display' => 'long'],
        ['type' => 'music', 'title' => 'Featured for you', 'source' => 'top-rated', 'limit' => 12, 'ids' => [], 'display' => 'carousel'],
    ];
}

function nia_music_card_item($item, $link_type = 'music') {
    if ($link_type === 'music') {
        $link = listen_url($item->id);
    } elseif ($link_type === 'video') {
        $link = function_exists('media_play_url') ? media_play_url($item->id, 'video', $item->title ?? '') : watch_url($item->id, $item->title ?? '');
    } else {
        $link = function_exists('view_url') ? view_url($item->id, $item->title ?? '') : (function_exists('image_url') ? image_url($item->id, $item->title ?? '') : '#');
    }
    $thumb = !empty($item->thumb) ? $item->thumb : (!empty($item->path) ? $item->path : '');
    if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
    $views = (int) ($item->views ?? 0);
    $viewsStr = $views >= 1000000 ? round($views / 1000000, 1) . 'M' : ($views >= 1000 ? round($views / 1000, 1) . 'K' : $views) . ' views';
    $channel = isset($item->user_id) ? get_user($item->user_id) : null;
    $chanName = $channel ? ($channel->username ?? $channel->name ?? '') : '';
    $duration = function_exists('nia_duration') ? nia_duration($item->duration ?? 0) : '';
    return compact('item', 'link', 'thumb', 'viewsStr', 'chanName', 'duration');
}

// Resolve items for a box
function nia_music_box_items($box) {
    $type = isset($box['type']) ? $box['type'] : 'music';
    $source = isset($box['source']) ? $box['source'] : 'browse';
    $limit = isset($box['limit']) ? (int) $box['limit'] : 12;
    $ids = isset($box['ids']) && is_array($box['ids']) ? $box['ids'] : [];

    if ($type === 'html') {
        return ['type' => 'html', 'content' => isset($box['content']) ? $box['content'] : ''];
    }

    if ($type === 'library') {
        $items = [];
        if (is_logged() && function_exists('get_playlist') && defined('PLAYLIST_HISTORY')) {
            $pl = get_playlist(PLAYLIST_HISTORY, current_user_id());
            if ($pl && function_exists('get_playlist_items')) {
                $pds = get_playlist_items($pl->id, 'music', $limit);
                foreach ($pds as $pd) {
                    $v = get_video($pd->media_id ?? 0);
                    if ($v && ($v->type ?? '') === 'music') $items[] = $v;
                }
            }
        }
        return ['type' => 'music', 'items' => $items, 'link_type' => 'music'];
    }

    if ($type === 'channel') {
        $chanType = isset($box['channel_type']) ? $box['channel_type'] : 'music';
        $chans = get_channels($chanType, 0);
        $chans = array_slice($chans, 0, $limit);
        return ['type' => 'channel', 'items' => $chans];
    }

    if ($type === 'playlist' && $source === 'ids' && !empty($ids)) {
        $pl = get_playlist((int) $ids[0]);
        $items = [];
        if ($pl && function_exists('get_playlist_items')) {
            foreach (get_playlist_items($pl->id, 'video', $limit) as $row) {
                $v = get_video($row->media_id);
                if ($v) $items[] = $v;
            }
        }
        return ['type' => $pl ? 'video' : 'music', 'items' => $items, 'link_type' => 'video'];
    }

    if ($type === 'image') {
        $items = get_images(['limit' => $limit]);
        return ['type' => 'image', 'items' => $items];
    }

    if ($type === 'video' || $type === 'music') {
        if ($source === 'ids' && !empty($ids)) {
            global $db;
            $pre = $db->prefix();
            $idList = array_slice(array_map('intval', $ids), 0, $limit);
            if (!empty($idList)) {
                $inList = implode(',', $idList);
                $uid = current_user_id();
                $items = $db->fetchAll("SELECT * FROM {$pre}videos WHERE id IN ($inList) AND (private = 0 OR user_id = ?) AND type = ? ORDER BY FIELD(id, $inList)", [$uid, $type]);
            } else {
                $items = [];
            }
        } else {
            $section = in_array($source, ['featured', 'most-viewed', 'top-rated'], true) ? $source : 'browse';
            $items = get_videos(['type' => $type, 'section' => $section, 'limit' => $limit]);
        }
        $link_type = ($type === 'music') ? 'music' : 'video';
        if (isset($box['display']) && $box['display'] === 'long') {
            usort($items, function ($a, $b) {
                $da = (float) ($a->duration ?? 0);
                $db = (float) ($b->duration ?? 0);
                return $db <=> $da;
            });
            $items = array_slice($items, 0, $limit);
        }
        return ['type' => $type, 'items' => $items, 'link_type' => $link_type];
    }

    return ['type' => 'music', 'items' => [], 'link_type' => 'music'];
}

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main nia-music-page container-fluid px-0 pb-4" data-pull-to-refresh>
    <div class="nia-music-pills-wrap">
        <div class="nia-chips nia-music-pills">
            <a class="nia-chip <?php echo $route_section === 'browse' ? 'active' : ''; ?>" href="<?php echo url('music'); ?>">Browse</a>
            <a class="nia-chip <?php echo $route_section === 'featured' ? 'active' : ''; ?>" href="<?php echo url('music/featured'); ?>">Featured</a>
            <a class="nia-chip <?php echo $route_section === 'most-viewed' ? 'active' : ''; ?>" href="<?php echo url('music/most-viewed'); ?>">Most viewed</a>
            <a class="nia-chip <?php echo $route_section === 'top-rated' ? 'active' : ''; ?>" href="<?php echo url('music/top-rated'); ?>">Top rated</a>
        </div>
    </div>

    <div class="nia-music-content px-2 px-md-3">
        <?php
        foreach ($boxes as $box) {
            $title = isset($box['title']) ? trim($box['title']) : '';
            $display = isset($box['display']) ? $box['display'] : 'carousel';
            $grid_size = isset($box['grid_size']) && in_array($box['grid_size'], ['small', 'medium', 'large'], true) ? $box['grid_size'] : 'medium';
            $data = nia_music_box_items($box);

            if ($data['type'] === 'html') {
                if (($data['content'] ?? '') === '') continue;
                echo '<section class="nia-music-section"><div class="nia-music-section-block">';
                if ($title !== '') echo '<h2 class="nia-music-section-title mb-3">' . _e($title) . '</h2>';
                echo $data['content'];
                echo '</div></section>';
                continue;
            }

            if ($data['type'] === 'channel') {
                $items = $data['items'];
                if (empty($items)) continue;
                $base = (get_option('channel-seo-url') ?: 'category');
                echo '<section class="nia-music-section"><div class="nia-music-section-head mb-3"><h2 class="nia-music-section-title mb-0">' . _e($title) . '</h2></div>';
                echo '<div class="nia-music-carousel-wrap"><div class="nia-music-carousel nia-music-cards-row" data-carousel>';
                foreach ($items as $c) {
                    $cslug = $c->slug ?? 'channel';
                    $clink = url($base . '/' . $cslug);
                    $cthumb = !empty($c->thumb) ? $c->thumb : '';
                    if ($cthumb !== '' && strpos($cthumb, 'http') !== 0) $cthumb = rtrim(SITE_URL, '/') . '/' . ltrim($cthumb, '/');
                    echo '<a href="' . _e($clink) . '" class="nia-music-card"><div class="nia-music-card-artwork position-relative rounded-3 overflow-hidden bg-secondary">';
                    if ($cthumb) echo '<img src="' . _e($cthumb) . '" alt="" class="w-100 h-100 object-fit-cover" loading="lazy">';
                    else echo '<span class="position-absolute top-50 start-50 translate-middle text-muted"><span class="material-icons">folder</span></span>';
                    echo '</div><div class="nia-music-card-body mt-2"><div class="nia-music-card-title text-truncate fw-semibold">' . _e($c->name ?? '') . '</div></div></a>';
                }
                echo '</div></div></section>';
                continue;
            }

            $items = isset($data['items']) ? $data['items'] : [];
            $link_type = isset($data['link_type']) ? $data['link_type'] : 'music';

            // Library placeholder when empty and logged in
            if ($data['type'] === 'library' && empty($items) && is_logged()) {
                $later_url = url('me/later');
                $hist_url = url('me/history');
                echo '<section class="nia-music-section"><div class="nia-music-section-head d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">';
                echo '<h2 class="nia-music-section-title mb-0">' . _e($title ?: 'From your library') . '</h2>';
                echo '<a href="' . _e($hist_url) . '" class="btn btn-sm btn-outline-secondary rounded-pill">More</a></div>';
                echo '<div class="nia-music-carousel-wrap"><div class="nia-music-carousel nia-music-cards-row" data-carousel>';
                echo '<a href="' . _e($later_url) . '" class="nia-music-card nia-music-card-placeholder"><div class="nia-music-card-artwork position-relative rounded-3 overflow-hidden bg-dark bg-opacity-50 d-flex align-items-center justify-content-center"><span class="material-icons text-muted" style="font-size: 2.5rem;">schedule</span></div><div class="nia-music-card-body mt-2"><div class="nia-music-card-title text-truncate fw-semibold">Watch Later</div><div class="nia-music-card-meta text-muted small">Playlist</div></div></a>';
                echo '<a href="' . _e($hist_url) . '" class="nia-music-card nia-music-card-placeholder"><div class="nia-music-card-artwork position-relative rounded-3 overflow-hidden bg-dark bg-opacity-50 d-flex align-items-center justify-content-center"><span class="material-icons text-muted" style="font-size: 2.5rem;">history</span></div><div class="nia-music-card-body mt-2"><div class="nia-music-card-title text-truncate fw-semibold">History</div><div class="nia-music-card-meta text-muted small">Recently played</div></div></a>';
                echo '</div></div></section>';
                continue;
            }

            if (empty($items)) continue;

            $section_title = $title ?: (($data['type'] === 'music') ? 'Music' : (($data['type'] === 'video') ? 'Videos' : 'Images'));
            $is_library_section = (isset($box['type']) && $box['type'] === 'library');
        ?>
        <section class="nia-music-section">
            <div class="nia-music-section-head d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h2 class="nia-music-section-title mb-0"><?php echo _e($section_title); ?></h2>
                <div class="nia-music-section-actions d-flex align-items-center gap-1">
                    <?php if ($is_library_section && is_logged()) { ?>
                    <a href="<?php echo _e(url('me/history')); ?>" class="btn btn-sm btn-outline-secondary rounded-pill nia-music-more">More</a>
                    <?php } ?>
                    <?php if ($display !== 'list' && $display !== 'long') { ?>
                    <button type="button" class="nia-music-nav-btn nia-music-nav-prev rounded-circle border-0 p-2" aria-label="Previous"><span class="material-icons">chevron_left</span></button>
                    <button type="button" class="nia-music-nav-btn nia-music-nav-next rounded-circle border-0 p-2" aria-label="Next"><span class="material-icons">chevron_right</span></button>
                    <?php } ?>
                </div>
            </div>

            <?php if ($display === 'list') { ?>
            <div class="nia-music-list-wrap">
                <div class="row g-2 nia-music-list" data-carousel>
                    <?php foreach ($items as $item) {
                        $d = nia_music_card_item($item, $link_type);
                    ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <a href="<?php echo _e($d['link']); ?>" class="nia-music-list-item d-flex align-items-center gap-3 text-decoration-none text-reset rounded-2 p-2">
                            <div class="nia-music-list-thumb rounded-2 overflow-hidden bg-secondary flex-shrink-0">
                                <img src="<?php echo _e($d['thumb'] ?: ''); ?>" alt="" class="object-fit-cover" loading="lazy" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.classList.remove('d-none');">
                                <span class="d-none w-100 h-100 align-items-center justify-content-center bg-secondary text-muted nia-music-list-fallback"><span class="material-icons">music_note</span></span>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="nia-music-list-title text-truncate fw-medium"><?php echo _e($item->title ?? ''); ?></div>
                                <div class="nia-music-list-meta text-muted small text-truncate"><?php echo _e($d['chanName']); ?> · <?php echo _e($d['viewsStr']); ?></div>
                            </div>
                            <span class="material-icons text-muted flex-shrink-0">play_circle_outline</span>
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } elseif ($display === 'long') { ?>
            <div class="nia-music-long-list">
                <div class="row g-0 nia-music-long-list-inner" data-carousel>
                    <?php foreach ($items as $item) {
                        $d = nia_music_card_item($item, $link_type);
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="<?php echo _e($d['link']); ?>" class="nia-music-long-item d-flex align-items-center gap-3 text-decoration-none text-reset rounded-2 p-2 py-2">
                            <div class="nia-music-long-thumb rounded-2 overflow-hidden bg-secondary flex-shrink-0">
                                <img src="<?php echo _e($d['thumb'] ?: ''); ?>" alt="" class="object-fit-cover" loading="lazy" onerror="this.style.display='none'">
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="nia-music-long-title text-truncate fw-medium"><?php echo _e($item->title ?? ''); ?></div>
                                <div class="nia-music-long-meta text-muted small text-truncate"><?php echo _e($d['chanName']); ?></div>
                            </div>
                            <span class="nia-music-long-duration text-muted small flex-shrink-0"><?php echo _e($d['duration']); ?></span>
                            <span class="material-icons text-muted flex-shrink-0">play_circle_outline</span>
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } elseif ($display === 'large') { ?>
            <div class="nia-music-carousel-wrap">
                <div class="nia-music-carousel nia-music-cards-row nia-music-cards-large nia-grid--<?php echo _e($grid_size); ?>" data-carousel>
                    <?php foreach ($items as $item) {
                        $d = nia_music_card_item($item, $link_type);
                    ?>
                    <a href="<?php echo _e($d['link']); ?>" class="nia-music-card">
                        <div class="nia-music-card-artwork position-relative rounded-3 overflow-hidden bg-secondary aspect-ratio-square">
                            <img src="<?php echo _e($d['thumb'] ?: ''); ?>" alt="" class="w-100 h-100 object-fit-cover" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                            <span class="nia-music-card-fallback d-none position-absolute top-50 start-50 translate-middle text-muted"><span class="material-icons">music_note</span></span>
                            <span class="nia-music-card-play position-absolute bottom-0 end-0 m-2 rounded-circle bg-primary d-flex align-items-center justify-content-center nia-music-card-play-small"><span class="material-icons text-white">play_arrow</span></span>
                        </div>
                        <div class="nia-music-card-body mt-2">
                            <div class="nia-music-card-title text-truncate fw-semibold"><?php echo _e($item->title ?? ''); ?></div>
                            <div class="nia-music-card-meta text-muted small text-truncate"><?php echo _e($d['chanName']); ?></div>
                        </div>
                    </a>
                    <?php } ?>
                </div>
            </div>
            <?php } else { ?>
            <div class="nia-music-carousel-wrap">
                <div class="nia-music-carousel nia-music-cards-row nia-grid--<?php echo _e($grid_size); ?>" data-carousel>
                    <?php foreach ($items as $item) {
                        $d = nia_music_card_item($item, $link_type);
                    ?>
                    <a href="<?php echo _e($d['link']); ?>" class="nia-music-card">
                        <div class="nia-music-card-artwork position-relative rounded-3 overflow-hidden bg-secondary">
                            <img src="<?php echo _e($d['thumb'] ?: ''); ?>" alt="" class="w-100 h-100 object-fit-cover" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                            <span class="nia-music-card-fallback d-none position-absolute top-50 start-50 translate-middle text-muted"><span class="material-icons">music_note</span></span>
                            <span class="nia-music-card-play position-absolute top-50 start-50 translate-middle rounded-circle bg-dark bg-opacity-75 d-flex align-items-center justify-content-center"><span class="material-icons text-white">play_arrow</span></span>
                        </div>
                        <div class="nia-music-card-body mt-2">
                            <div class="nia-music-card-title text-truncate fw-semibold"><?php echo _e($item->title ?? ''); ?></div>
                            <div class="nia-music-card-meta text-muted small text-truncate"><?php echo _e($d['chanName']); ?> <?php if ($d['viewsStr'] !== '0 views') { ?> · <?php echo _e($d['viewsStr']); ?><?php } ?></div>
                        </div>
                    </a>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </section>
        <?php
        }
        ?>

        <?php
        $has_any = false;
        foreach ($boxes as $box) {
            $d = nia_music_box_items($box);
            if ($d['type'] === 'html') { $has_any = true; break; }
            if (isset($d['items']) && count($d['items']) > 0) { $has_any = true; break; }
            if ($d['type'] === 'library' && is_logged()) { $has_any = true; break; }
        }
        if (!$has_any) {
        ?>
        <p class="text-muted text-center py-5 px-3">No music yet. <a href="<?php echo url('share?page=share-music'); ?>" class="text-decoration-underline">Add music</a>. <?php if (function_exists('is_moderator') && is_moderator()) { ?>Configure sections in <a href="<?php echo _e(url((defined('ADMINCP') ? ADMINCP : 'moderator') . '/music-page')); ?>">Admin → Music page builder</a>.<?php } ?></p>
        <?php } ?>
    </div>
</main>
<script>
(function() {
    document.querySelectorAll('.nia-music-carousel-wrap').forEach(function(wrap) {
        var carousel = wrap.querySelector('[data-carousel]');
        var prev = wrap.closest('.nia-music-section')?.querySelector('.nia-music-nav-prev');
        var next = wrap.closest('.nia-music-section')?.querySelector('.nia-music-nav-next');
        if (!carousel || (!prev && !next)) return;
        var step = 280;
        if (wrap.querySelector('.nia-music-cards-large')) step = 200;
        if (prev) prev.addEventListener('click', function() { carousel.scrollBy({ left: -step, behavior: 'smooth' }); });
        if (next) next.addEventListener('click', function() { carousel.scrollBy({ left: step, behavior: 'smooth' }); });
    });
})();
</script>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
