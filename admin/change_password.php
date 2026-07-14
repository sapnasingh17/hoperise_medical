<?php

include('common/css.php');

?>
<body class="fixed-navbar">
    <div class="page-wrapper">
        <!-- START HEADER-->
        <?php
            include('common/header.php');
        ?>
        <!-- END HEADER-->
        <!-- START SIDEBAR-->
        <?php
            include('common/sidebar.php');
            $admin_id=$_SESSION['admin_id'];
            // Old Logic:
            // $conn=mysqli_connect("localhost","root","","hoperise");
            // if(isset($_POST['submit']))
            // {
            //     $password=$_POST['password'];
            //     $new_password=$_POST['new_password'];
            //     $comfirm_password=$_POST['comfirm_password'];
            //     if($role=='admin')
            // {
            //     $query=mysqli_query($conn,"SELECT*FROM hs_admin WHERE admin_password='$password'");
            //     if(mysqli_num_rows($query)>0){
            //         if($new_password == $comfirm_password)
            //         {
            //             $query=mysqli_query($conn,"update hs_admin set admin_password='$comfirm_password'WHERE admin_id=$admin_id");
            //         if($query)
            //         {
            //             echo "Data update succefully";
            //         }
            //         }
            //     }
            //     else{
            //         echo"incorrect password ";
            //     }
            // }
            // else
            // {
            //     $query=mysqli_query($conn,"SELECT*FROM hs_doctor WHERE doctor_password='$password'");
            //     if(mysqli_num_rows($query)>0){
            //         if($new_password == $comfirm_password)
            //         {
            //             $query=mysqli_query($conn,"update hs_doctor set doctor_password='$comfirm_password' WHERE doctor_id=$admin_id");
            //         if($query)
            //         {
            //             echo "Data update succefully";
            //         }
            //         }
            //     }
            //     else{
            //         echo"incorrect password ";
            //     }
            // }
            // }

            // New Secure Logic with Prepared Statements and Hashed Passwords:
            if(isset($_POST['submit']))
            {
                // CSRF verification
                if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                    echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
                    exit();
                }

                $password=$_POST['password'];
                $new_password=$_POST['new_password'];
                $comfirm_password=$_POST['comfirm_password'];
                
                if($new_password !== $comfirm_password) {
                    echo "<script>alert('New password and confirm password do not match.');</script>";
                } else {
                    if($role=='admin')
                    {
                        // Retrieve the existing password hash
                        list($stmt, $result) = db_query_prepared($conn, "SELECT admin_password FROM hs_admin WHERE admin_id = ?", [$admin_id]);
                        if(mysqli_num_rows($result) > 0)
                        {
                            $row = mysqli_fetch_array($result);
                            $db_password = $row['admin_password'];
                            
                            // Verify old password strictly using BCrypt hash
                            if (password_verify($password, $db_password))
                            {
                                $new_hashed = password_hash($new_password, PASSWORD_BCRYPT);
                                list($update_stmt, $update_result) = db_query_prepared($conn, "UPDATE hs_admin SET admin_password = ? WHERE admin_id = ?", [$new_hashed, $admin_id]);
                                if($update_result)
                                {
                                    echo "<script>alert('Password updated successfully.');</script>";
                                }
                                if ($update_stmt) {
                                    mysqli_stmt_close($update_stmt);
                                }
                            }
                            else
                            {
                                echo "<script>alert('Incorrect old password.');</script>";
                            }
                        }
                        if ($stmt) {
                            mysqli_stmt_close($stmt);
                        }
                    }
                    else
                    {
                        // Retrieve the existing password hash for doctor
                        list($stmt, $result) = db_query_prepared($conn, "SELECT doctor_password FROM hs_doctor WHERE doctor_id = ?", [$admin_id]);
                        if(mysqli_num_rows($result) > 0)
                        {
                            $row = mysqli_fetch_array($result);
                            $db_password = $row['doctor_password'];
                            
                            // Verify old password strictly using BCrypt hash
                            if (password_verify($password, $db_password))
                            {
                                $new_hashed = password_hash($new_password, PASSWORD_BCRYPT);
                                list($update_stmt, $update_result) = db_query_prepared($conn, "UPDATE hs_doctor SET doctor_password = ? WHERE doctor_id = ?", [$new_hashed, $admin_id]);
                                if($update_result)
                                {
                                    echo "<script>alert('Password updated successfully.');</script>";
                                }
                                if ($update_stmt) {
                                    mysqli_stmt_close($update_stmt);
                                }
                            }
                            else
                            {
                                echo "<script>alert('Incorrect old password.');</script>";
                            }
                        }
                        if ($stmt) {
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
            }
        ?>
        <!-- END SIDEBAR-->
        <div class="content-wrapper">
            <!-- START PAGE CONTENT-->
            <div class="page-heading">
                <h1 class="page-title">Change Password</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="la la-home font-20"></i></a>
                    </li>
                    <li class="breadcrumb-item">Change Password</li>
                </ol>
            </div>
            <div class="page-content fade-in-up">
                <div class="row">
                    <div class="col-md-6">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Change Password</div>
                                <div class="ibox-tools">
                                    <a class="ibox-collapse"><i class="fa fa-minus"></i></a>
                                    <a class="dropdown-toggle" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item">option 1</a>
                                        <a class="dropdown-item">option 2</a>
                                    </div>
                                </div>
                            </div>
                            <div class="ibox-body">
                            <form action="" method="post">
                                     <!-- CSRF Token -->
                                     <?php echo get_csrf_input_html(); ?>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input class="form-control" type="password"name="password" placeholder="Old_Password">
                                    </div>
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input class="form-control" type="password"name="new_password" placeholder="New_Password">
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm Password</label>
                                        <input class="form-control" type="password"name="comfirm_password" placeholder="Confirm_New_Password">
                                    </div>
                                    <div class="form-group">
                                        <label class="ui-checkbox">
                                            <input type="checkbox">
                                            <span class="input-span"></span>Remamber me</label>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-default" type="submit"name="submit">Submit</button>
                                    </div>
                                </form>
                               
                            </div>
                        </div>
                    </div>
                </div>
                <!--  -->
            <!-- END PAGE CONTENT-->
            <?php
                include('common/footer.php');
            ?>
        </div>
    </div>
    <!-- BEGIN THEME CONFIG PANEL-->
    <?php
        include('common/configue_panel.php');
    ?>
    <!-- END THEME CONFIG PANEL-->
    <!-- BEGIN PAGA BACKDROPS-->
    <?php
        include('common/preloader.php');
    ?>
    <!-- END PAGA BACKDROPS-->
    <!-- CORE PLUGINS-->
    <?php
        include('common/main_footer.php');
    ?>