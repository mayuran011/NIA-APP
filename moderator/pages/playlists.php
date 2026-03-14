<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$sub = admin_subsection();
$id = (int) admin_segment(2);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $pid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($pid > 0 && $action === 'delete') {
        $db->query("DELETE FROM {$pre}playlist_data WHERE playlist_id = ?", [$pid]);
        $db->query("DELETE FROM {$pre}playlists WHERE id = ?", [$pid]);
        redirect(admin_url('playlists'));
    }
    if ($pid > 0 && $action === 'save') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        if ($name !== '' && $slug !== '') {
            $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
            $db->query("UPDATE {$pre}playlists SET name = ?, slug = ? WHERE id = ?", [$name, $slug, $pid]);
            redirect(admin_url('playlists'));
        }
    }
}

if ($sub === 'edit' && $id > 0) {
    $item = $db->fetch("SELECT p.*, u.username FROM {$pre}playlists p LEFT JOIN {$pre}users u ON u.id = p.user_id WHERE p.id = ?", [$id]);
    if (!$item) { redirect(admin_url('playlists')); }
    $item = admin_normalize_row($item, ['id', 'user_id', 'name', 'slug', 'type', 'system_key', 'created_at', 'username']);
    $admin_title = 'Edit playlist';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <p class="text-muted small">By <?php echo _e($item->username ?? '-'); ?> · Type: <?php echo _e($item->type); ?> <?php echo $item->system_key ? '(' . _e($item->system_key) . ')' : ''; ?></p>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo _e($item->name); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" value="<?php echo _e($item->slug); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo _e(admin_url('playlists')); ?>" class="btn btn-outline-secondary">Back to list</a>
                <a href="<?php echo _e(playlist_url($item->name ?? $item->slug ?? '', $item->id ?? 0)); ?>" class="btn btn-outline-secondary" target="_blank">View</a>
            </form>
        </div>
    </div>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete this playlist?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

$admin_title = 'Playlists';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 20;
$offset = ($page - 1) * $per;
$where = "1=1";
$params = [];
if ($q !== '') {
    $where .= " AND (p.name LIKE ? OR u.username LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$total = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}playlists p LEFT JOIN {$pre}users u ON u.id = p.user_id WHERE " . $where, $params));
$params[] = $per;
$params[] = $offset;
$items = $db->fetchAll("SELECT p.id AS id, p.name AS name, p.slug AS slug, p.type AS type, p.system_key AS system_key, p.created_at AS created_at, u.username AS username FROM {$pre}playlists p LEFT JOIN {$pre}users u ON u.id = p.user_id WHERE " . $where . " ORDER BY p.created_at DESC LIMIT ? OFFSET ?", $params);
$items = admin_normalize_rows($items, ['id', 'name', 'slug', 'type', 'system_key', 'created_at', 'username']);

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>
<form method="get" class="mb-3 d-flex gap-2">
    <input type="search" class="form-control" name="q" value="<?php echo _e($q); ?>" placeholder="Search playlists">
    <button type="submit" class="btn btn-primary">Search</button>
</form>
<table class="table table-hover table-sm">
    <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Type</th><th>User</th><th>System</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $p) { ?>
    <tr>
        <td><?php echo (int) $p->id; ?></td>
        <td><a href="<?php echo _e(admin_url('playlists/edit/' . $p->id)); ?>" class="fw-bold"><?php echo _e($p->name); ?></a></td>
        <td><small class="text-muted"><?php echo _e($p->slug); ?></small></td>
        <td><span class="badge bg-<?php echo ($p->type==='system')?'dark':'info'; ?>"><?php echo _e($p->type); ?></span></td>
        <td><?php echo _e($p->username ?? '-'); ?></td>
        <td><?php echo $p->system_key ? '<span class="badge bg-secondary">' . _e($p->system_key) . '</span>' : '<span class="text-muted">—</span>'; ?></td>
        <td><a href="<?php echo _e(admin_url('playlists/edit/' . $p->id)); ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">edit</span></a></td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1) {
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = admin_url('playlists') . '?p=' . $i . ($q !== '' ? '&q=' . rawurlencode($q) : '');
        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
