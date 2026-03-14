<?php
if (!defined('in_nia_app')) exit;

$tmp_base = defined('TMP_FOLDER') ? rtrim(TMP_FOLDER, DIRECTORY_SEPARATOR) : (ABSPATH . 'tmp');
$default_log = $tmp_base . DIRECTORY_SEPARATOR . 'error.log';
$log_path = get_option('error_log_path', '');
if ($log_path === '') {
    $log_path = $default_log;
} else {
    $log_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $log_path);
    if (!preg_match('#^[a-zA-Z]:#', $log_path)) {
        $log_path = ABSPATH . ltrim($log_path, DIRECTORY_SEPARATOR);
    }
}

$cleared = false;
$clear_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'clear_log') {
        if (is_file($log_path) && is_writable($log_path)) {
            if (file_put_contents($log_path, '') !== false) {
                redirect(admin_url('errorlog') . '?cleared=1');
            } else {
                $clear_error = 'Could not clear file.';
            }
        } else {
            $clear_error = 'File not found or not writable.';
        }
    }
}

$cleared = isset($_GET['cleared']) && $_GET['cleared'] === '1';

$max_bytes = 512 * 1024; // show last 512 KB
$log_content = '';
$log_size = 0;
$log_readable = false;
if (is_file($log_path)) {
    $log_size = filesize($log_path);
    $log_readable = is_readable($log_path);
    if ($log_readable && $log_size > 0) {
        $fh = fopen($log_path, 'rb');
        if ($fh) {
            if ($log_size > $max_bytes) {
                fseek($fh, -$max_bytes, SEEK_END);
                $log_content = "[… truncated, showing last " . round($max_bytes / 1024) . " KB …]\n\n" . fread($fh, $max_bytes);
            } else {
                $log_content = fread($fh, $log_size);
            }
            fclose($fh);
        }
    }
} elseif ($log_path === $default_log && !is_dir($tmp_base)) {
    $log_content = "(TMP folder not found. Create: " . _e($tmp_base) . ")";
}

$admin_title = 'Error log';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<?php if ($cleared) { ?><div class="alert alert-success">Log cleared.</div><?php } ?>
<?php if ($clear_error !== '') { ?><div class="alert alert-danger"><?php echo _e($clear_error); ?></div><?php } ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom d-flex flex-wrap align-items-center justify-content-between p-3">
        <h5 class="mb-0 d-flex align-items-center fw-bold"><span class="material-icons text-danger me-2">bug_report</span> Application Error Server Log</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center shadow-sm" id="errorlog-copy" title="Copy to clipboard">
                <span class="material-icons me-1" style="font-size: 16px;">content_copy</span> Copy Output
            </button>
            <form method="post" class="d-inline" onsubmit="return confirm('Clear the entire log file permanently?');">
                <input type="hidden" name="action" value="clear_log">
                <button type="submit" class="btn btn-sm btn-danger d-inline-flex align-items-center shadow-sm" <?php echo (!is_file($log_path) || !is_writable($log_path)) ? 'disabled' : ''; ?>>
                    <span class="material-icons me-1" style="font-size: 16px;">delete_sweep</span> Purge Log
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center flex-wrap justify-content-between mb-3 bg-light p-3 rounded border shadow-sm">
            <div class="mb-2 mb-md-0">
                <span class="fw-bold text-secondary small d-block mb-1">Log File Location</span>
                <code class="bg-white border p-1 px-2 rounded text-dark font-monospace shadow-sm"><?php echo _e($log_path); ?></code>
            </div>
            <div class="text-end">
                <?php
                $log_writable = (is_file($log_path) && is_writable($log_path)) || (!file_exists($log_path) && is_dir($tmp_base) && is_writable($tmp_base));
                ?>
                <span class="badge rounded-pill <?php echo $log_writable ? 'bg-success' : 'bg-warning text-dark'; ?> fs-6 mb-1 shadow-sm">
                    <span class="material-icons align-middle me-1" style="font-size: 14px;"><?php echo $log_writable ? 'check_circle' : 'warning'; ?></span>
                    <?php echo $log_writable ? 'Write Access Granted' : 'Read-Only / Protected'; ?>
                </span>
                <?php if (is_file($log_path)) { ?>
                    <div class="text-muted small fw-bold">Size: <?php echo number_format($log_size); ?> bytes</div>
                <?php } ?>
            </div>
        </div>
        
        <p class="small text-muted mb-3 d-flex align-items-center border-start border-4 border-info ps-3 py-1 bg-light rounded-end">
            <span class="material-icons text-info me-2" style="font-size: 18px;">info</span>
            This file securely records backend warnings, notices, and critical exceptions triggered by the core PHP framework.
        </p>

        <div class="bg-dark rounded shadow-sm p-1">
            <div class="bg-dark text-white-50 px-3 py-2 border-bottom border-secondary border-opacity-25 d-flex align-items-center small font-monospace">
                <span class="material-icons me-2 text-warning lh-1" style="font-size: 14px;">terminal</span> log viewer terminal
            </div>
            <textarea class="form-control border-0 font-monospace small" id="errorlog-content" rows="22" readonly style="font-size:0.85rem; background: var(--bs-dark); color: #00ff00; resize: vertical; box-shadow: inset 0 2px 10px rgba(0,0,0,0.5);"><?php echo _e($log_content ?: 'SYSTEM NORMAL: (empty or not readable)'); ?></textarea>
        </div>
    </div>
</div>

<script>
(function() {
    var ta = document.getElementById('errorlog-content');
    var btn = document.getElementById('errorlog-copy');
    if (!ta || !btn) return;
    btn.addEventListener('click', function() {
        ta.select();
        ta.setSelectionRange(0, ta.value.length);
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(ta.value).then(function() {
                    btn.querySelector('.material-icons').textContent = 'done';
                    setTimeout(function() { btn.querySelector('.material-icons').textContent = 'content_copy'; }, 2000);
                });
            } else {
                document.execCommand('copy');
                btn.querySelector('.material-icons').textContent = 'done';
                setTimeout(function() { btn.querySelector('.material-icons').textContent = 'content_copy'; }, 2000);
            }
        } catch (e) {}
    });
})();
</script>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
