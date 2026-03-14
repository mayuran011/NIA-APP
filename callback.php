<?php
/**
 * OAuth callback: Facebook and Google (config in nia_config.php).
 * Exchanges code for token, fetches profile, creates or logs in user, redirects.
 */

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

$provider = isset($_GET['provider']) ? trim($_GET['provider']) : '';
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if ($provider === '' || $code === '') {
    redirect(url('login'));
}

$redirect_uri = url('callback.php?provider=' . urlencode($provider));

if ($provider === 'facebook') {
    $app_id = defined('FB_APP_ID') ? FB_APP_ID : get_option('fb_app_id', '');
    $app_secret = defined('FB_APP_SECRET') ? FB_APP_SECRET : get_option('fb_app_secret', '');
    if ($app_id === '' || $app_secret === '') {
        redirect(url('login?error=fb_not_configured'));
    }
    $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token?client_id=' . urlencode($app_id) . '&redirect_uri=' . urlencode($redirect_uri) . '&client_secret=' . urlencode($app_secret) . '&code=' . urlencode($code);
    $token_json = @file_get_contents($token_url);
    $token_data = $token_json ? json_decode($token_json, true) : null;
    $access_token = $token_data['access_token'] ?? '';
    if ($access_token === '') {
        redirect(url('login?error=fb_token'));
    }
    $me_url = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . urlencode($access_token);
    $me_json = @file_get_contents($me_url);
    $me = $me_json ? json_decode($me_json, true) : null;
    if (!$me || empty($me['id'])) {
        redirect(url('login?error=fb_profile'));
    }
    $email = $me['email'] ?? ($me['id'] . '@facebook.user');
    $name = $me['name'] ?? 'User';
    $username = preg_replace('/[^a-z0-9_]/i', '', $me['id']);
    if (strlen($username) < 2) $username = 'fb_' . substr(md5($me['id']), 0, 8);
    $avatar = 'https://graph.facebook.com/' . $me['id'] . '/picture?type=large';
    $oauth_id = 'fb_' . $me['id'];
}

elseif ($provider === 'google') {
    $client_id = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : get_option('google_client_id', '');
    $client_secret = defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : get_option('google_client_secret', '');
    if ($client_id === '' || $client_secret === '') {
        redirect(url('login?error=google_not_configured'));
    }
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_post = http_build_query([
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
    ]);
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $token_post]]);
    $token_json = @file_get_contents($token_url, false, $ctx);
    $token_data = $token_json ? json_decode($token_json, true) : null;
    $access_token = $token_data['access_token'] ?? '';
    if ($access_token === '') {
        redirect(url('login?error=google_token'));
    }
    $me_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($access_token);
    $me_json = @file_get_contents($me_url);
    $me = $me_json ? json_decode($me_json, true) : null;
    if (!$me || empty($me['id'])) {
        redirect(url('login?error=google_profile'));
    }
    $email = $me['email'] ?? ($me['id'] . '@google.user');
    $name = $me['name'] ?? 'User';
    $username = preg_replace('/[^a-z0-9_]/i', '', $me['id']);
    if (strlen($username) < 2) $username = 'go_' . substr(md5($me['id']), 0, 8);
    $avatar = $me['picture'] ?? null;
    $oauth_id = 'go_' . $me['id'];
}

else {
    redirect(url('login'));
}

global $db;
$pre = $db->prefix();
$existing = $db->fetch("SELECT id FROM {$pre}users WHERE email = ? LIMIT 1", [$email]);
if ($existing) {
    $_SESSION['uid'] = (int) $existing->id;
    $db->query("UPDATE {$pre}users SET last_login = NOW(), avatar = COALESCE(avatar, ?) WHERE id = ?", [$avatar, (int) $existing->id]);
    redirect(url('dashboard'));
}

$db->query(
    "INSERT INTO {$pre}users (group_id, username, name, email, password, avatar) VALUES (?, ?, ?, ?, ?, ?)",
    [USER_GROUP_DEFAULT, $username, $name, $email, password_hash($oauth_id . auth_secret_salt(), PASSWORD_DEFAULT), $avatar]
);
$new_id = (int) $db->pdo()->lastInsertId();
ensure_system_playlists($new_id);
$_SESSION['uid'] = $new_id;
redirect(url('dashboard'));
