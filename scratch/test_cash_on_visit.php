<?php
// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['patient_id'] = 1;
$_SESSION['csrf_token'] = 'testtoken';

// Include db connection
include_once(__DIR__ . '/../dbcon.php');

// Let's create an appointment for patient 1 if not exists or select one
$res = mysqli_query($conn, "SELECT appointment_id FROM hs_appointment WHERE patient_id = 1 AND appointment_payment_status = 0 LIMIT 1");
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    $appointment_id = $row['appointment_id'];
} else {
    // Insert a dummy appointment
    mysqli_query($conn, "INSERT INTO hs_appointment (patient_id, category_id, sub_category_id, service_id, doctor_id, appointment_date, appointment_time, appointment_description) VALUES (1, 1, 1, 1, 1, '2026-07-20', '10:00:00', 'Test Appointment')");
    $appointment_id = mysqli_insert_id($conn);
}

echo "Testing with Appointment ID: " . $appointment_id . "\n";

// Now, we mock a POST request to payment.php by setting $_POST and $_SERVER
$_POST['pay_method'] = 'Cash on Visit';
$_POST['csrf_token'] = 'testtoken';
$_GET['appointment_id'] = $appointment_id;
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest'; // AJAX

// Capture output
ob_start();
include(__DIR__ . '/../payment.php');
$output = ob_get_clean();

echo "Response output:\n";
echo $output;
echo "\n";

// Query the database to check the updated state of the appointment
$res_check = mysqli_query($conn, "SELECT payment_method, payment_status, appointment_payment_status, transaction_id FROM hs_appointment WHERE appointment_id = " . $appointment_id);
$appt = mysqli_fetch_assoc($res_check);
echo "Database state after processing:\n";
print_r($appt);
?>
