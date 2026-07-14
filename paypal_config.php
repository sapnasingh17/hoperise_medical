<?php 
/* 
 * This is - PayPal and database configuration -  
*/ 
  
// PayPal configuration 
define('PAYPAL_ID', 'sb-kza43x33289444@business.example.com'); 
define('PAYPAL_SANDBOX', TRUE); //TRUE or FALSE 
 
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_url = $protocol . $host . rtrim($dir, '/') . '/';

define('PAYPAL_RETURN_URL', $base_url . 'paypal_success.php'); 
define('PAYPAL_CANCEL_URL', $base_url . 'paypal_cancel.php'); 
define('PAYPAL_NOTIFY_URL', $base_url . 'paypal_ipn.php'); 
define('PAYPAL_CURRENCY', 'USD'); 

// Database configuration 
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost'); 
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', 'root'); 
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', ''); 
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'paypal'); 
}

// Change not required 
define('PAYPAL_URL', (PAYPAL_SANDBOX == true)?"https://www.sandbox.paypal.com/cgi-bin/webscr":"https://www.paypal.com/cgi-bin/webscr");

// [ b_boy@gmail.com ]