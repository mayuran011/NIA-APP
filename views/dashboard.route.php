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

<main class="nia-main container py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="card bg-dark border-secondary dash-card p-3 h-100">
                <div class="text-center mb-4">
                    <img src="<?php echo $user->avatar ? _e($user->avatar) : 'https://ui-avatars.com/api?name=' . urlencode($user->name); ?>" class="rounded-circle mb-2" width="80" height="80" style="object-fit: cover; border: 2px solid var(--pv-primary);">
                    <h5 class="mb-0 fw-bold"><?php echo _e($user->name); ?></h5>
                    <p class="text-muted small">@<?php echo _e($user->username); ?></p>
                </div>
                
                <div class="list-group list-group-flush dash-sidebar-nav">
                    <?php foreach ($nav as $k => $n) { 
                        $active = ($section === $k) ? ' active' : '';
                    ?>
                    <a href="<?php echo _e($n['url']); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3<?php echo $active; ?>">
                        <span class="material-icons"><?php echo $n['icon']; ?></span>
                        <span><?php echo $n['label']; ?></span>
                    </a>
                    <?php } ?>
                </div>
                
                <hr class="border-secondary my-4">
                
                <div class="mt-auto">
                    <a href="<?php echo url('profile/' . $user->username . '/' . $user->id); ?>" class="btn btn-outline-secondary btn-sm w-100 mb-2">View Public Channel</a>
                    <?php if (is_moderator()) { ?>
                    <a href="<?php echo url(defined('ADMINCP') ? ADMINCP : 'moderator'); ?>" class="btn btn-primary btn-sm w-100">Admin Panel</a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-lg-9">
            <?php if ($msg) { echo '<div class="alert alert-success d-flex align-items-center mb-4"><span class="material-icons me-2">check_circle</span>' . _e($msg) . '</div>'; } ?>
            <?php if ($err) { echo '<div class="alert alert-danger d-flex align-items-center mb-4"><span class="material-icons me-2">error</span>' . _e($err) . '</div>'; } ?>

            <?php if ($section === 'overview') { ?>
                <!-- OVERVIEW STATS -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4">
                        <div class="card dash-card p-3 text-center border-0 bg-dark">
                            <div class="dash-stat-label">Views</div>
                            <div class="dash-stat-value text-primary"><?php echo number_format((int)($stats->total_views ?? 0)); ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card dash-card p-3 text-center border-0 bg-dark">
                            <div class="dash-stat-label">Likes</div>
                            <div class="dash-stat-value text-success"><?php echo number_format((int)($stats->total_likes ?? 0)); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dash-card p-3 text-center border-0 bg-dark">
                            <div class="dash-stat-label">Subscribers</div>
                            <div class="dash-stat-value text-info"><?php echo number_format($sub_count); ?></div>
                        </div>
                    </div>
                </div>

                <div class="dash-content-area bg-dark">
                    <h5 class="mb-4 d-flex align-items-center"><span class="material-icons me-2">history</span> Recent Insights</h5>
                    <?php 
                    $recent = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$user->id]);
                    if ($recent) { ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle mb-0">
                                <thead><tr><th>Media</th><th>Views</th><th>Created</th></tr></thead>
                                <tbody>
                                    <?php foreach ($recent as $r) { 
                                        $thumb = $r->thumb ? _e($r->thumb) : '';
                                        if ($thumb && strpos($thumb, 'http') !== 0) $thumb = url($thumb);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo $thumb; ?>" class="rounded" width="60" height="34" style="object-fit: cover;">
                                                <div class="text-truncate" style="max-width: 250px;"><?php echo _e($r->title); ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo (int)$r->views; ?></td>
                                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($r->created_at)); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <div class="text-center py-5 text-muted">
                            <span class="material-icons scale-2 mb-3" style="font-size: 4rem;">smart_display</span>
                            <p class="mb-3">No content uploaded yet.</p>
                            <a href="<?php echo url('share'); ?>" class="btn btn-primary">Start Creating</a>
                        </div>
                    <?php } ?>
                </div>

            <?php } elseif ($section === 'studio') { ?>
                <!-- MEDIA STUDIO -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="mb-0 fw-bold">My Content</h4>
                    <a href="<?php echo url('share'); ?>" class="btn btn-primary d-flex align-items-center gap-2" aria-label="Add content">
                        <span class="material-icons">add</span> Add Content
                    </a>
                </div>
                
                <div class="dash-content-area bg-dark">
                    <?php 
                    $all = $db->fetchAll("SELECT * FROM {$pre}videos WHERE user_id = ? ORDER BY created_at DESC", [$user->id]);
                    if ($all) { ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle">
                                <thead><tr><th>ID</th><th>Media</th><th>Status</th><th>Views</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach ($all as $m) { 
                                        $thumb = $m->thumb ? _e($m->thumb) : '';
                                        if ($thumb && strpos($thumb, 'http') !== 0) $thumb = url($thumb);
                                    ?>
                                    <tr>
                                        <td><?php echo (int)$m->id; ?></td>
                                        <td>
                                            <a href="<?php echo function_exists('media_play_url') ? media_play_url($m->id, $m->type ?? 'video', $m->title ?? '') : watch_url($m->id); ?>" target="_blank" class="text-light text-decoration-none">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?php echo $thumb; ?>" class="rounded shadow-sm" width="100" height="56" style="object-fit: cover;">
                                                    <div class="fw-bold"><?php echo _e($m->title); ?></div>
                                                </div>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (empty($m->private)) { ?>
                                                <span class="badge bg-success">Public</span>
                                            <?php } else { ?>
                                                <span class="badge bg-warning text-dark">Private</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo (int)$m->views; ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="<?php echo function_exists('media_play_url') ? media_play_url($m->id, $m->type ?? 'video', $m->title ?? '') : watch_url($m->id); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="View"><span class="material-icons">visibility</span></a>
                                                <form method="post" onsubmit="return confirm('Delete this media permanently?');">
                                                    <input type="hidden" name="action" value="delete_media">
                                                    <input type="hidden" name="media_id" value="<?php echo (int)$m->id; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><span class="material-icons">delete</span></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php } else { ?>
                        <p class="text-muted py-4 text-center">Nothing found.</p>
                    <?php } ?>
                </div>

            <?php } elseif ($section === 'subscriptions') { ?>
                <!-- FOLLOWED CHANNELS -->
                <h4 class="mb-4 fw-bold">Subscriptions</h4>
                <div class="row g-3">
                    <?php 
                    $subs = $db->fetchAll("SELECT u.id, u.name, u.username, u.avatar FROM {$pre}users_friends f JOIN {$pre}users u ON u.id = f.friend_id WHERE f.user_id = ?", [$user->id]);
                    if ($subs) {
                        foreach ($subs as $s) { ?>
                        <div class="col-md-4">
                            <div class="card dash-card p-3 bg-dark border-secondary text-center">
                                <img src="<?php echo $s->avatar ? _e($s->avatar) : 'https://ui-avatars.com/api?name=' . urlencode($s->name); ?>" class="rounded-circle mb-2 mx-auto" width="60" height="60" style="object-fit: cover;">
                                <h6 class="mb-1 text-truncate"><?php echo _e($s->name); ?></h6>
                                <p class="text-muted small mb-3">@<?php echo _e($s->username); ?></p>
                                <a href="<?php echo profile_url($s->username, $s->id); ?>" class="btn btn-sm btn-outline-primary rounded-pill">View Channel</a>
                            </div>
                        </div>
                        <?php }
                    } else { ?>
                        <div class="col-12 py-5 text-center text-muted">
                            <span class="material-icons mb-2 d-block">people_outline</span>
                            <p>You haven't subscribed to anyone yet.</p>
                        </div>
                    <?php } ?>
                </div>

            <?php } elseif ($section === 'settings') { ?>
                <!-- PROFILE SETTINGS -->
                <h4 class="mb-4 fw-bold">Profile Info</h4>
                <div class="dash-content-area bg-dark">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="profile">
                        <div class="col-md-12 mb-3 text-center">
                            <div class="dash-avatar-upload">
                                <img src="<?php echo $user->avatar ? _e($user->avatar) : 'https://ui-avatars.com/api?name=' . urlencode($user->name); ?>" id="avatar-preview" class="rounded-circle w-100 h-100" style="object-fit: cover;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control bg-dark border-secondary text-light" name="name" value="<?php echo _e($user->name); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control bg-dark border-secondary text-light" name="email" value="<?php echo _e($user->email); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Avatar URL</label>
                            <input type="url" class="form-control bg-dark border-secondary text-light" name="avatar" value="<?php echo _e($user->avatar); ?>" placeholder="https://site.com/avatar.jpg" oninput="document.getElementById('avatar-preview').src = this.value || 'https://ui-avatars.com/api?name=User'">
                            <div class="form-text">Direct link to an image file.</div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                        </div>
                    </form>
                </div>

            <?php } elseif ($section === 'security') { ?>
                <!-- SECURITY -->
                <h4 class="mb-4 fw-bold">Account Security</h4>
                <div class="dash-content-area bg-dark">
                    <form method="post" class="row g-3" style="max-width: 500px;">
                        <input type="hidden" name="action" value="security">
                        <div class="col-12">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control bg-dark border-secondary text-light" name="old_password" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control bg-dark border-secondary text-light" name="new_password" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control bg-dark border-secondary text-light" name="confirm_password" required>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary px-4">Update Password</button>
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
.list-group-flush .list-group-item:last-child {
    border-bottom: 0;
}
.table-dark {
    --bs-table-bg: transparent;
    --bs-table-hover-bg: rgba(255,255,255,0.05);
}
.scale-2 { transform: scale(1.5); }
@media (max-width: 991px) {
    .nia-main .col-lg-3 { margin-bottom: 2rem; }
}
</style>

<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
