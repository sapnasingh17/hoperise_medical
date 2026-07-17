<?php
/**
 * Asynchronous PayPal IPN (Instant Payment Notification) Webhook Listener
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payment_functions.php';

// Log incoming raw IPN data for verification auditing
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$my_post = array();
foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2) {
        $my_post[$keyval[0]] = urldecode($keyval[1]);
    }
}

// Read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
$req = 'cmd=_notify-validate';
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() == 1) {
    $get_magic_quotes_exists = true;
} else {
    $get_magic_quotes_exists = false;
}
foreach ($my_post as $key => $value) {
    if ($get_magic_quotes_exists == true) {
        $value = urlencode(stripslashes($value));
    } else {
        $value = urlencode($value);
    }
    $req .= "&$key=$value";
}

// Post request back to PayPal system to validate the IPN payload
$paypal_verify_url = PAYPAL_SANDBOX 
    ? "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr" 
    : "https://ipnpb.paypal.com/cgi-bin/webscr";

$ch = curl_init($paypal_verify_url);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

$res = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

$log_file = __DIR__ . '/ipn.log';

if (strcmp($res, "VERIFIED") == 0) {
    // Process verified transaction parameters
    $appointment_id = isset($_POST['item_number']) ? intval($_POST['item_number']) : 0;
    $payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : '';
    $payment_amount = isset($_POST['mc_gross']) ? floatval($_POST['mc_gross']) : 0.0;
    $txn_id = isset($_POST['txn_id']) ? trim($_POST['txn_id']) : '';
    $business_email = isset($_POST['receiver_email']) ? trim($_POST['receiver_email']) : '';

    $log_msg = "[" . date('Y-m-d H:i:s') . "] VERIFIED: Appointment ID #$appointment_id, Status: $payment_status, Amount: $payment_amount, Txn ID: $txn_id\n";
    file_put_contents($log_file, $log_msg, FILE_APPEND);

    // Validate email receiver and verify status
    if (strtolower($business_email) === strtolower(PAYPAL_BUSINESS_EMAIL) && $appointment_id > 0) {
        if ($payment_status === 'Completed') {
            $conn->begin_transaction();
            try {
                // Update payment details as paid
                $updated = update_appointment_payment_details($conn, $appointment_id, 'PayPal', 'Paid', 1, $txn_id);
                if (!$updated) {
                    throw new Exception("Database update failed");
                }

                $timeline_msg = "Consultation fee paid via PayPal (IPN verified). Transaction ID: $txn_id";
                $logged = add_payment_timeline_log($conn, $appointment_id, 'Paid', 'admin', $timeline_msg);
                if (!$logged) {
                    throw new Exception("Timeline log failed");
                }

                $conn->commit();
                
                // Trigger email notification
                send_appointment_email($conn, $appointment_id, 'status_updated', $timeline_msg);
            } catch (Exception $e) {
                $conn->rollback();
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] DATABASE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
    }
} else {
    // Log failures
    $log_msg = "[" . date('Y-m-d H:i:s') . "] INVALID/FAILED: Verification response: $res, Curl Error: $curl_error\n";
    file_put_contents($log_file, $log_msg, FILE_APPEND);
}

// Reply with 200 to acknowledge PayPal notification
http_response_code(200);
?>
