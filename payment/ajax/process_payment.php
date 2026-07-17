<?php
/**
 * AJAX Handler for processing UPI/QR and Cash on Visit payments
 */
header('Content-Type: application/json');

// Ensure request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Direct access forbidden']);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../payment_functions.php';

// Verify POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Verify CSRF Token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
    exit();
}

// Verify active session
if (!isset($_SESSION['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please log in again.']);
    exit();
}
$patient_id = $_SESSION['patient_id'];

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';

if ($appointment_id <= 0 || empty($payment_method)) {
    echo json_encode(['success' => false, 'message' => 'Missing required payment details']);
    exit();
}

// Fetch and validate appointment ownership
$appointment = get_payment_appointment_details($conn, $appointment_id, $patient_id);
if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment or unauthorized access']);
    exit();
}

// Prevent state update if already paid
if ($appointment['appointment_payment_status'] == 1) {
    echo json_encode(['success' => false, 'message' => 'This appointment has already been paid.']);
    exit();
}

// Define variables based on selection
if ($payment_method === 'Cash on Visit') {
    $db_method = 'Cash on Visit';
    $db_status = 'Pending';
    $db_pay_flag = 0; // Unpaid until they physically visit
    $timeline_comments = 'Selected Cash on Visit. Payment pending reception verification.';
    $success_msg = 'Cash on Visit selected. Appointment confirmed!';
} elseif ($payment_method === 'UPI / QR') {
    $db_method = 'UPI / QR';
    $db_status = 'Pending';
    $db_pay_flag = 0; // Unpaid until admin confirms receipt
    $timeline_comments = 'Selected UPI / QR payment. Payment verification pending.';
    $success_msg = 'UPI / QR details submitted! Verification pending.';
} else {
    echo json_encode(['success' => false, 'message' => 'Unsupported payment method']);
    exit();
}

// Update database records
$conn->begin_transaction();
try {
    $updated = update_appointment_payment_details($conn, $appointment_id, $db_method, $db_status, $db_pay_flag);
    if (!$updated) {
        throw new Exception("Failed to update database record");
    }

    $logged = add_payment_timeline_log($conn, $appointment_id, 'Payment Selected', 'patient', $timeline_comments);
    if (!$logged) {
        throw new Exception("Failed to log timeline history");
    }

    $conn->commit();
    
    // Attempt sending email notification (failures are caught internally and won't halt transaction)
    send_appointment_email($conn, $appointment_id, 'status_updated', $timeline_comments);

    echo json_encode(['success' => true, 'message' => $success_msg]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Transaction processing error: ' . $e->getMessage()]);
}
?>
