<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$sub = admin_subsection();
$id = (int) admin_segment(2);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $iid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($iid > 0) {
        if ($action === 'delete') {
            $db->query("DELETE FROM {$pre}images WHERE id = ?", [$iid]);
            redirect(admin_url('images'));
        }
        if ($action === 'save') {
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
            $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';
            if ($title !== '') {
                $db->query("UPDATE {$pre}images SET title = ?, description = ?, tags = ? WHERE id = ?", [$title, $desc, $tags, $iid]);
            }
            redirect(admin_url('images/edit/' . $iid));
        }
    }
}

if ($sub === 'edit' && $id > 0) {
    $item = $db->fetch("SELECT i.*, u.username FROM {$pre}images i LEFT JOIN {$pre}users u ON u.id = i.user_id WHERE i.id = ?", [$id]);
    if (!$item) { redirect(admin_url('images')); }
    $item = admin_normalize_row($item, ['id', 'user_id', 'title', 'description', 'tags', 'thumb', 'path', 'created_at', 'username']);
    $admin_title = 'Edit image';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <?php if (!empty($item->thumb)) { ?><img src="<?php echo _e($item->thumb); ?>" alt="" class="img-thumbnail mb-2" style="max-height:120px"><?php } ?>
            <p class="text-muted small">By <?php echo _e($item->username ?? '-'); ?></p>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" value="<?php echo _e($item->title); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"><?php echo _e($item->description ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tags</label>
                    <input type="text" class="form-control" name="tags" value="<?php echo _e($item->tags ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo _e(admin_url('images')); ?>" class="btn btn-outline-secondary">Back to list</a>
                <a href="<?php echo _e((function_exists('view_url') ? view_url($item->id ?? 0, $item->title ?? '') : image_url($item->id ?? 0, $item->title ?? ''))); ?>" class="btn btn-outline-secondary" target="_blank">View</a>
            </form>
        </div>
    </div>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

$admin_title = 'Images';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 20;
$offset = ($page - 1) * $per;
$where = "1=1";
$params = [];
if ($q !== '') {
    $where .= " AND (i.title LIKE ? OR i.description LIKE ? OR i.tags LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$total = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}images i WHERE " . $where, $params));
$params[] = $per;
$params[] = $offset;
$items = $db->fetchAll("SELECT i.id AS id, i.title AS title, i.thumb AS thumb, i.created_at AS created_at, u.username AS username FROM {$pre}images i LEFT JOIN {$pre}users u ON u.id = i.user_id WHERE " . $where . " ORDER BY i.created_at DESC LIMIT ? OFFSET ?", $params);
$items = admin_normalize_rows($items, ['id', 'title', 'thumb', 'created_at', 'username']);

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>
<form method="get" class="mb-3 d-flex gap-2">
    <input type="search" class="form-control" name="q" value="<?php echo _e($q); ?>" placeholder="Search images">
    <button type="submit" class="btn btn-primary">Search</button>
</form>
<table class="table table-hover table-sm align-middle">
    <thead><tr><th>ID</th><th>Thumb</th><th>Title</th><th>User</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $v) { ?>
    <tr>
        <td><?php echo (int) ($v->id ?? 0); ?></td>
        <td><?php if (!empty($v->thumb)) { ?><img src="<?php echo _e($v->thumb); ?>" alt="" class="rounded shadow-sm" style="height:48px; width:48px; object-fit:cover;"><?php } else { ?><div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:48px; width:48px;"><span class="material-icons text-muted">image</span></div><?php } ?></td>
        <td><a href="<?php echo _e(admin_url('images/edit/' . ($v->id ?? 0))); ?>" class="fw-bold"><?php echo _e($v->title ?? '-'); ?></a></td>
        <td><span class="badge bg-secondary"><?php echo _e($v->username ?? '-'); ?></span></td>
        <td><small class="text-muted"><?php echo _e($v->created_at ?? ''); ?></small></td>
        <td class="text-nowrap">
            <a href="<?php echo _e(admin_url('images/edit/' . ($v->id ?? 0))); ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">edit</span></a>
            <a href="<?php echo _e(function_exists('view_url') ? view_url($v->id ?? 0, $v->title ?? '') : image_url($v->id ?? 0, $v->title ?? '')); ?>" class="btn btn-sm btn-outline-info d-inline-flex align-items-center" target="_blank"><span class="material-icons" style="font-size: 16px;">visibility</span></a>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) ($v->id ?? 0); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">delete</span></button>
            </form>
        </td>
    </tr>
    <?php } ?>
    <?php if (empty($items)) { ?><tr><td colspan="6" class="text-muted text-center py-4">No images found.</td></tr><?php } ?>
    </tbody>
</table>
<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1) {
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = admin_url('images') . '?p=' . $i . ($q !== '' ? '&q=' . rawurlencode($q) : '');
        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
