<?php
// New Secure Logic with Prepared Statements and BCrypt:
include_once("dbcon.php");

if (isset($_SESSION['patient_id']) || isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$login_error = "";
if (isset($_SESSION['login_error'])) {
    $login_error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

$login_success = "";
if (isset($_SESSION['login_success'])) {
    $login_success = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['login_error'] = "CSRF Verification Failed";
        header("Location: login.php");
        exit();
    }

    $patient_email = trim($_POST['patient_email'] ?? '');
    $patient_password = $_POST['patient_password'] ?? '';
    
    if (empty($patient_email) || empty($patient_password)) {
        $_SESSION['login_error'] = "Please fill in all fields.";
        header("Location: login.php");
        exit();
    }

    // Select patient by email using prepared statement
    list($stmt, $result) = db_query_prepared($conn, "SELECT * FROM hs_patient WHERE patient_email = ?", [$patient_email]);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        
        file_put_contents('/var/www/html/pratice/hoperise_medical/error.log', "[" . date('Y-m-d H:i:s') . "] PATIENT LOGIN ATTEMPT - Email: '$patient_email', Entered Password: '$patient_password', Stored Hash: '{$row['patient_password']}'\n", FILE_APPEND);
        
        // Verify password using standard BCrypt hashing
        if (password_verify($patient_password, $row['patient_password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['patient_id'] = $row['patient_id'];
            $_SESSION['user_id'] = $row['patient_id'];
            $_SESSION['patient_fname'] = $row['patient_fname'];
            $_SESSION['patient_lname'] = $row['patient_lname'];
            $_SESSION['patient_email'] = $row['patient_email'];
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Incorrect password. Please try again.";
            header("Location: login.php");
            exit();
        }
    } else {
        file_put_contents('/var/www/html/pratice/hoperise_medical/error.log', "[" . date('Y-m-d H:i:s') . "] PATIENT LOGIN FAILED (NOT REGISTERED) - Email: '$patient_email'\n", FILE_APPEND);
        $_SESSION['login_error'] = "Email address not registered.";
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
include("includes/main_header.php");
?>
<body>
<div class="page-wrapper">
  <!-- Header elements -->
  <?php include("includes/top_header.php"); ?>
  <?php include("includes/upper_header.php"); ?>
  <?php include("includes/navbar.php"); ?>

  <!-- Page Title -->
  <section class="py-5 bg-primary-light text-primary position-relative overflow-hidden mb-5">
    <div class="container py-4 position-relative z-1 text-center">
      <h1 class="display-5 fw-bold text-dark mb-2">Patient Login</h1>
      <p class="text-secondary mb-0">Sign in to schedule appointments, manage reviews, and view prescriptions.</p>
    </div>
  </section>

  <!-- Login Section -->
  <section class="pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-5">
          <div class="glass-card p-5">
            <h3 class="fw-bold mb-4 text-center text-dark">Sign In</h3>
            
            <?php if (!empty($login_error)): ?>
              <div class="alert alert-danger border-0 rounded-3 mb-4 shadow-sm py-2 px-3 small text-center" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($login_error) ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($login_success)): ?>
              <div class="alert alert-success border-0 rounded-3 mb-4 shadow-sm py-2 px-3 small text-center" role="alert" style="background-color: #f0fdf4; color: #15803d;">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($login_success) ?>
              </div>
            <?php endif; ?>

            <form action="" method="post" class="d-flex flex-column gap-4">
              <!-- CSRF Token -->
              <?php echo get_csrf_input_html(); ?>

              <!-- Email -->
              <div>
                <label class="form-label fw-semibold text-secondary">Email Address</label>
                <input type="email" name="patient_email" class="form-control form-control-premium" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['patient_email'] ?? '') ?>" required>
              </div>

              <!-- Password -->
              <div>
                <label class="form-label fw-semibold text-secondary">Password</label>
                <input type="password" name="patient_password" class="form-control form-control-premium" placeholder="Enter password" required>
              </div>

              <!-- Action buttons -->
              <div class="mt-2">
                <button type="submit" name="submit" class="btn btn-premium btn-lg w-100">Login to Account</button>
              </div>
            </form>
            
            <div class="text-center mt-4">
              <span class="text-muted">New patient?</span> <a href="patient_reg.php" class="text-decoration-none fw-semibold">Register here</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Reusable modular Footer components -->
  <?php include("includes/footer.php"); ?>
</div>

<!-- JS imports -->
<?php include("includes/js.php"); ?>
</body>
</html>