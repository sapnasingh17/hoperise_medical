<?php
include('common/css.php');

// CSRF verification
if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
    die("CSRF Verification Failed");
}

$doctor_id = intval($_GET['doctor_id']);

$stmt = mysqli_prepare($conn, "DELETE FROM hs_doctor WHERE doctor_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    $query = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if($query)
    { 
        header("Location:display_doctor.php");
        exit();
    }
}
?>
