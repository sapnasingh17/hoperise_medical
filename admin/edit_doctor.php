<?php
include('common/css.php');

$doctor_id = intval($_GET['doctor_id'] ?? 0);

// Fetch existing doctor details
list($select_stmt, $select_doctor) = db_query_prepared($conn, "SELECT * FROM hs_doctor WHERE doctor_id = ?", [$doctor_id]);
$doctor_row = mysqli_fetch_array($select_doctor);
if ($select_stmt) {
    mysqli_stmt_close($select_stmt);
}

if (!$doctor_row) {
    echo "<script>alert('Doctor not found.'); window.location.href='display_doctor.php';</script>";
    exit();
}

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    // Fetch doctor role ID dynamically
    $doctor_role_id = 9;
    list($role_stmt, $role_res) = db_query_prepared($conn, "SELECT role_id FROM hs_role WHERE role_title = 'doctor'");
    if ($role_res && $role_row = mysqli_fetch_assoc($role_res)) {
        $doctor_role_id = intval($role_row['role_id']);
    }
    if ($role_stmt) mysqli_stmt_close($role_stmt);

    $role_id = $doctor_role_id;
    $category_id = intval($_POST['category_id']);
    $sub_category_id = intval($_POST['sub_category_id']);
    $service_id = intval($_POST['service_id']);
    $doctor_fname = $_POST['doctor_fname'];
    $doctor_mname = $_POST['doctor_mname'];
    $doctor_lname = $_POST['doctor_lname'];
    $doctor_email = $_POST['doctor_email'];
    $doctor_password = $_POST['doctor_password'];
    $doctor_phone = $_POST['doctor_phone'];
    $doctor_address = $_POST['doctor_address'];
    $doctor_dob = $_POST['doctor_dob'];
    $doctor_doj = $_POST['doctor_doj'];
    $doctor_gender = $_POST['doctor_gender'];

    $doctor_thumb_org = $doctor_row['doctor_thumb'];

    // Check if a new image is uploaded
    if (!empty($_FILES['doctor_thumb']['name'])) {
        $file_validation = validate_file_upload($_FILES['doctor_thumb']);
        if (!$file_validation['success']) {
            echo "<script>alert('" . esc($file_validation['message']) . "'); window.history.back();</script>";
            exit();
        }
        $doctor_thumb_org = $file_validation['new_name'];
        $path = "uploads/";
        $imagepath = $path . $doctor_thumb_org;
        move_uploaded_file($_FILES['doctor_thumb']['tmp_name'], $imagepath);
    }

    // Handle password hashing if changed
    if ($doctor_password !== $doctor_row['doctor_password']) {
        $doctor_password = password_hash($doctor_password, PASSWORD_BCRYPT);
    }

    // Update the doctor data in the database
    $update_stmt = mysqli_prepare($conn, "UPDATE hs_doctor SET role_id = ?, category_id = ?, sub_category_id = ?, service_id = ?, doctor_fname = ?, doctor_mname = ?, doctor_lname = ?, doctor_email = ?, doctor_password = ?, doctor_phone = ?, doctor_address = ?, doctor_dob = ?, doctor_doj = ?, doctor_gender = ?, doctor_thumb = ? WHERE doctor_id = ?");
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "iiiisssssssssssi", $role_id, $category_id, $sub_category_id, $service_id, $doctor_fname, $doctor_mname, $doctor_lname, $doctor_email, $doctor_password, $doctor_phone, $doctor_address, $doctor_dob, $doctor_doj, $doctor_gender, $doctor_thumb_org, $doctor_id);
        $query = mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        if ($query) {
            echo "<script>alert('Doctor updated successfully!'); window.location.href='display_doctor.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating doctor details!');</script>";
        }
    }
}
?>
<body class="fixed-navbar">
    <div class="page-wrapper">
    <?php
            include('common/header.php');
        ?>
        <!-- END HEADER-->
        <!-- START SIDEBAR-->
        <?php
            include('common/sidebar.php');
        ?>
        <!-- END SIDEBAR-->
        <div class="content-wrapper">
            <!-- START PAGE CONTENT-->
            <div class="page-heading">
                <h1 class="page-title">doctor Form</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="la la-home font-20"></i></a>
                    </li>
                    <li class="breadcrumb-item">doctor Form</li>
                </ol>
            </div>
            <div class="page-content fade-in-up">
                <div class="row">
                    <div class="col-md-6">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">doctor form</div>
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
                            <form action="" method="post" enctype="multipart/form-data">
                                    <!-- CSRF Token -->
                                    <?php echo get_csrf_input_html(); ?>

                                    <div class="row">
                                       <div class="col-sm-12 form-group">
                                            <label>category name</label>
                                            <select class="form-control" name="category_id" required>
                                                <?php
                                                list($cat_stmt, $category_data) = db_query_prepared($conn, "SELECT * FROM hs_category");
                                                while($category_row = mysqli_fetch_array($category_data))
                                                {
                                                ?>
                                                <option value="<?php echo esc($category_row['category_id']); ?>" <?php if($category_row['category_id'] == $doctor_row['category_id']) echo 'selected'; ?>><?php echo esc($category_row['category_title']); ?></option>
                                                <?php
                                                }
                                                if ($cat_stmt) mysqli_stmt_close($cat_stmt);
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>sub_category name</label>
                                            <select class="form-control" name="sub_category_id" required>
                                                <?php
                                                list($scat_stmt, $sub_category_data) = db_query_prepared($conn, "SELECT * FROM hs_sub_category");
                                                while($sub_category_row = mysqli_fetch_array($sub_category_data))
                                                {
                                                ?>
                                                <option value="<?php echo esc($sub_category_row['sub_category_id']); ?>" <?php if($sub_category_row['sub_category_id'] == $doctor_row['sub_category_id']) echo 'selected'; ?>><?php echo esc($sub_category_row['sub_category_title']); ?></option>
                                                <?php
                                                }
                                                if ($scat_stmt) mysqli_stmt_close($scat_stmt);
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Service name</label>
                                            <select class="form-control" name="service_id" required>
                                                <?php
                                                list($svc_stmt, $service_data) = db_query_prepared($conn, "SELECT * FROM hs_service");
                                                while($service_row = mysqli_fetch_array($service_data))
                                                {
                                                ?>
                                                <option value="<?php echo esc($service_row['service_id']); ?>" <?php if($service_row['service_id'] == $doctor_row['service_id']) echo 'selected'; ?>><?php echo esc($service_row['service_title']); ?></option>
                                                <?php
                                                }
                                                if ($svc_stmt) mysqli_stmt_close($svc_stmt);
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor First Name</label>
                                            <input class="form-control" type="text" placeholder="doctor fname" value="<?php echo esc($doctor_row['doctor_fname']); ?>" name="doctor_fname" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor Middle Name</label>
                                            <input class="form-control" type="text" placeholder="doctor mname" value="<?php echo esc($doctor_row['doctor_mname']); ?>" name="doctor_mname">
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor Last Name</label>
                                            <input class="form-control" type="text" placeholder="doctor lname" value="<?php echo esc($doctor_row['doctor_lname']); ?>" name="doctor_lname" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor Email</label>
                                            <input class="form-control" type="email" placeholder="doctor email" value="<?php echo esc($doctor_row['doctor_email']); ?>" name="doctor_email" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor Password</label>
                                            <input class="form-control" type="password" placeholder="doctor password" value="<?php echo esc($doctor_row['doctor_password']); ?>" name="doctor_password" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor Phone</label>
                                            <input class="form-control" type="tel" placeholder="doctor phone" value="<?php echo esc($doctor_row['doctor_phone']); ?>" name="doctor_phone" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor Address</label>
                                            <textarea class="form-control" placeholder="doctor address" name="doctor_address" rows="4" cols="10" required><?php echo esc($doctor_row['doctor_address']); ?></textarea>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor DOB</label>
                                            <input class="form-control" type="date" value="<?php echo esc($doctor_row['doctor_dob']); ?>" name="doctor_dob" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor DOJ</label>
                                            <input class="form-control" type="date" value="<?php echo esc($doctor_row['doctor_doj']); ?>" name="doctor_doj" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor Gender</label>
                                            <div>
                                                <label class="radio-inline"><input type="radio" name="doctor_gender" value="male" <?php if($doctor_row['doctor_gender'] == 'male') echo 'checked'; ?> required> Male</label>
                                                <label class="radio-inline"><input type="radio" name="doctor_gender" value="female" <?php if($doctor_row['doctor_gender'] == 'female') echo 'checked'; ?> required> Female</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Doctor Thumb</label>
                                            <input class="form-control" type="file" name="doctor_thumb">
                                            <small>Current Image: <?php echo esc($doctor_row['doctor_thumb']); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button class="btn btn-default" name="submit" type="submit">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
</body>
</html>