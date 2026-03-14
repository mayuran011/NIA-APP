<?php
if (!defined('in_nia_app')) exit;

$cleared = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'clear_cache') {
        $base = defined('TMP_FOLDER') ? rtrim(TMP_FOLDER, DIRECTORY_SEPARATOR) : (ABSPATH . 'tmp');
        $cache_dir = $base . DIRECTORY_SEPARATOR . 'cache';
        if (is_dir($cache_dir)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $f) {
                if ($f->isFile()) @unlink($f->getPathname());
            }
        }
        global $vibe_options_cache;
        $vibe_options_cache = [];
        if (function_exists('init_options')) init_options();
        $cleared = true;
    }
}

$admin_title = 'Cache';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<?php if ($cleared) { ?><div class="alert alert-success">Cache cleared.</div><?php } ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card mb-4 mt-2">
            <div class="card-header bg-white">
                <h5 class="mb-0 d-flex align-items-center"><span class="material-icons me-2 text-warning">cleaning_services</span> System Cache</h5>
            </div>
            <div class="card-body text-center p-5">
                <form method="post">
                    <input type="hidden" name="action" value="clear_cache">
                    <span class="material-icons text-muted mb-3" style="font-size: 4rem; opacity: 0.2;">cached</span>
                    <h5 class="mb-3">Clear temporary files and cache</h5>
                    <p class="text-muted mb-4 px-3">Over time, the application accumulates temporary files, cached settings, and compiled plugin scripts. Clearing the cache can resolve display issues and apply new settings instantly.</p>
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm rounded-pill"><span class="material-icons align-middle me-1">delete_sweep</span> Clear entire cache</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
