<?php
/**
 * Social Share: share buttons (Twitter, Facebook, WhatsApp, Copy link) on watch/listen page.
 * Options: socialshare_enabled, socialshare_buttons (comma-separated: twitter,facebook,whatsapp,copy).
 * Hooks: vibe_after_player.
 */

if (!defined('in_nia_app')) exit;

add_action('vibe_after_player', function ($container_id, $video, $opts) {
    if (get_option('socialshare_enabled', '1') !== '1' || !$video || empty($video->id)) return;
    $buttons = array_map('trim', explode(',', get_option('socialshare_buttons', 'twitter,facebook,whatsapp,copy')));
    $buttons = array_filter($buttons);
    if (empty($buttons)) return;
    $title = isset($video->title) ? $video->title : '';
    if (!empty($video->type) && $video->type === 'music' && function_exists('media_play_url')) {
        $watch_url = media_play_url($video->id, 'music', $title);
    } else {
        $watch_url = function_exists('watch_url') ? watch_url($video->id, $title) : (rtrim(SITE_URL, '/') . '/watch/' . (int) $video->id);
    }
    $enc_url = rawurlencode($watch_url);
    $enc_title = rawurlencode($title);
    $enc_text = rawurlencode($title . ' ' . $watch_url);
    ?>
    <div class="nia-social-share mt-2 mb-2 d-flex flex-wrap gap-2 align-items-center">
        <span class="small text-muted me-1">Share:</span>
        <?php if (in_array('twitter', $buttons, true)) { ?>
        <a href="https://twitter.com/intent/tweet?url=<?php echo $enc_url; ?>&text=<?php echo $enc_title; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm" aria-label="Share on Twitter"><span class="material-icons" style="font-size:1.1rem;">share</span> Twitter</a>
        <?php } ?>
        <?php if (in_array('facebook', $buttons, true)) { ?>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $enc_url; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm" aria-label="Share on Facebook"><span class="material-icons" style="font-size:1.1rem;">share</span> Facebook</a>
        <?php } ?>
        <?php if (in_array('whatsapp', $buttons, true)) { ?>
        <a href="https://wa.me/?text=<?php echo $enc_text; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm" aria-label="Share on WhatsApp"><span class="material-icons" style="font-size:1.1rem;">share</span> WhatsApp</a>
        <?php } ?>
        <?php if (in_array('copy', $buttons, true)) { ?>
        <button type="button" class="btn btn-outline-secondary btn-sm nia-share-copy" data-url="<?php echo _e($watch_url); ?>" aria-label="Copy link"><span class="material-icons" style="font-size:1.1rem;">link</span> Copy link</button>
        <?php } ?>
    </div>
    <script>
    (function(){
        var btn = document.querySelector('.nia-share-copy');
        if (btn) btn.addEventListener('click', function(){
            var url = this.getAttribute('data-url') || '';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function(){ btn.textContent = 'Copied!'; setTimeout(function(){ btn.innerHTML = '<span class="material-icons" style="font-size:1.1rem;">link</span> Copy link'; }, 2000); });
            } else { var i = document.createElement('input'); i.value = url; document.body.appendChild(i); i.select(); document.execCommand('copy'); document.body.removeChild(i); btn.textContent = 'Copied!'; setTimeout(function(){ btn.innerHTML = '<span class="material-icons" style="font-size:1.1rem;">link</span> Copy link'; }, 2000); }
        });
    })();
    </script>
    <?php
}, 10, 3);

add_action('vibe_plugin_settings', function () {
    $enabled = get_option('socialshare_enabled', '1');
    $buttons = get_option('socialshare_buttons', 'twitter,facebook,whatsapp,copy');
    echo '<h6 class="mt-3">Social Share</h6>';
    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="socialshare_enabled" value="1" ' . ($enabled === '1' ? 'checked' : '') . '><label class="form-check-label">Show share buttons on video/music page</label></div>';
    echo '<div class="mb-3"><label class="form-label">Buttons (comma-separated)</label><input type="text" class="form-control" name="socialshare_buttons" value="' . _e($buttons) . '" placeholder="twitter,facebook,whatsapp,copy"></div>';
});

add_action('vibe_plugin_settings_save', function ($post) {
    update_option('socialshare_enabled', isset($post['socialshare_enabled']) ? '1' : '0');
    if (isset($post['socialshare_buttons'])) {
        update_option('socialshare_buttons', trim($post['socialshare_buttons']) ?: 'twitter,facebook,whatsapp,copy');
    }
});
