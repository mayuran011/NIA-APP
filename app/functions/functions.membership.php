<?php
/**
 * Membership Plans & Billing foundation.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/**
 * Get all active membership plans.
 */
function get_membership_plans($active_only = true) {
    global $db;
    $pre = $db->prefix();
    $sql = "SELECT * FROM {$pre}membership_plans" . ($active_only ? " WHERE is_active = 1" : "") . " ORDER BY price ASC";
    return $db->fetchAll($sql);
}

/**
 * Get a specific plan by ID.
 */
function get_membership_plan($id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}membership_plans WHERE id = ?", [(int) $id]);
}

/**
 * Get user's active subscription if any.
 */
function get_user_subscription($user_id = null) {
    global $db;
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return null;
    $pre = $db->prefix();
    return $db->fetch(
        "SELECT s.*, p.title as plan_title 
         FROM {$pre}user_subscriptions s 
         JOIN {$pre}membership_plans p ON p.id = s.plan_id 
         WHERE s.user_id = ? AND s.status = 'active' AND s.end_date > NOW() 
         ORDER BY s.end_date DESC LIMIT 1", 
        [$user_id]
    );
}

/**
 * Create a new subscription for a user.
 */
function create_subscription($user_id, $plan_id, $payment_id = '') {
    global $db;
    $plan = get_membership_plan($plan_id);
    if (!$plan) return false;

    $start = date('Y-m-d H:i:s');
    $duration = (int) $plan->duration;
    // 0 duration means lifetime (set to 100 years out)
    $end = ($duration === 0) ? date('Y-m-d H:i:s', strtotime('+100 years')) : date('Y-m-d H:i:s', strtotime("+$duration days"));

    $pre = $db->prefix();
    // Deactivate old subscriptions
    $db->query("UPDATE {$pre}user_subscriptions SET status = 'expired' WHERE user_id = ? AND status = 'active'", [(int) $user_id]);
    
    $db->query(
        "INSERT INTO {$pre}user_subscriptions (user_id, plan_id, start_date, end_date, status, payment_id) VALUES (?, ?, ?, ?, 'active', ?)",
        [(int) $user_id, (int) $plan_id, $start, $end, $payment_id]
    );

    // Update user record (legacy compatibility)
    if (function_exists('set_premium_upto')) {
        set_premium_upto($user_id, $end);
    }
    
    return true;
}
