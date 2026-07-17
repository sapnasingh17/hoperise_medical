<?php
/**
 * Cancellation Return Page for PayPal Checkout
 */

require_once __DIR__ . '/config.php';

$appointment_id = 0;
if (isset($_GET['appointment_id'])) {
    $appointment_id = intval($_GET['appointment_id']);
} elseif (isset($_GET['item_number'])) {
    $appointment_id = intval($_GET['item_number']);
}

$_SESSION['toast_error'] = 'PayPal checkout transaction was canceled by the user.';

if ($appointment_id > 0) {
    header("Location: payment.php?appointment_id=" . $appointment_id);
} else {
    header("Location: ../my_appointment.php");
}
exit();
?>
