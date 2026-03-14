<?php
if (!defined('in_nia_app')) exit;

$admin_title = 'System Health';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';

global $db;
$pre = $db->prefix();

// Database check
$db_ok = false;
$db_error = '';
try {
    $db->query("SELECT 1");
    $db_ok = true;
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

$db_tables = [];
if ($db_ok) {
    $tbl = $db->fetchAll("SHOW TABLES");
    if (is_array($tbl) && !empty($tbl)) {
        foreach ($tbl as $r) {
            $row = (array) $r;
            $db_tables[] = reset($row);
        }
    }
}

// Media Engine Diagnostics
function _health_test_bin($cmd) {
    if (!$cmd) return ['ok' => false, 'out' => 'No command defined'];
    $out = [];
    $ret = -1;
    @exec($cmd . ' --version 2>&1', $out, $ret);
    if ($ret === 0 && !empty($out)) return ['ok' => true, 'out' => $out[0]];
    // Try simple version check
    @exec($cmd . ' -version 2>&1', $out, $ret);
    if ($ret === 0 && !empty($out)) return ['ok' => true, 'out' => $out[0]];
    return ['ok' => false, 'out' => 'Command not found or execution failed.'];
}

$yt_bin = get_option('yt_dlp_path', 'yt-dlp');
$ff_bin = get_option('ffmpeg-cmd', 'ffmpeg');
$check_yt = _health_test_bin($yt_bin);
$check_ff = _health_test_bin($ff_bin);

$tmp_base = defined('TMP_FOLDER') ? rtrim(TMP_FOLDER, DIRECTORY_SEPARATOR) : (ABSPATH . 'tmp');
$tmp_writable = is_dir($tmp_base) && is_writable($tmp_base);
$media_base = defined('MEDIA_FOLDER') ? rtrim(MEDIA_FOLDER, DIRECTORY_SEPARATOR) : (ABSPATH . 'media');
$media_writable = is_dir($media_base) && is_writable($media_base);

function _health_disk($path) {
    if (!is_dir($path)) return ['free' => null, 'total' => null, 'ok' => false];
    if (!function_exists('disk_free_space')) return ['free' => null, 'total' => null, 'ok' => true];
    $free = @disk_free_space($path);
    $total = @disk_total_space($path);
    return ['free' => $free, 'total' => $total, 'ok' => true];
}
$disk_tmp = _health_disk($tmp_base);
$disk_root = _health_disk(ABSPATH);

$php_ver = PHP_VERSION;
$memory_limit = ini_get('memory_limit');
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$max_execution = ini_get('max_execution_time');
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
?>

<div class="row g-4 mb-4">
    <!-- PHP CARD -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <span class="material-icons fs-5">settings_suggest</span>
                </div>
                <h6 class="mb-0 fw-bold">PHP Config</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-bottom-0 pb-1">
                        <span class="text-muted">Version</span>
                        <span class="fw-bold"><?php echo _e($php_ver); ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-bottom-0 pb-1">
                        <span class="text-muted">Memory</span>
                        <span class="fw-bold"><?php echo _e($memory_limit); ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-bottom-0 pb-1">
                        <span class="text-muted">Max Upload</span>
                        <span class="fw-bold"><?php echo _e($upload_max); ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-bottom-0 pb-0">
                        <span class="text-muted">Execution</span>
                        <span class="fw-bold"><?php echo _e($max_execution); ?>s</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- DATABASE CARD -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <span class="material-icons fs-5">storage</span>
                </div>
                <h6 class="mb-0 fw-bold">Database</h6>
            </div>
            <div class="card-body py-2">
                <div class="text-center py-2">
                   <?php if ($db_ok): ?>
                        <span class="material-icons text-success mb-2" style="font-size: 2.5rem;">check_circle</span>
                        <h6 class="fw-bold mb-1">Healthy</h6>
                        <span class="small text-muted"><?php echo count($db_tables); ?> Tables Index</span>
                   <?php else: ?>
                        <span class="material-icons text-danger mb-2" style="font-size: 2.5rem;">error</span>
                        <h6 class="fw-bold mb-1">Connection Error</h6>
                   <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- YT-DLP CARD -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                <div class="<?php echo $check_yt['ok'] ? 'bg-info bg-opacity-10 text-info' : 'bg-danger bg-opacity-10 text-danger'; ?> rounded-circle p-2 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <span class="material-icons fs-5">downloading</span>
                </div>
                <h6 class="mb-0 fw-bold">yt-dlp Engine</h6>
            </div>
            <div class="card-body py-2">
                <div class="text-center py-2">
                   <?php if ($check_yt['ok']): ?>
                        <span class="material-icons text-info mb-2" style="font-size: 2.5rem;">verified</span>
                        <h6 class="fw-bold mb-0">Installed</h6>
                        <div class="small text-muted text-truncate px-2" title="<?php echo _e($check_yt['out']); ?>"><?php echo _e(explode(' ', $check_yt['out'])[0] ?? 'Ready'); ?></div>
                   <?php else: ?>
                        <span class="material-icons text-muted mb-2" style="font-size: 2.5rem;">block</span>
                        <h6 class="fw-bold mb-1">Not Found</h6>
                        <span class="small text-danger">Check path in settings</span>
                   <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- FFMPEG CARD -->
    <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                <div class="<?php echo $check_ff['ok'] ? 'bg-warning bg-opacity-10 text-warning' : 'bg-danger bg-opacity-10 text-danger'; ?> rounded-circle p-2 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <span class="material-icons fs-5">movie</span>
                </div>
                <h6 class="mb-0 fw-bold">FFmpeg Engine</h6>
            </div>
            <div class="card-body py-2">
                <div class="text-center py-2">
                   <?php if ($check_ff['ok']): ?>
                        <span class="material-icons text-warning mb-2" style="font-size: 2.5rem;">bolt</span>
                        <h6 class="fw-bold mb-0">Operational</h6>
                        <div class="small text-muted text-truncate px-2" title="<?php echo _e($check_ff['out']); ?>"><?php echo _e(explode(' ', $check_ff['out'])[0] ?? 'Ready'); ?></div>
                   <?php else: ?>
                        <span class="material-icons text-muted mb-2" style="font-size: 2.5rem;">error_outline</span>
                        <h6 class="fw-bold mb-1">Missing</h6>
                        <span class="small text-danger">Manual install needed</span>
                   <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">Directory Permissions & Paths</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border rounded">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 px-4 py-3">Path Name</th>
                                <th class="border-0 py-3">Physical Location</th>
                                <th class="border-0 text-center py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 fw-bold">Temporary Folder</td>
                                <td><code class="small text-secondary"><?php echo _e($tmp_base); ?></code></td>
                                <td class="text-center"><?php echo $tmp_writable ? '<span class="badge bg-success">Writable</span>' : '<span class="badge bg-danger">Locked</span>'; ?></td>
                            </tr>
                            <tr>
                                <td class="px-4 fw-bold">Media Repository</td>
                                <td><code class="small text-secondary"><?php echo _e($media_base); ?></code></td>
                                <td class="text-center"><?php echo $media_writable ? '<span class="badge bg-success">Writable</span>' : '<span class="badge bg-danger">Locked</span>'; ?></td>
                            </tr>
                            <tr>
                                <td class="px-4 fw-bold">Web Server Root</td>
                                <td><code class="small text-secondary"><?php echo _e(ABSPATH); ?></code></td>
                                <td class="text-center"><span class="badge bg-info">System</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($db_ok && !empty($db_tables)): ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold">Registered Database Tables (<?php echo count($db_tables); ?>)</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($db_tables as $t): ?>
                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.75rem;"><span class="text-primary me-1">#</span><?php echo _e($t); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 bg-dark text-white mb-4">
            <div class="card-header bg-transparent border-bottom border-secondary d-flex align-items-center">
                <span class="material-icons me-2 text-info">info</span>
                <h6 class="mb-0 fw-bold">Server Environment</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="small text-muted text-uppercase fw-bold d-block mb-1">Software</label>
                    <div class="fw-bold"><?php echo _e($server_software); ?></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted text-uppercase fw-bold d-block mb-1">Memory Usage (Peak)</label>
                    <div class="fw-bold"><?php echo round(memory_get_peak_usage() / 1024 / 1024, 2); ?> MB</div>
                </div>
                <div class="mb-0">
                    <label class="small text-muted text-uppercase fw-bold d-block mb-1">Disk Health (Free)</label>
                    <?php
                    $fmt = function ($b) {
                        if ($b === null) return '—';
                        $u = ['B','KB','MB','GB','TB']; $i = 0; while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
                        return round($b, 2) . ' ' . $u[$i];
                    };
                    ?>
                    <div class="small">Root Partition: <span class="fw-bold text-success"><?php echo $fmt($disk_root['free']); ?></span></div>
                    <div class="small">Temp Partition: <span class="fw-bold text-info"><?php echo $fmt($disk_tmp['free']); ?></span></div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning border-0 shadow-sm d-flex small mb-0">
            <span class="material-icons me-2">lightbulb</span>
            <div>If <strong class="text-dark">FFmpeg</strong> or <strong class="text-dark">yt-dlp</strong> are missing, video processing and downloads will not work. Update paths in the Download Settings page.</div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php'; // Included twice logic from original? No, footer.php is below. ?>
<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
