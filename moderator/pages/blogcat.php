<?php
/**
 * Admin: Blog categories. List, add, edit, delete.
 */
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$sub = admin_subsection();
$id = (int) (admin_segment(2) ?: 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    if ($action === 'save' && $id > 0) {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        if ($slug === '') $slug = preg_replace('/[^a-z0-9\-]/i', '-', $name) ?: 'cat-' . $id;
        $db->query("UPDATE {$pre}blogcat SET name = ?, slug = ? WHERE id = ?", [$name, $slug, $id]);
        redirect(admin_url('blogcat'));
    }
    if ($action === 'create') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        if ($slug === '') $slug = preg_replace('/[^a-z0-9\-]/i', '-', $name) ?: 'cat-' . time();
        $db->query("INSERT INTO {$pre}blogcat (name, slug) VALUES (?, ?)", [$name, $slug]);
        redirect(admin_url('blogcat'));
    }
    if ($action === 'delete') {
        $cid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($cid > 0) $db->query("DELETE FROM {$pre}blogcat WHERE id = ?", [$cid]);
        redirect(admin_url('blogcat'));
    }
}

if ($sub === 'edit' && $id > 0) {
    $item = $db->fetch("SELECT * FROM {$pre}blogcat WHERE id = ?", [$id]);
    if (!$item) { redirect(admin_url('blogcat')); }
    $item = admin_normalize_row($item, ['id', 'name', 'slug']);
    $admin_title = 'Edit category';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo _e($item->name ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" value="<?php echo _e($item->slug ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo _e(admin_url('blogcat')); ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

if ($sub === 'add') {
    $admin_title = 'New category';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" placeholder="optional">
                </div>
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="<?php echo _e(admin_url('blogcat')); ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

$admin_title = 'Blog categories';
$items = [];
$cat_error = '';
try {
    $items = $db->fetchAll("SELECT id, name, slug FROM {$pre}blogcat ORDER BY name");
    $items = admin_normalize_rows($items ?: [], ['id', 'name', 'slug']);
} catch (Exception $e) {
    $cat_error = 'Blog categories table may not exist. Create table ' . $pre . 'blogcat (id, name, slug).';
}

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
if ($cat_error !== '') {
    echo '<div class="alert alert-warning">' . _e($cat_error) . '</div>';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}
?>
<p><a href="<?php echo _e(admin_url('blogcat/add')); ?>" class="btn btn-primary d-inline-flex align-items-center"><span class="material-icons me-1" style="font-size: 18px;">add_circle_outline</span> Add category</a></p>
<table class="table table-hover table-sm">
    <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $c) { ?>
    <tr>
        <td><?php echo (int) $c->id; ?></td>
        <td><a href="<?php echo _e(admin_url('blogcat/edit/' . $c->id)); ?>" class="fw-bold"><?php echo _e($c->name); ?></a></td>
        <td><small class="text-muted"><?php echo _e($c->slug ?? ''); ?></small></td>
        <td class="text-nowrap">
            <a href="<?php echo _e(admin_url('blogcat/edit/' . $c->id)); ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">edit</span></a>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $c->id; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">delete</span></button>
            </form>
        </td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<?php if (empty($items)) { ?><p class="text-muted">No categories yet.</p><?php } ?>
<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
