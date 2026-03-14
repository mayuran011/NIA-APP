<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$sub = admin_subsection();
$id = (int) admin_segment(2);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $cid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($action === 'create' || ($action === 'save' && $cid > 0)) {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        $type = isset($_POST['type']) && in_array($_POST['type'], ['video', 'music', 'image'], true) ? $_POST['type'] : 'video';
        $parent_id = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
        if ($name !== '' && $slug !== '') {
            $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slug));
            if ($action === 'create') {
                $db->query("INSERT INTO {$pre}channels (parent_id, name, slug, type, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)", [$parent_id, $name, $slug, $type, $description, $sort_order]);
                redirect(admin_url('channels'));
            } else {
                $db->query("UPDATE {$pre}channels SET name = ?, slug = ?, type = ?, parent_id = ?, description = ?, sort_order = ? WHERE id = ?", [$name, $slug, $type, $parent_id, $description, $sort_order, $cid]);
                redirect(admin_url('channels'));
            }
        }
    }
    if ($action === 'delete' && $cid > 0) {
        $db->query("DELETE FROM {$pre}channels WHERE id = ?", [$cid]);
        redirect(admin_url('channels'));
    }
}

if ($sub === 'edit' && $id > 0) {
    $item = $db->fetch("SELECT * FROM {$pre}channels WHERE id = ?", [$id]);
    if (!$item) { redirect(admin_url('channels')); }
    $item = admin_normalize_row($item, ['id', 'name', 'slug', 'type', 'parent_id', 'sort_order', 'description']);
    $admin_title = 'Edit channel';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    $channels = admin_normalize_rows($db->fetchAll("SELECT id, name, type FROM {$pre}channels WHERE id != ? ORDER BY type, name", [$id]), ['id', 'name', 'type']);
    ?>
    <div class="card mb-4">
        <div class="card-body">
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
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        <option value="video" <?php echo $item->type === 'video' ? 'selected' : ''; ?>>Video</option>
                        <option value="music" <?php echo $item->type === 'music' ? 'selected' : ''; ?>>Music</option>
                        <option value="image" <?php echo $item->type === 'image' ? 'selected' : ''; ?>>Image</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Parent channel</label>
                    <select class="form-select" name="parent_id">
                        <option value="0">— None —</option>
                        <?php foreach ($channels as $c) { ?><option value="<?php echo (int) $c->id; ?>" <?php echo (int) $item->parent_id === (int) $c->id ? 'selected' : ''; ?>><?php echo _e($c->name); ?> (<?php echo _e($c->type); ?>)</option><?php } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"><?php echo _e($item->description ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sort order</label>
                    <input type="number" class="form-control" name="sort_order" value="<?php echo (int) $item->sort_order; ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo _e(admin_url('channels')); ?>" class="btn btn-outline-secondary">Back to list</a>
            </form>
        </div>
    </div>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete this channel?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

$admin_title = 'Channels';
$items = $db->fetchAll("SELECT c.id AS id, c.name AS name, c.slug AS slug, c.type AS type, c.parent_id AS parent_id, c.sort_order AS sort_order, c.description AS description, (SELECT COUNT(*) FROM {$pre}videos v WHERE v.category_id = c.id) AS video_count FROM {$pre}channels c ORDER BY c.type, c.sort_order, c.name");
$items = admin_normalize_rows($items, ['id', 'name', 'slug', 'type', 'parent_id', 'sort_order', 'description', 'video_count']);

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<div class="card mb-4">
    <div class="card-header">Add channel</div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="action" value="create">
            <div class="row g-2">
                <div class="col-md-3"><input type="text" class="form-control" name="name" placeholder="Name" required></div>
                <div class="col-md-2"><input type="text" class="form-control" name="slug" placeholder="slug" required></div>
                <div class="col-md-2"><select class="form-select" name="type"><option value="video">Video</option><option value="music">Music</option><option value="image">Image</option></select></div>
                <div class="col-md-2"><input type="number" class="form-control" name="sort_order" value="0" placeholder="Order"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Create</button></div>
            </div>
            <input type="hidden" name="parent_id" value="0">
        </form>
    </div>
</div>

<table class="table table-sm table-striped">
    <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Type</th><th>Parent</th><th>Content</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $c) { ?>
    <tr>
        <td><?php echo (int) $c->id; ?></td>
        <td><a href="<?php echo _e(admin_url('channels/edit/' . $c->id)); ?>"><?php echo _e($c->name); ?></a></td>
        <td><?php echo _e($c->slug); ?></td>
        <td><span class="badge bg-<?php echo $c->type==='video' ? 'primary' : ($c->type==='music' ? 'success' : 'info'); ?>"><?php echo _e($c->type); ?></span></td>
        <td><?php echo (int) $c->parent_id; ?></td>
        <td><?php echo (int) ($c->video_count ?? 0); ?></td>
        <td><a href="<?php echo _e(admin_url('channels/edit/' . $c->id)); ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
    </tr>
    <?php } ?>
    </tbody>
</table>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
