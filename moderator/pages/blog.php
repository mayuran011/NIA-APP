<?php
/**
 * Admin: Blog posts. List, add, edit, delete.
 */
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$sub = admin_subsection();
$id = (int) (admin_segment(2) ?: 0);

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    if ($action === 'save' && $id > 0) {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        if ($slug === '') $slug = preg_replace('/[^a-z0-9\-]/i', '-', $title) ?: 'post-' . $id;
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
        $status = isset($_POST['status']) && $_POST['status'] === 'draft' ? 'draft' : 'publish';
        $category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $db->query(
            "UPDATE {$pre}posts SET title = ?, slug = ?, content = ?, excerpt = ?, status = ?, category_id = ? WHERE id = ?",
            [$title, $slug, $content, $excerpt, $status, $category_id, $id]
        );
        redirect(admin_url('blog'));
    }
    if ($action === 'create') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        if ($slug === '') $slug = preg_replace('/[^a-z0-9\-]/i', '-', $title) ?: 'post-' . time();
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
        $status = isset($_POST['status']) && $_POST['status'] === 'draft' ? 'draft' : 'publish';
        $category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $uid = (int) ($_SESSION['uid'] ?? 0);
        $db->query(
            "INSERT INTO {$pre}posts (user_id, title, slug, content, excerpt, status, category_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [$uid, $title, $slug, $content, $excerpt, $status, $category_id]
        );
        redirect(admin_url('blog'));
    }
    if ($action === 'delete') {
        $pid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($pid > 0) $db->query("DELETE FROM {$pre}posts WHERE id = ?", [$pid]);
        redirect(admin_url('blog'));
    }
}

// Edit form
if ($sub === 'edit' && $id > 0) {
    $item = $db->fetch("SELECT * FROM {$pre}posts WHERE id = ?", [$id]);
    if (!$item) { redirect(admin_url('blog')); }
    $item = admin_normalize_row($item, ['id', 'user_id', 'title', 'slug', 'content', 'excerpt', 'status', 'category_id', 'created_at']);
    $admin_title = 'Edit blog post';
    $cats = $db->fetchAll("SELECT id, name FROM {$pre}blogcat ORDER BY name");
    $cats = admin_normalize_rows($cats, ['id', 'name']);
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
                    <textarea class="form-control" name="content" rows="8"><?php echo _e($item->content ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Excerpt</label>
                    <textarea class="form-control" name="excerpt" rows="2"><?php echo _e($item->excerpt ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="publish" <?php echo ($item->status ?? '') === 'publish' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo ($item->status ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="0">— None —</option>
                        <?php foreach ($cats as $c) { ?><option value="<?php echo (int) $c->id; ?>" <?php echo (int)($item->category_id ?? 0) === (int)$c->id ? 'selected' : ''; ?>><?php echo _e($c->name); ?></option><?php } ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo _e(admin_url('blog')); ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

// Create form (inline or separate)
if ($sub === 'add') {
    $admin_title = 'New blog post';
    $cats = $db->fetchAll("SELECT id, name FROM {$pre}blogcat ORDER BY name");
    $cats = admin_normalize_rows($cats ?: [], ['id', 'name']);
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
                    <textarea class="form-control" name="content" rows="8"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Excerpt</label>
                    <textarea class="form-control" name="excerpt" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="publish">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="0">— None —</option>
                        <?php foreach ($cats as $c) { ?><option value="<?php echo (int) $c->id; ?>"><?php echo _e($c->name); ?></option><?php } ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="<?php echo _e(admin_url('blog')); ?>" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

// List
$admin_title = 'Blog (posts)';
$items = [];
$total = 0;
$blog_error = '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 20;
try {
    $offset = ($page - 1) * $per;
    $where = "1=1";
    $params = [];
    if ($q !== '') {
        $where .= " AND (p.title LIKE ? OR p.content LIKE ?)";
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }
    $total = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}posts p WHERE " . $where, $params));
    $params[] = $per;
    $params[] = $offset;
    $items = $db->fetchAll("SELECT p.id AS id, p.title AS title, p.slug AS slug, p.status AS status, p.created_at AS created_at, u.username AS username FROM {$pre}posts p LEFT JOIN {$pre}users u ON u.id = p.user_id WHERE " . $where . " ORDER BY p.created_at DESC LIMIT ? OFFSET ?", $params);
    $items = admin_normalize_rows($items, ['id', 'title', 'slug', 'status', 'created_at', 'username']);
} catch (Exception $e) {
    $blog_error = 'Posts table may not exist. Create table ' . $pre . 'posts (id, user_id, title, slug, content, excerpt, status, category_id, created_at).';
}

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
if ($blog_error !== '') {
    echo '<div class="alert alert-warning">' . _e($blog_error) . '</div>';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}
?>
<form method="get" class="mb-3 d-flex gap-2">
    <input type="search" class="form-control" name="q" value="<?php echo _e($q); ?>" placeholder="Search blog posts">
    <button type="submit" class="btn btn-primary">Search</button>
</form>
<p><a href="<?php echo _e(admin_url('blog/add')); ?>" class="btn btn-primary d-inline-flex align-items-center"><span class="material-icons me-1" style="font-size: 18px;">post_add</span> Add blog post</a></p>
<table class="table table-hover table-sm">
    <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $p) { ?>
    <tr>
        <td><?php echo (int) ($p->id ?? 0); ?></td>
        <td><a href="<?php echo _e(admin_url('blog/edit/' . ($p->id ?? 0))); ?>" class="fw-bold"><?php echo _e($p->title ?? '-'); ?></a></td>
        <td><span class="badge bg-secondary"><?php echo _e($p->username ?? '-'); ?></span></td>
        <td><span class="badge bg-<?php echo ($p->status === 'publish') ? 'success' : 'warning text-dark'; ?>"><?php echo _e(ucfirst($p->status ?? 'publish')); ?></span></td>
        <td><small class="text-muted"><?php echo _e($p->created_at ?? ''); ?></small></td>
        <td class="text-nowrap">
            <a href="<?php echo _e(admin_url('blog/edit/' . ($p->id ?? 0))); ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">edit</span></a>
            <a href="<?php echo _e(article_url($p->slug ?? '', $p->id ?? 0)); ?>" class="btn btn-sm btn-outline-info d-inline-flex align-items-center" target="_blank"><span class="material-icons" style="font-size: 16px;">visibility</span></a>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete this blog post?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) ($p->id ?? 0); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">delete</span></button>
            </form>
        </td>
    </tr>
    <?php } ?>
    <?php if (empty($items)) { ?><tr><td colspan="6" class="text-muted text-center py-4">No blog posts found. <a href="<?php echo _e(admin_url('blog/add')); ?>">Add blog post</a></td></tr><?php } ?>
    </tbody>
</table>
<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1) {
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = admin_url('blog') . '?p=' . $i . ($q !== '' ? '&q=' . rawurlencode($q) : '');
        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
