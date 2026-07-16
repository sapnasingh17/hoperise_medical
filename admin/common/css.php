<?php
// Old Logic:
// session_start();
//     $conn=mysqli_connect("localhost","root","","hoperise");
//     if(!isset($_SESSION['admin_id']))
//     {
//         header("Location:login.php");
//     }

include_once(__DIR__ . '/../../dbcon.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location:login.php");
    exit();
}

// Fetch user role for authorization checks
$role = '';
if (isset($_SESSION['role_id'])) {
    list($role_stmt, $role_result) = db_query_prepared($conn, "SELECT role_title FROM hs_role WHERE role_id = ?", [$_SESSION['role_id']]);
    if ($role_result && mysqli_num_rows($role_result) > 0) {
        $role_row = mysqli_fetch_array($role_result);
        $role = $role_row['role_title'];
    }
    if ($role_stmt) mysqli_stmt_close($role_stmt);
}

// Fetch user profile info (avatar and name)
$admin_id = intval($_SESSION['admin_id']);
$user_avatar = './assets/img/admin-avatar.png'; // default
$display_name = '';

if ($role == 'admin') {
    list($css_stmt, $css_res) = db_query_prepared($conn, "SELECT admin_username, admin_thumb FROM hs_admin WHERE admin_id = ?", [$admin_id]);
    if ($css_res && $css_row = mysqli_fetch_assoc($css_res)) {
        $display_name = $css_row['admin_username'];
        if (!empty($css_row['admin_thumb'])) {
            $user_avatar = 'uploads/' . $css_row['admin_thumb'];
        }
    }
    if ($css_stmt) mysqli_stmt_close($css_stmt);
} elseif ($role == 'doctor') {
    list($css_stmt, $css_res) = db_query_prepared($conn, "SELECT doctor_fname, doctor_lname, doctor_thumb FROM hs_doctor WHERE doctor_id = ?", [$admin_id]);
    if ($css_res && $css_row = mysqli_fetch_assoc($css_res)) {
        $display_name = $css_row['doctor_fname'] . ' ' . $css_row['doctor_lname'];
        if (!empty($css_row['doctor_thumb'])) {
            $user_avatar = 'uploads/' . $css_row['doctor_thumb'];
        }
    }
    if ($css_stmt) mysqli_stmt_close($css_stmt);
}

// Admin-only pages list to prevent privilege escalation by doctors
$admin_only_files = [
    'add_role.php', 'display_role.php', 'edit_role.php', 'delete_role.php',
    'add_category.php', 'display_category.php', 'edit_category.php', 'delete_category.php',
    'add_sub_category.php', 'display_sub_category.php', 'edit_sub_category.php', 'delete_sub_category.php',
    'add_service.php', 'display_service.php', 'edit_service.php', 'delete_service.php',
    'display_contact.php', 'contact_detail.php', 'delete_contact.php',
    'add_doctor.php', 'display_doctor.php', 'edit_doctor.php', 'delete_doctor.php'
];

if (in_array(basename($_SERVER['PHP_SELF']), $admin_only_files) && $role !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>";
    echo "<h1>403 Forbidden</h1>";
    echo "<p>Access Denied. You do not have permission to access this administrative page.</p>";
    echo "<p><a href='index.php'>Go back to Dashboard</a></p>";
    echo "</div>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width initial-scale=1.0">
    <title>Admincast bootstrap 4 &amp; angular 5 admin template, Шаблон админки | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="../css/custom.css?v=<?= time(); ?>" rel="stylesheet" />
    <link href="./assets/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
    <link href="./assets/vendors/themify-icons/css/themify-icons.css" rel="stylesheet" />
    
    <!-- PLUGINS STYLES-->
    <link href="./assets/vendors/DataTables/datatables.min.css" rel="stylesheet" />
    <link href="./assets/vendors/jvectormap/jquery-jvectormap-2.0.3.css" rel="stylesheet" />
    <!-- THEME STYLES-->
    <link href="assets/css/main.min.css" rel="stylesheet" />
    <?php if (isset($_COOKIE['setting-theme']) && $_COOKIE['setting-theme'] !== 'default'): ?>
        <link href="assets/css/themes/<?= htmlspecialchars($_COOKIE['setting-theme']) ?>.css" rel="stylesheet" id="theme-style" />
    <?php endif; ?>
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- PAGE LEVEL STYLES-->
</head>