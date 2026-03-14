<?php
/**
 * Admin: Blog page builder (layout for /blog).
 */
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$box_types = [
    'article' => 'Blog posts',
    'video'   => 'Videos',
    'music'   => 'Music',
    'image'   => 'Images',
    'html'    => 'HTML block',
];
$sources = [
    'latest'   => 'Latest blog posts',
    'featured' => 'Featured (by category)',
    'ids'      => 'Specific post IDs',
];
$option_key = 'articles_page_boxes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_boxes') {
        $raw = isset($_POST['boxes']) && is_array($_POST['boxes']) ? $_POST['boxes'] : [];
        $boxes = [];
        foreach ($raw as $b) {
            $type = isset($b['type']) ? trim($b['type']) : 'article';
            if (!isset($box_types[$type])) continue;
            $title = isset($b['title']) ? trim($b['title']) : ucfirst($type);
            $source = isset($b['source']) ? trim($b['source']) : 'latest';
            $limit = isset($b['limit']) ? (int) $b['limit'] : 10;
            $ids = [];
            if (!empty($b['ids']) && is_string($b['ids'])) {
                $ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $b['ids'])));
            }
            $content = isset($b['content']) ? trim($b['content']) : '';
            $box = [
                'type'   => $type,
                'title'  => $title,
                'source' => $source,
                'limit'  => $limit,
                'ids'    => array_values($ids),
            ];
            if ($type === 'html') $box['content'] = $content;
            $boxes[] = $box;
        }
        update_option($option_key, json_encode($boxes), 1);
        redirect(admin_url('article-page'));
    }
}

$admin_title = 'Blog page builder';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
$boxes = get_option($option_key, '[]');
$boxes = is_string($boxes) ? json_decode($boxes, true) : $boxes;
if (!is_array($boxes)) $boxes = [];
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold d-flex align-items-center">
            <span class="material-icons text-primary me-2">article</span> Blog Page Builder
        </h5>
        <a href="<?php echo url('blog'); ?>" class="btn btn-sm btn-light border d-inline-flex align-items-center shadow-sm" target="_blank">
            <span class="material-icons me-1" style="font-size: 16px;">open_in_new</span> View /blog
        </a>
    </div>
    <div class="card-body bg-light">
        <p class="text-muted small mb-4 d-flex align-items-center">
            <span class="material-icons text-info me-2" style="font-size: 18px;">info</span>
            Build hero rows and sections for your Blog page (Latest posts, by category, featured blocks, custom HTML).
        </p>
        <form method="post">
            <input type="hidden" name="action" value="save_boxes">
            <div id="homepage-sections" class="mb-4 d-flex flex-column gap-3">
                <?php foreach ($boxes as $i => $box) {
                    $type = isset($box['type']) ? $box['type'] : 'article';
                    $title = isset($box['title']) ? $box['title'] : ucfirst($type);
                    $source = isset($box['source']) ? $box['source'] : 'latest';
                    $limit = isset($box['limit']) ? (int) $box['limit'] : 10;
                    $ids = isset($box['ids']) && is_array($box['ids']) ? implode(', ', $box['ids']) : '';
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
                                    <input type="text" class="form-control border-secondary border-opacity-25" name="boxes[<?php echo $i; ?>][title]" value="<?php echo _e($title); ?>" placeholder="e.g. Latest stories">
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

<?php
include __DIR__ . DIRECTORY_SEPARATOR . 'video-page-footer.inc.php';

