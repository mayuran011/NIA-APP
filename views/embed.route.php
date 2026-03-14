<?php
/**
 * Embed: minimal iframe page for one video/music.
 * Used for iframe embeds and PWA/background audio (Media Session API).
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$video = null;
if (preg_match('#^(\d+)#', $nia_section, $m)) {
    $video = get_video((int) $m[1]);
}
$page_title = $video ? _e($video->title) : 'Embed';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?></title>
    <link rel="manifest" href="<?php echo url('app/favicos/site.webmanifest.php'); ?>">
    <meta name="theme-color" content="<?php echo _e(get_option('theme_color', '#0f0f12')); ?>">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #0f0f12; color: #f4f4f5; font-family: system-ui, sans-serif; min-height: 100vh; }
        .nia-player-container { max-width: 100%; }
        /* Embed player: 16:9 ratio and iframe fill (no Bootstrap on this page) */
        #nia-embed-player {
            position: relative;
            width: 100%;
            max-width: 100%;
            height: 0;
            padding-bottom: 56.25%;
            background: #000;
        }
        #nia-embed-player iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }
        #nia-embed-player .nia-player-logo {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            z-index: 6;
        }
        .nia-music-player .nia-waveform { min-height: 80px; margin-top: 0.5rem; }
    </style>
</head>
<body>
<?php
if ($video) {
    $player_opts = [
        'container_id' => 'nia-embed-player',
        'embed_mode'   => true,
        'autoplay'     => isset($_GET['autoplay']) && (int) $_GET['autoplay'] === 1,
        'no_media_session' => false,
    ];
    if (function_exists('do_action')) {
        do_action('nia_before_player', 'nia-embed-player', $video, $player_opts);
    }
    $player_html = NiaPlayers::render($video, $player_opts);
    if (function_exists('apply_filters')) {
        $player_html = apply_filters('the_embedded_video', $player_html, $video, array_merge($player_opts, ['placement' => 'embed']));
    }
    if (function_exists('do_action')) {
        do_action('vibe_after_player', 'nia-embed-player', $video, $player_opts);
    }
    echo $player_html;
    $is_music = isset($video->type) && $video->type === 'music';
    $thumb = !empty($video->thumb) ? $video->thumb : '';
    $title = $video->title ?? '';
?>
<script>
(function() {
    if (!('mediaSession' in navigator)) return;
    navigator.mediaSession.metadata = new MediaMetadata({
        title: <?php echo json_encode($title); ?>,
        artist: <?php echo json_encode(get_option('sitename', 'Nia App')); ?>,
        artwork: <?php echo $thumb ? json_encode([['src' => (strpos($thumb, 'http') === 0 ? $thumb : rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/')), 'sizes' => '512x512', 'type' => 'image/jpeg']]) : '[]'; ?>
    });
    navigator.mediaSession.setActionHandler('play', function() {
        var el = document.querySelector('#nia-embed-player audio, #nia-embed-player video');
        if (el) el.play();
    });
    navigator.mediaSession.setActionHandler('pause', function() {
        var el = document.querySelector('#nia-embed-player audio, #nia-embed-player video');
        if (el) el.pause();
    });
    navigator.mediaSession.setActionHandler('stop', function() {
        var el = document.querySelector('#nia-embed-player audio, #nia-embed-player video');
        if (el) { el.pause(); el.currentTime = 0; }
    });
    try { navigator.mediaSession.setActionHandler('previoustrack', function() {}); } catch (e) {}
    try { navigator.mediaSession.setActionHandler('nexttrack', function() {}); } catch (e) {}
    var el = document.querySelector('#nia-embed-player audio, #nia-embed-player video');
    if (el) {
        el.addEventListener('play', function() { navigator.mediaSession.playbackState = 'playing'; });
        el.addEventListener('pause', function() { navigator.mediaSession.playbackState = 'paused'; });
    }
})();
</script>
<script>
(function() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?php echo url('sw.js'); ?>').catch(function() {});
    }
})();
</script>
<p style="margin: 0; padding: 0.75rem 1rem; font-size: 0.875rem; color: #9ca3af;">Tip: Add to Home Screen for better background play on iOS.</p>
<?php
} else {
    echo '<p style="padding: 1rem;">Video not found.</p>';
}
?>
</body>
</html>
