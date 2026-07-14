<?php
include('common/css.php');

// Ensure role is admin
$role_id = intval($_SESSION['role_id']);
list($role_stmt, $role_data) = db_query_prepared($conn, "SELECT * FROM hs_role WHERE role_id = ?", [$role_id]);
$role_row = mysqli_fetch_array($role_data);
$role = $role_row['role_title'] ?? '';
if ($role_stmt) {
    mysqli_stmt_close($role_stmt);
}

if ($role !== 'admin') {
    echo "<script>alert('Access Denied'); window.location.href='index.php';</script>";
    exit();
}

// Retrieve filter options
$report_type = isset($_GET['report']) ? trim($_GET['report']) : 'appointments';
$timeframe = isset($_GET['timeframe']) ? trim($_GET['timeframe']) : 'monthly';
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-d');

// Construct filters for tables
$where_clauses = [];
$params = [];
$types = "";

if ($timeframe === 'monthly') {
    $where_clauses[] = "YEAR(a.appointment_date) = ? AND MONTH(a.appointment_date) = ?";
    $params[] = $year;
    $params[] = $month;
    $types .= "ii";
} elseif ($timeframe === 'yearly') {
    $where_clauses[] = "YEAR(a.appointment_date) = ?";
    $params[] = $year;
    $types .= "i";
} elseif ($timeframe === 'custom' && !empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "a.appointment_date >= ? AND a.appointment_date <= ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Compute dynamic KPIs for the selected timeframe
// 1. Total Appointments for selected period
list($kpi_apt_stmt, $kpi_apt_res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_appointment a $where_sql", $params, $types);
$kpi_apt_row = mysqli_fetch_assoc($kpi_apt_res);
$period_appointments = intval($kpi_apt_row['cnt']);
if ($kpi_apt_stmt) mysqli_stmt_close($kpi_apt_stmt);

// 2. Period Revenue (simulated: paid * ₹500)
$rev_clauses = $where_clauses;
$rev_clauses[] = "a.appointment_payment_status = 1";
$rev_where = count($rev_clauses) > 0 ? " WHERE " . implode(" AND ", $rev_clauses) : "";
list($kpi_rev_stmt, $kpi_rev_res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_appointment a $rev_where", $params, $types);
$kpi_rev_row = mysqli_fetch_assoc($kpi_rev_res);
$period_revenue = intval($kpi_rev_row['cnt']) * 500;
if ($kpi_rev_stmt) mysqli_stmt_close($kpi_rev_stmt);

// 3. New Patients Registered in Period
$pat_where = "";
if ($timeframe === 'monthly') {
    $pat_where = " WHERE YEAR(created_date) = ? AND MONTH(created_date) = ?";
} elseif ($timeframe === 'yearly') {
    $pat_where = " WHERE YEAR(created_date) = ?";
} elseif ($timeframe === 'custom') {
    $pat_where = " WHERE created_date >= ? AND created_date <= ?";
}
list($kpi_pat_stmt, $kpi_pat_res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_patient $pat_where", $params, $types);
$kpi_pat_row = mysqli_fetch_assoc($kpi_pat_res);
$period_patients = intval($kpi_pat_row['cnt']);
if ($kpi_pat_stmt) mysqli_stmt_close($kpi_pat_stmt);

// 4. Feedback received in period
$feed_where = "";
if ($timeframe === 'monthly') {
    $feed_where = " WHERE YEAR(created_date) = ? AND MONTH(created_date) = ?";
} elseif ($timeframe === 'yearly') {
    $feed_where = " WHERE YEAR(created_date) = ?";
} elseif ($timeframe === 'custom') {
    $feed_where = " WHERE created_date >= ? AND created_date <= ?";
}
list($kpi_feed_stmt, $kpi_feed_res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_feedback $feed_where", $params, $types);
$kpi_feed_row = mysqli_fetch_assoc($kpi_feed_res);
$period_feedback = intval($kpi_feed_row['cnt']);
if ($kpi_feed_stmt) mysqli_stmt_close($kpi_feed_stmt);

// 5. Total Doctors
list($kpi_doc_stmt, $kpi_doc_res) = db_query_prepared($conn, "SELECT COUNT(*) as cnt FROM hs_doctor");
$kpi_doc_row = mysqli_fetch_assoc($kpi_doc_res);
$total_doctors = intval($kpi_doc_row['cnt']);
if ($kpi_doc_stmt) mysqli_stmt_close($kpi_doc_stmt);


// Trend chart data: appointments & revenue over time based on the timeframe
$chart_labels = [];
$chart_appointments = [];
$chart_revenue = [];

if ($timeframe === 'monthly') {
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($d = 1; $d <= $days_in_month; $d++) {
        $chart_labels[] = "Day " . $d;
        $chart_appointments[$d] = 0;
        $chart_revenue[$d] = 0;
    }
    
    $sql_c = "SELECT DAY(appointment_date) as d_num, COUNT(*) as cnt FROM hs_appointment a 
              WHERE YEAR(appointment_date) = ? AND MONTH(appointment_date) = ? GROUP BY DAY(appointment_date)";
    list($st_c, $res_c) = db_query_prepared($conn, $sql_c, [$year, $month], 'ii');
    while ($r = mysqli_fetch_assoc($res_c)) {
        $chart_appointments[intval($r['d_num'])] = intval($r['cnt']);
    }
    if ($st_c) mysqli_stmt_close($st_c);

    $sql_r = "SELECT DAY(appointment_date) as d_num, COUNT(*) as cnt FROM hs_appointment a 
              WHERE YEAR(appointment_date) = ? AND MONTH(appointment_date) = ? AND appointment_payment_status = 1 GROUP BY DAY(appointment_date)";
    list($st_r, $res_r) = db_query_prepared($conn, $sql_r, [$year, $month], 'ii');
    while ($r = mysqli_fetch_assoc($res_r)) {
        $chart_revenue[intval($r['d_num'])] = intval($r['cnt']) * 500;
    }
    if ($st_r) mysqli_stmt_close($st_r);
    
    $chart_appointments = array_values($chart_appointments);
    $chart_revenue = array_values($chart_revenue);

} elseif ($timeframe === 'yearly') {
    $months_names = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    foreach ($months_names as $m_name) {
        $chart_labels[] = $m_name;
    }
    
    $chart_appointments = array_fill(0, 12, 0);
    $chart_revenue = array_fill(0, 12, 0);
    
    $sql_c = "SELECT MONTH(appointment_date) as m_num, COUNT(*) as cnt FROM hs_appointment a 
              WHERE YEAR(appointment_date) = ? GROUP BY MONTH(appointment_date)";
    list($st_c, $res_c) = db_query_prepared($conn, $sql_c, [$year], 'i');
    while ($r = mysqli_fetch_assoc($res_c)) {
        $chart_appointments[intval($r['m_num']) - 1] = intval($r['cnt']);
    }
    if ($st_c) mysqli_stmt_close($st_c);

    $sql_r = "SELECT MONTH(appointment_date) as m_num, COUNT(*) as cnt FROM hs_appointment a 
              WHERE YEAR(appointment_date) = ? AND appointment_payment_status = 1 GROUP BY MONTH(appointment_date)";
    list($st_r, $res_r) = db_query_prepared($conn, $sql_r, [$year], 'i');
    while ($r = mysqli_fetch_assoc($res_r)) {
        $chart_revenue[intval($r['m_num']) - 1] = intval($r['cnt']) * 500;
    }
    if ($st_r) mysqli_stmt_close($st_r);

} else {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
    
    $days_list = [];
    foreach ($period as $date) {
        $formatted = $date->format('Y-m-d');
        $days_list[$formatted] = 0;
        $chart_labels[] = $date->format('M d');
    }
    
    $chart_appointments = $days_list;
    $chart_revenue = $days_list;
    
    $sql_c = "SELECT appointment_date, COUNT(*) as cnt FROM hs_appointment a 
              WHERE appointment_date >= ? AND appointment_date <= ? GROUP BY appointment_date";
    list($st_c, $res_c) = db_query_prepared($conn, $sql_c, [$start_date, $end_date], 'ss');
    while ($r = mysqli_fetch_assoc($res_c)) {
        if (isset($chart_appointments[$r['appointment_date']])) {
            $chart_appointments[$r['appointment_date']] = intval($r['cnt']);
        }
    }
    if ($st_c) mysqli_stmt_close($st_c);

    $sql_r = "SELECT appointment_date, COUNT(*) as cnt FROM hs_appointment a 
              WHERE appointment_date >= ? AND appointment_date <= ? AND appointment_payment_status = 1 GROUP BY appointment_date";
    list($st_r, $res_r) = db_query_prepared($conn, $sql_r, [$start_date, $end_date], 'ss');
    while ($r = mysqli_fetch_assoc($res_r)) {
        if (isset($chart_revenue[$r['appointment_date']])) {
            $chart_revenue[$r['appointment_date']] = intval($r['cnt']) * 500;
        }
    }
    if ($st_r) mysqli_stmt_close($st_r);
    
    $chart_appointments = array_values($chart_appointments);
    $chart_revenue = array_values($chart_revenue);
}

// Doughnut Chart data (Status Breakdown)
$status_breakdown = ['Pending' => 0, 'Confirmed' => 0, 'Rejected' => 0];
$sql_st = "SELECT appointment_status, COUNT(*) as cnt FROM hs_appointment a $where_sql GROUP BY appointment_status";
list($stmt_st, $res_st) = db_query_prepared($conn, $sql_st, $params, $types);
while ($r = mysqli_fetch_assoc($res_st)) {
    $st = $r['appointment_status'];
    $st_lower = strtolower($st ?? '');
    if ($st_lower === 'accept' || $st_lower === 'confirmed') {
        $status_breakdown['Confirmed'] += intval($r['cnt']);
    } elseif ($st_lower === 'pending' || $st_lower === '') {
        $status_breakdown['Pending'] += intval($r['cnt']);
    } else {
        $status_breakdown['Rejected'] += intval($r['cnt']);
    }
}
if ($stmt_st) mysqli_stmt_close($stmt_st);
?>

<body class="fixed-navbar bg-soft">
    <div class="page-wrapper">
        <!-- START HEADER-->
        <?php include('common/header.php'); ?>
        <!-- END HEADER-->
        <!-- START SIDEBAR-->
        <?php include('common/sidebar.php'); ?>
        <!-- END SIDEBAR-->
        <div class="content-wrapper">
            <!-- START PAGE CONTENT-->
            <div class="page-heading d-flex align-items-center justify-content-between mb-4 mt-2 no-print">
                <div>
                    <h1 class="page-title text-dark fw-bold mb-1">Reports & Analytics</h1>
                    <p class="text-secondary small mb-0">Monitor hospital appointments, track clinic revenue, audit doctors roster and feedback details.</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded"><i class="fa fa-print me-1"></i> Print / PDF</button>
                    <a href="export_reports.php?report=<?= $report_type ?>&timeframe=<?= $timeframe ?>&year=<?= $year ?>&month=<?= $month ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-primary btn-sm rounded"><i class="fa fa-file-excel-o me-1"></i> Export Excel</a>
                </div>
            </div>
            
            <div class="page-content fade-in-up">
                
                <!-- Filters Panel -->
                <div class="ibox mb-4 no-print">
                    <div class="ibox-body">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-semibold text-secondary">Select Report Type</label>
                                <select name="report" class="form-control rounded">
                                    <option value="appointments" <?= $report_type == 'appointments' ? 'selected' : '' ?>>Appointments Report</option>
                                    <option value="revenue" <?= $report_type == 'revenue' ? 'selected' : '' ?>>Revenue Report</option>
                                    <option value="doctors" <?= $report_type == 'doctors' ? 'selected' : '' ?>>Doctors Directory & Activity</option>
                                    <option value="patients" <?= $report_type == 'patients' ? 'selected' : '' ?>>Patients Directory</option>
                                    <option value="payments" <?= $report_type == 'payments' ? 'selected' : '' ?>>Payments Audit Log</option>
                                    <option value="feedback" <?= $report_type == 'feedback' ? 'selected' : '' ?>>Feedbacks Audit</option>
                                </select>
                            </div>
                            
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-semibold text-secondary">Timeframe</label>
                                <select name="timeframe" id="timeframeSelect" class="form-control rounded" onchange="toggleTimeframeFields()">
                                    <option value="all" <?= $timeframe == 'all' ? 'selected' : '' ?>>All Time</option>
                                    <option value="monthly" <?= $timeframe == 'monthly' ? 'selected' : '' ?>>Monthly Report</option>
                                    <option value="yearly" <?= $timeframe == 'yearly' ? 'selected' : '' ?>>Yearly Report</option>
                                    <option value="custom" <?= $timeframe == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                                </select>
                            </div>
                            
                            <!-- Monthly Selectors -->
                            <div class="col-lg-2 col-md-4 timeframe-field timeframe-monthly timeframe-yearly d-none">
                                <label class="form-label fw-semibold text-secondary">Select Year</label>
                                <select name="year" class="form-control rounded">
                                    <?php for ($y = date('Y') - 5; $y <= date('Y'); $y++) { ?>
                                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 timeframe-field timeframe-monthly d-none">
                                <label class="form-label fw-semibold text-secondary">Select Month</label>
                                <select name="month" class="form-control rounded">
                                    <?php for ($m = 1; $m <= 12; $m++) { ?>
                                        <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <!-- Custom Range Inputs -->
                            <div class="col-lg-2 col-md-4 timeframe-field timeframe-custom d-none">
                                <label class="form-label fw-semibold text-secondary">Start Date</label>
                                <input type="date" name="start_date" class="form-control rounded" value="<?= htmlspecialchars($start_date) ?>">
                            </div>
                            <div class="col-lg-2 col-md-4 timeframe-field timeframe-custom d-none">
                                <label class="form-label fw-semibold text-secondary">End Date</label>
                                <input type="date" name="end_date" class="form-control rounded" value="<?= htmlspecialchars($end_date) ?>">
                            </div>
                            
                            <div class="col-lg-2 col-md-4">
                                <button type="submit" class="btn btn-primary w-100 rounded py-2"><i class="fa fa-filter me-1"></i> Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- KPI Metric summaries (Dashboard Analytics) -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="ibox kpi-card appointments p-3 bg-white border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small fw-semibold">Period Appointments</span>
                                    <h3 class="mb-0 fw-bold mt-1 text-dark"><?= $period_appointments ?></h3>
                                </div>
                                <div class="kpi-icon text-primary"><i class="fa fa-calendar-check-o"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="ibox kpi-card revenue p-3 bg-white border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small fw-semibold">Period Revenue</span>
                                    <h3 class="mb-0 fw-bold mt-1 text-success">₹<?= number_format($period_revenue) ?></h3>
                                </div>
                                <div class="kpi-icon text-success"><i class="fa fa-money"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="ibox kpi-card patients p-3 bg-white border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small fw-semibold">Period Patients</span>
                                    <h3 class="mb-0 fw-bold mt-1 text-warning"><?= $period_patients ?></h3>
                                </div>
                                <div class="kpi-icon text-warning"><i class="fa fa-users"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="ibox kpi-card feedback p-3 bg-white border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small fw-semibold">Period Feedbacks</span>
                                    <h3 class="mb-0 fw-bold mt-1 text-danger"><?= $period_feedback ?></h3>
                                </div>
                                <div class="kpi-icon text-danger"><i class="fa fa-comments-o"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Charts -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-3">
                        <div class="ibox bg-white p-3 border-0">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h5 class="fw-bold text-dark mb-0"><i class="fa fa-line-chart text-primary me-2"></i>Appointments & Revenue Growth</h5>
                                <span class="badge bg-light text-secondary rounded">Trend Analysis</span>
                            </div>
                            <div class="chart-container" style="position: relative; height: 300px;">
                                <canvas id="growthTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-3">
                        <div class="ibox bg-white p-3 border-0">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h5 class="fw-bold text-dark mb-0"><i class="fa fa-pie-chart text-success me-2"></i>Consultations Breakdown</h5>
                                <span class="badge bg-light text-secondary rounded">Status</span>
                            </div>
                            <div class="chart-container" style="position: relative; height: 300px;">
                                <canvas id="statusPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Records Table -->
                <div class="ibox bg-white border-0">
                    <div class="ibox-head d-flex align-items-center justify-content-between p-3 border-bottom">
                        <div class="ibox-title fw-bold text-dark mb-0 capitalize-header"><i class="fa fa-list me-2 text-secondary"></i>Detailed Report: <?= esc($report_type) ?></div>
                        <div class="d-flex gap-2 align-items-center no-print">
                            <input type="text" id="reportSearch" class="form-control form-control-sm rounded" placeholder="Quick Search table..." style="width: 220px;">
                        </div>
                    </div>
                    <div class="ibox-body p-3 overflow-auto">
                        <table class="table table-striped table-hover align-middle border mb-0" id="reportDataTable">
                            <?php if ($report_type === 'appointments') { ?>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient Name</th>
                                        <th>Doctor Name</th>
                                        <th>Specialty</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $table_sql = "SELECT a.*, p.patient_fname, p.patient_lname, d.doctor_fname, d.doctor_lname, c.category_title 
                                                  FROM hs_appointment a 
                                                  LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
                                                  LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id 
                                                  LEFT JOIN hs_category c ON a.category_id = c.category_id 
                                                  $where_sql 
                                                  ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                                    list($table_stmt, $table_res) = db_query_prepared($conn, $table_sql, $params, $types);
                                    while ($row = mysqli_fetch_assoc($table_res)) {
                                    ?>
                                        <tr>
                                            <td><?= esc($row['appointment_id']) ?></td>
                                            <td class="fw-semibold"><?= esc($row['patient_fname'] . ' ' . $row['patient_lname']) ?></td>
                                            <td>Dr. <?= esc($row['doctor_fname'] . ' ' . $row['doctor_lname']) ?></td>
                                            <td><?= esc($row['category_title'] ?? 'N/A') ?></td>
                                            <td><?= esc($row['appointment_date']) ?></td>
                                            <td><?= esc(date("g:i A", strtotime($row['appointment_time']))) ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?= $row['appointment_status'] == 'Accept' ? 'bg-success' : ($row['appointment_status'] == 'Cancel' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                                    <?= esc($row['appointment_status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?= $row['appointment_payment_status'] == 1 ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $row['appointment_payment_status'] == 1 ? 'Paid' : 'Unpaid' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } if ($table_stmt) mysqli_stmt_close($table_stmt); ?>
                                </tbody>
                            <?php } elseif ($report_type === 'revenue') { ?>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Billing Date</th>
                                        <th>Patient Name</th>
                                        <th>Doctor Name</th>
                                        <th>Service Category</th>
                                        <th>Gross Amount</th>
                                        <th>Payment Mode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $table_sql = "SELECT a.*, p.patient_fname, p.patient_lname, d.doctor_fname, d.doctor_lname, c.category_title 
                                                  FROM hs_appointment a 
                                                  LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
                                                  LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id 
                                                  LEFT JOIN hs_category c ON a.category_id = c.category_id 
                                                  $rev_where 
                                                  ORDER BY a.appointment_date DESC";
                                    list($table_stmt, $table_res) = db_query_prepared($conn, $table_sql, $params, $types);
                                    while ($row = mysqli_fetch_assoc($table_res)) {
                                    ?>
                                        <tr>
                                            <td><?= esc($row['appointment_id']) ?></td>
                                            <td><?= esc($row['appointment_date']) ?></td>
                                            <td class="fw-semibold"><?= esc($row['patient_fname'] . ' ' . $row['patient_lname']) ?></td>
                                            <td>Dr. <?= esc($row['doctor_fname'] . ' ' . $row['doctor_lname']) ?></td>
                                            <td><?= esc($row['category_title'] ?? 'Consultation') ?></td>
                                            <td class="fw-bold text-success">₹500</td>
                                            <td><span class="badge bg-light text-secondary rounded">Standard Gateway</span></td>
                                        </tr>
                                    <?php } if ($table_stmt) mysqli_stmt_close($table_stmt); ?>
                                </tbody>
                            <?php } elseif ($report_type === 'doctors') { ?>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Doctor Name</th>
                                        <th>Email Address</th>
                                        <th>Phone</th>
                                        <th>Specialty</th>
                                        <th>Joined Date</th>
                                        <th>Total Period Bookings</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $doc_params = [];
                                    $doc_types = "";
                                    $doc_where_sub = "";
                                    if ($timeframe === 'monthly') {
                                        $doc_where_sub = " AND YEAR(appointment_date) = ? AND MONTH(appointment_date) = ?";
                                        $doc_params = [$year, $month];
                                        $doc_types = "ii";
                                    } elseif ($timeframe === 'yearly') {
                                        $doc_where_sub = " AND YEAR(appointment_date) = ?";
                                        $doc_params = [$year];
                                        $doc_types = "i";
                                    } elseif ($timeframe === 'custom' && !empty($start_date) && !empty($end_date)) {
                                        $doc_where_sub = " AND appointment_date >= ? AND appointment_date <= ?";
                                        $doc_params = [$start_date, $end_date];
                                        $doc_types = "ss";
                                    }
                                    
                                    $table_sql = "SELECT d.*, c.category_title, 
                                                         (SELECT COUNT(*) FROM hs_appointment WHERE doctor_id = d.doctor_id $doc_where_sub) as total_appts 
                                                  FROM hs_doctor d 
                                                  LEFT JOIN hs_category c ON d.category_id = c.category_id 
                                                  ORDER BY total_appts DESC";
                                    list($table_stmt, $table_res) = db_query_prepared($conn, $table_sql, $doc_params, $doc_types);
                                    while ($row = mysqli_fetch_assoc($table_res)) {
                                    ?>
                                        <tr>
                                            <td><?= esc($row['doctor_id']) ?></td>
                                            <td class="fw-semibold">Dr. <?= esc($row['doctor_fname'] . ' ' . $row['doctor_lname']) ?></td>
                                            <td><?= esc($row['doctor_email']) ?></td>
                                            <td><?= esc($row['doctor_phone']) ?></td>
                                            <td><?= esc($row['category_title'] ?? 'General Medicine') ?></td>
                                            <td><?= esc($row['doctor_doj']) ?></td>
                                            <td class="fw-bold"><?= esc($row['total_appts']) ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?= $row['status'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $row['status'] == 1 ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } if ($table_stmt) mysqli_stmt_close($table_stmt); ?>
                                </tbody>
                            <?php } elseif ($report_type === 'patients') { ?>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient Name</th>
                                        <th>Email Address</th>
                                        <th>Phone Number</th>
                                        <th>Gender</th>
                                        <th>Registered Date</th>
                                        <th>Total Bookings</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $table_sql = "SELECT p.*, COUNT(a.appointment_id) AS total_bookings 
                                                  FROM hs_patient p 
                                                  LEFT JOIN hs_appointment a ON p.patient_id = a.patient_id 
                                                  GROUP BY p.patient_id 
                                                  ORDER BY total_bookings DESC";
                                    list($table_stmt, $table_res) = db_query_prepared($conn, $table_sql);
                                    while ($row = mysqli_fetch_assoc($table_res)) {
                                    ?>
                                        <tr>
                                            <td><?= esc($row['patient_id']) ?></td>
                                            <td class="fw-semibold"><?= esc($row['patient_fname'] . ' ' . $row['patient_lname']) ?></td>
                                            <td><?= esc($row['patient_email']) ?></td>
                                            <td><?= esc($row['patient_phone']) ?></td>
                                            <td><?= esc($row['patient_gender']) ?></td>
                                            <td><?= esc(date('Y-m-d', strtotime($row['created_date']))) ?></td>
                                            <td class="fw-bold"><?= esc($row['total_bookings']) ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?= $row['status'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $row['status'] == 1 ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } if ($table_stmt) mysqli_stmt_close($table_stmt); ?>
                                </tbody>
                            <?php } elseif ($report_type === 'payments') { ?>
                                <thead>
                                    <tr>
                                        <th>Appointment ID</th>
                                        <th>Billing Date</th>
                                        <th>Patient Name</th>
                                        <th>Consultation Price</th>
                                        <th>Payment Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $table_sql = "SELECT a.*, p.patient_fname, p.patient_lname 
                                                  FROM hs_appointment a 
                                                  LEFT JOIN hs_patient p ON a.patient_id = p.patient_id 
                                                  $where_sql 
                                                  ORDER BY a.appointment_date DESC";
                                    list($table_stmt, $table_res) = db_query_prepared($conn, $table_sql, $params, $types);
                                    while ($row = mysqli_fetch_assoc($table_res)) {
                                    ?>
                                        <tr>
                                            <td><?= esc($row['appointment_id']) ?></td>
                                            <td><?= esc($row['appointment_date']) ?></td>
                                            <td class="fw-semibold"><?= esc($row['patient_fname'] . ' ' . $row['patient_lname']) ?></td>
                                            <td class="fw-semibold">₹500</td>
                                            <td>
                                                <span class="badge rounded-pill <?= $row['appointment_payment_status'] == 1 ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $row['appointment_payment_status'] == 1 ? 'Paid' : 'Unpaid' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } if ($table_stmt) mysqli_stmt_close($table_stmt); ?>
                                </tbody>
                            <?php } elseif ($report_type === 'feedback') { ?>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Submission Date</th>
                                        <th>Patient Name</th>
                                        <th>Feedback Message</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $feed_tbl_params = [];
                                    $feed_tbl_types = "";
                                    $feed_tbl_where = "";
                                    if ($timeframe === 'monthly') {
                                        $feed_tbl_where = " WHERE YEAR(f.created_date) = ? AND MONTH(f.created_date) = ?";
                                        $feed_tbl_params = [$year, $month];
                                        $feed_tbl_types = "ii";
                                    } elseif ($timeframe === 'yearly') {
                                        $feed_tbl_where = " WHERE YEAR(f.created_date) = ?";
                                        $feed_tbl_params = [$year];
                                        $feed_tbl_types = "i";
                                    } elseif ($timeframe === 'custom' && !empty($start_date) && !empty($end_date)) {
                                        $feed_tbl_where = " WHERE f.created_date >= ? AND f.created_date <= ?";
                                        $feed_tbl_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
                                        $feed_tbl_types = "ss";
                                    }
                                    
                                    $table_sql = "SELECT f.*, p.patient_fname, p.patient_lname 
                                                  FROM hs_feedback f 
                                                  LEFT JOIN hs_patient p ON f.patient_id = p.patient_id 
                                                  $feed_tbl_where 
                                                  ORDER BY f.created_date DESC";
                                    list($table_stmt, $table_res) = db_query_prepared($conn, $table_sql, $feed_tbl_params, $feed_tbl_types);
                                    while ($row = mysqli_fetch_assoc($table_res)) {
                                    ?>
                                        <tr>
                                            <td><?= esc($row['feedback_id']) ?></td>
                                            <td><?= esc($row['created_date']) ?></td>
                                            <td class="fw-semibold"><?= esc($row['patient_fname'] . ' ' . $row['patient_lname']) ?></td>
                                            <td><?= esc($row['fd_message']) ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?= $row['status'] == 1 ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $row['status'] == 1 ? 'Published' : 'Hidden' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } if ($table_stmt) mysqli_stmt_close($table_stmt); ?>
                                </tbody>
                            <?php } ?>
                        </table>
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
    
    <!-- Load Chart.js from CDN dynamically -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    function toggleTimeframeFields() {
        const val = document.getElementById('timeframeSelect').value;
        document.querySelectorAll('.timeframe-field').forEach(el => el.classList.add('d-none'));
        
        if (val === 'monthly') {
            document.querySelectorAll('.timeframe-monthly').forEach(el => el.classList.remove('d-none'));
        } else if (val === 'yearly') {
            document.querySelectorAll('.timeframe-yearly').forEach(el => el.classList.remove('d-none'));
        } else if (val === 'custom') {
            document.querySelectorAll('.timeframe-custom').forEach(el => el.classList.remove('d-none'));
        }
    }
    
    // Run toggle on load
    document.addEventListener('DOMContentLoaded', toggleTimeframeFields);
    
    // Quick client-side filter
    document.getElementById('reportSearch').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#reportDataTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // 1. Trend Chart
    const trendCtx = document.getElementById('growthTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Bookings Count',
                    data: <?= json_encode($chart_appointments) ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'Gross Revenue (INR)',
                    data: <?= json_encode($chart_revenue) ?>,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Appointments' },
                    ticks: { stepSize: 1 },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Revenue (₹)' },
                    grid: { drawOnChartArea: false },
                    beginAtZero: true
                }
            }
        }
    });

    // 2. Status Breakdown Pie Chart
    const pieCtx = document.getElementById('statusPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($status_breakdown)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($status_breakdown)) ?>,
                backgroundColor: ['#f1c40f', '#2ecc71', '#e74c3c'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    </script>
    
    <style>
    .ibox {
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
        background: #fff;
    }
    .kpi-card {
        border-left: 4px solid #3498db;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
    }
    .kpi-card.appointments { border-left-color: #3498db; }
    .kpi-card.revenue { border-left-color: #2ecc71; }
    .kpi-card.patients { border-left-color: #f1c40f; }
    .kpi-card.feedback { border-left-color: #e74c3c; }

    .kpi-icon {
        font-size: 32px;
        opacity: 0.2;
    }
    
    .capitalize-header {
        text-transform: capitalize;
    }

    /* Print CSS Styles */
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .page-sidebar, 
        .header, 
        .theme-config, 
        .preloader, 
        .filter-section, 
        .no-print, 
        .ibox-head-btn,
        .page-backdrops,
        .admin-config {
            display: none !important;
        }
        .content-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .ibox {
            box-shadow: none !important;
            border: none !important;
            margin-bottom: 20px !important;
        }
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .table th, .table td {
            border: 1px solid #ddd !important;
            padding: 8px !important;
            font-size: 11px !important;
        }
        .chart-container {
            page-break-inside: avoid !important;
            max-height: 250px !important;
        }
    }
    </style>
</body>
</html>
