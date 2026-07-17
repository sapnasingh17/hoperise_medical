<?php
/**
 * Redirect Handler for PayPal Sandbox Integration (HTML Form POST Method)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payment_functions.php';

// Verify POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['toast_error'] = 'Invalid request method';
    header("Location: ../my_appointment.php");
    exit();
}

// Verify CSRF Token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['toast_error'] = 'CSRF verification failed';
    header("Location: ../my_appointment.php");
    exit();
}

$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$patient_id = $_SESSION['patient_id'];

// Fetch and validate appointment details
$appointment = get_payment_appointment_details($conn, $appointment_id, $patient_id);
if (!$appointment) {
    $_SESSION['toast_error'] = 'Appointment details could not be loaded';
    header("Location: ../my_appointment.php");
    exit();
}

// Check if already paid
if ($appointment['appointment_payment_status'] == 1) {
    $_SESSION['toast_success'] = 'This appointment has already been paid.';
    header("Location: ../my_appointment.php");
    exit();
}

// Amount calculation (convert INR to USD for Sandbox transaction, e.g. 590 INR = ~7.08 USD)
$amount_inr = floatval($appointment['amount'] ?? 590.00);
if ($amount_inr <= 0) {
    $amount_inr = 590.00;
}
$usd_amount = round($amount_inr * 0.012, 2); // Simple conversion rate
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Redirecting to PayPal...</title>
  
  <!-- Font Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Custom Payment CSS -->
  <link rel="stylesheet" href="assets/css/payment.css?v=<?= time() ?>">
</head>
<body class="payment-wrapper min-vh-100 d-flex align-items-center justify-content-center">

  <div class="glass-card p-5 text-center max-width-md shadow-lg">
    <!-- Standalone, Non-Nested PayPal Form -->
    <form id="paypal-redirect-form" action="<?= htmlspecialchars(PAYPAL_URL) ?>" method="POST">
      <!-- Required PayPal Variables -->
      <input type="hidden" name="cmd" value="_xclick">
      <input type="hidden" name="business" value="<?= htmlspecialchars(PAYPAL_BUSINESS_EMAIL) ?>">
      <input type="hidden" name="item_name" value="HopeRise Clinical Consultation - Appt #<?= $appointment_id ?>">
      <input type="hidden" name="item_number" value="<?= $appointment_id ?>">
      <input type="hidden" name="amount" value="<?= $usd_amount ?>">
      <input type="hidden" name="currency_code" value="<?= htmlspecialchars(PAYPAL_CURRENCY) ?>">
      
      <!-- Redirect & Callback URLs -->
      <input type="hidden" name="return" value="<?= htmlspecialchars(PAYPAL_RETURN_URL . '?appointment_id=' . $appointment_id) ?>">
      <input type="hidden" name="cancel_return" value="<?= htmlspecialchars(PAYPAL_CANCEL_URL . '?appointment_id=' . $appointment_id) ?>">
      <input type="hidden" name="notify_url" value="<?= htmlspecialchars(PAYPAL_NOTIFY_URL) ?>">
      
      <!-- Optional Display Rules -->
      <input type="hidden" name="no_shipping" value="1">
      <input type="hidden" name="no_note" value="1">

      <div class="mb-4">
        <div class="spinner-border text-teal" style="width: 3rem; height: 3rem;" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      
      <h4 class="fw-bold mb-2">Connecting to PayPal Sandbox</h4>
      <p class="text-secondary mb-4">Please wait while we redirect you to complete your secure payment transaction.</p>
      
      <button type="submit" class="btn btn-primary-theme px-4 py-2">
        Click here if not redirected automatically
      </button>
    </form>
  </div>

  <script>
    // Automatically submit the form to PayPal Sandbox immediately upon page load
    window.addEventListener('DOMContentLoaded', () => {
      document.getElementById('paypal-redirect-form').submit();
    });
  </script>
</body>
</html>
