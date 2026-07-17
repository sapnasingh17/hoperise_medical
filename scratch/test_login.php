<?php
session_start();
$_SESSION['patient_id'] = 3;
$_SESSION['user_id'] = 3;
header("Location: ../payment/payment.php?appointment_id=3");
exit();
