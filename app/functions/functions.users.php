<?php
/**
 * Users: vibe_users (name, username, email, avatar, group_id, last login).
 * Groups: admin (1), moderator (2), premium (3), default (4) from vibe_users_groups.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

const USER_GROUP_ADMIN     = 1;
const USER_GROUP_MODERATOR = 2;
const USER_GROUP_PREMIUM   = 3;
const USER_GROUP_DEFAULT   = 4;

/** @return object|null */
function get_user($id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}users WHERE id = ? LIMIT 1", [(int) $id]);
}

/** @return object|null */
function get_user_by_username($username) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}users WHERE username = ? LIMIT 1", [trim($username)]);
}

/** @return object|null */
function get_user_by_email($email) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}users WHERE email = ? LIMIT 1", [trim($email)]);
}

/** @return object|null Current logged-in user row. */
function current_user() {
    $uid = current_user_id();
    return $uid ? get_user($uid) : null;
}

function is_admin($user = null) {
    $user = $user ?? current_user();
    return $user && (int) $user->group_id === USER_GROUP_ADMIN;
}

function is_moderator($user = null) {
    $user = $user ?? current_user();
    return $user && in_array((int) $user->group_id, [USER_GROUP_ADMIN, USER_GROUP_MODERATOR], true);
}

function user_group_name($group_id) {
    global $db;
    $pre = $db->prefix();
    $row = $db->fetch("SELECT name FROM {$pre}users_groups WHERE id = ?", [(int) $group_id]);
    return $row ? $row->name : 'User';
}
