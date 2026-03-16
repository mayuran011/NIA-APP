<?php
/**
 * /users — members/channels listing.
 */
if (!defined('in_nia_app')) exit;
$section = trim($GLOBALS['nia_route_section'] ?? '');
$page_title = 'Members';
global $db;
$pre = $db->prefix();
$per_page = 24;
$count_row = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}users WHERE group_id > 0");
$total_users = isset($count_row->c) ? (int) $count_row->c : 0;
$total_pages = $per_page > 0 ? max(1, (int) ceil($total_users / $per_page)) : 1;
$page = isset($_GET['page']) ? max(1, min($total_pages, (int) $_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$users = $db->fetchAll("SELECT id, username, name, avatar, created_at FROM {$pre}users WHERE group_id > 0 ORDER BY created_at DESC LIMIT " . $per_page . " OFFSET " . $offset);
$users = is_array($users) ? $users : [];
$has_more_users = $total_users > $per_page && $page < $total_pages;
$users_base_url = url('users');
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Members</h1>
    <div id="nia-users-grid" class="row g-3">
        <?php foreach ($users as $u) {
            $user = is_object($u) ? $u : (object) $u;
            $profile = profile_url($user->username ?? '', $user->id ?? 0);
            $avatar = !empty($user->avatar) ? $user->avatar : '';
            if ($avatar !== '' && strpos($avatar, 'http') !== 0) $avatar = rtrim(SITE_URL, '/') . '/' . ltrim($avatar, '/');
            $name = $user->name ?? $user->username ?? '-';
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?php echo _e($profile); ?>" class="card text-decoration-none h-100 text-center">
                    <div class="card-body">
                        <?php if ($avatar) { ?><img src="<?php echo _e($avatar); ?>" alt="" class="rounded-circle" style="width:64px;height:64px;object-fit:cover"><?php } else { ?><span class="nia-video-avatar-initial d-inline-block rounded-circle" style="width:64px;height:64px;line-height:64px;font-size:1.5rem"><?php echo _e(strtoupper(substr($name, 0, 1))); ?></span><?php } ?>
                        <div class="mt-2 fw-medium"><?php echo _e($name); ?></div>
                        <small class="text-muted">@<?php echo _e($user->username ?? ''); ?></small>
                    </div>
                </a>
            </div>
        <?php } ?>
    </div>
    <?php if (empty($users)) { ?><p class="text-muted">No members yet.</p><?php } ?>
    <?php if (!empty($users)) {
        if ($has_more_users && $page === 1) { ?>
    <div class="nia-loadmore-wrap text-center py-3">
        <button type="button" class="btn btn-outline-primary nia-loadmore-btn d-inline-flex align-items-center gap-2" data-loadmore-type="users" data-loadmore-limit="<?php echo $per_page; ?>" data-loadmore-offset="<?php echo $per_page; ?>" data-loadmore-container="#nia-users-grid" aria-label="Load more members">
            <span class="material-icons" style="font-size:1.2rem;">expand_more</span>
            <span class="nia-loadmore-text">Load more</span>
            <span class="nia-loadmore-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
    </div>
    <?php }
        if ($total_pages > 1 && function_exists('nia_pagination')) nia_pagination($page, $total_pages, $users_base_url, 'page', 2);
    } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
