<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

// Video ads: VAST URL or IMA tag URL, placement, ad type. Store as options.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_ads') {
        update_option('ad_type', isset($_POST['ad_type']) ? trim($_POST['ad_type']) : 'vast');
        update_option('ad_vast_url', isset($_POST['ad_vast_url']) ? trim($_POST['ad_vast_url']) : '');
        update_option('ad_ima_tag_url', isset($_POST['ad_ima_tag_url']) ? trim($_POST['ad_ima_tag_url']) : '');
        update_option('ad_placement', isset($_POST['ad_placement']) ? trim($_POST['ad_placement']) : 'preroll');
        update_option('ads_enabled', isset($_POST['ads_enabled']) ? '1' : '0');
        redirect(admin_url('ads'));
    }
}

$admin_title = 'Ads';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
$ad_type = get_option('ad_type', 'vast');
$ad_vast = get_option('ad_vast_url', '');
$ad_ima = get_option('ad_ima_tag_url', '');
$ad_placement = get_option('ad_placement', 'preroll');
$ads_enabled = get_option('ads_enabled', '0');
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 d-flex align-items-center"><span class="material-icons text-primary me-2">monetization_on</span> Video Advertising Setup</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_ads">
                    
                    <div class="alert bg-light border d-flex align-items-center mb-4">
                        <div class="form-check form-switch fs-5 mb-0">
                            <input class="form-check-input mt-1" type="checkbox" role="switch" name="ads_enabled" id="ads_enabled" value="1" <?php echo $ads_enabled === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label ms-2 text-dark fw-bold" style="font-size: 1rem;" for="ads_enabled">Enable Video Advertisements</label>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Advertisement Protocol</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><span class="material-icons" style="font-size: 18px;">settings_ethernet</span></span>
                                <select class="form-select" name="ad_type">
                                    <option value="vast" <?php echo $ad_type === 'vast' ? 'selected' : ''; ?>>VAST 3.0 / 4.0 Standard</option>
                                    <option value="ima" <?php echo $ad_type === 'ima' ? 'selected' : ''; ?>>Google IMA SDK (vjimaads)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Ad Placement Strategy</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><span class="material-icons" style="font-size: 18px;">movie_filter</span></span>
                                <select class="form-select" name="ad_placement">
                                    <option value="preroll" <?php echo $ad_placement === 'preroll' ? 'selected' : ''; ?>>Pre-roll (Before video)</option>
                                    <option value="midroll" <?php echo $ad_placement === 'midroll' ? 'selected' : ''; ?>>Mid-roll (During video)</option>
                                    <option value="postroll" <?php echo $ad_placement === 'postroll' ? 'selected' : ''; ?>>Post-roll (After video)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary d-flex align-items-center"><span class="text-danger me-1 fw-bold">VAST</span> Extensible Markup Language URL</label>
                        <input type="url" class="form-control" name="ad_vast_url" value="<?php echo _e($ad_vast); ?>" placeholder="https://adserver.com/vast.xml">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary d-flex align-items-center"><span class="text-primary me-1 fw-bold">IMA</span> Advertising Tag URL / Network Code</label>
                        <input type="url" class="form-control" name="ad_ima_tag_url" value="<?php echo _e($ad_ima); ?>" placeholder="https://pubads.g.doubleclick.net/gampad/ads...">
                        <div class="form-text mt-2">
                            <span class="material-icons text-info align-middle" style="font-size: 16px;">info</span> Required for Google Interactive Media Ads integrations. Hooks injected automatically.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center px-4 py-2"><span class="material-icons me-1">save</span> Save Monetization Setup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 bg-light p-2">
            <div class="card-body">
                <h6 class="fw-bold d-flex align-items-center mb-3"><span class="material-icons text-warning me-2">help_outline</span> Developer Integration</h6>
                <p class="small text-muted mb-2">The selected Ad URL is globally injected into your site's player system.</p>
                <div class="bg-dark text-light p-3 rounded font-monospace small mb-3">
                    <span class="text-info">Hooks available:</span><br>
                    vibe_before_player<br>
                    vibe_after_player<br>
                    the_embedded_video
                </div>
                <p class="small text-muted mb-0">These action hooks can wrap elements dynamically or pass down IMA data dynamically to themes without editing core files.</p>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
