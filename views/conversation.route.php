<?php
/**
 * Single conversation thread (/conversation/:id).
 */
if (!defined('in_nia_app')) exit;
if (!is_logged()) {
    redirect(url('login'));
}
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$conversation_id = (int) preg_replace('/[^0-9].*$/', '', $nia_section);
if ($conversation_id <= 0) {
    redirect(url('msg'));
}
$conv = get_conversation($conversation_id);
if (!$conv) {
    redirect(url('msg'));
}

$messages = get_conversation_messages($conversation_id, 100, 0);
$other = $conv->other_user;
$other_name = $other ? _e($other->name ?? $other->username) : 'User';
$other_avatar = $other && !empty($other->avatar) ? $other->avatar : '';
if ($other_avatar !== '' && strpos($other_avatar, 'http') !== 0) { $other_avatar = rtrim(SITE_URL, '/') . '/' . ltrim($other_avatar, '/'); }

$page_title = 'Chat with ' . $other_name;

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<style>
.nia-chat-container {
    height: calc(100vh - 120px);
    max-width: 900px;
    margin: 0 auto;
    background: #0f0f0f;
    border: 1px solid var(--pv-border);
    border-radius: 1rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.nia-chat-header {
    background: #1a1a1a;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--pv-border);
}
.nia-chat-messages {
    flex-grow: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.nia-chat-input-area {
    padding: 1rem 1.5rem;
    background: #1a1a1a;
    border-top: 1px solid var(--pv-border);
}
.msg-bubble {
    max-width: 75%;
    padding: 0.75rem 1rem;
    border-radius: 1.25rem;
    position: relative;
    font-size: 0.95rem;
}
.msg-me {
    align-self: flex-end;
    background: var(--pv-primary);
    color: white;
    border-bottom-right-radius: 0.25rem;
}
.msg-other {
    align-self: flex-start;
    background: #2a2a2a;
    color: #efefef;
    border-bottom-left-radius: 0.25rem;
}
.msg-time {
    font-size: 0.7rem;
    opacity: 0.6;
    margin-top: 0.25rem;
}
</style>

<main class="nia-main container py-3">
    <div class="nia-chat-container shadow-lg">
        <div class="nia-chat-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <a href="<?php echo url('msg'); ?>" class="btn btn-link link-light p-0 me-3">
                    <span class="material-icons">arrow_back</span>
                </a>
                <?php if ($other_avatar): ?>
                    <img src="<?php echo $other_avatar; ?>" class="rounded-circle me-3" style="width:40px;height:40px;object-fit:cover;">
                <?php else: ?>
                    <div class="rounded-circle me-3 bg-secondary d-flex align-items-center justify-content-center text-white" style="width:40px;height:40px;"><?php echo strtoupper(substr($other_name, 0, 1)); ?></div>
                <?php endif; ?>
                <div>
                    <h5 class="mb-0 fw-bold"><?php echo $other_name; ?></h5>
                    <small class="text-success d-flex align-items-center"><span class="material-icons me-1" style="font-size:0.8rem;">fiber_manual_record</span> Online</small>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-link link-light" data-bs-toggle="dropdown"><span class="material-icons">more_vert</span></button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                    <li><a class="dropdown-item" href="<?php echo url('user/' . ($other->id ?? '')); ?>">View Profile</a></li>
                </ul>
            </div>
        </div>

        <div class="nia-chat-messages" id="chat-messages">
            <?php foreach ($messages as $m): $is_me = (int)$m->user_id === current_user_id(); ?>
                <div class="msg-bubble <?php echo $is_me ? 'msg-me shadow-sm' : 'msg-other'; ?>">
                    <div class="msg-text"><?php echo nl2br(_e($m->body)); ?></div>
                    <div class="msg-time <?php echo $is_me ? 'text-white' : 'text-muted'; ?> text-end">
                        <?php echo date('g:i A', strtotime($m->created_at)); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($messages)): ?>
                <div class="text-center py-5 text-muted opacity-50" id="no-msgs">
                    <span class="material-icons d-block mb-2" style="font-size:3rem;">forum</span>
                    No messages yet. Start the conversation!
                </div>
            <?php endif; ?>
        </div>

        <div class="nia-chat-input-area">
            <form id="chat-form" class="input-group">
                <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
                <input type="text" name="body" class="form-control bg-dark border-0 text-white p-3 rounded-start-pill" placeholder="Type a message..." aria-label="Type message" required autocomplete="off">
                <button type="submit" class="btn btn-primary px-4 rounded-end-pill d-flex align-items-center">
                    <span class="material-icons">send</span>
                </button>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var box = document.getElementById('chat-messages');
    var form = document.getElementById('chat-form');
    var noMsgs = document.getElementById('no-msgs');
    var url = '<?php echo url('app/ajax/sendMessage.php'); ?>';

    box.scrollTop = box.scrollHeight;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var input = form.querySelector('input[name="body"]');
        var val = input.value.trim();
        if(!val) return;

        var fd = new FormData(form);
        input.value = '';
        if(noMsgs) noMsgs.style.display = 'none';

        fetch(url, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if(data.ok) {
                    var div = document.createElement('div');
                    div.className = 'msg-bubble msg-me shadow-sm';
                    div.innerHTML = '<div class="msg-text">' + data.msg.body.replace(/\n/g, '<br>') + '</div>' + 
                                    '<div class="msg-time text-white text-end">' + data.msg.time.split(',')[1].trim() + '</div>';
                    box.appendChild(div);
                    box.scrollTop = box.scrollHeight;
                }
            });
    });
});
</script>

<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';
