<?php
if (!defined('in_nia_app')) exit;
if (!is_logged()) {
    redirect(url('login'));
}
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$section = $nia_section !== '' ? $nia_section : 'library';
$page_title = 'Library';
$uid = current_user_id();
global $db;
$pre = $db->prefix();

// Handle remove import source from Auto-import page (POST to me/auto-import)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_import_source' && function_exists('nia_youtube_remove_import_source')) {
    $rid = isset($_POST['source_id']) ? (int) $_POST['source_id'] : 0;
    if ($rid > 0) nia_youtube_remove_import_source($uid, $rid);
    redirect(url('me/auto-import') . '?removed=1');
}

// Handle add YouTube channel (from Channel URL block on auto-import page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'youtube_channel' && function_exists('nia_youtube_parse_channel_id') && function_exists('nia_youtube_add_import_source')) {
    $channel_url = isset($_POST['channel_url']) ? trim($_POST['channel_url']) : '';
    $auto_import = isset($_POST['auto_import']) ? 1 : 0;
    $channel_id = nia_youtube_parse_channel_id($channel_url);
        if ($channel_id) {
            $source_db_id = nia_youtube_add_import_source($uid, 'channel', $channel_id, $auto_import);
            if ($source_db_id > 0 && function_exists('nia_youtube_refresh_source_display')) {
                nia_youtube_refresh_source_display($source_db_id);
            }
            $initial_count = 0;
            if ($source_db_id > 0 && function_exists('nia_youtube_process_source')) {
                $initial_max = defined('NIA_YOUTUBE_SYNC_MAX_PER_RUN') ? NIA_YOUTUBE_SYNC_MAX_PER_RUN : 1000;
                $initial_count = nia_youtube_process_source($source_db_id, $initial_max);
            }
        $total_from_channel = $initial_count;
        if ($source_db_id > 0 && function_exists('nia_youtube_get_source')) {
            $src = nia_youtube_get_source($source_db_id);
            if ($src && isset($src->total_imported)) $total_from_channel = (int) $src->total_imported;
        }
        redirect(url('me/auto-import') . '?added=1&count=' . (int) $total_from_channel . '&initial=' . (int) $initial_count);
    } else {
        $api_err = function_exists('nia_youtube_last_api_error') ? nia_youtube_last_api_error() : null;
        $_SESSION['auto_import_channel_error'] = $api_err ? ('YouTube API: ' . $api_err) : 'Enter a valid YouTube channel URL (e.g. https://www.youtube.com/channel/UC... or https://www.youtube.com/@username).';
        redirect(url('me/auto-import') . '?channel_error=1');
    }
}

// Handle sync (user syncs their own Auto-import sources)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && function_exists('nia_youtube_process_source') && function_exists('nia_youtube_get_import_sources')) {
    if ($_POST['action'] === 'sync_source') {
        $sid = isset($_POST['source_id']) ? (int) $_POST['source_id'] : 0;
        $want_json = !empty($_POST['sync_ajax']);
        $chunk_size = isset($_POST['sync_chunk_size']) ? (int) $_POST['sync_chunk_size'] : 0;
        if ($sid > 0) {
            $sources = nia_youtube_get_import_sources($uid);
            $owned = false;
            foreach ($sources as $s) {
                if ((int)(is_array($s) ? $s['id'] : $s->id) === $sid) { $owned = true; break; }
            }
            if ($owned) {
                $max_per_run = defined('NIA_YOUTUBE_SYNC_MAX_PER_RUN') ? NIA_YOUTUBE_SYNC_MAX_PER_RUN : 1000;
                if ($chunk_size > 0) $max_per_run = min($chunk_size, $max_per_run);
                $count = nia_youtube_process_source($sid, $max_per_run);
                if ($want_json) {
                    $src = function_exists('nia_youtube_get_source') ? nia_youtube_get_source($sid) : null;
                    $total_imported = $src && isset($src->total_imported) ? (int) $src->total_imported : 0;
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => true, 'imported' => (int) $count, 'total_imported' => $total_imported]);
                    exit;
                }
                redirect(url('me/auto-import') . '?synced=1&count=' . (int) $count);
            }
        }
        if ($want_json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'imported' => 0, 'total_imported' => 0]);
            exit;
        }
        redirect(url('me/auto-import'));
    }
    if ($_POST['action'] === 'sync_all') {
        $sources = nia_youtube_get_import_sources($uid);
        $total = 0;
        $max_per_run = defined('NIA_YOUTUBE_SYNC_MAX_PER_RUN') ? NIA_YOUTUBE_SYNC_MAX_PER_RUN : 1000;
        foreach ($sources as $s) {
            $sid = (int)(is_array($s) ? $s['id'] : $s->id);
            if ($sid > 0) $total += nia_youtube_process_source($sid, $max_per_run);
        }
        redirect(url('me/auto-import') . '?synced_all=1&count=' . (int) $total);
    }
}

$nav = [
    'library'     => ['label' => 'Library', 'url' => url('me'), 'icon' => 'folder'],
    'later'       => ['label' => 'Watch later', 'url' => url('me/later'), 'icon' => 'schedule'],
    'history'     => ['label' => 'History', 'url' => url('me/history'), 'icon' => 'history'],
    'likes'       => ['label' => 'Likes', 'url' => url('me/likes'), 'icon' => 'thumb_up'],
    'playlists'   => ['label' => 'Playlists', 'url' => url('me/playlists'), 'icon' => 'playlist_play'],
    'auto-import' => ['label' => 'Auto-import', 'url' => url('me/auto-import'), 'icon' => 'sync'],
    'manage'      => ['label' => 'Manage', 'url' => url('me/manage'), 'icon' => 'tune'],
];

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Library</h1>
    <ul class="nav nav-pills mb-4 flex-wrap gap-2">
        <?php foreach ($nav as $k => $n) {
            $active = ($section === $k || ($k === 'auto-import' && strpos($nia_section, 'auto-import') === 0)) ? ' active' : '';
        ?>
        <li class="nav-item">
            <a class="nav-link<?php echo $active; ?> bg-dark border-secondary text-light d-inline-flex align-items-center gap-2" href="<?php echo _e($n['url']); ?>">
                <span class="material-icons" style="font-size:1.1rem;"><?php echo _e($n['icon'] ?? 'circle'); ?></span>
                <?php echo _e($n['label']); ?>
            </a>
        </li>
        <?php } ?>
    </ul>
    <div class="nia-me-content">
        <?php
        $auto_import_log_id = 0;
        if (preg_match('#^auto-import/log/(\d+)$#', $section, $m)) {
            $section = 'auto-import';
            $auto_import_log_id = (int) $m[1];
        }
        if ($section === 'auto-import') {
            $import_sources = function_exists('nia_youtube_get_import_sources') ? nia_youtube_get_import_sources($uid) : [];
            if ($auto_import_log_id > 0) {
                $log_source = function_exists('nia_youtube_get_source') ? nia_youtube_get_source($auto_import_log_id) : null;
                $can_view = $log_source && ((int) $log_source->user_id === $uid || (function_exists('is_moderator') && is_moderator()));
                if ($can_view && function_exists('nia_youtube_get_import_log')) {
                    $log_entries = nia_youtube_get_import_log($auto_import_log_id, 200);
                    $log_channel_name = !empty($log_source->channel_name) ? $log_source->channel_name : ('Channel ' . _e($log_source->value ?? ''));
                    ?>
                    <div class="mb-4">
                        <a href="<?php echo url('me/auto-import'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><span class="material-icons align-middle" style="font-size:1rem;">arrow_back</span> Back to Auto-import</a>
                        <h2 class="h5 mb-3">Import log: <?php echo _e($log_channel_name); ?></h2>
                        <p class="text-muted small">Videos imported from this channel (newest first).</p>
                        <ul class="list-group list-group-flush bg-transparent">
                            <?php foreach ($log_entries as $log) {
                                $log = is_array($log) ? (object) $log : $log;
                                $watch_link = function_exists('watch_url') ? watch_url($log->video_id) : url('watch/' . $log->video_id);
                            ?>
                            <li class="list-group-item bg-dark border-secondary d-flex align-items-center justify-content-between">
                                <a href="<?php echo _e($watch_link); ?>" class="text-decoration-none text-light"><?php echo _e($log->video_title ?? 'Video #' . $log->video_id); ?></a>
                                <span class="text-muted small"><?php echo function_exists('nia_time_ago') ? nia_time_ago($log->created_at ?? '') : ($log->created_at ?? ''); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php if (empty($log_entries)) { ?><p class="text-muted">No import log yet.</p><?php } ?>
                    </div>
                    <?php
                } else {
                    echo '<p class="text-muted">Log not found or access denied.</p>';
                    echo '<a href="' . url('me/auto-import') . '" class="btn btn-sm btn-outline-secondary">Back to Auto-import</a>';
                }
            } else {
                $removed_msg = isset($_GET['removed']) && $_GET['removed'] === '1';
                $synced_msg = isset($_GET['synced']) && $_GET['synced'] === '1';
                $synced_count = isset($_GET['count']) ? (int) $_GET['count'] : 0;
                $synced_all_msg = isset($_GET['synced_all']) && $_GET['synced_all'] === '1';
                $channel_added_msg = isset($_GET['added']) && $_GET['added'] === '1';
                $channel_added_initial = isset($_GET['initial']) ? (int) $_GET['initial'] : 0;
                $channel_added_total = isset($_GET['count']) ? (int) $_GET['count'] : 0;
                $channel_error_msg = isset($_GET['channel_error']) && $_GET['channel_error'] === '1' && !empty($_SESSION['auto_import_channel_error']);
                if ($channel_error_msg) { $channel_error_text = $_SESSION['auto_import_channel_error']; unset($_SESSION['auto_import_channel_error']); }
                ?>
                <!-- Add channel block (top) -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-body">
                        <p class="text-muted small mb-3">Add a YouTube channel. Enable Auto-import to automatically import new videos when the channel uploads (run via cron or Admin).</p>
                        <form method="post" action="<?php echo url('me/auto-import'); ?>">
                            <input type="hidden" name="action" value="youtube_channel">
                            <div class="mb-2">
                                <label class="form-label">Channel URL</label>
                                <input type="url" class="form-control form-control-sm bg-dark border-secondary text-light" name="channel_url" placeholder="https://www.youtube.com/channel/UC... or https://www.youtube.com/@username" style="max-height: 2.25rem;">
                            </div>
                            <div class="mb-2 form-check">
                                <input class="form-check-input" type="checkbox" name="auto_import" id="auto_import_ch" value="1" checked>
                                <label class="form-check-label small" for="auto_import_ch">Auto-import new videos when channel adds them on YouTube</label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Add channel</button>
                        </form>
                    </div>
                </div>
                <div class="mb-4">
                    <?php if ($channel_added_msg): ?><div class="alert alert-success mb-3 py-2 small">Channel added. <?php echo $channel_added_initial; ?> video(s) imported now. Total from this channel: <?php echo $channel_added_total; ?>. New videos will be auto-imported when you run Sync or cron.</div><?php endif; ?>
                    <?php if ($channel_error_msg): ?><div class="alert alert-danger mb-3 py-2 small"><?php echo _e($channel_error_text); ?></div><?php endif; ?>
                    <?php if ($removed_msg): ?><div class="alert alert-success mb-3 py-2 small">Import source removed.</div><?php endif; ?>
                    <?php if ($synced_msg): ?><div class="alert alert-success mb-3 py-2 small">Sync completed. <?php echo $synced_count; ?> new video(s) imported.</div><?php endif; ?>
                    <?php if ($synced_all_msg): ?><div class="alert alert-success mb-3 py-2 small">Sync all completed. <?php echo $synced_count; ?> new video(s) imported from your channels.</div><?php endif; ?>
                    <h2 class="h5 mb-3">Your import sources</h2>
                    <p class="text-muted small">Channels you added for importing. Use <strong>Sync</strong> to fetch new videos now, or rely on cron / Admin.</p>
                    <p class="text-muted small mb-0">Up to 1,000 videos per Sync; click Sync again to import more for large channels.</p>
                </div>
                <?php if (!empty($import_sources)) { ?>
                <div class="mb-3">
                    <form method="post" action="<?php echo url('me/auto-import'); ?>" class="d-inline">
                        <input type="hidden" name="action" value="sync_all">
                        <button type="submit" class="btn btn-primary btn-sm"><span class="material-icons align-middle" style="font-size:1rem;">sync</span> Sync all channels</button>
                    </form>
                </div>
                <ul class="list-group list-group-flush bg-transparent">
                    <?php foreach ($import_sources as $src) {
                        $src = is_array($src) ? (object) $src : $src;
                        if (($src->type ?? '') === 'channel' && function_exists('nia_youtube_refresh_source_display') && (empty($src->channel_name) || (($src->yt_video_count ?? null) === null))) {
                            nia_youtube_refresh_source_display($src->id);
                            $src = function_exists('nia_youtube_get_source') ? nia_youtube_get_source($src->id) : $src;
                            if (is_array($src)) $src = (object) $src;
                        }
                        $label = !empty($src->channel_name) ? $src->channel_name : ($src->type . ': ' . _e($src->value ?? ''));
                        $total = (int) ($src->total_imported ?? 0);
                        $yt_total = isset($src->yt_video_count) ? (int) $src->yt_video_count : null;
                        $count_str = $total . ' video(s) imported';
                        if ($yt_total !== null && $yt_total >= 0) $count_str .= ' of ' . number_format($yt_total) . ' on YouTube';
                        $count_str .= ' · ' . (!empty($src->auto_import) ? 'Auto-import on' : 'Manual') . ' · Last: ' . ($src->last_imported_at ? (function_exists('nia_time_ago') ? nia_time_ago($src->last_imported_at) : $src->last_imported_at) : 'Never');
                        $log_url = url('me/auto-import/log/' . $src->id);
                        $yt_total_attr = ($yt_total !== null && $yt_total >= 0) ? (int) $yt_total : 0;
                    ?>
                    <li class="list-group-item bg-dark border-secondary nia-autoimport-row" data-source-id="<?php echo (int) $src->id; ?>" data-total-imported="<?php echo (int) $total; ?>" data-yt-total="<?php echo $yt_total_attr; ?>">
                        <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                            <span class="material-icons text-warning">video_library</span>
                            <div class="min-w-0 flex-grow-1">
                                <strong><?php echo _e($label); ?></strong>
                                <span class="text-muted small d-block nia-autoimport-status"><?php echo _e($count_str); ?></span>
                                <div class="nia-autoimport-progress-wrap mt-2 d-none" style="max-width: 280px;">
                                    <div class="progress bg-secondary" style="height: 8px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="small text-muted mt-1 nia-autoimport-progress-text">0 of 0</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <form method="post" action="<?php echo url('me/auto-import'); ?>" class="d-inline nia-sync-form">
                                <input type="hidden" name="action" value="sync_source">
                                <input type="hidden" name="source_id" value="<?php echo (int) $src->id; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success nia-sync-btn" title="Fetch new videos now"><span class="material-icons align-middle" style="font-size:1rem;">sync</span> Sync</button>
                            </form>
                            <a href="<?php echo _e($log_url); ?>" class="btn btn-sm btn-outline-primary">View log</a>
                            <form method="post" action="<?php echo url('me/auto-import'); ?>" class="d-inline" onsubmit="return confirm('Remove this channel from auto-import?');">
                                <input type="hidden" name="action" value="remove_import_source">
                                <input type="hidden" name="source_id" value="<?php echo (int) $src->id; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </li>
                    <?php } ?>
                </ul>
                <script>
                (function(){
                    var syncUrl = <?php echo json_encode(url('me/auto-import')); ?>;
                    var chunkSize = 200;
                    var maxPerSync = <?php echo defined('NIA_YOUTUBE_SYNC_MAX_PER_RUN') ? (int)NIA_YOUTUBE_SYNC_MAX_PER_RUN : 1000; ?>;
                    document.querySelectorAll('.nia-sync-form').forEach(function(form){
                        form.addEventListener('submit', function(e){
                            e.preventDefault();
                            var row = form.closest('.nia-autoimport-row');
                            if (!row) return;
                            var sourceId = row.getAttribute('data-source-id');
                            var ytTotal = parseInt(row.getAttribute('data-yt-total') || '0', 10);
                            var wrap = row.querySelector('.nia-autoimport-progress-wrap');
                            var bar = row.querySelector('.nia-autoimport-progress-wrap .progress-bar');
                            var textEl = row.querySelector('.nia-autoimport-progress-text');
                            var btn = form.querySelector('.nia-sync-btn');
                            if (!wrap || !bar || !textEl || !sourceId) return;
                            var startTotal = parseInt(row.getAttribute('data-total-imported') || '0', 10);
                            if (btn) btn.disabled = true;
                            wrap.classList.remove('d-none');
                            function updateProgress(totalImported){
                                var total = ytTotal > 0 ? ytTotal : totalImported;
                                var pct = total > 0 ? Math.min(100, Math.round((totalImported / total) * 100)) : 0;
                                bar.style.width = pct + '%';
                                bar.setAttribute('aria-valuenow', pct);
                                textEl.textContent = totalImported.toLocaleString() + ' of ' + (total > 0 ? total.toLocaleString() : '?') + ' imported';
                                row.setAttribute('data-total-imported', totalImported);
                            }
                            function doChunk(){
                                var fd = new FormData();
                                fd.append('action', 'sync_source');
                                fd.append('source_id', sourceId);
                                fd.append('sync_ajax', '1');
                                fd.append('sync_chunk_size', chunkSize);
                                fetch(syncUrl, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                    .then(function(r){ return r.json(); })
                                    .then(function(j){
                                        var totalImported = j.hasOwnProperty('total_imported') ? j.total_imported : (parseInt(row.getAttribute('data-total-imported'), 10) + (j.imported || 0));
                                        updateProgress(totalImported);
                                        var addedThisRun = totalImported - startTotal;
                                        var done = (j.imported === 0) || (ytTotal > 0 && totalImported >= ytTotal) || (addedThisRun >= maxPerSync);
                                        if (done) { if (btn) btn.disabled = false; setTimeout(function(){ location.reload(); }, 800); }
                                        else doChunk();
                                    })
                                    .catch(function(){
                                        if (btn) btn.disabled = false;
                                        wrap.classList.add('d-none');
                                        location.reload();
                                    });
                            }
                            updateProgress(startTotal);
                            doChunk();
                        });
                    });
                })();
                </script>
                <?php } else { ?>
                <p class="text-muted">No channels yet. Add one using the form at the top.</p>
                <?php } ?>
            <?php
            }
        } elseif ($section === 'later' || $section === 'history' || $section === 'likes') {
            ensure_system_playlists($uid);
            $key = $section === 'later' ? PLAYLIST_LATER : ($section === 'history' ? PLAYLIST_HISTORY : PLAYLIST_LIKES);
            $pl = get_playlist($key, $uid);
            if ($pl) {
                $items = get_playlist_items($pl->id, 'video', 50);
                if ($items) {
                    foreach ($items as $pd) {
                        $v = get_video($pd->media_id);
                        if ($v) echo '<div class="mb-2"><a href="' . (function_exists('media_play_url') ? media_play_url($v->id, $v->type ?? 'video', $v->title ?? '') : watch_url($v->id)) . '">' . _e($v->title) . '</a></div>';
                    }
                } else echo '<p class="text-muted">Nothing here yet.</p>';
            } else echo '<p class="text-muted">Nothing here yet.</p>';
        } elseif ($section === 'library') {
            ensure_system_playlists($uid);
            $pl_later = get_playlist(PLAYLIST_LATER, $uid);
            $pl_hist  = get_playlist(PLAYLIST_HISTORY, $uid);
            $pl_likes = get_playlist(PLAYLIST_LIKES, $uid);
            $later_cnt = $pl_later ? count(get_playlist_items($pl_later->id, 'video', 999)) : 0;
            $hist_cnt  = $pl_hist  ? count(get_playlist_items($pl_hist->id, 'video', 999)) : 0;
            $likes_cnt = $pl_likes ? count(get_playlist_items($pl_likes->id, 'video', 999)) : 0;
            $pls_count = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}playlists WHERE user_id = ? AND system_key IS NULL", [$uid]);
            $pls_count = $pls_count ? (int)(is_object($pls_count) ? $pls_count->c : ($pls_count['c'] ?? 0)) : 0;
            ?>
            <p class="text-muted mb-4">Your saved content and playlists. Use the tabs above or the links below.</p>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="<?php echo url('me/later'); ?>" class="card bg-dark border-secondary text-decoration-none text-reset h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="material-icons text-primary" style="font-size:2rem;">schedule</span>
                            <div>
                                <div class="h5 mb-0">Watch later</div>
                                <small class="text-muted"><?php echo (int) $later_cnt; ?> item(s)</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?php echo url('me/history'); ?>" class="card bg-dark border-secondary text-decoration-none text-reset h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="material-icons text-info" style="font-size:2rem;">history</span>
                            <div>
                                <div class="h5 mb-0">History</div>
                                <small class="text-muted"><?php echo (int) $hist_cnt; ?> item(s)</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?php echo url('me/likes'); ?>" class="card bg-dark border-secondary text-decoration-none text-reset h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="material-icons text-danger" style="font-size:2rem;">thumb_up</span>
                            <div>
                                <div class="h5 mb-0">Likes</div>
                                <small class="text-muted"><?php echo (int) $likes_cnt; ?> item(s)</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?php echo url('me/playlists'); ?>" class="card bg-dark border-secondary text-decoration-none text-reset h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="material-icons text-warning" style="font-size:2rem;">playlist_play</span>
                            <div>
                                <div class="h5 mb-0">Playlists</div>
                                <small class="text-muted"><?php echo (int) $pls_count; ?> list(s)</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <?php
        } elseif ($section === 'playlists') {
            $pls = $db->fetchAll("SELECT * FROM {$pre}playlists WHERE user_id = ? AND system_key IS NULL ORDER BY name", [$uid]);
            if ($pls) {
                foreach ($pls as $pl) { $pl = is_array($pl) ? (object) $pl : $pl; ?>
                <div class="mb-2 d-flex align-items-center gap-2">
                    <span class="material-icons text-muted">playlist_play</span>
                    <a href="<?php echo playlist_url($pl->slug, $pl->id); ?>"><?php echo _e($pl->name); ?></a>
                </div>
                <?php }
            } else echo '<p class="text-muted">No playlists. Create one from a video page.</p>';
        } elseif ($section === 'manage') {
            ensure_system_playlists($uid);
            $stats_videos = $db->fetch("SELECT COUNT(*) AS c, COALESCE(SUM(views),0) AS total_views FROM {$pre}videos WHERE user_id = ? AND type = 'video'", [$uid]);
            $stats_music  = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos WHERE user_id = ? AND type = 'music'", [$uid]);
            $stats_pls    = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}playlists WHERE user_id = ? AND system_key IS NULL", [$uid]);
            $count_videos = $stats_videos ? (int)(is_object($stats_videos) ? $stats_videos->c : ($stats_videos['c'] ?? 0)) : 0;
            $count_music  = $stats_music  ? (int)(is_object($stats_music) ? $stats_music->c : ($stats_music['c'] ?? 0)) : 0;
            $count_pls    = $stats_pls    ? (int)(is_object($stats_pls) ? $stats_pls->c : ($stats_pls['c'] ?? 0)) : 0;
            $total_views  = $stats_videos ? (int)(is_object($stats_videos) ? $stats_videos->total_views : ($stats_videos['total_views'] ?? 0)) : 0;
            $pl_later  = get_playlist(PLAYLIST_LATER, $uid);
            $pl_hist   = get_playlist(PLAYLIST_HISTORY, $uid);
            $pl_likes  = get_playlist(PLAYLIST_LIKES, $uid);
            $later_cnt = $pl_later ? count(get_playlist_items($pl_later->id, 'video', 999)) : 0;
            $hist_cnt  = $pl_hist  ? count(get_playlist_items($pl_hist->id, 'video', 999)) : 0;
            $likes_cnt = $pl_likes ? count(get_playlist_items($pl_likes->id, 'video', 999)) : 0;
            $my_videos = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? AND type = 'video' ORDER BY created_at DESC LIMIT 24", [$uid]);
            $my_music  = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? AND type = 'music' ORDER BY created_at DESC LIMIT 24", [$uid]);
            $my_pls    = $db->fetchAll("SELECT * FROM {$pre}playlists WHERE user_id = ? AND system_key IS NULL ORDER BY name LIMIT 20", [$uid]);
            $site_url = rtrim(SITE_URL, '/');
            ?>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="material-icons text-primary" style="font-size:2.5rem;">videocam</span>
                            <div>
                                <div class="h4 mb-0"><?php echo (int) $count_videos; ?></div>
                                <small class="text-muted">Videos</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="material-icons text-info" style="font-size:2.5rem;">music_note</span>
                            <div>
                                <div class="h4 mb-0"><?php echo (int) $count_music; ?></div>
                                <small class="text-muted">Music</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="material-icons text-warning" style="font-size:2.5rem;">playlist_play</span>
                            <div>
                                <div class="h4 mb-0"><?php echo (int) $count_pls; ?></div>
                                <small class="text-muted">Playlists</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="material-icons text-success" style="font-size:2.5rem;">visibility</span>
                            <div>
                                <div class="h4 mb-0"><?php echo number_format($total_views); ?></div>
                                <small class="text-muted">Total views</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                        <span class="material-icons">dashboard</span> System
                    </h2>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo url('me/later'); ?>" class="card bg-dark border-secondary text-decoration-none text-light h-100 hover-border-primary">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <span class="d-flex align-items-center gap-2">
                                <span class="material-icons text-secondary">schedule</span>
                                Watch later
                            </span>
                            <span class="badge bg-secondary rounded-pill"><?php echo (int) $later_cnt; ?></span>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo url('me/history'); ?>" class="card bg-dark border-secondary text-decoration-none text-light h-100 hover-border-primary">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <span class="d-flex align-items-center gap-2">
                                <span class="material-icons text-secondary">history</span>
                                History
                            </span>
                            <span class="badge bg-secondary rounded-pill"><?php echo (int) $hist_cnt; ?></span>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo url('me/likes'); ?>" class="card bg-dark border-secondary text-decoration-none text-light h-100 hover-border-primary">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <span class="d-flex align-items-center gap-2">
                                <span class="material-icons text-secondary">thumb_up</span>
                                Liked videos
                            </span>
                            <span class="badge bg-secondary rounded-pill"><?php echo (int) $likes_cnt; ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <h2 class="h6 text-muted text-uppercase mb-0 d-flex align-items-center gap-2">
                        <span class="material-icons">bolt</span> Quick actions
                    </h2>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo url('share'); ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">upload</span> Upload
                        </a>
                        <a href="<?php echo url('dashboard'); ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">video_library</span> Studio
                        </a>
                        <?php $cu = current_user(); $profile_u = $cu && !empty($cu->username) ? $cu->username : ''; $profile_id = $cu && !empty($cu->id) ? (int)$cu->id : 0; if ($profile_u && $profile_id) { ?>
                        <a href="<?php echo _e(profile_url($profile_u, $profile_id)); ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">person</span> Profile
                        </a>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                        <span class="material-icons">videocam</span> My videos
                    </h2>
                </div>
                <?php
                if (!empty($my_videos)) {
                    foreach ($my_videos as $v) {
                        $v = is_array($v) ? (object) $v : $v;
                        $link = function_exists('media_play_url') ? media_play_url($v->id, $v->type ?? 'video', $v->title ?? '') : watch_url($v->id);
                        $thumb = !empty($v->thumb) ? $v->thumb : '';
                        if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
                        $duration = function_exists('nia_duration') ? nia_duration($v->duration ?? 0) : '';
                        $timeAgo = function_exists('nia_time_ago') ? nia_time_ago($v->created_at ?? null) : '';
                        $views = (int)($v->views ?? 0);
                        ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="card bg-dark border-secondary h-100">
                        <a href="<?php echo _e($link); ?>" class="text-decoration-none text-reset">
                            <div class="position-relative">
                                <img src="<?php echo _e($thumb ?: ''); ?>" class="card-img-top rounded-0" alt="" style="aspect-ratio:16/9;object-fit:cover;" loading="lazy" onerror="this.style.display='none'">
                                <?php if ($duration) { ?><span class="position-absolute bottom-0 end-0 m-1 badge bg-dark text-white small"><?php echo _e($duration); ?></span><?php } ?>
                            </div>
                            <div class="card-body p-2">
                                <div class="nia-video-title small text-truncate" title="<?php echo _e($v->title ?? ''); ?>"><?php echo _e($v->title ?? ''); ?></div>
                                <div class="small text-muted"><?php echo $views; ?> views · <?php echo _e($timeAgo); ?></div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-secondary p-2">
                            <a href="<?php echo _e($link); ?>" class="btn btn-sm btn-outline-primary w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">play_circle</span> View
                            </a>
                        </div>
                    </div>
                </div>
                <?php }
                } else { ?>
                <div class="col-12">
                    <p class="text-muted mb-0">No videos yet. <a href="<?php echo url('share'); ?>">Upload</a> or add from <a href="<?php echo url('videos'); ?>">browse</a>.</p>
                </div>
                <?php } ?>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                        <span class="material-icons">music_note</span> My music
                    </h2>
                </div>
                <?php
                if (!empty($my_music)) {
                    foreach ($my_music as $v) {
                        $v = is_array($v) ? (object) $v : $v;
                        $link = function_exists('listen_url') ? listen_url($v->id) : watch_url($v->id);
                        $thumb = !empty($v->thumb) ? $v->thumb : '';
                        if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
                        $timeAgo = function_exists('nia_time_ago') ? nia_time_ago($v->created_at ?? null) : '';
                        $views = (int)($v->views ?? 0);
                        ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="card bg-dark border-secondary h-100">
                        <a href="<?php echo _e($link); ?>" class="text-decoration-none text-reset">
                            <img src="<?php echo _e($thumb ?: ''); ?>" class="card-img-top rounded-0" alt="" style="aspect-ratio:1;object-fit:cover;" loading="lazy" onerror="this.style.display='none'">
                            <div class="card-body p-2">
                                <div class="nia-video-title small text-truncate" title="<?php echo _e($v->title ?? ''); ?>"><?php echo _e($v->title ?? ''); ?></div>
                                <div class="small text-muted"><?php echo $views; ?> views · <?php echo _e($timeAgo); ?></div>
                            </div>
                        </a>
                        <div class="card-footer bg-transparent border-secondary p-2">
                            <a href="<?php echo _e($link); ?>" class="btn btn-sm btn-outline-info w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                <span class="material-icons" style="font-size:1rem;">play_circle</span> Play
                            </a>
                        </div>
                    </div>
                </div>
                <?php }
                } else { ?>
                <div class="col-12">
                    <p class="text-muted mb-0">No music yet. <a href="<?php echo url('share'); ?>">Upload</a> or browse <a href="<?php echo url('music'); ?>">Music</a>.</p>
                </div>
                <?php } ?>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <h2 class="h6 text-muted text-uppercase mb-3 d-flex align-items-center gap-2">
                        <span class="material-icons">playlist_play</span> My playlists
                    </h2>
                </div>
                <div class="col-12">
                    <?php if (!empty($my_pls)) { ?>
                    <ul class="list-group list-group-flush bg-transparent">
                        <?php foreach ($my_pls as $pl) {
                            $pl = is_array($pl) ? (object) $pl : $pl;
                            $pl_url = playlist_url($pl->slug ?? '', $pl->id ?? 0);
                        ?>
                        <li class="list-group-item bg-dark border-secondary d-flex align-items-center justify-content-between">
                            <a href="<?php echo _e($pl_url); ?>" class="d-flex align-items-center gap-2 text-decoration-none text-light">
                                <span class="material-icons text-warning">playlist_play</span>
                                <?php echo _e($pl->name ?? ''); ?>
                            </a>
                            <a href="<?php echo _e($pl_url); ?>" class="btn btn-sm btn-outline-secondary">
                                <span class="material-icons" style="font-size:1rem;">open_in_new</span>
                            </a>
                        </li>
                        <?php } ?>
                    </ul>
                    <?php } else { ?>
                    <p class="text-muted mb-0">No playlists. Add videos to playlists from the <a href="<?php echo url('watch'); ?>">watch</a> page (Save / Add to playlist).</p>
                    <?php } ?>
                </div>
            </div>
        <?php
        } else {
            echo '<p class="text-muted">Choose a tab above: Library, Watch later, History, Likes, Playlists, or Manage.</p>';
        }
        ?>
    </div>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
