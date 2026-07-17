<?php
/**
 * Success Return Landing Page for PayPal Payments
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payment_functions.php';

// Get appointment ID from either GET or POST
$appointment_id = 0;
if (isset($_GET['appointment_id'])) {
    $appointment_id = intval($_GET['appointment_id']);
} elseif (isset($_GET['item_number'])) {
    $appointment_id = intval($_GET['item_number']);
} elseif (isset($_POST['item_number'])) {
    $appointment_id = intval($_POST['item_number']);
}

if (!isset($_SESSION['patient_id'])) {
    $_SESSION['toast_error'] = 'User session expired or unauthorized. Please log in.';
    header("Location: ../login.php");
    exit();
}
$patient_id = $_SESSION['patient_id'];

if ($appointment_id <= 0) {
    $_SESSION['toast_error'] = 'Invalid payment reference';
    header("Location: ../my_appointment.php");
    exit();
}

// Fetch appointment details
$appointment = get_payment_appointment_details($conn, $appointment_id, $patient_id);
if (!$appointment) {
    $_SESSION['toast_error'] = 'Appointment details could not be verified';
    header("Location: ../my_appointment.php");
    exit();
}

// Retrieve transaction ID from query string or request body
$txn_id = isset($_REQUEST['tx']) ? trim($_REQUEST['tx']) : (isset($_REQUEST['txn_id']) ? trim($_REQUEST['txn_id']) : '');
if (empty($txn_id)) {
    // Generate a unique mock transaction ID if none provided by Sandbox sandbox return URL
    $txn_id = 'PP-TXN-' . strtoupper(uniqid());
}

$update_success = false;

// If already paid (e.g. page refresh), skip database transaction to prevent duplicate entries
if ($appointment['appointment_payment_status'] == 1) {
    $update_success = true;
    if (!empty($appointment['transaction_id'])) {
        $txn_id = $appointment['transaction_id'];
    }
} else {
    $conn->begin_transaction();
    try {
        // Update the payment status in the database to paid
        $updated = update_appointment_payment_details($conn, $appointment_id, 'PayPal', 'Paid', 1, $txn_id);
        if (!$updated) {
            throw new Exception("Database update failed");
        }
        
        // Create payment timeline history entry
        $timeline_msg = "Consultation fee paid via PayPal. Transaction ID: $txn_id";
        $logged = add_payment_timeline_log($conn, $appointment_id, 'Paid', 'patient', $timeline_msg);
        if (!$logged) {
            throw new Exception("Timeline logging failed");
        }

        $conn->commit();
        $update_success = true;

        // Send email notification
        send_appointment_email($conn, $appointment_id, 'status_updated', $timeline_msg);
        
        $_SESSION['toast_success'] = 'PayPal Payment Completed Successfully!';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_error'] = 'Failed to record transaction details: ' . $e->getMessage();
    }
}

header("Location: payment_success.php?appointment_id=" . $appointment_id);
exit();
?>
