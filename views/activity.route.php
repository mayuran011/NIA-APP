<?php
/**
 * Modern Activity (Buzz) Feed
 * Displays user actions across the platform with rich visuals.
 */
if (!defined('in_nia_app')) exit;

$page_title = 'Activity';

// --- Filtering & pagination ---
$filter = $_GET['type'] ?? 'all';
$limit = 20;
$count_args = $filter !== 'all' ? ['action' => $filter] : [];
$total_activities = function_exists('get_activity_count') ? get_activity_count($count_args) : 0;
$total_pages = $limit > 0 ? max(1, (int) ceil($total_activities / $limit)) : 1;
$page = isset($_GET['page']) ? max(1, min($total_pages, (int) $_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$args = ['limit' => $limit, 'offset' => $offset];
if ($filter !== 'all') {
    $args['action'] = $filter;
}
$activities = get_activity($args);
$has_more_activity = count($activities) >= $limit && $page < $total_pages;
$activity_base_url = $filter === 'all' ? url('activity') : url('activity?type=' . $filter);

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>

<main class="nia-main container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="nia-title mb-1">What's New</h1>
            <p class="text-muted small mb-0">Discover the latest community interactions.</p>
        </div>
        
        <div class="nia-chips p-0 border-0 bg-transparent">
            <a href="<?php echo url('activity'); ?>" class="nia-chip<?php echo $filter === 'all' ? ' active' : ''; ?>">All</a>
            <a href="<?php echo url('activity?type=upload'); ?>" class="nia-chip<?php echo $filter === 'upload' ? ' active' : ''; ?>">Uploads</a>
            <a href="<?php echo url('activity?type=like'); ?>" class="nia-chip<?php echo $filter === 'like' ? ' active' : ''; ?>">Likes</a>
            <a href="<?php echo url('activity?type=subscribed'); ?>" class="nia-chip<?php echo $filter === 'subscribed' ? ' active' : ''; ?>">Subscribers</a>
        </div>
    </div>

    <div class="timeline-wrap" style="max-width: 800px; margin: 0 auto;">
    <?php
    if ($activities) {
        foreach ($activities as $a) {
            $actor_name = _e($a->user_name ?: $a->username ?: 'Someone');
            $actor_profile = profile_url($a->username, $a->user_id);
            $actor_avatar = $a->user_avatar ? _e($a->user_avatar) : 'https://ui-avatars.com/api?name=' . urlencode($actor_name);
            $time_str = isset($a->created_at) ? nia_time_ago($a->created_at) : '';
            
            // Icon & Action Mapping
            $icon = 'bolt';
            $color = 'primary';
            $desc = 'performed an action';
            
            switch($a->action) {
                case 'like':
                case 'liked':
                    $icon = 'thumb_up'; 
                    $color = 'success';
                    $desc = 'liked';
                    break;
                case 'subscribed':
                case 'subscribe':
                    $icon = 'person_add';
                    $color = 'info';
                    $desc = 'subscribed to';
                    break;
                case 'comment':
                case 'commented':
                    $icon = 'comment';
                    $color = 'warning';
                    $desc = 'commented on';
                    break;
                case 'upload':
                case 'uploaded':
                    $icon = 'cloud_upload';
                    $color = 'primary';
                    $desc = 'uploaded a new';
                    break;
                case 'watched':
                    $icon = 'visibility';
                    $color = 'secondary';
                    $desc = 'watched';
                    break;
            }

            // Object Context
            $obj_link = '#';
            $obj_title = 'content';
            $obj_thumb = '';
            
            if ($a->object_type === 'video' || $a->object_type === 'music') {
                $v = get_video($a->object_id, false);
                if ($v) {
                    $obj_link = watch_url($v->id);
                    $obj_title = $v->title;
                    $obj_thumb = $v->thumb;
                    if ($obj_thumb && strpos($obj_thumb, 'http') !== 0) $obj_thumb = url($obj_thumb);
                } else {
                    $obj_title = 'Private or Deleted Content';
                }
            } elseif ($a->object_type === 'user') {
                $u = get_user($a->object_id);
                if ($u) {
                    $obj_link = profile_url($u->username, $u->id);
                    $obj_title = $u->name;
                    $obj_thumb = $u->avatar;
                    if ($obj_thumb && strpos($obj_thumb, 'http') !== 0) $obj_thumb = url($obj_thumb);
                }
            }
            ?>
            
            <div class="card bg-dark border-secondary dash-card p-3 mb-3 activity-card">
                <div class="d-flex gap-3">
                    <!-- Actor Avatar -->
                    <a href="<?php echo $actor_profile; ?>" class="flex-shrink-0">
                        <img src="<?php echo $actor_avatar; ?>" class="rounded-circle" width="48" height="48" style="object-fit: cover; border: 2px solid var(--pv-border);">
                    </a>
                    
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small text-muted fw-bold d-flex align-items-center gap-1">
                                <span class="material-icons text-<?php echo $color; ?>" style="font-size: 1rem;"><?php echo $icon; ?></span>
                                <?php echo strtoupper($a->action); ?>
                            </span>
                            <span class="text-muted" style="font-size: 0.75rem;"><?php echo $time_str; ?></span>
                        </div>
                        
                        <div class="mb-2">
                            <a href="<?php echo $actor_profile; ?>" class="text-light fw-bold text-decoration-none"><?php echo $actor_name; ?></a>
                            <span class="text-muted"><?php echo $desc; ?></span>
                            <?php if ($obj_title) { ?>
                                <a href="<?php echo $obj_link; ?>" class="text-primary fw-bold text-decoration-none"><?php echo _e($obj_title); ?></a>
                            <?php } ?>
                        </div>

                        <?php if ($obj_thumb && $a->object_type !== 'user') { ?>
                        <a href="<?php echo $obj_link; ?>" class="d-block mt-2 rounded overflow-hidden" style="max-width: 200px; aspect-ratio: 16/9; background: #000;">
                            <img src="<?php echo $obj_thumb; ?>" class="w-100 h-100" style="object-fit: cover; opacity: 0.8; transition: opacity 0.2s;">
                        </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php
        }
        
        <?php if ($has_more_activity && $page === 1) {
            $load_more_url = $activity_base_url . (strpos($activity_base_url, '?') !== false ? '&' : '?') . 'page=2';
            ?>
            <div class="text-center mt-4">
                <a href="<?php echo _e($load_more_url); ?>" class="btn btn-outline-primary btn-lg px-4 rounded-pill shadow-sm d-inline-flex align-items-center gap-2">
                    <span class="material-icons" style="font-size:1.2rem;">expand_more</span> Load more
                </a>
            </div>
        <?php }
        if ($total_pages > 1 && function_exists('nia_pagination')) {
            echo '<div class="mt-4">';
            nia_pagination($page, $total_pages, $activity_base_url, 'page', 2);
            echo '</div>';
        }
    } else {
        ?>
        <div class="text-center py-5 text-muted bg-dark rounded border border-secondary">
            <span class="material-icons scale-2 mb-3" style="font-size: 4rem;">notifications_none</span>
            <p>No activity matches your filter.</p>
            <a href="<?php echo url('activity'); ?>" class="btn btn-outline-primary btn-sm">Refresh Feed</a>
        </div>
        <?php
    }
    ?>
    </div>
</main>

<style>
.activity-card {
    transition: transform 0.2s, background-color 0.2s;
}
.activity-card:hover {
    background-color: rgba(255,255,255,0.03) !important;
}
.activity-card img:hover {
    opacity: 1 !important;
}
.nia-light .activity-card {
    background: var(--pv-bg-elevated) !important;
}
.nia-light .activity-card:hover {
    background-color: #f8f9fa !important;
}
</style>

<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
