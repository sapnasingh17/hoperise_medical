<?php
include('common/css.php');

// Retrieve role from common/css.php query result
// $role is either 'admin' or 'doctor'
$doctor_id = intval($_SESSION['admin_id']);

if ($role == 'admin') {
    // 1. Total Patients
    list($pt_stmt, $res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_patient");
    $row = mysqli_fetch_assoc($res);
    $total_patients = intval($row['cnt']);
    if ($pt_stmt) mysqli_stmt_close($pt_stmt);

    // 2. Active Doctor Profiles
    list($doc_stmt, $res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_doctor");
    $row = mysqli_fetch_assoc($res);
    $total_doctors = intval($row['cnt']);
    if ($doc_stmt) mysqli_stmt_close($doc_stmt);

    // 3. Total Appointments
    list($apt_stmt, $res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_appointment");
    $row = mysqli_fetch_assoc($res);
    $total_appointments = intval($row['cnt']);
    if ($apt_stmt) mysqli_stmt_close($apt_stmt);

    // 4. Projected Revenue (simulated: paid * ₹500)
    list($rev_stmt, $res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_appointment WHERE appointment_payment_status = 1");
    $row = mysqli_fetch_assoc($res);
    $total_revenue = intval($row['cnt']) * 500;
    if ($rev_stmt) mysqli_stmt_close($rev_stmt);

    // 5. Specialty Distribution for Chart
    $specialty_labels = [];
    $specialty_counts = [];
    list($spec_stmt, $res) = db_query_prepared($conn, "SELECT c.category_title, COUNT(*) as cnt 
                                FROM hs_appointment a 
                                JOIN hs_category c ON a.category_id = c.category_id 
                                GROUP BY a.category_id");
    while ($row = mysqli_fetch_assoc($res)) {
        $specialty_labels[] = $row['category_title'];
        $specialty_counts[] = intval($row['cnt']);
    }
    if ($spec_stmt) mysqli_stmt_close($spec_stmt);
    if (empty($specialty_labels)) {
        $specialty_labels = ['General', 'Cardiology', 'Pediatrics'];
        $specialty_counts = [0, 0, 0];
    }

    // 6. Recent Appointments list
    $recent_appointments = [];
    list($rec_stmt, $res) = db_query_prepared($conn, "SELECT a.*, p.patient_fname, p.patient_lname, p.patient_email, d.doctor_fname, d.doctor_lname, c.category_title 
                                FROM hs_appointment a 
                                LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
                                LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id 
                                LEFT JOIN hs_category c ON a.category_id = c.category_id 
                                ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($res)) {
        $recent_appointments[] = $row;
    }
    if ($rec_stmt) mysqli_stmt_close($rec_stmt);
} else {
    // 1. Total Assigned Consultations
    list($t_stmt, $res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_appointment WHERE doctor_id = ?", [$doctor_id]);
    $row = mysqli_fetch_assoc($res);
    $total_assigned = intval($row['cnt']);
    if ($t_stmt) mysqli_stmt_close($t_stmt);

    // 2. Confirmed Sessions
    list($c_stmt, $res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_appointment WHERE doctor_id = ? AND appointment_status = 'Accept'", [$doctor_id]);
    $row = mysqli_fetch_assoc($res);
    $confirmed_sessions = intval($row['cnt']);
    if ($c_stmt) mysqli_stmt_close($c_stmt);

    // 3. Pending Visits
    list($p_stmt, $res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_appointment WHERE doctor_id = ? AND (appointment_status = 'Pending' OR appointment_status = '' OR appointment_status IS NULL)", [$doctor_id]);
    $row = mysqli_fetch_assoc($res);
    $pending_visits = intval($row['cnt']);
    if ($p_stmt) mysqli_stmt_close($p_stmt);

    // 4. Unique Patients
    list($u_stmt, $res) = db_query_prepared($conn, "SELECT COUNT(DISTINCT patient_id) as cnt FROM hs_appointment WHERE doctor_id = ?", [$doctor_id]);
    $row = mysqli_fetch_assoc($res);
    $unique_patients = intval($row['cnt']);
    if ($u_stmt) mysqli_stmt_close($u_stmt);

    // 5. Workload by status for Chart
    $status_labels = ['Confirmed', 'Pending', 'Rejected/Cancelled'];
    $status_counts = [0, 0, 0];
    list($w_stmt, $res) = db_query_prepared($conn, "SELECT appointment_status, COUNT(*) as cnt FROM hs_appointment WHERE doctor_id = ? GROUP BY appointment_status", [$doctor_id]);
    while ($row = mysqli_fetch_assoc($res)) {
        $st = $row['appointment_status'];
        $c = intval($row['cnt']);
        $st_lower = strtolower($st ?? '');
        if ($st_lower === 'accept' || $st_lower === 'confirmed') {
            $status_counts[0] = $c;
        } elseif ($st_lower === 'pending' || $st_lower === '') {
            $status_counts[1] += $c;
        } else {
            $status_counts[2] += $c;
        }
    }
    if ($w_stmt) mysqli_stmt_close($w_stmt);

    // 6. Recent Assigned Appointments
    $recent_appointments = [];
    list($ra_stmt, $res) = db_query_prepared($conn, "SELECT a.*, p.patient_fname, p.patient_lname, p.patient_email, c.category_title, s.service_title 
                                FROM hs_appointment a 
                                LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
                                LEFT JOIN hs_category c ON a.category_id = c.category_id 
                                LEFT JOIN hs_service s ON a.service_id = s.service_id
                                WHERE a.doctor_id = ? 
                                ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 5", [$doctor_id]);
    while ($row = mysqli_fetch_assoc($res)) {
        $recent_appointments[] = $row;
    }
    if ($ra_stmt) mysqli_stmt_close($ra_stmt);
}

// Fetch dynamic monthly trend chart data (Universal)
$monthly_labels = [];
$monthly_counts = [];
if ($role == 'doctor') {
    list($trend_stmt, $res) = db_query_prepared($conn, "SELECT DATE_FORMAT(appointment_date, '%b %Y') as month_label, COUNT(*) as cnt 
                                FROM hs_appointment WHERE doctor_id = ? 
                                GROUP BY DATE_FORMAT(appointment_date, '%b %Y'), DATE_FORMAT(appointment_date, '%Y-%m') 
                                ORDER BY DATE_FORMAT(appointment_date, '%Y-%m') ASC LIMIT 6", [$doctor_id]);
} else {
    list($trend_stmt, $res) = db_query_prepared($conn, "SELECT DATE_FORMAT(appointment_date, '%b %Y') as month_label, COUNT(*) as cnt 
                                FROM hs_appointment 
                                GROUP BY DATE_FORMAT(appointment_date, '%b %Y'), DATE_FORMAT(appointment_date, '%Y-%m') 
                                ORDER BY DATE_FORMAT(appointment_date, '%Y-%m') ASC LIMIT 6");
}
while ($row = mysqli_fetch_assoc($res)) {
    $monthly_labels[] = $row['month_label'];
    $monthly_counts[] = intval($row['cnt']);
}
if ($trend_stmt) mysqli_stmt_close($trend_stmt);

if (empty($monthly_labels)) {
    $monthly_labels = [date('M Y')];
    $monthly_counts = [0];
}
?>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* Custom scoped CSS overrides for a premium Hoperise Admin Dashboard */
body, .page-wrapper, .content-wrapper, .ibox, .table, .badge, .form-control {
    font-family: 'Outfit', sans-serif !important;
}
.content-wrapper {
    background-color: #f8fafc !important;
    padding: 30px !important;
}
.page-heading {
    margin-bottom: 30px !important;
}
.page-title {
    font-weight: 800 !important;
    font-size: 2.1rem !important;
    color: #0f172a !important;
    letter-spacing: -0.8px;
    margin-bottom: 6px !important;
}
.breadcrumb {
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    color: #64748b !important;
    font-size: 0.95rem !important;
}

/* Premium Statistics Cards */
.widget-stat {
    border: none !important;
    border-radius: 20px !important;
    overflow: hidden;
    position: relative;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
    padding: 28px 24px !important;
    margin-bottom: 24px !important;
    border: 1px solid rgba(255, 255, 255, 0.18) !important;
}
.widget-stat:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.15), 0 12px 20px -8px rgba(0, 0, 0, 0.1) !important;
}
.widget-stat .ibox-body {
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.widget-stat h2 {
    font-size: 2.6rem !important;
    font-weight: 800 !important;
    margin: 0 0 6px 0 !important;
    letter-spacing: -1px;
    line-height: 1.1;
}
.widget-stat .widget-stat-label {
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    opacity: 0.9;
    margin-bottom: 8px !important;
}
.widget-stat-icon {
    position: absolute;
    right: 24px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 4.2rem !important;
    opacity: 0.18;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.widget-stat:hover .widget-stat-icon {
    transform: translateY(-50%) scale(1.2) rotate(8deg);
}

/* Stat Cards Trend Indicators */
.stat-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.78rem !important;
    font-weight: 700 !important;
    padding: 3px 8px;
    border-radius: 30px;
    background: rgba(255, 255, 255, 0.2);
    width: fit-content;
    margin-top: 10px;
}

/* Premium Gradients */
.widget-stat-patients {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
    color: #ffffff !important;
}
.widget-stat-doctors {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
    color: #ffffff !important;
}
.widget-stat-bookings {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    color: #ffffff !important;
}
.widget-stat-revenue {
    background: linear-gradient(135deg, #ec4899 0%, #db2777 100%) !important;
    color: #ffffff !important;
}
.widget-stat-visits {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    color: #ffffff !important;
}
.widget-stat-confirmed {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
}
.widget-stat-pending {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important;
    color: #ffffff !important;
}
.widget-stat-mypatients {
    background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%) !important;
    color: #ffffff !important;
}

/* Cards / Boxes Styling */
.ibox {
    border: 1px solid #e2e8f0 !important;
    border-radius: 20px !important;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.02), 0 4px 6px -4px rgba(0,0,0,0.02) !important;
    background-color: #ffffff !important;
    margin-bottom: 28px !important;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.ibox:hover {
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.05) !important;
}
.ibox-head {
    border-bottom: 1px solid #f1f5f9 !important;
    background-color: #ffffff !important;
    padding: 22px 28px !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ibox-title {
    font-size: 1.2rem !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.3px;
}
.ibox-title i {
    font-size: 1.35rem;
}
.ibox-body {
    padding: 28px !important;
}

/* Search Box Focus Animation */
.form-control-premium-sm {
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 12px !important;
    padding: 10px 16px 10px 38px !important;
    font-size: 0.9rem !important;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    width: 210px !important;
    color: #334155;
    background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E") no-repeat 14px center;
    background-size: 16px;
}
.form-control-premium-sm:focus {
    border-color: #4f46e5 !important;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15) !important;
    width: 270px !important;
    outline: none !important;
}

/* Responsive Table */
.table-responsive {
    border-radius: 16px;
    overflow: hidden;
}
.table {
    margin-bottom: 0 !important;
    width: 100%;
}
.table th {
    border-top: none !important;
    border-bottom: 2px solid #e2e8f0 !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    font-size: 0.75rem !important;
    letter-spacing: 1px;
    color: #64748b !important;
    padding: 18px 24px !important;
    background-color: #fafbfc !important;
}
.table td {
    padding: 18px 24px !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f1f5f9 !important;
    color: #334155 !important;
    font-size: 0.95rem !important;
}
.table-hover tbody tr {
    transition: background-color 0.2s ease;
}
.table-hover tbody tr:hover {
    background-color: #f8fafc !important;
}

/* Patient Avatar Initials */
.patient-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #4f46e5;
    font-weight: 700;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* Badges */
.badge {
    padding: 8px 14px !important;
    font-weight: 700 !important;
    font-size: 0.72rem !important;
    border-radius: 20px !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
}
.badge-success {
    background-color: rgba(16, 185, 129, 0.12) !important;
    color: #059669 !important;
    border: 1px solid rgba(16, 185, 129, 0.2) !important;
}
.badge-warning {
    background-color: rgba(245, 158, 11, 0.12) !important;
    color: #d97706 !important;
    border: 1px solid rgba(245, 158, 11, 0.2) !important;
}
.badge-danger {
    background-color: rgba(239, 68, 68, 0.12) !important;
    color: #dc2626 !important;
    border: 1px solid rgba(239, 68, 68, 0.2) !important;
}

/* Quick Actions Grid Overhaul */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 12px;
}
@media (min-width: 576px) {
    .quick-actions-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (min-width: 992px) {
    .quick-actions-grid {
        grid-template-columns: repeat(1, 1fr);
    }
}
.quick-action-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 20px;
    border-radius: 16px;
    background-color: #f8fafc;
    border: 1.5px solid #e2e8f0;
    transition: all 0.25s ease;
    text-decoration: none !important;
    color: #334155 !important;
    font-weight: 600;
}
.quick-action-card:hover {
    background-color: #4f46e5;
    border-color: #4f46e5;
    color: #ffffff !important;
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.25);
}
.quick-action-icon-wrapper {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background-color: #e0e7ff;
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: all 0.25s ease;
}
.quick-action-card:hover .quick-action-icon-wrapper {
    background-color: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}

/* Calendar Grid */
.calendar-grid {
    gap: 8px !important;
}
.calendar-grid div {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem !important;
    font-weight: 600 !important;
    width: 36px !important;
    height: 36px !important;
    margin: auto !important;
    border-radius: 50% !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    color: #475569;
}
.calendar-day-header {
    color: #94a3b8 !important;
    font-weight: 800 !important;
    text-transform: uppercase;
    font-size: 0.75rem !important;
}
.calendar-cell-appt {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3) !important;
    cursor: pointer;
}
.calendar-cell-appt:hover {
    transform: scale(1.15) translateY(-2px);
    box-shadow: 0 6px 14px rgba(79, 70, 229, 0.45) !important;
}
.calendar-cell-today {
    border: 2px solid #4f46e5 !important;
    color: #4f46e5 !important;
}
.calendar-cell-appt.calendar-cell-today {
    border: 2px solid #ffffff !important;
    box-shadow: 0 0 0 2px #4f46e5, 0 4px 10px rgba(79, 70, 229, 0.3) !important;
    color: #ffffff !important;
}
</style>
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
                <h1 class="page-title">Dashboard</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="fa fa-home"></i></a>
                    </li>
                    <li class="breadcrumb-item">Overview of operational metrics, patient flow, and clinical schedules.</li>
                </ol>
            </div>
            <div class="page-content fade-in-up">
                <!-- Statistics Cards Row -->
                <?php if ($role == 'admin'): ?>
                    <!-- Admin Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="ibox widget-stat widget-stat-patients">
                                <div class="ibox-body">
                                    <div class="widget-stat-label">TOTAL PATIENTS</div>
                                    <h2><?= $total_patients ?></h2>
                                    <i class="fa fa-users widget-stat-icon"></i>
                                    <div><small>Registered patients</small></div>
                                    <span class="stat-trend"><i class="fa fa-caret-up"></i> +8% this month</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="ibox widget-stat widget-stat-doctors">
                                <div class="ibox-body">
                                    <div class="widget-stat-label">ACTIVE DOCTORS</div>
                                    <h2><?= $total_doctors ?></h2>
                                    <i class="fa fa-user-md widget-stat-icon"></i>
                                    <div><small>Verified profiles</small></div>
                                    <span class="stat-trend"><i class="fa fa-heartbeat"></i> Capacity 95%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="ibox widget-stat widget-stat-bookings">
                                <div class="ibox-body">
                                    <div class="widget-stat-label">TOTAL BOOKINGS</div>
                                    <h2><?= $total_appointments ?></h2>
                                    <i class="fa fa-calendar widget-stat-icon"></i>
                                    <div><small>All scheduled sessions</small></div>
                                    <span class="stat-trend"><i class="fa fa-caret-up"></i> +14% vs last week</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="ibox widget-stat widget-stat-revenue">
                                <div class="ibox-body">
                                    <div class="widget-stat-label">NET REVENUE</div>
                                    <h2>₹<?= $total_revenue ?></h2>
                                    <i class="fa fa-money widget-stat-icon"></i>
                                    <div><small>Paid consultations</small></div>
                                    <span class="stat-trend"><i class="fa fa-check"></i> Verified</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Doctor Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="ibox widget-stat widget-stat-visits">
                                <div class="ibox-body">
                                    <div class="widget-stat-label">TOTAL VISITS</div>
                                    <h2><?= $total_assigned ?></h2>
                                    <i class="fa fa-calendar widget-stat-icon"></i>
                                    <div><small>Assigned bookings</small></div>
                                    <span class="stat-trend"><i class="fa fa-user-md"></i> Clinic schedules</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="ibox widget-stat widget-stat-confirmed">
                                <div class="ibox-body">
                                    <div class="widget-stat-label">CONFIRMED</div>
                                    <h2><?= $confirmed_sessions ?></h2>
                                    <i class="fa fa-check-circle widget-stat-icon"></i>
                                    <div><small>Accepted consultations</small></div>
                                    <span class="stat-trend"><i class="fa fa-thumbs-up"></i> Active flow</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="ibox widget-stat widget-stat-pending">
                                <div class="ibox-body">
                                    <div class="widget-stat-label">PENDING</div>
                                    <h2><?= $pending_visits ?></h2>
                                    <i class="fa fa-hourglass-half widget-stat-icon"></i>
                                    <div><small>Awaiting review</small></div>
                                    <span class="stat-trend"><i class="fa fa-clock-o"></i> Review needed</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="ibox widget-stat widget-stat-mypatients">
                                <div class="ibox-body">
                                    <div class="widget-stat-label">MY PATIENTS</div>
                                    <h2><?= $unique_patients ?></h2>
                                    <i class="fa fa-users widget-stat-icon"></i>
                                    <div><small>Unique patients treated</small></div>
                                    <span class="stat-trend"><i class="fa fa-user"></i> Patient network</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Charts Row -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title"><i class="fa fa-line-chart text-primary"></i>Operational Booking Trends</div>
                            </div>
                            <div class="ibox-body">
                                <div style="height: 300px; position: relative;">
                                    <canvas id="dashboardTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title"><i class="fa fa-pie-chart text-primary"></i><?= ($role == 'admin') ? 'Specialty Distribution' : 'Workload Distribution' ?></div>
                            </div>
                            <div class="ibox-body">
                                <div style="height: 300px; position: relative;">
                                    <canvas id="dashboardPieChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & Calendar Row -->
                <div class="row">
                    <!-- Recent Activity Table -->
                    <div class="col-lg-8">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Recent Activity</div>
                                <div class="ibox-tools" style="display: flex; align-items: center;">
                                    <input type="text" id="adminSearch" class="form-control-premium-sm" placeholder="Search records...">
                                </div>
                            </div>
                            <div class="ibox-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th><?= ($role == 'admin') ? 'Doctor' : 'Service' ?></th>
                                                <th>Schedule</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activityTableBody">
                                            <?php if (!empty($recent_appointments)): ?>
                                                <?php foreach ($recent_appointments as $appt): 
                                                    $patient_lbl = htmlspecialchars($appt['patient_fname'] . " " . $appt['patient_lname']);
                                                    $detail_lbl = ($role == 'admin') ? "Dr. " . htmlspecialchars($appt['doctor_fname'] . " " . $appt['doctor_lname']) : htmlspecialchars($appt['service_title']);
                                                    
                                                    $st = $appt['appointment_status'];
                                                    $st_lower = strtolower($st ?? '');
                                                    $badge = "badge-warning";
                                                    if ($st_lower === 'accept' || $st_lower === 'confirmed') $badge = "badge-success";
                                                    elseif ($st_lower === 'reject' || $st_lower === 'cancel') $badge = "badge-danger";

                                                    // Generate patient avatar initials
                                                    $initials = '';
                                                    if (!empty($appt['patient_fname'])) {
                                                        $initials .= strtoupper(substr($appt['patient_fname'], 0, 1));
                                                    }
                                                    if (!empty($appt['patient_lname'])) {
                                                        $initials .= strtoupper(substr($appt['patient_lname'], 0, 1));
                                                    }
                                                    if (empty($initials)) $initials = 'PT';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="patient-cell">
                                                            <div class="avatar-circle"><?= $initials ?></div>
                                                            <div>
                                                                <div style="font-weight: 600; color: #0f172a;"><?= $patient_lbl ?></div>
                                                                <?php if (!empty($appt['patient_email'])): ?>
                                                                    <div style="font-size: 0.78rem; color: #64748b;"><?= htmlspecialchars($appt['patient_email']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td style="color: #475569; font-weight: 500;"><?= $detail_lbl ?></td>
                                                    <td>
                                                        <div style="font-weight: 600; font-size: 0.85rem; color: #1e293b;"><i class="fa fa-calendar text-primary m-r-5"></i><?= htmlspecialchars($appt['appointment_date']) ?></div>
                                                        <div style="font-size: 0.8rem; color: #64748b;"><i class="fa fa-clock-o text-primary m-r-5"></i><?= htmlspecialchars($appt['appointment_time']) ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($st ? $st : 'Pending') ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted">No appointments found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mini Calendar & Quick Actions -->
                    <div class="col-lg-4">
                        <!-- Quick Actions Grid -->
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Quick Actions</div>
                            </div>
                            <div class="ibox-body">
                                <div class="quick-actions-grid">
                                    <?php if ($role == 'admin'): ?>
                                        <a href="add_doctor.php" class="quick-action-card">
                                            <div class="quick-action-icon-wrapper"><i class="fa fa-user-md"></i></div>
                                            <span>Register Doctor</span>
                                        </a>
                                        <a href="display_appoinment.php" class="quick-action-card">
                                            <div class="quick-action-icon-wrapper"><i class="fa fa-calendar"></i></div>
                                            <span>Manage Bookings</span>
                                        </a>
                                        <a href="display_service.php" class="quick-action-card">
                                            <div class="quick-action-icon-wrapper"><i class="fa fa-cog"></i></div>
                                            <span>Service Directory</span>
                                        </a>
                                    <?php else: ?>
                                        <a href="display_appoinment.php" class="quick-action-card">
                                            <div class="quick-action-icon-wrapper"><i class="fa fa-calendar"></i></div>
                                            <span>My Bookings</span>
                                        </a>
                                        <a href="feedback.php" class="quick-action-card">
                                            <div class="quick-action-icon-wrapper"><i class="fa fa-commenting"></i></div>
                                            <span>Patient Reviews</span>
                                        </a>
                                        <a href="profile.php" class="quick-action-card">
                                            <div class="quick-action-icon-wrapper"><i class="fa fa-user-circle"></i></div>
                                            <span>Update Profile</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Time Slots Calendar</div>
                                <div class="ibox-tools">
                                    <span class="font-strong small" style="color: #64748b; font-weight: 700;"><?= date('F Y') ?></span>
                                </div>
                            </div>
                            <div class="ibox-body">
                                <div class="calendar-grid d-grid" style="grid-template-columns: repeat(7, 1fr);">
                                    <div class="calendar-day-header">Su</div>
                                    <div class="calendar-day-header">Mo</div>
                                    <div class="calendar-day-header">Tu</div>
                                    <div class="calendar-day-header">We</div>
                                    <div class="calendar-day-header">Th</div>
                                    <div class="calendar-day-header">Fr</div>
                                    <div class="calendar-day-header">Sa</div>
                                    <?php
                                    $start_day = date('w', strtotime(date('Y-m-01')));
                                    $days_in_month = date('t');
                                    
                                    for ($i = 0; $i < $start_day; $i++) {
                                        echo '<div></div>';
                                    }
                                    
                                    $start_date_bound = date('Y-m-01');
                                    $end_date_bound = date('Y-m-t');
                                    $appt_dates = [];
                                    if ($role == 'doctor') {
                                        list($stmt_check_month, $res_check_month) = db_query_prepared($conn, "SELECT DISTINCT appointment_date FROM hs_appointment WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?", [$doctor_id, $start_date_bound, $end_date_bound]);
                                    } else {
                                        list($stmt_check_month, $res_check_month) = db_query_prepared($conn, "SELECT DISTINCT appointment_date FROM hs_appointment WHERE appointment_date BETWEEN ? AND ?", [$start_date_bound, $end_date_bound]);
                                    }
                                    if ($res_check_month) {
                                        while ($r = mysqli_fetch_assoc($res_check_month)) {
                                            $appt_dates[] = $r['appointment_date'];
                                        }
                                    }
                                    if ($stmt_check_month) {
                                        mysqli_stmt_close($stmt_check_month);
                                    }
                                    
                                    for ($day = 1; $day <= $days_in_month; $day++) {
                                        $current_date = date('Y-m-') . sprintf('%02d', $day);
                                        $has_appt = in_array($current_date, $appt_dates);
                                        
                                        $cell_class = '';
                                        if ($has_appt) {
                                            $cell_class .= ' calendar-cell-appt';
                                        }
                                        if ($day == date('d')) {
                                            $cell_class .= ' calendar-cell-today';
                                        }
                                        
                                        echo "<div class='$cell_class'>$day</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
 
    <!-- Load Chart.js from CDN dynamically to avoid library conflicts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Client-side quick filter
    document.getElementById('adminSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#activityTableBody tr');
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // 1. Trend Chart
    const trendCtx = document.getElementById('dashboardTrendChart').getContext('2d');
    
    // Create gradient background
    const gradient = trendCtx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthly_labels) ?>,
            datasets: [{
                label: 'Consultations',
                data: <?= json_encode($monthly_counts) ?>,
                borderColor: '#6366f1',
                backgroundColor: gradient,
                borderWidth: 3.5,
                fill: true,
                tension: 0.45,
                pointBackgroundColor: '#6366f1',
                pointHoverBackgroundColor: '#6366f1',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { family: 'Outfit', size: 13, weight: 'bold' },
                    bodyFont: { family: 'Outfit', size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Outfit', size: 11, weight: 600 },
                        color: '#64748b'
                    }
                },
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Outfit', size: 11, weight: 600 },
                        color: '#64748b',
                        stepSize: 1
                    },
                    beginAtZero: true
                }
            }
        }
    });

    // 2. Specialty / Workload Doughnut Chart
    const pieCtx = document.getElementById('dashboardPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($role == 'admin' ? $specialty_labels : $status_labels) ?>,
            datasets: [{
                data: <?= json_encode($role == 'admin' ? $specialty_counts : $status_counts) ?>,
                backgroundColor: ['#6366f1', '#06b6d4', '#f59e0b', '#ec4899', '#14b8a6'],
                borderWidth: 4,
                borderColor: '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: true,
                        padding: 24,
                        font: { family: 'Outfit', size: 11, weight: 600 },
                        color: '#475569'
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { family: 'Outfit', size: 13, weight: 'bold' },
                    bodyFont: { family: 'Outfit', size: 12 },
                    padding: 12,
                    cornerRadius: 10
                }
            },
            cutout: '72%'
        }
    });
    </script>
</body>
</html>