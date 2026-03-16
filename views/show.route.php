<?php
/**
 * Global search (/show/): video grid, pictures grid, channels & playlists cards.
 * Type filter: all, videos, music, pictures, channels, playlists. Bootstrap + icons.
 */
if (!defined('in_nia_app')) exit;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
if (!in_array($type, ['all', 'videos', 'music', 'pictures', 'channels', 'playlists'], true)) {
    $type = 'all';
}
$modview = 'show';
$page_title = $q !== '' ? 'Search: ' . _e($q) : 'Search';
$results = $q !== '' ? search_global($q, $type, 24, 0) : ['videos' => [], 'images' => [], 'channels' => [], 'playlists' => []];
$site_url = rtrim(SITE_URL, '/');
$total_count = count($results['videos'] ?? []) + count($results['images'] ?? []) + count($results['channels'] ?? []) + count($results['playlists'] ?? []);

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4" data-pull-to-refresh>
    <h1 class="nia-title mb-4 d-flex align-items-center gap-2">
        <span class="material-icons text-primary">search</span>
        Search
    </h1>

    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo url('show'); ?>" class="row g-3 align-items-end">
                <div class="col-12 col-md-6 col-lg-5 position-relative">
                    <label for="show-q" class="form-label small text-muted d-flex align-items-center gap-1">
                        <span class="material-icons" style="font-size:1rem;">edit</span> Keyword
                    </label>
                    <input type="text" class="form-control bg-dark border-secondary text-light" id="show-q" name="q" value="<?php echo _e($q); ?>" placeholder="Videos, music, pictures, channels, playlists..." autocomplete="off" data-livesearch>
                    <div id="nia-livesearch-dropdown" class="position-absolute start-0 end-0 top-100 mt-1 bg-dark border border-secondary rounded shadow-lg p-2 d-none" style="z-index:1050; max-height: 320px; overflow-y: auto;"></div>
                </div>
                <div class="col-8 col-md-4 col-lg-3">
                    <label for="show-type" class="form-label small text-muted d-flex align-items-center gap-1">
                        <span class="material-icons" style="font-size:1rem;">filter_list</span> Type
                    </label>
                    <select class="form-select bg-dark border-secondary text-light" id="show-type" name="type">
                        <option value="all"<?php echo $type === 'all' ? ' selected' : ''; ?>>All</option>
                        <option value="videos"<?php echo $type === 'videos' ? ' selected' : ''; ?>>Videos</option>
                        <option value="music"<?php echo $type === 'music' ? ' selected' : ''; ?>>Music</option>
                        <option value="pictures"<?php echo $type === 'pictures' ? ' selected' : ''; ?>>Pictures</option>
                        <option value="channels"<?php echo $type === 'channels' ? ' selected' : ''; ?>>Channels</option>
                        <option value="playlists"<?php echo $type === 'playlists' ? ' selected' : ''; ?>>Playlists</option>
                    </select>
                </div>
                <div class="col-4 col-md-2">
                    <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-1">
                        <span class="material-icons" style="font-size:1.2rem;">search</span>
                        <span class="d-none d-sm-inline">Search</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($q !== '') { ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
        <span class="badge bg-secondary rounded-pill d-flex align-items-center gap-1">
            <span class="material-icons" style="font-size:1rem;">info</span>
            <?php echo (int) $total_count; ?> result<?php echo $total_count !== 1 ? 's' : ''; ?> for &quot;<?php echo _e($q); ?>&quot;
        </span>
        <div class="nia-chips">
            <a class="nia-chip <?php echo $type === 'all' ? 'active' : ''; ?>" href="<?php echo url('show'); ?>?q=<?php echo rawurlencode($q); ?>&type=all">All</a>
            <a class="nia-chip <?php echo $type === 'videos' ? 'active' : ''; ?>" href="<?php echo url('show'); ?>?q=<?php echo rawurlencode($q); ?>&type=videos">Videos</a>
            <a class="nia-chip <?php echo $type === 'music' ? 'active' : ''; ?>" href="<?php echo url('show'); ?>?q=<?php echo rawurlencode($q); ?>&type=music">Music</a>
            <a class="nia-chip <?php echo $type === 'pictures' ? 'active' : ''; ?>" href="<?php echo url('show'); ?>?q=<?php echo rawurlencode($q); ?>&type=pictures">Pictures</a>
            <a class="nia-chip <?php echo $type === 'channels' ? 'active' : ''; ?>" href="<?php echo url('show'); ?>?q=<?php echo rawurlencode($q); ?>&type=channels">Channels</a>
            <a class="nia-chip <?php echo $type === 'playlists' ? 'active' : ''; ?>" href="<?php echo url('show'); ?>?q=<?php echo rawurlencode($q); ?>&type=playlists">Playlists</a>
        </div>
    </div>

    <div class="nia-search-results">
        <?php if (!empty($results['videos'])) { ?>
        <section class="mb-4">
            <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                <span class="material-icons">videocam</span> Videos &amp; Music
            </h2>
            <div class="nia-video-grid">
                <?php foreach ($results['videos'] as $v) {
                    $v = is_array($v) ? (object) $v : $v;
                    $link = function_exists('media_play_url') ? media_play_url($v->id, $v->type ?? 'video', $v->title ?? '') : watch_url($v->id);
                    $thumb = !empty($v->thumb) ? $v->thumb : '';
                    if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
                    $duration = function_exists('nia_duration') ? nia_duration($v->duration ?? 0) : '';
                    $timeAgo = function_exists('nia_time_ago') ? nia_time_ago($v->created_at ?? null) : '';
                    $views = (int) ($v->views ?? 0);
                    $viewsStr = $views >= 1000000 ? round($views / 1000000, 1) . 'M' : ($views >= 1000 ? round($views / 1000, 1) . 'K' : $views) . ' views';
                    $chanName = !empty($v->channel_name) ? $v->channel_name : (!empty($v->channel_username) ? $v->channel_username : '');
                    $channel = isset($v->user_id) ? get_user($v->user_id) : null;
                    if (!$chanName && $channel) $chanName = $channel->username ?? $channel->name ?? '';
                    $avatar = $channel && !empty($channel->avatar) ? $channel->avatar : '';
                    if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = $site_url . '/' . ltrim($avatar, '/');
                    $initial = $chanName ? strtoupper(substr($chanName, 0, 1)) : '?';
                    $isMusic = isset($v->type) && $v->type === 'music';
                ?>
                <a href="<?php echo _e($link); ?>" class="nia-video-card">
                    <div class="nia-video-thumb-wrap">
                        <img class="nia-video-thumb" src="<?php echo _e($thumb ?: ''); ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                        <?php if ($duration !== '') { ?><span class="nia-video-duration"><?php echo _e($duration); ?></span><?php } ?>
                        <?php if ($isMusic) { ?><span class="position-absolute top-0 start-0 m-1 badge bg-info">Music</span><?php } ?>
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
                <?php } ?>
            </div>
        </section>
        <?php } ?>

        <?php if (!empty($results['images'])) { ?>
        <section class="mb-4">
            <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                <span class="material-icons">image</span> Pictures
            </h2>
            <div class="row g-3">
                <?php foreach ($results['images'] as $img) {
                    $img = is_array($img) ? (object) $img : $img;
                    $link = function_exists('view_url') ? view_url($img->id, $img->title ?? '') : image_url($img->id, $img->title ?? '');
                    $thumb = !empty($img->thumb) ? $img->thumb : ($img->path ?? '');
                    if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
                ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <a href="<?php echo _e($link); ?>" class="card bg-dark border-secondary text-decoration-none text-reset h-100">
                        <img src="<?php echo _e($thumb ?: ''); ?>" class="card-img-top rounded-0" alt="" style="aspect-ratio:1;object-fit:cover;" loading="lazy" onerror="this.style.display='none'">
                        <div class="card-body p-2">
                            <div class="nia-video-title small text-truncate" title="<?php echo _e($img->title ?? ''); ?>"><?php echo _e($img->title ?? ''); ?></div>
                        </div>
                    </a>
                </div>
                <?php } ?>
            </div>
        </section>
        <?php } ?>

        <?php if (!empty($results['channels'])) { ?>
        <section class="mb-4">
            <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                <span class="material-icons">people</span> Channels
            </h2>
            <div class="row g-3">
                <?php foreach ($results['channels'] as $u) {
                    $u = is_array($u) ? (object) $u : $u;
                    $prof_url = profile_url($u->username ?? '', $u->id ?? 0);
                    $name = $u->name ?? $u->username ?? '';
                    $avatar = $u->avatar ?? '';
                    if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = $site_url . '/' . ltrim($avatar, '/');
                ?>
                <div class="col-12 col-sm-6 col-md-4">
                    <a href="<?php echo _e($prof_url); ?>" class="card bg-dark border-secondary text-decoration-none text-light hover-border-primary d-flex flex-row align-items-center gap-3 p-3">
                        <?php if ($avatar) { ?><img src="<?php echo _e($avatar); ?>" alt="" class="rounded-circle flex-shrink-0" width="48" height="48" style="object-fit:cover"><?php } else { ?><span class="rounded-circle bg-secondary d-flex align-items-center justify-content-center flex-shrink-0 text-white fw-bold" style="width:48px;height:48px"><?php echo _e(strtoupper(substr($u->username ?? '?', 0, 1))); ?></span><?php } ?>
                        <div class="min-width-0">
                            <div class="fw-semibold text-truncate"><?php echo _e($name); ?></div>
                            <div class="small text-muted">@<?php echo _e($u->username ?? ''); ?></div>
                        </div>
                        <span class="material-icons text-muted ms-auto">chevron_right</span>
                    </a>
                </div>
                <?php } ?>
            </div>
        </section>
        <?php } ?>

        <?php if (!empty($results['playlists'])) { ?>
        <section class="mb-4">
            <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                <span class="material-icons">playlist_play</span> Playlists
            </h2>
            <ul class="list-group list-group-flush bg-transparent">
                <?php foreach ($results['playlists'] as $pl) {
                    $pl = is_array($pl) ? (object) $pl : $pl;
                    $owner = get_user($pl->user_id ?? 0);
                    $by = $owner ? (' @' . ($owner->username ?? '')) : '';
                    $pl_url = playlist_url($pl->slug ?? '', $pl->id ?? 0);
                ?>
                <li class="list-group-item bg-dark border-secondary">
                    <a href="<?php echo _e($pl_url); ?>" class="d-flex align-items-center gap-3 text-decoration-none text-light">
                        <span class="material-icons text-warning flex-shrink-0">playlist_play</span>
                        <div class="min-width-0 flex-grow-1">
                            <span class="fw-semibold"><?php echo _e($pl->name ?? ''); ?></span>
                            <span class="small text-muted"><?php echo _e($by); ?></span>
                        </div>
                        <span class="material-icons text-muted">open_in_new</span>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </section>
        <?php } ?>

        <?php
        $any = !empty($results['videos']) || !empty($results['images']) || !empty($results['channels']) || !empty($results['playlists']);
        if (!$any) {
            ?>
            <div class="card bg-dark border-secondary text-center py-5">
                <div class="card-body">
                    <span class="material-icons text-muted mb-2" style="font-size:3rem;">search_off</span>
                    <p class="text-muted mb-0">No results for &quot;<?php echo _e($q); ?>&quot;. Try another keyword or change the type filter.</p>
                </div>
            </div>
            <?php
        }
        ?>
    </div>

    <?php } else { ?>
    <div class="row g-4">
        <div class="col-12">
            <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                <span class="material-icons">explore</span> Explore
            </h2>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="<?php echo url('imgsearch'); ?>" class="card bg-dark border-secondary text-decoration-none text-light hover-border-primary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="material-icons text-info" style="font-size:2.5rem;">image</span>
                    <div>
                        <div class="fw-semibold">Image search</div>
                        <small class="text-muted">Search pictures by keyword</small>
                    </div>
                    <span class="material-icons ms-auto text-muted">chevron_right</span>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="<?php echo url('pplsearch'); ?>" class="card bg-dark border-secondary text-decoration-none text-light hover-border-primary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="material-icons text-primary" style="font-size:2.5rem;">people</span>
                    <div>
                        <div class="fw-semibold">People search</div>
                        <small class="text-muted">Find channels and users</small>
                    </div>
                    <span class="material-icons ms-auto text-muted">chevron_right</span>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="<?php echo url('playlistsearch'); ?>" class="card bg-dark border-secondary text-decoration-none text-light hover-border-primary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="material-icons text-warning" style="font-size:2.5rem;">playlist_play</span>
                    <div>
                        <div class="fw-semibold">Playlist search</div>
                        <small class="text-muted">Browse playlists</small>
                    </div>
                    <span class="material-icons ms-auto text-muted">chevron_right</span>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="<?php echo url('videos'); ?>" class="card bg-dark border-secondary text-decoration-none text-light hover-border-primary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="material-icons text-success" style="font-size:2.5rem;">videocam</span>
                    <div>
                        <div class="fw-semibold">Videos</div>
                        <small class="text-muted">Browse video grid</small>
                    </div>
                    <span class="material-icons ms-auto text-muted">chevron_right</span>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="<?php echo url('music'); ?>" class="card bg-dark border-secondary text-decoration-none text-light hover-border-primary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="material-icons text-info" style="font-size:2.5rem;">music_note</span>
                    <div>
                        <div class="fw-semibold">Music</div>
                        <small class="text-muted">Browse music</small>
                    </div>
                    <span class="material-icons ms-auto text-muted">chevron_right</span>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="<?php echo url('images'); ?>" class="card bg-dark border-secondary text-decoration-none text-light hover-border-primary h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="material-icons text-secondary" style="font-size:2.5rem;">collections</span>
                    <div>
                        <div class="fw-semibold">Images</div>
                        <small class="text-muted">Browse pictures</small>
                    </div>
                    <span class="material-icons ms-auto text-muted">chevron_right</span>
                </div>
            </a>
        </div>
    </div>
    <p class="text-muted small mt-4">Enter a keyword above and choose a type, or use the explore cards to jump to dedicated search and browse.</p>
    <?php } ?>
</main>
<script>
(function(){
    var input = document.querySelector('#show-q[data-livesearch]');
    var dropdown = document.getElementById('nia-livesearch-dropdown');
    var base = '<?php echo url('app/ajax/livesearch.php'); ?>';
    var timer;
    if (!input || !dropdown) return;
    input.addEventListener('input', function(){
        var q = this.value.trim();
        clearTimeout(timer);
        if (q.length < 1) { dropdown.classList.add('d-none'); return; }
        timer = setTimeout(function(){
            fetch(base + '?q=' + encodeURIComponent(q))
                .then(function(r){ return r.json(); })
                .then(function(d){
                    var html = '';
                    if (d.videos && d.videos.length) { html += '<div class="small text-muted mb-1">Videos</div>'; d.videos.forEach(function(v){ html += '<a class="d-block text-truncate text-light text-decoration-none py-1" href="' + (v.url || '#') + '">' + (v.title || '') + '</a>'; }); }
                    if (d.music && d.music.length) { html += '<div class="small text-muted mb-1 mt-2">Music</div>'; d.music.forEach(function(m){ html += '<a class="d-block text-truncate text-light text-decoration-none py-1" href="' + (m.url || '#') + '">' + (m.title || '') + '</a>'; }); }
                    if (d.channels && d.channels.length) { html += '<div class="small text-muted mb-1 mt-2">Channels</div>'; d.channels.forEach(function(c){ html += '<a class="d-block text-truncate text-light text-decoration-none py-1" href="' + (c.url || '#') + '">' + (c.name || c.username || '') + '</a>'; }); }
                    if (d.playlists && d.playlists.length) { html += '<div class="small text-muted mb-1 mt-2">Playlists</div>'; d.playlists.forEach(function(p){ html += '<a class="d-block text-truncate text-light text-decoration-none py-1" href="' + (p.url || '#') + '">' + (p.name || '') + '</a>'; }); }
                    dropdown.innerHTML = html || '<span class="text-muted small">No suggestions</span>';
                    dropdown.classList.remove('d-none');
                });
        }, 200);
    });
    input.addEventListener('blur', function(){ setTimeout(function(){ dropdown.classList.add('d-none'); }, 150); });
})();
</script>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
