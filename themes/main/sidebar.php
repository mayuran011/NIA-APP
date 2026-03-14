<?php
if (!defined('in_nia_app')) exit;
$uid = current_user_id();
$current_path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($base !== '' && strpos($current_path, $base) === 0) {
    $current_path = substr($current_path, strlen($base)) ?: '/';
}
$current_path = '/' . trim($current_path, '/');
if ($current_path === '') $current_path = '/';

$subs = [];
if (function_exists('get_channels')) {
    $subs = array_slice(get_channels('video', 0), 0, 8);
}
?>
<aside class="nia-sidebar" id="niaSidebar">
    <nav class="nia-sidebar-nav">
        <div class="nia-sidebar-section">
            <div class="nia-sidebar-heading">Main</div>
            <a class="nia-sidebar-item <?php echo ($current_path === '/' || $current_path === '') ? 'active' : ''; ?>" href="<?php echo url(); ?>">
                <span class="material-icons">home</span>
                <span>Home</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/show') !== false ? 'active' : ''; ?>" href="<?php echo url('show'); ?>">
                <span class="material-icons">explore</span>
                <span>Explore</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/music') !== false ? 'active' : ''; ?>" href="<?php echo url('music/browse'); ?>">
                <span class="material-icons">music_note</span>
                <span>Music</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/following') !== false ? 'active' : ''; ?>" href="<?php echo url('following'); ?>">
                <span class="material-icons">subscriptions</span>
                <span>Following</span>
            </a>
        </div>
        <?php if (!empty($subs)) { ?>
        <div class="nia-sidebar-section">
            <div class="nia-sidebar-heading">Subscriptions</div>
            <?php foreach ($subs as $ch) {
                $chLink = url((get_option('channel-seo-url') ?: 'category') . '/' . ($ch->slug ?? ''));
                $chThumb = !empty($ch->thumb) ? $ch->thumb : '';
                if ($chThumb !== '' && strpos($chThumb, 'http') !== 0) { $chThumb = rtrim(SITE_URL, '/') . '/' . ltrim($chThumb, '/'); }
            ?>
            <a class="nia-sidebar-item nia-sidebar-channel" href="<?php echo _e($chLink); ?>">
                <?php if ($chThumb) { ?><img src="<?php echo _e($chThumb); ?>" alt="" class="nia-sidebar-channel-avatar"><?php } else { ?><span class="nia-sidebar-channel-avatar nia-sidebar-channel-initial"><?php echo _e(strtoupper(substr($ch->name ?? '?', 0, 1))); ?></span><?php } ?>
                <span class="text-truncate"><?php echo _e($ch->name ?? ''); ?></span>
            </a>
            <?php } ?>
        </div>
        <?php } ?>
        <?php if ($uid) { ?>
        <div class="nia-sidebar-section">
            <div class="nia-sidebar-heading">Library</div>
            <a class="nia-sidebar-item <?php echo ($current_path === '/me') ? 'active' : ''; ?>" href="<?php echo url('me'); ?>">
                <span class="material-icons">folder</span>
                <span>Library</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/me/history') !== false ? 'active' : ''; ?>" href="<?php echo url('me/history'); ?>">
                <span class="material-icons">history</span>
                <span>History</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/me/playlists') !== false ? 'active' : ''; ?>" href="<?php echo url('me/playlists'); ?>">
                <span class="material-icons">playlist_play</span>
                <span>Playlists</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/me/later') !== false ? 'active' : ''; ?>" href="<?php echo url('me/later'); ?>">
                <span class="material-icons">schedule</span>
                <span>Watch later</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/me/likes') !== false ? 'active' : ''; ?>" href="<?php echo url('me/likes'); ?>">
                <span class="material-icons">thumb_up</span>
                <span>Liked videos</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/me/auto-import') !== false ? 'active' : ''; ?>" href="<?php echo url('me/auto-import'); ?>">
                <span class="material-icons">sync</span>
                <span>Auto-import</span>
            </a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/dashboard') !== false ? 'active' : ''; ?>" href="<?php echo url('dashboard'); ?>">
                <span class="material-icons">video_library</span>
                <span>Your videos</span>
            </a>
        </div>
        <?php } ?>
        <div class="nia-sidebar-section">
            <div class="nia-sidebar-heading">Browse</div>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/videos') !== false ? 'active' : ''; ?>" href="<?php echo url('videos/browse'); ?>"><span class="material-icons">videocam</span><span>Videos</span></a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/images') !== false ? 'active' : ''; ?>" href="<?php echo url('images/browse'); ?>"><span class="material-icons">image</span><span>Images</span></a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/category') !== false ? 'active' : ''; ?>" href="<?php echo url('category'); ?>"><span class="material-icons">folder</span><span>Channels</span></a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/lists') !== false ? 'active' : ''; ?>" href="<?php echo url('lists'); ?>"><span class="material-icons">playlist_play</span><span>Lists</span></a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/blog') !== false ? 'active' : ''; ?>" href="<?php echo url('blog'); ?>"><span class="material-icons">article</span><span>Blog</span></a>
        </div>
        <?php if ($uid) { ?>
        <div class="nia-sidebar-section">
            <div class="nia-sidebar-heading">Account</div>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/premiumhub') !== false ? 'active' : ''; ?>" href="<?php echo url('premiumhub'); ?>"><span class="material-icons">workspace_premium</span><span>Premium</span></a>
            <a class="nia-sidebar-item <?php echo strpos($current_path, '/msg') !== false ? 'active' : ''; ?>" href="<?php echo url('msg'); ?>"><span class="material-icons">mail</span><span>Messages</span></a>
        </div>
        <?php } ?>
    </nav>
</aside>
