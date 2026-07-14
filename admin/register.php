<?php
// Secure Logic with Prepared Statements, BCrypt, CSRF, and File Upload Validation:
include_once(__DIR__ . '/../dbcon.php');





if(isset($_POST['submit']))
{
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);

    // Strict file upload check
    $file_validation = validate_file_upload($_FILES['admin_thumb']);
    if (!$file_validation['success']) {
        echo "<script>alert('" . esc($file_validation['message']) . "'); window.history.back();</script>";
        exit();
    }
    
    $admin_thumb_org = $file_validation['new_name'];
    $target_file = "uploads/" . $admin_thumb_org;
    move_uploaded_file($_FILES['admin_thumb']['tmp_name'], $target_file);

    list($stmt_admin, $res_admin) = db_query_prepared($conn, "SELECT admin_id FROM hs_admin WHERE admin_email = ?", [$admin_email]);
    $admin_exists = mysqli_num_rows($res_admin) > 0;
    if ($stmt_admin) mysqli_stmt_close($stmt_admin);

    list($stmt_doc, $res_doc) = db_query_prepared($conn, "SELECT doctor_id FROM hs_doctor WHERE doctor_email = ?", [$admin_email]);
    $doctor_exists = mysqli_num_rows($res_doc) > 0;
    if ($stmt_doc) mysqli_stmt_close($stmt_doc);

    if ($admin_exists || $doctor_exists) {
        echo "<script>alert('Email already taken.'); window.history.back();</script>";
        exit();
    }

    // Fetch doctor role ID dynamically
    $doctor_role_id = 9;
    list($role_stmt, $role_res) = db_query_prepared($conn, "SELECT role_id FROM hs_role WHERE role_title = 'doctor'");
    if ($role_res && $role_row = mysqli_fetch_assoc($role_res)) {
        $doctor_role_id = intval($role_row['role_id']);
    }
    if ($role_stmt) mysqli_stmt_close($role_stmt);

    $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
    $query = false;

    if ($role_id == $doctor_role_id) {
        $parts = explode(' ', $admin_username, 2);
        $fname = $parts[0];
        $mname = '';
        $lname = $parts[1] ?? '';
        $category_id = 1; // Default Category
        $sub_category_id = 1; // Default subcategory
        $service_id = 1; // Default service
        $phone = '';
        $address = '';
        $dob = '2000-01-01';
        $doj = date('Y-m-d');
        $gender = 'male';
        
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO hs_doctor (role_id, category_id, sub_category_id, service_id, doctor_fname, doctor_mname, doctor_lname, doctor_email, doctor_password, doctor_phone, doctor_address, doctor_dob, doctor_doj, doctor_gender, doctor_thumb) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insert_stmt) {
            mysqli_stmt_bind_param($insert_stmt, "iiiisssssssssss", $role_id, $category_id, $sub_category_id, $service_id, $fname, $mname, $lname, $admin_email, $hashed_password, $phone, $address, $dob, $doj, $gender, $admin_thumb_org);
            $query = mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
        }
    } else {
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO hs_admin(admin_username, admin_email, admin_password, admin_thumb, role_id) VALUES (?, ?, ?, ?, ?)");
        if ($insert_stmt) {
            mysqli_stmt_bind_param($insert_stmt, "ssssi", $admin_username, $admin_email, $hashed_password, $admin_thumb_org, $role_id);
            $query = mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
        }
    }

    if ($query) {
        echo "<script>alert('Registered successfully.'); window.location.href='login.php';</script>";
        exit();
    } else {
        echo "<script>alert('Data could not be inserted.'); window.history.back();</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width initial-scale=1.0">
    <title>Hope Rise Medical | Register</title>
    <!-- GLOBAL MAINLY STYLES-->
    <link href="./assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="./assets/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
    <link href="./assets/vendors/themify-icons/css/themify-icons.css" rel="stylesheet" />
    <!-- THEME STYLES-->
    <link href="assets/css/main.css" rel="stylesheet" />
    <!-- PAGE LEVEL STYLES-->
    <link href="./assets/css/pages/auth-light.css" rel="stylesheet" />
</head>

<body class="bg-silver-300">
    <div class="content">
        <div class="brand">
            <a class="link" href="index.php">Hope Rise Medical</a>
        </div>
        <form action="" method="post" enctype="multipart/form-data">
            <!-- CSRF Token -->
            <?php echo get_csrf_input_html(); ?>

            <h2 class="login-title">Sign Up</h2>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <input class="form-control" type="text" name="admin_username" placeholder="User Name" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <input class="form-control" type="email" name="admin_email" placeholder="Email" autocomplete="off" required>
            </div>
            <div class="form-group">
                <input class="form-control" id="password" type="password" name="admin_password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input class="form-control" type="file" name="admin_thumb" placeholder="Thumb" required>
            </div>
            <div class="form-group">
                <select class="form-control" name="role_id" required>
                    <option value="" disabled selected>Choose Role</option>
                    <?php
                        list($role_stmt, $role_data) = db_query_prepared($conn, "SELECT * FROM hs_role");
                        while($role_row = mysqli_fetch_array($role_data))
                        {
                    ?>
                    <option value="<?php echo esc($role_row['role_id']);?>"><?php echo esc($role_row['role_title']);?></option>
                    <?php
                        }
                        if ($role_stmt) mysqli_stmt_close($role_stmt);
                    ?>
                </select>
            </div>
            <div class="form-group text-left">
                <label class="ui-checkbox ui-checkbox-info">
                    <input type="checkbox" name="agree" required>
                    <span class="input-span"></span>I agree the terms and policy</label>
            </div>
            <div class="form-group">
                <button class="btn btn-info btn-block" type="submit" name="submit">Sign up</button>
            </div>
            
            <div class="text-center">Already a member?
                <a class="color-blue" href="login.php">Login here</a>
            </div>
        </form>
    </div>
    <!-- BEGIN PAGA BACKDROPS-->
    <div class="sidenav-backdrop backdrop"></div>
    <div class="preloader-backdrop">
        <div class="page-preloader">Loading</div>
    </div>
    <!-- END PAGA BACKDROPS-->
    <!-- CORE PLUGINS -->
    <script src="./assets/vendors/jquery/dist/jquery.min.js" type="text/javascript"></script>
    <script src="./assets/vendors/popper.js/dist/umd/popper.min.js" type="text/javascript"></script>
    <script src="./assets/vendors/bootstrap/dist/js/bootstrap.min.js" type="text/javascript"></script>
    <!-- PAGE LEVEL PLUGINS -->
    <script src="./assets/vendors/jquery-validation/dist/jquery.validate.min.js" type="text/javascript"></script>
    <!-- CORE SCRIPTS-->
    <script src="assets/js/app.js" type="text/javascript"></script>
</body>

</html>