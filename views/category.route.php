<?php
/**
 * /category and /category/:slug — video channels (categories) listing.
 */
if (!defined('in_nia_app')) exit;
$rest = trim($GLOBALS['nia_route_section'] ?? '');
$page_title = 'Channels';
$channels = get_channels('video', 0);
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container-fluid py-4">
    <h1 class="nia-title mb-4">Channels</h1>
    <div class="nia-chips mb-3">
        <a class="nia-chip active" href="<?php echo url('category'); ?>">All</a>
        <a class="nia-chip" href="<?php echo url('videos'); ?>">Videos</a>
        <a class="nia-chip" href="<?php echo url('music'); ?>">Music</a>
    </div>
    <div class="row g-3">
        <?php foreach ($channels as $c) {
            $chan = is_object($c) ? $c : (object) $c;
            $slug = $chan->slug ?? ('channel-' . ($chan->id ?? 0));
            $name = $chan->name ?? '';
            $url = url('videos/browse?category=' . (int) ($chan->id ?? 0));
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?php echo _e($url); ?>" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <span class="material-icons text-muted" style="font-size:2.5rem;">folder</span>
                        <div class="mt-2"><?php echo _e($name); ?></div>
                    </div>
                </a>
            </div>
        <?php } ?>
    </div>
    <?php if (empty($channels)) { ?>
    <p class="text-muted">No channels yet.</p>
    <?php } ?>
</main>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
