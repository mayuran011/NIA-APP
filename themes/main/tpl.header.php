<?php
if (!defined('in_nia_app')) exit;
$site_name = get_option('sitename', 'Nia App');
$site_seo_title = get_option('site_title', $site_name);
if (empty($site_seo_title)) { $site_seo_title = $site_name; }
$title = isset($page_title) ? $page_title . ' - ' . $site_seo_title : $site_seo_title;
$nia_dark = isset($_SESSION['nia_dark']) ? $_SESSION['nia_dark'] : get_option('dark_mode', '1');
if (isset($_GET['nia_theme'])) {
    $nia_dark = $_GET['nia_theme'] === 'dark' ? '1' : '0';
    $_SESSION['nia_dark'] = $nia_dark;
    $curr_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $clean_url = strtok($curr_url, '?');
    $query = $_GET; unset($query['nia_theme']);
    if (!empty($query)) $clean_url .= '?' . http_build_query($query);
    redirect($clean_url);
}
$nia_body_class = ($nia_dark === '1') ? 'nia-body' : 'nia-body nia-light';
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('current_lang') ? _e(current_lang()) : 'en'; ?>"<?php echo (function_exists('is_rtl') && is_rtl()) ? ' dir="rtl"' : ''; ?> class="<?php echo $nia_dark === '1' ? '' : 'nia-light'; ?>" <?php echo $nia_dark === '1' ? 'data-bs-theme="dark"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=5.0, user-scalable=yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo _e($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php 
    $custom_font = get_option('site_font', '"Inter", system-ui, sans-serif');
    if (strpos($custom_font, 'Roboto') !== false) echo '<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">';
    if (strpos($custom_font, 'Inter') !== false) echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">';
    if (strpos($custom_font, 'Outfit') !== false) echo '<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">';
    if (strpos($custom_font, 'Montserrat') !== false) echo '<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">';
    if (strpos($custom_font, 'Open Sans') !== false) echo '<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;700&display=swap" rel="stylesheet">';
    if (strpos($custom_font, 'Poppins') !== false) echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">';
    ?>
    <link rel="stylesheet" href="<?php echo url('themes/main/nia.css'); ?>">
    <style>
        :root {
            --pv-primary: <?php echo _e(get_option('theme_color', '#4f46e5')); ?>; /* Modern indigo default */
            --pv-primary-dark: <?php echo _e(get_option('pv_primary_dark', '#4338ca')); ?>;
            --pv-bg: <?php echo _e(get_option('pv_bg', '#09090b')); ?>; /* Rich deep zinc */
            --pv-text: <?php echo _e(get_option('pv_text', '#f8fafc')); ?>;
            --pv-border: <?php echo _e(get_option('pv_border_color', 'rgba(255,255,255,0.08)')); ?>;
            --pv-radius: <?php echo _e(get_option('site_radius', '0.75rem')); ?>;
            --pv-font-sans: <?php echo $custom_font; ?>;
            --pv-text-muted: rgba(<?php 
                $txt = get_option('pv_text', '#ffffff');
                if(strpos($txt, '#') === 0) {
                    $hex = substr($txt, 1);
                    if(strlen($hex) == 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                    $r = hexdec(substr($hex,0,2));
                    $g = hexdec(substr($hex,2,2));
                    $b = hexdec(substr($hex,4,2));
                    echo "$r,$g,$b";
                } else {
                    echo "255,255,255";
                }
            ?>, 0.55);
        }
        body { 
            font-size: <?php echo _e(get_option('site_font_size', '15px')); ?>; 
            color: var(--pv-text) !important;
            background-color: var(--pv-bg);
            /* Soft gradient background for a very premium feel */
            background-image: radial-gradient(circle at 50% 0%, rgba(79, 70, 229, 0.08), transparent 50%);
            background-attachment: fixed;
        }
        .text-muted { color: var(--pv-text-muted) !important; }
        .nia-video-channel, .nia-video-stats, .nia-watch-subs { color: var(--pv-text-muted) !important; }
        <?php if ($nia_dark !== '1'): ?>
        .nia-light, html.nia-light {
            --pv-bg: #f4f4f5;
            --pv-text: #000000;
        }
        <?php endif; ?>
    </style>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="manifest" href="<?php echo url('app/favicos/site.webmanifest.php'); ?>">
    <?php if (get_option('favicon_url')): ?>
    <link rel="icon" href="<?php echo _e(get_option('favicon_url')); ?>">
    <?php endif; ?>
    <?php
    // --- Phase 4: SEO Suite Social Meta ---
    require_once ABSPATH . 'views' . DIRECTORY_SEPARATOR . 'seo-suite.route.php';
    if (!empty($video)) {
        $v_thumb = !empty($video->thumb) ? $video->thumb : '';
        if ($v_thumb !== '' && strpos($v_thumb, 'http') !== 0) $v_thumb = rtrim(SITE_URL, '/') . '/' . ltrim($v_thumb, '/');
        echo get_social_meta($video->title, mb_substr($video->description ?? '', 0, 160), $v_thumb, 'video.other');
    } else {
        echo get_social_meta($title);
    }
    ?>
    <meta name="theme-color" content="<?php echo $nia_dark === '1' ? _e(get_option('theme_color', '#ff0000')) : '#f4f4f5'; ?>">
    <?php if (function_exists('do_action')) { do_action('vibe_head'); } ?>
</head>
<body class="<?php echo _e($nia_body_class); ?><?php echo is_logged() ? ' nia-logged-in' : ''; ?>"<?php
if (!empty($nia_current_media_id)) {
    echo ' data-nia-media-id="' . (int) $nia_current_media_id . '" data-nia-media-type="' . _e($nia_current_media_type ?? 'video') . '"';
}
?>>
<?php if (get_option('announcement_enable', '0') === '1' && !empty(get_option('announcement_msg'))): 
    $ann_type = get_option('announcement_type', 'info');
    $ann_msg = get_option('announcement_msg');
    $ann_link = get_option('announcement_link');
?>
<div class="alert alert-<?php echo _e($ann_type); ?> alert-dismissible fade show rounded-0 border-0 m-0 py-2 px-4 shadow-sm text-center d-flex align-items-center justify-content-center" style="z-index: 2000; position: relative;" role="alert">
    <div class="d-flex align-items-center">
        <span class="material-icons me-2 small">campaign</span>
        <span class="fw-medium small"><?php echo _e($ann_msg); ?></span>
        <?php if ($ann_link): ?>
            <a href="<?php echo _e($ann_link); ?>" class="alert-link ms-2 small fw-bold">Learn more &rarr;</a>
        <?php endif; ?>
    </div>
    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if (function_exists('do_action')) { do_action('vibe_header'); } ?>
<div class="nia-sidebar-backdrop" id="niaSidebarBackdrop" aria-hidden="true"></div>

<header class="nia-header nia-header-theme">
    <div class="nia-header-left d-flex align-items-center flex-shrink-0">
        <button type="button" class="nia-header-btn nia-sidebar-toggle nia-hamburger" id="niaSidebarToggle" aria-label="Toggle menu">
            <span class="material-icons" id="niaSidebarToggleIcon" aria-hidden="true">menu</span>
        </button>
<?php
$logo_img = get_option('logo_url');
?>
        <a class="nia-logo" href="<?php echo url(); ?>">
            <?php if ($logo_img): ?>
                <img src="<?php echo _e($logo_img); ?>" alt="<?php echo _e(get_option('sitename', 'Nia App')); ?>" style="max-height: 32px;">
            <?php else: ?>
                <span class="material-icons nia-logo-icon">play_circle_filled</span>
                <span class="nia-logo-text"><?php echo _e(get_option('sitename', 'Nia App')); ?></span>
            <?php endif; ?>
        </a>
    </div>
    <div class="nia-search-wrap flex-shrink-1">
        <div class="nia-search-inner position-relative">
            <form class="nia-search-form" action="<?php echo url('show'); ?>" method="get" role="search">
                <span class="material-icons nia-search-icon-inside" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--pv-text-muted); pointer-events:none; z-index:2;">search</span>
                <input type="search" class="nia-search-input" id="nia-search-input" name="q" placeholder="Type to search..." value="<?php echo isset($_GET['q']) ? _e($_GET['q']) : ''; ?>" autocomplete="off" aria-label="Search" aria-autocomplete="list" aria-controls="nia-live-search-dropdown" style="padding-left: 2.75rem;">
                <button type="submit" class="nia-search-btn d-none" aria-label="Search"><span class="material-icons">search</span></button>
            </form>
            <div class="nia-live-search-dropdown position-absolute top-100 start-0 end-0 mt-1 d-none" id="nia-live-search-dropdown" role="listbox"></div>
        </div>
    </div>
    <div class="nia-header-actions flex-shrink-0">
        <a class="nia-header-btn nia-header-create" href="<?php echo is_logged() ? url('share') : url('login'); ?>" title="Create / Upload"><span class="material-icons">add_circle_outline</span><span class="nia-header-btn-label d-none d-md-inline">Create</span></a>
        <?php if (is_logged()) {
            $cu = current_user();
            $avatar = $cu && !empty($cu->avatar) ? $cu->avatar : '';
            $initial = $cu && !empty($cu->username) ? strtoupper(substr($cu->username, 0, 1)) : '?';
            if ($avatar !== '' && strpos($avatar, 'http') !== 0) { $avatar = rtrim(SITE_URL, '/') . '/' . ltrim($avatar, '/'); }
            $admin_path = defined('ADMINCP') ? ADMINCP : 'moderator';
        ?>
        <div class="dropdown d-inline-block">
            <button type="button" class="nia-header-btn position-relative border-0 bg-transparent p-0" id="nia-notifications-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <span class="material-icons">notifications_none</span>
                <?php try { $unread = count_unread_notifications(); } catch (Throwable $e) { $unread = 0; } if ($unread > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger nia-notif-badge" style="font-size: 0.6rem; padding: 0.25em 0.4em; z-index:1;"><?php echo $unread > 9 ? '9+' : $unread; ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end dropdown-menu-dark p-0 border-secondary border-opacity-25 shadow-lg" style="width: 320px; max-height: 520px; overflow-y: auto;" id="nia-notifications-dropdown">
                <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Notifications</h6>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-info small" id="mark-notifications-read">Mark all as read</button>
                </div>
                <div id="notifications-list" class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                     <div class="p-4 text-center text-muted small">Loading...</div>
                </div>
                <div class="p-2 border-top border-secondary border-opacity-25 text-center">
                    <a href="<?php echo url('activity'); ?>" class="small text-decoration-none text-white-50">View all activity</a>
                </div>
            </div>
        </div>
        <a class="nia-header-btn" href="?nia_theme=<?php echo $nia_dark === '1' ? 'light' : 'dark'; ?>" title="Toggle <?php echo $nia_dark === '1' ? 'Light' : 'Dark'; ?> Theme">
            <span class="material-icons"><?php echo $nia_dark === '1' ? 'light_mode' : 'dark_mode'; ?></span>
        </a>
        <div class="dropdown d-inline-block">
            <button type="button" class="nia-header-btn nia-header-avatar dropdown-toggle border-0 bg-transparent p-0" data-bs-toggle="dropdown" aria-expanded="false" title="Account">
                <?php if ($avatar) { ?><img src="<?php echo _e($avatar); ?>" alt="" class="nia-avatar-img"><?php } else { ?><span class="nia-avatar-initial"><?php echo _e($initial); ?></span><?php } ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                <li class="px-3 py-2 border-bottom border-secondary border-opacity-25 mb-1">
                    <div class="fw-bold text-truncate"><?php echo _e($cu ? ($cu->name ?? $cu->username ?? '') : ''); ?></div>
                    <?php if (has_premium()): ?>
                        <div class="badge bg-primary rounded-pill small mt-1" style="font-size: 0.65rem; letter-spacing: 0.5px;"><span class="material-icons align-middle me-1" style="font-size: 0.8rem;">workspace_premium</span>PREMIUM</div>
                    <?php endif; ?>
                </li>
                <li><a class="dropdown-item" href="<?php echo url('dashboard'); ?>"><span class="material-icons align-middle" style="font-size:1.1rem;">person</span> Dashboard</a></li>
                <li><a class="dropdown-item" href="<?php echo url('dashboard/settings'); ?>"><span class="material-icons align-middle" style="font-size:1.1rem;">settings</span> Settings</a></li>
                <li><a class="dropdown-item" href="<?php echo url('premiumhub'); ?>"><span class="material-icons align-middle" style="font-size:1.1rem;">workspace_premium</span> Premium</a></li>
                <li><a class="dropdown-item" href="<?php echo url('msg'); ?>"><span class="material-icons align-middle" style="font-size:1.1rem;">mail</span> Messages</a></li>
                <?php if (function_exists('is_moderator') && is_moderator()) { ?><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="<?php echo url($admin_path); ?>"><span class="material-icons align-middle" style="font-size:1.1rem;">admin_panel_settings</span> Admin</a></li><?php } ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo url('login'); ?>?action=logout"><span class="material-icons align-middle" style="font-size:1.1rem;">logout</span> Sign out</a></li>
            </ul>
        </div>
        <?php } else { ?>
        <a class="nia-header-btn btn btn-primary px-4 py-2 rounded-pill d-inline-flex align-items-center gap-2" style="font-weight:600; font-size: 0.9rem;" href="<?php echo url('login'); ?>"><span class="material-icons fs-6">login</span> Sign in</a>
        <?php } ?>
    </div>
</header>

<div class="nia-layout d-flex mt-3">
<?php
$sidebar_file = ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'sidebar.php';
try {
    if (is_file($sidebar_file)) {
        include $sidebar_file;
    } else {
        echo '<aside class="nia-sidebar" id="niaSidebar"><nav class="nia-sidebar-nav"><a class="nia-sidebar-item" href="' . _e(url()) . '"><span class="material-icons">home</span><span>Home</span></a><a class="nia-sidebar-item" href="' . _e(url('videos/browse')) . '"><span class="material-icons">videocam</span><span>Videos</span></a><a class="nia-sidebar-item" href="' . _e(url('music/browse')) . '"><span class="material-icons">music_note</span><span>Music</span></a></nav></aside>';
    }
} catch (Throwable $e) {
    if (function_exists('error_log')) { error_log('Sidebar error: ' . $e->getMessage()); }
    echo '<aside class="nia-sidebar" id="niaSidebar"><nav class="nia-sidebar-nav"><a class="nia-sidebar-item" href="' . _e(url()) . '"><span class="material-icons">home</span><span>Home</span></a><a class="nia-sidebar-item" href="' . _e(url('videos/browse')) . '"><span class="material-icons">videocam</span><span>Videos</span></a><a class="nia-sidebar-item" href="' . _e(url('music/browse')) . '"><span class="material-icons">music_note</span><span>Music</span></a><a class="nia-sidebar-item" href="' . _e(url('dashboard')) . '"><span class="material-icons">video_library</span><span>Dashboard</span></a></nav></aside>';
}
?>
<div class="nia-content flex-grow-1 overflow-auto">
