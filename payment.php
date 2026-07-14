<?php
include_once("dbcon.php");

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;

// Fetch the appointment details securely
$stmt = mysqli_prepare($conn, "SELECT a.*, d.doctor_fname, d.doctor_lname, c.category_title, s.service_title 
                               FROM hs_appointment a
                               LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id
                               LEFT JOIN hs_category c ON a.category_id = c.category_id
                               LEFT JOIN hs_service s ON a.service_id = s.service_id
                               WHERE a.appointment_id = ?");
$appointment = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $appointment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$appointment) {
    header("Location: my_appointment.php");
    exit();
}

$is_paid = ($appointment['appointment_payment_status'] == 1);

// Standard consultation charge if not specified
$fee_amount = 500; 

// Check if we should update payment status (for cash/UPI simulation)
if (isset($_POST['pay_method'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $method = $_POST['pay_method'];
    
    // Set payment status to 1 for successful payment
    $update_stmt = mysqli_prepare($conn, "UPDATE hs_appointment SET appointment_payment_status = 1 WHERE appointment_id = ?");
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);
        $success = mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        if ($success) {
            echo "<script>alert('Payment Registered Successfully using " . htmlspecialchars($method) . "!'); window.location.href='payment.php?appointment_id=" . $appointment_id . "';</script>";
            exit();
        }
    }
}

// Load legacy config safely if file exists
if (file_exists('paypal_config.php')) {
    include_once 'paypal_config.php';
}
if (!defined('PAYPAL_URL')) {
    define('PAYPAL_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr');
}
if (!defined('PAYPAL_ID')) {
    define('PAYPAL_ID', 'merchant@hoperise.com');
}
if (!defined('PAYPAL_CURRENCY')) {
    define('PAYPAL_CURRENCY', 'USD');
}

include("includes/main_header.php");
?>
<body>
<div class="page-wrapper">
  <!-- Headers -->
  <?php include("includes/top_header.php"); ?>
  <?php include("includes/upper_header.php"); ?>
  <?php include("includes/navbar.php"); ?>

  <!-- Page Title -->
  <section class="py-5 bg-primary-light text-primary position-relative overflow-hidden mb-5">
    <div class="container py-4 position-relative z-1 text-center">
      <h1 class="display-5 fw-bold text-dark mb-2">Checkout & Payment</h1>
      <p class="text-secondary mb-0">Select your preferred payment method to finalize your consultation reservation.</p>
    </div>
  </section>

  <!-- Checkout Workspace -->
  <section class="pb-5">
    <div class="container">
      <?php if ($is_paid): ?>
        <div class="row justify-content-center">
          <div class="col-lg-6 col-md-8 text-center">
            <div class="glass-card p-5 shadow-lg border-0 rounded-4 my-4">
              <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle p-4 mb-4 shadow" style="width: 80px; height: 80px;">
                <i class="bi bi-check-lg fs-1"></i>
              </div>
              <h2 class="fw-bold text-dark mb-2">Payment Completed!</h2>
              <p class="text-secondary mb-4">Your consultation payment has been successfully processed and verified.</p>
              
              <div class="card bg-light border-0 p-4 mb-4 text-start rounded-3">
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-secondary">Patient Profile ID:</span>
                  <span class="fw-semibold text-dark">#<?= htmlspecialchars($appointment['patient_id']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-secondary">Doctor Assigned:</span>
                  <span class="fw-semibold text-dark">Dr. <?= htmlspecialchars($appointment['doctor_fname'] . " " . $appointment['doctor_lname']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-secondary">Service:</span>
                  <span class="fw-semibold text-dark"><?= htmlspecialchars($appointment['service_title']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-secondary">Appointment Date:</span>
                  <span class="fw-semibold text-dark"><?= htmlspecialchars($appointment['appointment_date'] . " at " . $appointment['appointment_time']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-top pt-3 mt-2">
                  <span class="fw-bold text-dark">Amount Paid (Incl. GST):</span>
                  <span class="fw-bold text-success fs-5">₹<?= number_format($fee_amount * 1.18, 2) ?></span>
                </div>
              </div>

              <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="invoice.php?appointment_id=<?= $appointment_id ?>" target="_blank" class="btn btn-premium px-4 py-3"><i class="bi bi-file-earmark-pdf-fill me-2"></i>Download Invoice (PDF)</a>
                <a href="my_appointment.php" class="btn btn-outline-secondary px-4 py-3"><i class="bi bi-house-door-fill me-2"></i>Go to Dashboard</a>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="row g-5">
          <!-- Order Summary Card -->
          <div class="col-lg-5">
            <div class="glass-card p-4 mb-4">
              <h4 class="fw-bold text-dark mb-4"><i class="bi bi-receipt text-primary me-2"></i>Consultation Invoice</h4>
              
              <div class="d-flex flex-column gap-3 mb-4">
                <div class="d-flex justify-content-between border-bottom pb-2">
                  <span class="text-secondary">Patient Profile ID:</span>
                  <span class="fw-semibold text-dark">#<?= htmlspecialchars($appointment['patient_id']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2">
                  <span class="text-secondary">Doctor:</span>
                  <span class="fw-semibold text-dark">Dr. <?= htmlspecialchars($appointment['doctor_fname'] . " " . $appointment['doctor_lname']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2">
                  <span class="text-secondary">Specialty:</span>
                  <span class="fw-semibold text-dark"><?= htmlspecialchars($appointment['category_title']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2">
                  <span class="text-secondary">Service:</span>
                  <span class="fw-semibold text-dark"><?= htmlspecialchars($appointment['service_title']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2">
                  <span class="text-secondary">Schedule:</span>
                  <span class="fw-semibold text-dark"><?= htmlspecialchars($appointment['appointment_date'] . " at " . $appointment['appointment_time']) ?></span>
                </div>
              </div>

              <div class="d-flex flex-column gap-2 mb-4 bg-light p-3 rounded">
                <div class="d-flex justify-content-between">
                  <span class="text-secondary small">Consultation Fee:</span>
                  <span class="fw-semibold text-dark">₹<?= number_format($fee_amount, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2">
                  <span class="text-secondary small">GST (18%):</span>
                  <span class="fw-semibold text-dark">₹<?= number_format($fee_amount * 0.18, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2">
                  <span class="fw-bold text-primary">Total Payable:</span>
                  <span class="fs-4 fw-extrabold text-primary">₹<?= number_format($fee_amount * 1.18, 2) ?></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Modes Selection Card -->
          <div class="col-lg-7">
            <div class="glass-card p-5">
              <h4 class="fw-bold text-dark mb-4"><i class="bi bi-wallet2 text-primary me-2"></i>Select Payment Method</h4>

              <!-- Bootstrap Nav Tabs for methods -->
              <ul class="nav nav-pills mb-4 gap-2" id="paymentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active rounded-pill px-4 py-2" id="upi-tab" data-bs-toggle="tab" data-bs-target="#upi-pane" type="button" role="tab"><i class="bi bi-qr-code-scan me-2"></i>UPI / QR</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link rounded-pill px-4 py-2" id="cash-tab" data-bs-toggle="tab" data-bs-target="#cash-pane" type="button" role="tab"><i class="bi bi-cash-coin me-2"></i>Cash on Visit</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link rounded-pill px-4 py-2" id="paypal-tab" data-bs-toggle="tab" data-bs-target="#paypal-pane" type="button" role="tab"><i class="bi bi-paypal me-2"></i>PayPal (Legacy)</button>
                </li>
              </ul>

              <div class="tab-content" id="paymentTabsContent">
                <!-- UPI Payment Pane -->
                <div class="tab-pane fade show active" id="upi-pane" role="tabpanel" aria-labelledby="upi-tab">
                  <div class="text-center py-3">
                    <p class="text-secondary small mb-4">Scan the UPI QR code using BHIM, Google Pay, PhonePe or Paytm to make the payment.</p>
                    
                    <!-- Dynamic Mock UPI QR code -->
                    <div class="d-inline-block bg-white p-3 border rounded shadow-sm mb-4">
                      <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=hoperise@upi%26pn=Hope%20Rise%20Medical%26am=<?= $fee_amount * 1.18 ?>%26cu=INR" alt="UPI QR Code" style="width: 180px; height: 180px;">
                    </div>
                    
                    <form action="" method="post">
                      <?php echo get_csrf_input_html(); ?>
                      <input type="hidden" name="pay_method" value="UPI">
                      <button type="submit" class="btn btn-premium w-100 py-3">I Have Scanned & Paid</button>
                    </form>
                  </div>
                </div>

                <!-- Cash on Visit Pane -->
                <div class="tab-pane fade" id="cash-pane" role="tabpanel" aria-labelledby="cash-tab">
                  <div class="py-3">
                    <p class="text-secondary small mb-4">Select this option if you prefer to settle your consultation fees directly at the clinic reception desk when you arrive.</p>
                    
                    <form action="" method="post">
                      <?php echo get_csrf_input_html(); ?>
                      <input type="hidden" name="pay_method" value="Cash on Visit">
                      <button type="submit" class="btn btn-premium w-100 py-3"><i class="bi bi-check2-circle me-2"></i>Confirm Cash Payment at Clinic</button>
                    </form>
                  </div>
                </div>

                <!-- PayPal Legacy Pane -->
                <div class="tab-pane fade" id="paypal-pane" role="tabpanel" aria-labelledby="paypal-tab">
                  <div class="py-3">
                    <p class="text-secondary small mb-4">You will be redirected securely to the PayPal sandbox portal to authorize the transaction.</p>
                    
                    <!-- Legacy PayPal Form -->
                    <form action="<?php echo PAYPAL_URL; ?>" method="post" target="_blank">
                      <input type="hidden" name="business" value="<?php echo PAYPAL_ID; ?>">
                      <input type="hidden" name="cmd" value="_xclick">
                      <input type="hidden" name="item_name" value="Doctor Consultation #<?= $appointment['doctor_id']; ?>">
                      <input type="hidden" name="item_number" value="<?= $appointment['appointment_id']; ?>">
                      <input type="hidden" name="amount" value="<?= number_format(($fee_amount * 1.18) / 80, 2); // Convert to mock USD ?>">
                      <input type="hidden" name="id" value="<?= $appointment['patient_id']; ?>">
                      <input type="hidden" name="currency_code" value="USD">
                      <input type='hidden' name='cancel_return' value='<?= PAYPAL_CANCEL_URL . "?appointment_id=" . $appointment['appointment_id']; ?>'>
                      <input type='hidden' name='return' value='<?= PAYPAL_RETURN_URL . "?appointment_id=" . $appointment['appointment_id']; ?>'>
                      
                      <button type="submit" class="btn btn-premium w-100 py-3"><i class="bi bi-paypal me-2"></i>Proceed with PayPal Redirect</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Clean Checkout Footer -->
  <footer class="py-4 bg-dark border-top border-secondary mt-5">
    <div class="container text-center">
      <div class="mb-2">
        <span class="fw-bold text-primary"><i class="bi bi-heart-pulse-fill me-2"></i>Hope Rise Medical</span>
      </div>
      <p class="text-light-emphasis small mb-0">© <?= date('Y') ?> Hope Rise Medical. All rights reserved. All transactions are securely encrypted.</p>
      <div class="mt-2 small text-light-emphasis">
        <a href="about.php" class="text-light-emphasis text-decoration-none mx-2 hover-primary">About Us</a> | 
        <a href="contact.php" class="text-light-emphasis text-decoration-none mx-2 hover-primary">Contact</a> | 
        <a href="service.php" class="text-light-emphasis text-decoration-none mx-2 hover-primary">Services</a>
      </div>
    </div>
  </footer>
</div>

<!-- JS imports -->
<?php include("includes/js.php"); ?>

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