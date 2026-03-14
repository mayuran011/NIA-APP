<?php
/**
 * Premium hub (/premiumhub/): premium-only content; upgrade CTA if not premium.
 */
if (!defined('in_nia_app')) exit;

$nia_section = $GLOBALS['vibe_route_section'] ?? '';
$page_title = 'Premium Hub';
$allow = premium_allowed();
$is_premium = has_premium();
$upto = premium_upto();

global $db;
$pre = $db->prefix();
$premium_items = [];
if ($allow) {
    $uid = current_user_id();
    $premium_items = $db->fetchAll(
        "SELECT * FROM {$pre}videos WHERE premium = 1 AND (private = 0 OR user_id = ?) AND type IN ('video', 'music') ORDER BY created_at DESC LIMIT 24",
        [$uid]
    );
}

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<style>
.nia-premium-banner {
    background: linear-gradient(135deg, #1e1e2d 0%, #0f0f12 100%);
    border-radius: 1.5rem;
    padding: 3rem 2rem;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.05);
}
.nia-premium-banner::after {
    content: 'PREMIUM';
    position: absolute;
    right: -20px;
    bottom: -20px;
    font-size: 8rem;
    font-weight: 900;
    color: rgba(255, 255, 255, 0.02);
    pointer-events: none;
}
.nia-premium-icon-box {
    width: 64px;
    height: 64px;
    background: var(--pv-primary);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 20px rgba(255, 0, 0, 0.3);
}
</style>

<main class="nia-main container-fluid py-4">
    <div class="nia-premium-banner mb-5 text-center text-md-start">
        <div class="row align-items-center">
            <div class="col-md-2 d-none d-md-flex justify-content-center">
                <div class="nia-premium-icon-box">
                    <span class="material-icons text-white" style="font-size: 2.5rem;">workspace_premium</span>
                </div>
            </div>
            <div class="col-md-7">
                <h1 class="display-5 fw-bold mb-2">Premium Hub</h1>
                <p class="text-white-50 fs-5 mb-0">Discover exclusive content and premium-only media collections.</p>
            </div>
            <div class="col-md-3 text-md-end mt-4 mt-md-0">
                <?php if ($is_premium): ?>
                    <div class="badge bg-success px-3 py-2 rounded-pill"><span class="material-icons align-middle me-1" style="font-size: 1rem;">verified</span> Active Subscriber</div>
                <?php else: ?>
                    <a href="<?php echo url('payment'); ?>" class="btn btn-primary btn-lg px-4 rounded-pill shadow">Upgrade Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!$allow): ?>
        <div class="alert alert-secondary text-center p-5">
            <p class="mb-0">Premium features are currently disabled by the administrator.</p>
        </div>
    <?php elseif ($is_premium): ?>
        <?php if ($premium_items): ?>
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 fw-bold mb-0">Exclusive Feed</h2>
                    <span class="text-muted small"><?php echo count($premium_items); ?> Items found</span>
                </div>
                
                <div class="row g-4">
                    <?php foreach ($premium_items as $v): ?>
                        <div class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="card bg-dark border-secondary border-opacity-10 h-100 rounded-3 overflow-hidden">
                                <a href="<?php echo video_url($v->id, $v->title); ?>" class="position-relative d-block">
                                    <img src="<?php echo _e($v->thumb); ?>" class="card-img-top" alt="" style="aspect-ratio: 16/9; object-fit: cover;">
                                    <span class="position-absolute bottom-0 end-0 m-2 badge bg-primary">PREMIUM <?php echo strtoupper(_e($v->type)); ?></span>
                                </a>
                                <div class="card-body p-3">
                                    <h5 class="card-title h6 fw-bold text-truncate mb-1">
                                        <a href="<?php echo video_url($v->id, $v->title); ?>" class="text-white text-decoration-none"><?php echo _e($v->title); ?></a>
                                    </h5>
                                    <div class="small text-muted d-flex align-items-center">
                                        <span class="material-icons me-1" style="font-size: 0.9rem;">visibility</span> <?php echo _e($v->views); ?> views
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="text-center py-5 border rounded-4 border-dashed">
                <span class="material-icons text-muted mb-2" style="font-size: 4rem;">movie_filter</span>
                <p class="text-muted">No premium content available at this moment. Check back soon!</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card bg-dark border-0 h-100 p-4 text-center">
                    <span class="material-icons text-primary mb-3" style="font-size: 3rem;">ads_click</span>
                    <h3 class="h5 fw-bold">No Ads</h3>
                    <p class="small text-muted">Enjoy your favorite content without any interruptions.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark border-0 h-100 p-4 text-center">
                    <span class="material-icons text-primary mb-3" style="font-size: 3rem;">high_quality</span>
                    <h3 class="h5 fw-bold">Exclusive Access</h3>
                    <p class="small text-muted">Watch premium-only videos and listen to master-quality audio.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark border-0 h-100 p-4 text-center">
                    <span class="material-icons text-primary mb-3" style="font-size: 3rem;">download</span>
                    <h3 class="h5 fw-bold">Offline Downloads</h3>
                    <p class="small text-muted">Download any media and watch it anytime, anywhere.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center py-4 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25">
            <h4 class="fw-bold mb-3">Ready to join the elite?</h4>
            <a href="<?php echo url('payment'); ?>" class="btn btn-primary px-5 py-2 rounded-pill shadow fw-bold">View Membership Plans</a>
        </div>
    <?php endif; ?>
</main>

<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
