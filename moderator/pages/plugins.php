<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_plugins') {
        $enabled = '';
        if (!empty($_POST['plugins_toggle']) && is_array($_POST['plugins_toggle'])) {
            $enabled = implode(',', array_map(function ($s) { return preg_replace('/[^a-z0-9\-_]/', '', trim($s)); }, array_filter($_POST['plugins_toggle'])));
        }
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

$plugin_meta = [
    'cookiesfree'   => ['icon' => 'cookie',           'label' => 'Cookie consent banner', 'color' => 'primary'],
    'videodown'     => ['icon' => 'download',        'label' => 'Download video/audio on watch page', 'color' => 'info'],
    'socialshare'   => ['icon' => 'share',           'label' => 'Share buttons (Twitter, Facebook, WhatsApp, Copy)', 'color' => 'success'],
    'backtotop'     => ['icon' => 'vertical_align_top', 'label' => 'Floating back-to-top button', 'color' => 'secondary'],
    'footertext'    => ['icon' => 'description',     'label' => 'Custom footer line (copyright, links)', 'color' => 'dark'],
    'customhead'    => ['icon' => 'code',            'label' => 'Custom HTML in &lt;head&gt; (meta, scripts)', 'color' => 'warning'],
    'noindex'       => ['icon' => 'visibility_off',  'label' => 'Add noindex/nofollow meta (e.g. staging)', 'color' => 'danger'],
];
$settings_url = admin_url('settings');
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="mb-0 d-flex align-items-center">
                    <span class="material-icons text-primary me-2">extension</span>
                    Manage Plugins
                </h5>
                <a href="<?php echo _e($settings_url); ?>#plugin-settings" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center">
                    <span class="material-icons me-1" style="font-size:1.1rem;">settings</span>
                    Plugin options in Settings
                </a>
            </div>
            <div class="card-body">
                <form method="post" id="plugins-form">
                    <input type="hidden" name="action" value="save_plugins">

                    <?php if (!empty($available)) { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:3rem"></th>
                                    <th>Plugin</th>
                                    <th class="d-none d-md-table-cell">Description</th>
                                    <th class="text-center" style="width:6rem">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available as $slug) {
                                    $is_enabled = in_array(trim($slug), $enabled_list);
                                    $meta = isset($plugin_meta[$slug]) ? $plugin_meta[$slug] : ['icon' => 'extension', 'label' => 'Plugin', 'color' => 'secondary'];
                                    $icon = $meta['icon'];
                                    $label = $meta['label'];
                                    $color = $meta['color'];
                                ?>
                                <tr>
                                    <td>
                                        <span class="material-icons text-<?php echo _e($color); ?>" style="font-size:1.75rem;" title="<?php echo _e($slug); ?>"><?php echo _e($icon); ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold"><?php echo _e($slug); ?></span>
                                        <div class="d-md-none small text-muted mt-1"><?php echo _e($label); ?></div>
                                    </td>
                                    <td class="d-none d-md-table-cell text-muted small"><?php echo _e($label); ?></td>
                                    <td class="text-center">
                                        <div class="form-check form-switch form-check-lg justify-content-center mb-0 d-inline-flex">
                                            <input class="form-check-input" type="checkbox" name="plugins_toggle[]" value="<?php echo _e($slug); ?>" id="plugin-<?php echo _e($slug); ?>" <?php echo $is_enabled ? 'checked' : ''; ?>>
                                            <label class="form-check-label ms-2 small" for="plugin-<?php echo _e($slug); ?>">
                                                <?php echo $is_enabled ? 'On' : 'Off'; ?>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-3 mb-0">
                        <span class="material-icons align-middle me-1" style="font-size:1rem;">info</span>
                        Toggle each plugin on or off, then click <strong>Save</strong>. Configure options in <a href="<?php echo _e($settings_url); ?>">Settings</a> (Plugin settings section).
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center px-4">
                            <span class="material-icons me-1">save</span>
                            Save
                        </button>
                        <?php if (count($available) > 0) { ?><span class="badge bg-light text-dark border" id="plugins-count"><?php echo count($enabled_list); ?> enabled</span><?php } ?>
                    </div>
                    <?php } else { ?>
                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <span class="material-icons me-2">info</span>
                        <div>
                            <strong>No plugins found.</strong> Add plugin folders under <code>/app/plugins/</code> (each with a <code>plugin.php</code> file) to see them here.
                        </div>
                    </div>
                    <?php } ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('plugins-form');
    var countEl = document.getElementById('plugins-count');
    if (!form || !countEl) return;
    function updateCount() {
        var checked = form.querySelectorAll('input[name="plugins_toggle[]"]:checked');
        countEl.textContent = checked.length + ' enabled';
    }
    form.querySelectorAll('input[name="plugins_toggle[]"]').forEach(function(cb) {
        cb.addEventListener('change', updateCount);
    });
})();
</script>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
