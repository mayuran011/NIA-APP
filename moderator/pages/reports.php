<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $rid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($rid > 0) {
        if ($action === 'dismiss') {
            $db->query("DELETE FROM {$pre}reports WHERE id = ?", [$rid]);
            redirect(admin_url('reports'));
        }
        if ($action === 'delete_content') {
            $r = $db->fetch("SELECT object_type, object_id FROM {$pre}reports WHERE id = ?", [$rid]);
            $r = admin_normalize_row($r, ['object_type', 'object_id']);
            if ($r) {
                $otype = $r->object_type ?? '';
                $oid = (int) ($r->object_id ?? 0);
                if ($otype === 'video' && $oid > 0) {
                    $db->query("DELETE FROM {$pre}videos WHERE id = ?", [$oid]);
                } elseif ($otype === 'image' && $oid > 0) {
                    $db->query("DELETE FROM {$pre}images WHERE id = ?", [$oid]);
                } elseif ($otype === 'comment' && $oid > 0) {
                    $db->query("DELETE FROM {$pre}comment_likes WHERE comment_id = ?", [$oid]);
                    $db->query("DELETE FROM {$pre}comments WHERE id = ? OR parent_id = ?", [$oid, $oid]);
                }
                $db->query("DELETE FROM {$pre}reports WHERE object_type = ? AND object_id = ?", [$otype, $oid]);
            }
            redirect(admin_url('reports'));
        }
    }
}

$admin_title = 'Reports';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 30;
$offset = ($page - 1) * $per;
$total = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}reports"));
$items = $db->fetchAll(
    "SELECT r.id AS id, r.user_id AS user_id, r.object_type AS object_type, r.object_id AS object_id, r.reason AS reason, r.details AS details, r.created_at AS created_at, u.username AS reporter FROM {$pre}reports r LEFT JOIN {$pre}users u ON u.id = r.user_id ORDER BY r.created_at DESC LIMIT ? OFFSET ?",
    [$per, $offset]
);
$items = admin_normalize_rows($items, ['id', 'user_id', 'object_type', 'object_id', 'reason', 'details', 'created_at', 'reporter']);

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>
<table class="table table-hover table-sm">
    <thead><tr><th>ID</th><th>Reporter</th><th>Target Object</th><th>Reason</th><th>Details</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $r) {
        $obj_link = ($r->object_type ?? '') === 'video' ? watch_url($r->object_id ?? 0) : (($r->object_type ?? '') === 'music' ? (function_exists('listen_url') ? listen_url($r->object_id ?? 0) : watch_url($r->object_id ?? 0)) : (($r->object_type ?? '') === 'image' ? (function_exists('view_url') ? view_url($r->object_id ?? 0) : image_url($r->object_id ?? 0, '')) : '#'));
    ?>
    <tr>
        <td><?php echo (int) $r->id; ?></td>
        <td><?php echo _e($r->reporter ?? '-'); ?></td>
        <td><span class="badge bg-<?php echo ($r->object_type==='video') ? 'primary' : 'secondary'; ?>"><?php echo _e(ucfirst($r->object_type)); ?></span> <a href="<?php echo _e($obj_link); ?>" target="_blank" class="text-decoration-none fw-bold">#<?php echo (int) $r->object_id; ?> <span class="material-icons align-middle" style="font-size: 14px;">open_in_new</span></a></td>
        <td><span class="text-danger fw-bold"><?php echo _e($r->reason); ?></span></td>
        <td><small><?php echo _e(mb_substr($r->details ?? '', 0, 60)); ?><?php echo mb_strlen($r->details ?? '') > 60 ? '…' : ''; ?></small></td>
        <td><small class="text-muted"><?php echo _e($r->created_at ?? ''); ?></small></td>
        <td class="text-nowrap">
            <form method="post" class="d-inline">
                <input type="hidden" name="action" value="dismiss"><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
                <button type="submit" class="btn btn-sm btn-light border d-inline-flex align-items-center me-1"><span class="material-icons" style="font-size: 16px;">done</span></button>
            </form>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete reported content and all reports for it?');">
                <input type="hidden" name="action" value="delete_content"><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
                <button type="submit" class="btn btn-sm btn-danger d-inline-flex align-items-center"><span class="material-icons" style="font-size: 16px;">delete_forever</span></button>
            </form>
        </td>
    </tr>
    <?php } ?>
    </tbody>
</table>
<?php if (empty($items)) { ?><p class="text-muted">No reports.</p><?php } ?>
<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1) {
    echo '<nav class="mt-3"><ul class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = admin_url('reports') . '?p=' . $i;
        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
