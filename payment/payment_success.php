<?php
/**
 * Payment Success & Invoice Generation Page
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

// Fetch appointment detail with ownership check
$appointment = get_payment_appointment_details($conn, $appointment_id, $patient_id);
if (!$appointment) {
    $_SESSION['toast_error'] = 'Appointment not found or unauthorized access';
    header("Location: ../my_appointment.php");
    exit();
}

// Retrieve company settings from DB
$hospital_name = 'Hope Rise Medical';
$company_logo = '/var/www/html/pratice/hoperise_medical/images/img.png';
$stmt_settings = mysqli_prepare($conn, "SELECT hospital_name, logo FROM company_settings LIMIT 1");
if ($stmt_settings) {
    mysqli_stmt_execute($stmt_settings);
    $result_settings = mysqli_stmt_get_result($stmt_settings);
    if ($settings = mysqli_fetch_assoc($result_settings)) {
        if (!empty($settings['hospital_name'])) {
            $hospital_name = htmlspecialchars($settings['hospital_name']);
        }
        if (!empty($settings['logo'])) {
            $company_logo = '/var/www/html/pratice/hoperise_medical/uploads/company/' . $settings['logo'];
        }
    }
    mysqli_stmt_close($stmt_settings);
}

// Base64 encode company logo for inline display (compatibility in HTML and Dompdf)
$logo_base64 = '';
if (file_exists($company_logo)) {
    $logo_base64 = 'data:image/' . pathinfo($company_logo, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($company_logo));
}

// Map database fields to clean HTML entities
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

$total_amount = floatval($appointment['amount'] ?? 590.00);
if ($total_amount <= 0) {
    $total_amount = 590.00;
}
$base_fee = round($total_amount / 1.18, 2);
$gst_amount = round($total_amount - $base_fee, 2);

$payment_method = htmlspecialchars($appointment['payment_method'] ?? 'Not Selected');
$payment_status = htmlspecialchars($appointment['payment_status'] ?? 'Pending');
$transaction_id = htmlspecialchars($appointment['transaction_id'] ?? 'N/A');
$created_date = date('d M Y h:i A', strtotime($appointment['created_date'] ?? 'now'));

// Generate clean, inline-styled HTML receipt (reused by Dompdf)
$invoice_html = '
<div style="font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; color: #333; max-width: 800px; margin: auto; padding: 20px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 14px; line-height: 24px; background-color: #fff;">
    <!-- Invoice Header -->
    <table cellpadding="0" cellspacing="0" style="width: 100%; border-bottom: 2px solid #ddd; padding-bottom: 20px; margin-bottom: 20px;">
        <tr>
            <td style="width: 50%;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    ' . (!empty($logo_base64) ? '<img src="' . $logo_base64 . '" alt="' . $hospital_name . '" style="height: 40px; vertical-align: middle; max-width: 200px;">' : '') . '
                    <span style="font-size: 22px; font-weight: bold; color: #079d9c; vertical-align: middle; margin-left: 10px;">' . $hospital_name . '</span>
                </div>
                <div style="font-size: 11px; color: #777; margin-top: 5px;">
                    Hope Rise Medical Center<br>
                    12 Medical Zone, Mumbai, MH, 400001<br>
                    GSTIN: 27AAAAA1111A1Z1
                </div>
            </td>
            <td style="text-align: right; width: 50%; vertical-align: top;">
                <h2 style="margin: 0; color: #079d9c; font-size: 24px; font-weight: 800;">TAX INVOICE</h2>
                <div style="font-size: 12px; color: #555; margin-top: 5px;">
                    <strong>Invoice No:</strong> #INV-10' . $appointment_id . '<br>
                    <strong>Invoice Date:</strong> ' . date('d M Y', strtotime($appointment['created_date'] ?? 'now')) . '<br>
                    <strong>Status:</strong> <span style="color: ' . ($payment_status === 'Paid' ? '#10b981' : '#f5a92f') . '; font-weight: bold;">' . strtoupper($payment_status) . '</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Billing Info -->
    <table cellpadding="0" cellspacing="0" style="width: 100%; margin-bottom: 25px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 20px;">
                <span style="font-size: 11px; text-transform: uppercase; color: #999; font-weight: bold; display: block; margin-bottom: 5px;">Billed To (Patient)</span>
                <strong style="font-size: 15px; color: #222;">' . $patient_fname . ' ' . $patient_lname . '</strong><br>
                <span style="font-size: 12px; color: #555;">
                    Phone: ' . $patient_phone . '<br>
                    Email: ' . $patient_email . '<br>
                    Address: ' . $patient_address . '
                </span>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <span style="font-size: 11px; text-transform: uppercase; color: #999; font-weight: bold; display: block; margin-bottom: 5px;">Provider Details</span>
                <strong style="font-size: 15px; color: #222;">Dr. ' . $doctor_fname . ' ' . $doctor_lname . '</strong><br>
                <span style="font-size: 12px; color: #555;">
                    Specialist, Department of ' . $category_title . '<br>
                    <strong>Service:</strong> ' . $service_title . '<br>
                    <strong>Schedule:</strong> ' . $appointment_date . ' at ' . $appointment_time . '
                </span>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
        <thead>
            <tr style="background-color: #f8fafc; border-bottom: 2px solid #eee;">
                <th style="padding: 12px 10px; text-align: left; font-size: 12px; text-transform: uppercase; color: #666; font-weight: bold; width: 60%;">Description</th>
                <th style="padding: 12px 10px; text-align: right; font-size: 12px; text-transform: uppercase; color: #666; font-weight: bold; width: 20%;">Base Fee</th>
                <th style="padding: 12px 10px; text-align: right; font-size: 12px; text-transform: uppercase; color: #666; font-weight: bold; width: 20%;">Total (INR)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 15px 10px; vertical-align: middle;">
                    <strong style="color: #333; font-size: 14px;">Clinical Consultation &amp; Examination</strong><br>
                    <span style="font-size: 12px; color: #777;">Department: ' . $category_title . ' | Service: ' . $service_title . '</span>
                </td>
                <td style="padding: 15px 10px; text-align: right; vertical-align: middle; color: #444;">₹' . number_format($base_fee, 2) . '</td>
                <td style="padding: 15px 10px; text-align: right; vertical-align: middle; font-weight: bold; color: #333;">₹' . number_format($base_fee, 2) . '</td>
            </tr>
        </tbody>
    </table>

    <!-- Pricing Summary -->
    <table cellpadding="0" cellspacing="0" style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div style="background-color: #f9f9f9; border-left: 3px solid #079d9c; padding: 12px; font-size: 12px; color: #666; border-radius: 4px;">
                    <strong>Payment Method:</strong> ' . $payment_method . '<br>
                    <strong>Transaction ID:</strong> ' . $transaction_id . '<br>
                    <strong>Payment Time:</strong> ' . $created_date . '
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <table cellpadding="0" cellspacing="0" style="width: 100%; float: right; font-size: 13px;">
                    <tr>
                        <td style="padding: 6px 10px; text-align: right; color: #666; width: 60%;">Subtotal:</td>
                        <td style="padding: 6px 10px; text-align: right; font-weight: 600; color: #333; width: 40%;">₹' . number_format($base_fee, 2) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 10px; text-align: right; color: #666;">GST (18%):</td>
                        <td style="padding: 6px 10px; text-align: right; font-weight: 600; color: #333;">₹' . number_format($gst_amount, 2) . '</td>
                    </tr>
                    <tr style="border-top: 1px solid #ddd; background-color: #f1f7ff;">
                        <td style="padding: 10px; text-align: right; font-weight: bold; color: #079d9c; font-size: 14px;">Total Payable:</td>
                        <td style="padding: 10px; text-align: right; font-weight: bold; color: #079d9c; font-size: 16px;">₹' . number_format($total_amount, 2) . '</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer Note -->
    <div style="text-align: center; border-top: 1px solid #eee; padding-top: 15px; font-size: 11px; color: #999;">
        Thank you for choosing ' . $hospital_name . '. Wishing you a speedy recovery!<br>
        <span style="font-size: 9px; color: #bbb; display: block; margin-top: 4px;">This is a system generated tax invoice and does not require a physical signature.</span>
    </div>
</div>
';

// Handle PDF download action
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
if ($action === 'download') {
    require_once __DIR__ . '/../plugins/dompdf/autoload.inc.php';
    
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($invoice_html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $filename = "Invoice-INV-10" . $appointment_id . ".pdf";
    $dompdf->stream($filename, ["Attachment" => true]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Success &amp; Invoice | <?= $hospital_name ?></title>

  <!-- Font Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  
  <link href="../css/style.css?v=<?= time(); ?>" rel="stylesheet">
  <link href="../css/custom.css?v=<?= time(); ?>" rel="stylesheet">

  <style>
    :root {
      --primary-color: #079d9c;
      --primary-hover: #058281;
      --primary-light: #e6f6f6;
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --success-color: #10b981;
      --success-light: #d1fae5;
    }

    body {
      background-color: #f8fafc;
      font-family: 'Outfit', sans-serif;
      color: var(--text-dark);
      min-height: 100vh;
    }

    /* Navbar styling */
    .custom-navbar {
      background-color: #ffffff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .navbar-logo {
      height: 48px;
      width: auto;
      object-fit: contain;
    }

    /* Success Card Styles */
    .success-card {
      background-color: #ffffff;
      border: 1px solid rgba(0, 0, 0, 0.05);
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
      padding: 40px;
      text-align: center;
      height: 100%;
    }

    .success-icon-wrap {
      width: 76px;
      height: 76px;
      background-color: var(--success-light);
      color: var(--success-color);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      font-size: 2.5rem;
      animation: pulseSuccess 2s infinite;
    }

    @keyframes pulseSuccess {
      0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
      }
      70% {
        box-shadow: 0 0 0 15px rgba(16, 185, 129, 0);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
      }
    }

    /* Summary info block */
    .summary-grid {
      background-color: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 20px;
    }

    .summary-item {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      font-size: 0.95rem;
      border-bottom: 1px dashed rgba(0, 0, 0, 0.05);
    }

    .summary-item:last-child {
      border-bottom: none;
      font-weight: 700;
      color: var(--primary-color);
      font-size: 1.05rem;
    }

    /* Buttons */
    .btn-primary-custom {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      color: #ffffff;
      font-weight: 600;
      padding: 12px 24px;
      border-radius: 10px;
      transition: all 0.25s ease;
    }

    .btn-primary-custom:hover {
      background-color: var(--primary-hover);
      border-color: var(--primary-hover);
      color: #ffffff;
      transform: translateY(-2px);
    }

    .btn-outline-custom {
      border: 2px solid var(--primary-color);
      color: var(--primary-color);
      background-color: transparent;
      font-weight: 600;
      padding: 12px 24px;
      border-radius: 10px;
      transition: all 0.25s ease;
    }

    .btn-outline-custom:hover {
      background-color: var(--primary-light);
      color: var(--primary-color);
      border-color: var(--primary-color);
      transform: translateY(-2px);
    }

    .btn-secondary-custom {
      background-color: #f1f5f9;
      border-color: #f1f5f9;
      color: var(--text-muted);
      font-weight: 600;
      padding: 12px 24px;
      border-radius: 10px;
      transition: all 0.25s ease;
    }

    .btn-secondary-custom:hover {
      background-color: #e2e8f0;
      color: var(--text-dark);
      transform: translateY(-2px);
    }

    /* Hide standard UI elements during print */
    @media print {
      .no-print {
        display: none !important;
      }
      body {
        background-color: #ffffff !important;
      }
      .print-full-width {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
      }
      .invoice-container {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
      }
    }
  </style>
</head>
<body>

  <!-- Top Header Navigation -->
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

  <!-- Main Container -->
  <div class="container py-5">
    <div class="row g-4 justify-content-center">
      
      <!-- Left Column: Payment Summary Card (Hidden on Print) -->
      <div class="col-lg-5 col-md-12 no-print">
        <div class="success-card d-flex flex-column justify-content-between">
          <div>
            <div class="success-icon-wrap">
              <i class="bi bi-check2-all"></i>
            </div>
            
            <h3 class="fw-bold text-dark mb-2">Payment Successful</h3>
            <p class="text-secondary small mb-4">
              Thank you! Your payment has been received and your booking session is verified. A confirmation copy has been sent to your email.
            </p>

            <div class="summary-grid mb-4">
              <div class="summary-item">
                <span class="text-muted">Appointment ID</span>
                <span class="fw-bold text-dark">#<?= $appointment_id ?></span>
              </div>
              <div class="summary-item">
                <span class="text-muted">Invoice Reference</span>
                <span class="fw-semibold text-dark">#INV-10<?= $appointment_id ?></span>
              </div>
              <div class="summary-item">
                <span class="text-muted">Payment Status</span>
                <span class="badge bg-success-subtle text-success px-2 py-1 rounded"><?= $payment_status ?></span>
              </div>
              <div class="summary-item">
                <span class="text-muted">Payment Method</span>
                <span class="fw-semibold text-dark"><?= $payment_method ?></span>
              </div>
              <div class="summary-item">
                <span class="text-muted">Transaction Reference</span>
                <span class="fw-semibold text-dark text-break" style="max-width: 180px;"><?= $transaction_id ?></span>
              </div>
              <div class="summary-item">
                <span class="text-muted">Processed Date</span>
                <span class="text-dark small"><?= $created_date ?></span>
              </div>
              <div class="summary-item">
                <span class="text-muted">Total Amount</span>
                <span>₹<?= number_format($total_amount, 2) ?></span>
              </div>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="d-grid gap-2">
            <a href="payment_success.php?appointment_id=<?= $appointment_id ?>&amp;action=download" class="btn btn-primary-custom">
              <i class="bi bi-file-earmark-pdf-fill me-2"></i>Download Invoice (PDF)
            </a>
            <button onclick="window.print();" class="btn btn-outline-custom">
              <i class="bi bi-printer-fill me-2"></i>Print Invoice
            </button>
            <a href="../my_appointment.php" class="btn btn-secondary-custom">
              <i class="bi bi-speedometer2 me-2"></i>Back to Dashboard
            </a>
          </div>
        </div>
      </div>

      <!-- Right Column: Visual Invoice Preview Sheet -->
      <div class="col-lg-7 col-md-12 print-full-width">
        <div class="invoice-container shadow-sm border rounded-4 bg-white p-4 p-md-5">
          <!-- Render identical HTML template visually -->
          <?= $invoice_html ?>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
