<?php
/**
 * /lists — playlists listing (public playlists).
 */
if (!defined('in_nia_app')) exit;
$section = trim($GLOBALS['nia_route_section'] ?? '');
$page_title = 'Playlists';
global $db;
$pre = $db->prefix();
$per_page = 24;
$count_row = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}playlists p WHERE (p.system_key IS NULL OR p.system_key = '')");
$total_playlists = isset($count_row->c) ? (int) $count_row->c : 0;
$total_pages = $per_page > 0 ? max(1, (int) ceil($total_playlists / $per_page)) : 1;
$page = isset($_GET['page']) ? max(1, min($total_pages, (int) $_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$playlists = $db->fetchAll("SELECT p.*, u.username FROM {$pre}playlists p LEFT JOIN {$pre}users u ON u.id = p.user_id WHERE (p.system_key IS NULL OR p.system_key = '') ORDER BY p.created_at DESC LIMIT " . $per_page . " OFFSET " . $offset);
$playlists = is_array($playlists) ? $playlists : [];
$has_more_playlists = $total_playlists > $per_page && $page < $total_pages;
$lists_base_url = url('lists');
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Playlists</h1>
    <div id="nia-playlists-grid" class="row g-3">
        <?php foreach ($playlists as $p) {
            $pl = is_object($p) ? $p : (object) $p;
            $slug = $pl->slug ?? ('playlist-' . ($pl->id ?? 0));
            $url = function_exists('playlist_url') ? playlist_url($pl->name ?? 'playlist', $pl->id ?? 0) : url('playlist/' . $slug . '/' . (int) ($pl->id ?? 0));
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?php echo _e($url); ?>" class="card text-decoration-none h-100">
                    <div class="card-body">
                        <span class="material-icons text-muted">playlist_play</span>
                        <div class="mt-2 fw-medium"><?php echo _e($pl->name ?? 'Playlist'); ?></div>
                        <small class="text-muted"><?php echo _e($pl->username ?? '-'); ?></small>
                    </div>
                </a>
            </div>
        <?php } ?>
    </div>
    <?php if (empty($playlists)) { ?><p class="text-muted">No playlists yet.</p><?php } ?>
    <?php if (!empty($playlists)) {
        if ($has_more_playlists && $page === 1) { ?>
    <div class="nia-loadmore-wrap text-center py-3">
        <button type="button" class="btn btn-outline-primary nia-loadmore-btn d-inline-flex align-items-center gap-2" data-loadmore-type="playlists" data-loadmore-limit="<?php echo $per_page; ?>" data-loadmore-offset="<?php echo $per_page; ?>" data-loadmore-container="#nia-playlists-grid" aria-label="Load more playlists">
            <span class="material-icons" style="font-size:1.2rem;">expand_more</span>
            <span class="nia-loadmore-text">Load more</span>
            <span class="nia-loadmore-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
    </div>
    <?php }
        if ($total_pages > 1 && function_exists('nia_pagination')) nia_pagination($page, $total_pages, $lists_base_url, 'page', 2);
    } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
