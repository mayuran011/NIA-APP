<?php
/**
 * Cookiesfree: cookie/consent banner.
 * Options: cookiesfree_enabled, cookiesfree_message, cookiesfree_accept.
 * Admin: Settings → Plugin settings (vibe_plugin_settings).
 */

if (!defined('in_nia_app')) exit;

add_action('vibe_header', function () {
    if (get_option('cookiesfree_enabled', '0') !== '1') return;
    $message = get_option('cookiesfree_message', 'We use cookies to improve your experience. By continuing you accept our use of cookies.');
    $accept = get_option('cookiesfree_accept', 'Accept');
    $cookie_name = 'vibe_cookies_consent';
    $consented = isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] === '1';
    if ($consented) return;
    ?>
    <div id="nia-cookiesfree-banner" class="position-fixed bottom-0 start-0 end-0 p-3 bg-dark border-top border-secondary" style="z-index:1060; box-shadow:0 -4px 20px rgba(0,0,0,.3);">
        <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2">
            <p class="mb-0 small text-muted"><?php echo _e($message); ?></p>
            <button type="button" class="btn btn-primary btn-sm" id="nia-cookiesfree-accept"><?php echo _e($accept); ?></button>
        </div>
    </div>
    <script>
    (function(){
        var banner = document.getElementById('nia-cookiesfree-banner');
        var btn = document.getElementById('nia-cookiesfree-accept');
        if (!banner || !btn) return;
        btn.addEventListener('click', function(){
            document.cookie = 'vibe_cookies_consent=1; path=/; max-age=31536000; SameSite=Lax';
            banner.style.display = 'none';
        });
    })();
    </script>
    <?php
}, 5);

add_action('vibe_plugin_settings', function () {
    $enabled = get_option('cookiesfree_enabled', '0');
    $message = get_option('cookiesfree_message', 'We use cookies to improve your experience. By continuing you accept our use of cookies.');
    $accept = get_option('cookiesfree_accept', 'Accept');
    echo '<h6 class="mt-3">Cookiesfree (cookie consent)</h6>';
    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="cookiesfree_enabled" value="1" ' . ($enabled === '1' ? 'checked' : '') . '><label class="form-check-label">Show cookie consent banner</label></div>';
    echo '<div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="cookiesfree_message" rows="2">' . _e($message) . '</textarea></div>';
    echo '<div class="mb-3"><label class="form-label">Accept button text</label><input type="text" class="form-control" name="cookiesfree_accept" value="' . _e($accept) . '"></div>';
});

add_action('vibe_plugin_settings_save', function ($post) {
    update_option('cookiesfree_enabled', isset($post['cookiesfree_enabled']) ? '1' : '0');
    if (isset($post['cookiesfree_message'])) {
        update_option('cookiesfree_message', trim($post['cookiesfree_message']) ?: 'We use cookies to improve your experience.');
    }
    if (isset($post['cookiesfree_accept'])) {
        update_option('cookiesfree_accept', trim($post['cookiesfree_accept']) ?: 'Accept');
    }
});
