<?php
/**
 * Back to Top: floating button to scroll to top.
 * Options: backtotop_enabled, backtotop_position (bottom-right, bottom-left).
 * Hooks: vibe_footer.
 */

if (!defined('in_nia_app')) exit;

add_action('vibe_footer', function () {
    if (get_option('backtotop_enabled', '1') !== '1') return;
    $pos = get_option('backtotop_position', 'bottom-right');
    $right = ($pos === 'bottom-left') ? 'auto' : '1rem';
    $left = ($pos === 'bottom-left') ? '1rem' : 'auto';
    ?>
    <button type="button" id="nia-backtotop" class="btn btn-primary rounded-circle shadow position-fixed border-0 d-none" aria-label="Back to top" style="width:48px;height:48px;bottom:1.5rem;left:<?php echo _e($left); ?>;right:<?php echo _e($right); ?>;z-index:1050;">
        <span class="material-icons">arrow_upward</span>
    </button>
    <script>
    (function(){
        var btn = document.getElementById('nia-backtotop');
        if (!btn) return;
        function toggle() { btn.classList.toggle('d-none', window.scrollY < 300); }
        window.addEventListener('scroll', toggle, { passive: true });
        toggle();
        btn.addEventListener('click', function(){ window.scrollTo({ top: 0, behavior: 'smooth' }); });
    })();
    </script>
    <?php
}, 20);

add_action('vibe_plugin_settings', function () {
    $enabled = get_option('backtotop_enabled', '1');
    $position = get_option('backtotop_position', 'bottom-right');
    echo '<h6 class="mt-3">Back to Top</h6>';
    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="backtotop_enabled" value="1" ' . ($enabled === '1' ? 'checked' : '') . '><label class="form-check-label">Show back-to-top button</label></div>';
    echo '<div class="mb-3"><label class="form-label">Position</label><select class="form-select" name="backtotop_position"><option value="bottom-right"' . ($position === 'bottom-right' ? ' selected' : '') . '>Bottom right</option><option value="bottom-left"' . ($position === 'bottom-left' ? ' selected' : '') . '>Bottom left</option></select></div>';
});

add_action('vibe_plugin_settings_save', function ($post) {
    update_option('backtotop_enabled', isset($post['backtotop_enabled']) ? '1' : '0');
    if (isset($post['backtotop_position'])) {
        $p = trim($post['backtotop_position']);
        update_option('backtotop_position', in_array($p, ['bottom-right', 'bottom-left'], true) ? $p : 'bottom-right');
    }
});
