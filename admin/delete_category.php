<?php
include('common/css.php');

// CSRF verification
if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
    die("CSRF Verification Failed");
}

$category_id = intval($_GET['category_id']);

$stmt = mysqli_prepare($conn, "DELETE FROM hs_category WHERE category_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    $query = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if($query)
    { 
        header("Location:display_category.php");
        exit();
    }
}
?>
