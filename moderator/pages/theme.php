<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'save_theme') {
        // Text Settings
        update_option('sitename', $_POST['sitename'] ?? 'Nia App');
        update_option('site_title', $_POST['site_title'] ?? '');
        update_option('site_description', $_POST['site_description'] ?? '');
        
        // Colors (defaults: distinct theme, not video-site clone)
        update_option('theme_color', $_POST['theme_color'] ?? '#4f46e5');
        update_option('pv_primary_dark', $_POST['pv_primary_dark'] ?? '#4338ca');
        update_option('pv_bg', $_POST['pv_bg'] ?? '#09090b');
        update_option('pv_text', $_POST['pv_text'] ?? '#ffffff');
        update_option('pv_border_color', $_POST['pv_border_color'] ?? '#3f3f46');
        
        // Typography & UI
        update_option('site_font', $_POST['site_font'] ?? '"Inter", system-ui, sans-serif');
        update_option('site_font_size', $_POST['site_font_size'] ?? '16px');
        update_option('site_radius', $_POST['site_radius'] ?? '0.5rem');
        update_option('button_style', $_POST['button_style'] ?? 'rounded');

        // File Uploads
        $upload_dir = ABSPATH . 'media' . DIRECTORY_SEPARATOR . 'branding' . DIRECTORY_SEPARATOR;
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

        // Logo
        if (!empty($_FILES['logo_file']['name'])) {
            $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
            $target = $upload_dir . 'logo.' . $ext;
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target)) {
                update_option('logo_url', url('media/branding/logo.' . $ext));
            }
        } elseif (!empty($_POST['logo_url'])) {
            update_option('logo_url', $_POST['logo_url']);
        }

        // Favicon
        if (!empty($_FILES['favicon_file']['name'])) {
            $ext = pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION);
            $target = $upload_dir . 'favicon.' . $ext;
            if (move_uploaded_file($_FILES['favicon_file']['tmp_name'], $target)) {
                update_option('favicon_url', url('media/branding/favicon.' . $ext));
            }
        } elseif (!empty($_POST['favicon_url'])) {
            update_option('favicon_url', $_POST['favicon_url']);
        }

        redirect(admin_url('theme') . '&msg=saved');
    }
}

$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = 'Theme configuration saved successfully.';
}

$admin_title = 'Theme Customizer';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';

// Helper for Google Fonts
$google_fonts = [
    '"Inter", system-ui, sans-serif' => 'Inter',
    '"Roboto", sans-serif' => 'Roboto',
    '"Outfit", sans-serif' => 'Outfit',
    '"Montserrat", sans-serif' => 'Montserrat',
    '"Open Sans", sans-serif' => 'Open Sans',
    '"Poppins", sans-serif' => 'Poppins',
    'system-ui, sans-serif' => 'System Default'
];
?>

<p class="text-muted small mb-4">Customize the look of your site. The default theme uses a distinct, modern style (not tied to any other platform).</p>
<div class="row g-4">
    <div class="col-lg-8">
        <?php if ($msg): ?>
            <div class="alert alert-success d-flex align-items-center shadow-sm">
                <span class="material-icons me-2">check_circle</span>
                <span><?php echo _e($msg); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_theme">

            <!-- Site Branding -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-primary me-2">branding_watermark</span> Site Branding</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Site Name</label>
                            <input type="text" name="sitename" class="form-control" value="<?php echo _e(get_option('sitename', 'Nia App')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Page Title Prefix</label>
                            <input type="text" name="site_title" class="form-control" value="<?php echo _e(get_option('site_title', '')); ?>" placeholder="Leave empty to use Site Name">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Description</label>
                            <textarea name="site_description" class="form-control" rows="2"><?php echo _e(get_option('site_description', '')); ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Site Logo</label>
                            <div class="input-group mb-2">
                                <input type="text" name="logo_url" class="form-control form-control-sm" value="<?php echo _e(get_option('logo_url', '')); ?>" placeholder="Logo URL">
                            </div>
                            <input type="file" name="logo_file" class="form-control form-control-sm">
                            <div class="form-text small">Upload or provide URL. (SVG, PNG, JPG)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Favicon</label>
                            <div class="input-group mb-2">
                                <input type="text" name="favicon_url" class="form-control form-control-sm" value="<?php echo _e(get_option('favicon_url', '')); ?>" placeholder="Favicon URL">
                            </div>
                            <input type="file" name="favicon_file" class="form-control form-control-sm">
                            <div class="form-text small">Upload or provide URL. (ICO, PNG)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual Colors -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-primary me-2">palette</span> Color Palette</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block">Accent Color (Primary)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="theme_color" class="form-control form-control-color border-0 shadow-none p-0" id="theme_color" value="<?php echo _e(get_option('theme_color', '#4f46e5')); ?>" style="width: 45px; height: 45px;">
                                <input type="text" class="form-control form-control-sm font-monospace" value="<?php echo _e(get_option('theme_color', '#4f46e5')); ?>" oninput="document.getElementById('theme_color').value = this.value">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block">Accent Dark (Hover)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="pv_primary_dark" class="form-control form-control-color border-0 shadow-none p-0" id="pv_primary_dark" value="<?php echo _e(get_option('pv_primary_dark', '#4338ca')); ?>" style="width: 45px; height: 45px;">
                                <input type="text" class="form-control form-control-sm font-monospace" value="<?php echo _e(get_option('pv_primary_dark', '#4338ca')); ?>" oninput="document.getElementById('pv_primary_dark').value = this.value">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block">Text Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="pv_text" class="form-control form-control-color border-0 shadow-none p-0" id="pv_text" value="<?php echo _e(get_option('pv_text', '#ffffff')); ?>" style="width: 45px; height: 45px;">
                                <input type="text" class="form-control form-control-sm font-monospace" value="<?php echo _e(get_option('pv_text', '#ffffff')); ?>" oninput="document.getElementById('pv_text').value = this.value">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block">Background (Dark)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="pv_bg" class="form-control form-control-color border-0 shadow-none p-0" id="pv_bg" value="<?php echo _e(get_option('pv_bg', '#09090b')); ?>" style="width: 45px; height: 45px;">
                                <input type="text" class="form-control form-control-sm font-monospace" value="<?php echo _e(get_option('pv_bg', '#09090b')); ?>" oninput="document.getElementById('pv_bg').value = this.value">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block">Border Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="pv_border_color" class="form-control form-control-color border-0 shadow-none p-0" id="pv_border_color" value="<?php echo _e(get_option('pv_border_color', '#3f3f46')); ?>" style="width: 45px; height: 45px;">
                                <input type="text" class="form-control form-control-sm font-monospace" value="<?php echo _e(get_option('pv_border_color', '#3f3f46')); ?>" oninput="document.getElementById('pv_border_color').value = this.value">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Typography & Settings -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-primary me-2">font_download</span> Typography & UI Style</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Primary Font Family</label>
                            <select name="site_font" class="form-select">
                                <?php 
                                $current_font = get_option('site_font') ?: '"Inter", system-ui, sans-serif';
                                foreach ($google_fonts as $val => $lbl): 
                                ?>
                                    <option value="<?php echo _e($val); ?>" <?php echo ($current_font === $val) ? 'selected' : ''; ?>><?php echo _e($lbl); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Base Font Size</label>
                            <select name="site_font_size" class="form-select">
                                <option value="14px" <?php echo get_option('site_font_size') == '14px' ? 'selected' : ''; ?>>14px (Small)</option>
                                <option value="16px" <?php echo get_option('site_font_size','16px') == '16px' ? 'selected' : ''; ?>>16px (Normal)</option>
                                <option value="18px" <?php echo get_option('site_font_size') == '18px' ? 'selected' : ''; ?>>18px (Large)</option>
                            </select>
                        </div>
                         <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Border Radius</label>
                            <select name="site_radius" class="form-select">
                                <option value="0" <?php echo get_option('site_radius') == '0' ? 'selected' : ''; ?>>None</option>
                                <option value="0.25rem" <?php echo get_option('site_radius') == '0.25rem' ? 'selected' : ''; ?>>Subtle (4px)</option>
                                <option value="0.375rem" <?php echo get_option('site_radius') == '0.375rem' ? 'selected' : ''; ?>>Small (6px)</option>
                                <option value="0.5rem" <?php echo get_option('site_radius') == '0.5rem' ? 'selected' : ''; ?>>Rounded (8px)</option>
                                <option value="0.75rem" <?php echo get_option('site_radius', '0.75rem') == '0.75rem' ? 'selected' : ''; ?>>Medium (12px)</option>
                                <option value="1rem" <?php echo get_option('site_radius') == '1rem' ? 'selected' : ''; ?>>Full (16px)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-5 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm d-flex align-items-center">
                    <span class="material-icons me-2">check</span> Apply Theme Changes
                </button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 20px;">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-info me-2">visibility</span> Theme Preview</h6>
            </div>
            <div class="card-body text-center py-5">
                <!-- Simple Logo Preview -->
                <div class="mb-4">
                    <?php if (get_option('logo_url')): ?>
                        <img src="<?php echo _e(get_option('logo_url')); ?>" alt="Logo" style="max-height: 40px;" class="mb-2">
                    <?php else: ?>
                        <div class="d-inline-flex align-items-center gap-2 fs-4 fw-bold">
                            <span class="material-icons text-primary fs-1">play_circle_filled</span>
                            <span><?php echo _e(get_option('sitename', 'Nia App')); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-4 rounded border mb-4 bg-light">
                    <button class="btn btn-primary rounded-pill mb-3 w-100">Primary Button</button>
                    <div class="text-muted small">Text Sample: High quality videos and premium entertainment.</div>
                </div>

                <p class="small text-muted mb-0">Changes appear on the main website immediately after saving. Please refresh the frontend to see the results.</p>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
