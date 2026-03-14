<?php
if (!defined('in_nia_app')) exit;

global $db;
$pre = $db->prefix();
$table_sources = $pre . 'youtube_import_sources';

// Init keys
$keys_opt = get_option('youtube_api_keys', '');
$keys = [];
if ($keys_opt !== '') {
    $keys = json_decode($keys_opt, true);
    if (!is_array($keys)) $keys = [];
} else {
    $old_key = get_option('youtube_api_key', '');
    if ($old_key) {
        $keys[] = ['key' => $old_key, 'active' => 1, 'quota_exceeded' => 0, 'errors' => 0];
        update_option('youtube_api_keys', json_encode($keys));
    }
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'save_youtube') {
        $new_keys = [];
        $raw_keys = $_POST['api_keys'] ?? [];
        foreach ($raw_keys as $k) {
            $key_str = trim($k['val'] ?? '');
            if (!$key_str) continue;
            $new_keys[] = [
                'key' => $key_str,
                'active' => isset($k['active']) ? 1 : 0,
                'quota_exceeded' => isset($k['quota_exceeded']) ? 1 : 0,
                'errors' => (int)($k['errors'] ?? 0)
            ];
        }
        update_option('youtube_api_keys', json_encode($new_keys));
        update_option('youtube_importer_enabled', isset($_POST['youtube_importer_enabled']) ? '1' : '0');
        
        if (!empty($new_keys)) {
            update_option('youtube_api_key', $new_keys[0]['key']);
        } else {
            update_option('youtube_api_key', '');
        }
        redirect(admin_url('youtube') . '?msg=saved');
    }
    
    if ($action === 'check_keys') {
        foreach ($keys as &$k) {
            if (empty($k['key'])) continue;
            $url = 'https://www.googleapis.com/youtube/v3/videos?part=id&chart=mostPopular&maxResults=1&key=' . $k['key'];
            $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 10]]);
            $json = @file_get_contents($url, false, $ctx);
            $data = $json ? json_decode($json, true) : null;
            
            if ($data && !empty($data['error'])) {
                $code = $data['error']['code'] ?? 0;
                if ($code === 403) {
                    $k['quota_exceeded'] = 1;
                    $k['errors'] = ($k['errors'] ?? 0) + 1;
                } else {
                    $k['quota_exceeded'] = 0;
                }
            } elseif ($data && isset($data['items'])) {
                $k['quota_exceeded'] = 0;
                $k['errors'] = 0;
            } else {
                $k['errors'] = ($k['errors'] ?? 0);
            }
        }
        update_option('youtube_api_keys', json_encode($keys));
        redirect(admin_url('youtube') . '?msg=checked');
    }

    if ($action === 'add_source') {
        $type = $_POST['source_type'] ?? 'channel';
        $val = trim($_POST['source_val'] ?? '');
        $auto = isset($_POST['auto_import']) ? 1 : 0;
        if ($val) {
            $db->query("INSERT INTO {$table_sources} (user_id, type, value, auto_import) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE auto_import = VALUES(auto_import)", [function_exists('current_user_id') ? current_user_id() : 0, $type, $val, $auto]);
            redirect(admin_url('youtube') . '?msg=added');
        }
    }

    if ($action === 'delete_source') {
        $sid = (int)($_POST['source_id'] ?? 0);
        if ($sid > 0) {
            if (function_exists('nia_youtube_admin_remove_import_source')) {
                nia_youtube_admin_remove_import_source($sid);
            } else {
                $db->query("DELETE FROM {$table_sources} WHERE id = ?", [$sid]);
            }
            redirect(admin_url('youtube') . '?msg=deleted');
        }
    }

    if ($action === 'toggle_source') {
        $sid = (int)($_POST['source_id'] ?? 0);
        $state = (int)($_POST['state'] ?? 0);
        if ($sid > 0) {
            $db->query("UPDATE {$table_sources} SET auto_import = ? WHERE id = ?", [$state, $sid]);
            redirect(admin_url('youtube') . '?msg=updated');
        }
    }

    if ($action === 'run_import') {
        $sid = (int)($_POST['source_id'] ?? 0);
        if ($sid > 0) {
            $count = nia_youtube_process_source($sid, 50);
            redirect(admin_url('youtube') . '&msg=imported&count=' . $count);
        }
    }

    if ($action === 'run_import_all') {
        $all = $db->fetchAll("SELECT id FROM {$table_sources}");
        $total = 0;
        if (!empty($all)) {
            foreach ($all as $s) {
                $total += nia_youtube_process_source($s->id, 20);
            }
        }
        redirect(admin_url('youtube') . '&msg=imported&count=' . $total);
    }

    if ($action === 'save_yt_meta_display') {
        update_option('yt_meta_enabled', isset($_POST['yt_meta_enabled']) ? '1' : '0');
        update_option('yt_meta_channel_enabled', isset($_POST['yt_meta_channel_enabled']) ? '1' : '0');
        update_option('yt_meta_upload_enabled', isset($_POST['yt_meta_upload_enabled']) ? '1' : '0');
        update_option('yt_meta_label_channel', trim((string) ($_POST['yt_meta_label_channel'] ?? 'Channel')));
        update_option('yt_meta_label_upload', trim((string) ($_POST['yt_meta_label_upload'] ?? 'Upload')));
        $fs = trim((string) ($_POST['yt_meta_font_size'] ?? '0.5'));
        if ($fs !== '' && is_numeric($fs)) update_option('yt_meta_font_size', $fs);
        update_option('yt_meta_color', trim((string) ($_POST['yt_meta_color'] ?? '')));
        update_option('yt_meta_style', in_array($_POST['yt_meta_style'] ?? '', ['normal', 'uppercase', 'italic'], true) ? $_POST['yt_meta_style'] : 'uppercase');
        update_option('yt_meta_show_watch', isset($_POST['yt_meta_show_watch']) ? '1' : '0');
        update_option('yt_meta_show_cards', isset($_POST['yt_meta_show_cards']) ? '1' : '0');
        update_option('yt_meta_show_home', isset($_POST['yt_meta_show_home']) ? '1' : '0');
        redirect(admin_url('youtube') . '?msg=meta_saved');
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'saved') $msg = 'Configuration saved successfully.';
    if ($_GET['msg'] === 'checked') $msg = 'All API keys have been verified.';
    if ($_GET['msg'] === 'added') $msg = 'New import source added.';
    if ($_GET['msg'] === 'deleted') $msg = 'Import source removed.';
    if ($_GET['msg'] === 'updated') $msg = 'Automation status updated.';
    if ($_GET['msg'] === 'imported') $msg = 'Manual import completed. Found ' . (int)($_GET['count'] ?? 0) . ' new videos.';
    if ($_GET['msg'] === 'meta_saved') $msg = 'YouTube meta display settings saved.';
}

$admin_title = 'YouTube';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';

$total_keys = count($keys);
$active_keys = array_reduce($keys, function($carry, $item) { return $carry + (!empty($item['active']) ? 1 : 0); }, 0);
$quota_exceeded = array_reduce($keys, function($carry, $item) { return $carry + (!empty($item['quota_exceeded']) ? 1 : 0); }, 0);
$errors = array_reduce($keys, function($carry, $item) { return $carry + ((int)($item['errors'] ?? 0)); }, 0);

// Get sources (use helper for all sources if available, else keep existing query)
$sources = function_exists('nia_youtube_get_all_import_sources') ? nia_youtube_get_all_import_sources() : $db->fetchAll("SELECT s.*, u.name as user_name FROM {$table_sources} s LEFT JOIN " . $pre . "users u ON s.user_id = u.id ORDER BY s.id DESC");
if (function_exists('nia_youtube_get_all_import_sources') && !empty($sources)) {
    $user_ids = array_unique(array_filter(array_map(function($s) { return isset($s->user_id) ? (int)$s->user_id : 0; }, $sources)));
    $users = [];
    foreach ($user_ids as $uid) {
        $row = $db->fetch("SELECT name FROM " . $pre . "users WHERE id = ?", [$uid]);
        $users[$uid] = $row ? (is_array($row) ? ($row['name'] ?? '') : ($row->name ?? '')) : '';
    }
    foreach ($sources as $s) {
        $s->user_name = $users[(int)($s->user_id ?? 0)] ?? '';
    }
}
$show_log_id = isset($_GET['log']) ? (int) $_GET['log'] : 0;
$log_entries = [];
$log_source = null;
if ($show_log_id > 0 && function_exists('nia_youtube_get_source') && function_exists('nia_youtube_get_import_log')) {
    $log_source = nia_youtube_get_source($show_log_id);
    if ($log_source) {
        $log_entries = nia_youtube_get_import_log($show_log_id, 200);
        $uid = (int)($log_source->user_id ?? 0);
        $u = $uid ? $db->fetch("SELECT name FROM " . $pre . "users WHERE id = ?", [$uid]) : null;
        $log_source->user_name = $u ? (is_array($u) ? ($u['name'] ?? '') : ($u->name ?? '')) : '—';
    }
}
?>

<?php if ($msg): ?>
<div class="alert alert-success d-flex align-items-center shadow-sm">
    <span class="material-icons me-2">check_circle</span>
    <span><?php echo _e($msg); ?></span>
</div>
<?php endif; ?>

<?php if ($show_log_id > 0 && $log_source): ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons me-2">list</span> Import log: <?php echo _e(!empty($log_source->channel_name) ? $log_source->channel_name : ($log_source->value ?? 'Source #' . $show_log_id)); ?></h5>
        <a href="<?php echo admin_url('youtube'); ?>" class="btn btn-sm btn-outline-secondary">Back to YouTube</a>
    </div>
    <div class="card-body p-0">
        <p class="px-4 pt-3 small text-muted">Videos imported from this channel (newest first). Owner: <?php echo _e($log_source->user_name ?? '—'); ?></p>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th class="border-0 ps-4">Video</th><th class="border-0">Imported</th></tr></thead>
                <tbody>
                    <?php if (empty($log_entries)): ?>
                        <tr><td colspan="2" class="text-center py-4 text-muted">No import log yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($log_entries as $log): $log = is_array($log) ? (object) $log : $log; ?>
                        <tr>
                            <td class="ps-4"><a href="<?php echo function_exists('watch_url') ? watch_url($log->video_id) : (rtrim(ABSPATH, '/') . '/watch/' . $log->video_id); ?>" class="text-decoration-none"><?php echo _e($log->video_title ?? 'Video #' . ($log->video_id ?? '')); ?></a></td>
                            <td class="text-muted small"><?php echo !empty($log->created_at) ? date('M j, Y H:i', strtotime($log->created_at)) : '—'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- API KEY MANAGEMENT -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 d-flex align-items-center fw-bold"><span class="material-icons text-danger me-2">vpn_key</span> API Key Rotation Pool</h5>
                <form method="post" class="m-0">
                    <input type="hidden" name="action" value="check_keys">
                    <button type="submit" class="btn btn-sm btn-outline-info d-inline-flex align-items-center shadow-sm">
                        <span class="material-icons me-1" style="font-size: 16px;">sync</span> Verify & Check
                    </button>
                </form>
            </div>
            <div class="card-body bg-light">
                <div class="row text-center mb-4 g-2">
                    <div class="col-md-3">
                        <div class="bg-white p-3 rounded shadow-sm border h-100">
                            <h3 class="mb-0 text-dark fw-bolder"><?php echo $total_keys; ?></h3>
                            <span class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total Keys</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-white p-3 rounded shadow-sm border h-100 border-primary">
                            <h3 class="mb-0 text-primary fw-bolder"><?php echo $active_keys; ?></h3>
                            <span class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Active</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-white p-3 rounded shadow-sm border h-100 border-warning">
                            <h3 class="mb-0 text-warning fw-bolder"><?php echo $quota_exceeded; ?></h3>
                            <span class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Quota Met</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-white p-3 rounded shadow-sm border h-100 border-danger">
                            <h3 class="mb-0 text-danger fw-bolder"><?php echo $errors; ?></h3>
                            <span class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Errors</span>
                        </div>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="action" value="save_youtube">
                    
                    <div class="alert bg-white border d-flex align-items-center mb-4 shadow-sm">
                        <div class="form-check form-switch fs-5 mb-0">
                            <input class="form-check-input mt-1" type="checkbox" role="switch" name="youtube_importer_enabled" id="yt_imp" value="1" <?php echo get_option('youtube_importer_enabled', '0') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label ms-2 text-dark fw-bold" style="font-size: 1rem;" for="yt_imp">Enable Global YouTube Automation</label>
                        </div>
                    </div>

                    <div id="api-keys-container" class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($keys as $i => $k): ?>
                            <div class="card border-secondary border-opacity-25 shadow-sm api-key-row bg-white">
                                <div class="card-body p-3 d-flex align-items-center gap-3">
                                    <div class="form-check form-switch fs-5 mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" name="api_keys[<?php echo $i; ?>][active]" value="1" <?php echo !empty($k['active']) ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><span class="material-icons text-muted" style="font-size: 16px;">key</span></span>
                                            <input type="text" class="form-control font-monospace" name="api_keys[<?php echo $i; ?>][val]" value="<?php echo _e($k['key']); ?>" placeholder="API Key">
                                        </div>
                                    </div>
                                    <div style="min-width: 120px;">
                                        <?php if (!empty($k['quota_exceeded'])): ?>
                                            <span class="badge bg-danger w-100 py-2">Quota Met</span>
                                        <?php else: ?>
                                            <span class="badge bg-success w-100 py-2">Fine</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="api_keys[<?php echo $i; ?>][quota_exceeded]" value="<?php echo (int)(!empty($k['quota_exceeded'])); ?>">
                                    <input type="hidden" name="api_keys[<?php echo $i; ?>][errors]" value="<?php echo (int)($k['errors'] ?? 0); ?>">
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded remove-api-key"><span class="material-icons" style="font-size: 18px;">close</span></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3" id="add-api-key-btn"><span class="material-icons me-1 fs-6">add</span> Add Key</button>
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center px-4 py-2 rounded-pill shadow-sm"><span class="material-icons me-1">save</span> Save Configs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- YOUTUBE VIDEO META DISPLAY (Channel / Upload under title) -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons me-2">label</span> YouTube video meta (Channel / Upload under title)</h5>
            </div>
            <div class="card-body bg-light">
                <form method="post">
                    <input type="hidden" name="action" value="save_yt_meta_display">
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="yt_meta_enabled" id="yt_meta_enabled" value="1" <?php echo get_option('yt_meta_enabled', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="yt_meta_enabled">Enable YouTube meta line under video title (Channel &amp; Upload)</label>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="yt_meta_channel_enabled" id="yt_meta_channel" value="1" <?php echo get_option('yt_meta_channel_enabled', '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="yt_meta_channel">Show Channel</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="yt_meta_upload_enabled" id="yt_meta_upload" value="1" <?php echo get_option('yt_meta_upload_enabled', '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="yt_meta_upload">Show Upload</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Label for channel</label>
                            <input type="text" class="form-control form-control-sm" name="yt_meta_label_channel" value="<?php echo _e(get_option('yt_meta_label_channel', 'Channel')); ?>" placeholder="Channel">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Label for upload</label>
                            <input type="text" class="form-control form-control-sm" name="yt_meta_label_upload" value="<?php echo _e(get_option('yt_meta_label_upload', 'Upload')); ?>" placeholder="Upload">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Text size (rem)</label>
                            <select class="form-select form-select-sm" name="yt_meta_font_size">
                                <?php $fs = get_option('yt_meta_font_size', '0.5'); foreach (['0.35' => '0.35 (smallest)', '0.4' => '0.4', '0.45' => '0.45', '0.5' => '0.5 (default)', '0.55' => '0.55', '0.6' => '0.6'] as $v => $l) { ?>
                                <option value="<?php echo $v; ?>" <?php echo $fs === $v ? 'selected' : ''; ?>><?php echo _e($l); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Color (hex, leave empty for theme muted)</label>
                            <input type="text" class="form-control form-control-sm" name="yt_meta_color" value="<?php echo _e(get_option('yt_meta_color', '')); ?>" placeholder="#888 or empty">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Text style</label>
                            <select class="form-select form-select-sm" name="yt_meta_style">
                                <?php $sty = get_option('yt_meta_style', 'uppercase'); foreach (['normal' => 'Normal', 'uppercase' => 'Uppercase', 'italic' => 'Italic'] as $v => $l) { ?>
                                <option value="<?php echo $v; ?>" <?php echo $sty === $v ? 'selected' : ''; ?>><?php echo _e($l); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2 small fw-bold">Show this meta on:</div>
                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="yt_meta_show_watch" id="yt_meta_show_watch" value="1" <?php echo get_option('yt_meta_show_watch', '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="yt_meta_show_watch">Watch page</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="yt_meta_show_cards" id="yt_meta_show_cards" value="1" <?php echo get_option('yt_meta_show_cards', '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="yt_meta_show_cards">Video grid / cards (Videos, category, search, profile, playlist, following)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="yt_meta_show_home" id="yt_meta_show_home" value="1" <?php echo get_option('yt_meta_show_home', '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="yt_meta_show_home">Home page</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm px-4"><span class="material-icons me-1" style="font-size:18px;">save</span> Save meta display</button>
                </form>
            </div>
        </div>
    </div>

    <!-- AUTOMATION SOURCES -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-primary me-2">auto_fix_high</span> Automation & Import Sources</h5>
                <div class="d-flex gap-2">
                    <form method="post" class="m-0">
                        <input type="hidden" name="action" value="run_import_all">
                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none">
                            <span class="material-icons me-1 v-middle" style="font-size: 16px;">sync</span> Sync All
                        </button>
                    </form>
                    <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-none" data-bs-toggle="modal" data-bs-target="#addSourceModal">
                        <span class="material-icons me-1 v-middle" style="font-size: 16px;">add_circle</span> Add New Source
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 ps-4">Type</th>
                                <th class="border-0">Channel / Source</th>
                                <th class="border-0">Owner</th>
                                <th class="border-0 text-center">Total</th>
                                <th class="border-0">Last Import</th>
                                <th class="border-0">Auto</th>
                                <th class="border-0 text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sources)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">No automation sources found. Start by adding a channel ID.</td></tr>
                            <?php else: ?>
                                <?php foreach ($sources as $s): 
                                    $chan_label = !empty($s->channel_name) ? $s->channel_name : ($s->value ?? '');
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-opacity-10 <?php echo ($s->type ?? '') == 'channel' ? 'bg-info text-info' : 'bg-warning text-warning'; ?> border">
                                                <?php echo ucfirst($s->type ?? ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-medium text-dark"><?php echo _e($chan_label); ?></span>
                                            <?php if (!empty($s->channel_name) && $s->value !== $s->channel_name): ?><br><code class="small text-muted"><?php echo _e($s->value); ?></code><?php endif; ?>
                                        </td>
                                        <td><span class="small text-muted"><?php echo _e($s->user_name ?? '—'); ?></span></td>
                                        <td class="text-center"><span class="badge bg-secondary"><?php echo (int)($s->total_imported ?? 0); ?></span></td>
                                        <td class="small text-muted"><?php echo !empty($s->last_imported_at) ? date('M j, H:i', strtotime($s->last_imported_at)) : 'Never'; ?></td>
                                        <td>
                                            <form method="post" class="m-0">
                                                <input type="hidden" name="action" value="toggle_source">
                                                <input type="hidden" name="source_id" value="<?php echo $s->id; ?>">
                                                <input type="hidden" name="state" value="<?php echo !empty($s->auto_import) ? '0' : '1'; ?>">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input cursor-pointer" type="checkbox" onchange="this.form.submit()" <?php echo !empty($s->auto_import) ? 'checked' : ''; ?>>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?php echo admin_url('youtube') . '?log=' . (int)$s->id; ?>" class="btn btn-link text-info p-0 me-2" title="View log"><span class="material-icons" style="font-size: 20px;">list</span></a>
                                            <form method="post" class="m-0 d-inline">
                                                <input type="hidden" name="action" value="run_import">
                                                <input type="hidden" name="source_id" value="<?php echo $s->id; ?>">
                                                <button type="submit" class="btn btn-link text-primary p-0 me-2" title="Manual Sync"><span class="material-icons" style="font-size: 20px;">sync</span></button>
                                            </form>
                                            <form method="post" class="m-0 d-inline" onsubmit="return confirm('Remove this source?')">
                                                <input type="hidden" name="action" value="delete_source">
                                                <input type="hidden" name="source_id" value="<?php echo $s->id; ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0"><span class="material-icons" style="font-size: 20px;">delete_outline</span></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 bg-dark text-white">
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold d-flex align-items-center mb-0"><span class="material-icons text-info me-2">terminal</span> Cron Command</h6>
            </div>
            <div class="card-body">
                <p class="text-light small mb-3 opacity-75">Add this to your server cron tab to execute automation periodically.</p>
                <div class="bg-black p-3 rounded mb-3 border border-secondary shadow-sm">
                    <code class="text-success small d-block mb-2">Every 12 Hours:</code>
                    <code class="text-info d-block font-monospace user-select-all small" style="word-break: break-all;">php <?php echo _e(ABSPATH . 'app/cron/youtube-auto-import.php'); ?></code>
                </div>
                <div class="alert alert-info border-0 py-2 small bg-opacity-10 text-info">
                   <span class="material-icons align-middle me-1" style="font-size: 16px;">info</span>
                   The system will automatically rotate through active API keys if one hits a quota limit during the cron run.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addSourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="card border-0 shadow-lg">
            <form method="post">
                <input type="hidden" name="action" value="add_source">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Add Automation Source</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Source Type</label>
                        <select name="source_type" class="form-select">
                            <option value="channel">YouTube Channel ID</option>
                            <option value="playlist">YouTube Playlist ID</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ID / Value</label>
                        <input type="text" name="source_val" class="form-control" placeholder="e.g. UC_x5XG1OV2P6uZZ5FSM9Ttw" required>
                        <div class="form-text small">Provide the unique identifier (last part of the URL).</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="auto_import" value="1" id="autoImportNew" checked>
                        <label class="form-check-label small fw-bold ms-1" for="autoImportNew">Enable Auto-Import for this source</label>
                    </div>
                </div>
                <div class="card-footer bg-light text-end py-3">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Start Automation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="api-key-tpl">
    <div class="card border-primary border-opacity-50 shadow-sm api-key-row bg-white">
        <div class="card-body p-3 d-flex align-items-center gap-3">
            <div class="form-check form-switch fs-5 mb-0">
                <input class="form-check-input" type="checkbox" role="switch" name="api_keys[{{INDEX}}][active]" value="1" checked>
            </div>
            <div class="flex-grow-1">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><span class="material-icons" style="font-size: 16px;">key</span></span>
                    <input type="text" class="form-control font-monospace" name="api_keys[{{INDEX}}][val]" value="" placeholder="New Key" required>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2" style="min-width: 120px;">
                <span class="badge bg-secondary w-100 py-2">Untested</span>
            </div>
            <input type="hidden" name="api_keys[{{INDEX}}][quota_exceeded]" value="0">
            <input type="hidden" name="api_keys[{{INDEX}}][errors]" value="0">
            <button type="button" class="btn btn-outline-danger btn-sm rounded remove-api-key"><span class="material-icons" style="font-size: 18px;">close</span></button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('api-keys-container');
    const tpl = document.getElementById('api-key-tpl').innerHTML;
    const addBtn = document.getElementById('add-api-key-btn');

    let keyIndex = document.querySelectorAll('.api-key-row').length;

    addBtn.addEventListener('click', function() {
        const html = tpl.replace(/\{\{INDEX\}\}/g, keyIndex++);
        const div = document.createElement('div');
        div.innerHTML = html;
        container.appendChild(div.firstElementChild);
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-api-key')) {
            e.target.closest('.api-key-row').remove();
        }
    });
});
</script>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
