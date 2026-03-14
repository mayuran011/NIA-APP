<?php
/**
 * People (channels) search: dedicated route and backend.
 */
if (!defined('in_nia_app')) exit;
$nia_section = $GLOBALS['vibe_route_section'] ?? '';
$q = isset($_GET['q']) ? trim($_GET['q']) : (string) $nia_section;
global $db;
$pre = $db->prefix();
$users = [];
if ($q !== '') {
    $like = '%' . preg_replace('/%|_/', '\\\\$0', $q) . '%';
    $users = $db->fetchAll(
        "SELECT id, username, name, avatar FROM {$pre}users WHERE name LIKE ? OR username LIKE ? ORDER BY name LIMIT 48",
        [$like, $like]
    );
}
$page_title = $q !== '' ? 'People search: ' . _e($q) : 'People search';

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">People search</h1>
    <form method="get" action="<?php echo url('pplsearch'); ?>" class="mb-4">
        <div class="row g-2">
            <div class="col-auto flex-grow-1">
                <input type="text" class="form-control bg-dark border-secondary text-light" name="q" value="<?php echo _e($q); ?>" placeholder="Search channels by name or username...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>
    <?php if ($q !== '') { ?>
    <?php if ($users) { ?>
    <ul class="list-unstyled">
        <?php foreach ($users as $u) {
            $avatar = $u->avatar ?? ('https://ui-avatars.com/api?name=' . urlencode($u->name ?? $u->username));
        ?>
        <li class="mb-3 d-flex align-items-center gap-3">
            <a href="<?php echo profile_url($u->username ?? '', $u->id); ?>">
                <img src="<?php echo _e($avatar); ?>" alt="" class="rounded-circle" width="48" height="48">
            </a>
            <div>
                <a href="<?php echo profile_url($u->username ?? '', $u->id); ?>" class="text-decoration-none text-light fw-medium"><?php echo _e($u->name ?? $u->username); ?></a>
                <div class="small text-muted">@<?php echo _e($u->username ?? ''); ?></div>
            </div>
        </li>
        <?php } ?>
    </ul>
    <?php } else { ?>
    <p class="text-muted">No people found for “<?php echo _e($q); ?>”.</p>
    <?php } ?>
    <?php } else { ?>
    <p class="text-muted">Enter a name or username to search channels.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
