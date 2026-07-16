<?php
    include('common/css.php');
    
    $message = '';
    $error = '';
    $admin_id = intval($_SESSION['admin_id']);

    // Pre-load profile details for the initial fetch or processing
    if ($role == 'admin') {
        list($profile_stmt, $profile_data) = db_query_prepared($conn, "SELECT * FROM hs_admin WHERE admin_id = ?", [$admin_id]);
        $profile_row = mysqli_fetch_array($profile_data);
        if ($profile_stmt) mysqli_stmt_close($profile_stmt);
    } else {
        list($profile_stmt, $profile_data) = db_query_prepared($conn, "SELECT * FROM hs_doctor WHERE doctor_id = ?", [$admin_id]);
        $profile_row = mysqli_fetch_array($profile_data);
        if ($profile_stmt) mysqli_stmt_close($profile_stmt);
    }

    if (isset($_POST['update_profile'])) {
        // CSRF Check
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            $error = "CSRF Verification Failed";
        } else {
            if ($role == 'admin') {
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $new_password = $_POST['new_password'];
                
                // Validation
                if (empty($username) || empty($email)) {
                    $error = "Username and Email are required.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format.";
                } else {
                    // Check duplicate email
                    list($dup_stmt, $dup_res) = db_query_prepared($conn, "SELECT admin_id FROM hs_admin WHERE admin_email = ? AND admin_id != ?", [$email, $admin_id]);
                    if ($dup_res && mysqli_num_rows($dup_res) > 0) {
                        $error = "Email address is already in use by another administrator.";
                    }
                    if ($dup_stmt) mysqli_stmt_close($dup_stmt);
                    
                    // Avatar Upload check (optional upload)
                    $avatar_name = $profile_row['admin_thumb'];
                    if (isset($_FILES['admin_thumb']) && $_FILES['admin_thumb']['error'] == UPLOAD_ERR_OK) {
                        $file_validation = validate_file_upload($_FILES['admin_thumb']);
                        if (!$file_validation['success']) {
                            $error = $file_validation['message'];
                        } else {
                            $avatar_name = $file_validation['new_name'];
                            $path = "uploads/";
                            move_uploaded_file($_FILES['admin_thumb']['tmp_name'], $path . $avatar_name);
                        }
                    }

                    if (empty($error)) {
                        // Update admin details
                        if (!empty($new_password)) {
                            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                            list($upd_stmt, $upd_res) = db_query_prepared($conn, "UPDATE hs_admin SET admin_username = ?, admin_email = ?, admin_password = ?, admin_thumb = ? WHERE admin_id = ?", [$username, $email, $hashed, $avatar_name, $admin_id], 'ssssi');
                        } else {
                            list($upd_stmt, $upd_res) = db_query_prepared($conn, "UPDATE hs_admin SET admin_username = ?, admin_email = ?, admin_thumb = ? WHERE admin_id = ?", [$username, $email, $avatar_name, $admin_id], 'sssi');
                        }
                        
                        if ($upd_res) {
                            $message = "Profile updated successfully.";
                            $_SESSION['admin_username'] = $username;
                            $_SESSION['admin_email'] = $email;
                        } else {
                            $error = "Failed to update profile.";
                        }
                        if ($upd_stmt) mysqli_stmt_close($upd_stmt);
                    }
                }
            } elseif ($role == 'doctor') {
                $fname = trim($_POST['doctor_fname']);
                $mname = trim($_POST['doctor_mname']);
                $lname = trim($_POST['doctor_lname']);
                $email = trim($_POST['doctor_email']);
                $phone = trim($_POST['doctor_phone']);
                $address = trim($_POST['doctor_address']);
                $dob = $_POST['doctor_dob'];
                $gender = $_POST['doctor_gender'];
                $new_password = $_POST['new_password'];
                
                // Validation
                if (empty($fname) || empty($lname) || empty($email) || empty($phone) || empty($address) || empty($dob) || empty($gender)) {
                    $error = "All fields except middle name and password are required.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format.";
                } else {
                    // Check duplicate email
                    list($dup_stmt, $dup_res) = db_query_prepared($conn, "SELECT doctor_id FROM hs_doctor WHERE doctor_email = ? AND doctor_id != ?", [$email, $admin_id]);
                    if ($dup_res && mysqli_num_rows($dup_res) > 0) {
                        $error = "Email address is already in use by another doctor.";
                    }
                    if ($dup_stmt) mysqli_stmt_close($dup_stmt);
                    
                    // Avatar Upload check (optional upload)
                    $avatar_name = $profile_row['doctor_thumb'];
                    if (isset($_FILES['doctor_thumb']) && $_FILES['doctor_thumb']['error'] == UPLOAD_ERR_OK) {
                        $file_validation = validate_file_upload($_FILES['doctor_thumb']);
                        if (!$file_validation['success']) {
                            $error = $file_validation['message'];
                        } else {
                            $avatar_name = $file_validation['new_name'];
                            $path = "uploads/";
                            move_uploaded_file($_FILES['doctor_thumb']['tmp_name'], $path . $avatar_name);
                        }
                    }
                    
                    if (empty($error)) {
                        // Update doctor details
                        if (!empty($new_password)) {
                            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                            list($upd_stmt, $upd_res) = db_query_prepared($conn, "UPDATE hs_doctor SET doctor_fname = ?, doctor_mname = ?, doctor_lname = ?, doctor_email = ?, doctor_phone = ?, doctor_address = ?, doctor_dob = ?, doctor_gender = ?, doctor_thumb = ?, doctor_password = ? WHERE doctor_id = ?", [$fname, $mname, $lname, $email, $phone, $address, $dob, $gender, $avatar_name, $hashed, $admin_id], 'ssssssssssi');
                        } else {
                            list($upd_stmt, $upd_res) = db_query_prepared($conn, "UPDATE hs_doctor SET doctor_fname = ?, doctor_mname = ?, doctor_lname = ?, doctor_email = ?, doctor_phone = ?, doctor_address = ?, doctor_dob = ?, doctor_gender = ?, doctor_thumb = ? WHERE doctor_id = ?", [$fname, $mname, $lname, $email, $phone, $address, $dob, $gender, $avatar_name, $admin_id], 'sssssssssi');
                        }
                        
                        if ($upd_res) {
                            $message = "Profile updated successfully.";
                            $_SESSION['admin_username'] = $fname . ' ' . $lname;
                            $_SESSION['admin_email'] = $email;
                        } else {
                            $error = "Failed to update profile.";
                        }
                        if ($upd_stmt) mysqli_stmt_close($upd_stmt);
                    }
                }
            }
            
            // Reload profile details after updating
            if ($role == 'admin') {
                list($profile_stmt, $profile_data) = db_query_prepared($conn, "SELECT * FROM hs_admin WHERE admin_id = ?", [$admin_id]);
                $profile_row = mysqli_fetch_array($profile_data);
                if ($profile_stmt) mysqli_stmt_close($profile_stmt);
            } else {
                list($profile_stmt, $profile_data) = db_query_prepared($conn, "SELECT * FROM hs_doctor WHERE doctor_id = ?", [$admin_id]);
                $profile_row = mysqli_fetch_array($profile_data);
                if ($profile_stmt) mysqli_stmt_close($profile_stmt);
            }
            
            // Refresh dynamic user_avatar and display_name for header/sidebar
            if ($role == 'admin') {
                $display_name = $profile_row['admin_username'];
                if (!empty($profile_row['admin_thumb'])) {
                    $user_avatar = 'uploads/' . $profile_row['admin_thumb'];
                }
            } else {
                $display_name = $profile_row['doctor_fname'] . ' ' . $profile_row['doctor_lname'];
                if (!empty($profile_row['doctor_thumb'])) {
                    $user_avatar = 'uploads/' . $profile_row['doctor_thumb'];
                }
            }
        }
    }
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
        ?>
        <!-- END SIDEBAR-->
        <div class="content-wrapper">
            <!-- START PAGE CONTENT-->
            <div class="page-heading">
                <h1 class="page-title">Profile</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="la la-home font-20"></i></a>
                    </li>
                    <li class="breadcrumb-item">Profile</li>
                </ol>
            </div>
            <div class="page-content fade-in-up">
                <div class="row">
                    <div class="col-lg-3 col-md-4">
                        <div class="ibox">
                            <div class="ibox-body text-center">
                                <div class="m-t-20">
                                    <img class="img-circle" src="<?php echo esc($user_avatar); ?>" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover;" />
                                </div>
                                <h5 class="font-strong m-b-10 m-t-10"><?php echo esc($display_name); ?></h5>
                                <div class="m-b-20 text-muted"><?php echo esc($role); ?></div> 
                            </div>
                        </div>
                        <!-- Account details -->
                        <div class="ibox">
                            <div class="ibox-body">
                                <h6 class="m-b-15 font-strong"><i class="fa fa-info-circle text-primary"></i> Account Details</h6>
                                <ul class="list-group list-group-full list-group-divider font-13">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-muted">Role</span>
                                        <span class="font-strong text-capitalize"><?php echo esc($role); ?></span>
                                    </li>
                                    <?php if ($role == 'admin'): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Email</span>
                                            <span><?php echo esc($profile_row['admin_email'] ?? ''); ?></span>
                                        </li>
                                    <?php elseif ($role == 'doctor'): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Email</span>
                                            <span><?php echo esc($profile_row['doctor_email'] ?? ''); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Phone</span>
                                            <span><?php echo esc($profile_row['doctor_phone'] ?? ''); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Gender</span>
                                            <span class="text-capitalize"><?php echo esc($profile_row['doctor_gender'] ?? ''); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Date of Birth</span>
                                            <span><?php echo esc($profile_row['doctor_dob'] ?? ''); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Joined Date</span>
                                            <span><?php echo esc($profile_row['doctor_doj'] ?? ''); ?></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                                <?php if ($role == 'doctor' && !empty($profile_row['doctor_address'])): ?>
                                    <div class="m-t-15 font-13">
                                        <div class="text-muted m-b-5">Address</div>
                                        <div class="font-strong"><?php echo esc($profile_row['doctor_address']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                
            </div>
                   <div class="col-lg-9 col-md-8">
                        <div class="ibox">
                            <div class="ibox-body">
                                <ul class="nav nav-tabs tabs-line">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#tab-1" data-bs-toggle="tab"><i class="ti-bar-chart"></i> Overview</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#tab-2" data-bs-toggle="tab"><i class="ti-settings"></i> Settings</a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="tab-1">
                                        <?php
                                        if ($role == 'doctor') {
                                            $specialty_name = 'N/A';
                                            $category_name = 'N/A';
                                            list($svc_stmt, $svc_res) = db_query_prepared($conn, "
                                                SELECT s.service_title, c.category_name 
                                                FROM hs_doctor d 
                                                LEFT JOIN hs_service s ON d.service_id = s.service_id 
                                                LEFT JOIN hs_category c ON d.category_id = c.category_id 
                                                WHERE d.doctor_id = ?", [$admin_id]);
                                            if ($svc_res && $svc_row = mysqli_fetch_assoc($svc_res)) {
                                                $specialty_name = $svc_row['service_title'] ?? 'N/A';
                                                $category_name = $svc_row['category_name'] ?? 'N/A';
                                            }
                                            if ($svc_stmt) mysqli_stmt_close($svc_stmt);
                                            ?>
                                            <div class="alert alert-info d-flex align-items-center m-b-20" role="alert" style="border-radius: 8px;">
                                                <i class="fa fa-user-md font-24 m-r-15 text-info"></i>
                                                <div>
                                                    <h5 class="alert-heading m-b-5">Medical Assignment Details</h5>
                                                    <strong>Category / Department:</strong> <?php echo esc($category_name); ?> | 
                                                    <strong>Assigned Clinical Service:</strong> <?php echo esc($specialty_name); ?>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <h4 class="text-info m-b-20 m-t-20"><i class="fa fa-calendar-check-o"></i> Recent Appointments</h4>
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Patient Name</th>
                                                    <th>Age</th>
                                                    <th>Service / Reason</th>
                                                    <th>Status</th>
                                                    <th width="120px">Date / Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if ($role == 'doctor') {
                                                    list($appts_stmt, $appts_res) = db_query_prepared($conn, "
                                                        SELECT a.*, p.patient_fname, p.patient_lname, p.patient_dob, s.service_title 
                                                        FROM hs_appointment a 
                                                        LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
                                                        LEFT JOIN hs_service s ON a.service_id = s.service_id
                                                        WHERE a.doctor_id = ? 
                                                        ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 5", [$admin_id]);
                                                } else {
                                                    list($appts_stmt, $appts_res) = db_query_prepared($conn, "
                                                        SELECT a.*, p.patient_fname, p.patient_lname, p.patient_dob, s.service_title 
                                                        FROM hs_appointment a 
                                                        LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
                                                        LEFT JOIN hs_service s ON a.service_id = s.service_id
                                                        ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 5");
                                                }

                                                if ($appts_res && mysqli_num_rows($appts_res) > 0) {
                                                    while ($appt_row = mysqli_fetch_assoc($appts_res)) {
                                                        $patient_name = esc(($appt_row['patient_fname'] ?? '') . ' ' . ($appt_row['patient_lname'] ?? ''));
                                                        
                                                        // Calculate age
                                                        $age = 'N/A';
                                                        if (!empty($appt_row['patient_dob'])) {
                                                            $dob_dt = new DateTime($appt_row['patient_dob']);
                                                            $now_dt = new DateTime();
                                                            $age = $now_dt->diff($dob_dt)->y . ' yrs';
                                                        }
                                                        
                                                        $service_lbl = esc($appt_row['service_title'] ?? $appt_row['appointment_description']);
                                                        $status_lbl = esc($appt_row['appointment_status']);
                                                        $badge_class = 'badge-warning';
                                                        if ($status_lbl == 'Accept') {
                                                            $badge_class = 'badge-success';
                                                            $status_lbl = 'Confirmed';
                                                        } elseif ($status_lbl == 'Reject') {
                                                            $badge_class = 'badge-danger';
                                                            $status_lbl = 'Rejected';
                                                        }
                                                        
                                                        $date_time = esc($appt_row['appointment_date'] . ' ' . $appt_row['appointment_time']);
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $patient_name; ?></td>
                                                            <td><?php echo $age; ?></td>
                                                            <td><?php echo $service_lbl; ?></td>
                                                            <td>
                                                                <span class="badge <?php echo $badge_class; ?>"><?php echo $status_lbl; ?></span>
                                                            </td>
                                                            <td><?php echo $date_time; ?></td>
                                                        </tr>
                                                        <?php
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='5' class='text-center text-muted py-3'>No recent appointments.</td></tr>";
                                                }
                                                if ($appts_stmt) mysqli_stmt_close($appts_stmt);
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="tab-pane fade" id="tab-2">
                                        <?php if (!empty($message)): ?>
                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                <?php echo esc($message); ?>
                                                <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($error)): ?>
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                <?php echo esc($error); ?>
                                                <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form action="" method="post" enctype="multipart/form-data">
                                            <!-- CSRF token -->
                                            <?php echo get_csrf_input_html(); ?>
                                            
                                            <?php if ($role == 'admin'): ?>
                                                <div class="form-group">
                                                    <label>Username</label>
                                                    <input class="form-control" type="text" name="username" value="<?php echo esc($profile_row['admin_username'] ?? ''); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input class="form-control" type="email" name="email" value="<?php echo esc($profile_row['admin_email'] ?? ''); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Profile Avatar</label>
                                                    <input class="form-control-file" type="file" name="admin_thumb">
                                                    <small class="form-text text-muted">Leave empty to keep current avatar.</small>
                                                </div>
                                            <?php elseif ($role == 'doctor'): ?>
                                                <div class="row">
                                                    <div class="col-sm-4 form-group">
                                                        <label>First Name</label>
                                                        <input class="form-control" type="text" name="doctor_fname" value="<?php echo esc($profile_row['doctor_fname'] ?? ''); ?>" required>
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label>Middle Name</label>
                                                        <input class="form-control" type="text" name="doctor_mname" value="<?php echo esc($profile_row['doctor_mname'] ?? ''); ?>">
                                                    </div>
                                                    <div class="col-sm-4 form-group">
                                                        <label>Last Name</label>
                                                        <input class="form-control" type="text" name="doctor_lname" value="<?php echo esc($profile_row['doctor_lname'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-6 form-group">
                                                        <label>Email</label>
                                                        <input class="form-control" type="email" name="doctor_email" value="<?php echo esc($profile_row['doctor_email'] ?? ''); ?>" required>
                                                    </div>
                                                    <div class="col-sm-6 form-group">
                                                        <label>Phone Number</label>
                                                        <input class="form-control" type="tel" name="doctor_phone" value="<?php echo esc($profile_row['doctor_phone'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-6 form-group">
                                                        <label>Date of Birth</label>
                                                        <input class="form-control" type="date" name="doctor_dob" value="<?php echo esc($profile_row['doctor_dob'] ?? ''); ?>" required>
                                                    </div>
                                                    <div class="col-sm-6 form-group">
                                                        <label>Gender</label>
                                                        <select class="form-control" name="doctor_gender" required>
                                                            <option value="male" <?php if (($profile_row['doctor_gender'] ?? '') == 'male') echo 'selected'; ?>>Male</option>
                                                            <option value="female" <?php if (($profile_row['doctor_gender'] ?? '') == 'female') echo 'selected'; ?>>Female</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Address</label>
                                                    <textarea class="form-control" name="doctor_address" rows="3" required><?php echo esc($profile_row['doctor_address'] ?? ''); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Profile Avatar</label>
                                                    <input class="form-control-file" type="file" name="doctor_thumb">
                                                    <small class="form-text text-muted">Leave empty to keep current avatar.</small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="form-group">
                                                <label>New Password (Optional)</label>
                                                <input class="form-control" type="password" name="new_password" placeholder="Leave empty to keep existing password">
                                            </div>
                                            
                                            <div class="form-group m-t-20">
                                                <button class="btn btn-primary" name="update_profile" type="submit">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <style>
                    .profile-social a {
                        font-size: 16px;
                        margin: 0 10px;
                        color: #999;
                    }

                    .profile-social a:hover {
                        color: #485b6f;
                    }

                    .profile-stat-count {
                        font-size: 22px
                    }
                </style>
                
            </div>
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