<?php
/**
 * Payment Module Checkout Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payment_functions.php';

// Validate appointment ID
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
if ($appointment_id <= 0) {
    $_SESSION['toast_error'] = 'Invalid appointment reference';
    header("Location: ../my_appointment.php");
    exit();
}

if (!isset($_SESSION['patient_id'])) {
    $_SESSION['toast_error'] = 'Your session has expired. Please log in again.';
    header("Location: ../login.php");
    exit();
}
$patient_id = $_SESSION['patient_id'];

// Fetch appointment detail
$appointment = get_payment_appointment_details($conn, $appointment_id, $patient_id);
if (!$appointment) {
    $_SESSION['toast_error'] = 'Appointment not found or unauthorized access';
    header("Location: ../my_appointment.php");
    exit();
}

// Redirect if already paid
if ($appointment['appointment_payment_status'] == 1) {
    $_SESSION['toast_success'] = 'This appointment has already been paid.';
    header("Location: ../my_appointment.php");
    exit();
}

// Retrieve details
$patient_fname = htmlspecialchars($appointment['patient_fname']);
$patient_lname = htmlspecialchars($appointment['patient_lname']);
$patient_email = htmlspecialchars($appointment['patient_email']);
$patient_phone = htmlspecialchars($appointment['patient_phone']);
$patient_address = htmlspecialchars($appointment['patient_address'] ?? 'Not Provided');

$doctor_fname = htmlspecialchars($appointment['doctor_fname']);
$doctor_lname = htmlspecialchars($appointment['doctor_lname']);
$doctor_email = htmlspecialchars($appointment['doctor_email']);
$doctor_phone = htmlspecialchars($appointment['doctor_phone'] ?? 'Not Provided');

$category_title = htmlspecialchars($appointment['category_title']);
$service_title = htmlspecialchars($appointment['service_title']);
$appointment_date = date('d M Y', strtotime($appointment['appointment_date']));
$appointment_time = date('h:i A', strtotime($appointment['appointment_time']));

// Fee breakdown calculations
$total_amount = floatval($appointment['amount'] ?? 590.00);
if ($total_amount <= 0) {
    $total_amount = 590.00;
}
$base_fee = round($total_amount / 1.18, 2);
$gst_amount = round($total_amount - $base_fee, 2);

// Generate UPI payment payload URI
$upi_payload = "upi://pay?pa=" . rawurlencode(UPI_ID) . 
               "&pn=" . rawurlencode(UPI_MERCHANT_NAME) . 
               "&am=" . rawurlencode(number_format($total_amount, 2, '.', '')) . 
               "&cu=INR" . 
               "&tn=" . rawurlencode("HRM Appt #" . $appointment_id);

// Generate QR Code URL via public API
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_payload);

// Retrieve company settings for dynamic logo and brand name
$company_logo = !empty($GLOBALS['company_settings']['logo']) ? '../uploads/company/' . $GLOBALS['company_settings']['logo'] : '../images/img.png';
$hospital_name = htmlspecialchars($GLOBALS['company_settings']['hospital_name'] ?? 'Hope Rise Medical');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Checkout | Hope Rise Medical</title>
  
  <!-- Font Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  
  <!-- Custom Payment CSS -->
  <link rel="stylesheet" href="assets/css/payment.css?v=<?= time() ?>">
  <link href="../css/style.css?v=<?= time(); ?>" rel="stylesheet">
  <link href="../css/custom.css?v=<?= time(); ?>" rel="stylesheet">
  
  <style>
    /* Compact viewport scaling to fit elements cleanly on one screen */
    .glass-navbar {
      padding-top: 8px !important;
      padding-bottom: 8px !important;
    }
    .payment-hero {
      padding-top: 1rem !important;
      padding-bottom: 1rem !important;
      margin-bottom: 1rem !important;
    }
    .page-heading {
      font-size: 1.6rem !important;
    }
    .page-subtitle {
      font-size: 0.9rem !important;
    }
    .payment-card {
      padding: 24px 28px !important;
      border-radius: 16px !important;
    }
    .invoice-header-premium {
      margin-bottom: 10px !important;
      padding-bottom: 6px !important;
    }
    .invoice-details-list {
      gap: 1px !important;
    }
    .invoice-detail-row {
      font-size: 0.82rem !important;
      padding-bottom: 3px !important;
      margin-bottom: 3px !important;
    }
    .pricing-table-premium {
      margin-top: 10px !important;
      padding-top: 10px !important;
    }
    .pricing-row-premium {
      font-size: 0.82rem !important;
      margin-bottom: 3px !important;
    }
    .total-payable-row {
      padding: 6px 10px !important;
      margin-top: 6px !important;
    }
    .method-card {
      padding: 12px 8px !important;
      border-radius: 12px !important;
    }
    .method-icon {
      font-size: 1.5rem !important;
      margin-bottom: 2px !important;
    }
    .method-title {
      font-size: 0.78rem !important;
    }
    .payment-details-container {
      padding: 12px !important;
      margin-top: 12px !important;
      border-radius: 10px !important;
    }
    .qr-container {
      padding: 6px !important;
      max-width: 120px !important;
    }
    .qr-code-img {
      max-height: 90px !important;
    }
    .btn-primary-payment {
      padding: 10px 20px !important;
      font-size: 0.9rem !important;
    }
    @media (min-width: 992px) {
      .payment-wrapper {
        overflow-y: auto;
      }
      .payment-card {
        height: 100%;
      }
    }
  </style>
</head>
<body class="payment-wrapper">

  <nav class="navbar navbar-expand-lg sticky-top glass-navbar py-3 no-print">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="../index.php">
        <?php if (!empty($GLOBALS['company_settings']['logo'])): ?>
          <img src="../uploads/company/<?= htmlspecialchars($GLOBALS['company_settings']['logo']) ?>" alt="<?= $hospital_name ?> Logo" height="40" class="d-inline-block align-top">
        <?php else: ?>
          <img src="../images/img.png" alt="Hope Rise Medical Logo" height="40" class="d-inline-block align-top">
        <?php endif; ?>
        <span class="fs-4 fw-bold text-primary brand-name"><?= $hospital_name ?></span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-2 align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link px-3" href="../index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3" href="../about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3" href="../service.php">Service</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3" href="../team.php">Doctors</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3" href="../add_appointment.php">Appointment</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3" href="../contact.php">Contact</a>
          </li>
          <?php if(isset($_SESSION['patient_id'])): ?>
            <li class="nav-item">
              <a class="nav-link px-3" href="../feedback.php">Feedback</a>
            </li>
            <li class="nav-item">
              <a class="nav-link px-3" href="../my_appointment.php">Profile</a>
            </li>
            <li class="nav-item ms-lg-2">
              <a class="btn btn-premium btn-sm" href="../logout.php">Logout</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link px-3" href="../patient_reg.php">Register</a>
            </li>
            <li class="nav-item ms-lg-2">
              <a class="btn btn-premium btn-sm" href="../login.php">Login</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Light Blue Gradient Hero Section -->
  <div class="payment-hero text-center py-5">
    <div class="container">
      <h1 class="fw-bold mb-2 text-dark page-heading">Checkout &amp; Payment</h1>
      <p class="text-secondary page-subtitle mb-0">Please complete your payment preference to confirm your clinical consultation slot</p>
    </div>
  </div>

  <main class="container pb-5">
    <div class="row g-4 justify-content-center">
      <div class="col-lg-12">
        <div class="row g-4 align-items-stretch">
          
          <!-- Left Column: Consultation Invoice -->
          <div class="col-lg-6 col-md-12 d-flex">
            <div class="payment-card w-100 p-4 p-md-5 d-flex flex-column justify-content-between">
              
              <div>
                <!-- Professional Invoice Header -->
                <div class="invoice-header-premium mb-4 pb-3 border-bottom">
                  <div class="row align-items-center">
                    <div class="col-6">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="<?= $company_logo ?>" alt="<?= $hospital_name ?> Logo" class="invoice-hospital-logo" style="height: 38px; width: auto; object-fit: contain;">
                        <span class="fs-5 fw-bold text-dark"><?= $hospital_name ?></span>
                      </div>
                      <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill small-badge">
                        <i class="bi bi-shield-fill-check me-1"></i>Secure Checkout
                      </span>
                    </div>
                    <div class="col-6 text-end">
                      <h4 class="fw-bold text-primary mb-1">Consultation Invoice</h4>
                      <div class="invoice-meta-item small text-secondary">
                        Invoice No: <strong class="text-dark">#INV-10<?= $appointment_id ?></strong>
                      </div>
                      <div class="invoice-meta-item small text-secondary">
                        Invoice Date: <strong class="text-dark"><?= date('d M Y', strtotime($appointment['created_date'] ?? 'now')) ?></strong>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Invoice Details List -->
                <div class="invoice-details-list">
                  <div class="invoice-detail-row py-2 d-flex justify-content-between border-bottom">
                    <span class="detail-label text-secondary">Patient ID</span>
                    <span class="detail-value fw-semibold text-dark">#<?= $patient_id ?></span>
                  </div>
                  <div class="invoice-detail-row py-2 d-flex justify-content-between border-bottom">
                    <span class="detail-label text-secondary">Patient Name</span>
                    <span class="detail-value fw-semibold text-dark"><?= $patient_fname . ' ' . $patient_lname ?></span>
                  </div>
                  <div class="invoice-detail-row py-2 d-flex justify-content-between border-bottom">
                    <span class="detail-label text-secondary">Doctor</span>
                    <span class="detail-value fw-semibold text-dark">Dr. <?= $doctor_fname . ' ' . $doctor_lname ?></span>
                  </div>
                  <div class="invoice-detail-row py-2 d-flex justify-content-between border-bottom">
                    <span class="detail-label text-secondary">Department</span>
                    <span class="detail-value fw-semibold text-dark"><?= $category_title ?></span>
                  </div>
                  <div class="invoice-detail-row py-2 d-flex justify-content-between border-bottom">
                    <span class="detail-label text-secondary">Service</span>
                    <span class="detail-value fw-semibold text-dark"><?= $service_title ?></span>
                  </div>
                  <div class="invoice-detail-row py-2 d-flex justify-content-between border-bottom">
                    <span class="detail-label text-secondary">Appointment Date</span>
                    <span class="detail-value fw-semibold text-dark"><?= $appointment_date ?></span>
                  </div>
                  <div class="invoice-detail-row py-2 d-flex justify-content-between border-bottom">
                    <span class="detail-label text-secondary">Appointment Time</span>
                    <span class="detail-value fw-semibold text-dark"><?= $appointment_time ?></span>
                  </div>
                </div>
              </div>

              <!-- Pricing breakdown -->
              <div class="pricing-table-premium mt-4 pt-3 border-top">
                <div class="pricing-row-premium d-flex justify-content-between py-2 border-bottom">
                  <span class="text-secondary">Consultation Fee</span>
                  <span class="fw-semibold text-dark">₹<?= number_format($base_fee, 2) ?></span>
                </div>
                <div class="pricing-row-premium d-flex justify-content-between py-2 border-bottom">
                  <span class="text-secondary">GST (18%)</span>
                  <span class="fw-semibold text-dark">₹<?= number_format($gst_amount, 2) ?></span>
                </div>
                
                <!-- Highlight Total Amount with different background and larger bold font -->
                <div class="total-payable-row d-flex justify-content-between align-items-center py-3 px-3 mt-3 rounded">
                  <span class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.5px;">Total Payable</span>
                  <span class="fs-4 fw-bold text-primary">₹<?= number_format($total_amount, 2) ?></span>
                </div>
                
                <!-- Green Secure Payment badge at bottom -->
                <div class="text-center mt-3">
                  <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-shield-fill-check me-1"></i>Secure Payment Guaranteed
                  </span>
                </div>
              </div>

            </div>
          </div>

          <!-- Right Column: Select Payment Method -->
          <div class="col-lg-6 col-md-12 d-flex">
            <div class="payment-card w-100 p-4 p-md-5 d-flex flex-column justify-content-between">
              
              <form id="payment-form" method="POST" class="d-flex flex-column justify-content-between h-100 w-100">
                <input type="hidden" id="csrf-token" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" id="appointment-id" name="appointment_id" value="<?= $appointment_id ?>">
                <input type="hidden" id="selected-method" name="pay_method" value="">

                <div>
                  <h3 class="fw-bold text-primary mb-2">Select Payment Method</h3>
                  <p class="text-secondary mb-4 small">Choose a payment option below to finalize your booking and secure your slot.</p>

                  <!-- Modern Equal-Size Cards for Payment Options -->
                  <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                      <div class="method-card text-center" data-method="UPI / QR">
                        <i class="bi bi-qr-code-scan d-block mb-2 fs-3 method-icon"></i>
                        <span class="method-title">UPI / QR</span>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="method-card text-center" data-method="PayPal">
                        <i class="bi bi-paypal d-block mb-2 fs-3 method-icon"></i>
                        <span class="method-title">PayPal</span>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="method-card text-center" data-method="Cash on Visit">
                        <i class="bi bi-cash-stack d-block mb-2 fs-3 method-icon"></i>
                        <span class="method-title">Cash on Visit</span>
                      </div>
                    </div>
                  </div>

                  <!-- Dynamic details section -->
                  <div class="payment-details-container p-4 rounded-3 mb-4 bg-light">
                    
                    <!-- UPI / QR Code Box -->
                    <div class="method-details" id="details-upi-or-qr" style="display: none;">
                      <div class="text-center">
                        <div class="qr-container mb-3">
                          <img src="<?= $qr_code_url ?>" alt="UPI QR Code" class="qr-code-img img-fluid">
                        </div>
                        <p class="small text-muted mb-2">
                          UPI ID: <strong class="text-dark"><?= htmlspecialchars(UPI_ID) ?></strong>
                        </p>
                        <div class="alert alert-light border text-start small mb-0 p-3 shadow-sm rounded-3">
                          <span class="fw-bold d-block mb-2 text-primary"><i class="bi bi-info-circle me-1"></i> How to Pay:</span>
                          <ol class="ps-3 mb-0 text-secondary small-list">
                            <li>Open your UPI app (GPay, PhonePe, Paytm, etc.).</li>
                            <li>Scan the QR code displayed above.</li>
                            <li>Verify the payee name (<strong><?= htmlspecialchars(UPI_MERCHANT_NAME) ?></strong>).</li>
                            <li>Enter your UPI PIN to complete the transfer.</li>
                          </ol>
                        </div>
                      </div>
                    </div>

                    <!-- PayPal Box -->
                    <div class="method-details" id="details-paypal" style="display: none;">
                      <div class="alert alert-primary-subtle border-0 rounded-3 p-3 mb-0 shadow-sm">
                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-paypal me-2"></i>PayPal Checkout</h6>
                        <p class="mb-0 text-secondary small">
                          You will be redirected to PayPal sandbox checkout portal. Please use a developer sandbox account to log in and confirm transaction mock.
                        </p>
                      </div>
                    </div>

                    <!-- Cash on Visit Box -->
                    <div class="method-details" id="details-cash-on-visit" style="display: none;">
                      <div class="alert alert-warning-subtle border-0 rounded-3 p-3 mb-0 shadow-sm">
                        <h6 class="fw-bold text-warning-dark mb-2"><i class="bi bi-cash-stack me-2"></i>Pay at Reception</h6>
                        <p class="mb-0 text-secondary small">
                          No upfront payment required online. Your appointment remains <strong>Pending</strong> until physically validated upon payment at the check-in desk.
                        </p>
                      </div>
                    </div>

                  </div>
                </div>

                <div class="mt-auto">
                  <button type="submit" id="submit-payment-btn" class="btn btn-primary-payment w-100 py-3 shadow-sm">
                    Please Select a Method
                  </button>
                </div>
              </form>

            </div>
          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- jQuery & Bootstrap 5 Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Custom Javascript -->
  <script src="assets/js/payment.js?v=<?= time() ?>"></script>
</body>
</html>
