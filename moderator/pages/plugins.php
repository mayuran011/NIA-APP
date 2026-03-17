<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_plugins') {
        $enabled = isset($_POST['plugins_enabled']) ? trim($_POST['plugins_enabled']) : '';
        update_option('plugins_enabled', $enabled);
        redirect(admin_url('plugins'));
    }
}

$admin_title = 'Plugins';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
$plugins_dir = ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
$enabled = get_option('plugins_enabled', '');
$enabled_list = $enabled !== '' ? array_map('trim', explode(',', $enabled)) : [];
$available = is_dir($plugins_dir) ? array_filter(scandir($plugins_dir), function ($f) use ($plugins_dir) { return $f !== '.' && $f !== '..' && is_dir($plugins_dir . $f); }) : [];
sort($available);
$plugin_descriptions = [
    'cookiesfree' => 'Cookie consent banner',
    'videodown' => 'Download video/audio link on watch page',
    'socialshare' => 'Share buttons (Twitter, Facebook, WhatsApp, Copy link)',
    'backtotop' => 'Floating back-to-top button',
    'footertext' => 'Custom footer line (copyright, links)',
    'customhead' => 'Custom HTML in &lt;head&gt; (meta, scripts)',
];
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 d-flex align-items-center"><span class="material-icons text-primary me-2">extension</span> Manage Plugins</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_plugins">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Active Plugins Configuration</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><span class="material-icons">power</span></span>
                            <input type="text" class="form-control" name="plugins_enabled" value="<?php echo _e($enabled); ?>" placeholder="Enter plugin folder names separated by commas (e.g., videodown, cookiesfree)">
                        </div>
                        <div class="form-text mt-2 text-muted">
                            To enable a plugin, type its exact folder name below. Separate multiple plugins with commas. Removing a name disables the plugin.
                        </div>
                    </div>

                    <?php if (!empty($available)) { ?>
                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Available Plugins</h6>
                    <p class="text-muted small mb-3">Configure plugin options in <a href="<?php echo _e(admin_url('settings')); ?>">Settings</a> (scroll to Plugin settings).</p>
                    <div class="row g-3 mb-4">
                        <?php foreach($available as $avail) {
                            $is_enabled = in_array(trim($avail), $enabled_list);
                            $desc = isset($plugin_descriptions[$avail]) ? $plugin_descriptions[$avail] : 'Plugin';
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="p-3 border rounded shadow-sm h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0 text-dark fw-bold"><?php echo _e($avail); ?></h6>
                                    <span class="badge rounded-pill <?php echo $is_enabled ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $is_enabled ? 'On' : 'Off'; ?></span>
                                </div>
                                <p class="small text-muted mb-2 flex-grow-1"><?php echo _e($desc); ?></p>
                                <small class="font-monospace text-muted">/app/plugins/<?php echo _e($avail); ?></small>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } else { ?>
                        <div class="alert alert-info d-flex align-items-center">
                            <span class="material-icons me-2">info</span>
                            No plugins detected in the <code>/app/plugins/</code> directory yet.
                        </div>
                    <?php } ?>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center px-4 py-2 mt-2"><span class="material-icons me-1">save</span> Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
