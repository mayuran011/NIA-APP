<?php
/**
 * Admin: Static pages. List, add, edit, delete.
 */
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$sub = admin_subsection();
$id = (int) (admin_segment(2) ?: 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    if ($action === 'save' && $id > 0) {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        if ($slug === '') $slug = preg_replace('/[^a-z0-9\-]/i', '-', $title) ?: 'page-' . $id;
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $db->query("UPDATE {$pre}pages SET title = ?, slug = ?, content = ? WHERE id = ?", [$title, $slug, $content, $id]);
        redirect(admin_url('staticpages'));
    }
    if ($action === 'create') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        if ($slug === '') $slug = preg_replace('/[^a-z0-9\-]/i', '-', $title) ?: 'page-' . time();
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $db->query("INSERT INTO {$pre}pages (title, slug, content, created_at) VALUES (?, ?, ?, NOW())", [$title, $slug, $content]);
        redirect(admin_url('staticpages'));
    }
    if ($action === 'delete') {
        $pid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($pid > 0) $db->query("DELETE FROM {$pre}pages WHERE id = ?", [$pid]);
        redirect(admin_url('staticpages'));
    }
}

if ($sub === 'edit' && $id > 0) {
    $item = $db->fetch("SELECT * FROM {$pre}pages WHERE id = ?", [$id]);
    if (!$item) { redirect(admin_url('staticpages')); }
    $item = admin_normalize_row($item, ['id', 'title', 'slug', 'content', 'created_at']);
    $admin_title = 'Edit page';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" value="<?php echo _e($item->title ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" value="<?php echo _e($item->slug ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea class="form-control" name="content" rows="10"><?php echo _e($item->content ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo _e(admin_url('staticpages')); ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

if ($sub === 'add') {
    $admin_title = 'New page';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" name="slug" placeholder="optional">
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea class="form-control" name="content" rows="10"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="<?php echo _e(admin_url('staticpages')); ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

$admin_title = 'Static pages';
$items = [];
$pages_error = '';
try {
    $items = $db->fetchAll("SELECT id, title, slug, created_at FROM {$pre}pages ORDER BY title");
    $items = admin_normalize_rows($items ?: [], ['id', 'title', 'slug', 'created_at']);
} catch (Exception $e) {
    $pages_error = 'Pages table may not exist. Create table ' . $pre . 'pages (id, title, slug, content, created_at).';
}

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
if ($pages_error !== '') {
    echo '<div class="alert alert-warning">' . _e($pages_error) . '</div>';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}
?>
<p><a href="<?php echo _e(admin_url('staticpages/add')); ?>" class="btn btn-primary d-inline-flex align-items-center"><span class="material-icons me-1" style="font-size: 18px;">note_add</span> Add page</a></p>
<table class="table table-hover table-sm">
    <thead><tr><th>ID</th><th>Title</th><th>Slug</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $p) { ?>
    <tr>
        <td><?php echo (int) $p->id; ?></td>
        <td><a href="<?php echo _e(admin_url('staticpages/edit/' . $p->id)); ?>" class="fw-bold"><?php echo _e($p->title); ?></a></td>
        <td><small class="text-muted"><?php echo _e($p->slug ?? ''); ?></small></td>
        <td><small class="text-muted"><?php echo _e($p->created_at ?? ''); ?></small></td>
        <td class="text-nowrap">
            <a href="<?php echo _e(admin_url('staticpages/edit/' . $p->id)); ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">edit</span></a>
            <a href="<?php echo _e(page_url($p->slug ?? '', $p->id)); ?>" class="btn btn-sm btn-outline-info d-inline-flex align-items-center" target="_blank"><span class="material-icons" style="font-size: 16px;">visibility</span></a>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete this page?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $p->id; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">delete</span></button>
            </form>
        </td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<?php if (empty($items)) { ?><p class="text-muted">No pages yet.</p><?php } ?>
<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
