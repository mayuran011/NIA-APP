<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_vine') {
        update_option('vine_importer_enabled', isset($_POST['vine_importer_enabled']) ? '1' : '0');
        redirect(admin_url('vine'));
    }
}

$admin_title = 'Vine';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 d-flex align-items-center"><span class="material-icons text-secondary me-2">history</span> Vine Legacy Integration</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning border-0 d-flex mb-4 shadow-sm">
                    <span class="material-icons me-3 fs-2 text-warning">warning_amber</span>
                    <div>
                        <h6 class="fw-bold mb-1">Legacy Notice</h6>
                        <p class="mb-0 small">Vine has been officially discontinued. This section is preserved solely to manage backward compatibility and import logic for legacy Vine.co URLs.</p>
                    </div>
                </div>
                
                <form method="post">
                    <input type="hidden" name="action" value="save_vine">
                    
                    <div class="alert bg-light border d-flex align-items-center mb-4">
                        <div class="form-check form-switch fs-5 mb-0">
                            <input class="form-check-input mt-1" type="checkbox" role="switch" name="vine_importer_enabled" id="vine_importer_enabled" value="1" <?php echo get_option('vine_importer_enabled', '0') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label ms-2 text-dark fw-bold" style="font-size: 1rem;" for="vine_importer_enabled">Enable Legacy Vine Importer</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center px-4 py-2"><span class="material-icons me-1">save</span> Save Legacy Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
