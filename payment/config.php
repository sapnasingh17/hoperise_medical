<?php
/**
 * Payment Module Configuration
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include global database connection
require_once __DIR__ . '/../dbcon.php';

// PayPal Settings
define('PAYPAL_SANDBOX', true); // Change to false for production
define('PAYPAL_BUSINESS_EMAIL', 'sb-kza43x33289444@business.example.com');
define('PAYPAL_CURRENCY', 'USD'); // Currency for PayPal transactions

// UPI Settings
define('UPI_ID', 'hoperise@upi');
define('UPI_MERCHANT_NAME', 'HopeRise Medical');

// Base URL calculation for payment module
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$payment_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Ensure the directory path ends with a slash and points to /payment
if (substr($payment_dir, -7) !== '/payment' && substr($payment_dir, -8) !== '/payment/') {
    $payment_base_url = $protocol . $host . rtrim($payment_dir, '/') . '/payment/';
} else {
    $payment_base_url = $protocol . $host . rtrim($payment_dir, '/') . '/';
}

define('PAYPAL_RETURN_URL', $payment_base_url . 'paypal_success.php');
define('PAYPAL_CANCEL_URL', $payment_base_url . 'paypal_cancel.php');
define('PAYPAL_NOTIFY_URL', $payment_base_url . 'paypal_ipn.php');
define('PAYPAL_URL', PAYPAL_SANDBOX ? "https://www.sandbox.paypal.com/cgi-bin/webscr" : "https://www.paypal.com/cgi-bin/webscr");
?>
