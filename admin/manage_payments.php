<?php
/**
 * Admin Payment Management Portal
 */
include_once(__DIR__ . '/../dbcon.php');

// Enforce session check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch admin user role
$role = '';
if (isset($_SESSION['role_id'])) {
    list($role_stmt, $role_result) = db_query_prepared($conn, "SELECT role_title FROM hs_role WHERE role_id = ?", [$_SESSION['role_id']]);
    if ($role_result && mysqli_num_rows($role_result) > 0) {
        $role_row = mysqli_fetch_array($role_result);
        $role = $role_row['role_title'];
    }
    if ($role_stmt) mysqli_stmt_close($role_stmt);
}

if ($role !== 'admin') {
    die("Access Denied. Only administrators are allowed to access this portal.");
}

// Build Search and Filters logic
$where_clauses = [];
$params = [];
$types = "";

// Filter by Status
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
if ($status_filter !== '') {
    if ($status_filter === 'Paid') {
        $where_clauses[] = "(p.payment_status = 'Paid' OR a.appointment_payment_status = 1)";
    } else {
        $where_clauses[] = "(p.payment_status = 'Pending' OR p.payment_status IS NULL OR a.appointment_payment_status = 0)";
    }
}

// Filter by Method
$method_filter = isset($_GET['method']) ? trim($_GET['method']) : '';
if ($method_filter !== '') {
    $where_clauses[] = "(p.payment_method = ? OR a.payment_method = ?)";
    $params[] = $method_filter;
    $params[] = $method_filter;
    $types .= "ss";
}

// Filter by Text Search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search_query !== '') {
    $search_pattern = "%" . $search_query . "%";
    $where_clauses[] = "(pat.patient_fname LIKE ? OR pat.patient_lname LIKE ? OR d.doctor_fname LIKE ? OR d.doctor_lname LIKE ? OR p.transaction_id LIKE ? OR a.appointment_id LIKE ?)";
    for ($i = 0; $i < 6; $i++) {
        $params[] = $search_pattern;
        $types .= "s";
    }
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Primary Query
$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.appointment_status, a.amount AS appt_amount, a.appointment_payment_status,
               pat.patient_fname, pat.patient_lname, pat.patient_email, pat.patient_phone,
               d.doctor_fname, d.doctor_lname,
               p.id AS payment_record_id, p.payment_method AS pm_method, p.transaction_id AS pm_txn, p.amount AS paid_amount, p.currency AS pm_currency, p.payment_status AS pm_status, p.payment_date AS pm_date
        FROM hs_appointment a
        LEFT JOIN hs_patient pat ON a.patient_id = pat.patient_id
        LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id
        LEFT JOIN hs_payments p ON a.appointment_id = p.appointment_id
        $where_sql
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

// Export CSV handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hoperise_payments_' . date('Ymd_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel compatibility
    
    fputcsv($output, [
        'Appointment ID', 
        'Patient Name', 
        'Doctor Name', 
        'Appointment Date', 
        'Amount (INR)', 
        'Payment Method', 
        'Transaction ID', 
        'Payment Status', 
        'Payment Date'
    ]);
    
    list($stmt, $result) = db_query_prepared($conn, $sql, $params, $types);
    while ($row = mysqli_fetch_assoc($result)) {
        $pm = $row['pm_method'] ?: ($row['payment_method'] ?: 'Not Selected');
        $status = ($row['appointment_payment_status'] == 1 || $row['pm_status'] === 'Paid') ? 'Paid' : 'Pending';
        $txn = $row['pm_txn'] ?: ($row['transaction_id'] ?: 'N/A');
        $pdate = $row['pm_date'] ?: 'N/A';
        
        fputcsv($output, [
            $row['appointment_id'],
            $row['patient_fname'] . ' ' . $row['patient_lname'],
            'Dr. ' . $row['doctor_fname'] . ' ' . $row['doctor_lname'],
            $row['appointment_date'],
            $row['appt_amount'] ?: 590.00,
            $pm,
            $txn,
            $status,
            $pdate
        ]);
    }
    if ($stmt) mysqli_stmt_close($stmt);
    fclose($output);
    exit();
}

// Load admin layouts CSS
include('common/css.php');
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
        <h1 class="page-title"><i class="fa fa-credit-card me-2"></i>Payment Management</h1>
      </div>
      
      <div class="page-content fade-in-up">
        
        <!-- Filters Card -->
        <div class="ibox mb-4">
          <div class="ibox-body">
            <form method="GET" action="" id="payment-filter-form">
              <div class="row align-items-end g-3">
                <div class="col-md-4">
                  <label class="form-label font-weight-bold">Search</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Patient, Doctor, Txn ID or Appt ID..." value="<?= htmlspecialchars($search_query) ?>">
                  </div>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label font-weight-bold">Payment Status</label>
                  <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Paid" <?= $status_filter === 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  </select>
                </div>
                
                <div class="col-md-3">
                  <label class="form-label font-weight-bold">Payment Method</label>
                  <select name="method" class="form-select" onchange="this.form.submit()">
                    <option value="">All Methods</option>
                    <option value="UPI / QR" <?= $method_filter === 'UPI / QR' ? 'selected' : '' ?>>UPI / QR</option>
                    <option value="Cash on Visit" <?= $method_filter === 'Cash on Visit' ? 'selected' : '' ?>>Cash on Visit</option>
                    <option value="PayPal" <?= $method_filter === 'PayPal' ? 'selected' : '' ?>>PayPal Sandbox</option>
                  </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                  <button type="submit" class="btn btn-primary w-100"><i class="fa fa-filter mr-1"></i>Filter</button>
                  <a href="manage_payments.php" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Payments Table Card -->
        <div class="ibox">
          <div class="ibox-head d-flex justify-content-between align-items-center py-3">
            <div class="ibox-title">Payment Transaction Directory</div>
            <div>
              <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success btn-sm font-weight-bold px-3">
                <i class="fa fa-file-excel-o mr-1"></i>Export CSV
              </a>
            </div>
          </div>
          <div class="ibox-body">
            <div class="table-responsive">
              <table class="table table-striped table-bordered table-hover" id="payments-table">
                <thead>
                  <tr>
                    <th>Appt ID</th>
                    <th>Patient Details</th>
                    <th>Consulting Doctor</th>
                    <th>Billing Fee</th>
                    <th>Payment Method</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  list($stmt, $result) = db_query_prepared($conn, $sql, $params, $types);
                  
                  if (mysqli_num_rows($result) === 0) {
                      echo "<tr><td colspan='9' class='text-center py-4 text-secondary'><i class='fa fa-credit-card fa-2x d-block mb-2'></i>No payment records found.</td></tr>";
                  } else {
                      while ($row = mysqli_fetch_assoc($result)) {
                          $pm = $row['pm_method'] ?: ($row['payment_method'] ?: 'Not Selected');
                          $status = ($row['appointment_payment_status'] == 1 || $row['pm_status'] === 'Paid') ? 'Paid' : 'Pending';
                          $txn = $row['pm_txn'] ?: ($row['transaction_id'] ?: 'N/A');
                          $pdate = $row['pm_date'] ? date('d M Y, h:i A', strtotime($row['pm_date'])) : 'N/A';
                          
                          // Style badge for payment method
                          $method_badge = 'badge-secondary';
                          if ($pm === 'UPI / QR') $method_badge = 'badge-info';
                          elseif ($pm === 'PayPal') $method_badge = 'badge-primary';
                          elseif ($pm === 'Cash on Visit') $method_badge = 'badge-warning';
                          ?>
                          <tr id="row-<?= $row['appointment_id'] ?>">
                            <td class="font-weight-bold text-teal">#INV-10<?= $row['appointment_id'] ?></td>
                            <td>
                              <strong class="text-dark"><?= htmlspecialchars($row['patient_fname'] . ' ' . $row['patient_lname']) ?></strong>
                              <small class="d-block text-muted"><?= htmlspecialchars($row['patient_email']) ?></small>
                            </td>
                            <td>Dr. <?= htmlspecialchars($row['doctor_fname'] . ' ' . $row['doctor_lname']) ?></td>
                            <td>₹<?= number_format($row['appt_amount'] ?: 590.00, 2) ?></td>
                            <td>
                              <span class="badge <?= $method_badge ?> px-2 py-1"><?= htmlspecialchars($pm) ?></span>
                            </td>
                            <td id="txn-cell-<?= $row['appointment_id'] ?>" class="text-monospace"><?= htmlspecialchars($txn) ?></td>
                            <td>
                              <select class="form-select form-select-sm select-payment-status font-weight-bold" data-id="<?= $row['appointment_id'] ?>" data-method="<?= htmlspecialchars($pm) ?>">
                                <option value="Pending" class="text-warning" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Paid" class="text-success" <?= $status === 'Paid' ? 'selected' : '' ?>>Paid</option>
                              </select>
                            </td>
                            <td id="date-cell-<?= $row['appointment_id'] ?>"><?= $pdate ?></td>
                            <td class="text-center">
                              <div class="d-flex justify-content-center gap-2">
                                <?php if ($status === 'Pending'): ?>
                                  <button class="btn btn-success btn-xs btn-verify-payment" data-id="<?= $row['appointment_id'] ?>" data-method="<?= htmlspecialchars($pm) ?>">
                                    <i class="fa fa-check"></i> Verify
                                  </button>
                                <?php endif; ?>
                                <a href="../invoice.php?appointment_id=<?= $row['appointment_id'] ?>" target="_blank" class="btn btn-outline-info btn-xs">
                                  <i class="fa fa-file-text-o"></i> Receipt
                                </a>
                              </div>
                            </td>
                          </tr>
                          <?php
                      }
                  }
                  if ($stmt) mysqli_stmt_close($stmt);
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer layout -->
      <?php include('common/footer.php'); ?>
    </div>
  </div>

  <!-- Theme config & scripts -->
  <?php include('common/configue_panel.php'); ?>
  <?php include('common/preloader.php'); ?>
  <?php include('common/main_footer.php'); ?>

  <script>
    $(document).ready(function() {
      // Initialize DataTables
      var table = $('#payments-table').DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, "desc"]],
        columnDefs: [
          { orderable: false, targets: 8 }
        ]
      });

      // Handle dropdown status changes via AJAX
      $('#payments-table').on('change', '.select-payment-status', function() {
        var dropdown = $(this);
        var apptId = dropdown.data('id');
        var method = dropdown.data('method');
        var newStatus = dropdown.val();

        updatePaymentStatus(apptId, method, newStatus, dropdown);
      });

      // Handle Quick Verify Button
      $('#payments-table').on('click', '.btn-verify-payment', function() {
        var btn = $(this);
        var apptId = btn.data('id');
        var method = btn.data('method');

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        
        updatePaymentStatus(apptId, method, 'Paid', null, function(success, data) {
          if (success) {
            // Find and update row status dropdown
            var select = $('#row-' + apptId + ' .select-payment-status');
            select.val('Paid');
            // Remove verify button
            btn.fadeOut(300, function() { btn.remove(); });
          } else {
            btn.prop('disabled', false).html('<i class="fa fa-check"></i> Verify');
          }
        });
      });

      function updatePaymentStatus(apptId, method, status, dropdownElement, callback) {
        $.ajax({
          url: 'api_update_payment.php',
          type: 'POST',
          dataType: 'json',
          data: {
            appointment_id: apptId,
            payment_status: status,
            payment_method: method
          },
          success: function(response) {
            if (response.success) {
              // Update cells in UI
              $('#txn-cell-' + apptId).text(response.transaction_id);
              
              var now = new Date();
              var formattedDate = now.getDate() + ' ' + 
                                  now.toLocaleString('en-US', { month: 'short' }) + ' ' + 
                                  now.getFullYear() + ', ' + 
                                  now.toLocaleString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
              $('#date-cell-' + apptId).text(status === 'Paid' ? formattedDate : 'N/A');

              if (dropdownElement) {
                dropdownElement.addClass('border-success');
                setTimeout(() => dropdownElement.removeClass('border-success'), 1500);
              }

              if (callback) callback(true, response);
            } else {
              alert('Error: ' + response.message);
              if (dropdownElement) {
                dropdownElement.val(status === 'Paid' ? 'Pending' : 'Paid');
              }
              if (callback) callback(false);
            }
          },
          error: function() {
            alert('Failed to connect to the server. Action aborted.');
            if (dropdownElement) {
              dropdownElement.val(status === 'Paid' ? 'Pending' : 'Paid');
            }
            if (callback) callback(false);
          }
        });
      }
    });
  </script>
</body>
</html>
