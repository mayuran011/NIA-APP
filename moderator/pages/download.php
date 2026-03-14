<?php
if (!defined('in_nia_app')) exit;

$msg = '';
$test_result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    
    if ($action === 'save_download') {
        update_option('download_allowed', isset($_POST['download_allowed']) ? '1' : '0');
        update_option('download_premium_only', isset($_POST['download_premium_only']) ? '1' : '0');
        update_option('download_guests_allowed', isset($_POST['download_guests_allowed']) ? '1' : '0');
        update_option('youtube_download_enabled', isset($_POST['youtube_download_enabled']) ? '1' : '0');
        update_option('yt_dlp_path', isset($_POST['yt_dlp_path']) ? trim($_POST['yt_dlp_path']) : 'yt-dlp');
        update_option('binpath', isset($_POST['binpath']) ? trim($_POST['binpath']) : '');
        update_option('ffmpeg-cmd', isset($_POST['ffmpeg_cmd']) ? trim($_POST['ffmpeg_cmd']) : 'ffmpeg');
        update_option('download_max_res', isset($_POST['download_max_res']) ? trim($_POST['download_max_res']) : '1080');
        update_option('download_audio_format', isset($_POST['download_audio_format']) ? trim($_POST['download_audio_format']) : 'mp3');
        redirect(admin_url('download') . '?msg=saved');
    }
    
    if ($action === 'test_system') {
        $yt_dlp = get_option('yt_dlp_path', 'yt-dlp');
        $ffmpeg = ffmpeg_path();
        $res = [];
        $cmd1 = escapeshellarg($yt_dlp) . ' --version 2>&1';
        exec($cmd1, $o1, $r1);
        $res[] = '<strong>yt-dlp:</strong> ' . ($r1 === 0 ? '<span class="text-success">OK (v' . _e($o1[0] ?? '') . ')</span>' : '<span class="text-danger">Failed</span>');
        $cmd2 = escapeshellarg($ffmpeg) . ' -version 2>&1';
        exec($cmd2, $o2, $r2);
        if ($r2 === 0 && !empty($o2)) {
            $ver = explode("\n", $o2[0])[0];
            $res[] = '<strong>FFmpeg:</strong> <span class="text-success">OK (' . _e(substr($ver, 0, 40)) . '...)</span>';
        } else {
            $res[] = '<strong>FFmpeg:</strong> <span class="text-danger">Failed</span>';
        }
        $test_result = implode('<br>', $res);
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'saved') $msg = 'Configuration updated.';

$admin_title = 'Download';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success d-flex align-items-center shadow-sm border-0 mb-4">
    <span class="material-icons me-2">check_circle</span>
    <div><?php echo _e($msg); ?></div>
</div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="action" value="save_download">

    <div class="row">
        <!-- Content Permissions -->
        <div class="col-lg-12 mb-4">
            <div class="card shadow-sm border-0 position-relative overflow-hidden">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold d-flex align-items-center">
                        <span class="material-icons text-primary me-2">verified_user</span> 
                        Download Permissions & Accessibility
                    </h5>
                </div>
                <div class="card-body bg-light">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card h-100 border shadow-none bg-white">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2 h5">
                                        <input class="form-check-input" type="checkbox" role="switch" name="download_allowed" id="dl_allowed" value="1" <?php echo get_option('download_allowed', '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="dl_allowed">Master Enable</label>
                                    </div>
                                    <p class="text-muted small">Show the download button on the video watch page globally.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border shadow-none bg-white">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2 h5">
                                        <input class="form-check-input" type="checkbox" role="switch" name="download_premium_only" id="dl_premium" value="1" <?php echo get_option('download_premium_only', '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="dl_premium">Premium Only</label>
                                    </div>
                                    <p class="text-muted small">Restrict file extraction and downloads specifically to premium group members.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border shadow-none bg-white">
                                <div class="card-body">
                                    <div class="form-check form-switch mb-2 h5">
                                        <input class="form-check-input" type="checkbox" role="switch" name="download_guests_allowed" id="dl_guests" value="1" <?php echo get_option('download_guests_allowed', '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="dl_guests">Guest Access</label>
                                    </div>
                                    <p class="text-muted small">Allow unauthenticated guests to download local files (if Premium Only is off).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Extraction Configuration (yt-dlp) -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 d-flex align-items-center fw-bold">
                        <span class="material-icons text-danger me-2">smart_display</span> 
                        Tube & Social Engine Setup
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 shadow-none bg-opacity-10 bg-info d-flex align-items-center mb-4">
                        <span class="material-icons me-2">info</span>
                        <span class="small">This system uses <strong>yt-dlp</strong>. It supports YouTube, Instagram, Tiktok, Twitter, and 1000+ more sites.</span>
                    </div>

                    <div class="form-check form-switch fs-5 mb-4 p-3 border rounded bg-light">
                        <input class="form-check-input ms-0" style="margin-right: 15px;" type="checkbox" role="switch" name="youtube_download_enabled" id="yt_dl_enabled" value="1" <?php echo get_option('youtube_download_enabled', '0') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold text-dark" for="yt_dl_enabled">Enable External Video Extraction</label>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">yt-dlp Binary Path</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><span class="material-icons" style="font-size: 18px;">terminal</span></span>
                                <input type="text" class="form-control font-monospace" name="yt_dlp_path" value="<?php echo _e(get_option('yt_dlp_path', 'yt-dlp')); ?>" placeholder="yt-dlp">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Max Resolution</label>
                            <select class="form-select" name="download_max_res">
                                <?php $res = get_option('download_max_res', '1080'); ?>
                                <option value="1080" <?php echo $res == '1080' ? 'selected' : ''; ?>>Full HD (1080p)</option>
                                <option value="720" <?php echo $res == '720' ? 'selected' : ''; ?>>HD (720p)</option>
                                <option value="480" <?php echo $res == '480' ? 'selected' : ''; ?>>SD (480p)</option>
                                <option value="360" <?php echo $res == '360' ? 'selected' : ''; ?>>Low (360p)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Audio Extract Format</label>
                            <select class="form-select" name="download_audio_format">
                                <?php $af = get_option('download_audio_format', 'mp3'); ?>
                                <option value="mp3" <?php echo $af == 'mp3' ? 'selected' : ''; ?>>High Quality MP3 (320k)</option>
                                <option value="m4a" <?php echo $af == 'm4a' ? 'selected' : ''; ?>>native M4A (AAC)</option>
                                <option value="opus" <?php echo $af == 'opus' ? 'selected' : ''; ?>>Opus (Web)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">FFmpeg Command</label>
                            <input type="text" class="form-control font-monospace" name="ffmpeg_cmd" value="<?php echo _e(get_option('ffmpeg-cmd', 'ffmpeg')); ?>" placeholder="ffmpeg">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-secondary">Binary Binpath (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><span class="material-icons" style="font-size: 18px;">folder</span></span>
                                <input type="text" class="form-control font-monospace" name="binpath" value="<?php echo _e(get_option('binpath', '')); ?>" placeholder="e.g. C:\ffmpeg\bin or /usr/bin">
                            </div>
                            <div class="form-text small">Leave blank if binaries are already in system PATH. Use this if you have a custom bin folder.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status & Diagnostics -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4 h-100 bg-white">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h6 class="fw-bold d-flex align-items-center mb-0"><span class="material-icons text-warning me-2">settings_suggest</span> System Diagnostics</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Verify if extraction tools are correctly installed and reachable by the web server.</p>
                    
                    <?php if ($test_result): ?>
                        <div class="bg-dark text-light p-3 rounded mb-4 font-monospace small shadow-sm">
                            <h6 class="text-info border-bottom border-secondary pb-2 mb-2 d-flex align-items-center">
                                <span class="material-icons me-1 fs-6">analytics</span> Execution Results
                            </h6>
                            <?php echo $test_result; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <button type="submit" name="action" value="test_system" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-2 shadow-sm">
                            <span class="material-icons me-2">play_circle</span> Run Requirement Check
                        </button>
                    </div>

                    <div class="p-3 bg-light rounded border border-warning border-opacity-25 mt-auto">
                        <h6 class="fw-bold fs-6 d-flex align-items-center mb-2"><span class="material-icons text-warning me-1 small">help_outline</span> Quick Tip</h6>
                        <ul class="small text-muted ps-3 mb-0">
                            <li>Ensure <strong>FFmpeg</strong> is installed for YouTube MP3 conversion.</li>
                            <li>If on Linux, you may need to <code>chmod +x</code> your binaries.</li>
                            <li>If on Windows, ensure the full path terminates exactly at the .exe if not globally available.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-4 bg-white p-3 rounded shadow-sm border mt-2">
        <button type="submit" name="action" value="save_download" class="btn btn-primary btn-lg d-inline-flex align-items-center px-5 shadow-lg">
            <span class="material-icons me-2">save</span> Update Configuration
        </button>
    </div>
</form>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
