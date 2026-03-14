<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$sub = admin_subsection();
$id = (int) admin_segment(2);

// POST: delete, save (edit), featured, unpublish/publish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $vid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($vid > 0) {
        if ($action === 'delete') {
            $db->query("DELETE FROM {$pre}videos WHERE id = ?", [$vid]);
            redirect(admin_url('videos'));
        }
        if ($action === 'featured') {
            $feat = isset($_POST['featured']) ? 1 : 0;
            $db->query("UPDATE {$pre}videos SET featured = ? WHERE id = ?", [$feat, $vid]);
            redirect(admin_url('videos'));
        }
        if ($action === 'publish') {
            $db->query("UPDATE {$pre}videos SET private = 0 WHERE id = ?", [$vid]);
            redirect(admin_url('videos'));
        }
        if ($action === 'unpublish') {
            $db->query("UPDATE {$pre}videos SET private = 1 WHERE id = ?", [$vid]);
            redirect(admin_url('videos'));
        }
        if ($action === 'save') {
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
            $cat = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
            $feat = isset($_POST['featured']) ? 1 : 0;
            $priv = isset($_POST['private']) ? 1 : 0;
            $nsfw = isset($_POST['nsfw']) ? 1 : 0;
            if ($title !== '') {
                $db->query("UPDATE {$pre}videos SET title = ?, description = ?, category_id = ?, featured = ?, private = ?, nsfw = ? WHERE id = ?", [$title, $desc, $cat, $feat, $priv, $nsfw, $vid]);
            }
            redirect(admin_url('videos/edit/' . $vid));
        }
    }
}

// Edit form
if ($sub === 'edit' && $id > 0) {
    $item = $db->fetch("SELECT v.*, u.username FROM {$pre}videos v LEFT JOIN {$pre}users u ON u.id = v.user_id WHERE v.id = ?", [$id]);
    if (!$item) { redirect(admin_url('videos')); }
    $item = admin_normalize_row($item, ['id', 'user_id', 'title', 'description', 'type', 'source', 'category_id', 'featured', 'private', 'nsfw', 'file_path', 'remote_url', 'embed_code', 'thumb', 'duration', 'created_at', 'username']);
    $admin_title = 'Edit video';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    $channels = admin_normalize_rows($db->fetchAll("SELECT id, name FROM {$pre}channels WHERE type = 'video' ORDER BY name"), ['id', 'name']);
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <p class="text-muted small">By <?php echo _e($item->username ?? '-'); ?> · <?php echo _e($item->created_at ?? ''); ?></p>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" value="<?php echo _e($item->title); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?php echo _e($item->description ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Channel</label>
                    <select class="form-select" name="category_id">
                        <option value="0">— None —</option>
                        <?php foreach ($channels as $c) { ?><option value="<?php echo (int) $c->id; ?>" <?php echo (int) $item->category_id === (int) $c->id ? 'selected' : ''; ?>><?php echo _e($c->name); ?></option><?php } ?>
                    </select>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="featured" id="feat" value="1" <?php echo !empty($item->featured) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="feat">Featured</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="private" id="priv" value="1" <?php echo !empty($item->private) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="priv">Private</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="nsfw" id="nsfw" value="1" <?php echo !empty($item->nsfw) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="nsfw">NSFW</label>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo _e(admin_url('videos')); ?>" class="btn btn-outline-secondary">Back to list</a>
                <a href="<?php echo _e(watch_url($item->id ?? 0)); ?>" class="btn btn-outline-secondary" target="_blank">View</a>
            </form>
        </div>
    </div>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete this video?');">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

// List
$admin_title = 'Videos';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 20;
$offset = ($page - 1) * $per;

$where = "v.type = 'video'";
$params = [];
if ($q !== '') {
    $where .= " AND (v.title LIKE ? OR v.description LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$total = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos v WHERE " . $where, $params));
$params[] = $per;
$params[] = $offset;
$items = $db->fetchAll("SELECT v.id AS id, v.title AS title, v.views AS views, v.featured AS featured, v.private AS private, v.created_at AS created_at, u.username AS username FROM {$pre}videos v LEFT JOIN {$pre}users u ON u.id = v.user_id WHERE " . $where . " ORDER BY v.created_at DESC LIMIT ? OFFSET ?", $params);
$items = admin_normalize_rows($items, ['id', 'title', 'views', 'featured', 'private', 'created_at', 'username']);

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<form method="get" class="mb-3 d-flex gap-2">
    <input type="search" class="form-control" name="q" value="<?php echo _e($q); ?>" placeholder="Search videos">
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<table class="table table-sm table-striped">
    <thead><tr><th>ID</th><th>Title</th><th>User</th><th>Views</th><th>Featured</th><th>Private</th><th>Created</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $v) { ?>
    <tr>
        <td><?php echo (int) ($v->id ?? 0); ?></td>
        <td><a href="<?php echo _e(admin_url('videos/edit/' . ($v->id ?? 0))); ?>"><?php echo _e($v->title ?? '-'); ?></a></td>
        <td><?php echo _e($v->username ?? '-'); ?></td>
        <td><?php echo (int) ($v->views ?? 0); ?></td>
        <td><?php if(!empty($v->featured)) echo '<span class="badge bg-warning text-dark">Featured</span>'; else echo '<span class="text-muted small">No</span>'; ?></td>
        <td><?php if(!empty($v->private)) echo '<span class="badge bg-danger">Private</span>'; else echo '<span class="badge bg-success">Public</span>'; ?></td>
        <td><?php echo _e($v->created_at ?? ''); ?></td>
        <td>
            <a href="<?php echo _e(admin_url('videos/edit/' . ($v->id ?? 0))); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <a href="<?php echo _e(watch_url($v->id ?? 0)); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">View</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) ($v->id ?? 0); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </td>
    </tr>
    <?php } ?>
    <?php if (empty($items)) { ?><tr><td colspan="8" class="text-muted text-center py-4">No videos found.</td></tr><?php } ?>
    </tbody>
</table>

<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1) {
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = admin_url('videos') . '?p=' . $i . ($q !== '' ? '&q=' . rawurlencode($q) : '');
        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
