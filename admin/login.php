<?php
// New Secure Logic with Prepared Statements and BCrypt:
include_once(__DIR__ . '/../dbcon.php');

if (isset($_SESSION['admin_id'])) {
    header("Location:index.php");
    exit();
}

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $role_title = $_POST['role_title'] ?? '';
    
    if ($role_title == 'admin') {
        list($stmt, $result) = db_query_prepared($conn, "SELECT * FROM hs_admin WHERE admin_email = ?", [$admin_email]);
        
        if (mysqli_num_rows($result) > 0) {
            $email_row = mysqli_fetch_array($result);
            
            // Verify password using standard BCrypt hashing
            if (password_verify($admin_password, $email_row['admin_password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                $_SESSION['admin_id'] = $email_row['admin_id'];
                $_SESSION['admin_username'] = $email_row['admin_username'];
                $_SESSION['role_id'] = $email_row['role_id'];
                $_SESSION['admin_email'] = $email_row['admin_email'];
                if ($stmt) mysqli_stmt_close($stmt);
                header("Location:index.php");
                exit();
            } else {
                echo "<script>alert('Password Incorrect');</script>";
            }
        } else {
            echo "<script>alert('Admin email not registered');</script>";
        }
        if ($stmt) mysqli_stmt_close($stmt);
    } else {
        list($stmt, $result) = db_query_prepared($conn, "SELECT * FROM hs_doctor WHERE doctor_email = ?", [$admin_email]);
        
        if (mysqli_num_rows($result) > 0) {
            $email_row = mysqli_fetch_array($result);
            
            // Verify password using standard BCrypt hashing
            if (password_verify($admin_password, $email_row['doctor_password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                $_SESSION['admin_id'] = $email_row['doctor_id'];
                $_SESSION['admin_username'] = $email_row['doctor_fname'] . " " . $email_row['doctor_lname'];
                $_SESSION['role_id'] = $email_row['role_id'];
                $_SESSION['admin_email'] = $email_row['doctor_email'];
                if ($stmt) mysqli_stmt_close($stmt);
                header("Location:index.php");
                exit();
            } else {
                echo "<script>alert('Password Incorrect');</script>";
            }
        } else {
            echo "<script>alert('Doctor email not registered');</script>";
        }
        if ($stmt) mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width initial-scale=1.0">
    <title>Hope Rise Medical | Login</title>
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
        <form  action="" method="post">
            <!-- CSRF token -->
            <?php echo get_csrf_input_html(); ?>
            
            <h2 class="login-title">Log in</h2>
            <div class="form-group">
                <div class="input-group-icon right">
                    <div class="input-icon"><i class="fa fa-envelope"></i></div>
                    <input class="form-control" type="email" name="admin_email" placeholder="Email" autocomplete="off" required>
                </div>
            </div>
            <div class="form-group">
                <div class="input-group-icon right">
                    <div class="input-icon"><i class="fa fa-lock font-16"></i></div>
                    <input class="form-control" type="password" name="admin_password" placeholder="Password" required>
                </div>
            </div>
            <div class="form-group">
                <div class="input-group-icon right">
                    <div class="input-icon"><i class="fa fa-lock font-16"></i></div>
                    <select class="form-control" name="role_title" required>
                        <?php
                        list($select_role_stmt, $select_role) = db_query_prepared($conn, "SELECT * FROM hs_role");
                        while($select_row = mysqli_fetch_array($select_role))
                        {
                        ?>
                        <option value="<?php echo esc($select_row['role_title']); ?>"><?php echo esc($select_row['role_title']); ?></option>
                        <?php
                        }
                        if ($select_role_stmt) {
                            mysqli_stmt_close($select_role_stmt);
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group d-flex justify-content-between">
                <label class="ui-checkbox ui-checkbox-info">
                    <input type="checkbox">
                    <span class="input-span"></span>Remember me</label>
                <a href="forgot_password.php">Forgot password?</a>
            </div>
            <div class="form-group">
                <button class="btn btn-info btn-block" type="submit" name="submit">Login</button>
            </div>
            
            <div class="text-center">Not a member?
                <a class="color-blue" href="register.php">Create accaunt</a>
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