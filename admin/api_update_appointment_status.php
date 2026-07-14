<?php
include_once(__DIR__ . '/../../dbcon.php');
include_once(__DIR__ . '/../email_helper.php');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$appointment_status = isset($_POST['appointment_status']) ? trim($_POST['appointment_status']) : '';

$valid_statuses = ['Pending', 'Accept', 'Reject', 'Cancel'];
if (!in_array($appointment_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment status.']);
    exit();
}

// If user is a doctor, verify that the appointment belongs to them
$doctor_id = intval($_SESSION['admin_id']);
if ($role == 'doctor') {
    list($chk_stmt, $chk_res) = db_query_prepared($conn, "SELECT doctor_id FROM hs_appointment WHERE appointment_id = ?", [$appointment_id]);
    $chk_row = mysqli_fetch_assoc($chk_res);
    if ($chk_stmt) mysqli_stmt_close($chk_stmt);
    
    if (!$chk_row || intval($chk_row['doctor_id']) !== $doctor_id) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to update this appointment.']);
        exit();
    }
}

// Update status
$update_stmt = mysqli_prepare($conn, "UPDATE hs_appointment SET appointment_status = ? WHERE appointment_id = ?");
if ($update_stmt) {
    mysqli_stmt_bind_param($update_stmt, "si", $appointment_status, $appointment_id);
    $success = mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    if ($success) {
        // Log to timeline
        $timeline_action = ($appointment_status == 'Accept') ? 'Confirmed' : (($appointment_status == 'Reject') ? 'Rejected' : $appointment_status);
        $comments = "Appointment status updated to " . $appointment_status . " by " . ($role == 'doctor' ? 'doctor' : 'administrator') . " via inline status change.";
        $action_by = ($role == 'doctor') ? 'doctor' : 'admin';
        
        $timeline_stmt = mysqli_prepare($conn, "INSERT INTO hs_appointment_timeline (appointment_id, status_action, action_by, comments) VALUES (?, ?, ?, ?)");
        if ($timeline_stmt) {
            mysqli_stmt_bind_param($timeline_stmt, "isss", $appointment_id, $timeline_action, $action_by, $comments);
            mysqli_stmt_execute($timeline_stmt);
            mysqli_stmt_close($timeline_stmt);
        }
        
        // Trigger email notification
        send_appointment_email($conn, $appointment_id, 'status_updated', $comments);
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status in the database.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: Prepare failed.']);
}
?>
