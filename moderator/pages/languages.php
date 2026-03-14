<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_lang') {
        update_option('default_language', isset($_POST['default_language']) ? trim($_POST['default_language']) : 'en');
        update_option('languages_enabled', isset($_POST['languages_enabled']) ? trim($_POST['languages_enabled']) : 'en');
        update_option('rtl_languages', isset($_POST['rtl_languages']) ? trim($_POST['rtl_languages']) : '');
        redirect(admin_url('languages'));
    }
}

$admin_title = 'Languages';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 d-flex align-items-center"><span class="material-icons text-primary me-2">language</span> Language Configuration</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_lang">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">Primary Default Language</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><span class="material-icons" style="font-size: 18px;">public</span></span>
                            <select class="form-select" name="default_language">
                                <option value="en" <?php echo get_option('default_language', 'en') === 'en' ? 'selected' : ''; ?>>English (EN)</option>
                                <option value="es" <?php echo get_option('default_language') === 'es' ? 'selected' : ''; ?>>Spanish (ES)</option>
                                <option value="fr" <?php echo get_option('default_language') === 'fr' ? 'selected' : ''; ?>>French (FR)</option>
                                <option value="de" <?php echo get_option('default_language') === 'de' ? 'selected' : ''; ?>>German (DE)</option>
                                <option value="pt" <?php echo get_option('default_language') === 'pt' ? 'selected' : ''; ?>>Portuguese (PT)</option>
                            </select>
                        </div>
                        <div class="form-text mt-2">This is the default fallback language for all visitors.</div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">Enabled / Supported Languages</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><span class="material-icons" style="font-size: 18px;">translate</span></span>
                            <input type="text" class="form-control" name="languages_enabled" value="<?php echo _e(get_option('languages_enabled', 'en')); ?>" placeholder="en,es,fr,de">
                        </div>
                        <div class="form-text mt-2 text-muted">Comma-separated list of enabled language short codes. These control the language switcher in the frontend. (e.g. <code>en, es, pt, ar</code>)</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">RTL (Right-to-Left) Orientations</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><span class="material-icons" style="font-size: 18px;">format_textdirection_r_to_l</span></span>
                            <input type="text" class="form-control" name="rtl_languages" value="<?php echo _e(get_option('rtl_languages', '')); ?>" placeholder="ar,he">
                        </div>
                        <div class="form-text mt-2 text-muted">Add comma-separated language codes that require Right-to-Left layout support. (e.g. Arabic <code>ar</code>, Hebrew <code>he</code>)</div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center px-4 py-2"><span class="material-icons me-1">save</span> Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 bg-light p-2">
            <div class="card-body">
                <h6 class="fw-bold d-flex align-items-center mb-3"><span class="material-icons text-warning me-2">help_outline</span> Translations Guide</h6>
                <p class="small text-muted mb-2">The system checks for localized strings inside the <code>lang/</code> directory folder.</p>
                <div class="bg-dark text-light p-3 rounded font-monospace small mb-3">
                    /lang/en.php <br>
                    /lang/es.php
                </div>
                <p class="small text-muted mb-0">Missing translations will automatically fallback to English mapping or the direct option keys.</p>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
