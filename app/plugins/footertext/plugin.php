<?php
/**
 * Footer Text: custom line in footer (copyright, links, disclaimer).
 * Options: footertext_enabled, footertext_content (HTML allowed), footertext_placement (before_copyright, after_copyright, replace_copyright).
 * Hooks: vibe_footer.
 */

if (!defined('in_nia_app')) exit;

add_action('vibe_footer', function () {
    if (get_option('footertext_enabled', '0') !== '1') return;
    $content = get_option('footertext_content', '');
    if ($content === '') return;
    $placement = get_option('footertext_placement', 'after_copyright');
    ?>
    <div class="nia-footertext small text-muted text-center py-2" data-placement="<?php echo _e($placement); ?>">
        <?php echo $content; ?>
    </div>
    <?php
}, 5);

add_action('vibe_plugin_settings', function () {
    $enabled = get_option('footertext_enabled', '0');
    $content = get_option('footertext_content', '');
    $placement = get_option('footertext_placement', 'after_copyright');
    echo '<h6 class="mt-3">Footer Text</h6>';
    echo '<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="footertext_enabled" value="1" ' . ($enabled === '1' ? 'checked' : '') . '><label class="form-check-label">Show custom footer line</label></div>';
    echo '<div class="mb-3"><label class="form-label">Content (HTML allowed)</label><textarea class="form-control" name="footertext_content" rows="3" placeholder="e.g. &lt;p&gt;© 2026 My Site. &lt;a href=\"/privacy\"&gt;Privacy&lt;/a&gt;&lt;/p&gt;">' . _e($content) . '</textarea></div>';
    echo '<div class="mb-3"><label class="form-label">Placement</label><select class="form-select" name="footertext_placement"><option value="before_copyright"' . ($placement === 'before_copyright' ? ' selected' : '') . '>Before site copyright</option><option value="after_copyright"' . ($placement === 'after_copyright' ? ' selected' : '') . '>After site copyright</option><option value="replace_copyright"' . ($placement === 'replace_copyright' ? ' selected' : '') . '>Replace site copyright</option></select></div>';
});

add_action('vibe_plugin_settings_save', function ($post) {
    update_option('footertext_enabled', isset($post['footertext_enabled']) ? '1' : '0');
    if (isset($post['footertext_content'])) {
        update_option('footertext_content', trim($post['footertext_content']));
    }
    if (isset($post['footertext_placement'])) {
        $p = trim($post['footertext_placement']);
        update_option('footertext_placement', in_array($p, ['before_copyright', 'after_copyright', 'replace_copyright'], true) ? $p : 'after_copyright');
    }
});
