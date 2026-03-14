<?php
// Shared template + drag/drop JS used by builder pages (video, image, music, article).
?>
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
                        <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[{{INDEX}}][title]" value="" placeholder="Section title">
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

