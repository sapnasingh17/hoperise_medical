<?php
include('common/css.php');

// CSRF verification
if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
    die("CSRF Verification Failed");
}

$service_id = intval($_GET['service_id']);

$stmt = mysqli_prepare($conn, "DELETE FROM hs_service WHERE service_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $service_id);
    $query = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if($query)
    { 
        header("Location:display_service.php");
        exit();
    }
}
?>
