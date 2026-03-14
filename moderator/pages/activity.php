<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$admin_title = 'Activity log';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 50;
$offset = ($page - 1) * $per;
$total = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}activity"));
$items = $db->fetchAll(
    "SELECT a.id AS id, a.user_id AS user_id, a.action AS action, a.object_type AS object_type, a.object_id AS object_id, a.created_at AS created_at, u.username AS username FROM {$pre}activity a LEFT JOIN {$pre}users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT ? OFFSET ?",
    [$per, $offset]
);
$items = admin_normalize_rows($items, ['id', 'user_id', 'action', 'object_type', 'object_id', 'created_at', 'username']);

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 d-flex align-items-center"><span class="material-icons text-primary me-2">history</span> Audit & Activity Log</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="bg-light text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                    <tr>
                        <th class="border-bottom-0 ps-4">Event ID</th>
                        <th class="border-bottom-0">Initiated By</th>
                        <th class="border-bottom-0">Event Action</th>
                        <th class="border-bottom-0">Target Entity</th>
                        <th class="border-bottom-0">Target ID</th>
                        <th class="border-bottom-0 pe-4">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                <?php foreach ($items as $a) { ?>
                <tr>
                    <td class="ps-4 text-muted"><small>#<?php echo (int) $a->id; ?></small></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="material-icons text-secondary me-2" style="font-size: 16px;">person</span>
                            <span class="fw-bold text-dark"><?php echo _e($a->username ?? 'System/Guest'); ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="badge border bg-light text-dark fw-medium border-secondary border-opacity-25 rounded-pill px-3 py-2">
                            <span class="material-icons text-info align-middle me-1" style="font-size: 14px;">bolt</span>
                            <?php echo _e($a->action); ?>
                        </span>
                    </td>
                    <td><span class="badge bg-secondary rounded text-uppercase px-2 py-1"><small><?php echo _e($a->object_type); ?></small></span></td>
                    <td class="text-muted font-monospace"><small><?php echo (int) $a->object_id; ?></small></td>
                    <td class="pe-4 text-muted"><span class="material-icons text-muted align-middle me-1" style="font-size: 14px;">schedule</span><small><?php echo _e($a->created_at ?? ''); ?></small></td>
                </tr>
                <?php } ?>
                <?php if(empty($items)) { ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <span class="material-icons d-block mb-2 text-secondary" style="font-size: 32px;">hourglass_empty</span>
                            No activity logs found.
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1) {
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = admin_url('activity') . '?p=' . $i;
        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
