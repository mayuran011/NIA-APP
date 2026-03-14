<?php
/**
 * Admin: Music page builder (layout for /music).
 */
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$box_types = [
    'music'   => 'Music',
    'video'   => 'Videos',
    'image'   => 'Images',
    'channel' => 'Channels',
    'playlist'=> 'Playlist',
    'library' => 'From your library',
    'html'    => 'HTML block',
];
$sources = [
    'browse'       => 'Browse (latest)',
    'featured'     => 'Featured',
    'most-viewed'  => 'Most viewed',
    'top-rated'    => 'Top rated',
    'ids'          => 'Specific IDs',
];
$displays = [
    'carousel' => 'Carousel (cards)',
    'list'     => 'List (quick picks)',
    'large'    => 'Large cards',
    'long'     => 'List with duration',
];
$grid_sizes = [
    'small'  => 'Small cards',
    'medium' => 'Medium cards',
    'large'  => 'Large cards',
];
$option_key = 'music_page_boxes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_boxes') {
        $raw = isset($_POST['boxes']) && is_array($_POST['boxes']) ? $_POST['boxes'] : [];
        $boxes = [];
        foreach ($raw as $b) {
            $type = isset($b['type']) ? trim($b['type']) : 'music';
            if (!isset($box_types[$type])) continue;
            $title = isset($b['title']) ? trim($b['title']) : ucfirst($type);
            $source = isset($b['source']) ? trim($b['source']) : 'browse';
            $limit = isset($b['limit']) ? (int) $b['limit'] : 12;
            $ids = [];
            if (!empty($b['ids']) && is_string($b['ids'])) {
                $ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $b['ids'])));
            }
            $channel_type = isset($b['channel_type']) ? trim($b['channel_type']) : 'music';
            $content = isset($b['content']) ? trim($b['content']) : '';
            $display = isset($b['display']) && isset($displays[$b['display']]) ? $b['display'] : 'carousel';
            $grid_size = isset($b['grid_size']) && isset($grid_sizes[$b['grid_size']]) ? $b['grid_size'] : 'medium';
            $box = [
                'type'    => $type,
                'title'   => $title,
                'source'  => $source,
                'limit'   => $limit,
                'ids'     => array_values($ids),
                'display' => $display,
                'grid_size' => $grid_size,
            ];
            if ($type === 'channel') $box['channel_type'] = $channel_type;
            if ($type === 'html') $box['content'] = $content;
            $boxes[] = $box;
        }
        update_option($option_key, json_encode($boxes), 1);
        redirect(admin_url('music-page'));
    }
}

$admin_title = 'Music page builder';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
$boxes = get_option($option_key, '[]');
$boxes = is_string($boxes) ? json_decode($boxes, true) : $boxes;
if (!is_array($boxes)) $boxes = [];
// Seed default sections so admin can edit/reorder and save
if (empty($boxes)) {
    $boxes = [
        ['type' => 'music', 'title' => 'Listen again', 'source' => 'browse', 'limit' => 10, 'ids' => [], 'display' => 'carousel'],
        ['type' => 'library', 'title' => 'From your library', 'source' => 'browse', 'limit' => 12, 'ids' => [], 'display' => 'carousel'],
        ['type' => 'music', 'title' => 'Quick picks', 'source' => 'most-viewed', 'limit' => 12, 'ids' => [], 'display' => 'list'],
        ['type' => 'music', 'title' => 'New releases', 'source' => 'browse', 'limit' => 12, 'ids' => [], 'display' => 'large'],
        ['type' => 'music', 'title' => 'Long listens', 'source' => 'browse', 'limit' => 9, 'ids' => [], 'display' => 'long'],
        ['type' => 'music', 'title' => 'Featured for you', 'source' => 'top-rated', 'limit' => 12, 'ids' => [], 'display' => 'carousel'],
    ];
}
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold d-flex align-items-center">
            <span class="material-icons text-primary me-2">library_music</span> Music Page Builder
        </h5>
        <a href="<?php echo url('music'); ?>" class="btn btn-sm btn-light border d-inline-flex align-items-center shadow-sm" target="_blank">
            <span class="material-icons me-1" style="font-size: 16px;">open_in_new</span> View /music
        </a>
    </div>
    <div class="card-body bg-light">
        <p class="text-muted small mb-4 d-flex align-items-center">
            <span class="material-icons text-info me-2" style="font-size: 18px;">info</span>
            Configure hero shelves and rows for the Music browse page (Listen again, New releases, Featured playlists, etc.).
        </p>
        <form method="post">
            <input type="hidden" name="action" value="save_boxes">
            <div id="homepage-sections" class="mb-4 d-flex flex-column gap-3">
                <?php foreach ($boxes as $i => $box) {
                    $type = isset($box['type']) ? $box['type'] : 'music';
                    $title = isset($box['title']) ? $box['title'] : ucfirst($type);
                    $source = isset($box['source']) ? $box['source'] : 'browse';
                    $limit = isset($box['limit']) ? (int) $box['limit'] : 12;
                    $ids = isset($box['ids']) && is_array($box['ids']) ? implode(', ', $box['ids']) : '';
                    $channel_type = isset($box['channel_type']) ? $box['channel_type'] : 'music';
                    $content = isset($box['content']) ? $box['content'] : '';
                    $display = isset($box['display']) ? $box['display'] : 'carousel';
                    $grid_size = isset($box['grid_size']) && in_array($box['grid_size'], ['small', 'medium', 'large'], true) ? $box['grid_size'] : 'medium';
                    $is_library = ($type === 'library');
                    $is_html = ($type === 'html');
                ?>
                <div class="homepage-section card border-0 shadow-sm" data-index="<?php echo $i; ?>" draggable="true">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="drag-handle text-muted cursor-grab bg-light rounded d-flex align-items-center justify-content-center border" title="Drag to reorder" style="width: 40px; height: 40px; cursor: grab;">
                                <span class="material-icons align-middle text-secondary">drag_indicator</span>
                            </span>
                            <div class="flex-grow-1 row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Section Type</label>
                                    <select class="form-select section-type border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][type]">
                                        <?php foreach ($box_types as $k => $v) { ?>
                                            <option value="<?php echo _e($k); ?>" <?php echo $type === $k ? 'selected' : ''; ?>><?php echo _e($v); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted mb-1">Display Title</label>
                                    <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][title]" value="<?php echo _e($title); ?>" placeholder="e.g. Listen again">
                                </div>
                                <div class="col-md-2 section-display-wrap" style="display:<?php echo $is_html ? 'none' : 'block'; ?>">
                                    <label class="form-label small fw-bold text-muted mb-1">Layout</label>
                                    <select class="form-select border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][display]">
                                        <?php foreach ($displays as $k => $v) { ?>
                                            <option value="<?php echo _e($k); ?>" <?php echo $display === $k ? 'selected' : ''; ?>><?php echo _e($v); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 section-grid-size-wrap" style="display:<?php echo ($is_html || $display === 'list' || $display === 'long') ? 'none' : 'block'; ?>">
                                    <label class="form-label small fw-bold text-muted mb-1">Card size</label>
                                    <select class="form-select border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][grid_size]">
                                        <?php foreach ($grid_sizes as $k => $v) { ?>
                                            <option value="<?php echo _e($k); ?>" <?php echo $grid_size === $k ? 'selected' : ''; ?>><?php echo _e($v); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 section-source-wrap" style="display:<?php echo ($is_library || $is_html) ? 'none' : 'block'; ?>">
                                    <label class="form-label small fw-bold text-muted mb-1">Data Source</label>
                                    <select class="form-select border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][source]">
                                        <?php foreach ($sources as $k => $v) { ?>
                                            <option value="<?php echo _e($k); ?>" <?php echo $source === $k ? 'selected' : ''; ?>><?php echo _e($v); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-1 section-limit-wrap" style="display:<?php echo ($is_library || $is_html) ? 'none' : 'block'; ?>">
                                    <label class="form-label small fw-bold text-muted mb-1">Limit</label>
                                    <input type="number" class="form-control border-secondary border-opacity-25 text-center" name="boxes[<?php echo $i; ?>][limit]" value="<?php echo $limit; ?>" min="1" max="48">
                                </div>
                                <div class="col-6 col-md-2 section-ids-wrap" style="display:<?php echo ($is_library || $is_html) ? 'none' : 'block'; ?>">
                                    <label class="form-label small fw-bold text-muted mb-1">Specific IDs</label>
                                    <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][ids]" value="<?php echo _e($ids); ?>" placeholder="1, 2, 3...">
                                </div>
                                <div class="col-md-2 section-channel-type-wrap" style="display:<?php echo ($type !== 'channel' || $is_html) ? 'none' : 'block'; ?>">
                                    <label class="form-label small fw-bold text-muted mb-1">Filter Entity</label>
                                    <select class="form-select border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][channel_type]">
                                        <option value="music" <?php echo ($channel_type ?? '') === 'music' ? 'selected' : ''; ?>>Music</option>
                                        <option value="video" <?php echo ($channel_type ?? '') === 'video' ? 'selected' : ''; ?>>Video</option>
                                        <option value="image" <?php echo ($channel_type ?? '') === 'image' ? 'selected' : ''; ?>>Image</option>
                                    </select>
                                </div>
                                <div class="col-12 section-html-wrap" style="display:<?php echo $is_html ? 'block' : 'none'; ?>">
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

<?php
// Music-specific template (Layout + Library type) and drag/add script
?>
<template id="section-tpl">
    <div class="homepage-section card border-0 shadow-sm mb-3" data-index="{{INDEX}}" draggable="true">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-3">
                <span class="drag-handle text-muted cursor-grab bg-light rounded d-flex align-items-center justify-content-center border" title="Drag to reorder" style="width: 40px; height: 40px; cursor: grab;">
                    <span class="material-icons align-middle text-secondary">drag_indicator</span>
                </span>
                <div class="flex-grow-1 row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Section Type</label>
                        <select class="form-select section-type border-secondary border-opacity-25" name="boxes[{{INDEX}}][type]">
                            <?php foreach ($box_types as $k => $v) { ?><option value="<?php echo _e($k); ?>"><?php echo _e($v); ?></option><?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Display Title</label>
                        <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[{{INDEX}}][title]" value="" placeholder="Section title">
                    </div>
                    <div class="col-md-2 section-display-wrap">
                        <label class="form-label small fw-bold text-muted mb-1">Layout</label>
                        <select class="form-select border-secondary border-opacity-25" name="boxes[{{INDEX}}][display]">
                            <?php foreach ($displays as $k => $v) { ?><option value="<?php echo _e($k); ?>"><?php echo _e($v); ?></option><?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2 section-grid-size-wrap">
                        <label class="form-label small fw-bold text-muted mb-1">Card size</label>
                        <select class="form-select border-secondary border-opacity-25" name="boxes[{{INDEX}}][grid_size]">
                            <?php foreach ($grid_sizes as $k => $v) { ?><option value="<?php echo _e($k); ?>"><?php echo _e($v); ?></option><?php } ?>
                        </select>
                    </div>
                    <div class="col-md-2 section-source-wrap">
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
                    <div class="col-md-2 section-channel-type-wrap" style="display:none">
                        <label class="form-label small fw-bold text-muted mb-1">Filter Entity</label>
                        <select class="form-select border-secondary border-opacity-25" name="boxes[{{INDEX}}][channel_type]">
                            <option value="music">Music</option>
                            <option value="video">Video</option>
                            <option value="image">Image</option>
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
<style>.drag-handle { cursor: grab; user-select: none; } .drag-handle:active { cursor: grabbing; } .homepage-section.dragging { opacity: 0.6; }</style>
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
    function toggleMusicSectionVisibility(section) {
        var typeSelect = section.querySelector('.section-type');
        var displaySelect = section.querySelector('select[name*="[display]"]');
        if (!typeSelect) return;
        var v = typeSelect.value;
        var displayVal = displaySelect ? displaySelect.value : 'carousel';
        var showGridSize = (v !== 'html') && displayVal !== 'list' && displayVal !== 'long';
        var htmlWrap = section.querySelector('.section-html-wrap');
        var displayWrap = section.querySelector('.section-display-wrap');
        var gridSizeWrap = section.querySelector('.section-grid-size-wrap');
        var sourceWrap = section.querySelector('.section-source-wrap');
        var limitWrap = section.querySelector('.section-limit-wrap');
        var idsWrap = section.querySelector('.section-ids-wrap');
        var channelWrap = section.querySelector('.section-channel-type-wrap');
        if (htmlWrap) htmlWrap.style.display = (v === 'html') ? 'block' : 'none';
        if (displayWrap) displayWrap.style.display = (v === 'html') ? 'none' : 'block';
        if (gridSizeWrap) gridSizeWrap.style.display = showGridSize ? 'block' : 'none';
        if (sourceWrap) sourceWrap.style.display = (v === 'library' || v === 'html') ? 'none' : 'block';
        if (limitWrap) limitWrap.style.display = (v === 'library' || v === 'html') ? 'none' : 'block';
        if (idsWrap) idsWrap.style.display = (v === 'library' || v === 'html') ? 'none' : 'block';
        if (channelWrap) channelWrap.style.display = (v === 'channel') ? 'block' : 'none';
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
        var removeBtn = section.querySelector('.section-remove');
        if (typeSelect) {
            typeSelect.addEventListener('change', function() { toggleMusicSectionVisibility(section); });
            toggleMusicSectionVisibility(section);
        }
        var displaySelect = section.querySelector('select[name*="[display]"]');
        if (displaySelect) displaySelect.addEventListener('change', function() { toggleMusicSectionVisibility(section); });
        if (removeBtn) removeBtn.addEventListener('click', function() { if (confirm('Remove this section?')) section.remove(); });
        var handle = section.querySelector('.drag-handle');
        if (handle) {
            handle.addEventListener('mousedown', function() { section.classList.add('dragging'); });
            handle.addEventListener('mouseup', function() { section.classList.remove('dragging'); });
        }
    }
    container.querySelectorAll('.homepage-section').forEach(attachSectionEvents);
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
    container.addEventListener('dragend', function() { dragged = null; });
})();
</script>
<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';

