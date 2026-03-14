<?php
/**
 * Players: JW Player, FlowPlayer, jPlayer, VideoJS; WaveSurfer for music (VideoJS + wavesurfer).
 * Options: choosen-player, remote-player, youtube-player, jwkey, player-logo, etc.
 */

if (!defined('in_nia_app')) {
    exit;
}

class NiaPlayers {

    /** @var string videojs | jwplayer | flowplayer | jplayer */
    protected static function mainPlayer() {
        return get_option('choosen-player', 'videojs');
    }

    /** @var string how to play remote (embed | same) */
    protected static function remotePlayer() {
        return get_option('remote-player', 'embed');
    }

    /** @var string youtube: embed iframe or inline */
    protected static function youtubePlayer() {
        return get_option('youtube-player', 'embed');
    }

    protected static function jwKey() {
        return get_option('jwkey', '');
    }

    protected static function playerLogo() {
        return get_option('player-logo', '');
    }

    /**
     * Resolve playback URL for a video row: stream.php for local, or remote_url / embed.
     */
    public static function playbackUrl($video) {
        if (empty($video)) return '';
        $source = $video->source ?? 'local';
        if ($source === 'local' && !empty($video->file_path)) {
            return stream_url($video->id, '');
        }
        if (in_array($source, ['youtube', 'vimeo', 'dailymotion', 'twitch', 'facebook', 'vine'], true)) {
            $detect = NiaProviders::detect($video->remote_url ?? $video->embed_code ?? '');
            return $detect['embed_url'] ?? $video->remote_url ?? '';
        }
        if (in_array($source, ['gdrive', 'direct_video', 'direct_audio'], true) && !empty($video->remote_url)) {
            return $video->remote_url;
        }
        if (!empty($video->remote_url)) {
            return $video->remote_url;
        }
        return stream_url($video->id, '');
    }

    /**
     * Whether this item should use the music player (VideoJS + WaveSurfer).
     */
    public static function isMusicPlayer($video) {
        return isset($video->type) && $video->type === 'music';
    }

    /**
     * Render player HTML/JS for the given video. Used on single video page and embed.
     *
     * @param object $video Row from vibe_videos
     * @param array  $opts  { container_id, embed_mode, autoplay, no_media_session }
     * @return string HTML fragment
     */
    public static function render($video, $opts = []) {
        if (empty($video)) return '';
        $container_id = $opts['container_id'] ?? 'nia-player-' . (int) $video->id;
        $embed_mode   = !empty($opts['embed_mode']);
        $autoplay     = !empty($opts['autoplay']);
        $no_media_session = !empty($opts['no_media_session']);

        $source = $video->source ?? 'local';
        $url    = self::playbackUrl($video);
        $title  = $video->title ?? '';
        $thumb  = $video->thumb ?? '';
        $is_music = self::isMusicPlayer($video);

        // YouTube: use YouTube's native player (plain iframe, no custom controls/overlays)
        if ($source === 'youtube' && $url) {
            return self::renderEmbedIframe($url, $container_id, $title, $autoplay);
        }
        // Vimeo / Dailymotion / etc.: Nia Video Player (embed with custom chrome)
        if (in_array($source, ['vimeo', 'dailymotion', 'twitch', 'facebook', 'vine', 'gdrive'], true) && $url) {
            return self::renderNiaVideoPlayerEmbed($url, $container_id, $title, $autoplay);
        }

        // Direct video URL (MP4 etc.): Nia Video Player (native)
        if ($source === 'direct_video' && $url && !$is_music) {
            return self::renderNiaVideoPlayerNative($video, $container_id, $opts);
        }

        // Remote URL (non-embed source): Nia Video Player (embed) or legacy
        if ($source === 'remote' && $url && self::remotePlayer() === 'embed') {
            return self::renderNiaVideoPlayerEmbed($url, $container_id, $title, $autoplay);
        }

        // Music: VideoJS + WaveSurfer (vjswaveplayer)
        if ($is_music && $url) {
            return self::renderVideoJsWaveSurfer($video, $container_id, $opts);
        }

        // Local/stream: Nia Video Player (native)
        return self::renderNiaVideoPlayerNative($video, $container_id, $opts);
    }

    /**
     * Shared control bar HTML for Nia Video Player (YouTube-style).
     */
    protected static function niaVideoPlayerControlsHtml($container_id) {
        $cid = _e($container_id);
        return '<div class="nia-vp-progress-wrap" role="slider" aria-label="Progress" tabindex="0">' .
            '<div class="nia-vp-progress"><div class="nia-vp-progress-bar"></div></div></div>' .
            '<div class="nia-vp-bar">' .
            '<button type="button" class="nia-vp-btn nia-vp-play" aria-label="Play"><span class="material-icons">play_arrow</span></button>' .
            '<span class="nia-vp-time">0:00</span><span class="nia-vp-time-sep">/</span><span class="nia-vp-duration">0:00</span>' .
            '<div class="nia-vp-spacer"></div>' .
            '<div class="nia-vp-volume-wrap">' .
            '<button type="button" class="nia-vp-btn nia-vp-volume" aria-label="Volume"><span class="material-icons">volume_up</span></button>' .
            '<input type="range" class="nia-vp-volume-range" min="0" max="1" step="0.05" value="1" aria-label="Volume">' .
            '</div>' .
            '<button type="button" class="nia-vp-btn nia-vp-settings" aria-label="Settings" aria-haspopup="true"><span class="material-icons">settings</span></button>' .
            '<div class="nia-vp-settings-menu" role="menu" aria-label="Settings">' .
            '<div class="nia-vp-settings-speed"><span class="nia-vp-settings-label">Speed</span>' .
            '<select class="nia-vp-speed-select" aria-label="Playback speed"><option value="0.5">0.5x</option><option value="0.75">0.75x</option><option value="1" selected>1x</option><option value="1.25">1.25x</option><option value="1.5">1.5x</option><option value="1.75">1.75x</option><option value="2">2x</option></select></div>' .
            '<button type="button" class="nia-vp-settings-subtitles" role="menuitem">Subtitles Off</button></div>' .
            '<button type="button" class="nia-vp-btn nia-vp-airplay" aria-label="AirPlay" title="AirPlay"><span class="material-icons">airplay</span></button>' .
            '<div class="nia-vp-cast-wrap"><button type="button" class="nia-vp-btn nia-vp-cast" aria-label="Cast" title="Cast to TV"><span class="material-icons">cast</span></button><span class="nia-vp-cast-tooltip" aria-hidden="true">Cast this tab from your browser</span></div>' .
            '<button type="button" class="nia-vp-btn nia-vp-fullscreen" aria-label="Fullscreen"><span class="material-icons">fullscreen</span></button>' .
            '</div>';
    }

    /**
     * Nia Video Player — embed (YouTube, Vimeo, etc.): same chrome, iframe inside.
     * Plain embed URL so video always displays; optional YouTube API can be enabled via data-nia-vp-yt-id.
     */
    protected static function renderNiaVideoPlayerEmbed($embedUrl, $container_id, $title, $autoplay) {
        $sep = strpos($embedUrl, '?') !== false ? '&' : '?';
        if ($autoplay) $embedUrl .= $sep . 'autoplay=1';
        $ytId = null;
        if (preg_match('#(?:youtube\.com/embed/|youtu\.be/)([a-zA-Z0-9_-]{11})#', $embedUrl, $m)) {
            $ytId = $m[1];
            $embedUrl .= (strpos($embedUrl, '?') !== false ? '&' : '?') . 'enablejsapi=1';
        }
        $logo = self::playerLogo();
        $logoHtml = $logo ? '<div class="nia-player-logo position-absolute top-0 start-0 m-2" style="z-index:6"><img src="' . _e($logo) . '" alt="" height="28"></div>' : '';
        $cssUrl = url('themes/main/nia-video-player.css');
        $jsUrl = url('themes/main/nia-video-player.js');
        $iframeId = _e($container_id) . '-yt-iframe';
        $dataYt = $ytId !== null ? ' data-nia-vp-yt-id="' . _e($ytId) . '"' : '';
        $ytCovers = $ytId !== null
            ? '<div class="nia-vp-yt-cover nia-vp-yt-cover-top" aria-hidden="true"></div>'
            . '<div class="nia-vp-yt-cover nia-vp-yt-cover-bottom" aria-hidden="true"></div>'
            . '<div class="nia-vp-yt-cover nia-vp-yt-cover-corner" aria-hidden="true"></div>'
            : '';
        $html = '<link rel="stylesheet" href="' . _e($cssUrl) . '">' .
            '<div class="nia-video-player nia-vp-embed position-relative bg-black" id="' . _e($container_id) . '" data-nia-vp-embed="1"' . $dataYt . ' style="position:relative; width:100%; padding-bottom:56.25%; height:0; overflow:hidden;">' .
            $logoHtml .
            '<div class="nia-vp-media" style="position:absolute; top:0; left:0; width:100%; height:100%;"><div class="nia-vp-iframe-wrap" style="width:100%; height:100%;">' .
            '<iframe id="' . $iframeId . '" src="' . _e($embedUrl) . '" title="' . _e($title) . '" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"></iframe>' .
            '</div></div>' .
            $ytCovers .
            '<div class="nia-vp-controls">' . self::niaVideoPlayerControlsHtml($container_id) . '</div>' .
            '<button type="button" class="nia-vp-big-play" aria-label="Play"><span class="nia-vp-icon-play">▶</span></button>' .
            '<div class="nia-vp-loading" aria-hidden="true">Loading…</div>' .
            '</div>' .
            '<script src="' . _e($jsUrl) . '"></script>' .
            '<script>(function(){var c=document.getElementById("' . _e($container_id) . '");if(c&&typeof NiaVideoPlayer!=="undefined")NiaVideoPlayer.init(c);else window.addEventListener("DOMContentLoaded",function(){var el=document.getElementById("' . _e($container_id) . '");if(el&&typeof NiaVideoPlayer!=="undefined")NiaVideoPlayer.init(el);});})();</script>';
        return $html;
    }

    /**
     * Nia Video Player — native (MP4, local stream): same chrome, <video> inside.
     */
    protected static function renderNiaVideoPlayerNative($video, $container_id, $opts) {
        $url = self::playbackUrl($video);
        $thumb = $video->thumb ?? '';
        if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
        $thumb = _e($thumb);
        $autoplay = !empty($opts['autoplay']) ? ' autoplay' : '';
        $subtitle_url = $opts['subtitle_url'] ?? ($video->subtitle_url ?? '');
        $subtitle_label = $opts['subtitle_label'] ?? 'English';
        $trackHtml = '';
        if ($subtitle_url !== '' && preg_match('/\.(srt|vtt)$/i', $subtitle_url)) {
            $src = (strpos($subtitle_url, 'http') === 0) ? $subtitle_url : rtrim(SITE_URL, '/') . '/' . ltrim($subtitle_url, '/');
            $trackHtml = '<track kind="subtitles" src="' . _e($src) . '" srclang="' . _e($subtitle_label) . '" label="' . _e($subtitle_label) . '" default>';
        }
        $logo = self::playerLogo();
        $logoHtml = $logo ? '<div class="nia-player-logo position-absolute top-0 start-0 m-2" style="z-index:6"><img src="' . _e($logo) . '" alt="" height="28"></div>' : '';
        $cssUrl = url('themes/main/nia-video-player.css');
        $jsUrl = url('themes/main/nia-video-player.js');
        $videoElId = _e($container_id) . '-el';
        $html = '<link rel="stylesheet" href="' . _e($cssUrl) . '">' .
            '<div class="nia-video-player nia-vp-native" id="' . _e($container_id) . '" data-nia-vp-src="' . _e($url) . '">' .
            $logoHtml .
            '<div class="nia-vp-media">' .
            '<video id="' . $videoElId . '" class="nia-vp-video" preload="metadata" playsinline ' . $autoplay . ' poster="' . $thumb . '">' .
            '<source src="' . _e($url) . '" type="video/mp4">' . $trackHtml . '</video>' .
            '<div class="nia-vp-poster"' . ($thumb !== '' ? ' style="background-image:url(' . $thumb . ')"' : '') . '></div>' .
            '</div>' .
            '<div class="nia-vp-controls">' . self::niaVideoPlayerControlsHtml($container_id) . '</div>' .
            '<button type="button" class="nia-vp-big-play" aria-label="Play"><span class="nia-vp-icon-play">▶</span></button>' .
            '<div class="nia-vp-loading" aria-hidden="true">Loading…</div>' .
            '</div>' .
            '<script src="' . _e($jsUrl) . '"></script>' .
            '<script>(function(){var c=document.getElementById("' . _e($container_id) . '");if(c&&typeof NiaVideoPlayer!=="undefined")NiaVideoPlayer.init(c);else window.addEventListener("DOMContentLoaded",function(){var el=document.getElementById("' . _e($container_id) . '");if(el&&typeof NiaVideoPlayer!=="undefined")NiaVideoPlayer.init(el);});})();</script>';
        return $html;
    }

    protected static function renderEmbedIframe($embedUrl, $container_id, $title, $autoplay) {
        $sep = strpos($embedUrl, '?') !== false ? '&' : '?';
        if ($autoplay) {
            $embedUrl .= $sep . 'autoplay=1';
        }
        $logo = self::playerLogo();
        $logoHtml = $logo ? '<div class="nia-player-logo position-absolute top-0 start-0 m-2"><img src="' . _e($logo) . '" alt="" height="28"></div>' : '';
        return '<div class="nia-player-container position-relative bg-black" id="' . _e($container_id) . '" style="position:relative; width:100%; padding-bottom:56.25%; height:0; overflow:hidden;">' .
            $logoHtml .
            '<iframe src="' . _e($embedUrl) . '" title="' . _e($title) . '" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"></iframe>' .
            '</div>';
    }

    protected static function renderVideoJs($video, $container_id, $opts) {
        $url = self::playbackUrl($video);
        $title = _e($video->title ?? '');
        $thumb = _e($video->thumb ?? '');
        $autoplay = !empty($opts['autoplay']) ? ' autoplay' : '';
        $subtitle_url = $opts['subtitle_url'] ?? ($video->subtitle_url ?? '');
        $subtitle_label = $opts['subtitle_label'] ?? 'English';
        $base = rtrim(SITE_URL, '/');
        $logo = self::playerLogo();
        $logoHtml = $logo ? '<img src="' . _e($logo) . '" alt="" class="nia-player-logo-img" height="28">' : '';
        $trackHtml = '';
        if ($subtitle_url !== '' && preg_match('/\.(srt|vtt)$/i', $subtitle_url)) {
            $src = (strpos($subtitle_url, 'http') === 0) ? $subtitle_url : rtrim(SITE_URL, '/') . '/' . ltrim($subtitle_url, '/');
            $trackHtml = '<track kind="subtitles" src="' . _e($src) . '" srclang="' . _e($subtitle_label) . '" label="' . _e($subtitle_label) . '" default>';
        }
        return '<div class="nia-player-container position-relative" id="' . _e($container_id) . '">' .
            $logoHtml .
            '<video id="' . _e($container_id) . '-el" class="video-js vjs-big-play-centered" controls preload="metadata" data-setup="{}" ' . $autoplay . ' poster="' . $thumb . '">' .
            '<source src="' . _e($url) . '" type="video/mp4">' .
            $trackHtml .
            '<p class="vjs-no-js">Enable JavaScript to play.</p>' .
            '</video>' .
            '<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>' .
            '<link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet">' .
            '<script>(function(){ var v=document.getElementById("' . _e($container_id) . '-el"); if(v && typeof videojs !== "undefined") videojs(v); })();</script>' .
            '</div>';
    }

    protected static function renderVideoJsWaveSurfer($video, $container_id, $opts) {
        $url = self::playbackUrl($video);
        $title = _e($video->title ?? '');
        $thumb = _e($video->thumb ?? '');
        $autoplay = !empty($opts['autoplay']) ? ' autoplay' : '';
        $base = rtrim(SITE_URL, '/');
        return '<div class="nia-player-container nia-music-player position-relative" id="' . _e($container_id) . '">' .
            '<audio id="' . _e($container_id) . '-el" class="vjs-wavesurfer-audio" controls preload="metadata" ' . $autoplay . '>' .
            '<source src="' . _e($url) . '" type="audio/mpeg">' .
            '</audio>' .
            '<div id="' . _e($container_id) . '-wave" class="nia-waveform"></div>' .
            '<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>' .
            '<script src="https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js"></script>' .
            '<link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet">' .
            '<script>' .
            '(function(){ var cid="' . _e($container_id) . '"; var audio=document.getElementById(cid+"-el"); var waveEl=document.getElementById(cid+"-wave"); if(!audio||!waveEl) return; if(typeof videojs !== "undefined" && typeof WaveSurfer !== "undefined") { videojs(audio).ready(function(){ var w=WaveSurfer.create({ container: waveEl, waveColor: "#6366f1", progressColor: "#4f46e5", height: 64 }); w.load(audio.querySelector("source").src); audio.addEventListener("play", function(){ w.play(); }); audio.addEventListener("pause", function(){ w.pause(); }); }); } })();' .
            '</script>' .
            '</div>';
    }

    protected static function renderJwPlayer($video, $container_id, $opts) {
        $url = self::playbackUrl($video);
        $key = self::jwKey();
        if ($key === '') {
            return self::renderVideoJs($video, $container_id, $opts);
        }
        $thumb = _e($video->thumb ?? '');
        $autoplay = !empty($opts['autoplay']) ? 'true' : 'false';
        return '<div id="' . _e($container_id) . '"></div>' .
            '<script src="https://ssl.p.jwpcdn.com/player/v/8.28.2/jwplayer.js"></script>' .
            '<script>jwplayer.key="' . _e($key) . '"; jwplayer("' . _e($container_id) . '").setup({ file: "' . _e($url) . '", image: "' . $thumb . '", autostart: ' . $autoplay . ' });</script>';
    }

    protected static function renderFlowPlayer($video, $container_id, $opts) {
        $url = self::playbackUrl($video);
        $thumb = _e($video->thumb ?? '');
        return '<div class="nia-player-container position-relative ratio ratio-16x9 bg-dark" id="' . _e($container_id) . '"></div>' .
            '<script src="https://cdn.flowplayer.com/player/7.2.14/flowplayer.min.js"></script>' .
            '<link rel="stylesheet" href="https://cdn.flowplayer.com/player/7.2.14/skin/minimalist.css">' .
            '<script>flowplayer("#' . _e($container_id) . '", { clip: { sources: [{ src: "' . _e($url) . '", type: "video/mp4" }], poster: "' . $thumb . '" } });</script>';
    }

    protected static function renderJPlayer($video, $container_id, $opts) {
        $url = self::playbackUrl($video);
        $thumb = _e($video->thumb ?? '');
        return '<div id="' . _e($container_id) . '" class="jp-jplayer nia-jplayer"></div>' .
            '<div id="' . _e($container_id) . '-container" class="jp-audio nia-jplayer-container" role="application">' .
            '<div class="jp-gui"><div class="jp-controls"><button class="jp-play" role="button">play</button><button class="jp-stop" role="button">stop</button></div>' .
            '<div class="jp-progress"><div class="jp-seek-bar"><div class="jp-play-bar"></div></div></div></div></div>' .
            '<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>' .
            '<script src="https://cdn.jsdelivr.net/npm/jplayer@2.9.2/dist/jplayer/jquery.jplayer.min.js"></script>' .
            '<script>jQuery(document).ready(function($){ $("#'. _e($container_id) . '").jPlayer({ ready: function(){ $(this).jPlayer("setMedia", { mp4: "' . _e($url) . '" }); }, swfPath: "https://cdn.jsdelivr.net/npm/jplayer@2.9.2/dist/jplayer", supplied: "mp4", solution: "html" }); });</script>';
    }
}
