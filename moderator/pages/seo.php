<?php
if (!defined('in_nia_app')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_seo') {
        update_option('video-seo-url', isset($_POST['video_seo_url']) ? trim($_POST['video_seo_url']) : '');
        update_option('image-seo-url', isset($_POST['image_seo_url']) ? trim($_POST['image_seo_url']) : '');
        update_option('profile-seo-url', isset($_POST['profile_seo_url']) ? trim($_POST['profile_seo_url']) : '');
        update_option('channel-seo-url', isset($_POST['channel_seo_url']) ? trim($_POST['channel_seo_url']) : '');
        update_option('page-seo-url', isset($_POST['page_seo_url']) ? trim($_POST['page_seo_url']) : '');
        update_option('article-seo-url', isset($_POST['article_seo_url']) ? trim($_POST['article_seo_url']) : '');
        update_option('meta_description', isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '');
        update_option('meta_keywords', isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '');
        redirect(admin_url('seo'));
    }
}

$admin_title = 'SEO / SEF';
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 d-flex align-items-center"><span class="material-icons text-primary me-2">link</span> SEO & Permalinks</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_seo">
                    
                    <h6 class="fw-bold mb-3 text-secondary">URL Permalinks</h6>
                    <p class="text-muted small mb-4">Customize the first segment of your dynamic URLs. (e.g. yoursite.com/<strong>video</strong>/id) Leave blank to use default.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Video URLs</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">/</span>
                                <input type="text" class="form-control" name="video_seo_url" value="<?php echo _e(get_option('video-seo-url', '')); ?>" placeholder="video">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Image URLs</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">/</span>
                                <input type="text" class="form-control" name="image_seo_url" value="<?php echo _e(get_option('image-seo-url', '')); ?>" placeholder="image">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">User Profiles</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">/</span>
                                <input type="text" class="form-control" name="profile_seo_url" value="<?php echo _e(get_option('profile-seo-url', '')); ?>" placeholder="profile">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Channels / Categories</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">/</span>
                                <input type="text" class="form-control" name="channel_seo_url" value="<?php echo _e(get_option('channel-seo-url', '')); ?>" placeholder="category">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Static Pages</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">/</span>
                                <input type="text" class="form-control" name="page_seo_url" value="<?php echo _e(get_option('page-seo-url', '')); ?>" placeholder="read">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Blog posts</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">/</span>
                                <input type="text" class="form-control" name="article_seo_url" value="<?php echo _e(get_option('article-seo-url', '')); ?>" placeholder="read">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3 text-secondary">Global Meta Data</h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Default Meta Description</label>
                        <textarea class="form-control" name="meta_description" rows="3" placeholder="A brief description of your site used by search engines..."><?php echo _e(get_option('meta_description', '')); ?></textarea>
                        <div class="form-text">Keep it between 150-160 characters for best SEO results.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Default Meta Keywords</label>
                        <input type="text" class="form-control" name="meta_keywords" value="<?php echo _e(get_option('meta_keywords', '')); ?>" placeholder="video, music, sharing, media">
                        <div class="form-text">Comma separated list of keywords relevant to your site.</div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center px-4 py-2"><span class="material-icons me-1">save</span> Save SEO Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body">
                <h6 class="fw-bold d-flex align-items-center mb-3"><span class="material-icons text-info me-2">lightbulb</span> SEO Tips</h6>
                <p class="small text-muted mb-2"><strong>Permalinks:</strong> Changing permalinks can break old links if search engines have already indexed them. Make sure to only change these before launching your site publicly.</p>
                <p class="small text-muted mb-0"><strong>Meta Data:</strong> Fill out your site's global meta description as it's the primary fallback if an individual video or page lacks its own description.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold d-flex align-items-center"><span class="material-icons text-success me-2">auto_awesome</span> SEO Suite Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo url('seo-suite?action=sitemap'); ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" target="_blank">
                        <span class="material-icons me-2" style="font-size: 16px;">xml</span> View XML Sitemap
                    </a>
                    <a href="<?php echo url('seo-suite?action=robots'); ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center" target="_blank">
                        <span class="material-icons me-2" style="font-size: 16px;">precision_manufacturing</span> View robots.txt
                    </a>
                </div>
                <div class="mt-3 small text-muted">
                    <span class="material-icons align-middle" style="font-size: 14px;">info</span> These links are dynamically generated.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
