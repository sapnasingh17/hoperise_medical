<?php
include_once("dbcon.php");

// Restrict to logged-in patients only
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = intval($_SESSION['patient_id']);

// Handle form submission securely
if (isset($_POST['submit_feedback'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $fd_message = trim($_POST['fd_message'] ?? '');

    if (empty($fd_message)) {
        echo "<script>alert('Please enter a feedback message.');</script>";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO hs_feedback (patient_id, fd_message, status) VALUES (?, ?, 1)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "is", $patient_id, $fd_message);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($success) {
                echo "<script>alert('Thank you! Your feedback has been submitted successfully.'); window.location.href='my_appointment.php';</script>";
                exit();
            } else {
                echo "<script>alert('Failed to submit feedback. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('System Error. Please contact support.');</script>";
        }
    }
}

include('includes/main_header.php');
?>
<body>
<div class="page-wrapper">
  <!-- Headers -->
  <?php include('includes/top_header.php'); ?>
  <?php include('includes/upper_header.php'); ?>
  <?php include('includes/navbar.php'); ?>

  <!-- Page Title -->
  <section class="py-5 bg-primary-light text-primary position-relative overflow-hidden mb-5">
    <div class="container py-4 position-relative z-1 text-center">
      <h1 class="display-5 fw-bold text-dark mb-2">Submit Feedback</h1>
      <p class="text-secondary mb-0">Help us improve by sharing your clinical treatment experience.</p>
    </div>
  </section>

  <!-- Feedback Submission Form Workspace -->
  <section class="pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="glass-card p-5">
            <h3 class="fw-bold text-dark mb-4"><i class="bi bi-chat-heart-fill text-primary me-2"></i>We Value Your Opinion</h3>
            
            <form action="" method="post" class="row g-4">
              <!-- CSRF token -->
              <?php echo get_csrf_input_html(); ?>

              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Patient Account</label>
                <input type="text" class="form-control form-control-premium" value="<?= htmlspecialchars($_SESSION['patient_fname'] . ' ' . $_SESSION['patient_lname'] . ' (' . $_SESSION['patient_email'] . ')') ?>" readonly disabled style="background-color: #f8fafc; cursor: not-allowed;">
              </div>
              
              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Your Feedback Message</label>
                <textarea name="fd_message" class="form-control form-control-premium" rows="6" placeholder="Tell us about your consultation, surgeon support, or hospital stay..." required></textarea>
              </div>
              
              <div class="col-md-12 text-end mt-4">
                <button type="submit" name="submit_feedback" class="btn btn-premium btn-lg w-100">Submit Feedback</button>
              </div>
            </form>
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
