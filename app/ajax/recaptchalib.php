<?php
/**
 * Optional reCAPTCHA integration.
 * Include this where needed (e.g. register, login, comment forms).
 * When reCAPTCHA keys are not set in options/config, verify is a no-op (returns true).
 */

if (!defined('in_nia_app')) {
    exit;
}

/**
 * Verify reCAPTCHA response (v2/v3). Returns true if not configured or verification passes.
 * @param string $response Token from frontend (g-recaptcha-response or similar)
 * @param string|null $remote_ip Optional client IP
 * @return bool
 */
function recaptcha_verify($response, $remote_ip = null) {
    $secret = get_option('recaptcha_secret_key', defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : '');
    if ($secret === '') {
        return true;
    }
    $remote_ip = $remote_ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = ['secret' => $secret, 'response' => $response, 'remoteip' => $remote_ip];
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
        ],
    ];
    $ctx = stream_context_create($opts);
    $result = @file_get_contents($url, false, $ctx);
    if ($result === false) {
        return false;
    }
    $json = json_decode($result, true);
    return !empty($json['success']);
}
