<?php
/**
 * Admin: Unavailable / Broken videos
 * Lists videos with remote_url (YouTube, etc.) so admins can check and remove
 * "Video unavailable" / "not made available in your country" / broken links.
 */
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

// AJAX: list all remote video IDs
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
if ($is_ajax && isset($_GET['list_remote_ids'])) {
    header('Content-Type: application/json; charset=utf-8');
    $where = "(remote_url IS NOT NULL AND remote_url != '')";
    $rows = $db->fetchAll("SELECT id FROM {$pre}videos WHERE " . $where . " ORDER BY id ASC", []);
    $rows = is_array($rows) ? $rows : [];
    $ids = [];
    foreach ($rows as $r) {
        $ids[] = (int) (isset($r->id) ? $r->id : 0);
    }
    $ids = array_filter($ids);
    echo json_encode(['ids' => array_values($ids)]);
    exit;
}

// AJAX: check availability for multiple videos (batch, max 25 per request to limit DB connections on shared hosts)
if ($is_ajax && isset($_GET['check_availability_batch']) && !empty($_GET['ids']) && is_array($_GET['ids'])) {
    header('Content-Type: application/json; charset=utf-8');
    $ids = array_slice(array_filter(array_map('intval', $_GET['ids'])), 0, 25);
    $results = [];
    foreach ($ids as $check_id) {
        if ($check_id <= 0) continue;
        try {
        $row = $db->fetch("SELECT id, remote_url, source FROM {$pre}videos WHERE id = ?", [$check_id]);
        if (!$row || empty($row->remote_url)) {
            $results[] = ['id' => $check_id, 'available' => false, 'reason' => 'No remote URL'];
            continue;
        }
        $url = trim($row->remote_url);
        $available = false;
        $reason = '';
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            $oembed = 'https://www.youtube.com/oembed?url=' . rawurlencode($url) . '&format=json';
            $headers = @get_headers($oembed, 1);
            $statusOk = is_array($headers) && isset($headers[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $m) && (int)$m[1] >= 200 && (int)$m[1] < 300;
            if (!$statusOk) {
                $code = (!empty($m[1])) ? (int)$m[1] : 0;
                $reason = $code >= 500 ? 'Unavailable (HTTP ' . $code . ')' : ($code >= 400 ? 'Unavailable (HTTP ' . $code . ')' : 'Unavailable (oEmbed failed)');
            } else {
                $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
                $raw = @file_get_contents($oembed, false, $ctx);
                if ($raw !== false) {
                    $json = @json_decode($raw, true);
                    if (is_array($json) && (isset($json['title']) || isset($json['author_name']))) {
                        $available = true;
                        $reason = 'OK';
                    } else {
                        $reason = 'Video unavailable or restricted';
                    }
                } else {
                    $reason = 'Unavailable (oEmbed failed)';
                }
            }
        } elseif (strpos($url, 'vimeo.com') !== false) {
            $oembed = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode($url);
            $headers = @get_headers($oembed, 1);
            $statusOk = is_array($headers) && isset($headers[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $m) && (int)$m[1] >= 200 && (int)$m[1] < 300;
            if (!$statusOk) {
                $code = (!empty($m[1])) ? (int)$m[1] : 0;
                $reason = $code >= 500 ? 'Unavailable (HTTP ' . $code . ')' : ($code >= 400 ? 'Unavailable (HTTP ' . $code . ')' : 'Unavailable (oEmbed failed)');
            } else {
                $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
                $raw = @file_get_contents($oembed, false, $ctx);
                if ($raw !== false) {
                    $json = @json_decode($raw, true);
                    if (is_array($json) && (isset($json['title']) || isset($json['author_name']))) {
                        $available = true;
                        $reason = 'OK';
                    } else {
                        $reason = 'Video unavailable or private';
                    }
                } else {
                    $reason = 'Unavailable (oEmbed failed)';
                }
            }
        } else {
            $headers = @get_headers($url, 1);
            $code = 0;
            if (is_array($headers) && isset($headers[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $m)) {
                $code = (int)$m[1];
            }
            $available = $code >= 200 && $code < 300;
            $reason = $available ? 'OK' : ($code >= 500 ? 'Unavailable (HTTP ' . $code . ')' : ($code >= 400 ? 'Unavailable (HTTP ' . $code . ')' : 'Link not reachable'));
        }
        $results[] = ['id' => $check_id, 'available' => $available, 'reason' => $reason];
        } catch (Throwable $e) {
            $results[] = ['id' => $check_id, 'available' => false, 'reason' => 'Check error'];
        }
    }
    echo json_encode(['results' => $results]);
    exit;
}

// GET: clear stored broken list (reset filter)
if (isset($_GET['clear_broken']) && $_GET['clear_broken'] === '1') {
    unset($_SESSION['broken_video_ids']);
    redirect(admin_url('broken-videos'));
}

// POST: store broken IDs in session (for "show only broken" filter)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'store_broken') {
    if (!empty($_POST['ids']) && is_array($_POST['ids'])) {
        $_SESSION['broken_video_ids'] = array_values(array_filter(array_map('intval', $_POST['ids'])));
    } else {
        $_SESSION['broken_video_ids'] = [];
    }
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'count' => count($_SESSION['broken_video_ids'])]);
        exit;
    }
    redirect(admin_url('broken-videos?filter=broken'));
}

// AJAX: check availability of one video (YouTube oEmbed, etc.)
$check_id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
if ($is_ajax && $check_id > 0 && isset($_GET['check_availability'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
    $row = $db->fetch("SELECT id, remote_url, source FROM {$pre}videos WHERE id = ?", [$check_id]);
    if (!$row || empty($row->remote_url)) {
        echo json_encode(['id' => $check_id, 'available' => false, 'reason' => 'No remote URL']);
        exit;
    }
    $url = trim($row->remote_url);
    $source = strtolower(trim($row->source ?? ''));
    $available = false;
    $reason = '';

    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        $oembed = 'https://www.youtube.com/oembed?url=' . rawurlencode($url) . '&format=json';
        $headers = @get_headers($oembed, 1);
        $statusOk = is_array($headers) && isset($headers[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $m) && (int)$m[1] >= 200 && (int)$m[1] < 300;
        if (!$statusOk) {
            $code = (!empty($m[1])) ? (int)$m[1] : 0;
            $reason = $code >= 500 ? 'Unavailable (HTTP ' . $code . ')' : ($code >= 400 ? 'Unavailable (HTTP ' . $code . ')' : 'Unavailable (oEmbed failed)');
        } else {
            $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
            $raw = @file_get_contents($oembed, false, $ctx);
            if ($raw !== false) {
                $json = @json_decode($raw, true);
                if (is_array($json) && (isset($json['title']) || isset($json['author_name']))) {
                    $available = true;
                    $reason = 'OK';
                } else {
                    $reason = 'Video unavailable or restricted';
                }
            } else {
                $reason = 'Unavailable (oEmbed failed)';
            }
        }
    } elseif (strpos($url, 'vimeo.com') !== false) {
        $oembed = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode($url);
        $headers = @get_headers($oembed, 1);
        $statusOk = is_array($headers) && isset($headers[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $m) && (int)$m[1] >= 200 && (int)$m[1] < 300;
        if (!$statusOk) {
            $code = (!empty($m[1])) ? (int)$m[1] : 0;
            $reason = $code >= 500 ? 'Unavailable (HTTP ' . $code . ')' : ($code >= 400 ? 'Unavailable (HTTP ' . $code . ')' : 'Unavailable (oEmbed failed)');
        } else {
            $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
            $raw = @file_get_contents($oembed, false, $ctx);
            if ($raw !== false) {
                $json = @json_decode($raw, true);
                if (is_array($json) && (isset($json['title']) || isset($json['author_name']))) {
                    $available = true;
                    $reason = 'OK';
                } else {
                    $reason = 'Video unavailable or private';
                }
            } else {
                $reason = 'Unavailable (oEmbed failed)';
            }
        }
    } else {
        $headers = @get_headers($url, 1);
        $code = 0;
        if (is_array($headers) && isset($headers[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $m)) {
            $code = (int)$m[1];
        }
        $available = $code >= 200 && $code < 300;
        $reason = $available ? 'OK' : ($code >= 500 ? 'Unavailable (HTTP ' . $code . ')' : ($code >= 400 ? 'Unavailable (HTTP ' . $code . ')' : 'Link not reachable'));
    }

    echo json_encode(['id' => $check_id, 'available' => $available, 'reason' => $reason]);
    } catch (Throwable $e) {
        echo json_encode(['id' => $check_id, 'available' => false, 'reason' => 'Check error']);
    }
    exit;
}

// POST: delete single or bulk
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'delete' && isset($_POST['id'])) {
        $vid = (int) $_POST['id'];
        if ($vid > 0) {
            $db->query("DELETE FROM {$pre}videos WHERE id = ?", [$vid]);
            redirect(admin_url('broken-videos?deleted=1'));
        }
    }
    if ($action === 'bulk_delete' && !empty($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = array_filter(array_map('intval', $_POST['ids']));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM {$pre}videos WHERE id IN ($placeholders)", $ids);
            redirect(admin_url('broken-videos?deleted=' . count($ids)));
        }
    }
}

$admin_title = 'Unavailable / Broken videos';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per_options = [10, 25, 50, 100];
$per = isset($_GET['per']) && in_array((int) $_GET['per'], $per_options, true) ? (int) $_GET['per'] : 25;
$offset = ($page - 1) * $per;

$filter_broken = isset($_GET['filter']) && $_GET['filter'] === 'broken';
$filter_source = isset($_GET['source']) && $_GET['source'] !== '' ? trim($_GET['source']) : '';
$broken_ids = [];
if ($filter_broken && !empty($_SESSION['broken_video_ids'])) {
    $broken_ids = array_values(array_filter(array_map('intval', $_SESSION['broken_video_ids'])));
}

// Base count of all remote videos (for stats)
$_tr = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos v WHERE (v.remote_url IS NOT NULL AND v.remote_url != '')", []);
$total_remote = (int) (isset($_tr->c) ? $_tr->c : 0);
// Distinct sources for filter dropdown
$sources = $db->fetchAll("SELECT DISTINCT source FROM {$pre}videos WHERE (remote_url IS NOT NULL AND remote_url != '') AND source IS NOT NULL AND source != '' ORDER BY source", []);
$sources = is_array($sources) ? $sources : [];
$source_list = [];
foreach ($sources as $s) {
    $source_list[] = isset($s->source) ? $s->source : '';
}
$source_list = array_filter($source_list);

// Only videos that have a remote URL (or only broken from last test)
$where = "(v.remote_url IS NOT NULL AND v.remote_url != '')";
$params = [];
if ($filter_broken) {
    if (!empty($broken_ids)) {
        $placeholders = implode(',', array_fill(0, count($broken_ids), '?'));
        $where = "v.id IN ($placeholders)";
        $params = $broken_ids;
    } else {
        $where = "1 = 0";
    }
}
if ($filter_source !== '' && !$filter_broken) {
    $where .= " AND TRIM(LOWER(v.source)) = ?";
    $params[] = strtolower($filter_source);
} elseif ($filter_source !== '' && $filter_broken && !empty($params)) {
    // when filter=broken, params are ids; we need to add source filter via AND id IN (...) AND source = ?
    // so we need a different approach: filter broken_ids by source, or add source to WHERE
    $where .= " AND TRIM(LOWER(v.source)) = ?";
    $params[] = strtolower($filter_source);
}
$_tc = $db->fetch("SELECT COUNT(*) AS c FROM {$pre}videos v WHERE " . $where, $params);
$total = (int) (isset($_tc->c) ? $_tc->c : 0);
$params = array_merge($params, [$per, $offset]);
$items = $db->fetchAll(
    "SELECT v.id, v.title, v.thumb, v.remote_url, v.source, v.type, v.views, v.created_at, u.username " .
    "FROM {$pre}videos v LEFT JOIN {$pre}users u ON u.id = v.user_id WHERE " . $where . " ORDER BY v.created_at DESC LIMIT ? OFFSET ?",
    $params
);
$items = is_array($items) ? $items : [];

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';

$deleted = isset($_GET['deleted']) ? (int) $_GET['deleted'] : 0;
if ($deleted > 0) {
    echo '<div class="alert alert-success alert-dismissible fade show">' . (int) $deleted . ' video(s) removed. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
$broken_count = !empty($_SESSION['broken_video_ids']) ? count($_SESSION['broken_video_ids']) : 0;
$show_all_url = admin_url('broken-videos');
$broken_only_url = admin_url('broken-videos') . (strpos(admin_url('broken-videos'), '?') !== false ? '&' : '?') . 'filter=broken';
$clear_broken_url = admin_url('broken-videos') . (strpos(admin_url('broken-videos'), '?') !== false ? '&' : '?') . 'clear_broken=1';
$base_query = [];
if ($filter_broken) $base_query['filter'] = 'broken';
if ($filter_source !== '') $base_query['source'] = $filter_source;
$base_query_str = http_build_query($base_query);
$pagination_base = admin_url('broken-videos') . ($base_query_str ? '?' . $base_query_str . '&' : '?');
?>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3">
                    <span class="material-icons text-primary" style="font-size:1.75rem;">video_library</span>
                </div>
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Total remote videos</div>
                    <div class="h4 mb-0"><?php echo (int) $total_remote; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-3 bg-danger bg-opacity-10 p-3 me-3">
                    <span class="material-icons text-danger" style="font-size:1.75rem;">broken_image</span>
                </div>
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">Broken (last test)</div>
                    <div class="h4 mb-0"><?php echo (int) $broken_count; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-3 bg-secondary bg-opacity-10 p-3 me-3">
                    <span class="material-icons text-secondary" style="font-size:1.75rem;">list</span>
                </div>
                <div>
                    <div class="text-muted small text-uppercase fw-semibold">On this page</div>
                    <div class="h4 mb-0"><?php echo count($items); ?> <span class="text-muted fw-normal">/ <?php echo (int) $total; ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="card-title d-flex align-items-center gap-2 mb-2">
            <span class="material-icons text-warning">warning</span>
            Unavailable / Broken videos
        </h5>
        <p class="text-muted small mb-0">
            These are videos with a remote link (e.g. YouTube, Vimeo). They may show &quot;Video unavailable&quot; or &quot;The uploader has not made this video available in your country&quot;. Test and remove any broken or unavailable entries.
        </p>
        <?php if ($filter_broken && !empty($broken_ids)) { ?>
            <p class="text-muted small mb-0 mt-2">
                <span class="material-icons align-middle" style="font-size:1rem;">filter_alt</span>
                Showing only Unavailable / Broken videos from last test.
                <a href="<?php echo _e($show_all_url); ?>" class="text-decoration-none"><span class="material-icons align-middle" style="font-size:1rem;">link</span> Show all remote videos</a>
            </p>
        <?php } ?>
    </div>
</div>

<!-- Test summary & progress -->
<div id="test-summary" class="alert alert-secondary d-flex align-items-center flex-wrap gap-2 py-3 mb-3" <?php if ($broken_count <= 0) { ?>style="display:none;"<?php } ?>>
    <span class="material-icons">info</span>
    <span id="broken-count-text"><?php
        if ($broken_count > 0) {
            $blink = admin_url('broken-videos') . (strpos(admin_url('broken-videos'), '?') !== false ? '&' : '?') . 'filter=broken';
            echo '<a href="' . _e($blink) . '" class="fw-bold text-dark text-decoration-none">' . (int) $broken_count . ' Unavailable / Broken videos</a>';
        } else {
            echo '<span id="broken-count-inner">0 Unavailable / Broken videos</span>';
        }
    ?></span>
    <span class="ms-2 text-muted small" id="checked-count-text"></span>
    <?php if ($broken_count > 0) { ?>
        <a href="<?php echo _e($clear_broken_url); ?>" class="btn btn-sm btn-outline-secondary ms-2" title="Clear stored broken list">
            <span class="material-icons align-middle" style="font-size:1rem;">clear_all</span> Clear stored
        </a>
    <?php } ?>
</div>
<div id="test-progress-wrap" class="mb-3" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="text-muted small" id="tested-count-text">Tested 0 / 0</span>
    </div>
    <div class="progress" style="height:8px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="test-progress-bar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</div>

<form method="post" id="bulk-form" data-broken-only-url="<?php echo _e($broken_only_url); ?>">
    <input type="hidden" name="action" value="bulk_delete">
    <!-- Toolbar: filters, Test, actions, per page -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm" id="test-all-btn" title="Check availability of all remote videos">
                        <span class="material-icons align-middle" style="font-size:1.1rem;">play_circle</span> Test all
                    </button>
                    <?php if ($broken_count > 0) { ?>
                        <a href="<?php echo _e($broken_only_url); ?>" class="btn btn-outline-warning btn-sm text-dark" title="View only broken">
                            <span class="material-icons align-middle" style="font-size:1.1rem;">error_outline</span> View broken only
                        </a>
                    <?php } ?>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary" id="select-all-btn" title="Select all on page"><span class="material-icons align-middle" style="font-size:1rem;">check_box</span></button>
                    <button type="button" class="btn btn-outline-secondary" id="select-none-btn" title="Deselect all"><span class="material-icons align-middle" style="font-size:1rem;">check_box_outline_blank</span></button>
                    <button type="button" class="btn btn-outline-secondary" id="select-broken-btn" title="Select only broken (after test)" style="display:none;"><span class="material-icons align-middle" style="font-size:1rem;">dangerous</span> Select broken</button>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" id="bulk-delete-btn" style="display:none;">
                    <span class="material-icons align-middle" style="font-size:1.1rem;">delete</span> Remove selected
                </button>
                <span class="text-muted small" id="selected-count"></span>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0 d-flex align-items-center gap-1">
                        <span class="material-icons" style="font-size:1rem;">filter_list</span> Source
                    </label>
                    <select class="form-select form-select-sm" id="source-filter" style="width:auto; max-width:140px;">
                        <option value="">All</option>
                        <?php foreach ($source_list as $src) {
                            $sel = (strtolower($filter_source) === strtolower($src)) ? ' selected' : '';
                            echo '<option value="' . _e($src) . '"' . $sel . '>' . _e($src) . '</option>';
                        } ?>
                    </select>
                    <label class="text-muted small mb-0 ms-2 d-flex align-items-center gap-1">
                        <span class="material-icons" style="font-size:1rem;">tune</span> Per page
                    </label>
                    <select class="form-select form-select-sm" id="per-page" style="width:auto; max-width:80px;">
                        <?php foreach ($per_options as $po) {
                            $sel = ($per === $po) ? ' selected' : '';
                            echo '<option value="' . $po . '"' . $sel . '>' . $po . '</option>';
                        } ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;"><input type="checkbox" class="form-check-input" id="select-all" aria-label="Select all"></th>
                    <th style="width:90px;">Preview</th>
                    <th>Title</th>
                    <th style="width:100px;">Source</th>
                    <th style="width:180px;">Status</th>
                    <th style="width:70px;">Views</th>
                    <th style="width:120px;">Added</th>
                    <th style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $v) {
                $thumb = !empty($v->thumb) ? $v->thumb : '';
                if ($thumb !== '' && strpos($thumb, 'http') !== 0) {
                    $thumb = rtrim(SITE_URL, '/') . '/' . ltrim($thumb, '/');
                }
                $watch_link = function_exists('watch_url') ? watch_url($v->id, $v->title ?? '') : (rtrim(SITE_URL, '/') . '/watch/' . (int) $v->id);
                $src = $v->source ?? 'remote';
                $src_icon = (strpos(strtolower($src), 'youtube') !== false) ? 'smart_display' : ((strpos(strtolower($src), 'vimeo') !== false) ? 'videocam' : 'link');
            ?>
                <tr>
                    <td><input type="checkbox" class="form-check-input row-check" name="ids[]" value="<?php echo (int) $v->id; ?>"></td>
                    <td>
                        <?php if ($thumb) { ?>
                            <a href="<?php echo _e($watch_link); ?>" target="_blank" class="d-block rounded overflow-hidden" style="width:80px;height:45px;"><img src="<?php echo _e($thumb); ?>" alt="" class="w-100 h-100" style="object-fit:cover;"></a>
                        <?php } else { ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:80px;height:45px;"><span class="material-icons text-muted" style="font-size:1.5rem;">videocam_off</span></div>
                        <?php } ?>
                    </td>
                    <td>
                        <a href="<?php echo _e($watch_link); ?>" target="_blank" class="text-decoration-none fw-medium"><?php echo _e($v->title ?? '-'); ?></a>
                        <?php if (!empty($v->type) && $v->type === 'music') { ?><span class="badge bg-info ms-1">Music</span><?php } ?>
                    </td>
                    <td><span class="badge bg-secondary d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size:0.9rem;"><?php echo $src_icon; ?></span><?php echo _e($src); ?></span></td>
                    <td class="status-cell" data-video-id="<?php echo (int) $v->id; ?>">
                        <span class="status-text text-muted small d-inline-flex align-items-center gap-1">—</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary test-one-btn" data-id="<?php echo (int) $v->id; ?>" title="Test this video"><span class="material-icons" style="font-size:1rem;">play_circle_outline</span></button>
                    </td>
                    <td><span class="text-muted"><?php echo number_format((int) ($v->views ?? 0)); ?></span></td>
                    <td class="small text-muted"><?php echo _e($v->created_at ?? ''); ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?php echo _e($v->remote_url ?? '#'); ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener" title="Open original link"><span class="material-icons" style="font-size:1rem;">open_in_new</span></a>
                            <a href="<?php echo _e($watch_link); ?>" class="btn btn-outline-primary" target="_blank" title="View on site"><span class="material-icons" style="font-size:1rem;">play_circle</span></a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Remove this video from the site?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $v->id; ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Remove"><span class="material-icons" style="font-size:1rem;">delete</span></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            <?php if (empty($items)) { ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="text-muted">
                            <span class="material-icons d-block mb-2" style="font-size:3rem; opacity:0.5;">video_library</span>
                            <div>No remote videos found.</div>
                            <small>All videos are local, or there are no videos yet. Add YouTube/Vimeo links to see them here.</small>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</form>

<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1 || $total > 0) {
    $nav_url = function($p) use ($pagination_base, $per) {
        return $pagination_base . 'p=' . $p . '&per=' . (int) $per;
    };
    echo '<nav class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">';
    echo '<div class="text-muted small">Page ' . $page . ' of ' . max(1, $total_pages) . ' &middot; ' . (int) $total . ' total</div>';
    echo '<ul class="pagination pagination-sm mb-0">';
    if ($total_pages > 1) {
        echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . ($page <= 1 ? '#' : _e($nav_url(1))) . '"><span class="material-icons" style="font-size:1rem;">first_page</span></a></li>';
        echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . ($page <= 1 ? '#' : _e($nav_url($page - 1))) . '"><span class="material-icons" style="font-size:1rem;">chevron_left</span></a></li>';
        for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
            echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($nav_url($i)) . '">' . $i . '</a></li>';
        }
        echo '<li class="page-item' . ($page >= $total_pages ? ' disabled' : '') . '"><a class="page-link" href="' . ($page >= $total_pages ? '#' : _e($nav_url($page + 1))) . '"><span class="material-icons" style="font-size:1rem;">chevron_right</span></a></li>';
        echo '<li class="page-item' . ($page >= $total_pages ? ' disabled' : '') . '"><a class="page-link" href="' . ($page >= $total_pages ? '#' : _e($nav_url($total_pages))) . '"><span class="material-icons" style="font-size:1rem;">last_page</span></a></li>';
    }
    echo '</ul></nav>';
}
?>

<script>
(function() {
    var selectAll = document.getElementById('select-all');
    var rowChecks = document.querySelectorAll('.row-check');
    var bulkBtn = document.getElementById('bulk-delete-btn');
    var countSpan = document.getElementById('selected-count');
    var form = document.getElementById('bulk-form');
    var testAllBtn = document.getElementById('test-all-btn');
    var testSummary = document.getElementById('test-summary');
    var brokenCountText = document.getElementById('broken-count-text');
    var checkedCountText = document.getElementById('checked-count-text');
    var testProgressWrap = document.getElementById('test-progress-wrap');
    var testedCountText = document.getElementById('tested-count-text');
    var testProgressBar = document.getElementById('test-progress-bar');
    var baseUrl = window.location.pathname + (window.location.search || '');
    var brokenOnlyUrl = form && form.getAttribute('data-broken-only-url') ? form.getAttribute('data-broken-only-url') : (baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'filter=broken');

    function updateState() {
        var n = 0;
        rowChecks.forEach(function(c) { if (c.checked) n++; });
        if (bulkBtn) bulkBtn.style.display = n ? 'inline-flex' : 'none';
        if (countSpan) countSpan.textContent = n ? n + ' selected' : '';
        if (checkedCountText) checkedCountText.textContent = n + ' checked';
    }
    if (selectAll) selectAll.addEventListener('change', function() {
        rowChecks.forEach(function(c) { c.checked = selectAll.checked; });
        updateState();
    });
    rowChecks.forEach(function(c) { c.addEventListener('change', updateState); });
    if (bulkBtn && form) bulkBtn.addEventListener('click', function() {
        var n = 0;
        rowChecks.forEach(function(c) { if (c.checked) n++; });
        if (n && confirm('Remove ' + n + ' selected video(s)?')) form.submit();
    });

    var selectAllBtn = document.getElementById('select-all-btn');
    var selectNoneBtn = document.getElementById('select-none-btn');
    var selectBrokenBtn = document.getElementById('select-broken-btn');
    if (selectAllBtn) selectAllBtn.addEventListener('click', function() {
        if (selectAll) selectAll.checked = true;
        rowChecks.forEach(function(c) { c.checked = true; });
        updateState();
    });
    if (selectNoneBtn) selectNoneBtn.addEventListener('click', function() {
        if (selectAll) selectAll.checked = false;
        rowChecks.forEach(function(c) { c.checked = false; });
        updateState();
    });
    if (selectBrokenBtn) selectBrokenBtn.addEventListener('click', function() {
        if (selectAll) selectAll.checked = false;
        rowChecks.forEach(function(c) { c.checked = false; });
        document.querySelectorAll('.status-cell').forEach(function(cell) {
            var text = cell.querySelector('.status-text');
            if (text && text.classList.contains('text-danger')) {
                var row = cell.closest('tr');
                var cb = row ? row.querySelector('.row-check') : null;
                if (cb) cb.checked = true;
            }
        });
        updateState();
    });

    var sourceFilter = document.getElementById('source-filter');
    var perPage = document.getElementById('per-page');
    if (sourceFilter) sourceFilter.addEventListener('change', function() {
        var params = new URLSearchParams(window.location.search);
        params.set('source', this.value || '');
        params.delete('p');
        window.location.search = params.toString();
    });
    if (perPage) perPage.addEventListener('change', function() {
        var params = new URLSearchParams(window.location.search);
        params.set('per', this.value);
        params.set('p', '1');
        window.location.search = params.toString();
    });

    function checkBaseUrl() {
        return baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'check_availability=1&id=';
    }
    function batchUrl(ids) {
        var u = baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'check_availability_batch=1';
        ids.forEach(function(id) { u += '&ids[]=' + id; });
        return u;
    }
    function setStatus(cell, available, reason) {
        var wrap = cell ? cell.querySelector('.status-text') : null;
        if (!wrap) return;
        var btn = cell ? cell.querySelector('.test-one-btn') : null;
        var label = reason || (available ? 'OK' : 'Unavailable');
        var icon = available ? 'check_circle' : 'error';
        wrap.innerHTML = '<span class="material-icons align-middle me-1" style="font-size:1rem;">' + icon + '</span>' + label;
        wrap.className = 'status-text small d-inline-flex align-items-center ' + (available ? 'text-success' : 'text-danger');
        if (btn) btn.disabled = false;
    }
    function setTesting(cell) {
        var wrap = cell ? cell.querySelector('.status-text') : null;
        var btn = cell ? cell.querySelector('.test-one-btn') : null;
        if (wrap) wrap.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Checking…';
        if (btn) btn.disabled = true;
    }
    function showSummary(brokenTotal, checkedN) {
        if (!testSummary || !brokenCountText) return;
        brokenCountText.innerHTML = '<a href="' + brokenOnlyUrl + '" class="fw-bold text-dark text-decoration-none">' + brokenTotal + ' Unavailable / Broken videos</a>';
        if (checkedCountText) checkedCountText.textContent = (checkedN !== undefined ? checkedN : 0) + ' checked';
        testSummary.style.display = 'flex';
        if (selectBrokenBtn && brokenTotal > 0) selectBrokenBtn.style.display = 'inline-flex';
    }
    function testOne(id, cell, checkThenSelectUnavailable) {
        if (!cell) cell = document.querySelector('.status-cell[data-video-id="' + id + '"]');
        if (!cell) return Promise.resolve();
        setTesting(cell);
        var url = checkBaseUrl() + id;
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                setStatus(cell, data.available, data.reason);
                if (checkThenSelectUnavailable && !data.available) {
                    var row = cell.closest('tr');
                    var cb = row ? row.querySelector('.row-check') : null;
                    if (cb) cb.checked = true;
                    updateState();
                }
                return data;
            })
            .catch(function() {
                setStatus(cell, false, 'Check failed');
                var b = cell && cell.querySelector('.test-one-btn');
                if (b) b.disabled = false;
                return { id: id, available: false };
            });
    }

    document.querySelectorAll('.test-one-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = parseInt(btn.getAttribute('data-id'), 10);
            if (!id) return;
            testOne(id, btn.closest('.status-cell'), true);
        });
    });

    if (testAllBtn) testAllBtn.addEventListener('click', function() {
        var listUrl = baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'list_remote_ids=1';
        testAllBtn.disabled = true;
        if (brokenCountText) brokenCountText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing all videos…';
        if (testSummary) testSummary.style.display = 'flex';
        if (testProgressWrap) testProgressWrap.style.display = 'block';
        if (testProgressBar) testProgressBar.style.width = '0%';
        if (testedCountText) testedCountText.textContent = 'Tested 0 / 0';
        fetch(listUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var allIds = data.ids || [];
                if (allIds.length === 0) {
                    if (brokenCountText) brokenCountText.innerHTML = '0 Unavailable / Broken videos';
                    if (checkedCountText) checkedCountText.textContent = '0 checked';
                    testAllBtn.disabled = false;
                    if (testProgressWrap) testProgressWrap.style.display = 'none';
                    return;
                }
                if (testedCountText) testedCountText.textContent = 'Tested 0 / ' + allIds.length;
                var broken = [];
                var resultsById = {};
                var chunkSize = 25;
                var index = 0;
                function runChunk() {
                    if (index >= allIds.length) {
                        var formData = new FormData();
                        formData.append('action', 'store_broken');
                        broken.forEach(function(id) { formData.append('ids[]', id); });
                        if (testProgressBar) testProgressBar.style.width = '100%';
                        if (testedCountText) testedCountText.textContent = 'Tested ' + allIds.length + ' / ' + allIds.length;
                        return fetch(baseUrl, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData
                        }).then(function() {
                            showSummary(broken.length, 0);
                            var cells = document.querySelectorAll('.status-cell[data-video-id]');
                            var checked = 0;
                            cells.forEach(function(cell) {
                                var id = parseInt(cell.getAttribute('data-video-id'), 10);
                                var res = resultsById[id];
                                if (res) {
                                    setStatus(cell, res.available, res.reason);
                                    if (!res.available) {
                                        var row = cell.closest('tr');
                                        var cb = row ? row.querySelector('.row-check') : null;
                                        if (cb) { cb.checked = true; checked++; }
                                    }
                                }
                            });
                            updateState();
                            if (checkedCountText) checkedCountText.textContent = checked + ' checked';
                            testAllBtn.disabled = false;
                            if (testProgressWrap) testProgressWrap.style.display = 'none';
                        });
                    }
                    var chunk = allIds.slice(index, index + chunkSize);
                    index += chunkSize;
                    if (testProgressBar) testProgressBar.style.width = Math.round((index / allIds.length) * 100) + '%';
                    if (testedCountText) testedCountText.textContent = 'Tested ' + Math.min(index, allIds.length) + ' / ' + allIds.length;
                    var batchReqUrl = batchUrl(chunk);
                    return fetch(batchReqUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(r) { return r.json(); })
                        .then(function(batch) {
                            var list = batch.results || [];
                            list.forEach(function(res) {
                                resultsById[res.id] = res;
                                if (!res.available) broken.push(res.id);
                            });
                            return runChunk();
                        });
                }
                return runChunk();
            })
            .catch(function() {
                if (brokenCountText) brokenCountText.textContent = 'Test failed';
                testAllBtn.disabled = false;
                if (testProgressWrap) testProgressWrap.style.display = 'none';
            });
    });
    updateState();
})();
</script>

<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
