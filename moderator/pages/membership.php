<?php
if (!defined('in_nia_app')) exit;

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_plan') {
        $title = trim($_POST['title']);
        $price = (float) ($_POST['price'] ?? 0);
        $duration = (int) ($_POST['duration'] ?? 30);
        $desc = trim($_POST['description'] ?? '');
        
        if ($title !== '') {
            $db->query(
                "INSERT INTO {$pre}membership_plans (title, description, price, duration, is_active) VALUES (?, ?, ?, ?, 1)",
                [$title, $desc, $price, $duration]
            );
            redirect(admin_url('membership') . '&msg=added');
        }
    }
    if ($action === 'toggle_plan') {
        $id = (int) $_POST['id'];
        $db->query("UPDATE {$pre}membership_plans SET is_active = 1 - is_active WHERE id = ?", [$id]);
        echo json_encode(['ok' => true]);
        exit;
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $msg = 'Membership plan created successfully.';
}

$plans = get_membership_plans(false);
$admin_title = 'Membership & Plans';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <?php if ($msg): ?>
            <div class="alert alert-success d-flex align-items-center shadow-sm mb-4">
                <span class="material-icons me-2">check_circle</span>
                <span><?php echo _e($msg); ?></span>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-primary me-2">workspace_premium</span> Active Membership Plans</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Title</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($plans)): ?>
                                <tr>
                                    <td colspan="5" class="py-5 text-center text-muted">No plans found. Create one on the right.</td>
                                </tr>
                            <?php else: foreach ($plans as $p): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo _e($p->title); ?></div>
                                        <div class="small text-muted"><?php echo _e($p->description); ?></div>
                                    </td>
                                    <td><?php echo _e($p->price . ' ' . $p->currency); ?></td>
                                    <td><?php echo $p->duration == 0 ? 'Lifetime' : $p->duration . ' Days'; ?></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input nia-toggle-plan" type="checkbox" data-id="<?php echo $p->id; ?>" <?php echo $p->is_active ? 'checked' : ''; ?>>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-danger" onclick="if(confirm('Delete?')) location.href='?id=<?php echo $p->id; ?>&action=delete';"><span class="material-icons" style="font-size:1rem;">delete</span></button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Create New Plan</h6>
            </div>
            <div class="card-body p-4">
                <form method="post">
                    <input type="hidden" name="action" value="add_plan">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Plan Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Pro Monthly" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="9.99">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Days</label>
                            <input type="number" name="duration" class="form-control" value="30">
                            <div class="form-text small" style="font-size:0.65rem;">0 = Lifetime access</div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Ad-free, HD videos, etc..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm fw-bold">Create Plan</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4 text-center">
                <span class="material-icons mb-2" style="font-size:3rem;">insights</span>
                <h5 class="fw-bold">Monetization</h5>
                <p class="small opacity-75">Define plans here. In the next phase, we will connect these to payment gateways like Stripe and PayPal.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.nia-toggle-plan').forEach(function(el) {
        el.addEventListener('change', function() {
            var fd = new FormData();
            fd.append('action', 'toggle_plan');
            fd.append('id', el.getAttribute('data-id'));
            fetch(window.location.href, { method: 'POST', body: fd });
        });
    });
});
</script>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
