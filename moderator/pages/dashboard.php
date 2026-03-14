<?php
if (!defined('in_nia_app')) exit;
$admin_title = 'Dashboard';
global $db;
$pre = $db->prefix();

$stats = ['videos' => 0, 'music' => 0, 'images' => 0, 'users' => 0, 'comments' => 0, 'reports' => 0, 'channels' => 0, 'playlists' => 0];
try {
    $stats['videos'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos WHERE type = 'video'"));
    $stats['music']  = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos WHERE type = 'music'"));
    $stats['users']  = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}users"));
} catch (Exception $e) {}
try {
    $stats['images'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}images"));
} catch (Exception $e) {}
try {
    $stats['comments'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}comments"));
    $stats['reports']  = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}reports"));
    $stats['channels'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}channels"));
    $stats['playlists'] = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}playlists"));
} catch (Exception $e) {}

$recent_videos = [];
$recent_users = [];
try {
    $recent_videos = $db->fetchAll("SELECT v.id AS id, v.title AS title, v.type AS type, v.views AS views, v.created_at AS created_at, u.username AS username FROM {$pre}videos v LEFT JOIN {$pre}users u ON u.id = v.user_id ORDER BY v.created_at DESC LIMIT 10");
    $recent_videos = admin_normalize_rows($recent_videos, ['id', 'title', 'type', 'views', 'created_at', 'username']);
    $recent_users = $db->fetchAll("SELECT id AS id, username AS username, name AS name, created_at AS created_at FROM {$pre}users ORDER BY created_at DESC LIMIT 10");
    $recent_users = admin_normalize_rows($recent_users, ['id', 'username', 'name', 'created_at']);
} catch (Exception $e) {}

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-1">Welcome back, Admin!</h4>
                    <p class="mb-0 opacity-75">Here is what's happening on your site today.</p>
                </div>
                <div class="d-none d-md-block">
                    <span class="material-icons" style="font-size: 3rem; opacity: 0.5;">dashboard</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Videos</h6>
                    <span class="material-icons text-primary bg-primary bg-opacity-10 rounded p-1">videocam</span>
                </div>
                <p class="mb-0 fs-3 fw-bold"><?php echo number_format((int) $stats['videos']); ?></p>
                <a href="<?php echo admin_url('videos'); ?>" class="small text-decoration-none mt-2 d-block">Manage videos <span class="material-icons align-middle" style="font-size: 1rem;">arrow_forward</span></a>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Music</h6>
                    <span class="material-icons text-success bg-success bg-opacity-10 rounded p-1">music_note</span>
                </div>
                <p class="mb-0 fs-3 fw-bold"><?php echo number_format((int) $stats['music']); ?></p>
                <a href="<?php echo admin_url('music'); ?>" class="small text-decoration-none mt-2 d-block">Manage music <span class="material-icons align-middle" style="font-size: 1rem;">arrow_forward</span></a>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Images</h6>
                    <span class="material-icons text-info bg-info bg-opacity-10 rounded p-1">image</span>
                </div>
                <p class="mb-0 fs-3 fw-bold"><?php echo number_format((int) $stats['images']); ?></p>
                <a href="<?php echo admin_url('images'); ?>" class="small text-decoration-none mt-2 d-block">Manage images <span class="material-icons align-middle" style="font-size: 1rem;">arrow_forward</span></a>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Users</h6>
                    <span class="material-icons text-warning bg-warning bg-opacity-10 rounded p-1">people_alt</span>
                </div>
                <p class="mb-0 fs-3 fw-bold"><?php echo number_format((int) $stats['users']); ?></p>
                <a href="<?php echo admin_url('users'); ?>" class="small text-decoration-none mt-2 d-block">Manage users <span class="material-icons align-middle" style="font-size: 1rem;">arrow_forward</span></a>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm border-start border-secondary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Comments</h6>
                    <span class="material-icons text-secondary bg-secondary bg-opacity-10 rounded p-1">comment</span>
                </div>
                <p class="mb-0 fs-3 fw-bold"><?php echo number_format((int) $stats['comments']); ?></p>
                <a href="<?php echo admin_url('comments'); ?>" class="small text-decoration-none mt-2 d-block">Manage comments <span class="material-icons align-middle" style="font-size: 1rem;">arrow_forward</span></a>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Reports</h6>
                    <span class="material-icons text-danger bg-danger bg-opacity-10 rounded p-1">flag</span>
                </div>
                <p class="mb-0 fs-3 fw-bold"><?php echo number_format((int) $stats['reports']); ?></p>
                <a href="<?php echo admin_url('reports'); ?>" class="small text-decoration-none mt-2 d-block">Manage reports <span class="material-icons align-middle" style="font-size: 1rem;">arrow_forward</span></a>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm border-start border-dark border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Channels</h6>
                    <span class="material-icons text-dark bg-dark bg-opacity-10 rounded p-1">folder</span>
                </div>
                <p class="mb-0 fs-3 fw-bold"><?php echo number_format((int) $stats['channels']); ?></p>
                <a href="<?php echo admin_url('channels'); ?>" class="small text-decoration-none mt-2 d-block">Manage channels <span class="material-icons align-middle" style="font-size: 1rem;">arrow_forward</span></a>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0 shadow-sm border-start border-dark border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title text-muted fw-bold mb-0">Playlists</h6>
                    <span class="material-icons text-dark bg-dark bg-opacity-10 rounded p-1">playlist_play</span>
                </div>
                <p class="mb-0 fs-3 fw-bold"><?php echo number_format((int) $stats['playlists']); ?></p>
                <a href="<?php echo admin_url('playlists'); ?>" class="small text-decoration-none mt-2 d-block">Manage playlists <span class="material-icons align-middle" style="font-size: 1rem;">arrow_forward</span></a>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <a href="<?php echo _e(url('share')); ?>" class="btn btn-outline-primary btn-sm me-2">Share content</a>
    <a href="<?php echo admin_url('health'); ?>" class="btn btn-outline-secondary btn-sm me-2">System health</a>
    <a href="<?php echo admin_url('errorlog'); ?>" class="btn btn-outline-secondary btn-sm">Error log</a>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">Recent videos / music</div>
            <div class="list-group list-group-flush">
                <?php foreach ($recent_videos as $v) {
                    $edit_url = admin_url('videos/edit/' . $v->id);
                    if ($v->type === 'music') $edit_url = admin_url('music/edit/' . $v->id);
                ?>
                <a href="<?php echo _e($edit_url); ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><?php echo _e($v->title); ?> <span class="badge bg-secondary"><?php echo _e($v->type); ?></span></span>
                    <small class="text-muted"><?php echo _e($v->username ?? '-'); ?> · <?php echo _e($v->created_at ?? ''); ?></small>
                </a>
                <?php } ?>
                <?php if (empty($recent_videos)) { ?><div class="list-group-item text-muted">No recent videos or music.</div><?php } ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">Recent users</div>
            <div class="list-group list-group-flush">
                <?php foreach ($recent_users as $u) { ?>
                <a href="<?php echo _e(admin_url('users/edit/' . $u->id)); ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><?php echo _e($u->username); ?> (<?php echo _e($u->name); ?>)</span>
                    <small class="text-muted"><?php echo _e($u->created_at ?? ''); ?></small>
                </a>
                <?php } ?>
                <?php if (empty($recent_users)) { ?><div class="list-group-item text-muted">No recent users.</div><?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
