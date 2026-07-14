<?php
include_once("dbcon.php");

$appt_id = 0;
if (isset($_GET['appointment_id'])) {
    $appt_id = intval($_GET['appointment_id']);
} elseif (isset($_GET['item_number'])) {
    $appt_id = intval($_GET['item_number']);
}

$_SESSION['toast_error'] = 'PayPal checkout transaction was canceled by user.';

if ($appt_id > 0) {
    header("Location: payment.php?appointment_id=" . $appt_id);
} else {
    header("Location: my_appointment.php");
}
exit();
?>