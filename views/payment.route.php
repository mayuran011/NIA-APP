<?php
/**
 * Payment route (/payment/): handles plan selection and payment flow.
 */
if (!defined('in_nia_app')) exit;

if (!is_logged()) {
    redirect(url('login') . '?redirect=' . urlencode(url('payment')));
}

$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : '');
$thanks = isset($_GET['thanks']);
$cancelled = isset($_GET['cancelled']);

// Handle plan activation (Mock checkout)
if ($action === 'confirm' && isset($_POST['plan_id'])) {
    $plan_id = (int)$_POST['plan_id'];
    if (create_subscription(current_user_id(), $plan_id, 'MOCK_PAYMENT_' . time())) {
        redirect(url('payment?thanks=1'));
    }
}

$page_title = 'Get Premium';
$plans = get_membership_plans();

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<style>
.nia-plan-card {
    transition: transform 0.3s, border-color 0.3s;
    border: 1px solid var(--pv-border) !important;
}
.nia-plan-card:hover {
    transform: translateY(-5px);
    border-color: var(--pv-primary) !important;
}
.nia-plan-price { font-size: 2rem; font-weight: 800; }
.nia-plan-check { border-bottom: 3px solid var(--pv-primary); width: 40px; }
</style>

<main class="nia-main container py-5">
    <?php if ($thanks): ?>
        <div class="text-center py-5">
            <span class="material-icons text-success mb-3" style="font-size: 5rem;">check_circle</span>
            <h1 class="fw-bold">Welcome to Premium!</h1>
            <p class="text-muted fs-5">Your subscription has been activated. Enjoy exclusive features.</p>
            <div class="mt-4">
                <a href="<?php echo url('premiumhub'); ?>" class="btn btn-primary px-5 py-2 rounded-pill shadow">Go to Premium Hub</a>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center mb-5">
            <h1 class="nia-title display-5 mb-2">Upgrade your experience</h1>
            <p class="text-muted fs-5">Choose the plan that fits you best and unlock premium content.</p>
            <div class="d-inline-block nia-plan-check"></div>
        </div>

        <?php if (empty($plans)): ?>
            <div class="alert alert-secondary text-center p-5">
                <span class="material-icons mb-2">construction</span>
                <p class="mb-0">Premium plans are currently being updated. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="row g-4 justify-content-center">
                <?php foreach ($plans as $p): ?>
                    <div class="col-md-4">
                        <div class="card h-100 nia-plan-card bg-dark rounded-4 overflow-hidden">
                            <div class="card-body p-4 text-center">
                                <h3 class="h5 fw-bold mb-4 text-uppercase tracking-wider"><?php echo _e($p->title); ?></h3>
                                <div class="nia-plan-price mb-1"><?php echo _e($p->currency); ?> <?php echo _e($p->price); ?></div>
                                <div class="small text-muted mb-4"><?php echo $p->duration == 0 ? 'Lifetime access' : 'For ' . $p->duration . ' days'; ?></div>
                                
                                <div class="text-start mb-4">
                                    <?php 
                                    $features = explode("\n", $p->description);
                                    foreach ($features as $f): if (trim($f) === '') continue;
                                    ?>
                                        <div class="d-flex align-items-center mb-2 small">
                                            <span class="material-icons text-success me-2" style="font-size: 1.1rem;">check_circle_outline</span>
                                            <span><?php echo _e(trim($f)); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <form method="post" action="?action=confirm">
                                    <input type="hidden" name="plan_id" value="<?php echo $p->id; ?>">
                                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm">Get Started</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-5 pt-4 text-center border-top border-secondary border-opacity-25">
             <p class="small text-muted mb-0">Secure checkout. We currently support mock payments for demonstration. <br>
             <a href="<?php echo url('premiumhub'); ?>" class="text-decoration-none">Back to Premium Hub</a></p>
        </div>
    <?php endif; ?>
</main>

<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
