<?php
/**
 * Messages inbox: list of conversations (/msg/).
 */
if (!defined('in_nia_app')) exit;
if (!is_logged()) {
    redirect(url('login'));
}
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$page_title = 'Inbox';
$conversations = get_conversations();

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<main class="nia-main container py-4" style="max-width: 800px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="nia-title h4 mb-0">Messages</h1>
        <div class="text-muted small"><?php echo count($conversations); ?> conversations</div>
    </div>

    <div class="card bg-dark border-secondary border-opacity-10 rounded-4 overflow-hidden shadow-sm">
        <div class="list-group list-group-flush">
            <?php if ($conversations) { ?>
                <?php foreach ($conversations as $c) {
                    $other = $c->other_user ?? null;
                    if (!$other) continue;
                    $name = _e($other->name ?? $other->username);
                    $avatar = !empty($other->avatar) ? $other->avatar : '';
                    if ($avatar !== '' && strpos($avatar, 'http') !== 0) { $avatar = rtrim(SITE_URL, '/') . '/' . ltrim($avatar, '/'); }
                    
                    $last = $c->last_message ?? null;
                    $last_body = $last ? _e(mb_substr($last->body, 0, 80)) . (mb_strlen($last->body) > 80 ? '…' : '') : 'No messages yet';
                    $last_time = $last ? (function_exists('time_ago') ? time_ago($last->created_at) : date('M j', strtotime($last->created_at))) : '';
                ?>
                <a href="<?php echo conversation_url($c->id); ?>" class="list-group-item list-group-item-action bg-transparent border-secondary border-opacity-10 py-3 px-4 d-flex align-items-center gap-3">
                    <?php if ($avatar): ?>
                        <img src="<?php echo $avatar; ?>" alt="" class="rounded-circle shadow-sm" width="56" height="56" style="object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white shadow-sm fw-bold" width="56" height="56" style="min-width:56px; height:56px; font-size: 1.2rem;">
                            <?php echo strtoupper(substr($name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 fw-bold text-white"><?php echo $name; ?></h6>
                            <span class="small text-muted"><?php echo $last_time; ?></span>
                        </div>
                        <div class="small text-muted text-truncate"><?php echo $last_body; ?></div>
                    </div>
                    
                    <span class="material-icons text-muted opacity-25" style="font-size: 1.2rem;">chevron_right</span>
                </a>
                <?php } ?>
            <?php } else { ?>
                <div class="p-5 text-center text-muted">
                    <span class="material-icons mb-3" style="font-size: 4rem; opacity: 0.2;">mail_outline</span>
                    <p class="mb-0">Your inbox is empty.</p>
                    <p class="small text-muted">Start a conversation from any user's profile page.</p>
                </div>
            <?php } ?>
        </div>
    </div>
</main>

<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
