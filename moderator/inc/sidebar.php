<?php
if (!defined('in_nia_app')) exit;
$sec = admin_section();
$base = admin_url();

$groups = [
    'main' => ['label' => null, 'items' => [
        ['section' => '', 'label' => 'Dashboard', 'icon' => 'dashboard', 'url' => $base],
        ['section' => 'analytics', 'label' => 'Analytics', 'icon' => 'analytics', 'url' => admin_url('analytics')],
    ]],
    'content' => ['label' => 'Content', 'items' => [
        ['section' => 'videos', 'label' => 'Videos', 'icon' => 'videocam', 'url' => admin_url('videos')],
        ['section' => 'music', 'label' => 'Music', 'icon' => 'music_note', 'url' => admin_url('music')],
        ['section' => 'images', 'label' => 'Images', 'icon' => 'image', 'url' => admin_url('images')],
        ['section' => 'broken-videos', 'label' => 'Unavailable / Broken videos', 'icon' => 'warning', 'url' => admin_url('broken-videos')],
        ['section' => 'channels', 'label' => 'Channels', 'icon' => 'folder', 'url' => admin_url('channels')],
        ['section' => 'playlists', 'label' => 'Playlists', 'icon' => 'playlist_play', 'url' => admin_url('playlists')],
    ]],
    'community' => ['label' => 'Community', 'items' => [
        ['section' => 'users', 'label' => 'Users', 'icon' => 'people', 'url' => admin_url('users')],
        ['section' => 'comments', 'label' => 'Comments', 'icon' => 'comment', 'url' => admin_url('comments')],
        ['section' => 'reports', 'label' => 'Reports', 'icon' => 'flag', 'url' => admin_url('reports')],
    ]],
    'site' => ['label' => 'Site', 'items' => [
        ['section' => 'homepage', 'label' => 'Homepage', 'icon' => 'home', 'url' => admin_url('homepage')],
        ['section' => 'video-page', 'label' => 'Video page', 'icon' => 'video_settings', 'url' => admin_url('video-page')],
        ['section' => 'music-page', 'label' => 'Music page', 'icon' => 'library_music', 'url' => admin_url('music-page')],
        ['section' => 'image-page', 'label' => 'Image page', 'icon' => 'image', 'url' => admin_url('image-page')],
        ['section' => 'article-page', 'label' => 'Blog page', 'icon' => 'article', 'url' => admin_url('article-page')],
        ['section' => 'blog', 'label' => 'Blog (posts)', 'icon' => 'article', 'url' => admin_url('blog')],
        ['section' => 'blogcat', 'label' => 'Blog categories', 'icon' => 'category', 'url' => admin_url('blogcat')],
        ['section' => 'staticpages', 'label' => 'Static pages', 'icon' => 'description', 'url' => admin_url('staticpages')],
        ['section' => 'seo', 'label' => 'SEO / SEF', 'icon' => 'search', 'url' => admin_url('seo')],
        ['section' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'url' => admin_url('settings')],
        ['section' => 'languages', 'label' => 'Languages', 'icon' => 'language', 'url' => admin_url('languages')],
        ['section' => 'maintenance', 'label' => 'Maintenance', 'icon' => 'construction', 'url' => admin_url('maintenance')],
        ['section' => 'theme', 'label' => 'Theme Customizer', 'icon' => 'palette', 'url' => admin_url('theme')],
        ['section' => 'announcement', 'label' => 'Announcements', 'icon' => 'campaign', 'url' => admin_url('announcement')],
        ['section' => 'membership', 'label' => 'Membership & Plans', 'icon' => 'workspace_premium', 'url' => admin_url('membership')],
    ]],
    'tools' => ['label' => 'Tools', 'items' => [
        ['section' => 'health', 'label' => 'System health', 'icon' => 'monitor_heart', 'url' => admin_url('health')],
        ['section' => 'errorlog', 'label' => 'Error log', 'icon' => 'bug_report', 'url' => admin_url('errorlog')],
        ['section' => 'ads', 'label' => 'Ads', 'icon' => 'campaign', 'url' => admin_url('ads')],
        ['section' => 'plugins', 'label' => 'Plugins', 'icon' => 'extension', 'url' => admin_url('plugins')],
        ['section' => 'cache', 'label' => 'Cache', 'icon' => 'cached', 'url' => admin_url('cache')],
        ['section' => 'youtube', 'label' => 'YouTube', 'icon' => 'video_library', 'url' => admin_url('youtube')],
        ['section' => 'download', 'label' => 'Download', 'icon' => 'download', 'url' => admin_url('download')],
        ['section' => 'vine', 'label' => 'Vine', 'icon' => 'movie', 'url' => admin_url('vine')],
        ['section' => 'activity', 'label' => 'Activity log', 'icon' => 'history', 'url' => admin_url('activity')],
    ]],
];

function admin_sec_in_group($group_items, $sec) {
    foreach ($group_items as $it) if (($it['section'] ?? '') === $sec) return true;
    return false;
}
?>
<style>
/* Premium Sidebar Customizations */
.admin-sidebar {
    background: linear-gradient(180deg, #1e1e2d 0%, #151521 100%);
    color: #a2a3b7;
    border-right: 1px solid #2b2b40 !important;
}
.admin-sidebar a.nav-link, .admin-sidebar button.nav-link {
    color: #a2a3b7;
    transition: all 0.3s ease;
    border-radius: 6px;
    margin-bottom: 2px;
}
.admin-sidebar a.nav-link:hover, .admin-sidebar button.nav-link:hover {
    color: #ffffff;
    background-color: rgba(255,255,255,0.05);
}
.admin-sidebar .nav-link.active, .admin-sidebar button.nav-link.active {
    color: #ffffff !important;
    background-color: #3699ff !important;
    box-shadow: 0 4px 10px rgba(54, 153, 255, 0.3);
}
.admin-sidebar .nav-item .collapse .nav-link {
    background-color: transparent !important;
    box-shadow: none !important;
    padding-left: 2.8rem;
    position: relative;
}
.admin-sidebar .nav-item .collapse .nav-link:hover {
    color: #3699ff !important;
}
.admin-sidebar .nav-item .collapse .nav-link.active {
    color: #3699ff !important;
}
.admin-sidebar .nav-item .collapse .nav-link::before {
    content: "";
    position: absolute;
    left: 1.5rem;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #494b74;
    transition: all 0.2s;
}
.admin-sidebar .nav-item .collapse .nav-link.active::before,
.admin-sidebar .nav-item .collapse .nav-link:hover::before {
    background-color: #3699ff;
}
.sidebar-brand {
    padding: 1rem 0 1.5rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    margin-bottom: 1.5rem;
}
.sidebar-icon-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 8px;
    background: #3699ff;
    color: #fff;
    margin-right: 12px;
    box-shadow: 0 4px 8px rgba(54,153,255,0.3);
}
/* Scrollbar */
.admin-sidebar-menu::-webkit-scrollbar {
    width: 5px;
}
.admin-sidebar-menu::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 4px;
}
.admin-sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.2);
}
</style>

<nav class="admin-sidebar admin-sidebar-nav vh-100 p-3 d-flex flex-column position-fixed" style="width: 260px; z-index: 1040; overflow-y: auto;">
    <div class="sidebar-brand d-flex align-items-center flex-shrink-0">
        <div class="sidebar-icon-brand">
            <span class="material-icons" style="font-size: 20px;">layers</span>
        </div>
        <div>
            <a class="text-white text-decoration-none fw-bolder fs-5" href="<?php echo _e(url()); ?>" target="_blank"><?php echo _e(get_option('sitename', 'Nia App')); ?></a>
            <span class="text-white-50 small d-block" style="font-size: 0.75rem; letter-spacing: 1px; text-transform: uppercase;">Admin Central</span>
        </div>
    </div>
    
    <div class="admin-sidebar-menu flex-grow-1 pe-2">
        <div class="text-uppercase text-white-50 fw-bold mb-2 ps-2" style="font-size: 0.7rem; letter-spacing: 1px;">Navigation</div>
        <ul class="nav flex-column mb-4">
            <?php foreach ($groups as $gid => $group) {
                $sidebar_group_items = $group['items'];
                $label = $group['label'];
                $is_main = ($label === null);
                $has_active = admin_sec_in_group($sidebar_group_items, $sec);
                $collapse_id = 'admin-collapse-' . $gid;
                
                // Group Icons
                $group_icon = 'folder';
                if($gid == 'content') $group_icon = 'view_quilt';
                elseif($gid == 'community') $group_icon = 'group_work';
                elseif($gid == 'site') $group_icon = 'language';
                elseif($gid == 'tools') $group_icon = 'auto_fix_high';

                if ($is_main) {
                    foreach ($sidebar_group_items as $it) {
                        $active = ($sec === ($it['section'] ?? '')) ? ' active' : '';
                        echo '<li class="nav-item"><a class="nav-link' . $active . ' d-flex align-items-center py-2 px-3" href="' . _e($it['url']) . '"><span class="material-icons me-3" style="font-size:1.3rem;">' . _e($it['icon']) . '</span> <span class="fw-medium">' . _e($it['label']) . '</span></a></li>';
                    }
                    continue;
                }
            ?>
            <li class="nav-item border-top border-secondary border-opacity-10 mt-2 pt-2">
                <button class="nav-link w-100 text-start border-0 bg-transparent d-flex align-items-center justify-content-between py-2 px-3 <?php echo $has_active ? 'text-white fw-bold' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapse_id; ?>" aria-expanded="<?php echo $has_active ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapse_id; ?>">
                    <div class="d-flex align-items-center">
                        <span class="material-icons me-3" style="font-size:1.3rem; opacity: 0.8;"><?php echo $group_icon; ?></span>
                        <span class="fw-medium text-uppercase tracking-wider" style="font-size: 0.8rem; letter-spacing: 0.5px;"><?php echo _e($label); ?></span>
                    </div>
                    <span class="material-icons" style="font-size:1.2rem; transition: transform 0.3s; <?php echo $has_active ? 'transform: rotate(180deg);' : ''; ?>">expand_more</span>
                </button>
                <div class="collapse<?php echo $has_active ? ' show' : ''; ?> mt-1" id="<?php echo $collapse_id; ?>">
                    <ul class="nav flex-column pb-1">
                        <?php foreach ($sidebar_group_items as $it) {
                            $active = ($sec === ($it['section'] ?? '')) ? ' active' : '';
                            echo '<li class="nav-item"><a class="nav-link py-2' . $active . ' d-flex align-items-center" href="' . _e($it['url']) . '"><span class="fw-medium small">' . _e($it['label']) . '</span></a></li>';
                        } ?>
                    </ul>
                </div>
            </li>
            <?php } ?>
        </ul>
        
        <div class="text-uppercase text-white-50 fw-bold mb-2 ps-2 mt-4" style="font-size: 0.7rem; letter-spacing: 1px;">Quick Links</div>
        <ul class="nav flex-column shadow-sm rounded p-2" style="background: rgba(0,0,0,0.2);">
            <li class="nav-item"><a class="nav-link d-flex align-items-center py-2 px-3 text-info" href="<?php echo _e(url()); ?>" target="_blank"><span class="material-icons me-3" style="font-size:1.3rem;">open_in_new</span> <span class="fw-medium small">View Frontend</span></a></li>
            <li class="nav-item border-top border-secondary border-opacity-25 mt-1 pt-1"><a class="nav-link d-flex align-items-center py-2 px-3 text-warning" href="<?php echo _e(url('dashboard')); ?>" target="_blank"><span class="material-icons me-3" style="font-size:1.3rem;">manage_accounts</span> <span class="fw-medium small">User Account</span></a></li>
        </ul>
    </div>
</nav>
<script>
// Handle rotation of collapse icons
document.addEventListener('DOMContentLoaded', function() {
    var collapsibles = document.querySelectorAll('.admin-sidebar-menu .collapse');
    collapsibles.forEach(function(collapseEl) {
        collapseEl.addEventListener('show.bs.collapse', function () {
            var btn = document.querySelector('[data-bs-target="#' + this.id + '"]');
            if(btn) {
                var icon = btn.querySelector('.material-icons:last-child');
                if(icon) icon.style.transform = 'rotate(180deg)';
                btn.classList.add('text-white', 'fw-bold');
            }
        });
        collapseEl.addEventListener('hide.bs.collapse', function () {
            var btn = document.querySelector('[data-bs-target="#' + this.id + '"]');
            if(btn) {
                var icon = btn.querySelector('.material-icons:last-child');
                if(icon) icon.style.transform = 'none';
                btn.classList.remove('text-white', 'fw-bold');
            }
        });
    });
});
</script>
