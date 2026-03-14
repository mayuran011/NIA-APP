<?php
/**
 * Create payment order (PayPal); redirect to approval URL.
 */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

if (!is_logged() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('payment'));
    exit;
}

$currency = isset($_POST['currency']) ? trim($_POST['currency']) : premium_currency();
$amount = isset($_POST['amount']) ? trim($_POST['amount']) : premium_price();
$return_url = isset($_POST['return_url']) ? trim($_POST['return_url']) : url('payment/return');
$cancel_url = isset($_POST['cancel_url']) ? trim($_POST['cancel_url']) : url('payment/cancel');

require_once ABSPATH . 'app' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions.payment.php';

$approval_url = payment_create_paypal_order($amount, $currency, $return_url, $cancel_url);
if ($approval_url) {
    header('Location: ' . $approval_url);
    exit;
}

header('Location: ' . url('payment?error=create_failed'));
