<?php
/**
 * Modern User Dashboard (Phase 4 Enhancement)
 * Features: Statistics, Media Studio, Subscriptions, Profile, Security.
 */
if (!defined('in_nia_app')) exit;
if (!is_logged()) {
    redirect(url('login'));
}

global $db;
$pre = $db->prefix();
$user = current_user();
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$section = $nia_section !== '' ? $nia_section : 'overview';
$modview = 'me.manage';
$page_title = 'Dashboard – ' . ucfirst($section);

$msg = '';
$err = '';

// --- POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. Update Profile
    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $avatar = trim($_POST['avatar'] ?? '');
        
        if ($name === '') {
            $err = 'Name is required.';
        } else {
            $db->query("UPDATE {$pre}users SET name = ?, email = ?, avatar = ?, updated_at = NOW() WHERE id = ?", [$name, $email, $avatar, $user->id]);
            $msg = 'Profile updated successfully.';
            $user = get_user($user->id); // Refresh
        }
    }
    
    // 2. Change Password
    if ($action === 'security') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($old, $user->password)) {
            $err = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $err = 'New password must be at least 6 characters.';
        } elseif ($new !== $conf) {
            $err = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $db->query("UPDATE {$pre}users SET password = ?, updated_at = NOW() WHERE id = ?", [$hash, $user->id]);
            $msg = 'Password changed successfully.';
        }
    }
    
    // 3. Delete Media (Studio)
    if ($action === 'delete_media') {
        $mid = (int) ($_POST['media_id'] ?? 0);
        if ($mid > 0) {
            $db->query("DELETE FROM {$pre}videos WHERE id = ? AND user_id = ?", [$mid, $user->id]);
            $msg = 'Content deleted.';
        }
    }
}

// --- DATA FETCHING ---
// Stats
$stats = $db->fetch("SELECT SUM(views) as total_views, SUM(likes) as total_likes FROM {$pre}videos WHERE user_id = ?", [$user->id]);
$stats = $stats ?: (object)['total_views' => 0, 'total_likes' => 0];
$content_counts = $db->fetch("SELECT COUNT(*) as total, SUM(CASE WHEN type = 'music' THEN 1 ELSE 0 END) as music, SUM(CASE WHEN (type IS NULL OR type != 'music') THEN 1 ELSE 0 END) as video FROM {$pre}videos WHERE user_id = ?", [$user->id]);
$content_counts = $content_counts ?: (object)['total' => 0, 'music' => 0, 'video' => 0];
$sub_count = function_exists('subscriber_count') ? subscriber_count($user->id) : 0;

// Sub-navigation
$nav = [
    'overview'      => ['label' => 'Overview', 'icon' => 'dashboard', 'url' => url('dashboard')],
    'studio'        => ['label' => 'Content', 'icon' => 'video_library', 'url' => url('dashboard/studio')],
    'subscriptions' => ['label' => 'Following', 'icon' => 'subscriptions', 'url' => url('dashboard/subscriptions')],
    'settings'      => ['label' => 'Profile', 'icon' => 'person', 'url' => url('dashboard/settings')],
    'security'      => ['label' => 'Security', 'icon' => 'verified_user', 'url' => url('dashboard/security')],
];

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>

<main class="nia-main container-fluid container-lg py-3 py-md-4 px-3 px-md-4">
    <div class="row g-3 g-lg-4">
        <!-- Sidebar: collapse on small, full on lg -->
        <div class="col-12 col-lg-3 order-2 order-lg-1">
            <div class="card bg-dark border-secondary dash-card p-3 h-100 shadow-sm rounded-3">
                <div class="text-center mb-3 mb-md-4">
                    <img src="<?php echo $user->avatar ? _e($user->avatar) : 'https://ui-avatars.com/api?name=' . urlencode($user->name); ?>" class="rounded-circle mb-2 dash-avatar" width="72" height="72" style="object-fit: cover; border: 2px solid var(--pv-primary, #0d6efd);" alt="">
                    <h5 class="mb-0 fw-bold small text-truncate"><?php echo _e($user->name); ?></h5>
                    <p class="text-muted small mb-0">@<?php echo _e($user->username); ?></p>
                </div>
                <nav class="dash-sidebar-nav d-none d-lg-block">
                    <div class="list-group list-group-flush">
                        <?php foreach ($nav as $k => $n) {
                            $active = ($section === $k) ? ' active' : '';
                        ?>
                        <a href="<?php echo _e($n['url']); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2<?php echo $active; ?>">
                            <span class="material-icons" style="font-size:1.25rem;"><?php echo $n['icon']; ?></span>
                            <span><?php echo $n['label']; ?></span>
                        </a>
                        <?php } ?>
                    </div>
                </nav>
                <!-- Mobile: horizontal pills -->
                <div class="d-lg-none overflow-auto pb-2 mb-2" style="-webkit-overflow-scrolling:touch;">
                    <div class="d-flex flex-nowrap gap-1">
                        <?php foreach ($nav as $k => $n) {
                            $active = ($section === $k) ? ' active' : '';
                        ?>
                        <a href="<?php echo _e($n['url']); ?>" class="btn btn-sm btn-outline-secondary text-nowrap d-inline-flex align-items-center gap-1<?php echo $active; ?>">
                            <span class="material-icons" style="font-size:1rem;"><?php echo $n['icon']; ?></span>
                            <span><?php echo $n['label']; ?></span>
                        </a>
                        <?php } ?>
                    </div>
                </div>
                <hr class="border-secondary my-3">
                <div class="d-flex flex-column gap-2">
                    <a href="<?php echo url('profile/' . $user->username . '/' . $user->id); ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center justify-content-center gap-1">
                        <span class="material-icons" style="font-size:1.1rem;">person</span> View Channel
                    </a>
                    <?php if (function_exists('is_moderator') && is_moderator()) { ?>
                    <a href="<?php echo url(defined('ADMINCP') ? ADMINCP : 'moderator'); ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1">
                        <span class="material-icons" style="font-size:1.1rem;">admin_panel_settings</span> Admin
                    </a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-12 col-lg-9 order-1 order-lg-2">
            <?php if ($msg) { echo '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert"><span class="material-icons me-2">check_circle</span><span>' . _e($msg) . '</span><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button></div>'; } ?>
            <?php if ($err) { echo '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert"><span class="material-icons me-2">error</span><span>' . _e($err) . '</span><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button></div>'; } ?>

            <?php if ($section === 'overview') {
                $recent = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? ORDER BY created_at DESC LIMIT 6", [$user->id]);
                $recent = is_array($recent) ? $recent : [];
                $site_url = rtrim(SITE_URL, '/');
            ?>
                <!-- Stats: 4 cards with icons, responsive -->
                <div class="row g-2 g-md-3 mb-3 mb-md-4">
                    <div class="col-6 col-md-3">
                        <div class="card dash-card border-0 bg-dark bg-opacity-50 rounded-3 p-3 h-100 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-primary bg-opacity-25 p-2"><span class="material-icons text-primary">visibility</span></div>
                                <div class="min-w-0">
                                    <div class="text-muted small text-uppercase fw-semibold">Views</div>
                                    <div class="fw-bold fs-5"><?php echo number_format((int)($stats->total_views ?? 0)); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card dash-card border-0 bg-dark bg-opacity-50 rounded-3 p-3 h-100 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-success bg-opacity-25 p-2"><span class="material-icons text-success">thumb_up</span></div>
                                <div class="min-w-0">
                                    <div class="text-muted small text-uppercase fw-semibold">Likes</div>
                                    <div class="fw-bold fs-5"><?php echo number_format((int)($stats->total_likes ?? 0)); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card dash-card border-0 bg-dark bg-opacity-50 rounded-3 p-3 h-100 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-info bg-opacity-25 p-2"><span class="material-icons text-info">people</span></div>
                                <div class="min-w-0">
                                    <div class="text-muted small text-uppercase fw-semibold">Subscribers</div>
                                    <div class="fw-bold fs-5"><?php echo number_format($sub_count); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card dash-card border-0 bg-dark bg-opacity-50 rounded-3 p-3 h-100 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-secondary bg-opacity-25 p-2"><span class="material-icons text-secondary">video_library</span></div>
                                <div class="min-w-0">
                                    <div class="text-muted small text-uppercase fw-semibold">Content</div>
                                    <div class="fw-bold fs-5"><?php echo number_format((int)($content_counts->total ?? 0)); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="dash-content-area bg-dark bg-opacity-50 rounded-3 p-3 mb-3 mb-md-4">
                    <h6 class="text-muted text-uppercase small fw-semibold mb-2 d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1rem;">bolt</span> Quick actions</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo url('share'); ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">add_circle</span> Add content
                        </a>
                        <a href="<?php echo url('dashboard/studio'); ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">video_library</span> My content
                        </a>
                        <a href="<?php echo url('profile/' . $user->username . '/' . $user->id); ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">person</span> View channel
                        </a>
                    </div>
                </div>

                <!-- Recent: small grid -->
                <div class="dash-content-area bg-dark rounded-3 p-3 p-md-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <span class="material-icons text-primary">history</span> Recent
                        </h5>
                        <?php if (!empty($recent)) { ?>
                        <a href="<?php echo url('dashboard/studio'); ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">See all <span class="material-icons" style="font-size:1rem;">arrow_forward</span></a>
                        <?php } ?>
                    </div>
                    <?php if (!empty($recent)) { ?>
                    <div class="row g-2 g-md-3">
                        <?php foreach ($recent as $r) {
                            $r = is_array($r) ? (object)$r : $r;
                            $thumb = !empty($r->thumb) ? $r->thumb : '';
                            if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
                            $play_url = function_exists('media_play_url') ? media_play_url($r->id, $r->type ?? 'video', $r->title ?? '') : watch_url($r->id);
                        ?>
                        <div class="col-6 col-sm-4 col-md-4 col-lg-2">
                            <a href="<?php echo _e($play_url); ?>" class="card bg-dark border-secondary text-decoration-none text-reset h-100 dash-recent-card">
                                <div class="position-relative" style="aspect-ratio:16/9;">
                                    <?php if ($thumb) { ?><img src="<?php echo _e($thumb); ?>" class="card-img-top rounded-top w-100 h-100" style="object-fit:cover;" alt="" loading="lazy"><?php } else { ?><div class="w-100 h-100 bg-secondary rounded-top d-flex align-items-center justify-content-center"><span class="material-icons text-dark">videocam</span></div><?php } ?>
                                    <span class="position-absolute bottom-0 end-0 m-1 badge bg-dark small"><?php echo (int)$r->views; ?> views</span>
                                </div>
                                <div class="card-body p-2">
                                    <div class="small text-truncate" title="<?php echo _e($r->title ?? ''); ?>"><?php echo _e($r->title ?? '—'); ?></div>
                                    <div class="small text-muted"><?php echo date('M j', strtotime($r->created_at ?? '')); ?></div>
                                </div>
                            </a>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } else { ?>
                    <div class="text-center py-5 text-muted">
                        <span class="material-icons d-block mb-2 opacity-50" style="font-size:3.5rem;">smart_display</span>
                        <p class="mb-3">No content yet.</p>
                        <a href="<?php echo url('share'); ?>" class="btn btn-primary d-inline-flex align-items-center gap-1"><span class="material-icons">add</span> Add content</a>
                    </div>
                    <?php } ?>
                </div>

            <?php } elseif ($section === 'studio') {
                    $site_url = rtrim(SITE_URL, '/');
                    $studio_total = (int)($content_counts->total ?? 0);
                    $studio_videos = (int)($content_counts->video ?? 0);
                    $studio_music = (int)($content_counts->music ?? 0);
                    $studio_views = (int)($stats->total_views ?? 0);
                    $studio_per_page = 20;
                    $studio_total_pages = $studio_per_page > 0 ? max(1, (int) ceil($studio_total / $studio_per_page)) : 1;
                    $studio_page = isset($_GET['page']) ? max(1, min($studio_total_pages, (int) $_GET['page'])) : 1;
                    $studio_offset = ($studio_page - 1) * $studio_per_page;
                    $studio_items = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? ORDER BY created_at DESC LIMIT " . $studio_per_page . " OFFSET " . $studio_offset, [$user->id]);
                    $studio_items = is_array($studio_items) ? $studio_items : [];
                    $studio_has_more = $studio_total > $studio_per_page && $studio_page < $studio_total_pages;
                    $studio_base_url = url('dashboard/studio');
                ?>
                <!-- Studio header + CTA -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 mb-md-4">
                    <h4 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <span class="material-icons text-primary">video_library</span> My Content
                    </h4>
                    <a href="<?php echo url('share'); ?>" class="btn btn-primary d-inline-flex align-items-center gap-2" aria-label="Add content">
                        <span class="material-icons">add_circle</span> Add Content
                    </a>
                </div>

                <!-- Stats bar: responsive -->
                <div class="row g-2 g-md-3 mb-3 mb-md-4">
                    <div class="col-6 col-md-3">
                        <div class="card bg-dark bg-opacity-50 border-0 rounded-3 p-2 p-md-3 h-100 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-primary bg-opacity-25 p-1 p-md-2"><span class="material-icons text-primary" style="font-size:1.25rem;">folder</span></div>
                                <div class="min-w-0">
                                    <div class="text-muted small text-uppercase fw-semibold">Total</div>
                                    <div class="fw-bold fs-6"><?php echo number_format($studio_total); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-dark bg-opacity-50 border-0 rounded-3 p-2 p-md-3 h-100 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-info bg-opacity-25 p-1 p-md-2"><span class="material-icons text-info" style="font-size:1.25rem;">videocam</span></div>
                                <div class="min-w-0">
                                    <div class="text-muted small text-uppercase fw-semibold">Videos</div>
                                    <div class="fw-bold fs-6"><?php echo number_format($studio_videos); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-dark bg-opacity-50 border-0 rounded-3 p-2 p-md-3 h-100 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-success bg-opacity-25 p-1 p-md-2"><span class="material-icons text-success" style="font-size:1.25rem;">music_note</span></div>
                                <div class="min-w-0">
                                    <div class="text-muted small text-uppercase fw-semibold">Music</div>
                                    <div class="fw-bold fs-6"><?php echo number_format($studio_music); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-dark bg-opacity-50 border-0 rounded-3 p-2 p-md-3 h-100 shadow-sm">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-3 bg-secondary bg-opacity-25 p-1 p-md-2"><span class="material-icons text-secondary" style="font-size:1.25rem;">visibility</span></div>
                                <div class="min-w-0">
                                    <div class="text-muted small text-uppercase fw-semibold">Total views</div>
                                    <div class="fw-bold fs-6"><?php echo number_format($studio_views); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toolbar: search, type, sort -->
                <div class="dash-content-area bg-dark border-secondary p-3 rounded-3 mb-3 mb-md-4">
                    <div class="row g-2 g-md-3 align-items-end flex-wrap">
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <label class="form-label small text-muted mb-1 d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1rem;">search</span> Search</label>
                            <input type="text" id="studio-search" class="form-control form-control-sm bg-dark border-secondary text-light" placeholder="Filter by title..." autocomplete="off">
                        </div>
                        <div class="col-6 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label small text-muted mb-1 d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1rem;">filter_list</span> Type</label>
                            <select id="studio-filter" class="form-select form-select-sm bg-dark border-secondary text-light">
                                <option value="">All</option>
                                <option value="video">Video</option>
                                <option value="music">Music</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label small text-muted mb-1 d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1rem;">sort</span> Sort</label>
                            <select id="studio-sort" class="form-select form-select-sm bg-dark border-secondary text-light">
                                <option value="newest">Newest</option>
                                <option value="oldest">Oldest</option>
                                <option value="views">Most views</option>
                                <option value="title">Title A–Z</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto ms-md-auto pt-2 pt-md-0">
                            <span id="studio-count" class="text-muted small d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:1rem;">list</span> <span id="studio-count-num">0</span> shown</span>
                        </div>
                    </div>
                </div>

                <?php if ($studio_total > 0) {
                    $studio_from = $studio_offset + 1;
                    $studio_to = min($studio_offset + $studio_per_page, $studio_total);
                ?>
                <p class="text-muted small mb-2 d-flex align-items-center gap-1 flex-wrap">
                    <span class="material-icons" style="font-size:1rem;">info</span>
                    Showing <?php echo $studio_from; ?>–<?php echo $studio_to; ?> of <?php echo number_format($studio_total); ?> item<?php echo $studio_total !== 1 ? 's' : ''; ?>
                </p>
                <?php } ?>

                <!-- Grid -->
                <div class="row g-2 g-md-3" id="studio-grid">
                    <?php
                    if ($studio_items) {
                        foreach ($studio_items as $m) {
                            $m = is_array($m) ? (object) $m : $m;
                            $thumb = !empty($m->thumb) ? $m->thumb : '';
                            if ($thumb !== '' && strpos($thumb, 'http') !== 0) $thumb = $site_url . '/' . ltrim($thumb, '/');
                            $play_url = function_exists('media_play_url') ? media_play_url($m->id, $m->type ?? 'video', $m->title ?? '') : watch_url($m->id);
                            $media_type = isset($m->type) && $m->type === 'music' ? 'music' : 'video';
                            $edit_url = (function_exists('is_moderator') && is_moderator() && defined('ADMINCP')) ? ($media_type === 'music' ? url(ADMINCP . '/music/edit/' . $m->id) : url(ADMINCP . '/videos/edit/' . $m->id)) : '';
                            $created_ts = !empty($m->created_at) ? strtotime($m->created_at) : 0;
                            $views_num = (int)($m->views ?? 0);
                            $title_sort = strtolower($m->title ?? '');
                    ?>
                    <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xl-2 studio-card" data-title="<?php echo _e($title_sort); ?>" data-type="<?php echo _e($media_type); ?>" data-created="<?php echo $created_ts; ?>" data-views="<?php echo $views_num; ?>" data-sort-title="<?php echo _e($title_sort); ?>">
                        <div class="card studio-item-card bg-dark border-secondary h-100 rounded-3 overflow-hidden shadow-sm">
                            <a href="<?php echo _e($play_url); ?>" class="text-decoration-none text-reset d-block">
                                <div class="position-relative studio-thumb" style="aspect-ratio:16/9;">
                                    <?php if ($thumb) { ?><img src="<?php echo _e($thumb); ?>" class="card-img-top w-100 h-100" alt="" style="object-fit:cover;" loading="lazy" onerror="this.style.display='none'; var n=this.nextElementSibling; if(n) n.classList.remove('d-none');"><div class="w-100 h-100 bg-secondary position-absolute top-0 start-0 d-none d-flex align-items-center justify-content-center"><span class="material-icons text-dark"><?php echo $media_type === 'music' ? 'music_note' : 'videocam'; ?></span></div><?php } else { ?><div class="w-100 h-100 bg-secondary d-flex align-items-center justify-content-center"><span class="material-icons text-dark"><?php echo $media_type === 'music' ? 'music_note' : 'videocam'; ?></span></div><?php } ?>
                                    <span class="position-absolute bottom-0 start-0 m-1 badge bg-dark bg-opacity-90 small"><?php echo $media_type === 'music' ? 'Music' : 'Video'; ?></span>
                                    <span class="position-absolute top-0 end-0 m-1 small text-muted"><?php echo $views_num; ?> <span class="material-icons align-middle" style="font-size:0.9rem;">visibility</span></span>
                                </div>
                                <div class="card-body p-2 p-md-3">
                                    <div class="small text-truncate fw-semibold" title="<?php echo _e($m->title ?? ''); ?>"><?php echo _e($m->title ?? '—'); ?></div>
                                    <div class="small text-muted d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:0.85rem;">schedule</span>
                                        <?php echo $created_ts ? date('M j, Y', $created_ts) : '—'; ?>
                                    </div>
                                    <?php if (isset($m->likes) && (int)$m->likes > 0) { ?><div class="small text-muted d-flex align-items-center gap-1 mt-1"><span class="material-icons" style="font-size:0.85rem;">thumb_up</span><?php echo (int)$m->likes; ?></div><?php } ?>
                                </div>
                            </a>
                            <div class="card-footer bg-transparent border-secondary p-2 d-flex flex-wrap gap-1 align-items-center">
                                <a href="<?php echo _e($play_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1" title="Play"><span class="material-icons" style="font-size:1.1rem;">play_circle</span></a>
                                <?php if ($edit_url !== '') { ?><a href="<?php echo _e($edit_url); ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Edit"><span class="material-icons" style="font-size:1.1rem;">edit</span></a><?php } ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this permanently?');">
                                    <input type="hidden" name="action" value="delete_media">
                                    <input type="hidden" name="media_id" value="<?php echo (int)$m->id; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" title="Delete"><span class="material-icons" style="font-size:1.1rem;">delete</span></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php }
                    } else { ?>
                    <div class="col-12">
                        <div class="dash-content-area bg-dark rounded-3 p-5 text-center text-muted">
                            <span class="material-icons d-block mb-3 opacity-50" style="font-size:4rem;">video_library</span>
                            <p class="mb-0">No content yet. Upload videos or music to get started.</p>
                            <a href="<?php echo url('share'); ?>" class="btn btn-primary mt-3 d-inline-flex align-items-center gap-2"><span class="material-icons">add</span> Add Content</a>
                        </div>
                    </div>
                    <?php } ?>
                </div>

                <?php if (!empty($studio_items)) {
                    if ($studio_has_more && $studio_page === 1) { ?>
                <div class="nia-loadmore-wrap text-center py-3 py-md-4">
                    <button type="button" class="btn btn-outline-primary nia-loadmore-btn d-inline-flex align-items-center gap-2" data-loadmore-type="studio" data-loadmore-limit="<?php echo $studio_per_page; ?>" data-loadmore-offset="<?php echo $studio_per_page; ?>" data-loadmore-container="#studio-grid" aria-label="Load more">
                        <span class="material-icons" style="font-size:1.2rem;">expand_more</span>
                        <span class="nia-loadmore-text">Load more</span>
                        <span class="nia-loadmore-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
                <?php }
                    if ($studio_total_pages > 1 && function_exists('nia_pagination')) {
                        echo '<div class="nia-studio-pagination">';
                        nia_pagination($studio_page, $studio_total_pages, $studio_base_url, 'page', 2);
                        echo '</div>';
                    }
                } ?>

                <script>
                (function(){
                    var grid = document.getElementById('studio-grid');
                    var searchEl = document.getElementById('studio-search');
                    var filterEl = document.getElementById('studio-filter');
                    var sortEl = document.getElementById('studio-sort');
                    var countEl = document.getElementById('studio-count-num');
                    var cards = grid ? grid.querySelectorAll('.studio-card') : [];
                    function getCards(){ return grid ? grid.querySelectorAll('.studio-card') : []; }
                    function visibleCards(){ var c=getCards(); var out=[]; c.forEach(function(el){ if(el.style.display!=='none') out.push(el); }); return out; }
                    function updateCount(){ if(countEl) countEl.textContent = visibleCards().length; }
                    function filter(){
                        var q = (searchEl&&searchEl.value) ? searchEl.value.trim().toLowerCase() : '';
                        var t = (filterEl&&filterEl.value) ? filterEl.value.trim().toLowerCase() : '';
                        getCards().forEach(function(el){
                            var title = (el.getAttribute('data-title')||'').toLowerCase();
                            var type = (el.getAttribute('data-type')||'').toLowerCase();
                            el.style.display = ((!q||title.indexOf(q)!==-1)&&(!t||type===t)) ? '' : 'none';
                        });
                        updateCount();
                    }
                    function sortGrid(){
                        var order = (sortEl&&sortEl.value) ? sortEl.value : 'newest';
                        var container = grid;
                        if(!container) return;
                        var items = Array.prototype.slice.call(getCards());
                        items.sort(function(a,b){
                            if(order==='newest') return (parseInt(b.getAttribute('data-created')||0,10) - parseInt(a.getAttribute('data-created')||0,10));
                            if(order==='oldest') return (parseInt(a.getAttribute('data-created')||0,10) - parseInt(b.getAttribute('data-created')||0,10));
                            if(order==='views') return (parseInt(b.getAttribute('data-views')||0,10) - parseInt(a.getAttribute('data-views')||0,10));
                            if(order==='title') return ((a.getAttribute('data-sort-title')||'').localeCompare(b.getAttribute('data-sort-title')||''));
                            return 0;
                        });
                        items.forEach(function(node){ container.appendChild(node); });
                    }
                    function run(){ sortGrid(); filter(); updateCount(); }
                    if(searchEl){ searchEl.addEventListener('input',run); searchEl.addEventListener('keyup',run); }
                    if(filterEl) filterEl.addEventListener('change',run);
                    if(sortEl) sortEl.addEventListener('change',run);
                    document.addEventListener('nia-loadmore-appended', function(ev){ if(ev.detail && ev.detail.container && ev.detail.container.id==='studio-grid') run(); });
                    run();
                })();
                </script>

            <?php } elseif ($section === 'subscriptions') {
                    $subs = $db->fetchAll("SELECT u.id, u.name, u.username, u.avatar FROM {$pre}users_friends f JOIN {$pre}users u ON u.id = f.friend_id WHERE f.user_id = ? ORDER BY u.name", [$user->id]);
                    $subs = is_array($subs) ? $subs : [];
                ?>
                <div class="dash-content-area bg-dark rounded-3 p-3 p-md-4">
                    <h4 class="mb-3 mb-md-4 fw-bold d-flex align-items-center gap-2">
                        <span class="material-icons text-primary">subscriptions</span> Following
                    </h4>
                    <?php if (!empty($subs)) { ?>
                    <div class="row g-3">
                        <?php foreach ($subs as $s) {
                            $s = is_array($s) ? (object)$s : $s;
                            $av = $s->avatar ? _e($s->avatar) : 'https://ui-avatars.com/api?name=' . urlencode($s->name ?? '');
                        ?>
                        <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                            <div class="card bg-dark border-secondary h-100 rounded-3 overflow-hidden shadow-sm">
                                <div class="card-body p-3 text-center">
                                    <img src="<?php echo $av; ?>" class="rounded-circle mb-2" width="56" height="56" style="object-fit:cover;" alt="">
                                    <h6 class="mb-0 text-truncate small"><?php echo _e($s->name ?? ''); ?></h6>
                                    <p class="text-muted small mb-2">@<?php echo _e($s->username ?? ''); ?></p>
                                    <a href="<?php echo profile_url($s->username ?? '', $s->id ?? 0); ?>" class="btn btn-sm btn-outline-primary rounded-pill d-inline-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1rem;">person</span> Channel
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } else { ?>
                    <div class="text-center py-5 text-muted">
                        <span class="material-icons d-block mb-2 opacity-50" style="font-size:3.5rem;">people_outline</span>
                        <p class="mb-0">You haven't followed anyone yet.</p>
                        <a href="<?php echo url('users'); ?>" class="btn btn-outline-primary btn-sm mt-3 d-inline-flex align-items-center gap-1"><span class="material-icons">explore</span> Discover</a>
                    </div>
                    <?php } ?>
                </div>

            <?php } elseif ($section === 'settings') { ?>
                <div class="dash-content-area bg-dark rounded-3 p-3 p-md-4">
                    <h4 class="mb-3 mb-md-4 fw-bold d-flex align-items-center gap-2">
                        <span class="material-icons text-primary">person</span> Profile
                    </h4>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="profile">
                        <div class="col-12 text-center mb-2">
                            <div class="d-inline-block position-relative">
                                <img src="<?php echo $user->avatar ? _e($user->avatar) : 'https://ui-avatars.com/api?name=' . urlencode($user->name); ?>" id="avatar-preview" class="rounded-circle border border-secondary" width="100" height="100" style="object-fit:cover; max-width:100px; max-height:100px;" alt="">
                                <span class="position-absolute bottom-0 end-0 bg-dark rounded-circle p-1 border border-secondary"><span class="material-icons text-muted" style="font-size:1.25rem;">photo_camera</span></span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1.1rem;">badge</span> Full name</label>
                            <input type="text" class="form-control form-control-lg bg-dark border-secondary text-light" name="name" value="<?php echo _e($user->name); ?>" required placeholder="Your name">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1.1rem;">email</span> Email</label>
                            <input type="email" class="form-control form-control-lg bg-dark border-secondary text-light" name="email" value="<?php echo _e($user->email); ?>" required placeholder="you@example.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1.1rem;">link</span> Avatar URL</label>
                            <input type="url" class="form-control bg-dark border-secondary text-light" name="avatar" value="<?php echo _e($user->avatar); ?>" placeholder="https://…" oninput="var i=document.getElementById('avatar-preview'); if(i) i.src=this.value||'https://ui-avatars.com/api?name=User'">
                            <div class="form-text text-muted">Direct link to a profile image.</div>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-2">
                                <span class="material-icons">save</span> Save
                            </button>
                        </div>
                    </form>
                </div>

            <?php } elseif ($section === 'security') { ?>
                <div class="dash-content-area bg-dark rounded-3 p-3 p-md-4">
                    <h4 class="mb-3 mb-md-4 fw-bold d-flex align-items-center gap-2">
                        <span class="material-icons text-primary">verified_user</span> Security
                    </h4>
                    <form method="post" class="row g-3" style="max-width: 28rem;">
                        <input type="hidden" name="action" value="security">
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1.1rem;">lock</span> Current password</label>
                            <input type="password" class="form-control form-control-lg bg-dark border-secondary text-light" name="old_password" required autocomplete="current-password" placeholder="••••••••">
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1.1rem;">lock_reset</span> New password</label>
                            <input type="password" class="form-control form-control-lg bg-dark border-secondary text-light" name="new_password" required minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
                            <div class="form-text text-muted">Minimum 6 characters.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-flex align-items-center gap-1"><span class="material-icons" style="font-size:1.1rem;">check_circle</span> Confirm new password</label>
                            <input type="password" class="form-control form-control-lg bg-dark border-secondary text-light" name="confirm_password" required placeholder="Repeat new password">
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-2">
                                <span class="material-icons">key</span> Update password
                            </button>
                        </div>
                    </form>
                </div>
            <?php } ?>
        </div>
    </div>
</main>

<style>
.dash-sidebar-nav .list-group-item {
    transition: all 0.2s;
}
.dash-sidebar-nav .list-group-item.active {
    background: rgba(var(--bs-primary-rgb), 0.2);
    border-color: var(--bs-primary);
    color: var(--bs-primary);
}
.list-group-flush .list-group-item:last-child {
    border-bottom: 0;
}
.dash-avatar {
    width: 64px;
    height: 64px;
    object-fit: cover;
}
.dash-stat-value { font-variant-numeric: tabular-nums; }
.dash-content-area { transition: box-shadow 0.2s; }
.dash-content-area:hover { box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.15); }
.dash-recent-card { transition: transform 0.15s, box-shadow 0.15s; }
.dash-recent-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.2);
}
.studio-item-card { transition: transform 0.15s ease, box-shadow 0.15s ease; }
.studio-item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.35rem 0.75rem rgba(0,0,0,0.25);
}
.studio-thumb { overflow: hidden; }
.studio-thumb .card-img-top { transition: transform 0.2s ease; }
.studio-item-card:hover .studio-thumb .card-img-top { transform: scale(1.05); }
.table-dark {
    --bs-table-bg: transparent;
    --bs-table-hover-bg: rgba(255,255,255,0.05);
}
.scale-2 { transform: scale(1.5); }
@media (max-width: 991px) {
    .nia-main .col-lg-3 { margin-bottom: 1rem; }
    .dash-sidebar-nav .list-group-item { padding: 0.5rem 0.75rem; font-size: 0.9rem; }
}
.nia-studio-pagination { margin-top: 0.5rem; }
@media (max-width: 575px) {
    .dash-stat-value, .fw-bold.fs-5 { font-size: 1rem !important; }
    .dash-recent-card .card-body { padding: 0.5rem !important; }
    .studio-item-card .card-footer .btn { min-width: 2.25rem; padding: 0.35rem; }
    #studio-count { font-size: 0.75rem; }
    .nia-studio-pagination .pagination { font-size: 0.9rem; }
    .nia-loadmore-wrap .btn { width: 100%; max-width: 280px; }
}
</style>

<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
