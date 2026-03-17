<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_settings') {
        $keys = [
            'sitename', 'site_description', 'logo_url', 'favicon_url', 'theme_color', 'background_color',
            'facebook_url', 'twitter_url', 'instagram_url', 'youtube_url',
            'choosen-player', 'jwkey', 'player-logo', 'remote-player', 'youtube-player',
            'mediafolder', 'tmp-folder', 'ffmpeg-cmd', 'yt_dlp_path', 'binpath',
            'cache_enabled', 'cache_ttl', 'dark_mode',
            'allow_registration', 'auto_approve_videos', 'require_email_verification', 'max_upload_size',
            'custom_css', 'custom_js', 'analytics_code'
        ];
        foreach ($keys as $k) {
            if ($k === 'dark_mode') continue;
            if (isset($_POST[$k])) {
                update_option($k, is_string($_POST[$k]) ? trim($_POST[$k]) : (string) $_POST[$k]);
            }
        }
        update_option('dark_mode', isset($_POST['dark_mode']) ? '1' : '0');
        update_option('allow_registration', isset($_POST['allow_registration']) ? '1' : '0');
        update_option('auto_approve_videos', isset($_POST['auto_approve_videos']) ? '1' : '0');
        update_option('require_email_verification', isset($_POST['require_email_verification']) ? '1' : '0');
        
        if (function_exists('do_action')) {
            do_action('nia_plugin_settings_save', $_POST);
            do_action('vibe_plugin_settings_save', $_POST);
        }
        redirect(admin_url('settings'));
    }
}

$admin_title = 'System Configuration';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom py-3">
        <ul class="nav nav-pills card-header-pills" id="settingsTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">
                    <span class="material-icons fs-5 me-2">settings</span> General
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-branding" type="button">
                    <span class="material-icons fs-5 me-2">palette</span> Branding
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-players" type="button">
                    <span class="material-icons fs-5 me-2">play_circle</span> Players
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-media" type="button">
                    <span class="material-icons fs-5 me-2">movie</span> Media &amp; Engine
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-social" type="button">
                    <span class="material-icons fs-5 me-2">share</span> Social
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link d-flex align-items-center" data-bs-toggle="tab" data-bs-target="#tab-advanced" type="button">
                    <span class="material-icons fs-5 me-2">code</span> Advanced
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <form method="post" id="settings-form">
            <input type="hidden" name="action" value="save_settings">
            
            <div class="tab-content" id="settingsTabsContent">
                <!-- General -->
                <div class="tab-pane fade show active" id="tab-general">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Site Name</label>
                                <input type="text" class="form-control" name="sitename" value="<?php echo _e(get_option('sitename', 'Nia App')); ?>">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Site Description</label>
                                <textarea class="form-control" name="site_description" rows="3"><?php echo _e(get_option('site_description', '')); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Registration</label>
                                    <div class="form-check form-switch p-0 ps-5 mt-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="allow_registration" value="1" <?php echo get_option('allow_registration', '1') !== '0' ? 'checked' : ''; ?>>
                                        <label class="form-check-label ms-2">Allow New Users</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Approvals</label>
                                    <div class="form-check form-switch p-0 ps-5 mt-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="auto_approve_videos" value="1" <?php echo get_option('auto_approve_videos', '1') !== '0' ? 'checked' : ''; ?>>
                                        <label class="form-check-label ms-2">Auto-Approve Videos</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="alert alert-info border-0 shadow-sm d-flex">
                                <span class="material-icons me-2">info</span>
                                <div class="small">These settings control the primary persona and behavioral core of your platform.</div>
                            </div>
                            <div class="p-3 bg-light border rounded">
                                <h6 class="fw-bold mb-3 small text-uppercase">Interface Defaults</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="dark_mode" value="1" <?php echo get_option('dark_mode', '1') !== '0' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Universal Dark Mode</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cache_enabled" value="1" <?php echo get_option('cache_enabled', '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Object Caching</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branding -->
                <div class="tab-pane fade" id="tab-branding">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Primary Logo</label>
                            <div class="input-group mb-4">
                                <span class="input-group-text bg-light"><span class="material-icons">image</span></span>
                                <input type="text" class="form-control" name="logo_url" value="<?php echo _e(get_option('logo_url', '')); ?>" placeholder="https://...">
                            </div>
                            
                            <label class="form-label fw-bold">Favicon (16x16)</label>
                            <div class="input-group mb-4">
                                <span class="input-group-text bg-light"><span class="material-icons">layers</span></span>
                                <input type="text" class="form-control" name="favicon_url" value="<?php echo _e(get_option('favicon_url', '')); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6 mb-4">
                                    <label class="form-label fw-bold">Theme Color</label>
                                    <input type="color" class="form-control form-control-color w-100" name="theme_color" value="<?php echo _e(get_option('theme_color', '#0f0f12')); ?>">
                                </div>
                                <div class="col-6 mb-4">
                                    <label class="form-label fw-bold">Canvas Background</label>
                                    <input type="color" class="form-control form-control-color w-100" name="background_color" value="<?php echo _e(get_option('background_color', '#0f0f12')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Players -->
                <div class="tab-pane fade" id="tab-players">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Core Media Engine</label>
                                <select class="form-select" name="choosen-player">
                                    <option value="videojs" <?php echo get_option('choosen-player') === 'videojs' ? 'selected' : ''; ?>>Video.js (Modern & Flexible)</option>
                                    <option value="jwplayer" <?php echo get_option('choosen-player') === 'jwplayer' ? 'selected' : ''; ?>>JW Player (Premium Features)</option>
                                    <option value="flowplayer" <?php echo get_option('choosen-player') === 'flowplayer' ? 'selected' : ''; ?>>FlowPlayer</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">JW Player License Key</label>
                                <input type="text" class="form-control" name="jwkey" value="<?php echo _e(get_option('jwkey', '')); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold">In-Player Brand Overlay</label>
                                <input type="text" class="form-control" name="player-logo" value="<?php echo _e(get_option('player-logo', '')); ?>" placeholder="URL to small png">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media -->
                <div class="tab-pane fade" id="tab-media">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Library Path (Rel/Abs)</label>
                                <input type="text" class="form-control" name="mediafolder" value="<?php echo _e(get_option('mediafolder', '')); ?>" placeholder="Default: media/">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">FFmpeg Binary Path</label>
                                <input type="text" class="form-control" name="ffmpeg-cmd" value="<?php echo _e(get_option('ffmpeg-cmd', 'ffmpeg')); ?>">
                                <div class="form-text">Used for thumbnail generation and transcoders.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">yt-dlp Engine Path</label>
                                <input type="text" class="form-control" name="yt_dlp_path" value="<?php echo _e(get_option('yt_dlp_path', 'yt-dlp')); ?>">
                                <div class="form-text text-primary">Required for the Video Downloader and YouTube Import.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Max Package Size (MB)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="max_upload_size" value="<?php echo _e(get_option('max_upload_size', '500')); ?>">
                                    <span class="input-group-text bg-light text-muted">MB</span>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Temporary Cache Folder</label>
                                <input type="text" class="form-control" name="tmp-folder" value="<?php echo _e(get_option('tmp-folder', '')); ?>" placeholder="Default: tmp/">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social -->
                <div class="tab-pane fade" id="tab-social">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col">
                            <label class="form-label small text-uppercase">Facebook</label>
                            <input type="text" class="form-control" name="facebook_url" value="<?php echo _e(get_option('facebook_url', '')); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label small text-uppercase">Twitter / X</label>
                            <input type="text" class="form-control" name="twitter_url" value="<?php echo _e(get_option('twitter_url', '')); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label small text-uppercase">Instagram</label>
                            <input type="text" class="form-control" name="instagram_url" value="<?php echo _e(get_option('instagram_url', '')); ?>">
                        </div>
                        <div class="col">
                            <label class="form-label small text-uppercase">YouTube Official</label>
                            <input type="text" class="form-control" name="youtube_url" value="<?php echo _e(get_option('youtube_url', '')); ?>">
                        </div>
                    </div>
                </div>

                <!-- Advanced -->
                <div class="tab-pane fade" id="tab-advanced">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Analytics &amp; Head Scripts</label>
                        <textarea class="form-control font-monospace small" name="analytics_code" rows="4"><?php echo _e(get_option('analytics_code', '')); ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Global CSS Overrides</label>
                        <textarea class="form-control font-monospace small" name="custom_css" rows="6"><?php echo _e(get_option('custom_css', '')); ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Footer JavaScript</label>
                        <textarea class="form-control font-monospace small" name="custom_js" rows="4"><?php echo _e(get_option('custom_js', '')); ?></textarea>
                    </div>
                </div>
            </div>

            <hr class="my-5">
            
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <?php if (function_exists('do_action')) { do_action('vibe_plugin_settings'); } ?>
                </div>
                <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm d-flex align-items-center">
                    <span class="material-icons me-2">verified</span> Finalize &amp; Save All Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>

