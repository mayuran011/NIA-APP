<?php
/**
 * /lists — playlists listing (public playlists).
 */
if (!defined('in_nia_app')) exit;
$section = trim($GLOBALS['nia_route_section'] ?? '');
$page_title = 'Playlists';
global $db;
$pre = $db->prefix();
$playlists = $db->fetchAll("SELECT p.*, u.username FROM {$pre}playlists p LEFT JOIN {$pre}users u ON u.id = p.user_id WHERE p.system_key IS NULL OR p.system_key = '' ORDER BY p.created_at DESC LIMIT 48");
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Playlists</h1>
    <div class="row g-3">
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
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
