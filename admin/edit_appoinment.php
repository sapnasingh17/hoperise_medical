<?php
include('common/css.php');
include_once(__DIR__ . '/../email_helper.php');

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
list($select_stmt, $select_appointment) = db_query_prepared($conn, "SELECT * FROM hs_appointment WHERE appointment_id = ?", [$appointment_id]);
$appointment_row = mysqli_fetch_array($select_appointment);
if ($select_stmt) {
    mysqli_stmt_close($select_stmt);
}

// Security Check: if doctor, they must own this appointment
if ($appointment_row) {
    if ($role == 'doctor' && intval($appointment_row['doctor_id']) !== intval($_SESSION['admin_id'])) {
        header("HTTP/1.1 403 Forbidden");
        echo "<div style='padding: 20px; font-family: sans-serif; color: #ef4444; font-weight: bold;'>403 Forbidden: You do not have permission to access this appointment.</div>";
        exit();
    }
} else {
    header("HTTP/1.1 404 Not Found");
    echo "<div style='padding: 20px; font-family: sans-serif; color: #64748b;'>Appointment not found.</div>";
    exit();
}

$message = '';
$error = '';

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF Verification Failed";
    } else {
        $appointment_status = $_POST['appointment_status'];
        
        // Update appointment status using prepared statement
        $update_stmt = mysqli_prepare($conn, "UPDATE hs_appointment SET appointment_status = ? WHERE appointment_id = ?");
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "si", $appointment_status, $appointment_id);
            $query = mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            if ($query) {
                // Log to timeline
                $timeline_action = ($appointment_status == 'Accept') ? 'Confirmed' : (($appointment_status == 'Reject') ? 'Rejected' : $appointment_status);
                $comments = "Appointment status updated to " . $appointment_status . " by " . ($role == 'doctor' ? 'doctor' : 'administrator') . ".";
                
                $action_by = ($role == 'doctor') ? 'doctor' : 'admin';
                $timeline_stmt = mysqli_prepare($conn, "INSERT INTO hs_appointment_timeline (appointment_id, status_action, action_by, comments) VALUES (?, ?, ?, ?)");
                if ($timeline_stmt) {
                    mysqli_stmt_bind_param($timeline_stmt, "isss", $appointment_id, $timeline_action, $action_by, $comments);
                    mysqli_stmt_execute($timeline_stmt);
                    mysqli_stmt_close($timeline_stmt);
                }
                
                // Trigger email notifications
                send_appointment_email($conn, $appointment_id, 'status_updated', $comments);
                
                $message = "Appointment status successfully updated and notification sent.";
                
                // Refresh appointment row to reflect new status in the form fields
                list($select_stmt, $select_appointment) = db_query_prepared($conn, "SELECT * FROM hs_appointment WHERE appointment_id = ?", [$appointment_id]);
                $appointment_row = mysqli_fetch_array($select_appointment);
                if ($select_stmt) {
                    mysqli_stmt_close($select_stmt);
                }
            } else {
                $error = "Error updating appointment status.";
            }
        } else {
            $error = "Database error: Could not prepare status update statement.";
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
                            <?php if (!empty($message)): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                             <form action="" method="post">
                                    <!-- CSRF Token -->
                                    <?php echo get_csrf_input_html(); ?>
                                    <div class="row">
                                    <div class="col-sm-12 form-group">
                                         <label>patient Id</label>
                                          <select class="form-control main pt-0 pb-0" name="patient_id" disabled="disabled">
                                         <?php
                                         list($pat_stmt, $patient_data) = db_query_prepared($conn, "select * from hs_patient");
                                         while($patient_row=mysqli_fetch_array($patient_data))
                                         {
                                          ?>
                                        <option value="<?php echo esc($patient_row['patient_id']); ?>" <?php if($patient_row['patient_id'] == $appointment_row['patient_id']) echo 'selected'; ?>><?php echo esc($patient_row['patient_fname']); ?></option>
                                       <?php
                                       }
                                       if ($pat_stmt) mysqli_stmt_close($pat_stmt);
                                         ?>
                                      </select>
                                      </div>
                                        <div class="col-sm-12 form-group">
                                        <label>Doctor Id</label>
                                       <select class="form-control main pt-0 pb-0" name="doctor_id" disabled="disabled">
                                       <?php
                                       list($doc_stmt, $doctor_data) = db_query_prepared($conn, "select * from hs_doctor");
                                       while($doctor_row=mysqli_fetch_array($doctor_data))
                                       {
                                       ?>
                                       <option value="<?php echo esc($doctor_row['doctor_id']); ?>" <?php if($doctor_row['doctor_id'] == $appointment_row['doctor_id']) echo 'selected'; ?>><?php echo esc($doctor_row['doctor_fname']); ?></option>
                                      <?php
                                      }
                                      if ($doc_stmt) mysqli_stmt_close($doc_stmt);
                                       ?>
                                      </select>
                                    </div>
                                    <div class="col-sm-12 form-group">
                                   <label>category name</label>
                                   <select class="form-control main pt-0 pb-0" name="category_id" disabled="disabled">
                                   <?php
                                   list($cat_stmt, $category_data) = db_query_prepared($conn, "select * from hs_category");
                                   while($category_row=mysqli_fetch_array($category_data))
                                  {
                                 ?>
                                 <option value="<?php echo esc($category_row['category_id']); ?>" <?php if($category_row['category_id'] == $appointment_row['category_id']) echo 'selected'; ?>><?php echo esc($category_row['category_title']); ?></option>
                                <?php
                                }
                                if ($cat_stmt) mysqli_stmt_close($cat_stmt);
                               ?>
                             </select>
                              </div>
                               <div class="col-sm-12 form-group">
                               <label>sub_category name</label>
                              <select class="form-control main pt-0 pb-0" name="sub_category_id" disabled="disabled">
                               <?php
                               list($subcat_stmt, $subcat_data) = db_query_prepared($conn, "select * from hs_sub_category");
                               while($category_row=mysqli_fetch_array($subcat_data))
                                 {
                               ?>
                              <option value="<?php echo esc($category_row['sub_category_id']); ?>" <?php if($category_row['sub_category_id'] == $appointment_row['sub_category_id']) echo 'selected'; ?>><?php echo esc($category_row['sub_category_title']); ?></option>
                                <?php
                            }
                            if ($subcat_stmt) mysqli_stmt_close($subcat_stmt);
                               ?>
                              </select>
                                  </div>
                               <div class="col-sm-12 form-group">
                              <label>Service name</label>
                            <select class="form-control main pt-0 pb-0" name="service_id" disabled="disabled">
                              <?php
                              list($serv_stmt, $service_data) = db_query_prepared($conn, "select * from hs_service");
                             while($service_row=mysqli_fetch_array($service_data))
                            {
                                 ?>
                              <option value="<?php echo esc($service_row['service_id']); ?>" <?php if($service_row['service_id'] == $appointment_row['service_id']) echo 'selected'; ?>><?php echo esc($service_row['service_title']); ?></option>
                              <?php
                      }
                      if ($serv_stmt) mysqli_stmt_close($serv_stmt);
                      ?>
                    </select>
                    </div>
                                     
            
                                     <div class="col-sm-12 form-group">
                                              <label>appointment date</label>
                                              <input type="date" disabled="disabled" name="appointment_date" class="form-control main pt-0 pb-0" placeholder="appointment_date" value="<?php echo esc($appointment_row['appointment_date'] ?? ''); ?>" required>
                                          </div>
                                          <div class="col-sm-12 form-group">
                                              <label>appointment time</label>
                                              <input type="time" name="appointment_time" class="form-control main pt-0 pb-0" placeholder="appointment_time" disabled="disabled" value="<?php echo esc($appointment_row['appointment_time'] ?? ''); ?>" required>
                                          </div>
                                          <div class="col-sm-12 form-group">
                                              <label>appointment description</label>
                                              <input type="text" name="appointment_description" class="form-control main pt-0 pb-0" placeholder="appointment_description" disabled="disabled" value="<?php echo esc($appointment_row['appointment_description'] ?? ''); ?>" required>
                                          </div>
                                          <div class="col-sm-12 form-group">
                                              <label>appointment status</label>
                                               <select name="appointment_status" class="form-control main pt-0 pb-0" >
                                                   <option value="Pending" <?php if(strcasecmp($appointment_row['appointment_status'] ?? '', 'Pending') === 0 || empty($appointment_row['appointment_status'])) echo 'selected'; ?>>Pending</option>
                                                   <option value="Accept" <?php if(strcasecmp($appointment_row['appointment_status'] ?? '', 'Accept') === 0) echo 'selected'; ?>>Accept</option>
                                                   <option value="Reject" <?php if(strcasecmp($appointment_row['appointment_status'] ?? '', 'Reject') === 0) echo 'selected'; ?>>Reject</option>
                                               </select>
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