<?php
include_once("dbcon.php");

// 4. Check session authentication.
if (!isset($_SESSION['user_id']) && !isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Map user_id and patient_id for consistency
if (isset($_SESSION['user_id']) && !isset($_SESSION['patient_id'])) {
    $_SESSION['patient_id'] = $_SESSION['user_id'];
} elseif (isset($_SESSION['patient_id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['patient_id'];
}

$user_id = intval($_SESSION['user_id']);

// 6. If patient_id is stored in patients table, first get patient ID:
$patient_id = $user_id; // Default fallback
$table_chk_hs = mysqli_query($conn, "SHOW TABLES LIKE 'hs_patient'");
if ($table_chk_hs && mysqli_num_rows($table_chk_hs) > 0) {
    list($p_stmt_chk, $p_res_chk) = db_query_prepared($conn, "SELECT patient_id FROM hs_patient WHERE patient_id = ?", [$user_id], 'i');
    if ($p_res_chk && mysqli_num_rows($p_res_chk) > 0) {
        $row_p = mysqli_fetch_assoc($p_res_chk);
        $patient_id = intval($row_p['patient_id']);
    }
    if ($p_stmt_chk) {
        mysqli_stmt_close($p_stmt_chk);
    }
} else {
    $table_chk_p = mysqli_query($conn, "SHOW TABLES LIKE 'patients'");
    if ($table_chk_p && mysqli_num_rows($table_chk_p) > 0) {
        list($p_stmt_chk, $p_res_chk) = db_query_prepared($conn, "SELECT id FROM patients WHERE user_id = ?", [$user_id], 'i');
        if ($p_res_chk && mysqli_num_rows($p_res_chk) > 0) {
            $row_p = mysqli_fetch_assoc($p_res_chk);
            $patient_id = intval($row_p['id']);
        }
        if ($p_stmt_chk) {
            mysqli_stmt_close($p_stmt_chk);
        }
    }
}

$profile_errors = [];
$profile_success = "";
$active_tab = 'dashboard';

// Handle Profile Update Request
if (isset($_POST['update_profile'])) {
    $active_tab = 'profile';
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $profile_errors[] = "CSRF Verification Failed";
    } else {
        $fname = trim($_POST['patient_fname'] ?? '');
        $mname = trim($_POST['patient_mname'] ?? '');
        $lname = trim($_POST['patient_lname'] ?? '');
        $email = trim($_POST['patient_email'] ?? '');
        $gender = trim($_POST['patient_gender'] ?? '');
        $dob = trim($_POST['patient_dob'] ?? '');
        $phone = trim($_POST['patient_phone'] ?? '');
        $address = trim($_POST['patient_address'] ?? '');
        
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!preg_match("/^[a-zA-Z\s]{1,50}$/", $fname)) {
            $profile_errors[] = "First name must contain only letters and spaces (1-50 characters).";
        }
        if ($mname !== "" && !preg_match("/^[a-zA-Z\s]{0,50}$/", $mname)) {
            $profile_errors[] = "Middle name must contain only letters and spaces (max 50 characters).";
        }
        if (!preg_match("/^[a-zA-Z\s]{1,50}$/", $lname)) {
            $profile_errors[] = "Last name must contain only letters and spaces (1-50 characters).";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profile_errors[] = "Please provide a valid email address.";
        }
        if ($gender !== 'male' && $gender !== 'female') {
            $profile_errors[] = "Please select a valid gender option.";
        }
        if (!preg_match("/^[0-9\+\-\s]{10,15}$/", $phone)) {
            $profile_errors[] = "Phone number must be between 10 and 15 digits.";
        }
        $dob_timestamp = strtotime($dob);
        if (!$dob_timestamp || $dob_timestamp > time()) {
            $profile_errors[] = "Please select a valid date of birth in the past.";
        }

        // Email uniqueness check
        list($chk_stmt, $chk_res) = db_query_prepared($conn, "SELECT patient_id FROM hs_patient WHERE patient_email = ? AND patient_id != ?", [$email, $patient_id], 'si');
        if (mysqli_num_rows($chk_res) > 0) {
            $profile_errors[] = "Email address is already registered by another user.";
        }
        if ($chk_stmt) {
            mysqli_stmt_close($chk_stmt);
        }

        // Password updates check
        if (!empty($new_password)) {
            if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $new_password)) {
                $profile_errors[] = "New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&).";
            }
            if ($new_password !== $confirm_password) {
                $profile_errors[] = "Confirm password does not match the new password.";
            }
        }

        if (empty($profile_errors)) {
            if (!empty($new_password)) {
                $hashed_pw = password_hash($new_password, PASSWORD_BCRYPT);
                $up_stmt = mysqli_prepare($conn, "UPDATE hs_patient SET patient_fname = ?, patient_mname = ?, patient_lname = ?, patient_email = ?, patient_gender = ?, patient_dob = ?, patient_phone = ?, patient_address = ?, patient_password = ? WHERE patient_id = ?");
                mysqli_stmt_bind_param($up_stmt, "sssssssssi", $fname, $mname, $lname, $email, $gender, $dob, $phone, $address, $hashed_pw, $patient_id);
            } else {
                $up_stmt = mysqli_prepare($conn, "UPDATE hs_patient SET patient_fname = ?, patient_mname = ?, patient_lname = ?, patient_email = ?, patient_gender = ?, patient_dob = ?, patient_phone = ?, patient_address = ? WHERE patient_id = ?");
                mysqli_stmt_bind_param($up_stmt, "ssssssssi", $fname, $mname, $lname, $email, $gender, $dob, $phone, $address, $patient_id);
            }

            if (mysqli_stmt_execute($up_stmt)) {
                $profile_success = "Profile updated successfully!";
                $_SESSION['patient_fname'] = $fname;
                $_SESSION['patient_lname'] = $lname;
                $_SESSION['patient_email'] = $email;
            } else {
                $profile_errors[] = "Failed to update profile due to a database error.";
            }
            if ($up_stmt) {
                mysqli_stmt_close($up_stmt);
            }
        }
    }
}

// Load current patient details
$patient_fname = '';
$patient_mname = '';
$patient_lname = '';
$patient_email = '';
$patient_gender = '';
$patient_address = '';
$patient_phone = '';
$patient_dob = '';

list($p_stmt, $p_res) = db_query_prepared($conn, "SELECT * FROM hs_patient WHERE patient_id = ?", [$patient_id]);
if ($p_res && $p_row = mysqli_fetch_assoc($p_res)) {
    $patient_fname = $p_row['patient_fname'];
    $patient_mname = $p_row['patient_mname'];
    $patient_lname = $p_row['patient_lname'];
    $patient_email = $p_row['patient_email'];
    $patient_gender = $p_row['patient_gender'];
    $patient_address = $p_row['patient_address'];
    $patient_phone = $p_row['patient_phone'];
    $patient_dob = $p_row['patient_dob'];
}
if ($p_stmt) {
    mysqli_stmt_close($p_stmt);
}

// Handle Cancellation Request
if (isset($_POST['cancel_appointment'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_error'] = 'CSRF Verification Failed';
        header("Location: my_appointment.php");
        exit();
    }
    $appointment_id = intval($_POST['appointment_id']);
    // Verify appointment belongs to this patient
    $verify_sql = "SELECT appointment_status FROM hs_appointment WHERE appointment_id = ? AND patient_id = ?";
    $v_stmt = mysqli_prepare($conn, $verify_sql);
    if ($v_stmt) {
        mysqli_stmt_bind_param($v_stmt, "ii", $appointment_id, $patient_id);
        mysqli_stmt_execute($v_stmt);
        mysqli_stmt_store_result($v_stmt);
        if (mysqli_stmt_num_rows($v_stmt) > 0) {
            mysqli_stmt_close($v_stmt);
            
            // Cancel appointment
            $cancel_sql = "UPDATE hs_appointment SET appointment_status = 'Cancel' WHERE appointment_id = ?";
            $c_stmt = mysqli_prepare($conn, $cancel_sql);
            if ($c_stmt) {
                mysqli_stmt_bind_param($c_stmt, "i", $appointment_id);
                mysqli_stmt_execute($c_stmt);
                mysqli_stmt_close($c_stmt);
                
                // Write to timeline
                $timeline_sql = "INSERT INTO hs_appointment_timeline (appointment_id, status_action, action_by, comments) VALUES (?, 'Cancelled', 'patient', 'Appointment cancelled by patient')";
                $t_stmt = mysqli_prepare($conn, $timeline_sql);
                if ($t_stmt) {
                    mysqli_stmt_bind_param($t_stmt, "i", $appointment_id);
                    mysqli_stmt_execute($t_stmt);
                    mysqli_stmt_close($t_stmt);
                }
                
                // Send email
                include_once('email_helper.php');
                send_appointment_email($conn, $appointment_id, 'cancelled');
                
                $_SESSION['toast_success'] = 'Appointment Cancelled Successfully!';
                header("Location: my_appointment.php");
                exit();
            }
        } else {
            mysqli_stmt_close($v_stmt);
        }
    }
}

// Handle Rescheduling Request
if (isset($_POST['reschedule_appointment'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_error'] = 'CSRF Verification Failed';
        header("Location: my_appointment.php");
        exit();
    }
    $appointment_id = intval($_POST['appointment_id']);
    $new_date = $_POST['reschedule_date'];
    $new_time = $_POST['reschedule_time'];
    $comments = trim($_POST['reschedule_comments']);
    
    // Verify ownership and get doctor_id
    $verify_sql = "SELECT doctor_id, appointment_date, appointment_time FROM hs_appointment WHERE appointment_id = ? AND patient_id = ?";
    $v_stmt = mysqli_prepare($conn, $verify_sql);
    if ($v_stmt) {
        mysqli_stmt_bind_param($v_stmt, "ii", $appointment_id, $patient_id);
        mysqli_stmt_execute($v_stmt);
        $v_res = mysqli_stmt_get_result($v_stmt);
        if ($row = mysqli_fetch_assoc($v_res)) {
            $doctor_id = intval($row['doctor_id']);
            $old_date = $row['appointment_date'];
            $old_time = $row['appointment_time'];
            mysqli_stmt_close($v_stmt);
            
            // Double check slot availability
            $day_of_week = date('l', strtotime($new_date));
            $schedule_sql = "SELECT start_time, end_time, slot_duration, status FROM hs_doctor_schedule WHERE doctor_id = ? AND day_of_week = ? AND status = 1";
            $sched_stmt = mysqli_prepare($conn, $schedule_sql);
            $start_time = '09:00:00';
            $end_time = '17:00:00';
            $slot_duration = 30;
            $is_available_day = true;

            if ($sched_stmt) {
                mysqli_stmt_bind_param($sched_stmt, "is", $doctor_id, $day_of_week);
                mysqli_stmt_execute($sched_stmt);
                $res = mysqli_stmt_get_result($sched_stmt);
                if ($r = mysqli_fetch_assoc($res)) {
                    $start_time = $r['start_time'];
                    $end_time = $r['end_time'];
                    $slot_duration = intval($r['slot_duration']);
                } else {
                    if ($day_of_week === 'Saturday' || $day_of_week === 'Sunday') {
                        $is_available_day = false;
                    }
                }
                mysqli_stmt_close($sched_stmt);
            }
            
            $is_past = false;
            if ($new_date < date('Y-m-d')) {
                $is_past = true;
            } elseif ($new_date === date('Y-m-d')) {
                if (strtotime($new_date . ' ' . $new_time) <= time()) {
                    $is_past = true;
                }
            }

            $booked = false;
            $booking_sql = "SELECT 1 FROM hs_appointment WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND appointment_status NOT IN ('Reject', 'Cancel') AND appointment_id != ?";
            $booking_stmt = mysqli_prepare($conn, $booking_sql);
            if ($booking_stmt) {
                $norm_time = date('H:i:s', strtotime($new_time));
                mysqli_stmt_bind_param($booking_stmt, "issi", $doctor_id, $new_date, $norm_time, $appointment_id);
                mysqli_stmt_execute($booking_stmt);
                mysqli_stmt_store_result($booking_stmt);
                if (mysqli_stmt_num_rows($booking_stmt) > 0) {
                    $booked = true;
                }
                mysqli_stmt_close($booking_stmt);
            }

            if (!$is_available_day || $is_past || $booked) {
                $_SESSION['toast_error'] = 'Error: The selected time slot is no longer available. Please select another slot.';
                header("Location: my_appointment.php");
                exit();
            }
            
            // Reschedule appointment and set status back to Pending
            $resched_sql = "UPDATE hs_appointment SET appointment_date = ?, appointment_time = ?, appointment_status = 'Pending' WHERE appointment_id = ?";
            $r_stmt = mysqli_prepare($conn, $resched_sql);
            if ($r_stmt) {
                mysqli_stmt_bind_param($r_stmt, "ssi", $new_date, $new_time, $appointment_id);
                mysqli_stmt_execute($r_stmt);
                mysqli_stmt_close($r_stmt);
                
                // Write to timeline
                $old_time_formatted = date('h:i A', strtotime($old_time));
                $new_time_formatted = date('h:i A', strtotime($new_time));
                $timeline_comment = "Rescheduled by patient from $old_date $old_time_formatted to $new_date $new_time_formatted. Note: " . $comments;
                
                $t_sql = "INSERT INTO hs_appointment_timeline (appointment_id, status_action, action_by, comments) VALUES (?, 'Rescheduled', 'patient', ?)";
                $t_stmt = mysqli_prepare($conn, $t_sql);
                if ($t_stmt) {
                    mysqli_stmt_bind_param($t_stmt, "is", $appointment_id, $timeline_comment);
                    mysqli_stmt_execute($t_stmt);
                    mysqli_stmt_close($t_stmt);
                }
                
                // Send email
                include_once('email_helper.php');
                send_appointment_email($conn, $appointment_id, 'rescheduled', $timeline_comment);
                
                $_SESSION['toast_success'] = 'Appointment Rescheduled Successfully! Status is now Pending review.';
                header("Location: my_appointment.php");
                exit();
            }
        } else {
            mysqli_stmt_close($v_stmt);
        }
    }
}

// Fetch patient dashboard statistics
$total_bookings = 0;
$confirmed_bookings = 0;
$pending_bookings = 0;
$paid_amount = 0;

// 8. Fix dashboard cards also
list($stats_stmt, $stats_result) = db_query_prepared($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN appointment_status = 'Accept' OR appointment_status = 'Confirmed' OR appointment_status = 'Completed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN appointment_status = 'Pending' OR appointment_status = 'pending' OR appointment_status = '' OR appointment_status IS NULL THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN appointment_payment_status = 1 THEN 500 ELSE 0 END) as paid
    FROM hs_appointment WHERE patient_id = ?", [$patient_id], 'i');

if ($stats_result && $row_stats = mysqli_fetch_assoc($stats_result)) {
    $total_bookings = intval($row_stats['total']);
    $confirmed_bookings = intval($row_stats['confirmed']);
    $pending_bookings = intval($row_stats['pending']);
    $paid_amount = intval($row_stats['paid']);
}
if ($stats_stmt) {
    mysqli_stmt_close($stats_stmt);
}

// Fetch dynamic monthly chart data
$monthly_labels = [];
$monthly_counts = [];
list($chart_stmt, $chart_result) = db_query_prepared($conn, "SELECT 
    DATE_FORMAT(appointment_date, '%b %Y') as month_label,
    COUNT(*) as count
    FROM hs_appointment 
    WHERE patient_id = ? 
    GROUP BY DATE_FORMAT(appointment_date, '%b %Y'), DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY DATE_FORMAT(appointment_date, '%Y-%m') ASC
    LIMIT 6", [$patient_id], 'i');

if ($chart_result) {
    while ($c_row = mysqli_fetch_assoc($chart_result)) {
        $monthly_labels[] = $c_row['month_label'];
        $monthly_counts[] = intval($c_row['count']);
    }
}
if ($chart_stmt) {
    mysqli_stmt_close($chart_stmt);
}

// Chart data fallback
if (empty($monthly_labels)) {
    $monthly_labels = [date('M Y')];
    $monthly_counts = [$total_bookings];
}

// Early load of all appointments for upcoming/past list and stats widgets
$use_hs_tables = false;
$table_chk_app = mysqli_query($conn, "SHOW TABLES LIKE 'hs_appointment'");
if ($table_chk_app && mysqli_num_rows($table_chk_app) > 0) {
    $use_hs_tables = true;
}

if ($use_hs_tables) {
    $sql_app = "SELECT a.*, 
                   c.category_title, 
                   sc.sub_category_title, 
                   s.service_title, 
                   d.doctor_fname, d.doctor_lname, d.doctor_id,
                   p.patient_id, p.patient_fname, p.patient_lname
            FROM hs_appointment a
            LEFT JOIN hs_category c ON a.category_id = c.category_id
            LEFT JOIN hs_sub_category sc ON a.sub_category_id = sc.sub_category_id
            LEFT JOIN hs_service s ON a.service_id = s.service_id
            LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id
            LEFT JOIN hs_patient p ON a.patient_id = p.patient_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
} else {
    // Schema fallback
    $sql_app = "SELECT 
                    a.*,
                    d.name AS doctor_name,
                    dep.name AS department_name
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN departments dep ON d.department_id = dep.id
                WHERE a.patient_id = ?
                ORDER BY a.appointment_date DESC";
}

list($app_stmt, $appoinment_data_res) = db_query_prepared($conn, $sql_app, [$patient_id], 'i');

$upcoming_appointments = [];
$past_appointments = [];
$all_appointments = [];
$today_str = date('Y-m-d');

if ($appoinment_data_res) {
    while($row = mysqli_fetch_array($appoinment_data_res)) {
        // Normalize fields to ensure full compatibility with the UI in both database environments
        if (!isset($row['appointment_id']) && isset($row['id'])) {
            $row['appointment_id'] = $row['id'];
        }
        if (!isset($row['appointment_status']) && isset($row['status'])) {
            $row['appointment_status'] = $row['status'];
        }
        if (!isset($row['doctor_fname']) && isset($row['doctor_name'])) {
            $row['doctor_fname'] = $row['doctor_name'];
            $row['doctor_lname'] = '';
        }
        if (!isset($row['category_title']) && isset($row['department_name'])) {
            $row['category_title'] = $row['department_name'];
        }
        if (!isset($row['service_title'])) {
            $row['service_title'] = $row['category_title'] ?? '';
        }

        $all_appointments[] = $row;
        $status = $row['appointment_status'] ?? 'pending';
        $app_date = $row['appointment_date'];
        if ($app_date >= $today_str && strcasecmp($status, 'Cancel') !== 0 && strcasecmp($status, 'Reject') !== 0) {
            $upcoming_appointments[] = $row;
        } else {
            $past_appointments[] = $row;
        }
    }
}
if ($app_stmt) mysqli_stmt_close($app_stmt);

// Completed count
$completed_bookings = 0;
foreach ($all_appointments as $appt) {
    if ($appt['appointment_status'] === 'Accept') {
        $completed_bookings++;
    }
}

// 7. Define $appointments for debugging
$appointments = $all_appointments;

// Next visit appointment
$next_visit = null;
if (!empty($upcoming_appointments)) {
    $next_visit = end($upcoming_appointments);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
include("includes/main_header.php");
?>
<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<body>

<div class="page-wrapper">
  <!-- Top header components -->
  <?php include("includes/top_header.php"); ?>
  <?php include("includes/upper_header.php"); ?>
  <?php include("includes/navbar.php"); ?>

  <!-- Dashboard Hero Section -->
  <section class="py-4 bg-primary-light text-primary position-relative overflow-hidden mb-4">
    <div class="container py-3 position-relative z-1">
      <div class="row align-items-center">
        <div class="col-md-8">
          <span class="badge bg-primary text-white px-3 py-2 rounded-pill mb-2 fw-semibold">Patient Portal</span>
          <h1 class="display-6 fw-bold text-dark mb-1">Welcome, <?= htmlspecialchars($patient_fname) ?> 👋</h1>
          <?php
          $hour = intval(date('H'));
          if ($hour < 12) {
              $greeting = "Good Morning!";
          } elseif ($hour < 17) {
              $greeting = "Good Afternoon!";
          } else {
              $greeting = "Good Evening!";
          }
          ?>
          <p class="text-primary-emphasis fw-bold mb-0"><i class="bi bi-sun-fill me-1"></i><?= $greeting ?></p>
        </div>
        <div class="col-md-4 mt-3 mt-md-0 text-md-end">
          <a href="add_appointment.php" class="btn btn-premium"><i class="bi bi-calendar-plus-fill me-2"></i>Book Appointment</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Dashboard Workspace -->
  <section class="pb-5">
    <div class="container">
      <div class="row g-4">
        <!-- Sidebar Navigation Widget -->
        <div class="col-lg-3">
          <div class="glass-card p-4 mb-4">
            <div class="text-center mb-4">
              <div class="bg-primary-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                <i class="bi bi-person-fill fs-2"></i>
              </div>
              <h5 class="mb-1 fw-bold"><?= (isset($_SESSION['patient_fname']) && isset($_SESSION['patient_lname'])) ? htmlspecialchars($_SESSION['patient_fname'] . " " . $_SESSION['patient_lname']) : 'Patient Profile' ?></h5>
              <span class="text-muted small d-block mb-3"><?= isset($_SESSION['patient_email']) ? htmlspecialchars($_SESSION['patient_email']) : '' ?></span>
              
              <!-- Profile Completion Widget -->
              <div class="p-3 border rounded bg-light-subtle text-start">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="fw-bold text-dark small">Profile Strength</span>
                  <span class="badge bg-success-light text-success fw-bold">85%</span>
                </div>
                <div class="progress mb-2" style="height: 6px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                 <a href="#" class="text-primary small fw-semibold text-decoration-none d-flex align-items-center gap-1 btn-trigger-profile-tab">
                  Complete Your Profile <i class="bi bi-arrow-right fs-7"></i>
                </a>
              </div>
            </div>
            
            <hr class="text-muted opacity-25">
            
            <div class="list-group list-group-flush gap-2" id="portal-nav-group">
              <a href="#" class="list-group-item list-group-item-action border-0 rounded active d-flex align-items-center py-2 px-3" data-tab="dashboard">
                <i class="bi bi-grid-fill me-3"></i> Dashboard
              </a>
              <a href="display_doctor.php" class="list-group-item list-group-item-action border-0 rounded d-flex align-items-center py-2 px-3">
                <i class="bi bi-people-fill me-3"></i> Doctors
              </a>
              <a href="#" class="list-group-item list-group-item-action border-0 rounded d-flex align-items-center py-2 px-3" data-tab="dashboard" data-scroll="appointmentRecords">
                <i class="bi bi-calendar-check-fill me-3"></i> Appointments
              </a>
              <a href="#" class="list-group-item list-group-item-action border-0 rounded d-flex align-items-center py-2 px-3" data-tab="dashboard" data-scroll="medical-reports">
                <i class="bi bi-file-medical-fill me-3"></i> Reports
              </a>
              <a href="#" class="list-group-item list-group-item-action border-0 rounded d-flex align-items-center py-2 px-3" data-tab="dashboard" data-scroll="notifications-card">
                <i class="bi bi-bell-fill me-3"></i> Notifications 🔔
              </a>
              <a href="#" class="list-group-item list-group-item-action border-0 rounded d-flex align-items-center py-2 px-3" data-tab="profile">
                <i class="bi bi-person-fill me-3"></i> Profile Settings
              </a>
              <a href="logout.php" class="list-group-item list-group-item-action border-0 rounded d-flex align-items-center py-2 px-3 text-danger">
                <i class="bi bi-box-arrow-right me-3"></i> Logout
              </a>
            </div>
          </div>

          <!-- Calendar Widget -->
          <div class="glass-card p-4 mb-4">
            <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-calendar3 text-primary me-2"></i>My Schedule</h5>
            <div class="calendar-header d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold small text-dark"><?= date('F Y') ?></span>
            </div>
            <div class="calendar-grid d-grid gap-1" style="grid-template-columns: repeat(7, 1fr);">
              <div class="text-center small text-muted fw-semibold">Su</div>
              <div class="text-center small text-muted fw-semibold">Mo</div>
              <div class="text-center small text-muted fw-semibold">Tu</div>
              <div class="text-center small text-muted fw-semibold">We</div>
              <div class="text-center small text-muted fw-semibold">Th</div>
              <div class="text-center small text-muted fw-semibold">Fr</div>
              <div class="text-center small text-muted fw-semibold">Sa</div>
              <?php
              $start_day = date('w', strtotime(date('Y-m-01')));
              $days_in_month = date('t');
              
              // Pre-fetch all appointment dates for this month to optimize performance (avoid N+1 queries)
              $start_date_bound = date('Y-m-01');
              $end_date_bound = date('Y-m-t');
              $active_appt_dates = [];
              list($appt_stmt, $appt_check) = db_query_prepared($conn, "SELECT DISTINCT appointment_date FROM hs_appointment WHERE patient_id = ? AND appointment_date BETWEEN ? AND ?", [$patient_id, $start_date_bound, $end_date_bound], 'iss');
              if ($appt_check) {
                  while ($r = mysqli_fetch_assoc($appt_check)) {
                      $active_appt_dates[] = $r['appointment_date'];
                  }
              }
              if ($appt_stmt) {
                  mysqli_stmt_close($appt_stmt);
              }
              
              for ($i = 0; $i < $start_day; $i++) {
                  echo '<div></div>';
              }
              
              for ($day = 1; $day <= $days_in_month; $day++) {
                  $current_date = date('Y-m-') . sprintf('%02d', $day);
                  $has_appt = in_array($current_date, $active_appt_dates);
                  
                  $active_class = $has_appt ? 'bg-primary text-white rounded-circle fw-bold' : '';
                  $today_class = ($day == date('d')) ? 'border border-primary rounded-circle' : '';
                  
                  echo "<div class='text-center small py-1 $active_class $today_class' style='width: 28px; height: 28px; line-height: 20px; margin: auto;'>$day</div>";
              }
              ?>
            </div>
          </div>
        </div>

        <!-- Main Dashboard Workspace -->
        <div class="col-lg-9">
          <!-- Dashboard Tab Pane -->
          <div id="tab-pane-dashboard" class="portal-tab-pane">
            <div class="row g-4">
            <!-- Left Column: Primary clinical content (Welcome banner, Next Visit, Quick Stats, Trend Chart, Tables) -->
            <div class="col-xl-8 col-lg-12">
              
              <!-- Upcoming Appointment Card -->
              <div class="glass-card p-4 mb-4 border-start border-primary border-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
                <div class="position-absolute end-0 bottom-0 opacity-10 translate-middle-y me-3 pointer-events-none">
                  <i class="bi bi-camera-video-fill text-primary" style="font-size: 100px;"></i>
                </div>
                <span class="badge bg-primary-light text-primary px-3 py-2 rounded-pill mb-3 fw-bold small text-uppercase tracking-wider">Upcoming Appointment</span>
                
                <?php if ($next_visit): 
                  $is_accepted = (strcasecmp($next_visit['appointment_status'] ?? '', 'Accept') === 0 || strcasecmp($next_visit['appointment_status'] ?? '', 'Confirmed') === 0);
                  $appt_status_label = $is_accepted ? 'Confirmed' : 'Pending';
                  $appt_status_class = $is_accepted ? 'bg-success' : 'bg-warning text-dark';
                ?>
                  <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                      <div class="text-muted small">Doctor</div>
                      <div class="fw-bold text-dark fs-5">Dr. <?= htmlspecialchars($next_visit['doctor_fname'] . " " . $next_visit['doctor_lname']) ?></div>
                    </div>
                    <div class="col-sm-6">
                      <div class="text-muted small">Department</div>
                      <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($next_visit['category_title']) ?></div>
                    </div>
                    <div class="col-sm-6">
                      <div class="text-muted small">Time</div>
                      <div class="fw-bold text-dark"><i class="bi bi-calendar-event text-primary me-2"></i><?= date('D, M d, Y', strtotime($next_visit['appointment_date'])) ?> &bull; <?= date('h:i A', strtotime($next_visit['appointment_time'])) ?></div>
                    </div>
                    <div class="col-sm-6">
                      <div class="text-muted small">Room</div>
                      <div class="fw-bold text-dark"><i class="bi bi-laptop text-primary me-2"></i>Room <?= 100 + $next_visit['appointment_id'] ?> (Virtual)</div>
                    </div>
                    <div class="col-sm-6">
                      <div class="text-muted small">Status</div>
                      <span class="badge <?= $appt_status_class ?> px-2 py-1 small"><?= $appt_status_label ?></span>
                    </div>
                  </div>
                  
                  <button class="btn btn-premium btn-lg w-100 shadow-premium-glowing" onclick="alert('Connecting to Telehealth Meeting...')"><i class="bi bi-camera-video-fill me-2"></i>Join Meeting</button>
                <?php else: ?>
                  <div class="text-center py-4">
                    <div class="text-primary-light mb-3">
                      <i class="bi bi-calendar-x fs-1 text-muted opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No upcoming appointments found</h5>
                    <p class="text-secondary small mb-3">You don't have any sessions scheduled at the moment.</p>
                    <a href="add_appointment.php" class="btn btn-premium btn-sm"><i class="bi bi-calendar-plus-fill me-2"></i>Book Your First Visit</a>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Quick Stats Grid -->
              <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                  <div class="glass-card p-3 d-flex align-items-center gap-3">
                    <div class="bg-primary-light text-primary rounded p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                      <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <div>
                      <div class="small text-muted fw-semibold">Appointments</div>
                      <h4 class="mb-0 fw-extrabold text-dark"><?= $total_bookings ?></h4>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-6">
                  <div class="glass-card p-3 d-flex align-items-center gap-3">
                    <div class="bg-success-light text-success rounded p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0fdf4;">
                      <i class="bi bi-check-circle fs-4" style="color: #16a34a;"></i>
                    </div>
                    <div>
                      <div class="small text-muted fw-semibold">Completed</div>
                      <h4 class="mb-0 fw-extrabold text-dark"><?= $completed_bookings ?></h4>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-6">
                  <div class="glass-card p-3 d-flex align-items-center gap-3">
                    <div class="bg-warning-light text-warning rounded p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #fffbeb;">
                      <i class="bi bi-clock-history fs-4" style="color: #d97706;"></i>
                    </div>
                    <div>
                      <div class="small text-muted fw-semibold">Pending</div>
                      <h4 class="mb-0 fw-extrabold text-dark"><?= $pending_bookings ?></h4>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-6">
                  <div class="glass-card p-3 d-flex align-items-center gap-3">
                    <div class="bg-info-light text-info rounded p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #ecfeff;">
                      <i class="bi bi-file-medical fs-4" style="color: #0891b2;"></i>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Consultation Activity Trend Chart -->
              <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-activity text-primary me-2"></i>Appointment Timeline</h5>
                <div style="height: 250px;">
                  <canvas id="patientTrendChart"></canvas>
                </div>

                <!-- Appointment Flow Stepper -->
                <div class="mt-4 pt-3 border-top">
                  <div class="text-secondary small mb-3 fw-bold"><i class="bi bi-arrow-right-circle-fill me-1 text-primary"></i>Appointment Flow Lifecycle</div>
                  <div class="d-flex justify-content-between align-items-start position-relative px-2">
                    <!-- Progress Line -->
                    <div class="position-absolute bg-light" style="height: 4px; top: 16px; left: 10%; right: 10%; z-index: 1;"></div>
                    <div class="position-absolute bg-primary" style="height: 4px; top: 16px; left: 10%; width: 53%; z-index: 2; transition: width 0.5s ease;"></div>
                    
                    <!-- Step 1: Appointment Booked -->
                    <div class="text-center position-relative" style="z-index: 3; width: 22%;">
                      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 32px; height: 32px; box-shadow: 0 0 0 4px #eff6ff;">
                        <i class="bi bi-calendar-check fs-6"></i>
                      </div>
                      <div class="fs-7 fw-bold text-dark line-height-sm">Appointment Booked</div>
                    </div>
                    
                    <!-- Step 2: Confirmed -->
                    <div class="text-center position-relative" style="z-index: 3; width: 22%;">
                      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 32px; height: 32px; box-shadow: 0 0 0 4px #eff6ff;">
                        <i class="bi bi-check-lg fs-6"></i>
                      </div>
                      <div class="fs-7 fw-bold text-dark line-height-sm">Confirmed</div>
                    </div>

                    <!-- Step 3: Doctor Assigned -->
                    <div class="text-center position-relative" style="z-index: 3; width: 22%;">
                      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 32px; height: 32px; box-shadow: 0 0 0 4px #eff6ff;">
                        <i class="bi bi-person-badge fs-6"></i>
                      </div>
                      <div class="fs-7 fw-bold text-dark line-height-sm">Doctor Assigned</div>
                    </div>

                    <!-- Step 4: Completed -->
                    <div class="text-center position-relative" style="z-index: 3; width: 22%;">
                      <div class="bg-white text-secondary border border-2 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 32px; height: 32px;">
                        <i class="bi bi-flag fs-6"></i>
                      </div>
                      <div class="fs-7 fw-semibold text-muted line-height-sm">Completed</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Appointment Table Area with Tabbed View -->
              <div class="glass-card p-4" id="appointmentRecords">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                  <div>
                    <h4 class="fw-bold mb-0 text-dark">Upcoming Appointments</h4>
                    <p class="text-secondary small mb-0">Manage upcoming and view past doctor consultations</p>
                  </div>
                  <div style="max-width: 250px; width: 100%;">
                    <div class="input-group">
                      <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                      <input type="text" id="appointmentSearch" class="form-control form-control-premium border-start-0" placeholder="Search appointments...">
                    </div>
                  </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" id="appointmentTabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming-pane" type="button" role="tab" aria-controls="upcoming-pane" aria-selected="true">
                      <i class="bi bi-calendar-event me-2 text-primary"></i>Upcoming (<?= count($upcoming_appointments) ?>)
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold" id="past-tab" data-bs-toggle="tab" data-bs-target="#past-pane" type="button" role="tab" aria-controls="past-pane" aria-selected="false">
                      <i class="bi bi-clock-history me-2 text-secondary"></i>Past & Cancelled (<?= count($past_appointments) ?>)
                    </button>
                  </li>
                </ul>

                <div class="tab-content" id="appointmentTabsContent">
                  <!-- Upcoming Appointments Pane -->
                  <div class="tab-pane fade show active" id="upcoming-pane" role="tabpanel" aria-labelledby="upcoming-tab">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle table-premium table-premium-responsive mb-0">
                        <thead>
                          <tr>
                            <th scope="col" class="py-3 px-4">ID</th>
                            <th scope="col" class="py-3">Specialty</th>
                            <th scope="col" class="py-3">Doctor</th>
                            <th scope="col" class="py-3">Schedule</th>
                            <th scope="col" class="py-3">Status</th>
                            <th scope="col" class="py-3 text-center">Actions</th>
                          </tr>
                        </thead>
                        <tbody class="appointmentTableBody">
                          <?php if (!empty($upcoming_appointments)): ?>
                            <?php foreach ($upcoming_appointments as $appt): 
                              $doc_name = "Dr. " . htmlspecialchars($appt['doctor_fname'] . " " . $appt['doctor_lname']);
                              $specialty = htmlspecialchars($appt['category_title']);
                              $status = $appt['appointment_status'] ?: 'Pending';
                              if (strcasecmp($status, 'Accept') === 0 || strcasecmp($status, 'Confirmed') === 0) {
                                  $badge_class = "badge-status-accept";
                              } elseif (strcasecmp($status, 'Reject') === 0 || strcasecmp($status, 'Cancel') === 0) {
                                  $badge_class = "badge-status-reject";
                              } else {
                                  $badge_class = "badge-status-pending";
                              }
                            ?>
                            <tr>
                              <td class="px-4 fw-semibold text-secondary" data-label="ID">#<?= htmlspecialchars($appt['appointment_id']) ?></td>
                              <td data-label="Specialty">
                                <div class="fw-semibold text-dark"><?= $specialty ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($appt['service_title']) ?></div>
                              </td>
                              <td class="fw-semibold text-secondary" data-label="Doctor"><?= $doc_name ?></td>
                              <td data-label="Schedule">
                                <div class="fw-semibold text-dark"><i class="bi bi-calendar-event me-2 text-primary"></i><?= htmlspecialchars($appt['appointment_date']) ?></div>
                                <div class="text-muted small"><i class="bi bi-clock me-2 text-primary"></i><?= date('h:i A', strtotime($appt['appointment_time'])) ?></div>
                              </td>
                              <td data-label="Status">
                                <span class="badge badge-status <?= $badge_class ?>"><?= htmlspecialchars($status) ?></span>
                              </td>
                              <td class="text-center" data-label="Actions">
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                  <button type="button" class="btn btn-outline-info btn-sm px-2 py-1 btn-timeline-appt" data-id="<?= $appt['appointment_id'] ?>" title="View Timeline">
                                    <i class="bi bi-clock-history"></i> Timeline
                                  </button>
                                  
                                  <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 btn-reschedule-appt" 
                                          data-id="<?= $appt['appointment_id'] ?>" 
                                          data-doctor="<?= $appt['doctor_id'] ?>" 
                                          data-date="<?= $appt['appointment_date'] ?>"
                                          title="Reschedule Visit">
                                    <i class="bi bi-calendar-plus"></i> Reschedule
                                  </button>

                                  <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 btn-cancel-appt" data-id="<?= $appt['appointment_id'] ?>" title="Cancel Appointment">
                                    <i class="bi bi-x-circle"></i> Cancel
                                  </button>

                                  <?php if ($status == 'Accept' && $appt['appointment_payment_status'] == 0): ?>
                                    <a href="payment.php?appointment_id=<?= $appt['appointment_id']; ?>" class="btn btn-premium btn-sm py-1 px-3">Pay</a>
                                  <?php elseif ($status == 'Accept' && $appt['appointment_payment_status'] == 1): ?>
                                    <div class="d-flex align-items-center gap-2">
                                      <span class="text-success fw-bold small"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>
                                      <a href="invoice.php?appointment_id=<?= $appt['appointment_id']; ?>" target="_blank" class="btn btn-outline-success btn-sm py-0 px-2" style="font-size: 11px;" title="Download Invoice Receipt"><i class="bi bi-download"></i> Receipt</a>
                                    </div>
                                  <?php endif; ?>
                                </div>
                              </td>
                            </tr>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <tr class="empty-state-placeholder">
                              <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 mb-2 d-block text-light-emphasis"></i>
                                <span>No upcoming appointments found</span>
                              </td>
                            </tr>
                          <?php endif; ?>
                          <tr class="empty-search-row d-none">
                            <td colspan="6" class="text-center py-5 text-muted">
                              <i class="bi bi-search fs-2 mb-2 d-block text-secondary"></i>
                              <span class="fw-semibold">No matching upcoming appointments found.</span>
                              <p class="small text-secondary mb-0">Refine your search keyword (e.g. check doctor name or status).</p>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Past & Cancelled Appointments Pane -->
                  <div class="tab-pane fade" id="past-pane" role="tabpanel" aria-labelledby="past-tab">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle table-premium table-premium-responsive mb-0">
                        <thead>
                          <tr>
                            <th scope="col" class="py-3 px-4">ID</th>
                            <th scope="col" class="py-3">Specialty</th>
                            <th scope="col" class="py-3">Doctor</th>
                            <th scope="col" class="py-3">Schedule</th>
                            <th scope="col" class="py-3">Status</th>
                            <th scope="col" class="py-3 text-center">Actions</th>
                          </tr>
                        </thead>
                        <tbody class="appointmentTableBody">
                          <?php if (!empty($past_appointments)): ?>
                            <?php foreach ($past_appointments as $appt): 
                              $doc_name = "Dr. " . htmlspecialchars($appt['doctor_fname'] . " " . $appt['doctor_lname']);
                              $specialty = htmlspecialchars($appt['category_title']);
                              $status = $appt['appointment_status'] ?: 'Pending';
                              if (strcasecmp($status, 'Accept') === 0 || strcasecmp($status, 'Confirmed') === 0) {
                                  $badge_class = "badge-status-accept";
                              } elseif (strcasecmp($status, 'Reject') === 0 || strcasecmp($status, 'Cancel') === 0) {
                                  $badge_class = "badge-status-reject";
                              } else {
                                  $badge_class = "badge-status-pending";
                              }
                            ?>
                            <tr>
                              <td class="px-4 fw-semibold text-secondary" data-label="ID">#<?= htmlspecialchars($appt['appointment_id']) ?></td>
                              <td data-label="Specialty">
                                <div class="fw-semibold text-dark"><?= $specialty ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($appt['service_title']) ?></div>
                              </td>
                              <td class="fw-semibold text-secondary" data-label="Doctor"><?= $doc_name ?></td>
                              <td data-label="Schedule">
                                <div class="fw-semibold text-dark"><i class="bi bi-calendar-event me-2 text-primary"></i><?= htmlspecialchars($appt['appointment_date']) ?></div>
                                <div class="text-muted small"><i class="bi bi-clock me-2 text-primary"></i><?= date('h:i A', strtotime($appt['appointment_time'])) ?></div>
                              </td>
                              <td data-label="Status">
                                <span class="badge badge-status <?= $badge_class ?>"><?= htmlspecialchars($status) ?></span>
                              </td>
                              <td class="text-center" data-label="Actions">
                                <div class="d-flex justify-content-center gap-2">
                                  <button type="button" class="btn btn-outline-info btn-sm px-3 py-1 btn-timeline-appt" data-id="<?= $appt['appointment_id'] ?>">
                                    <i class="bi bi-clock-history me-1"></i> Timeline History
                                  </button>
                                </div>
                              </td>
                            </tr>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <tr class="empty-state-placeholder">
                              <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 mb-2 d-block text-light-emphasis"></i>
                                <span>No past or cancelled appointments found.</span>
                              </td>
                            </tr>
                          <?php endif; ?>
                          <tr class="empty-search-row d-none">
                            <td colspan="6" class="text-center py-5 text-muted">
                              <i class="bi bi-search fs-2 mb-2 d-block text-secondary"></i>
                              <span class="fw-semibold">No matching past appointments found.</span>
                              <p class="small text-secondary mb-0">Refine your search keyword (e.g. check doctor name or status).</p>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Secondary clinical/activity widgets -->
            <div class="col-xl-4 col-lg-12">
              
              <!-- Notifications Widget -->
              <div class="glass-card p-4 mb-4" id="notifications-card">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bell-fill text-primary me-2"></i>Notifications</h5>
                <div class="d-flex flex-column gap-3">
                  <?php
                  $notifications = [];

                  // 1. Check for next upcoming visit
                  if ($next_visit) {
                      $doc_full = "Dr. " . htmlspecialchars($next_visit['doctor_fname'] . " " . $next_visit['doctor_lname']);
                      $is_accepted = (strcasecmp($next_visit['appointment_status'] ?? '', 'Accept') === 0 || strcasecmp($next_visit['appointment_status'] ?? '', 'Confirmed') === 0);
                      
                      if ($is_accepted) {
                          $notifications[] = [
                              'title' => 'Appointment Confirmed',
                              'text' => "Your consultation with {$doc_full} is confirmed.",
                              'type' => 'success',
                              'icon' => 'bi-calendar-check-fill'
                          ];
                      } else {
                          $notifications[] = [
                              'title' => 'Appointment Pending',
                              'text' => "Your consultation request with {$doc_full} is pending review.",
                              'type' => 'primary',
                              'icon' => 'bi-calendar-fill'
                          ];
                      }
                      
                      // Check if tomorrow
                      if ($next_visit['appointment_date'] === date('Y-m-d', strtotime('+1 day'))) {
                          $notifications[] = [
                              'title' => 'Reminder Tomorrow',
                              'text' => "You have a visit with {$doc_full} tomorrow at " . date('h:i A', strtotime($next_visit['appointment_time'])) . ".",
                              'type' => 'warning',
                              'icon' => 'bi-exclamation-triangle-fill'
                          ];
                      }
                  }

                  // 2. Check for completed consultations for prescriptions/reports
                  $completed_appts = [];
                  foreach ($all_appointments as $appt) {
                      if (strcasecmp($appt['appointment_status'] ?? '', 'Accept') === 0) {
                          $completed_appts[] = $appt;
                      }
                  }

                  if (!empty($completed_appts)) {
                      $latest_comp = $completed_appts[0];
                      $doc_full = "Dr. " . htmlspecialchars($latest_comp['doctor_fname'] . " " . $latest_comp['doctor_lname']);
                      $notifications[] = [
                          'title' => 'Prescription Uploaded',
                          'text' => "New treatment prescription has been uploaded by {$doc_full}.",
                          'type' => 'primary',
                          'icon' => 'bi-file-earmark-medical-fill'
                      ];
                  }

                  // 3. Check for successful payments
                  if ($paid_amount > 0) {
                      $notifications[] = [
                          'title' => 'Payment Successful',
                          'text' => "Invoice of ₹{$paid_amount} was paid successfully.",
                          'type' => 'success',
                          'icon' => 'bi-credit-card-2-front-fill'
                      ];
                  }

                  if (!empty($notifications)):
                      foreach ($notifications as $notif): 
                          $bg_class = $notif['type'] === 'success' ? 'bg-success-light' : ($notif['type'] === 'warning' ? 'bg-warning-light' : 'bg-primary-light');
                          $border_class = $notif['type'] === 'success' ? 'border-success' : ($notif['type'] === 'warning' ? 'border-warning' : 'border-primary');
                          $text_class = $notif['type'] === 'success' ? 'text-success' : ($notif['type'] === 'warning' ? 'text-warning' : 'text-primary');
                          $bg_color = $notif['type'] === 'success' ? '#f0fdf4' : ($notif['type'] === 'warning' ? '#fffbeb' : '#f0f6ff');
                  ?>
                          <div class="p-3 rounded <?= $bg_class ?> border-start <?= $border_class ?> border-3 d-flex align-items-start gap-3" style="background-color: <?= $bg_color ?>;">
                            <i class="bi <?= $notif['icon'] ?> <?= $text_class ?> fs-5"></i>
                            <div>
                              <div class="fw-bold text-dark small"><?= $notif['title'] ?></div>
                              <div class="text-secondary small"><?= $notif['text'] ?></div>
                            </div>
                          </div>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <div class="text-center py-4 text-muted">
                        <i class="bi bi-bell-slash fs-2 mb-2 d-block text-secondary opacity-50"></i>
                        <span class="small">No new notifications.</span>
                      </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- My Doctors Widget -->
              <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-people-fill text-primary me-2"></i>My Doctors</h5>
                <div class="d-flex flex-column gap-3">
                  <?php 
                  $my_doctors = [];
                  $unique_doc_ids = [];
                  foreach ($all_appointments as $row) {
                      if (!in_array($row['doctor_id'], $unique_doc_ids)) {
                          $unique_doc_ids[] = $row['doctor_id'];
                          $my_doctors[] = [
                              'name' => 'Dr. ' . $row['doctor_fname'] . ' ' . $row['doctor_lname'],
                              'specialty' => $row['category_title']
                          ];
                      }
                  }
                  if (!empty($my_doctors)): 
                  ?>
                    <?php foreach (array_slice($my_doctors, 0, 3) as $doc): ?>
                      <div class="d-flex align-items-center justify-content-between p-2 rounded border bg-light-subtle">
                        <div class="d-flex align-items-center gap-2">
                          <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-person-fill"></i>
                          </div>
                          <div>
                            <div class="fw-bold text-dark small"><?= htmlspecialchars($doc['name']) ?></div>
                            <div class="text-muted small fs-7"><?= htmlspecialchars($doc['specialty']) ?></div>
                          </div>
                        </div>
                        <div class="text-warning small">
                          <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-center py-3 text-muted">
                      <i class="bi bi-people fs-3 mb-2 d-block text-secondary opacity-50"></i>
                      <span class="small">No practitioners assigned yet.</span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Reports Widget -->
              <div class="glass-card p-4 mb-4" id="medical-reports">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-medical-fill text-primary me-2"></i>Reports</h5>
                <div class="d-flex flex-column gap-2">
                  <?php if (!empty($completed_appts)): ?>
                    <?php foreach (array_slice($completed_appts, 0, 2) as $appt): ?>
                      <div class="p-2 border rounded bg-white d-flex align-items-center justify-content-between shadow-sm">
                        <div>
                          <div class="fw-bold text-dark small">Consultation Summary</div>
                          <div class="text-muted fs-7">Dr. <?= htmlspecialchars($appt['doctor_fname'] . " " . $appt['doctor_lname']) ?> &bull; Completed</div>
                        </div>
                        <a href="#" onclick="alert('Downloading Consultation Report PDF...')" class="btn btn-sm btn-outline-primary px-2 py-1"><i class="bi bi-download"></i> Download PDF</a>
                      </div>
                      <div class="p-2 border rounded bg-white d-flex align-items-center justify-content-between shadow-sm">
                        <div>
                          <div class="fw-bold text-dark small">Prescription</div>
                          <div class="text-muted fs-7">Dr. <?= htmlspecialchars($appt['doctor_fname'] . " " . $appt['doctor_lname']) ?> &bull; Active</div>
                        </div>
                        <a href="#" onclick="alert('Downloading Prescription PDF...')" class="btn btn-sm btn-outline-primary px-2 py-1"><i class="bi bi-download"></i> Download PDF</a>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-center py-3 text-muted">
                      <i class="bi bi-file-earmark-medical fs-3 mb-2 d-block text-secondary opacity-50"></i>
                      <span class="small">No reports uploaded yet.</span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Medicine Schedule -->
              <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-capsule text-primary me-2"></i>Medicine Schedule</h5>
                <div class="d-flex flex-column gap-2">
                  <?php 
                  $prescriptions = [];
                  foreach ($past_appointments as $appt) {
                      if (strcasecmp($appt['appointment_status'] ?? '', 'Accept') === 0) {
                          $prescriptions[] = [
                              'id' => $appt['appointment_id'],
                              'doctor' => 'Dr. ' . $appt['doctor_fname'] . ' ' . $appt['doctor_lname'],
                              'date' => date('M d, Y', strtotime($appt['appointment_date']))
                          ];
                      }
                  }
                  if (!empty($prescriptions)): 
                  ?>
                    <?php foreach (array_slice($prescriptions, 0, 2) as $presc): ?>
                      <div class="p-2 border rounded bg-white d-flex align-items-center justify-content-between shadow-sm">
                        <div>
                          <div class="fw-bold text-dark small">Prescription #<?= $presc['id'] ?> (Active)</div>
                          <div class="text-muted fs-7"><?= htmlspecialchars($presc['doctor']) ?> &bull; Daily Schedule</div>
                        </div>
                        <a href="#" onclick="alert('Downloading prescription PDF...')" class="btn btn-sm btn-outline-primary px-2 py-1"><i class="bi bi-download"></i> Download PDF</a>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-center py-3 text-muted">
                      <i class="bi bi-capsule-health fs-3 mb-2 d-block text-secondary opacity-50"></i>
                      <span class="small">No active prescriptions found.</span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Recent Payments -->
              <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-credit-card-fill text-primary me-2"></i>Recent Payments</h5>
                <div class="d-flex flex-column gap-2">
                  <?php 
                  $payments = [];
                  foreach ($all_appointments as $row) {
                      if ($row['appointment_payment_status'] == 1) {
                          $payments[] = [
                              'id' => $row['appointment_id'],
                              'date' => date('M d, Y', strtotime($row['appointment_date'])),
                              'amount' => 500
                          ];
                      }
                  }
                  if (!empty($payments)): 
                  ?>
                    <?php foreach (array_slice($payments, 0, 2) as $pay): ?>
                      <div class="p-2 border rounded bg-white d-flex align-items-center justify-content-between shadow-sm">
                        <div>
                          <div class="fw-bold text-dark small">Invoice #INV-10<?= $pay['id'] ?></div>
                          <div class="text-muted fs-7">Paid on <?= $pay['date'] ?> &bull; ₹<?= $pay['amount'] ?></div>
                        </div>
                        <a href="invoice.php?appointment_id=<?= $pay['id'] ?>" target="_blank" class="btn btn-sm btn-outline-success px-2 py-1"><i class="bi bi-download"></i> Download</a>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                      <div class="p-3 border rounded bg-white text-center shadow-sm">
                        <i class="bi bi-credit-card text-muted d-block fs-3 mb-1"></i>
                        <span class="text-secondary small">No paid consultation invoices found.</span>
                      </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Health Progress -->
           
          </div> <!-- Close Dashboard Row -->
          </div> <!-- Close tab-pane-dashboard -->

          <!-- Profile Settings Tab Pane -->
          <div id="tab-pane-profile" class="portal-tab-pane d-none">
            <div class="glass-card p-5">
              <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-person-fill text-primary me-2"></i>Profile Settings</h3>
              
              <?php if (!empty($profile_errors)): ?>
                <div class="alert alert-danger border-0 rounded-3 mb-4 shadow-sm" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                  <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following profile validation errors:</div>
                  <ul class="mb-0 ps-3">
                    <?php foreach ($profile_errors as $error): ?>
                      <li class="small"><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <?php if (!empty($profile_success)): ?>
                <div class="alert alert-success border-0 rounded-3 mb-4 shadow-sm py-2 px-3 small text-center" role="alert" style="background-color: #f0fdf4; color: #15803d;">
                  <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($profile_success) ?>
                </div>
              <?php endif; ?>

              <form action="" method="post" id="patientProfileForm" class="row g-4" novalidate>
                <!-- CSRF Token -->
                <?php echo get_csrf_input_html(); ?>

                <!-- Name fields -->
                <div class="col-md-4">
                  <label class="form-label fw-semibold text-secondary">First Name</label>
                  <input type="text" id="prof_patient_fname" name="patient_fname" class="form-control form-control-premium" placeholder="First Name" value="<?= htmlspecialchars($patient_fname) ?>" required>
                  <div class="invalid-feedback">First name is required (letters and spaces only).</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold text-secondary">Middle Name</label>
                  <input type="text" id="prof_patient_mname" name="patient_mname" class="form-control form-control-premium" placeholder="Middle Name" value="<?= htmlspecialchars($patient_mname) ?>">
                  <div class="invalid-feedback">Middle name can only contain letters and spaces.</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold text-secondary">Last Name</label>
                  <input type="text" id="prof_patient_lname" name="patient_lname" class="form-control form-control-premium" placeholder="Last Name" value="<?= htmlspecialchars($patient_lname) ?>" required>
                  <div class="invalid-feedback">Last name is required (letters and spaces only).</div>
                </div>

                <!-- Email and Phone -->
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary">Email Address</label>
                  <input type="email" id="prof_patient_email" name="patient_email" class="form-control form-control-premium" placeholder="name@example.com" value="<?= htmlspecialchars($patient_email) ?>" required>
                  <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary">Phone Number</label>
                  <input type="tel" id="prof_patient_phone" name="patient_phone" class="form-control form-control-premium" placeholder="e.g. +91 98765 43210" value="<?= htmlspecialchars($patient_phone) ?>" required>
                  <div class="invalid-feedback">Phone number must be between 10 and 15 digits (may include +, -, and spaces).</div>
                </div>

                <!-- Gender and DOB -->
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary d-block">Gender</label>
                  <div class="d-flex gap-4 mt-2">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="patient_gender" id="prof_genderMale" value="male" <?= ($patient_gender === 'male') ? 'checked' : '' ?> required>
                      <label class="form-check-label text-dark" for="prof_genderMale">Male</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="patient_gender" id="prof_genderFemale" value="female" <?= ($patient_gender === 'female') ? 'checked' : '' ?> required>
                      <label class="form-check-label text-dark" for="prof_genderFemale">Female</label>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary">Date of Birth</label>
                  <input type="date" id="prof_patient_dob" name="patient_dob" class="form-control form-control-premium" value="<?= htmlspecialchars($patient_dob) ?>" max="<?= date('Y-m-d') ?>" required>
                  <div class="invalid-feedback">Please select a valid date of birth in the past.</div>
                </div>

                <!-- Address -->
                <div class="col-md-12">
                  <label class="form-label fw-semibold text-secondary">Residential Address</label>
                  <textarea id="prof_patient_address" name="patient_address" class="form-control form-control-premium" rows="3" placeholder="Enter Full Street Address" required><?= htmlspecialchars($patient_address) ?></textarea>
                  <div class="invalid-feedback">Please enter your residential address (10-200 characters).</div>
                </div>

                <!-- Password modification separator -->
                <div class="col-md-12 mt-4 pt-3 border-top">
                  <h5 class="fw-bold text-dark mb-2"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Change Password (Optional)</h5>
                  <p class="text-secondary small">Leave these fields blank if you do not want to change your password.</p>
                </div>

                <!-- Password modification fields -->
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary">New Password</label>
                  <input type="password" id="prof_new_password" name="new_password" class="form-control form-control-premium" placeholder="New Secure Password">
                  <div class="invalid-feedback">Password must be at least 8 characters with 1 uppercase, 1 lowercase, 1 number, and 1 special character (@$!%*?&).</div>
                  
                  <div class="password-strength-wrapper mt-2 d-none" id="prof-pw-strength-wrapper">
                    <div class="progress" style="height: 6px;">
                      <div id="prof-pw-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div id="prof-pw-strength-text" class="small mt-1 fw-semibold text-muted">Password strength</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary">Confirm New Password</label>
                  <input type="password" id="prof_confirm_password" name="confirm_password" class="form-control form-control-premium" placeholder="Confirm New Password">
                  <div class="invalid-feedback">Confirm password must match your new password.</div>
                </div>

                <!-- Submit Button -->
                <div class="col-md-12 text-end mt-4">
                  <button type="submit" name="update_profile" class="btn btn-premium btn-lg w-100">Save Changes</button>
                </div>
              </form>
            </div>
          </div>
        </div>

          <!-- Modals Container -->

          <!-- 1. Cancellation Modal -->
          <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <form method="POST" action="">
                  <?php echo get_csrf_input_html(); ?>
                  <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger" id="cancelModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Cancel Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body py-3">
                    <input type="hidden" name="appointment_id" id="cancel_appointment_id">
                    <p class="text-secondary mb-0">Are you sure you want to cancel this appointment? This action cannot be undone, and the doctor will be notified of the cancellation.</p>
                  </div>
                  <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" name="cancel_appointment" class="btn btn-danger rounded">Confirm Cancellation</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- 2. Reschedule Modal -->
          <div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <form method="POST" action="" id="rescheduleForm">
                  <?php echo get_csrf_input_html(); ?>
                  <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-primary" id="rescheduleModalLabel"><i class="bi bi-calendar-plus-fill me-2"></i>Reschedule Consultation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body py-4">
                    <input type="hidden" name="appointment_id" id="reschedule_appointment_id">
                    <input type="hidden" name="reschedule_time" id="reschedule_time_input" required>
                    
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Choose New Date</label>
                        <input type="date" name="reschedule_date" id="reschedule_date" class="form-control form-control-premium" min="<?= date('Y-m-d') ?>" required>
                      </div>
                      
                      <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Comments / Reason for Reschedule</label>
                        <textarea name="reschedule_comments" class="form-control form-control-premium" rows="1" placeholder="Brief reason..."></textarea>
                      </div>

                      <div class="col-md-12" id="reschedule_slots_wrapper" style="display:none;">
                        <label class="form-label small fw-semibold text-secondary">Select Available Slot</label>
                        <div class="d-flex flex-wrap gap-2" id="reschedule_slots_grid"></div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reschedule_appointment" class="btn btn-primary rounded">Request Reschedule</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- 3. Timeline Modal -->
          <div class="modal fade" id="timelineModal" tabindex="-1" aria-labelledby="timelineModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md">
              <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header border-0 pb-0">
                  <h5 class="modal-title fw-bold text-dark" id="timelineModalLabel"><i class="bi bi-clock-history me-2 text-primary"></i>Appointment Timeline History</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4" style="max-height: 450px; overflow-y: auto;">
                  <ul class="timeline" id="timeline_history_list">
                    <!-- Loaded dynamically via AJAX -->
                  </ul>
                </div>
                <div class="modal-footer border-0 pt-0">
                  <button type="button" class="btn btn-secondary btn-sm rounded" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>

          <style>
          /* Timeline Styles */
          .timeline {
              position: relative;
              padding-left: 20px;
              list-style: none;
              margin-top: 10px;
          }
          .timeline::before {
              content: "";
              position: absolute;
              top: 0;
              bottom: 0;
              left: 5px;
              width: 2px;
              background: #e2e8f0;
          }
          .timeline-item {
              position: relative;
              margin-bottom: 20px;
          }
          .timeline-marker {
              position: absolute;
              top: 5px;
              left: -20px;
              width: 12px;
              height: 12px;
              border-radius: 50%;
              background: #2563eb;
              border: 2px solid #fff;
              box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
          }
          .timeline-item.cancelled .timeline-marker,
          .timeline-item.rejected .timeline-marker,
          .timeline-item.cancel .timeline-marker,
          .timeline-item.reject .timeline-marker {
              background: #ef4444;
              box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
          }
          .timeline-item.confirmed .timeline-marker,
          .timeline-item.accept .timeline-marker,
          .timeline-item.booked .timeline-marker {
              background: #22c55e;
              box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
          }
          .timeline-content {
              background: #f8fafc;
              border: 1px solid #e2e8f0;
              border-radius: 8px;
              padding: 10px 14px;
              margin-left: 10px;
          }
          .timeline-title {
              font-size: 13px;
              font-weight: 700;
              margin-bottom: 2px;
              color: #0f172a;
          }
          .timeline-date {
              font-size: 11px;
              color: #64748b;
              margin-bottom: 4px;
          }
          .timeline-body {
              font-size: 12px;
              color: #475569;
              line-height: 1.4;
          }
          </style>

          <!-- End of Modals -->
        </div>
      </div>
    </div>
  </section>

  <!-- Reusable modular Footer components -->
  <?php include("includes/footer.php"); ?>
</div>

<!-- JS imports -->
<?php include("includes/js.php"); ?>

<!-- Client-side table search, Chart.js Config, and Modal Controllers -->
<script>
document.getElementById('appointmentSearch').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase().trim();
    
    document.querySelectorAll('.appointmentTableBody').forEach(tbody => {
        const rows = tbody.querySelectorAll('tr:not(.empty-search-row):not(.empty-state-placeholder)');
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.cells.length > 1) {
                const text = row.textContent.toLowerCase();
                const matches = text.includes(filter);
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            }
        });
        
        const emptySearchRow = tbody.querySelector('.empty-search-row');
        const defaultEmptyRow = tbody.querySelector('.empty-state-placeholder');
        
        if (emptySearchRow) {
            if (visibleCount === 0) {
                emptySearchRow.classList.remove('d-none');
                if (defaultEmptyRow) defaultEmptyRow.style.display = 'none';
            } else {
                emptySearchRow.classList.add('d-none');
                if (defaultEmptyRow && filter === '') defaultEmptyRow.style.display = '';
            }
        }
    });
});

// Chart.js Rendering
const ctx = document.getElementById('patientTrendChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($monthly_labels) ?>,
        datasets: [{
            label: 'Appointments Booked',
            data: <?= json_encode($monthly_counts) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#2563eb',
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Modal Action Handlers
document.addEventListener('DOMContentLoaded', function() {
    // 1. Cancel Appointment Handler
    const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
    const cancelIdInput = document.getElementById('cancel_appointment_id');
    
    document.querySelectorAll('.btn-cancel-appt').forEach(btn => {
        btn.addEventListener('click', function() {
            cancelIdInput.value = this.dataset.id;
            cancelModal.show();
        });
    });

    // 2. Reschedule Appointment Handler
    const rescheduleModal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
    const rescheduleForm = document.getElementById('rescheduleForm');
    const rescheduleIdInput = document.getElementById('reschedule_appointment_id');
    const rescheduleDateInput = document.getElementById('reschedule_date');
    const rescheduleTimeInput = document.getElementById('reschedule_time_input');
    const rescheduleSlotsWrapper = document.getElementById('reschedule_slots_wrapper');
    const rescheduleSlotsGrid = document.getElementById('reschedule_slots_grid');

    function fetchRescheduleSlots() {
        const doctorId = rescheduleForm.dataset.doctorId;
        const dateVal = rescheduleDateInput.value;

        if (!doctorId || !dateVal) {
            rescheduleSlotsWrapper.style.display = 'none';
            return;
        }

        rescheduleSlotsGrid.innerHTML = '<div class="text-secondary small py-2"><i class="bi bi-arrow-repeat spin me-2"></i>Loading slots...</div>';
        rescheduleSlotsWrapper.style.display = 'block';
        rescheduleTimeInput.value = '';

        fetch('api_get_slots.php?doctor_id=' + doctorId + '&date=' + dateVal)
            .then(res => res.json())
            .then(data => {
                rescheduleSlotsGrid.innerHTML = '';
                if (data.success) {
                    if (data.slots && data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = slot.available ? 'btn btn-outline-primary btn-sm py-2 px-3 fw-semibold' : 'btn btn-outline-secondary btn-sm py-2 px-3 fw-semibold';
                            btn.textContent = slot.time_formatted;
                            btn.style.borderRadius = '20px';
                            if (!slot.available) {
                                btn.disabled = true;
                                btn.style.opacity = '0.35';
                            } else {
                                btn.addEventListener('click', function() {
                                    document.querySelectorAll('#reschedule_slots_grid .btn-primary').forEach(b => {
                                        b.classList.remove('btn-primary');
                                        b.classList.add('btn-outline-primary');
                                    });
                                    btn.classList.remove('btn-outline-primary');
                                    btn.classList.add('btn-primary');
                                    rescheduleTimeInput.value = slot.time;
                                });
                            }
                            rescheduleSlotsGrid.appendChild(btn);
                        });
                    } else {
                        rescheduleSlotsGrid.innerHTML = '<div class="text-danger small py-2">Doctor not available on ' + (data.day || 'this day') + '.</div>';
                    }
                } else {
                    rescheduleSlotsGrid.innerHTML = '<div class="text-danger small py-2">Error loading slots.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                rescheduleSlotsGrid.innerHTML = '<div class="text-danger small py-2">Error connecting to server.</div>';
            });
    }

    document.querySelectorAll('.btn-reschedule-appt').forEach(btn => {
        btn.addEventListener('click', function() {
            rescheduleIdInput.value = this.dataset.id;
            rescheduleDateInput.value = this.dataset.date;
            rescheduleForm.dataset.doctorId = this.dataset.doctor;
            fetchRescheduleSlots();
            rescheduleModal.show();
        });
    });

    rescheduleDateInput.addEventListener('change', fetchRescheduleSlots);

    // 3. Timeline Handler
    const timelineModal = new bootstrap.Modal(document.getElementById('timelineModal'));
    const timelineList = document.getElementById('timeline_history_list');

    document.querySelectorAll('.btn-timeline-appt').forEach(btn => {
        btn.addEventListener('click', function() {
            const apptId = this.dataset.id;
            timelineList.innerHTML = '<div class="text-center py-3 text-secondary"><i class="bi bi-arrow-repeat spin fs-4 d-block mb-2"></i>Loading history...</div>';
            timelineModal.show();

            fetch('api_get_timeline.php?appointment_id=' + apptId)
                .then(res => res.json())
                .then(data => {
                    timelineList.innerHTML = '';
                    if (data.success && data.timeline && data.timeline.length > 0) {
                        data.timeline.forEach(item => {
                            const li = document.createElement('li');
                            const actionClass = item.status_action.toLowerCase();
                            li.className = `timeline-item ${actionClass}`;
                            
                            li.innerHTML = `
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">${item.status_action}</div>
                                    <div class="timeline-date">${item.created_at_formatted} by ${item.action_by.toUpperCase()}</div>
                                    <div class="timeline-body">${item.comments || 'No comments left.'}</div>
                                </div>
                            `;
                            timelineList.appendChild(li);
                        });
                    } else {
                        timelineList.innerHTML = '<div class="text-center py-3 text-muted">No history timeline events found.</div>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    timelineList.innerHTML = '<div class="text-center py-3 text-danger">Error loading timeline.</div>';
                });
    });

    // 4. Tab Switcher Logic
    const $portalNavGroup = $('#portal-nav-group');
    $portalNavGroup.find('a[data-tab]').on('click', function(e) {
        e.preventDefault();
        const targetTab = $(this).attr('data-tab');
        
        // Update active class
        $portalNavGroup.find('a').removeClass('active');
        $(this).addClass('active');
        
        // Show/hide panes
        $('.portal-tab-pane').addClass('d-none');
        $('#tab-pane-' + targetTab).removeClass('d-none');
        
        // Handle scrolling if requested
        const scrollTarget = $(this).attr('data-scroll');
        if (scrollTarget) {
            const $el = $('#' + scrollTarget);
            if ($el.length) {
                $('html, body').animate({
                    scrollTop: $el.offset().top - 120
                }, 300);
            }
        }
    });

    // Profile triggers
    $('.btn-trigger-profile-tab').on('click', function(e) {
        e.preventDefault();
        $portalNavGroup.find('a[data-tab="profile"]').trigger('click');
    });

    // Tab persistence on postback (e.g. if profile has errors/success)
    const activeTab = '<?= $active_tab ?>';
    if (activeTab === 'profile') {
        $portalNavGroup.find('a[data-tab="profile"]').trigger('click');
    }

    // 5. Patient Profile Client-side Validations
    const $profForm = $('#patientProfileForm');
    const $profFname = $('#prof_patient_fname');
    const $profMname = $('#prof_patient_mname');
    const $profLname = $('#prof_patient_lname');
    const $profEmail = $('#prof_patient_email');
    const $profPhone = $('#prof_patient_phone');
    const $profAddress = $('#prof_patient_address');
    const $profDob = $('#prof_patient_dob');
    const $profNewPw = $('#prof_new_password');
    const $profConfirmPw = $('#prof_confirm_password');

    const nameRegex = /^[a-zA-Z\s]{1,50}$/;
    const mnameRegex = /^[a-zA-Z\s]{0,50}$/;
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const phoneRegex = /^[0-9\+\-\s]{10,15}$/;
    const pwRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    function markField(field, isValid) {
        if (isValid) {
            field.removeClass('is-invalid').addClass('is-valid');
        } else {
            field.removeClass('is-valid').addClass('is-invalid');
        }
    }

    $profFname.on('input blur', function() {
        markField($profFname, nameRegex.test($(this).val().trim()));
    });
    
    $profMname.on('input blur', function() {
        const val = $(this).val().trim();
        markField($profMname, val === "" || mnameRegex.test(val));
    });

    $profLname.on('input blur', function() {
        markField($profLname, nameRegex.test($(this).val().trim()));
    });

    $profEmail.on('input blur', function() {
        markField($profEmail, emailRegex.test($(this).val().trim()));
    });

    $profPhone.on('input blur', function() {
        markField($profPhone, phoneRegex.test($(this).val().trim()));
    });

    $profAddress.on('input blur', function() {
        const len = $(this).val().trim().length;
        markField($profAddress, len >= 10 && len <= 200);
    });

    $profDob.on('input blur change', function() {
        const dobVal = $(this).val();
        if (!dobVal) {
            markField($profDob, false);
            return;
        }
        const selectedDate = new Date(dobVal);
        const today = new Date();
        markField($profDob, selectedDate < today);
    });

    $profNewPw.on('focus', function() {
        $('#prof-pw-strength-wrapper').removeClass('d-none');
    });

    $profNewPw.on('input blur', function() {
        const val = $(this).val();
        if (val === "") {
            $('#prof-pw-strength-wrapper').addClass('d-none');
            $profNewPw.removeClass('is-invalid is-valid');
            $profConfirmPw.removeClass('is-invalid is-valid');
            return;
        }
        
        $('#prof-pw-strength-wrapper').removeClass('d-none');
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[@$!%*?&]/.test(val)) score++;

        const $bar = $('#prof-pw-strength-bar');
        const $text = $('#prof-pw-strength-text');
        const width = (score / 5) * 100;
        $bar.css('width', width + '%');

        if (score <= 2) {
            $bar.removeClass('bg-warning bg-success').addClass('bg-danger');
            $text.text('Weak password').removeClass('text-warning text-success').addClass('text-danger');
        } else if (score <= 4) {
            $bar.removeClass('bg-danger bg-success').addClass('bg-warning');
            $text.text('Medium strength').removeClass('text-danger text-success').addClass('text-warning');
        } else {
            $bar.removeClass('bg-danger bg-warning').addClass('bg-success');
            $text.text('Strong password').removeClass('text-danger text-warning').addClass('text-success');
        }

        markField($profNewPw, pwRegex.test(val));
    });

    $profConfirmPw.on('input blur', function() {
        const newPw = $profNewPw.val();
        const confPw = $(this).val();
        if (newPw === "" && confPw === "") {
            $profConfirmPw.removeClass('is-invalid is-valid');
            return;
        }
        markField($profConfirmPw, newPw === confPw);
    });

    $profForm.on('submit', function(e) {
        $profFname.trigger('blur');
        $profMname.trigger('blur');
        $profLname.trigger('blur');
        $profEmail.trigger('blur');
        $profPhone.trigger('blur');
        $profAddress.trigger('blur');
        $profDob.trigger('blur');
        
        if ($profNewPw.val() !== "") {
            $profNewPw.trigger('blur');
            $profConfirmPw.trigger('blur');
        }

        if ($profForm.find('.is-invalid').length > 0) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $profForm.find('.is-invalid').first().offset().top - 120
            }, 300);
        }
    });
});

</script>

<!-- Premium Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1060;">
  <div id="premiumToast" class="toast toast-premium hide align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex p-3 align-items-center">
      <div class="toast-icon-container me-3 text-primary fs-4">
        <i class="bi bi-info-circle-fill" id="premiumToastIcon"></i>
      </div>
      <div class="toast-body p-0 flex-grow-1">
        <h6 class="mb-0 fw-bold text-dark" id="premiumToastTitle">Notification</h6>
        <span class="small text-secondary" id="premiumToastMsg"></span>
      </div>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script>
function showToast(message, type = 'success', title = '') {
    const toastEl = document.getElementById('premiumToast');
    const toastIcon = document.getElementById('premiumToastIcon');
    const toastTitle = document.getElementById('premiumToastTitle');
    const toastMsg = document.getElementById('premiumToastMsg');
    
    toastMsg.textContent = message;
    toastTitle.textContent = title || (type === 'success' ? 'Success' : 'Error');
    
    toastIcon.className = '';
    if (type === 'success') {
        toastIcon.className = 'bi bi-check-circle-fill text-success';
        toastEl.style.borderLeft = '4px solid #10b981';
    } else {
        toastIcon.className = 'bi bi-exclamation-triangle-fill text-danger';
        toastEl.style.borderLeft = '4px solid #ef4444';
    }
    
    const toastObj = new bootstrap.Toast(toastEl, { delay: 5000 });
    toastObj.show();
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['toast_success'])): ?>
        showToast(<?= json_encode($_SESSION['toast_success']) ?>, 'success', 'Success');
        <?php unset($_SESSION['toast_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['toast_error'])): ?>
        showToast(<?= json_encode($_SESSION['toast_error']) ?>, 'error', 'Error');
        <?php unset($_SESSION['toast_error']); ?>
    <?php endif; ?>
});
</script>
</body>
</html>
