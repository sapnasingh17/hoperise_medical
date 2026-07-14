<?php
include('common/css.php');

// Validate CSRF
if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
    die("CSRF Verification Failed");
}

$role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;
$stmt = mysqli_prepare($conn, "delete from hs_role where role_id=?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $role_id);
    $query = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if($query)
    { 
        header("Location: display_role.php");
        exit();
    }
}
?>
