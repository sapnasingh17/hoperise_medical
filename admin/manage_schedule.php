<?php
include('common/css.php');

$selected_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
if ($role == 'doctor') {
    $selected_doctor_id = intval($_SESSION['admin_id']);
}
$message = '';
$error = '';

// Handle Form Submission
if (isset($_POST['save_schedule']) && $selected_doctor_id > 0) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF Verification Failed";
    } else {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $success_count = 0;
        
        foreach ($days as $day) {
            $start_key = 'start_' . $day;
            $end_key = 'end_' . $day;
            $duration_key = 'duration_' . $day;
            $status_key = 'status_' . $day;
            
            $start_time = isset($_POST[$start_key]) ? $_POST[$start_key] : '09:00:00';
            $end_time = isset($_POST[$end_key]) ? $_POST[$end_key] : '17:00:00';
            $duration = isset($_POST[$duration_key]) ? intval($_POST[$duration_key]) : 30;
            $status = isset($_POST[$status_key]) ? 1 : 0;
            
            // Ensure times have correct H:i:s format
            if (strlen($start_time) == 5) $start_time .= ':00';
            if (strlen($end_time) == 5) $end_time .= ':00';
            
            // Insert or Update schedule
            $sql = "INSERT INTO hs_doctor_schedule (doctor_id, day_of_week, start_time, end_time, slot_duration, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    start_time = VALUES(start_time), 
                    end_time = VALUES(end_time), 
                    slot_duration = VALUES(slot_duration), 
                    status = VALUES(status)";
                    
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isssii", $selected_doctor_id, $day, $start_time, $end_time, $duration, $status);
                if (mysqli_stmt_execute($stmt)) {
                    $success_count++;
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        if ($success_count > 0) {
            $message = "Doctor schedule updated successfully for $success_count days.";
        } else {
            $error = "Error updating schedule.";
        }
    }
}

// Fetch doctor list
$doctors_res = null;
$doc_stmt = null;
if ($role == 'admin') {
    list($doc_stmt, $doctors_res) = db_query_prepared($conn, "SELECT doctor_id, doctor_fname, doctor_lname FROM hs_doctor ORDER BY doctor_fname ASC");
}

// Fetch current schedule for the selected doctor
$schedule = [];
if ($selected_doctor_id > 0) {
    list($sched_stmt, $sched_res) = db_query_prepared($conn, "SELECT * FROM hs_doctor_schedule WHERE doctor_id = ?", [$selected_doctor_id]);
    while ($row = mysqli_fetch_assoc($sched_res)) {
        $schedule[$row['day_of_week']] = $row;
    }
    if ($sched_stmt) {
        mysqli_stmt_close($sched_stmt);
    }
}

// Default schedule helper values
$default_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<body class="fixed-navbar">
    <div class="page-wrapper">
        <!-- START HEADER-->
        <?php include('common/header.php'); ?>
        <!-- END HEADER-->
        <!-- START SIDEBAR-->
        <?php include('common/sidebar.php'); ?>
        <!-- END SIDEBAR-->
        <div class="content-wrapper">
            <!-- START PAGE CONTENT-->
            <div class="page-heading">
                <h1 class="page-title">Doctor Schedules</h1>
            </div>
            <div class="page-content fade-in-up">
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Doctor Selector Card -->
                    <?php if ($role == 'admin'): ?>
                    <div class="col-md-12 mb-4">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Select Practitioner</div>
                            </div>
                            <div class="ibox-body">
                                <div class="form-group row align-items-center">
                                    <label class="col-sm-2 col-form-label fw-bold">Select Doctor:</label>
                                    <div class="col-sm-6">
                                        <select class="form-control" id="doctor_selector" onchange="location = this.value;">
                                            <option value="manage_schedule.php">-- Choose Doctor --</option>
                                            <?php while ($doc = mysqli_fetch_assoc($doctors_res)): 
                                                $selected = ($doc['doctor_id'] == $selected_doctor_id) ? 'selected' : '';
                                            ?>
                                                <option value="manage_schedule.php?doctor_id=<?= esc($doc['doctor_id']) ?>" <?= $selected ?>>
                                                    Dr. <?= esc($doc['doctor_fname'] . ' ' . $doc['doctor_lname']) ?>
                                                </option>
                                            <?php endwhile; 
                                            if ($doc_stmt) {
                                                mysqli_stmt_close($doc_stmt);
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($selected_doctor_id > 0): ?>
                    <!-- Schedule Form Card -->
                    <div class="col-md-12">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Weekly Consultation Schedule Settings</div>
                            </div>
                            <div class="ibox-body">
                                <form method="POST" action="">
                                    <!-- CSRF Token -->
                                    <?php echo get_csrf_input_html(); ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-middle">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Day of the Week</th>
                                                    <th>Status</th>
                                                    <th>Start Time</th>
                                                    <th>End Time</th>
                                                    <th>Slot Duration (minutes)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($default_days as $day): 
                                                    // Get existing data or use default values
                                                    $is_active = isset($schedule[$day]) ? intval($schedule[$day]['status']) : (($day != 'Saturday' && $day != 'Sunday') ? 1 : 0);
                                                    $start = isset($schedule[$day]) ? date('H:i', strtotime($schedule[$day]['start_time'])) : '09:00';
                                                    $end = isset($schedule[$day]) ? date('H:i', strtotime($schedule[$day]['end_time'])) : '17:00';
                                                    $duration = isset($schedule[$day]) ? intval($schedule[$day]['slot_duration']) : 30;
                                                ?>
                                                <tr>
                                                    <td class="fw-bold"><?= $day ?></td>
                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="status_<?= $day ?>" value="1" <?= $is_active ? 'checked' : '' ?>>
                                                            <label class="form-check-label small text-secondary"><?= $is_active ? 'Working' : 'Day Off' ?></label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="time" name="start_<?= $day ?>" class="form-control form-control-sm" value="<?= $start ?>" required>
                                                    </td>
                                                    <td>
                                                        <input type="time" name="end_<?= $day ?>" class="form-control form-control-sm" value="<?= $end ?>" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="duration_<?= $day ?>" class="form-control form-control-sm" min="10" max="180" step="5" value="<?= $duration ?>" required>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="form-group text-end mt-4">
                                        <button type="submit" name="save_schedule" class="btn btn-primary px-4 py-2"><i class="fa fa-save me-2"></i>Save Weekly Schedule</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-12 text-center py-5 text-secondary">
                        <i class="fa fa-calendar-times-o fa-4x mb-3 text-muted"></i>
                        <h5>Please select a practitioner above to customize their schedule.</h5>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
            <!-- END PAGE CONTENT-->
            <?php include('common/footer.php'); ?>
        </div>
    </div>
    <!-- BEGIN THEME CONFIG PANEL-->
    <?php include('common/configue_panel.php'); ?>
    <!-- END THEME CONFIG PANEL-->
    <!-- BEGIN PAGA BACKDROPS-->
    <?php include('common/preloader.php'); ?>
    <!-- END PAGA BACKDROPS-->
    <!-- CORE PLUGINS-->
    <?php include('common/main_footer.php'); ?>
</body>
</html>
