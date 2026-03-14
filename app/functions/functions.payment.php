<?php
/**
 * Payment flow: create order, handle return/capture, set premium_upto.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/**
 * Create PayPal order (stub: in production use PayPal REST API).
 * Returns approval_url to redirect user, or null on failure.
 */
function payment_create_paypal_order($amount, $currency, $return_url, $cancel_url) {
    $client_id = defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : get_option('paypal_client_id', '');
    $secret = defined('PAYPAL_SECRET') ? PAYPAL_SECRET : get_option('paypal_secret', '');
    if ($client_id === '' || $secret === '') return null;

    $base = 'https://api-m.paypal.com';
    if (get_option('paypal_sandbox', 1)) {
        $base = 'https://api-m.sandbox.paypal.com';
    }

    $token_url = $base . '/v1/oauth2/token';
    $auth = base64_encode($client_id . ':' . $secret);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Basic {$auth}\r\nContent-Type: application/x-www-form-urlencoded",
            'content' => 'grant_type=client_credentials',
        ],
    ]);
    $token_json = @file_get_contents($token_url, false, $ctx);
    $token_data = $token_json ? json_decode($token_json, true) : null;
    $access_token = $token_data['access_token'] ?? null;
    if (!$access_token) return null;

    $order_url = $base . '/v2/checkout/orders';
    $body = json_encode([
        'intent' => 'CAPTURE',
        'purchase_units' => [['amount' => ['currency_code' => $currency, 'value' => $amount]]],
        'application_context' => ['return_url' => $return_url, 'cancel_url' => $cancel_url],
    ]);
    $ctx2 = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer {$access_token}\r\nContent-Type: application/json",
            'content' => $body,
        ],
    ]);
    $order_json = @file_get_contents($order_url, false, $ctx2);
    $order_data = $order_json ? json_decode($order_json, true) : null;
    $links = $order_data['links'] ?? [];
    foreach ($links as $link) {
        if (($link['rel'] ?? '') === 'approve') {
            return $link['href'] ?? null;
        }
    }
    return null;
}

/**
 * Capture PayPal order and grant premium.
 */
function payment_handle_return($order_id) {
    $uid = current_user_id();
    if ($uid <= 0) return false;
    $client_id = defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : get_option('paypal_client_id', '');
    $secret = defined('PAYPAL_SECRET') ? PAYPAL_SECRET : get_option('paypal_secret', '');
    if ($client_id === '' || $secret === '') return false;

    $base = 'https://api-m.paypal.com';
    if (get_option('paypal_sandbox', 1)) $base = 'https://api-m.sandbox.paypal.com';

    $token_url = $base . '/v1/oauth2/token';
    $auth = base64_encode($client_id . ':' . $secret);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Basic {$auth}\r\nContent-Type: application/x-www-form-urlencoded",
            'content' => 'grant_type=client_credentials',
        ],
    ]);
    $token_json = @file_get_contents($token_url, false, $ctx);
    $token_data = $token_json ? json_decode($token_json, true) : null;
    $access_token = $token_data['access_token'] ?? null;
    if (!$access_token) return false;

    $capture_url = $base . '/v2/checkout/orders/' . $order_id . '/capture';
    $ctx2 = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer {$access_token}\r\nContent-Type: application/json",
            'content' => '{}',
        ],
    ]);
    $cap_json = @file_get_contents($capture_url, false, $ctx2);
    $cap = $cap_json ? json_decode($cap_json, true) : null;
    $status = $cap['status'] ?? '';
    if ($status !== 'COMPLETED') return false;

    $pre = $db->prefix();
    $existing = premium_upto($uid);
    $base_ts = $existing && strtotime($existing) > time() ? strtotime($existing) : time();
    $new_upto = date('Y-m-d H:i:s', strtotime('+1 month', $base_ts));
    set_premium_upto($uid, $new_upto);
    $db->query(
        "INSERT INTO {$pre}payments (user_id, provider, external_id, amount, currency, status) VALUES (?, 'paypal', ?, 0, ?, 'completed')",
        [$uid, $order_id, premium_currency()]
    );
    return true;
}
