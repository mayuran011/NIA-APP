<?php
/**
 * Listen page: music playback with unified audio player, playlist queue, related music.
 * URL: /listen/:id — All music plays in this single player; responsive, mobile-first.
 */
if (!defined('in_nia_app')) exit;

$nia_section = $GLOBALS['nia_route_section'] ?? '';
$nia_current_media_id = null;
$nia_current_media_type = 'music';
$video = null;

if ($nia_section !== '' && is_numeric($nia_section)) {
    $video = get_video((int) $nia_section);
    if ($video && (!isset($video->type) || $video->type !== 'music')) {
        redirect(watch_url($video->id, $video->title ?? ''));
    }
}

if ($video) {
    $nia_current_media_id = (int) $video->id;
    $GLOBALS['nia_current_media_id'] = $nia_current_media_id;
}

$page_title = $video ? _e($video->title) : 'Listen';

// Permissions & NSFW
$is_premium_content = $video && !empty($video->premium);
$can_watch = !$video || !$is_premium_content || (function_exists('can_access_premium_content') && can_access_premium_content($video));
$is_nsfw = $video && !empty($video->nsfw);
$nsfw_ok = isset($_GET['nsfw']) && $_GET['nsfw'] === '1' || !empty($_SESSION['nsfw_ok']);

if ($video && $is_nsfw && !$nsfw_ok) {
    if (isset($_POST['nsfw_confirm'])) {
        $_SESSION['nsfw_ok'] = true;
        redirect(listen_url($video->id) . '?nsfw=1');
    }
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
    echo '<main class="nia-main container py-4"><div class="card border-warning"><div class="card-body"><p class="mb-2">This content is marked NSFW.</p><form method="post"><input type="hidden" name="nsfw_confirm" value="1"><button type="submit" class="btn btn-warning btn-sm">Continue</button></form> <a href="' . url() . '" class="btn btn-outline-secondary btn-sm">Leave</a></div></div></main>';
    require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
    exit;
}

// Data fetching
$user_like = $video && is_logged() ? get_user_like(current_user_id(), 'video', $video->id) : 0;
$channel = $video && isset($video->user_id) ? get_user($video->user_id) : null;
$subs_count = $channel && function_exists('subscriber_count') ? subscriber_count($channel->id) : 0;
$is_subscribed = $channel && is_logged() && function_exists('is_subscribed_to') ? is_subscribed_to($channel->id) : false;
$related = $video && function_exists('get_related_videos') ? get_related_videos($video->id, 20) : [];

// Playlist context: ?playlist=ID → sidebar shows playlist tracks; next/prev use playlist order
$playlist_id = isset($_GET['playlist']) ? (int) $_GET['playlist'] : 0;
$playlist = $playlist_id > 0 && function_exists('get_playlist') ? get_playlist($playlist_id) : null;
$sidebar_tracks = [];
$playlist_current_index = -1;
if ($playlist && $video && function_exists('get_playlist_items')) {
    $pl_items = get_playlist_items($playlist->id, $playlist->type ?? 'video', 100);
    foreach ($pl_items as $i => $row) {
        $mid = (int) ($row->media_id ?? 0);
        if ($mid <= 0) continue;
        $item = get_video($mid);
        if (!$item || (isset($item->type) && $item->type !== 'music')) continue;
        $sidebar_tracks[] = $item;
        if ((int) $item->id === (int) $video->id) $playlist_current_index = count($sidebar_tracks) - 1;
    }
}
if (empty($sidebar_tracks)) {
    $sidebar_tracks = $related;
    $playlist_id = 0;
    $playlist_current_index = -1;
}

// Admin Control for Autoplay (0 or 1)
$autoplay_admin = (int) get_option('music_autoplay_next', '1');

// Listen Base URL
$listen_base = rtrim(SITE_URL, '/') . (function_exists('listen_url') ? preg_replace('#^https?://[^/]+#', '', listen_url(0)) : '/listen');
$listen_base = str_replace('/0', '', $listen_base);

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>

<style>
/* Listen page: theme-aware (dark/light), YouTube-style layout */
.nia-player-rebuild {
    background: var(--pv-bg, #0f0f0f);
    color: var(--pv-text, #fff);
    min-height: calc(100vh - 100px);
    font-family: var(--pv-font-sans, system-ui, sans-serif);
}
.nia-listen-layout {
    display: flex;
    gap: 1.5rem;
    max-width: 100%;
    padding: 1rem 0;
}
.nia-listen-main {
    flex: 1;
    min-width: 0;
}
.nia-listen-related {
    width: 402px;
    min-width: 402px;
    flex-shrink: 0;
}
@media (max-width: 1199.98px) {
    .nia-listen-related { width: 320px; min-width: 320px; }
}
@media (max-width: 991.98px) {
    .nia-listen-layout { flex-direction: column; }
    .nia-listen-related { width: 100%; min-width: 0; }
}
.nia-artwork-container {
    position: relative;
    border-radius: var(--pv-radius, 8px);
    overflow: hidden;
    box-shadow: var(--pv-shadow, 0 4px 24px rgba(0,0,0,0.4));
    flex-shrink: 0;
}
.nia-seek-bar-rebuild {
    height: 6px;
    cursor: pointer;
    background: var(--pv-border, rgba(255,255,255,0.2));
    border-radius: 3px;
}
.nia-seek-bar-fill {
    height: 100%;
    background: var(--pv-primary, #ff0000);
    border-radius: 3px;
    transition: width 0.1s linear;
}
.nia-seek-bar-handle {
    width: 14px;
    height: 14px;
    background: var(--pv-primary, #ff0000);
    border-radius: 50%;
    position: absolute;
    right: -7px;
    top: -4px;
    opacity: 0;
    transition: opacity 0.2s;
    box-shadow: 0 0 0 2px var(--pv-bg);
}
.nia-seek-bar-rebuild:hover .nia-seek-bar-handle { opacity: 1; }
.nia-listen .btn-player {
    color: var(--pv-text-muted, rgba(255,255,255,0.7));
    transition: color 0.2s, transform 0.15s;
    border: none;
    background: none;
    padding: 0.25rem;
}
.nia-listen .btn-player:hover { color: var(--pv-text); }
.nia-listen .btn-player.active { color: var(--pv-primary); }
.nia-listen .large-play-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--pv-primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    transition: transform 0.15s, opacity 0.2s;
}
.nia-listen .large-play-btn:hover { transform: scale(1.05); opacity: 0.9; }
.nia-listen-related-header {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--pv-text);
    padding: 0.5rem 0 0.75rem;
    border-bottom: 1px solid var(--pv-border);
    margin-bottom: 0.5rem;
}
.nia-listen-related-list { list-style: none; margin: 0; padding: 0; }
.nia-listen-related-item { margin-bottom: 0.5rem; }
.nia-listen-related-link {
    display: flex;
    gap: 0.75rem;
    padding: 0.5rem 0;
    text-decoration: none;
    color: inherit;
    border-radius: var(--pv-radius-sm);
    transition: background 0.15s;
}
.nia-listen-related-link:hover { background: var(--pv-surface); }
.nia-listen-related-link.current { background: var(--pv-surface); }
.nia-listen-related-thumb {
    width: 120px;
    min-width: 120px;
    height: 68px;
    border-radius: 4px;
    overflow: hidden;
    background: var(--pv-border);
}
.nia-listen-related-thumb img { width: 100%; height: 100%; object-fit: cover; }
.nia-listen-related-info { flex: 1; min-width: 0; }
.nia-listen-related-title {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--pv-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.nia-listen-related-artist {
    font-size: 0.8rem;
    color: var(--pv-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.nia-listen-related-duration {
    font-size: 0.75rem;
    color: var(--pv-text-muted);
    flex-shrink: 0;
    align-self: center;
}
</style>

<main class="nia-player-rebuild nia-listen py-4">
    <?php if ($video && $can_watch) {
        $thumb = !empty($video->thumb) ? $video->thumb : '';
        if (strpos($thumb, 'http') !== 0 && $thumb !== '') { $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/'); }
        $video_source = $video->source ?? 'local';
        $is_native = in_array($video_source, ['local', 'direct_audio'], true);
        $chan_name = $channel ? ($channel->username ?? $channel->name ?? '') : '';
        
        // Sidebar list for JS (playlist queue or related)
        $sidebar_json = [];
        $playlist_param = $playlist_id > 0 ? '&playlist=' . $playlist_id : '';
        foreach ($sidebar_tracks as $r) {
            $r_thumb = !empty($r->thumb) ? $r->thumb : '';
            if ($r_thumb !== '' && strpos($r_thumb, 'http') !== 0) $r_thumb = rtrim(SITE_URL, '/') . '/' . ltrim($r_thumb, '/');
            $r_ch = isset($r->user_id) ? get_user($r->user_id) : null;
            $listen_url_r = listen_url($r->id) . ($playlist_param ? '?playlist=' . $playlist_id : '');
            $sidebar_json[] = [
                'id' => (int)$r->id,
                'title' => $r->title ?? '',
                'artist' => $r_ch ? ($r_ch->username ?? $r_ch->name ?? '') : '',
                'thumb' => $r_thumb,
                'url' => $listen_url_r
            ];
        }
        $related_json = $sidebar_json;
        $audio_src = class_exists('NiaPlayers') ? NiaPlayers::playbackUrl($video) : '';
        if ($audio_src !== '' && strpos($audio_src, 'http') !== 0 && strpos($audio_src, '//') !== 0) {
            $audio_src = rtrim(SITE_URL, '/') . '/' . ltrim($audio_src, '/');
        }
    ?>
    <?php
        $current_duration = isset($video->duration) ? (int) $video->duration : 0;
        $current_dur_str = function_exists('nia_duration') ? nia_duration($current_duration) : '0:00';
    ?>
    <div class="container-fluid">
        <div class="nia-listen-layout">
            <!-- Left: Now Playing + Howler player -->
            <div class="nia-listen-main">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-4 mb-4">
                    <div class="nia-artwork-container" style="width: 200px; height: 200px;">
                        <img src="<?php echo _e($thumb); ?>" class="w-100 h-100 object-fit-cover" id="rebuild-artwork" alt="">
                    </div>
                    <div class="flex-grow-1 min-w-0 w-100 w-md-auto">
                        <h1 class="h4 fw-bold mb-1" id="rebuild-title"><?php echo _e($video->title); ?></h1>
                        <p class="text-muted mb-3" id="rebuild-artist"><?php echo _e($chan_name); ?></p>
                        <div class="mb-2">
                            <div class="nia-seek-bar-rebuild mb-1" id="rebuild-progress-container">
                                <div class="nia-seek-bar-fill" id="rebuild-progress-bar" style="width: 0%;">
                                    <div class="nia-seek-bar-handle"></div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span id="rebuild-time-current">0:00</span>
                                <span id="rebuild-time-total"><?php echo _e($current_dur_str); ?></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn-player" id="btn-shuffle" title="Shuffle"><span class="material-icons">shuffle</span></button>
                                <button type="button" class="btn-player" id="btn-prev" title="Previous"><span class="material-icons">skip_previous</span></button>
                                <button type="button" class="btn large-play-btn" id="btn-main-play">
                                    <span class="material-icons" style="font-size: 2rem;" id="main-play-icon">play_arrow</span>
                                </button>
                                <button type="button" class="btn-player" id="btn-next" title="Next"><span class="material-icons">skip_next</span></button>
                                <button type="button" class="btn-player" id="btn-repeat" title="Repeat"><span class="material-icons">repeat</span></button>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="material-icons text-muted" style="font-size: 1.25rem;">volume_up</span>
                                <input type="range" class="form-range" style="width: 90px;" id="rebuild-volume" min="0" max="1" step="0.01" value="1">
                                <button type="button" class="btn-player" id="btn-queue" title="Queue"><span class="material-icons">queue_music</span></button>
                            </div>
                        </div>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <div class="form-check form-switch mb-0 small">
                                <input class="form-check-input" type="checkbox" id="autoplay-toggle-main" <?php echo $autoplay_admin ? 'checked' : ''; ?>>
                                <label class="form-check-label text-muted" for="autoplay-toggle-main">Autoplay next</label>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="rebuild-like"><span class="material-icons align-middle" style="font-size:1rem;">thumb_up</span></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="rebuild-share"><span class="material-icons align-middle" style="font-size:1rem;">share</span></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Related / Playlist (YouTube watch style) -->
            <aside class="nia-listen-related">
                <div class="nia-listen-related-header">
                    <?php echo $playlist_id > 0 && $playlist ? _e($playlist->name ?? 'Playlist') : 'Related music'; ?>
                </div>
                <ul class="nia-listen-related-list" id="rebuild-playlist-container">
                    <?php foreach ($sidebar_tracks as $r):
                        $r_thumb = !empty($r->thumb) ? $r->thumb : '';
                        if ($r_thumb !== '' && strpos($r_thumb, 'http') !== 0) $r_thumb = rtrim(SITE_URL, '/') . '/' . ltrim($r_thumb, '/');
                        $r_ch = isset($r->user_id) ? get_user($r->user_id) : null;
                        $r_dur = isset($r->duration) ? (int) $r->duration : 0;
                        $r_dur_str = function_exists('nia_duration') ? nia_duration($r_dur) : '0:00';
                        $is_current = (int)$r->id === (int)$video->id;
                        $r_link = $playlist_id > 0 ? listen_url($r->id) . '?playlist=' . $playlist_id : listen_url($r->id);
                    ?>
                    <li class="nia-listen-related-item">
                        <a href="<?php echo _e($r_link); ?>" class="nia-listen-related-link<?php echo $is_current ? ' current' : ''; ?>">
                            <div class="nia-listen-related-thumb">
                                <img src="<?php echo _e($r_thumb); ?>" alt="" loading="lazy">
                            </div>
                            <div class="nia-listen-related-info">
                                <div class="nia-listen-related-title"><?php echo _e($r->title); ?></div>
                                <div class="nia-listen-related-artist"><?php echo _e($r_ch ? ($r_ch->username ?? $r_ch->name ?? '') : ''); ?></div>
                            </div>
                            <span class="nia-listen-related-duration"><?php echo _e($r_dur_str); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        </div>
    </div>

    <!-- Howler.js handles all audio; no hidden HTML5 player needed -->

    <!-- Scripts: Howler.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.4/howler.min.js"></script>
    <script>
    window.niaListen = {
        config: {
            base: <?php echo json_encode(rtrim(SITE_URL, '/')); ?>,
            autoplayAdmin: <?php echo $autoplay_admin; ?>,
            current: { 
                id: <?php echo (int)$video->id; ?>,
                title: <?php echo json_encode($video->title); ?>,
                artist: <?php echo json_encode($chan_name); ?>,
                thumb: <?php echo json_encode($thumb); ?>,
                audioSrc: <?php echo json_encode($audio_src ?? ''); ?>
            },
            related: <?php echo json_encode($related_json); ?>,
            playlistId: <?php echo (int)$playlist_id; ?>,
            currentIndex: <?php echo (int)$playlist_current_index; ?>,
            isNative: <?php echo $is_native ? 'true' : 'false'; ?>,
            source: <?php echo json_encode($video_source); ?>
        },
        state: {
            isPlaying: false,
            currentTime: 0,
            duration: 0,
            shuffle: false,
            repeat: 'none', // none, one, all
            autoplay: (localStorage.getItem('nia_autoplay') !== null) ? (localStorage.getItem('nia_autoplay') === '1') : (<?php echo $autoplay_admin; ?> === 1)
        }
    };

    (function(){
        "use strict";
        function runListenPlayer() {
            var howl = null;
            var progressTick = null;
            const cfg = window.niaListen.config;
            const state = window.niaListen.state;
            const playBtn = document.getElementById('btn-main-play');
            const playIcon = document.getElementById('main-play-icon');
            const progressFill = document.getElementById('rebuild-progress-bar');
            const progressContainer = document.getElementById('rebuild-progress-container');
            const timeCurrent = document.getElementById('rebuild-time-current');
            const timeTotal = document.getElementById('rebuild-time-total');
            const volumeSlider = document.getElementById('rebuild-volume');
            const nextBtn = document.getElementById('btn-next');
            const prevBtn = document.getElementById('btn-prev');
            const autoplayToggleMain = document.getElementById('autoplay-toggle-main');

            function formatTime(s) {
                if (isNaN(s) || s < 0) return '0:00';
                var m = Math.floor(s / 60);
                var sec = Math.floor(s % 60);
                return m + ":" + (sec < 10 ? "0" : "") + sec;
            }

            function updateUI() {
                if (playIcon) playIcon.textContent = state.isPlaying ? 'pause' : 'play_arrow';
                if (autoplayToggleMain) autoplayToggleMain.checked = state.autoplay;
            }

            function updateProgress() {
                if (!howl || !progressFill) return;
                var dur = howl.duration();
                var pos = howl.seek();
                if (dur > 0 && !isNaN(pos)) {
                    var pct = (pos / dur) * 100;
                    progressFill.style.width = pct + '%';
                    if (timeCurrent) timeCurrent.textContent = formatTime(pos);
                }
            }

            function stopProgressTick() {
                if (progressTick) {
                    cancelAnimationFrame(progressTick);
                    progressTick = null;
                }
            }

            function startProgressTick() {
                function tick() {
                    updateProgress();
                    progressTick = requestAnimationFrame(tick);
                }
                progressTick = requestAnimationFrame(tick);
            }

            function goNext() {
                var list = cfg.related || [];
                if (list.length === 0) return;
                if (cfg.playlistId && cfg.currentIndex >= 0 && cfg.currentIndex + 1 < list.length) {
                    window.location.href = list[cfg.currentIndex + 1].url;
                    return;
                }
                if (cfg.playlistId) return;
                window.location.href = list[0].url;
            }

            (function trackView() {
                if (cfg.current && cfg.current.id) fetch(cfg.base + '/app/ajax/track.php?media_type=music&media_id=' + cfg.current.id, { credentials: 'same-origin' });
            })();
            function initHowl() {
                var src = (cfg.current && cfg.current.audioSrc) ? cfg.current.audioSrc : '';
                if (!src || typeof Howler === 'undefined') return;
                if (howl) {
                    howl.unload();
                    howl = null;
                }
                stopProgressTick();
                howl = new Howl({
                    src: [src],
                    html5: true,
                    onload: function() {
                        if (timeTotal) timeTotal.textContent = formatTime(howl.duration());
                        if (state.autoplay) howl.play();
                    },
                    onloaderror: function(id, err) {
                        if (timeTotal) timeTotal.textContent = '0:00';
                    },
                    onplay: function() {
                        state.isPlaying = true;
                        updateUI();
                        startProgressTick();
                    },
                    onpause: function() {
                        state.isPlaying = false;
                        updateUI();
                        stopProgressTick();
                    },
                    onstop: function() {
                        state.isPlaying = false;
                        updateUI();
                        stopProgressTick();
                    },
                    onend: function() {
                        stopProgressTick();
                        if (progressFill) progressFill.style.width = '0%';
                        if (timeCurrent) timeCurrent.textContent = '0:00';
                        if (state.autoplay) goNext();
                        else {
                            state.isPlaying = false;
                            updateUI();
                        }
                    }
                });
                if (volumeSlider) howl.volume(parseFloat(volumeSlider.value, 10));
            }

            function togglePlay(e) {
                if (e) { e.preventDefault(); e.stopPropagation(); }
                if (!howl) {
                    initHowl();
                    if (howl) howl.play();
                    return;
                }
                if (howl.playing()) howl.pause(); else howl.play();
            }

            initHowl();
            if (playBtn) {
                playBtn.addEventListener('click', togglePlay);
            }
            function goPrev() {
                var list = cfg.related || [];
                if (cfg.playlistId && cfg.currentIndex > 0 && list[cfg.currentIndex - 1]) {
                    window.location.href = list[cfg.currentIndex - 1].url;
                    return;
                }
                window.history.back();
            }
            if (nextBtn) nextBtn.addEventListener('click', goNext);
            if (prevBtn) prevBtn.addEventListener('click', goPrev);

            if (progressContainer) {
                progressContainer.addEventListener('mousedown', function(e) {
                    if (!howl) return;
                    var rect = progressContainer.getBoundingClientRect();
                    var pos = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                    var dur = howl.duration();
                    if (dur && !isNaN(dur)) howl.seek(pos * dur);
                    var move = function(ev) {
                        var p = Math.max(0, Math.min(1, (ev.clientX - rect.left) / rect.width));
                        if (dur) howl.seek(p * dur);
                    };
                    var up = function() {
                        window.removeEventListener('mousemove', move);
                        window.removeEventListener('mouseup', up);
                    };
                    window.addEventListener('mousemove', move);
                    window.addEventListener('mouseup', up);
                });
            }

            if (volumeSlider) {
                volumeSlider.addEventListener('input', function(e) {
                    var v = parseFloat(e.target.value, 10);
                    if (howl) howl.volume(v);
                });
            }

            var syncAutoplay = function(e) {
                state.autoplay = e.target.checked;
                localStorage.setItem('nia_autoplay', state.autoplay ? '1' : '0');
                updateUI();
            };
            if (autoplayToggleMain) autoplayToggleMain.addEventListener('change', syncAutoplay);

            var likeBtn = document.getElementById('rebuild-like');
            if (likeBtn) {
                likeBtn.addEventListener('click', function() {
                    var fd = new FormData();
                    fd.append('object_type', 'video');
                    fd.append('object_id', cfg.current.id);
                    fetch(cfg.base + '/app/ajax/like.php', { method: 'POST', body: fd })
                        .then(function(r) { return r.json(); })
                        .then(function(j) { if (j && j.ok) likeBtn.classList.add('btn-primary', 'text-white'); });
                });
            }
            var shareBtn = document.getElementById('rebuild-share');
            if (shareBtn) {
                shareBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var url = cfg.base + '/listen/' + cfg.current.id;
                    if (navigator.share) navigator.share({ title: cfg.current.title, url: url });
                    else { navigator.clipboard.writeText(url); alert('Link copied to clipboard!'); }
                });
            }

            window.addEventListener('keydown', function(e) {
                if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
                if (e.code === 'Space') { e.preventDefault(); togglePlay(e); }
            });

            updateUI();
            updateProgress();
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runListenPlayer);
        } else {
            runListenPlayer();
        }
    })();
    </script>

    <?php } else { ?>
    <div class="container py-5 text-center">
        <h2 class="text-dark"><?php echo $video ? 'Track limit restricted' : 'Track not found'; ?></h2>
        <a href="<?php echo url('music'); ?>" class="btn btn-primary mt-3">Back to Music</a>
    </div>
    <?php } ?>
</main>

<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
?>
