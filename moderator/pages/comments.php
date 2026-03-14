<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $cid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($cid > 0 && $action === 'delete') {
        $db->query("DELETE FROM {$pre}comment_likes WHERE comment_id = ?", [$cid]);
        $db->query("DELETE FROM {$pre}comments WHERE id = ? OR parent_id = ?", [$cid, $cid]);
        redirect(admin_url('comments'));
    }
}

$admin_title = 'Comments';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 30;
$offset = ($page - 1) * $per;
$where = "1=1";
$params = [];
if ($q !== '') {
    $where .= " AND (c.body LIKE ? OR u.username LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$total = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}comments c LEFT JOIN {$pre}users u ON u.id = c.user_id WHERE " . $where, $params));
$params[] = $per;
$params[] = $offset;
$items = $db->fetchAll(
    "SELECT c.id AS id, c.parent_id AS parent_id, c.object_type AS object_type, c.object_id AS object_id, c.body AS body, c.likes_count AS likes_count, c.created_at AS created_at, u.username AS username FROM {$pre}comments c LEFT JOIN {$pre}users u ON u.id = c.user_id WHERE " . $where . " ORDER BY c.created_at DESC LIMIT ? OFFSET ?",
    $params
);
$items = admin_normalize_rows($items, ['id', 'parent_id', 'object_type', 'object_id', 'body', 'likes_count', 'created_at', 'username']);

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>
<form method="get" class="mb-3 d-flex gap-2">
    <input type="search" class="form-control" name="q" value="<?php echo _e($q); ?>" placeholder="Search comments">
    <button type="submit" class="btn btn-primary">Search</button>
</form>
<table class="table table-hover table-sm">
    <thead><tr><th>ID</th><th>Author</th><th>Content</th><th>Target Object</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $c) {
        $on_link = ($c->object_type ?? '') === 'video' ? watch_url($c->object_id ?? 0) : (($c->object_type ?? '') === 'music' ? (function_exists('listen_url') ? listen_url($c->object_id ?? 0) : watch_url($c->object_id ?? 0)) : (($c->object_type ?? '') === 'image' ? (function_exists('view_url') ? view_url($c->object_id ?? 0) : image_url($c->object_id ?? 0, '')) : '#'));
    ?>
    <tr>
        <td><?php echo (int) $c->id; ?></td>
        <td><?php echo _e($c->username ?? '-'); ?></td>
        <td><?php echo _e(mb_substr($c->body, 0, 80)); ?><?php echo mb_strlen($c->body) > 80 ? '…' : ''; ?></td>
        <td><span class="badge bg-<?php echo ($c->object_type==='video') ? 'primary' : 'info'; ?>"><?php echo _e(ucfirst($c->object_type)); ?></span> <a href="<?php echo _e($on_link); ?>" target="_blank" class="text-decoration-none">#<?php echo (int) $c->object_id; ?> <span class="material-icons align-middle" style="font-size: 14px;">open_in_new</span></a></td>
        <td><small class="text-muted"><?php echo _e($c->created_at ?? ''); ?></small></td>
        <td>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete this comment?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $c->id; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center"><span class="material-icons" style="font-size: 16px;">delete</span></button>
            </form>
        </td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1) {
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = admin_url('comments') . '?p=' . $i . ($q !== '' ? '&q=' . rawurlencode($q) : '');
        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
