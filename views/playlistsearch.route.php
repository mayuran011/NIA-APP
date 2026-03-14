<?php
/**
 * Playlist search: dedicated route and backend.
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['vibe_route_section'] ?? '';
$q = isset($_GET['q']) ? trim($_GET['q']) : (string) $nia_section;
global $db;
$pre = $db->prefix();
$playlists = [];
if ($q !== '') {
    $like = '%' . preg_replace('/%|_/', '\\\\$0', $q) . '%';
    $playlists = $db->fetchAll(
        "SELECT p.*, u.username, u.name AS owner_name FROM {$pre}playlists p LEFT JOIN {$pre}users u ON u.id = p.user_id WHERE p.system_key IS NULL AND (p.name LIKE ? OR p.slug LIKE ?) ORDER BY p.name LIMIT 48",
        [$like, $like]
    );
}
$page_title = $q !== '' ? 'Playlist search: ' . _e($q) : 'Playlist search';

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Playlist search</h1>
    <form method="get" action="<?php echo url('playlistsearch'); ?>" class="mb-4">
        <div class="row g-2">
            <div class="col-auto flex-grow-1">
                <input type="text" class="form-control bg-dark border-secondary text-light" name="q" value="<?php echo _e($q); ?>" placeholder="Search playlists by name...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>
    <?php if ($q !== '') { ?>
    <?php if ($playlists) { ?>
    <ul class="list-unstyled">
        <?php foreach ($playlists as $pl) { ?>
        <li class="mb-3">
            <a href="<?php echo playlist_url($pl->slug ?? '', $pl->id); ?>" class="text-decoration-none text-light fw-medium"><?php echo _e($pl->name ?? ''); ?></a>
            <?php if (!empty($pl->username)) { ?>
            <span class="text-muted small">by <a href="<?php echo profile_url($pl->username ?? '', $pl->user_id ?? 0); ?>" class="text-muted">@<?php echo _e($pl->username ?? ''); ?></a></span>
            <?php } ?>
        </li>
        <?php } ?>
    </ul>
    <?php } else { ?>
    <p class="text-muted">No playlists found for “<?php echo _e($q); ?>”.</p>
    <?php } ?>
    <?php } else { ?>
    <p class="text-muted">Enter a keyword to search playlists.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
