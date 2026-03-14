<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_announcement') {
        update_option('announcement_enable', isset($_POST['announcement_enable']) ? '1' : '0');
        update_option('announcement_msg', trim($_POST['announcement_msg'] ?? ''));
        update_option('announcement_link', trim($_POST['announcement_link'] ?? ''));
        update_option('announcement_type', $_POST['announcement_type'] ?? 'info');
        redirect(admin_url('announcement') . '&msg=saved');
    }
}

$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = 'Site announcement updated successfully.';
}

$admin_title = 'Site Announcement';
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
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-primary me-2">campaign</span> Broadcast Announcement</h6>
            </div>
            <div class="card-body p-4">
                <form method="post">
                    <input type="hidden" name="action" value="save_announcement">

                    <div class="form-check form-switch mb-4 fs-5">
                        <input class="form-check-input" type="checkbox" role="switch" name="announcement_enable" id="ann_enable" value="1" <?php echo get_option('announcement_enable', '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold ms-2" for="ann_enable">Enable Global Announcement Bar</label>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Announcement Message</label>
                        <textarea name="announcement_msg" class="form-control" rows="3" placeholder="What would you like to say?"><?php echo _e(get_option('announcement_msg', '')); ?></textarea>
                        <div class="form-text small">Supports plain text. This will appear at the top of every page.</div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted text-uppercase">Action Link URL (Optional)</label>
                            <input type="text" name="announcement_link" class="form-control" value="<?php echo _e(get_option('announcement_link', '')); ?>" placeholder="https://...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Notice Style</label>
                            <select name="announcement_type" class="form-select">
                                <option value="primary" <?php echo get_option('announcement_type') == 'primary' ? 'selected' : ''; ?>>Primary (Blue)</option>
                                <option value="info" <?php echo get_option('announcement_type', 'info') == 'info' ? 'selected' : ''; ?>>Informative (Cyan)</option>
                                <option value="warning" <?php echo get_option('announcement_type') == 'warning' ? 'selected' : ''; ?>>Attention (Yellow)</option>
                                <option value="danger" <?php echo get_option('announcement_type') == 'danger' ? 'selected' : ''; ?>>Urgent (Red)</option>
                                <option value="success" <?php echo get_option('announcement_type') == 'success' ? 'selected' : ''; ?>>Success (Green)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end pt-2 border-top">
                        <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm d-flex align-items-center">
                            <span class="material-icons me-2">broadcast_on_home</span> Update Broadcast
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
                    Announcement Tips
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small fw-bold text-dark mb-1">Visibility</div>
                    <p class="small text-muted mb-0">The announcement bar appears for all visitors (logged in or guests) at the very top of the frontend.</p>
                </div>
                <div class="mb-0">
                    <div class="small fw-bold text-dark mb-1">Engagement</div>
                    <p class="small text-muted mb-0">Use the Action Link to drive traffic to specific blog posts, videos, or premium subscription pages.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
