<?php
/*
// Old Logic:
    $conn=mysqli_connect("localhost","root","","hoperise");
    if(isset($_POST['submit']))
    {
        $admin_email=$_POST['admin_email'];
        $email_query=mysqli_query($conn,"select * from hs_admin where admin_email='$admin_email'");
        
        if(mysqli_num_rows($email_query)>0)
        {
            echo "mail sent";
        }
        else
        {
            echo "error";
        }
    }
*/

// New Secure Logic with Prepared Statements:
include_once(__DIR__ . '/../dbcon.php');

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $admin_email = $_POST['admin_email'];
    
    // Query admin email using prepared statements
    list($stmt, $result) = db_query_prepared($conn, "SELECT * FROM hs_admin WHERE admin_email = ?", [$admin_email]);
    
    if (mysqli_num_rows($result) > 0) {
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
        echo "<script>alert('Password reset instructions have been sent to your admin email.'); window.location.href='login.php';</script>";
        exit();
    } else {
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
        // Fallback to checking doctor email
        list($doctor_stmt, $doctor_result) = db_query_prepared($conn, "SELECT * FROM hs_doctor WHERE doctor_email = ?", [$admin_email]);
        if (mysqli_num_rows($doctor_result) > 0) {
            if ($doctor_stmt) {
                mysqli_stmt_close($doctor_stmt);
            }
            echo "<script>alert('Password reset instructions have been sent to your doctor email.'); window.location.href='login.php';</script>";
            exit();
        } else {
            if ($doctor_stmt) {
                mysqli_stmt_close($doctor_stmt);
            }
            echo "<script>alert('No account found with that email address.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width initial-scale=1.0">
    <title>Hope Rise Medical | Forgot password</title>
    <!-- GLOBAL MAINLY STYLES-->
    <link href="./assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" />
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
        <form action=" " method="post">
            <!-- CSRF Token -->
            <?php echo get_csrf_input_html(); ?>
            <h3 class="m-t-10 m-b-10">Forgot password</h3>
            <p class="m-b-20">Enter your email address below and we'll send you password reset instructions.</p>
            <div class="form-group">
                <input class="form-control" type="email" name="admin_email" placeholder="Email" autocomplete="off">
            </div>
            <div class="form-group">
                <button class="btn btn-info btn-block" type="submit"  name="submit">Submit</button>
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
    <!-- PAGE LEVEL SCRIPTS-->
    <script type="text/javascript">
        $(function() {
            $('#forgot-form').validate({
                errorClass: "help-block",
                rules: {
                    email: {
                        required: true,
                        email: true
                    },
                },
                highlight: function(e) {
                    $(e).closest(".form-group").addClass("has-error")
                },
                unhighlight: function(e) {
                    $(e).closest(".form-group").removeClass("has-error")
                },
            });
        });
    </script>
</body>

</html>