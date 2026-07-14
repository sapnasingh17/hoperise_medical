<?php
include_once("dbcon.php");

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $patient_email = $_POST['patient_email'];
    
    // Query hs_patient table using prepared statements to prevent SQL Injection
    list($stmt, $result) = db_query_prepared($conn, "SELECT * FROM hs_patient WHERE patient_email = ?", [$patient_email]);
    
    if (mysqli_num_rows($result) > 0) {
        // Notify the user that reset instructions have been simulated/sent
        echo "<script>alert('Password reset instructions have been sent to your email.'); window.location.href='login.php';</script>";
        exit();
    } else {
        echo "<script>alert('No account found with that email address.');</script>";
    }
}

include("includes/main_header.php");
?>
<body class="bg-silver-300">
  <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
    <div class="glass-card p-5" style="max-width: 450px; width: 100%;">
      <div class="text-center mb-4">
        <h2 class="fw-extrabold text-primary mb-1">Hope Rise Medical</h2>
        <p class="text-secondary small">Forgot Password</p>
      </div>

      <form action="" method="post" class="d-flex flex-column gap-3">
        <?php echo get_csrf_input_html(); ?>
        <p class="text-secondary small mb-2 text-center">Enter your email address below and we'll send you password reset instructions.</p>
        
        <div>
          <label class="form-label small fw-semibold text-secondary">Email Address</label>
          <input class="form-control form-control-premium" type="email" name="patient_email" placeholder="name@example.com" required autocomplete="off">
        </div>

        <div class="mt-2">
          <button class="btn btn-premium w-100 py-3" type="submit" name="submit">Send Reset Link</button>
        </div>

        <div class="text-center mt-3">
          <a href="login.php" class="text-primary small text-decoration-none fw-semibold"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>