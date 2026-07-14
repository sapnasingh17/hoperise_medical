<?php
include_once("../dbcon.php");

// Enforce session access control
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve administrator role check
$role_id = intval($_SESSION['role_id']);
list($role_stmt, $role_data) = db_query_prepared($conn, "SELECT * FROM hs_role WHERE role_id = ?", [$role_id]);
$role_row = mysqli_fetch_array($role_data);
$role = $role_row['role_title'] ?? '';
if ($role_stmt) {
    mysqli_stmt_close($role_stmt);
}

if ($role !== 'admin') {
    die("Access Denied.");
}

// Retrieve filter parameters
$report_type = isset($_GET['report']) ? trim($_GET['report']) : 'appointments';
$timeframe = isset($_GET['timeframe']) ? trim($_GET['timeframe']) : 'all';
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Helper to escape cells to prevent CSV Injection
function clean_cell($val) {
    $val = trim(strval($val));
    if (isset($val[0]) && in_array($val[0], ['=', '+', '-', '@'])) {
        return "'" . $val;
    }
    return $val;
}

// Construct filters
$where_clauses = [];
$params = [];
$types = "";

if ($timeframe === 'monthly') {
    $where_clauses[] = "YEAR(a.appointment_date) = ? AND MONTH(a.appointment_date) = ?";
    $params[] = $year;
    $params[] = $month;
    $types .= "ii";
} elseif ($timeframe === 'yearly') {
    $where_clauses[] = "YEAR(a.appointment_date) = ?";
    $params[] = $year;
    $types .= "i";
} elseif ($timeframe === 'custom' && !empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "a.appointment_date >= ? AND a.appointment_date <= ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Open output stream
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="hoperise_' . $report_type . '_report_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');

// Add BOM for proper UTF-8 Excel handling
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($report_type === 'appointments') {
    fputcsv($output, ['Appointment ID', 'Patient Name', 'Doctor Name', 'Category/Specialty', 'Date', 'Time', 'Status', 'Payment Status', 'Description']);
    
    $sql = "SELECT a.*, p.patient_fname, p.patient_lname, d.doctor_fname, d.doctor_lname, c.category_title 
            FROM hs_appointment a 
            LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
            LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id 
            LEFT JOIN hs_category c ON a.category_id = c.category_id 
            $where_sql 
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            
    list($stmt, $result) = db_query_prepared($conn, $sql, $params, $types);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            clean_cell($row['appointment_id']),
            clean_cell($row['patient_fname'] . ' ' . $row['patient_lname']),
            clean_cell('Dr. ' . $row['doctor_fname'] . ' ' . $row['doctor_lname']),
            clean_cell($row['category_title'] ?? 'N/A'),
            clean_cell($row['appointment_date']),
            clean_cell(date("g:i A", strtotime($row['appointment_time']))),
            clean_cell($row['appointment_status']),
            clean_cell($row['appointment_payment_status'] == 1 ? 'Paid' : 'Unpaid'),
            clean_cell($row['appointment_description'])
        ]);
    }
    if ($stmt) mysqli_stmt_close($stmt);

} elseif ($report_type === 'revenue') {
    fputcsv($output, ['Appointment ID', 'Transaction Date', 'Patient Name', 'Doctor Name', 'Service / Specialty', 'Amount Paid (INR)', 'Payment Mode']);
    
    // Revenue is only for paid appointments
    $rev_clauses = $where_clauses;
    $rev_clauses[] = "a.appointment_payment_status = 1";
    $rev_where = " WHERE " . implode(" AND ", $rev_clauses);
    
    $sql = "SELECT a.*, p.patient_fname, p.patient_lname, d.doctor_fname, d.doctor_lname, c.category_title 
            FROM hs_appointment a 
            LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
            LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id 
            LEFT JOIN hs_category c ON a.category_id = c.category_id 
            $rev_where 
            ORDER BY a.appointment_date DESC";
            
    list($stmt, $result) = db_query_prepared($conn, $sql, $params, $types);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            clean_cell($row['appointment_id']),
            clean_cell($row['appointment_date']),
            clean_cell($row['patient_fname'] . ' ' . $row['patient_lname']),
            clean_cell('Dr. ' . $row['doctor_fname'] . ' ' . $row['doctor_lname']),
            clean_cell($row['category_title'] ?? 'Consultation'),
            500, // Fixed fee amount
            clean_cell('Standard Gateway')
        ]);
    }
    if ($stmt) mysqli_stmt_close($stmt);

} elseif ($report_type === 'doctors') {
    fputcsv($output, ['Doctor ID', 'Doctor Name', 'Email', 'Phone', 'Specialty', 'Joined Date', 'Total Appointments', 'Status']);
    
    $sql = "SELECT d.*, c.category_title, COUNT(a.appointment_id) AS total_appts 
            FROM hs_doctor d 
            LEFT JOIN hs_category c ON d.category_id = c.category_id 
            LEFT JOIN hs_appointment a ON d.doctor_id = a.doctor_id 
            GROUP BY d.doctor_id 
            ORDER BY total_appts DESC";
            
    list($stmt, $result) = db_query_prepared($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            clean_cell($row['doctor_id']),
            clean_cell('Dr. ' . $row['doctor_fname'] . ' ' . $row['doctor_lname']),
            clean_cell($row['doctor_email']),
            clean_cell($row['doctor_phone']),
            clean_cell($row['category_title'] ?? 'General Medicine'),
            clean_cell($row['doctor_doj']),
            clean_cell($row['total_appts']),
            clean_cell($row['status'] == 1 ? 'Active' : 'Inactive')
        ]);
    }
    if ($stmt) mysqli_stmt_close($stmt);

} elseif ($report_type === 'patients') {
    fputcsv($output, ['Patient ID', 'Patient Name', 'Email', 'Phone', 'Gender', 'Joined Date', 'Total Bookings', 'Status']);
    
    $sql = "SELECT p.*, COUNT(a.appointment_id) AS total_bookings 
            FROM hs_patient p 
            LEFT JOIN hs_appointment a ON p.patient_id = a.patient_id 
            GROUP BY p.patient_id 
            ORDER BY total_bookings DESC";
            
    list($stmt, $result) = db_query_prepared($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            clean_cell($row['patient_id']),
            clean_cell($row['patient_fname'] . ' ' . $row['patient_lname']),
            clean_cell($row['patient_email']),
            clean_cell($row['patient_phone']),
            clean_cell($row['patient_gender']),
            clean_cell(date('Y-m-d', strtotime($row['created_date']))),
            clean_cell($row['total_bookings']),
            clean_cell($row['status'] == 1 ? 'Active' : 'Inactive')
        ]);
    }
    if ($stmt) mysqli_stmt_close($stmt);

} elseif ($report_type === 'payments') {
    fputcsv($output, ['Appointment ID', 'Billing Date', 'Patient Name', 'Payment Status', 'Amount (INR)']);
    
    $sql = "SELECT a.*, p.patient_fname, p.patient_lname 
            FROM hs_appointment a 
            LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
            $where_sql 
            ORDER BY a.appointment_date DESC";
            
    list($stmt, $result) = db_query_prepared($conn, $sql, $params, $types);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            clean_cell($row['appointment_id']),
            clean_cell($row['appointment_date']),
            clean_cell($row['patient_fname'] . ' ' . $row['patient_lname']),
            clean_cell($row['appointment_payment_status'] == 1 ? 'Paid' : 'Unpaid'),
            500
        ]);
    }
    if ($stmt) mysqli_stmt_close($stmt);

} elseif ($report_type === 'feedback') {
    fputcsv($output, ['Feedback ID', 'Date Submitted', 'Patient Name', 'Message', 'Status']);
    
    // Filter feedback by date (since hs_feedback has created_date column)
    $feed_params = [];
    $feed_types = "";
    $feed_where = "";
    if ($timeframe === 'monthly') {
        $feed_where = " WHERE YEAR(f.created_date) = ? AND MONTH(f.created_date) = ?";
        $feed_params = [$year, $month];
        $feed_types = "ii";
    } elseif ($timeframe === 'yearly') {
        $feed_where = " WHERE YEAR(f.created_date) = ?";
        $feed_params = [$year];
        $feed_types = "i";
    } elseif ($timeframe === 'custom' && !empty($start_date) && !empty($end_date)) {
        $feed_where = " WHERE f.created_date >= ? AND f.created_date <= ?";
        $feed_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        $feed_types = "ss";
    }

    $sql = "SELECT f.*, p.patient_fname, p.patient_lname 
            FROM hs_feedback f 
            LEFT JOIN hs_patient p ON f.patient_id = p.patient_id 
            $feed_where 
            ORDER BY f.created_date DESC";
            
    list($stmt, $result) = db_query_prepared($conn, $sql, $feed_params, $feed_types);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            clean_cell($row['feedback_id']),
            clean_cell($row['created_date']),
            clean_cell($row['patient_fname'] . ' ' . $row['patient_lname']),
            clean_cell($row['fd_message']),
            clean_cell($row['status'] == 1 ? 'Published' : 'Hidden')
        ]);
    }
    if ($stmt) mysqli_stmt_close($stmt);
}

fclose($output);
exit();
