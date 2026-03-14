</div><!-- .nia-content -->
</div><!-- .nia-layout -->

<nav class="nia-bottom-nav d-lg-none d-flex">
    <a href="<?php echo url(); ?>" class="nia-bottom-nav-item<?php echo (isset($modview) && $modview === 'home') ? ' active' : ''; ?>">
        <span class="material-icons">home</span>
        <span class="nia-bottom-nav-label">Home</span>
    </a>
    <a href="<?php echo url('videos'); ?>" class="nia-bottom-nav-item<?php echo (isset($modview) && in_array($modview, ['video', 'videos', 'watch'], true)) ? ' active' : ''; ?>">
        <span class="material-icons">smart_display</span>
        <span class="nia-bottom-nav-label">Videos</span>
    </a>
    <a href="<?php echo is_logged() ? url('me') : url('login'); ?>" class="nia-bottom-nav-item<?php echo (isset($modview) && $modview === 'me') ? ' active' : ''; ?>">
        <span class="material-icons">video_library</span>
        <span class="nia-bottom-nav-label">Library</span>
    </a>
    <a href="<?php echo is_logged() ? url('me/manage') : url('login'); ?>" class="nia-bottom-nav-item<?php echo (isset($modview) && $modview === 'me.manage') ? ' active' : ''; ?>">
        <span class="material-icons">person</span>
        <span class="nia-bottom-nav-label">You</span>
    </a>
</nav>


<!-- Premium Share Modal -->
<div class="modal fade nia-share-modal" id="niaShareModal" tabindex="-1" aria-labelledby="niaShareModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="niaShareModalLabel">Share</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="nia-share-grid">
                    <a href="#" class="nia-share-item" data-share="whatsapp">
                        <div class="nia-share-icon-wrap bg-whatsapp"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.038 3.284l-.569 2.1c-.149.546.454 1.034.963.767l2.057-.923c.691.314 1.543.513 2.278.514 3.178 0 5.767-2.586 5.768-5.766.001-3.187-2.589-5.766-5.767-5.766zm3.391 8.221c-.142.401-.719.726-.957.771-.245.051-.512.064-1.3-.234-1.27-.482-2.316-1.503-2.924-2.317-.924-1.238-1.558-2.613-1.602-3.173-.044-.56.232-.843.434-1.034.191-.181.415-.271.552-.271.137 0 .272.006.393.017.136.012.272-.039.363.15.121.252.414 1.008.452 1.082.037.073.063.159.012.26-.051.102-.077.165-.152.253-.075.088-.158.196-.226.264-.131.131-.269.273-.116.536.153.263.681 1.121 1.464 1.819.654.582 1.201.812 1.463.945.263.133.415.111.57-.067.155-.178.658-.764.832-1.026.175-.262.349-.221.587-.133.238.089 1.498.706 1.756.834.258.128.43.192.493.301.063.109.063.633-.079 1.034z"/></svg></div>
                        <span class="nia-share-label">WhatsApp</span>
                    </a>
                    <a href="#" class="nia-share-item" data-share="facebook">
                        <div class="nia-share-icon-wrap bg-facebook"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg></div>
                        <span class="nia-share-label">Facebook</span>
                    </a>
                    <a href="#" class="nia-share-item" data-share="twitter">
                        <div class="nia-share-icon-wrap bg-twitter"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></div>
                        <span class="nia-share-label">Twitter</span>
                    </a>
                    <a href="#" class="nia-share-item" data-share="reddit">
                        <div class="nia-share-icon-wrap bg-reddit"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0 1c-4.962 0-9 4.038-9 9 0 4.963 4.038 9 9 9 4.963 0 9-4.037 9-9 0-4.962-4.037-9-9-9zm5 9c0 1.104-.896 2-2 2h-.143c-.417 0-.79-.126-1.1-.34-.959.605-2.261 1.01-3.702 1.091l.732-2.308 1.944.409c.041.512.467.915.986.915.548 0 .991-.441.991-.983-.001-.542-.444-.983-.991-.983-.438 0-.809.284-.937.674l-2.222-.468c-.122-.026-.241.056-.279.176l-.887 2.8c-1.428-.088-2.716-.496-3.662-1.097-.311.213-.683.337-1.083.337-1.104 0-2-.896-2-2 0-.693.355-1.302.894-1.657-.044-.226-.067-.457-.067-.693 0-2.203 2.508-4 5.617-4 3.109 0 5.617 1.797 5.617 4 0 .23-.021.458-.063.682.548.354.912.966.912 1.668zm-5.462 2.873c-.156.155-.386.155-.541 0-.156-.156-.156-.407 0-.563.155-.155.385-.155.541 0 .155.156.155.407 0 .563zm3.179-.563c-.155-.155-.385-.155-.541 0-.156.156-.156.407 0 .563.156.155.386.155.541 0 .156-.156.156-.407 0-.563zm-3.111 2.27c-.017.021-.157.193-.606.193-.45 0-.59-.172-.606-.193-.06-.075-.171-.088-.247-.028-.076.061-.089.172-.029.248.016.02.213.256.882.256.67 0 .867-.236.882-.256.06-.076.047-.187-.028-.248-.076-.06-.188-.047-.248.028z"/></svg></div>
                        <span class="nia-share-label">Reddit</span>
                    </a>
                    <a href="#" class="nia-share-item" data-share="linkedin">
                        <div class="nia-share-icon-wrap bg-linkedin"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5c0 1.381-1.11 2.5-2.48 2.5s-2.48-1.119-2.48-2.5c0-1.38 1.11-2.5 2.48-2.5s2.48 1.12 2.48 2.5zm.02 4.5h-5v16h5v-16zm7.982 0h-4.968v16h4.969v-8.399c0-4.67 6.029-5.052 6.029 0v8.399h4.988v-10.131c0-7.88-8.922-7.593-11.018-3.714v-2.155z"/></svg></div>
                        <span class="nia-share-label">LinkedIn</span>
                    </a>
                    <a href="#" class="nia-share-item" data-share="pinterest">
                        <div class="nia-share-icon-wrap bg-pinterest"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm1.255 16.337c.771-2.313.729-3.418 1.442-5.461 1.013 2.924 3.42 2.378 3.42-1.35 0-3.327-2.909-4.526-5-4.526-2.52 0-5.5 1.516-5.5 4.5 0 1.487.807 2.019 1.189 2.019.229 0 .285-.503.493-1.071-.462-1.168.04-2.859 1.444-2.859 1.332 0 2.227 1.408 2.227 3.018 0 1.547-1.107 3.518-2.227 3.518-.558 0-.961-.312-1.111-.643-.223.864-.811 2.373-1.077 3.124-.199.564-.403 1.171-.607 1.771l-.105.313C8.4 20.3 10.1 21 12 21c4.542 0 7.545-3.057 7.545-7.5s-3-7.5-7.545-7.5c-4.55 0-8.455 3-8.455 7.5a6.002 6.002 0 0 0 2.96 5.1c.148-.48.45-1.192.607-1.763z"/></svg></div>
                        <span class="nia-share-label">Pinterest</span>
                    </a>

                    <a href="#" class="nia-share-item" data-share="telegram">
                        <div class="nia-share-icon-wrap bg-primary" style="background-color: #0088cc !important;"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.25-5.54 3.69-.52.35-1 .53-1.42.52-.47-.01-1.37-.26-2.03-.48-.82-.27-1.47-.42-1.42-.88.03-.24.33-.48.91-.74 3.56-1.55 5.92-2.57 7.09-3.07 3.38-1.41 4.08-1.66 4.54-1.67.1 0 .32.02.47.14.12.1.16.21.18.33.01.07.02.21.01.35z"/></svg></div>
                        <span class="nia-share-label">Telegram</span>
                    </a>
                    <a href="#" class="nia-share-item" data-share="email">
                        <div class="nia-share-icon-wrap bg-email"><span class="material-icons">email</span></div>
                        <span class="nia-share-label">Email</span>
                    </a>
                </div>


                <div class="row g-3 mb-4">
                    <div class="col-sm-8">
                        <div class="nia-copy-box mb-3">
                            <input type="text" class="nia-copy-input" id="niaShareUrl" readonly value="">
                            <button type="button" class="btn btn-primary nia-copy-btn" id="niaCopyLinkBtn">Copy</button>
                        </div>

                        <div class="nia-embed-box">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small">Embed code</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-info" id="niaCopyEmbedBtn">Copy</button>
                            </div>
                            <textarea class="form-control bg-dark border-secondary text-muted small" id="niaShareEmbed" rows="2" readonly></textarea>
                        </div>
                    </div>
                    <div class="col-sm-4 text-center">
                        <div class="ps-sm-2">
                            <span class="fw-bold small d-block mb-2">QR Code</span>
                            <div class="nia-qr-wrap bg-white p-2 rounded shadow-sm d-inline-block">
                                <img id="niaShareQR" src="" alt="QR" style="width: 100%; height: auto; max-width: 120px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="nia-footer border-top border-secondary mt-auto py-4">

    <div class="container-fluid">
        <div class="row text-muted small">
            <div class="col-md-6">&copy; <?php echo date('Y'); ?> <?php echo _e(get_option('sitename', 'Nia App')); ?></div>
            <div class="col-md-6 text-md-end">
                <a href="<?php echo url('blog'); ?>" class="text-muted text-decoration-none">Blog</a>
                <span class="mx-2">|</span>
                <a href="<?php echo url('feed'); ?>" class="text-muted text-decoration-none">Feed</a>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    var sidebar = document.getElementById('niaSidebar');
    var backdrop = document.getElementById('niaSidebarBackdrop');
    var toggle = document.getElementById('niaSidebarToggle');
    var icon = document.getElementById('niaSidebarToggleIcon');
    var layout = document.querySelector('.nia-layout');
    var storageKey = 'niaSidebarCollapsed';

    function isMobile() { return window.matchMedia('(max-width: 991.98px)').matches; }

    function openSidebar() {
        sidebar.classList.add('open');
        backdrop.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
        if (icon) icon.textContent = 'close';
        if (toggle) toggle.setAttribute('aria-label', 'Close menu');
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
        if (icon) icon.textContent = 'menu';
        if (toggle) toggle.setAttribute('aria-label', 'Open menu');
    }

    function setDesktopCollapsed(collapsed) {
        if (!layout) return;
        if (collapsed) {
            layout.classList.add('nia-sidebar-collapsed');
            if (icon) icon.textContent = 'menu';
            if (toggle) toggle.setAttribute('aria-label', 'Open menu');
            try { localStorage.setItem(storageKey, '1'); } catch (e) {}
        } else {
            layout.classList.remove('nia-sidebar-collapsed');
            if (icon) icon.textContent = 'close';
            if (toggle) toggle.setAttribute('aria-label', 'Close menu');
            try { localStorage.setItem(storageKey, '0'); } catch (e) {}
        }
    }

    if (sidebar && backdrop && toggle && layout) {
        toggle.addEventListener('click', function() {
            if (isMobile()) {
                if (sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                setDesktopCollapsed(!layout.classList.contains('nia-sidebar-collapsed'));
            }
        });
        backdrop.addEventListener('click', function() {
            if (isMobile()) closeSidebar();
        });

        if (!isMobile()) {
            var saved = '';
            try { saved = localStorage.getItem(storageKey); } catch (e) {}
            if (saved === '1') {
                layout.classList.add('nia-sidebar-collapsed');
                if (icon) icon.textContent = 'menu';
                if (toggle) toggle.setAttribute('aria-label', 'Open menu');
            } else {
                if (icon) icon.textContent = 'close';
                if (toggle) toggle.setAttribute('aria-label', 'Close menu');
            }
        }

        window.addEventListener('resize', function() {
            if (!isMobile()) {
                closeSidebar();
                var saved = '';
                try { saved = localStorage.getItem(storageKey); } catch (e) {}
                if (saved === '1') {
                    layout.classList.add('nia-sidebar-collapsed');
                    if (icon) icon.textContent = 'menu';
                } else {
                    layout.classList.remove('nia-sidebar-collapsed');
                    if (icon) icon.textContent = 'close';
                }
            } else {
                layout.classList.remove('nia-sidebar-collapsed');
                closeSidebar();
            }
        });
    }
})();
(function() {
    var searchInput = document.getElementById('nia-search-input');
    var dropdown = document.getElementById('nia-live-search-dropdown');
    if (!searchInput || !dropdown) return;
    var apiSearch = '<?php echo url('api/search'); ?>';
    var debounceTimer = null;
    var hideTimer = null;

    function showDropdown() { dropdown.classList.remove('d-none'); }
    function hideDropdown() { dropdown.classList.add('d-none'); }

    function esc(s) { return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function renderResults(data) {
        var q = (data && data.q) ? data.q : '';
        var videos = (data && data.videos) ? data.videos : [];
        var channels = (data && data.channels) ? data.channels : [];
        var playlists = (data && data.playlists) ? data.playlists : [];
        var images = (data && data.images) ? data.images : [];
        var parts = [];
        if (videos.length) {
            parts.push('<div class="nia-live-search-section"><div class="nia-live-search-label">Videos</div><ul class="nia-live-search-list">');
            videos.slice(0, 6).forEach(function(v) {
                var title = esc(v.title);
                var channel = esc(v.channel || '');
                var channelLine = channel ? '<div class="nia-live-search-channel text-muted text-truncate">' + channel + '</div>' : '';
                parts.push('<li><a class="nia-live-search-item nia-live-search-item-video d-flex align-items-center gap-2 text-decoration-none text-reset" href="' + esc(v.url || '') + '"><img src="' + esc(v.thumb || '') + '" alt="" class="nia-live-search-thumb flex-shrink-0"><div class="nia-live-search-meta min-width-0 flex-grow-1"><div class="nia-live-search-title text-truncate">' + title + '</div>' + channelLine + '</div></a></li>');
            });
            parts.push('</ul></div>');
        }
        if (channels.length) {
            parts.push('<div class="nia-live-search-section"><div class="nia-live-search-label">Channels</div><ul class="nia-live-search-list">');
            channels.slice(0, 3).forEach(function(c) {
                var name = esc(c.name || c.username || '');
                parts.push('<li><a class="nia-live-search-item d-flex align-items-center gap-2 text-decoration-none text-reset" href="' + esc(c.url || '') + '"><span class="material-icons flex-shrink-0 nia-live-search-icon nia-live-search-icon-channel">person</span><div class="text-truncate">' + name + '</div></a></li>');
            });
            parts.push('</ul></div>');
        }
        if (playlists.length) {
            parts.push('<div class="nia-live-search-section"><div class="nia-live-search-label">Playlists</div><ul class="nia-live-search-list">');
            playlists.slice(0, 2).forEach(function(p) {
                parts.push('<li><a class="nia-live-search-item d-flex align-items-center gap-2 text-decoration-none text-reset" href="' + esc(p.url || '') + '"><span class="material-icons flex-shrink-0 nia-live-search-icon nia-live-search-icon-channel">playlist_play</span><div class="text-truncate">' + esc(p.name || '') + '</div></a></li>');
            });
            parts.push('</ul></div>');
        }
        if (parts.length === 0) {
            parts.push('<div class="nia-live-search-empty p-3 text-muted small">No results for "' + (q ? q.replace(/</g, '&lt;') : '') + '"</div>');
        } else {
            parts.push('<div class="nia-live-search-footer p-2 border-top border-secondary"><a class="small text-primary text-decoration-none" href="<?php echo _e(url('show')); ?>?q=' + encodeURIComponent(q) + '">View all results</a></div>');
        }
        dropdown.innerHTML = parts.join('');
        showDropdown();
    }

    function doSearch() {
        var q = (searchInput.value || '').trim();
        if (q.length < 1) {
            hideDropdown();
            return;
        }
        if (q.length < 2) {
            return;
        }
        fetch(apiSearch + '?q=' + encodeURIComponent(q) + '&limit=10')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (searchInput.value.trim() === q) renderResults(data);
            })
            .catch(function() {
                if (searchInput.value.trim() === q) dropdown.innerHTML = '<div class="p-3 text-muted small">Search unavailable</div>';
                showDropdown();
            });
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doSearch, 280);
    });
    searchInput.addEventListener('focus', function() {
        clearTimeout(hideTimer);
        if ((searchInput.value || '').trim().length >= 1 && dropdown.innerHTML.trim()) showDropdown();
    });
    searchInput.addEventListener('blur', function() {
        hideTimer = setTimeout(hideDropdown, 280);
    });
    dropdown.addEventListener('mousedown', function(e) {
        clearTimeout(hideTimer);
        e.preventDefault();
    });
})();
(function() {
    var addToUrl = '<?php echo url('app/ajax/addto.php'); ?>';
    document.querySelectorAll('.nia-addto-later, .nia-addto-likes').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var mediaId = document.body.getAttribute('data-nia-media-id');
            var mediaType = document.body.getAttribute('data-nia-media-type') || 'video';
            if (!mediaId) return;
            var action = el.getAttribute('data-action');
            var fd = new FormData();
            fd.append('action', action);
            fd.append('media_id', mediaId);
            fd.append('media_type', mediaType);
            fetch(addToUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(d) { if (d.ok) el.classList.add('text-success'); });
        });
    });
})();
</script>
<script>
(function() {
    var main = document.querySelector('main[data-pull-to-refresh]');
    if (!main) return;
    var ptr = document.createElement('div');
    ptr.className = 'nia-ptr';
    ptr.setAttribute('aria-live', 'polite');
    ptr.innerHTML = '<span class="material-icons">refresh</span><span>Pull to refresh</span>';
    document.body.insertBefore(ptr, document.body.firstChild);
    var startY = 0, pulling = false;
    document.addEventListener('touchstart', function(e) {
        if (window.scrollY <= 0) { startY = e.touches[0].clientY; pulling = true; }
    }, { passive: true });
    document.addEventListener('touchmove', function(e) {
        if (!pulling || window.scrollY > 0) return;
        var y = e.touches[0].clientY;
        if (y - startY > 60) ptr.classList.add('visible');
    }, { passive: true });
    document.addEventListener('touchend', function() {
        if (ptr.classList.contains('visible')) location.reload();
        ptr.classList.remove('visible');
        pulling = false;
    });
})();
</script>
<?php
if (function_exists('do_action')) {
    do_action('vibe_footer');
}
?>
<script>
(function() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?php echo url('sw.js'); ?>').catch(function() {});
    }
})();
</script>
<?php if (is_logged()): ?>
<script>
(function() {
    var btn = document.getElementById('nia-notifications-btn');
    var list = document.getElementById('notifications-list');
    var badge = document.querySelector('.nia-notif-badge');
    var markRead = document.getElementById('mark-notifications-read');
    var url = '<?php echo url('app/ajax/getNotifications.php'); ?>';
    var loading = false;

    if (btn && list) {
        btn.addEventListener('show.bs.dropdown', function() {
            if (loading) return;
            loading = true;
            list.innerHTML = '<div class="p-4 text-center text-muted small"><div class="spinner-border spinner-border-sm me-2"></div> Loading...</div>';
            
            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    loading = false;
                    if (data.ok && data.html) {
                        list.innerHTML = data.html;
                        if (badge) {
                             if (data.unread > 0) {
                                 badge.textContent = data.unread > 9 ? '9+' : data.unread;
                                 badge.style.display = 'block';
                             } else {
                                 badge.style.display = 'none';
                             }
                        }
                    }
                })
                .catch(function() {
                    loading = false;
                    list.innerHTML = '<div class="p-4 text-center text-muted small">Could not load notifications</div>';
                });
        });
    }

    if (markRead) {
        markRead.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fetch(url + '?action=mark_read')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.ok) {
                        if (badge) badge.style.display = 'none';
                        document.querySelectorAll('.list-group-item.bg-dark.bg-opacity-25').forEach(function(el) {
                            el.classList.remove('bg-dark', 'bg-opacity-25');
                        });
                    }
                });
        });
    }
})();
</script>
<?php endif; ?>

<script>
(function() {
    var shareModalEl = document.getElementById('niaShareModal');
    if (!shareModalEl) return;
    var shareModal = new bootstrap.Modal(shareModalEl);
    var shareUrlInput = document.getElementById('niaShareUrl');
    var shareEmbedInput = document.getElementById('niaShareEmbed');
    var copyBtn = document.getElementById('niaCopyLinkBtn');
    var copyEmbedBtn = document.getElementById('niaCopyEmbedBtn');

    window.niaOpenShareModal = function(url, title, mediaId) {
        shareUrlInput.value = url;
        // QR Code
        document.getElementById('niaShareQR').src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(url);
        
        if (mediaId && mediaId !== 'null') {
            shareEmbedInput.value = '<iframe width="560" height="315" src="' + window.location.origin + '/embed/' + mediaId + '" frameborder="0" allowfullscreen></iframe>';
            var eb = document.querySelector('.nia-embed-box');
            if (eb) eb.style.display = 'block';
        } else {
            var eb = document.querySelector('.nia-embed-box');
            if (eb) eb.style.display = 'none';
        }
        shareModal.show();
        
        // Update social links
        document.querySelectorAll('.nia-share-item').forEach(function(item) {
            var type = item.dataset.share;
            var href = '#';
            var u = encodeURIComponent(url);
            var t = encodeURIComponent(title);
            if (type === 'whatsapp') href = 'https://api.whatsapp.com/send?text=' + t + '%20' + u;
            else if (type === 'facebook') href = 'https://www.facebook.com/sharer/sharer.php?u=' + u;
            else if (type === 'twitter') href = 'https://twitter.com/intent/tweet?text=' + t + '&url=' + u;
            else if (type === 'reddit') href = 'https://www.reddit.com/submit?url=' + u + '&title=' + t;
            else if (type === 'linkedin') href = 'https://www.linkedin.com/sharing/share-offsite/?url=' + u;
            else if (type === 'pinterest') href = 'https://pinterest.com/pin/create/button/?url=' + u + '&description=' + t;
            else if (type === 'telegram') href = 'https://t.me/share/url?url=' + u + '&text=' + t;
            else if (type === 'email') href = 'mailto:?subject=' + t + '&body=' + u;
            item.href = href;
            item.target = '_blank';
        });
    };

    function copyToClipboard(input, btn) {
        input.select();
        document.execCommand('copy');
        var oldText = btn.textContent;
        btn.textContent = 'Copied!';
        btn.classList.replace('btn-primary', 'btn-success');
        setTimeout(function() {
            btn.textContent = oldText;
            btn.classList.replace('btn-success', 'btn-primary');
        }, 2000);
    }

    if (copyBtn) copyBtn.addEventListener('click', function() { copyToClipboard(shareUrlInput, copyBtn); });
    if (copyEmbedBtn) copyEmbedBtn.addEventListener('click', function() { copyToClipboard(shareEmbedInput, copyEmbedBtn); });

    // Global listener for share buttons
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.nia-share-btn');
        if (btn) {
            e.preventDefault();
            var url = btn.dataset.url || window.location.href;
            var title = btn.dataset.title || document.title;
            var mediaId = btn.dataset.mediaId || null;
            window.niaOpenShareModal(url, title, mediaId);
        }
    });
})();
</script>

</body>
</html>
