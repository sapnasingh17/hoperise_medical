<?php
include_once("dbcon.php");

$appt_id = 0;
if (isset($_GET['appointment_id'])) {
    $appt_id = intval($_GET['appointment_id']);
} elseif (isset($_GET['item_number'])) {
    $appt_id = intval($_GET['item_number']);
}

if ($appt_id > 0) {
    // Update the payment status in the database to paid
    $stmt = mysqli_prepare($conn, "UPDATE hs_appointment SET payment_method = 'PayPal', payment_status = 'Paid', appointment_payment_status = 1 WHERE appointment_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $appt_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // Create payment timeline history entry
    $t_sql = "INSERT INTO hs_appointment_timeline (appointment_id, status_action, action_by, comments) VALUES (?, 'Paid', 'patient', 'Consultation fee paid via PayPal')";
    $t_stmt = mysqli_prepare($conn, $t_sql);
    if ($t_stmt) {
        mysqli_stmt_bind_param($t_stmt, "i", $appt_id);
        mysqli_stmt_execute($t_stmt);
        mysqli_stmt_close($t_stmt);
    }

    $_SESSION['toast_success'] = 'PayPal Payment Completed Successfully!';
    header("Location: payment.php?appointment_id=" . $appt_id);
    exit();
}

header("Location: my_appointment.php");
exit();
?>
