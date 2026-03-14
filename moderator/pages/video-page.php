<?php
/**
 * Admin: Video page builder (layout for /videos).
 * Reuses the homepage builder UI but stores config in option `videos_page_boxes`.
 */
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

// Boxes: [{ type, title, source, ids, limit, channel_type?, content? }]
$box_types = [
    'video'   => 'Videos',
    'music'   => 'Music',
    'image'   => 'Images',
    'channel' => 'Channels',
    'playlist'=> 'Playlist',
    'html'    => 'HTML block',
];
$sources = [
    'browse'   => 'Browse (latest)',
    'featured' => 'Featured',
    'ids'      => 'Specific IDs',
];
$option_key = 'videos_page_boxes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_boxes') {
        $grid_size = isset($_POST['videos_grid_size']) && in_array($_POST['videos_grid_size'], ['small', 'medium', 'large'], true) ? $_POST['videos_grid_size'] : 'medium';
        update_option('videos_grid_size', $grid_size, 1);
        $raw = isset($_POST['boxes']) && is_array($_POST['boxes']) ? $_POST['boxes'] : [];
        $boxes = [];
        foreach ($raw as $b) {
            $type = isset($b['type']) ? trim($b['type']) : 'video';
            if (!isset($box_types[$type])) continue;
            $title = isset($b['title']) ? trim($b['title']) : ucfirst($type);
            $source = isset($b['source']) ? trim($b['source']) : 'browse';
            $limit = isset($b['limit']) ? (int) $b['limit'] : 12;
            $ids = [];
            if (!empty($b['ids']) && is_string($b['ids'])) {
                $ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $b['ids'])));
            }
            $channel_type = isset($b['channel_type']) ? trim($b['channel_type']) : 'video';
            $content = isset($b['content']) ? trim($b['content']) : '';
            $box = [
                'type'   => $type,
                'title'  => $title,
                'source' => $source,
                'limit'  => $limit,
                'ids'    => array_values($ids),
            ];
            if ($type === 'channel') $box['channel_type'] = $channel_type;
            if ($type === 'html') $box['content'] = $content;
            $boxes[] = $box;
        }
        update_option($option_key, json_encode($boxes), 1);
        redirect(admin_url('video-page'));
    }
}

$admin_title = 'Video page builder';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
$boxes = get_option($option_key, '[]');
$boxes = is_string($boxes) ? json_decode($boxes, true) : $boxes;
if (!is_array($boxes)) $boxes = [];
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold d-flex align-items-center">
            <span class="material-icons text-primary me-2">video_settings</span> Video Page Builder
        </h5>
        <a href="<?php echo url('videos'); ?>" class="btn btn-sm btn-light border d-inline-flex align-items-center shadow-sm" target="_blank">
            <span class="material-icons me-1" style="font-size: 16px;">open_in_new</span> View /videos
        </a>
    </div>
    <div class="card-body bg-light">
        <p class="text-muted small mb-4 d-flex align-items-center">
            <span class="material-icons text-info me-2" style="font-size: 18px;">info</span>
            Design the sections for your public Videos page (e.g. Trending, Latest, Playlists). Drag cards to reorder.
        </p>
        <?php
        $videos_grid_size = get_option('videos_grid_size', 'medium');
        ?>
        <div class="mb-4 p-3 bg-white border rounded shadow-sm">
            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Card size for /videos page</label>
            <select class="form-select form-select-sm w-auto" id="videos_grid_size" form="video-page-builder-form" name="videos_grid_size">
                <option value="small" <?php echo $videos_grid_size === 'small' ? 'selected' : ''; ?>>Small (more columns)</option>
                <option value="medium" <?php echo $videos_grid_size === 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="large" <?php echo $videos_grid_size === 'large' ? 'selected' : ''; ?>>Large (fewer columns)</option>
            </select>
            <div class="form-text small">Controls the size of video cards on the Videos browse page.</div>
        </div>
        <form method="post" id="video-page-builder-form">
            <input type="hidden" name="action" value="save_boxes">
            <div id="homepage-sections" class="mb-4 d-flex flex-column gap-3">
                <?php foreach ($boxes as $i => $box) {
                    $type = isset($box['type']) ? $box['type'] : 'video';
                    $title = isset($box['title']) ? $box['title'] : ucfirst($type);
                    $source = isset($box['source']) ? $box['source'] : 'browse';
                    $limit = isset($box['limit']) ? (int) $box['limit'] : 12;
                    $ids = isset($box['ids']) && is_array($box['ids']) ? implode(', ', $box['ids']) : '';
                    $channel_type = isset($box['channel_type']) ? $box['channel_type'] : 'video';
                    $content = isset($box['content']) ? $box['content'] : '';
                ?>
                <div class="homepage-section card border-0 shadow-sm" data-index="<?php echo $i; ?>">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="drag-handle text-muted cursor-grab bg-light rounded d-flex align-items-center justify-content-center border" title="Drag to reorder" style="width: 40px; height: 40px; cursor: grab;">
                                <span class="material-icons align-middle text-secondary">drag_indicator</span>
                            </span>
                            <div class="flex-grow-1 row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Section Type</label>
                                    <select class="form-select section-type border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][type]">
                                        <?php foreach ($box_types as $k => $v) { ?>
                                            <option value="<?php echo _e($k); ?>" <?php echo $type === $k ? 'selected' : ''; ?>><?php echo _e($v); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted mb-1">Display Title</label>
                                    <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][title]" value="<?php echo _e($title); ?>" placeholder="e.g. Trending now">
                                </div>
                                <div class="col-md-3 section-source-wrap">
                                    <label class="form-label small fw-bold text-muted mb-1">Data Source</label>
                                    <select class="form-select border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][source]">
                                        <?php foreach ($sources as $k => $v) { ?>
                                            <option value="<?php echo _e($k); ?>" <?php echo $source === $k ? 'selected' : ''; ?>><?php echo _e($v); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-1 section-limit-wrap">
                                    <label class="form-label small fw-bold text-muted mb-1">Limit</label>
                                    <input type="number" class="form-control border-secondary border-opacity-25 text-center" name="boxes[<?php echo $i; ?>][limit]" value="<?php echo $limit; ?>" min="1" max="48">
                                </div>
                                <div class="col-6 col-md-2 section-ids-wrap">
                                    <label class="form-label small fw-bold text-muted mb-1">Specific IDs</label>
                                    <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][ids]" value="<?php echo _e($ids); ?>" placeholder="1, 2, 3...">
                                </div>
                                <div class="col-md-3 section-channel-type-wrap">
                                    <label class="form-label small fw-bold text-muted mb-1">Filter Entity</label>
                                    <select class="form-select border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][channel_type]">
                                        <option value="video" <?php echo ($channel_type ?? '') === 'video' ? 'selected' : ''; ?>>Video content</option>
                                        <option value="music" <?php echo ($channel_type ?? '') === 'music' ? 'selected' : ''; ?>>Audio content</option>
                                        <option value="image" <?php echo ($channel_type ?? '') === 'image' ? 'selected' : ''; ?>>Image content</option>
                                    </select>
                                </div>
                                <div class="col-12 section-html-wrap" style="display:<?php echo $type === 'html' ? 'block' : 'none'; ?>">
                                    <label class="form-label small fw-bold text-muted mb-1">Raw HTML Output</label>
                                    <textarea class="form-control border-secondary border-opacity-25 font-monospace small" name="boxes[<?php echo $i; ?>][content]" rows="3" placeholder="<div class='custom-block'>...</div>"><?php echo _e($content); ?></textarea>
                                </div>
                            </div>
                            <div class="ps-2 border-start">
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle section-remove d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Remove section">
                                    <span class="material-icons" style="font-size: 16px;">close</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between p-3 bg-white border rounded shadow-sm mt-4">
                <button type="button" class="btn btn-success d-inline-flex align-items-center shadow-sm px-3" id="add-section-btn">
                    <span class="material-icons me-1">add_circle</span> Add New Section
                </button>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center shadow-sm px-4">
                    <span class="material-icons me-1">save</span> Save Layout Config
                </button>
            </div>
        </form>
    </div>
</div>

<template id="section-tpl">
    <div class="homepage-section card border-0 shadow-sm mb-3" data-index="{{INDEX}}">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-3">
                <span class="drag-handle text-muted cursor-grab bg-light rounded d-flex align-items-center justify-content-center border" title="Drag to reorder" style="width: 40px; height: 40px; cursor: grab;">
                    <span class="material-icons align-middle text-secondary">drag_indicator</span>
                </span>
                <div class="flex-grow-1 row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Section Type</label>
                        <select class="form-select section-type border-secondary border-opacity-25" name="boxes[{{INDEX}}][type]">
                            <?php foreach ($box_types as $k => $v) { ?><option value="<?php echo _e($k); ?>"><?php echo _e($v); ?></option><?php } ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Display Title</label>
                        <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[{{INDEX}}][title]" value="" placeholder="e.g. Latest Videos">
                    </div>
                    <div class="col-md-3 section-source-wrap">
                        <label class="form-label small fw-bold text-muted mb-1">Data Source</label>
                        <select class="form-select border-secondary border-opacity-25" name="boxes[{{INDEX}}][source]">
                            <?php foreach ($sources as $k => $v) { ?><option value="<?php echo _e($k); ?>"><?php echo _e($v); ?></option><?php } ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-1 section-limit-wrap">
                        <label class="form-label small fw-bold text-muted mb-1">Limit</label>
                        <input type="number" class="form-control border-secondary border-opacity-25 text-center" name="boxes[{{INDEX}}][limit]" value="12" min="1" max="48">
                    </div>
                    <div class="col-6 col-md-2 section-ids-wrap">
                        <label class="form-label small fw-bold text-muted mb-1">Specific IDs</label>
                        <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[{{INDEX}}][ids]" value="" placeholder="1, 2, 3...">
                    </div>
                    <div class="col-md-3 section-channel-type-wrap">
                        <label class="form-label small fw-bold text-muted mb-1">Filter Entity</label>
                        <select class="form-select border-secondary border-opacity-25" name="boxes[{{INDEX}}][channel_type]">
                            <option value="video">Video content</option>
                            <option value="music">Audio content</option>
                            <option value="image">Image content</option>
                        </select>
                    </div>
                    <div class="col-12 section-html-wrap" style="display:none">
                        <label class="form-label small fw-bold text-muted mb-1">Raw HTML Output</label>
                        <textarea class="form-control border-secondary border-opacity-25 font-monospace small" name="boxes[{{INDEX}}][content]" rows="3" placeholder="<div class='custom-block'>...</div>"></textarea>
                    </div>
                </div>
                <div class="ps-2 border-start">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle section-remove d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Remove section">
                        <span class="material-icons" style="font-size: 16px;">close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.drag-handle { cursor: grab; user-select: none; }
.drag-handle:active { cursor: grabbing; }
.homepage-section.dragging { opacity: 0.6; }
</style>
<script>
(function() {
    var container = document.getElementById('homepage-sections');
    var tpl = document.getElementById('section-tpl');
    var addBtn = document.getElementById('add-section-btn');
    if (!container || !tpl || !addBtn) return;

    function nextIndex() {
        var max = -1;
        container.querySelectorAll('.homepage-section').forEach(function(el) {
            var i = parseInt(el.getAttribute('data-index'), 10);
            if (!isNaN(i) && i > max) max = i;
        });
        return max + 1;
    }

    addBtn.addEventListener('click', function() {
        var idx = nextIndex();
        var html = tpl.innerHTML.replace(/{{INDEX}}/g, idx);
        var div = document.createElement('div');
        div.innerHTML = html;
        var node = div.firstElementChild;
        container.appendChild(node);
        attachSectionEvents(node);
    });

    function attachSectionEvents(section) {
        var typeSelect = section.querySelector('.section-type');
        var htmlWrap = section.querySelector('.section-html-wrap');
        var removeBtn = section.querySelector('.section-remove');
        if (typeSelect && htmlWrap) {
            typeSelect.addEventListener('change', function() {
                htmlWrap.style.display = (this.value === 'html') ? 'block' : 'none';
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (confirm('Remove this section?')) section.remove();
            });
        }
        var handle = section.querySelector('.drag-handle');
        if (handle) {
            handle.addEventListener('mousedown', function() {
                section.classList.add('dragging');
            });
            handle.addEventListener('mouseup', function() {
                section.classList.remove('dragging');
            });
        }
    }

    container.querySelectorAll('.homepage-section').forEach(attachSectionEvents);

    // Simple drag-and-drop reorder using HTML5 API
    var dragged;
    container.addEventListener('dragstart', function(e) {
        var sec = e.target.closest('.homepage-section');
        if (!sec) return;
        dragged = sec;
        e.dataTransfer.effectAllowed = 'move';
    });
    container.addEventListener('dragover', function(e) {
        e.preventDefault();
        var sec = e.target.closest('.homepage-section');
        if (!sec || sec === dragged) return;
        var rect = sec.getBoundingClientRect();
        var next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
        container.insertBefore(dragged, next && sec.nextSibling || sec);
    });
    container.addEventListener('dragend', function() {
        dragged = null;
    });
})();
</script>

<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';

