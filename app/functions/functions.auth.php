<?php
/**
 * Auth: email/password (login, register, forgot password).
 * Session + cookie; COOKIEKEY, SECRETSALT from config or options.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

function auth_cookie_key() {
    return get_option('COOKIEKEY', defined('COOKIEKEY') ? COOKIEKEY : 'vibe-cookie');
}

function auth_secret_salt() {
    return get_option('SECRETSALT', defined('SECRETSALT') ? SECRETSALT : 'vibe-salt');
}

/**
 * Login by email + password. Sets session and optional cookie.
 * @return array [ 'ok' => bool, 'error' => string, 'user' => object|null ]
 */
function auth_login($email, $password) {
    global $db;
    $user = get_user_by_email(trim($email));
    if (!$user) {
        return ['ok' => false, 'error' => 'invalid_credentials', 'user' => null];
    }
    $hash = $user->password ?? '';
    if (!password_verify($password, $hash)) {
        return ['ok' => false, 'error' => 'invalid_credentials', 'user' => null];
    }
    $_SESSION['uid'] = (int) $user->id;
    $pre = $db->prefix();
    $db->query("UPDATE {$pre}users SET last_login = NOW() WHERE id = ?", [(int) $user->id]);
    return ['ok' => true, 'error' => null, 'user' => $user];
}

/**
 * Register: username, name, email, password. Creates user in group default.
 * @return array [ 'ok' => bool, 'error' => string, 'user' => object|null ]
 */
function auth_register($username, $name, $email, $password) {
    global $db;
    $username = trim(preg_replace('/[^a-zA-Z0-9_\-]/', '', $username));
    $name = trim($name);
    $email = trim($email);
    if (strlen($username) < 2) {
        return ['ok' => false, 'error' => 'username_invalid', 'user' => null];
    }
    if (get_user_by_username($username)) {
        return ['ok' => false, 'error' => 'username_taken', 'user' => null];
    }
    if (get_user_by_email($email)) {
        return ['ok' => false, 'error' => 'email_taken', 'user' => null];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'password_short', 'user' => null];
    }
    $pre = $db->prefix();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->query(
        "INSERT INTO {$pre}users (group_id, username, name, email, password) VALUES (?, ?, ?, ?, ?)",
        [USER_GROUP_DEFAULT, $username, $name, $email, $hash]
    );
    $id = (int) $db->pdo()->lastInsertId();
    $user = get_user($id);
    $_SESSION['uid'] = $id;
    if ($user) {
        ensure_system_playlists($id);
    }
    return ['ok' => true, 'error' => null, 'user' => $user];
}

function auth_logout() {
    $_SESSION['uid'] = null;
    unset($_SESSION['uid']);
    if (isset($_COOKIE[auth_cookie_key()])) {
        setcookie(auth_cookie_key(), '', time() - 3600, '/');
    }
}

/**
 * Forgot password: look up by email, set reset token (optional), send mail (stub).
 * @return array [ 'ok' => bool, 'error' => string ]
 */
function auth_forgot_password($email) {
    $user = get_user_by_email(trim($email));
    if (!$user) {
        return ['ok' => false, 'error' => 'email_not_found'];
    }
    // TODO: create reset token in DB, send email with link
    return ['ok' => true, 'error' => null];
}
