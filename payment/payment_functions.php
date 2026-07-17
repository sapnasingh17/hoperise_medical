<?php
/**
 * Helper Functions for Hoperise Medical Payment Module
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../email_helper.php';

/**
 * Fetch detailed appointment information for payment processing
 * 
 * @param mysqli $conn Database connection
 * @param int $appointment_id Appointment ID
 * @param int $patient_id Patient ID (for ownership validation)
 * @return array|false Appointment data array or false if not found/unauthorized
 */
function get_payment_appointment_details($conn, $appointment_id, $patient_id) {
    $sql = "SELECT a.*, 
                   d.doctor_fname, d.doctor_lname, d.doctor_email, d.doctor_phone,
                   c.category_title, 
                   s.service_title,
                   p.patient_fname, p.patient_lname, p.patient_email, p.patient_phone, p.patient_address
            FROM hs_appointment a
            LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id
            LEFT JOIN hs_category c ON a.category_id = c.category_id
            LEFT JOIN hs_service s ON a.service_id = s.service_id
            LEFT JOIN hs_patient p ON a.patient_id = p.patient_id
            WHERE a.appointment_id = ? AND a.patient_id = ? AND a.status = 1";
            
    list($stmt, $result) = db_query_prepared($conn, $sql, [$appointment_id, $patient_id], 'ii');
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $data ?: false;
}

/**
 * Update payment details for an appointment
 * 
 * @param mysqli $conn Database connection
 * @param int $appointment_id Appointment ID
 * @param string $payment_method Payment method name
 * @param string $payment_status Payment status string
 * @param int $appointment_payment_status Binary payment flag (1 = paid, 0 = unpaid)
 * @param string $transaction_id (Optional) Transaction identifier
 * @return bool True on success, false on failure
 */
function update_appointment_payment_details($conn, $appointment_id, $payment_method, $payment_status, $appointment_payment_status, $transaction_id = '') {
    $sql = "UPDATE hs_appointment 
            SET payment_method = ?, 
                payment_status = ?, 
                appointment_payment_status = ?, 
                transaction_id = ?,
                update_date = NOW() 
            WHERE appointment_id = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ssisi", $payment_method, $payment_status, $appointment_payment_status, $transaction_id, $appointment_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if ($success) {
        // Also record/sync into our new hs_payments table!
        $t_status = ($appointment_payment_status == 1) ? 'Paid' : 'Pending';
        record_payment_transaction($conn, $appointment_id, $payment_method, $transaction_id, $t_status);
    }
    
    return $success;
}

/**
 * Record a transaction entry in the hs_payments table using prepared statements
 * 
 * @param mysqli $conn Database connection
 * @param int $appointment_id Appointment ID
 * @param string $payment_method Payment method name
 * @param string $transaction_id Transaction ID
 * @param string $payment_status Status string ('Paid', 'Pending')
 * @param string|null $payment_date Explicit transaction date
 * @return bool True on success, false on failure
 */
function record_payment_transaction($conn, $appointment_id, $payment_method, $transaction_id, $payment_status, $payment_date = null) {
    // Fetch amount from hs_appointment
    $sql_amt = "SELECT amount FROM hs_appointment WHERE appointment_id = ?";
    list($stmt_amt, $res_amt) = db_query_prepared($conn, $sql_amt, [$appointment_id], 'i');
    $row_amt = mysqli_fetch_assoc($res_amt);
    mysqli_stmt_close($stmt_amt);
    
    $amount = floatval($row_amt['amount'] ?? 590.00);
    if ($amount <= 0) {
        $amount = 590.00;
    }
    
    $currency = 'INR'; // Standard local medical currency
    
    if ($payment_date === null) {
        $payment_date = date('Y-m-d H:i:s');
    }
    
    // Check if record exists
    $check_sql = "SELECT id FROM hs_payments WHERE appointment_id = ?";
    list($check_stmt, $check_res) = db_query_prepared($conn, $check_sql, [$appointment_id], 'i');
    $existing = mysqli_fetch_assoc($check_res);
    mysqli_stmt_close($check_stmt);
    
    if ($existing) {
        $sql = "UPDATE hs_payments 
                SET payment_method = ?, 
                    transaction_id = ?, 
                    amount = ?, 
                    currency = ?, 
                    payment_status = ?, 
                    payment_date = ?,
                    updated_at = NOW()
                WHERE appointment_id = ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ssdsssi", $payment_method, $transaction_id, $amount, $currency, $payment_status, $payment_date, $appointment_id);
    } else {
        $sql = "INSERT INTO hs_payments (appointment_id, payment_method, transaction_id, amount, currency, payment_status, payment_date, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, "issdsss", $appointment_id, $payment_method, $transaction_id, $amount, $currency, $payment_status, $payment_date);
    }
    
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $success;
}

/**
 * Add a log entry to the appointment timeline history
 * 
 * @param mysqli $conn Database connection
 * @param int $appointment_id Appointment ID
 * @param string $status_action Timeline action status
 * @param string $action_by Initiator ('patient', 'doctor', 'admin')
 * @param string $comments Log description comments
 * @return bool True on success, false on failure
 */
function add_payment_timeline_log($conn, $appointment_id, $status_action, $action_by, $comments) {
    $sql = "INSERT INTO hs_appointment_timeline (appointment_id, status_action, action_by, comments, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
            
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "isss", $appointment_id, $status_action, $action_by, $comments);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $success;
}
?>
