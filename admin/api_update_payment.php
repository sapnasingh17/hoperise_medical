<?php
/**
 * Admin API Endpoint to Update Payment Status
 */
include_once(__DIR__ . '/../dbcon.php');
include_once(__DIR__ . '/../payment/payment_functions.php');
include_once(__DIR__ . '/email_helper.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Fetch user role
$role = '';
if (isset($_SESSION['role_id'])) {
    list($role_stmt, $role_result) = db_query_prepared($conn, "SELECT role_title FROM hs_role WHERE role_id = ?", [$_SESSION['role_id']]);
    if ($role_result && mysqli_num_rows($role_result) > 0) {
        $role_row = mysqli_fetch_array($role_result);
        $role = $role_row['role_title'];
    }
    if ($role_stmt) mysqli_stmt_close($role_stmt);
}

if ($role !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access Denied. Only Administrators can update payments.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$payment_status = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : '';
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
$transaction_id = isset($_POST['transaction_id']) ? trim($_POST['transaction_id']) : '';

if ($appointment_id <= 0 || !in_array($payment_status, ['Paid', 'Pending'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data.']);
    exit();
}

// Map payment status to binary representation
$appointment_payment_status = ($payment_status === 'Paid') ? 1 : 0;
$db_status = ($payment_status === 'Paid') ? 'Paid' : 'Pending';

// Verify appointment exists
list($appt_stmt, $appt_res) = db_query_prepared($conn, "SELECT payment_method, transaction_id FROM hs_appointment WHERE appointment_id = ?", [$appointment_id]);
$appt_row = mysqli_fetch_assoc($appt_res);
if ($appt_stmt) mysqli_stmt_close($appt_stmt);

if (!$appt_row) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
    exit();
}

// Default values if empty
if (empty($payment_method)) {
    $payment_method = $appt_row['payment_method'] ?: 'Cash on Visit';
}
if (empty($transaction_id)) {
    $transaction_id = $appt_row['transaction_id'] ?: '';
    if ($payment_status === 'Paid' && empty($transaction_id)) {
        $transaction_id = 'TXN-ADM-' . strtoupper(uniqid());
    }
}

// Update payment details inside hs_appointment and hs_payments tables
$success = update_appointment_payment_details($conn, $appointment_id, $payment_method, $db_status, $appointment_payment_status, $transaction_id);

if ($success) {
    // Add timeline action log
    $comments = "Payment status updated to " . $payment_status . " by administrator. Method: " . $payment_method . " | Txn ID: " . $transaction_id;
    add_payment_timeline_log($conn, $appointment_id, 'Payment Updated', 'admin', $comments);
    
    // Trigger email notification
    send_appointment_email($conn, $appointment_id, 'payment_verified', $comments);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Payment status updated successfully.', 
        'transaction_id' => $transaction_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update payment details in database.']);
}
?>
