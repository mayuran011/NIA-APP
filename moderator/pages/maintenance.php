<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_maintenance') {
        update_option('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        update_option('maintenance_message', isset($_POST['maintenance_message']) ? trim($_POST['maintenance_message']) : 'Our site is currently undergoing scheduled maintenance. We will be back soon!');
        redirect(admin_url('maintenance') . '&msg=saved');
    }
}

$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = 'Maintenance settings updated.';
}

$admin_title = 'Maintenance Mode';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <?php if ($msg): ?>
            <div class="alert alert-success d-flex align-items-center shadow-sm">
                <span class="material-icons me-2">check_circle</span>
                <span><?php echo _e($msg); ?></span>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom d-flex align-items-center py-3">
                <span class="material-icons text-warning me-2">construction</span>
                <h5 class="mb-0 fw-bold">Maintenance Configuration</h5>
            </div>
            <div class="card-body p-4">
                <form method="post">
                    <input type="hidden" name="action" value="save_maintenance">
                    
                    <div class="alert bg-light border d-flex align-items-center mb-4 p-3 rounded shadow-none">
                        <div class="form-check form-switch fs-5 mb-0">
                            <input class="form-check-input mt-1" type="checkbox" role="switch" name="maintenance_mode" id="m_mode" value="1" <?php echo get_option('maintenance_mode', '0') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label ms-2 text-dark fw-bold" for="m_mode" style="font-size: 1rem;">Enable Maintenance Mode</label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Maintenance Message</label>
                        <textarea class="form-control" name="maintenance_message" rows="5" placeholder="Enter the message visitors will see..."><?php echo _e(get_option('maintenance_message', 'Our site is currently undergoing scheduled maintenance. We will be back soon!')); ?></textarea>
                        <div class="form-text mt-2 small text-muted">This message will be displayed to all visitors except logged-in administrators.</div>
                    </div>

                    <div class="d-flex justify-content-end pb-2">
                        <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm d-flex align-items-center">
                            <span class="material-icons me-2">save</span> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 bg-white mb-4">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-bold d-flex align-items-center">
                    <span class="material-icons text-info me-2">info_outline</span> 
                    Quick Overview
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small fw-bold text-dark mb-1">Public Access</div>
                    <p class="small text-muted mb-0">All non-admin traffic will be intercepted and shown the maintenance screen.</p>
                </div>
                <div class="mb-3">
                    <div class="small fw-bold text-dark mb-1">Core Admin Access</div>
                    <p class="small text-muted mb-0">Administrators retain full access to both the dashboard and the frontend for testing.</p>
                </div>
                <div class="mb-0">
                    <div class="small fw-bold text-dark mb-1">SEO Impact</div>
                    <p class="small text-muted mb-0">The system returns a <code>503 Service Unavailable</code> header to prevent search engines from de-indexing your pages during downtime.</p>
                </div>
            </div>
        </div>
        
        <div class="alert bg-warning bg-opacity-10 border-warning border-opacity-25 text-dark small d-flex mb-0 rounded-3">
            <span class="material-icons me-2 fs-5 text-warning">lightbulb</span>
            <span>Remember to disable this once your maintenance work is complete!</span>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
