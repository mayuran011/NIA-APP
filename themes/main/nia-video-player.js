/**
 * Nia Video Player — custom YouTube-style controls.
 * Init with NiaVideoPlayer.init(containerElement).
 * Supports native <video> and embed (iframe); same chrome for both.
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) define([], factory);
    else if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.NiaVideoPlayer = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function formatTime(seconds) {
        if (!isFinite(seconds) || seconds < 0) return '0:00';
        var m = Math.floor(seconds / 60);
        var s = Math.floor(seconds % 60);
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function setupMediaSession(container, opts) {
        if (!container || !('mediaSession' in navigator)) return;
        var title = container.getAttribute('data-nia-vp-title') || '';
        var artwork = container.getAttribute('data-nia-vp-artwork') || '';
        try {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: title,
                artist: '',
                album: '',
                artwork: artwork ? [{ src: artwork, sizes: '512x512', type: 'image/jpeg' }] : []
            });
        } catch (e) { return; }
        if (opts.video) {
            var video = opts.video;
            navigator.mediaSession.setActionHandler('play', function () { video.play(); });
            navigator.mediaSession.setActionHandler('pause', function () { video.pause(); });
            navigator.mediaSession.setActionHandler('seekbackward', function () { video.currentTime = Math.max(0, video.currentTime - 10); });
            navigator.mediaSession.setActionHandler('seekforward', function () { video.currentTime = Math.min(video.duration, video.currentTime + 10); });
            navigator.mediaSession.setActionHandler('seekto', function (d) { if (d.seekTime != null) video.currentTime = d.seekTime; });
            function updatePosition() {
                if (video.duration && isFinite(video.duration) && navigator.mediaSession.setPositionState) {
                    try { navigator.mediaSession.setPositionState({ duration: video.duration, playbackRate: video.playbackRate, position: video.currentTime }); } catch (err) {}
                }
            }
            video.addEventListener('timeupdate', updatePosition);
            video.addEventListener('durationchange', updatePosition);
        }
        if (opts.ytPlayer) {
            var yt = opts.ytPlayer;
            navigator.mediaSession.setActionHandler('play', function () { if (typeof yt.playVideo === 'function') yt.playVideo(); });
            navigator.mediaSession.setActionHandler('pause', function () { if (typeof yt.pauseVideo === 'function') yt.pauseVideo(); });
            navigator.mediaSession.setActionHandler('seekbackward', function () {
                if (typeof yt.getCurrentTime === 'function' && typeof yt.seekTo === 'function') yt.seekTo(Math.max(0, yt.getCurrentTime() - 10), true);
            });
            navigator.mediaSession.setActionHandler('seekforward', function () {
                if (typeof yt.getCurrentTime === 'function' && typeof yt.getDuration === 'function' && typeof yt.seekTo === 'function') yt.seekTo(Math.min(yt.getDuration(), yt.getCurrentTime() + 10), true);
            });
            navigator.mediaSession.setActionHandler('seekto', function (d) {
                if (d.seekTime != null && typeof yt.seekTo === 'function') yt.seekTo(d.seekTime, true);
            });
        }
    }

    function init(container) {
        if (!container || !container.classList || !container.classList.contains('nia-video-player')) return;
        var video = container.querySelector('.nia-vp-video, video');
        var iframeWrap = container.querySelector('.nia-vp-iframe-wrap');
        var isNative = !!video;
        var bigPlay = container.querySelector('.nia-vp-big-play');
        var btnPlay = container.querySelector('.nia-vp-play');
        var progressWrap = container.querySelector('.nia-vp-progress-wrap');
        var progressBar = container.querySelector('.nia-vp-progress-bar');
        var timeEl = container.querySelector('.nia-vp-time');
        var durationEl = container.querySelector('.nia-vp-duration');
        var btnVolume = container.querySelector('.nia-vp-volume');
        var volumeRange = container.querySelector('.nia-vp-volume-range');
        var btnFullscreen = container.querySelector('.nia-vp-fullscreen');
        var loadingEl = container.querySelector('.nia-vp-loading');
        var btnSettings = container.querySelector('.nia-vp-settings');
        var settingsMenu = container.querySelector('.nia-vp-settings-menu');
        var speedSelect = container.querySelector('.nia-vp-speed-select');
        var btnSubtitles = container.querySelector('.nia-vp-settings-subtitles');
        var btnAirplay = container.querySelector('.nia-vp-airplay');
        var btnCast = container.querySelector('.nia-vp-cast');
        var castTooltip = container.querySelector('.nia-vp-cast-tooltip');
        var isEmbed = container.getAttribute('data-nia-vp-embed') === '1';
        var castMediaUrl = container.getAttribute('data-nia-vp-src') || (video && video.querySelector('source') && video.querySelector('source').src) || '';

        function setPlaying(playing) {
            container.classList.toggle('nia-vp-playing', playing);
            if (bigPlay) bigPlay.setAttribute('aria-label', playing ? 'Pause' : 'Play');
            if (btnPlay && btnPlay.querySelector('.material-icons')) {
                btnPlay.querySelector('.material-icons').textContent = playing ? 'pause' : 'play_arrow';
            }
        }

        function setControlsVisible(visible) {
            container.classList.toggle('nia-vp-controls-visible', visible);
        }

        if (isNative && video) {
            video.removeAttribute('controls');
            video.setAttribute('playsinline', '');
            video.setAttribute('webkit-playsinline', '');
            if (!video.hasAttribute('disableRemotePlayback')) {
                video.setAttribute('x-webkit-airplay', 'allow');
            }

            if (loadingEl) {
                video.addEventListener('loadstart', function () { container.classList.add('nia-vp-loading'); });
                video.addEventListener('canplay', function () { container.classList.remove('nia-vp-loading'); });
                video.addEventListener('waiting', function () { container.classList.add('nia-vp-loading'); });
                video.addEventListener('playing', function () { container.classList.remove('nia-vp-loading'); });
            }

            video.addEventListener('play', function () { setPlaying(true); });
            video.addEventListener('pause', function () { setPlaying(false); });
            video.addEventListener('timeupdate', function () {
                if (timeEl) timeEl.textContent = formatTime(video.currentTime);
                if (progressBar && video.duration && isFinite(video.duration)) {
                    var pct = (100 * video.currentTime / video.duration) || 0;
                    progressBar.style.width = Math.min(100, Math.max(0, pct)) + '%';
                }
            });
            video.addEventListener('durationchange', function () {
                if (durationEl) durationEl.textContent = formatTime(video.duration);
            });
            video.addEventListener('loadedmetadata', function () {
                if (durationEl) durationEl.textContent = formatTime(video.duration);
                if (volumeRange) volumeRange.value = video.volume;
                if (btnVolume && btnVolume.querySelector('.material-icons')) {
                    btnVolume.querySelector('.material-icons').textContent = video.muted || video.volume === 0 ? 'volume_off' : (video.volume < 0.5 ? 'volume_down' : 'volume_up');
                }
            });

            if (bigPlay) {
                bigPlay.addEventListener('click', function () {
                    if (video.paused) video.play(); else video.pause();
                });
            }
            if (btnPlay) {
                btnPlay.addEventListener('click', function () {
                    if (video.paused) video.play(); else video.pause();
                });
            }

            if (progressWrap && progressBar) {
                function seek(e) {
                    var rect = progressWrap.getBoundingClientRect();
                    var x = (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;
                    var pct = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
                    if (video.duration && isFinite(video.duration)) video.currentTime = pct * video.duration;
                }
                progressWrap.addEventListener('click', function (e) {
                    e.preventDefault();
                    seek(e);
                });
                progressWrap.addEventListener('mousedown', function (e) {
                    if (e.button !== 0) return;
                    e.preventDefault();
                    container.classList.add('nia-vp-seeking');
                    seek(e);
                    function move(ev) { seek(ev); }
                    function up() {
                        container.classList.remove('nia-vp-seeking');
                        document.removeEventListener('mousemove', move);
                        document.removeEventListener('mouseup', up);
                    }
                    document.addEventListener('mousemove', move);
                    document.addEventListener('mouseup', up);
                });
                progressWrap.addEventListener('touchstart', function (e) {
                    if (e.cancelable) e.preventDefault();
                    container.classList.add('nia-vp-seeking');
                    seek(e);
                    function touchMove(ev) { seek(ev); }
                    function touchEnd() {
                        container.classList.remove('nia-vp-seeking');
                        progressWrap.removeEventListener('touchmove', touchMove);
                        progressWrap.removeEventListener('touchend', touchEnd);
                    }
                    progressWrap.addEventListener('touchmove', touchMove, { passive: true });
                    progressWrap.addEventListener('touchend', touchEnd);
                }, { passive: false });
            }

            if (volumeRange) {
                volumeRange.addEventListener('input', function () {
                    video.volume = parseFloat(volumeRange.value, 10);
                });
                video.addEventListener('volumechange', function () {
                    volumeRange.value = video.volume;
                    if (btnVolume && btnVolume.querySelector('.material-icons')) {
                        btnVolume.querySelector('.material-icons').textContent = video.muted || video.volume === 0 ? 'volume_off' : (video.volume < 0.5 ? 'volume_down' : 'volume_up');
                    }
                });
            }
            if (btnVolume) {
                btnVolume.addEventListener('click', function () {
                    video.muted = !video.muted;
                });
            }
            if (speedSelect) {
                speedSelect.addEventListener('change', function () {
                    video.playbackRate = parseFloat(speedSelect.value, 10);
                });
            }
            if (btnSubtitles && video.textTracks && video.textTracks.length) {
                var tracks = video.textTracks;
                btnSubtitles.style.display = 'block';
                btnSubtitles.textContent = 'Subtitles Off';
                btnSubtitles.addEventListener('click', function () {
                    var showing = false;
                    for (var t = 0; t < tracks.length; t++) {
                        if (tracks[t].mode === 'showing') { showing = true; break; }
                    }
                    if (showing) {
                        for (var i = 0; i < tracks.length; i++) tracks[i].mode = 'hidden';
                        btnSubtitles.textContent = 'Subtitles Off';
                    } else {
                        for (var j = 0; j < tracks.length; j++) tracks[j].mode = 'showing';
                        btnSubtitles.textContent = 'Subtitles On';
                    }
                });
            } else if (btnSubtitles) {
                btnSubtitles.style.display = 'none';
            }
            if (btnAirplay) {
                if (typeof video.webkitShowPlaybackTargetPicker !== 'function') {
                    btnAirplay.style.display = 'none';
                } else {
                    btnAirplay.addEventListener('click', function () {
                        video.webkitShowPlaybackTargetPicker();
                    });
                }
            }
            setupMediaSession(container, { video: video });
        } else if (iframeWrap) {
            var iframe = iframeWrap.querySelector('iframe');
            var ytId = container.getAttribute('data-nia-vp-yt-id');
            if (ytId && iframe) {
                if (loadingEl) container.classList.add('nia-vp-loading');
                var ytPlayer = null;
                var ytTimeInterval = null;
                function stopYtTimePoll() {
                    if (ytTimeInterval) {
                        clearInterval(ytTimeInterval);
                        ytTimeInterval = null;
                    }
                }
                function seekYt(e) {
                    if (!ytPlayer || typeof ytPlayer.getDuration !== 'function') return;
                    var rect = progressWrap.getBoundingClientRect();
                    var x = (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;
                    var pct = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
                    var dur = ytPlayer.getDuration();
                    if (dur && isFinite(dur)) ytPlayer.seekTo(pct * dur, true);
                }
                function initYtPlayer() {
                    var YT = window.YT;
                    if (!YT || !YT.Player) return;
                    YT.ready(function () {
                        ytPlayer = new YT.Player(iframe, {
                            events: {
                                onReady: function (e) {
                                    var p = e.target;
                                    container._niaVpYtPlayer = p;
                                    if (durationEl) durationEl.textContent = formatTime(p.getDuration() || 0);
                                    if (loadingEl) container.classList.remove('nia-vp-loading');
                                    setupMediaSession(container, { ytPlayer: p });
                                    if (bigPlay) {
                                        bigPlay.addEventListener('click', function () {
                                            if (p.getPlayerState() === 1) p.pauseVideo(); else p.playVideo();
                                        });
                                    }
                                    if (btnPlay) {
                                        btnPlay.addEventListener('click', function () {
                                            if (p.getPlayerState() === 1) p.pauseVideo(); else p.playVideo();
                                        });
                                    }
                                    if (progressWrap && progressBar) {
                                        progressWrap.addEventListener('click', function (e) { e.preventDefault(); seekYt(e); });
                                        progressWrap.addEventListener('mousedown', function (e) {
                                            if (e.button !== 0) return;
                                            e.preventDefault();
                                            container.classList.add('nia-vp-seeking');
                                            seekYt(e);
                                            function move(ev) { seekYt(ev); }
                                            function up() {
                                                container.classList.remove('nia-vp-seeking');
                                                document.removeEventListener('mousemove', move);
                                                document.removeEventListener('mouseup', up);
                                            }
                                            document.addEventListener('mousemove', move);
                                            document.addEventListener('mouseup', up);
                                        });
                                        progressWrap.addEventListener('touchstart', function (e) {
                                            if (e.cancelable) e.preventDefault();
                                            container.classList.add('nia-vp-seeking');
                                            seekYt(e);
                                            function touchMove(ev) { seekYt(ev); }
                                            function touchEnd() {
                                                container.classList.remove('nia-vp-seeking');
                                                progressWrap.removeEventListener('touchmove', touchMove);
                                                progressWrap.removeEventListener('touchend', touchEnd);
                                            }
                                            progressWrap.addEventListener('touchmove', touchMove, { passive: true });
                                            progressWrap.addEventListener('touchend', touchEnd);
                                        });
                                    }
                                    if (volumeRange) {
                                        volumeRange.value = (p.getVolume() || 0) / 100;
                                        volumeRange.addEventListener('input', function () {
                                            p.setVolume(Math.round(parseFloat(volumeRange.value, 10) * 100));
                                        });
                                    }
                                    if (btnVolume) {
                                        btnVolume.addEventListener('click', function () {
                                            if (p.isMuted()) p.unMute(); else p.mute();
                                        });
                                        function updateVolIcon() {
                                            if (!btnVolume || !btnVolume.querySelector('.material-icons')) return;
                                            var v = p.getVolume();
                                            var muted = p.isMuted();
                                            btnVolume.querySelector('.material-icons').textContent = muted || v === 0 ? 'volume_off' : (v < 50 ? 'volume_down' : 'volume_up');
                                        }
                                        setInterval(updateVolIcon, 500);
                                    }
                                    if (speedSelect) {
                                        speedSelect.addEventListener('change', function () {
                                            var rate = parseFloat(speedSelect.value, 10);
                                            if (typeof p.setPlaybackRate === 'function') p.setPlaybackRate(rate);
                                        });
                                    }
                                },
                                onStateChange: function (e) {
                                    var state = e.data;
                                    setPlaying(state === 1);
                                    if (loadingEl) container.classList.toggle('nia-vp-loading', state === 3);
                                    if (state === 1) {
                                        stopYtTimePoll();
                                        ytTimeInterval = setInterval(function () {
                                            if (!ytPlayer || typeof ytPlayer.getCurrentTime !== 'function') return;
                                            var t = ytPlayer.getCurrentTime();
                                            var d = ytPlayer.getDuration();
                                            if (timeEl) timeEl.textContent = formatTime(t);
                                            if (durationEl && d) durationEl.textContent = formatTime(d);
                                            if (progressBar && d && isFinite(d)) {
                                                var pct = (100 * t / d) || 0;
                                                progressBar.style.width = Math.min(100, Math.max(0, pct)) + '%';
                                            }
                                            if (navigator.mediaSession && navigator.mediaSession.setPositionState && d && isFinite(d)) {
                                                try { navigator.mediaSession.setPositionState({ duration: d, playbackRate: 1, position: t }); } catch (err) {}
                                            }
                                        }, 250);
                                    } else {
                                        stopYtTimePoll();
                                    }
                                }
                            }
                        });
                    });
                }
                if (window.YT && window.YT.Player) {
                    initYtPlayer();
                } else {
                    var prev = window.onYouTubeIframeAPIReady;
                    window.onYouTubeIframeAPIReady = function () {
                        if (prev) prev();
                        initYtPlayer();
                    };
                    var tag = document.createElement('script');
                    tag.src = 'https://www.youtube.com/iframe_api';
                    var first = document.getElementsByTagName('script')[0];
                    first.parentNode.insertBefore(tag, first);
                }
                if (btnSubtitles) btnSubtitles.style.display = 'none';
                if (btnAirplay) btnAirplay.style.display = 'none';
            } else {
                if (bigPlay) {
                    bigPlay.addEventListener('click', function () {
                        container.classList.toggle('nia-vp-playing');
                        setPlaying(container.classList.contains('nia-vp-playing'));
                    });
                }
                if (timeEl) timeEl.style.visibility = 'hidden';
                if (durationEl) durationEl.style.visibility = 'hidden';
                if (progressWrap) progressWrap.style.pointerEvents = 'none';
                if (btnAirplay) btnAirplay.style.display = 'none';
                if (btnSettings) btnSettings.style.display = 'none';
            }
        }
        if (btnSettings && settingsMenu) {
            btnSettings.addEventListener('click', function (e) {
                e.stopPropagation();
                container.classList.toggle('nia-vp-settings-open');
            });
            settingsMenu.addEventListener('click', function (e) { e.stopPropagation(); });
            document.addEventListener('click', function closeSettings() {
                if (container.classList.contains('nia-vp-settings-open')) {
                    container.classList.remove('nia-vp-settings-open');
                }
            });
        }
        if (btnCast && castTooltip) {
            btnCast.addEventListener('click', function () {
                castTooltip.textContent = isEmbed ? 'Cast this tab from your browser (Cast icon in toolbar)' : 'Cast this tab from your browser to watch on TV';
                container.classList.add('nia-vp-cast-tooltip-visible');
                setTimeout(function () { container.classList.remove('nia-vp-cast-tooltip-visible'); }, 3000);
            });
        }

        if (btnFullscreen) {
            btnFullscreen.addEventListener('click', function () {
                if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                    if (container.requestFullscreen) container.requestFullscreen();
                    else if (container.webkitRequestFullscreen) container.webkitRequestFullscreen();
                    else if (container.webkitRequestFullScreen) container.webkitRequestFullScreen();
                    container.classList.add('nia-vp-fullscreen');
                } else {
                    if (document.exitFullscreen) document.exitFullscreen();
                    else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                    container.classList.remove('nia-vp-fullscreen');
                }
            });
        }
        function updateFullscreenIcon() {
            var isFs = !!(document.fullscreenElement || document.webkitFullscreenElement);
            container.classList.toggle('nia-vp-fullscreen', isFs);
            if (btnFullscreen && btnFullscreen.querySelector('.material-icons')) {
                btnFullscreen.querySelector('.material-icons').textContent = isFs ? 'fullscreen_exit' : 'fullscreen';
                btnFullscreen.setAttribute('aria-label', isFs ? 'Exit fullscreen' : 'Fullscreen');
            }
        }
        document.addEventListener('fullscreenchange', updateFullscreenIcon);
        document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);

        container.addEventListener('mousemove', function () { setControlsVisible(true); });
        container.addEventListener('mouseleave', function () { setControlsVisible(false); });
        container.addEventListener('touchstart', function () { setControlsVisible(true); });

        container.addEventListener('keydown', function (e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON') return;
            if (e.code === 'Space') {
                e.preventDefault();
                if (isNative && video) {
                    if (video.paused) video.play(); else video.pause();
                } else if (container._niaVpYtPlayer && typeof container._niaVpYtPlayer.getPlayerState === 'function') {
                    var state = container._niaVpYtPlayer.getPlayerState();
                    if (state === 1) container._niaVpYtPlayer.pauseVideo(); else container._niaVpYtPlayer.playVideo();
                }
            }
        });
        if (!container.hasAttribute('tabindex')) container.setAttribute('tabindex', '0');

        if (durationEl && isNative && video) {
            var d = video.duration;
            durationEl.textContent = formatTime((d && isFinite(d)) ? d : 0);
        }
    }

    return { init: init };
});
