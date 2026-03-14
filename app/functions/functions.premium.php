<?php
/**
 * Premium: has_premium(), premium_upto(), premium group; options allowpremium, currency, price.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/**
 * Whether premium feature is enabled (site option).
 */
function premium_allowed() {
    return (bool) get_option('allowpremium', 0);
}

/**
 * Premium price (e.g. "9.99"); from options.
 */
function premium_price() {
    return get_option('price', '9.99');
}

/**
 * Currency code (e.g. USD); from options or config.
 */
function premium_currency() {
    $c = get_option('currency', '');
    if ($c !== '' && $c !== null) return $c;
    return defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'USD';
}

/**
 * Whether the user has active premium (group or premium_upto in future).
 * @param int|null $user_id Default current user.
 * @return bool
 */
function has_premium($user_id = null) {
    if (!premium_allowed()) return false;
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return false;
    $user = get_user($user_id);
    if (!$user) return false;
    if ((int) $user->group_id === USER_GROUP_ADMIN || (int) $user->group_id === USER_GROUP_MODERATOR) return true;
    if ((int) $user->group_id === USER_GROUP_PREMIUM) return true;
    $upto = premium_upto($user_id);
    return $upto !== null && strtotime($upto) > time();
}

/**
 * Premium valid until date (from user.premium_upto or null).
 * @param int|null $user_id
 * @return string|null Y-m-d H:i:s or null
 */
function premium_upto($user_id = null) {
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return null;
    $user = get_user($user_id);
    if (!$user || empty($user->premium_upto)) return null;
    return $user->premium_upto;
}

/**
 * Set premium_upto for user (e.g. after successful payment).
 * @param int    $user_id
 * @param string $until Y-m-d H:i:s or relative like +1 month
 * @return bool
 */
function set_premium_upto($user_id, $until) {
    global $db;
    $user_id = (int) $user_id;
    if ($user_id <= 0) return false;
    $ts = strtotime($until);
    if ($ts === false) return false;
    $pre = $db->prefix();
    $db->query("UPDATE {$pre}users SET premium_upto = ? WHERE id = ?", [date('Y-m-d H:i:s', $ts), $user_id]);
    return true;
}

/**
 * Check if content (video row) is premium-only and user has access.
 */
function can_access_premium_content($video_or_row, $user_id = null) {
    $premium_flag = isset($video_or_row->premium) ? (int) $video_or_row->premium : 0;
    if ($premium_flag === 0) return true;
    return has_premium($user_id);
}

/**
 * Whether the user can download video/audio (MP4/MP3).
 * Admin and moderator always have access when download is enabled.
 * @param int|null $user_id Default current user.
 * @return bool
 */
function can_download_media($user_id = null) {
    if ((int) get_option('download_allowed', '0') !== 1) return false;
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return false;
    $user = get_user($user_id);
    if ($user && in_array((int) $user->group_id, [1, 2], true)) return true;
    if ((int) get_option('download_premium_only', '0') !== 1) return true;
    return has_premium($user_id);
}
