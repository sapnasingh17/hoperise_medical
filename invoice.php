<?php
include_once("dbcon.php");

// Redirect guest users to login page
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$patient_id = intval($_SESSION['patient_id']);

// Fetch appointment, patient, and doctor details in one secure prepared statement
$stmt = mysqli_prepare($conn, "
    SELECT a.*, 
           p.patient_fname, p.patient_lname, p.patient_email, p.patient_phone, p.patient_address,
           d.doctor_fname, d.doctor_lname, 
           c.category_title, 
           s.service_title 
    FROM hs_appointment a
    LEFT JOIN hs_patient p ON a.patient_id = p.patient_id
    LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id
    LEFT JOIN hs_category c ON a.category_id = c.category_id
    LEFT JOIN hs_service s ON a.service_id = s.service_id
    WHERE a.appointment_id = ? AND a.patient_id = ?
");

$invoice = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $invoice = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$invoice) {
    header("Location: my_appointment.php");
    exit();
}

// Calculate pricing breakdown (Assuming a total base amount of ₹500 plus 18% GST)
$base_amount = 500;
$gst_rate = 0.18; // 18%
$gst_amount = $base_amount * $gst_rate;
$cgst_amount = $gst_amount / 2; // 9%
$sgst_amount = $gst_amount / 2; // 9%
$total_amount = $base_amount + $gst_amount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Hope Rise Medical | Invoice #INV-10<?= $invoice['appointment_id'] ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    body {
      background-color: #f8fafc;
      font-family: 'Inter', sans-serif;
    }
    .invoice-card {
      background: #ffffff;
      border: none;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
      padding: 40px;
    }
    .invoice-header {
      border-bottom: 2px solid #f1f5f9;
      padding-bottom: 30px;
      margin-bottom: 30px;
    }
    .brand-logo {
      color: #2563eb;
      font-size: 28px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }
    .invoice-title {
      font-size: 24px;
      font-weight: 800;
      color: #0f172a;
    }
    .badge-paid {
      background-color: #dcfce7;
      color: #166534;
      font-weight: 700;
      padding: 6px 16px;
      border-radius: 30px;
      font-size: 14px;
    }
    .badge-unpaid {
      background-color: #fee2e2;
      color: #991b1b;
      font-weight: 700;
      padding: 6px 16px;
      border-radius: 30px;
      font-size: 14px;
    }
    .table-invoice th {
      background-color: #f8fafc;
      color: #64748b;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border: none;
      padding: 12px 16px;
    }
    .table-invoice td {
      padding: 16px;
      border-bottom: 1px solid #f1f5f9;
      color: #334155;
    }
    .summary-box {
      background-color: #f8fafc;
      border-radius: 12px;
      padding: 24px;
      margin-top: 20px;
    }
    .btn-premium {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: #ffffff;
      border: none;
      font-weight: 600;
      border-radius: 10px;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
      transition: all 0.2s ease-in-out;
    }
    .btn-premium:hover {
      background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
      color: #ffffff;
      transform: translateY(-1px);
    }
    
    /* Print Styles */
    @media print {
      body {
        background-color: #ffffff;
        font-size: 12px;
      }
      .no-print {
        display: none !important;
      }
      .invoice-card {
        box-shadow: none;
        padding: 0;
        border-radius: 0;
      }
      .table-invoice th {
        background-color: #f8fafc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .badge-paid {
        background-color: #dcfce7 !important;
        color: #166534 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .badge-unpaid {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <!-- Action Buttons (hidden during printing) -->
      <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="my_appointment.php" class="btn btn-outline-secondary px-3 py-2 rounded-3"><i class="bi bi-arrow-left me-2"></i>Back to Dashboard</a>
        <button onclick="window.print();" class="btn btn-premium px-4 py-2"><i class="bi bi-printer-fill me-2"></i>Print / Download PDF</button>
      </div>

      <!-- Invoice Sheet Card -->
      <div class="invoice-card">
        
        <!-- Header -->
        <div class="invoice-header d-flex justify-content-between align-items-start flex-wrap gap-4">
          <div>
            <div class="brand-logo mb-2"><i class="bi bi-heart-pulse-fill me-2"></i>Hope Rise Medical</div>
            <p class="text-secondary small mb-0">
              12 Medical Zone, Mumbai, Maharashtra, 400001<br>
              <strong>GSTIN:</strong> 27AAAAA1111A1Z1<br>
              <strong>Email:</strong> contact@hoperise.com &bull; <strong>Phone:</strong> +91 2215 463 895
            </p>
          </div>
          <div class="text-md-end">
            <span class="invoice-title d-block mb-2">TAX INVOICE</span>
            <span class="text-secondary small d-block">Invoice No: <strong>#INV-10<?= $invoice['appointment_id'] ?></strong></span>
            <?php if (!empty($invoice['transaction_id'])): ?>
              <span class="text-secondary small d-block">Appt Number: <strong><?= htmlspecialchars($invoice['transaction_id']) ?></strong></span>
            <?php endif; ?>
            <span class="text-secondary small d-block mb-3">Date: <strong><?= date('d-M-Y', strtotime($invoice['appointment_date'])) ?></strong></span>
            <?php if ($invoice['appointment_payment_status'] == 1): ?>
              <span class="badge-paid"><i class="bi bi-check-circle-fill me-2"></i>PAID</span>
            <?php else: ?>
              <span class="badge-unpaid"><i class="bi bi-exclamation-circle-fill me-2"></i>UNPAID</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Billing Info -->
        <div class="row g-4 mb-5">
          <div class="col-sm-6">
            <span class="text-muted small uppercase d-block mb-2">Billed To (Patient)</span>
            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($invoice['patient_fname'] . " " . $invoice['patient_lname']) ?></h5>
            <p class="text-secondary small mb-0">
              <?= htmlspecialchars($invoice['patient_address'] ?? 'Not Provided') ?><br>
              <strong>Phone:</strong> <?= htmlspecialchars($invoice['patient_phone']) ?><br>
              <strong>Email:</strong> <?= htmlspecialchars($invoice['patient_email']) ?>
            </p>
          </div>
          <div class="col-sm-6 text-sm-end">
            <span class="text-muted small uppercase d-block mb-2">Provider Details</span>
            <h5 class="fw-bold text-dark mb-1">Dr. <?= htmlspecialchars($invoice['doctor_fname'] . " " . $invoice['doctor_lname']) ?></h5>
            <p class="text-secondary small mb-0">
              Hope Rise Medical Practitioner<br>
              Department of <?= htmlspecialchars($invoice['category_title']) ?><br>
              <strong>Service:</strong> <?= htmlspecialchars($invoice['service_title']) ?>
            </p>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive mb-4">
          <table class="table table-invoice align-middle">
            <thead>
              <tr>
                <th style="width: 50%;">Description</th>
                <th class="text-end" style="width: 25%;">Base Price</th>
                <th class="text-end" style="width: 25%;">Amount (INR)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="fw-bold text-dark">Consultation & Examination Fee</div>
                  <div class="text-muted small">Doctor: Dr. <?= htmlspecialchars($invoice['doctor_fname'] . " " . $invoice['doctor_lname']) ?> (<?= htmlspecialchars($invoice['service_title']) ?>)</div>
                </td>
                <td class="text-end">₹<?= number_format($base_amount, 2) ?></td>
                <td class="text-end fw-semibold text-dark">₹<?= number_format($base_amount, 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="row justify-content-end">
          <div class="col-md-6 col-sm-8">
            <div class="summary-box">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary small">Subtotal:</span>
                <span class="fw-semibold text-dark">₹<?= number_format($base_amount, 2) ?></span>
              </div>
              <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                <span class="text-secondary small">CGST (9%):</span>
                <span class="fw-semibold text-dark">₹<?= number_format($cgst_amount, 2) ?></span>
              </div>
              <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                <span class="text-secondary small">SGST (9%):</span>
                <span class="fw-semibold text-dark">₹<?= number_format($sgst_amount, 2) ?></span>
              </div>
              <div class="d-flex justify-content-between align-items-center pt-2">
                <span class="fw-bold text-dark">Grand Total (Incl. GST):</span>
                <span class="fs-5 fw-extrabold text-primary">₹<?= number_format($total_amount, 2) ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center mt-5 pt-4 border-top">
          <p class="text-secondary small mb-0">Thank you for choosing Hope Rise Medical. Wishing you a speedy recovery!</p>
          <span class="text-muted small fs-8">This is a system generated invoice and does not require a physical signature.</span>
        </div>

      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
