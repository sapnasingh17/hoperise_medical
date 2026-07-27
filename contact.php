<?php
include_once("dbcon.php");
if(isset($_POST['submit']))
{
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    // Server-side validation & sanitization
    $raw_name    = isset($_POST['hs_name']) ? trim($_POST['hs_name']) : '';
    $raw_email   = isset($_POST['hs_email']) ? trim($_POST['hs_email']) : '';
    $raw_phone   = isset($_POST['hs_phone']) ? trim($_POST['hs_phone']) : '';
    $raw_message = isset($_POST['hs_message']) ? trim($_POST['hs_message']) : '';

    // Header Injection Prevention: Strip carriage returns and line feeds
    $hs_name    = str_replace(["\r", "\n"], '', $raw_name);
    $hs_email   = str_replace(["\r", "\n"], '', $raw_email);
    $hs_phone   = str_replace(["\r", "\n"], '', $raw_phone);
    $hs_message = str_replace("\0", '', $raw_message); // Strip null bytes

    $errors = [];
    if (empty($hs_name)) {
        $errors[] = "Name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]{1,100}$/", $hs_name)) {
        $errors[] = "Name must contain only letters and spaces (1-100 characters).";
    }

    if (empty($hs_email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($hs_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (empty($hs_phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match("/^[0-9\+\-\s]{10,15}$/", $hs_phone)) {
        $errors[] = "Phone number must be between 10 and 15 digits.";
    }

    if (empty($hs_message)) {
        $errors[] = "Message is required.";
    }

    if (!empty($errors)) {
        $error_str = implode("\\n", array_map('addslashes', $errors));
        echo "<script>alert('Validation Failed:\\n" . $error_str . "'); window.history.back();</script>";
        exit();
    }
     
    // Prepared Statement to prevent SQL Injection
    $stmt = mysqli_prepare($conn, "INSERT INTO hs_contact(hs_name, hs_email, hs_phone, hs_message) VALUES (?, ?, ?, ?)");
    $query = false;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $hs_name, $hs_email, $hs_phone, $hs_message);
        $query = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    if($query)
    {
        include_once("smtp_helper.php");
        
        $to = $hs_email;
        $subject = "Hope Rise Medical Contact Request";
        
        // HTML Email Template with Output Escaping for XSS Prevention
        $message = '<html xmlns="http://www.w3.org/1999/xhtml">
        <body style="background-color: #f8fafc; font-family: sans-serif; padding: 40px;">
            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="background: #2563eb; color: white; padding: 20px; text-align: center;">
                    <h2 style="margin:0;">Contact Request Received</h2>
                </div>
                <div style="padding: 30px; color: #334155; line-height: 1.6;">
                    <p>Dear ' . htmlspecialchars($hs_name, ENT_QUOTES, 'UTF-8') . ',</p>
                    <p>Thank you for reaching out to Hope Rise Medical. We have received your inquiry and our support team will contact you shortly.</p>
                    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #1e293b;">Inquiry Details:</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 6px 0; font-weight: bold; color: #64748b; width: 100px;">Name:</td>
                            <td style="padding: 6px 0; color: #0f172a;">' . htmlspecialchars($hs_name, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; font-weight: bold; color: #64748b;">Email:</td>
                            <td style="padding: 6px 0; color: #0f172a;">' . htmlspecialchars($hs_email, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; font-weight: bold; color: #64748b;">Phone:</td>
                            <td style="padding: 6px 0; color: #0f172a;">' . htmlspecialchars($hs_phone, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; font-weight: bold; color: #64748b; vertical-align: top;">Message:</td>
                            <td style="padding: 6px 0; color: #0f172a; white-space: pre-line;">' . htmlspecialchars($hs_message, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                    </table>
                </div>
                <div style="background-color: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; color: #64748b;">
                    This is an automated copy of your message submitted to Hope Rise Medical.
                </div>
            </div>
        </body>
        </html>';
        
        // Retrieve company/admin email and name dynamically
        $company_email = isset($GLOBALS['company_settings']['email']) && !empty($GLOBALS['company_settings']['email']) ? $GLOBALS['company_settings']['email'] : (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'sapnnna988@gmail.com');
        $company_name = isset($GLOBALS['company_settings']['hospital_name']) && !empty($GLOBALS['company_settings']['hospital_name']) ? $GLOBALS['company_settings']['hospital_name'] : (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Hope Rise Medical');

        $client_error = '';
        $admin_error = '';

        // Send auto-reply to client
        $client_sent = send_smtp_email($to, $subject, $message, $company_email, $company_name, $client_error);

        // Send notification to admin
        $admin_sent = send_smtp_email($company_email, "New Contact Submission: " . $hs_name, $message, $company_email, $company_name . ' Support', $admin_error);

        // Log any SMTP failures for debugging
        if (!$client_sent) {
            error_log("HS Contact Error: failed to send auto-reply to {$to}: {$client_error}");
        }
        if (!$admin_sent) {
            error_log("HS Contact Error: failed to send admin notification to {$company_email}: {$admin_error}");
        }

        // Output user-friendly response messages
        if ($client_sent || $admin_sent) {
            echo "<script>alert('✅ Message sent successfully.'); window.location.href='contact.php';</script>";
        } else {
            $reason = !empty($client_error) ? $client_error : (!empty($admin_error) ? $admin_error : 'SMTP Authentication Error');
            echo "<script>alert('❌ Failed to send email: " . addslashes($reason) . "'); window.location.href='contact.php';</script>";
        }
        exit();
    }
    else
    {
        echo "<script>alert('Failed to save message to database.'); window.history.back();</script>";
        exit();
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
      <h1 class="display-5 fw-bold text-dark mb-2">Contact Us</h1>
      <p class="text-secondary mb-0">Get in touch with our administrative desks and support teams.</p>
    </div>
  </section>

  <!-- Contact Form & Info Workspace -->
  <section class="pb-5">
    <div class="container">
      <div class="row g-5">
        <!-- Contact Information Block -->
        <div class="col-lg-4">
          <div class="d-flex flex-column gap-4">
            <div class="glass-card p-4">
              <div class="d-flex align-items-center gap-3">
                <div class="bg-primary-light text-primary rounded-circle p-3"><i class="bi bi-geo-alt-fill fs-4"></i></div>
                <div>
                  <h5 class="fw-bold text-dark mb-1">Our Location</h5>
                  <p class="text-secondary small mb-0">401, Solaris Royce, Opp. Old RTO, Besides Vanita Vishram Ground, Surat.</p>
                </div>
              </div>
            </div>
            
            <div class="glass-card p-4">
              <div class="d-flex align-items-center gap-3">
                <div class="bg-primary-light text-primary rounded-circle p-3"><i class="bi bi-telephone-fill fs-4"></i></div>
                <div>
                  <h5 class="fw-bold text-dark mb-1">Phone Number</h5>
                  <p class="text-secondary small mb-0">+91 3265 895 632</p>
                </div>
              </div>
            </div>

            <div class="glass-card p-4">
              <div class="d-flex align-items-center gap-3">
                <div class="bg-primary-light text-primary rounded-circle p-3"><i class="bi bi-envelope-fill fs-4"></i></div>
                <div>
                  <h5 class="fw-bold text-dark mb-1">Support Email</h5>
                  <p class="text-secondary small mb-0">sskn057@gmail.com</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Form Box -->
        <div class="col-lg-8">
          <div class="glass-card p-5">
            <h3 class="fw-bold text-dark mb-4"><i class="bi bi-chat-right-text-fill text-primary me-2"></i>Send Us a Message</h3>
            
            <form action="" method="post" class="row g-4">
              <!-- CSRF token -->
              <?php echo get_csrf_input_html(); ?>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Your Name</label>
                <input type="text" name="hs_name" class="form-control form-control-premium" placeholder="Name" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Email Address</label>
                <input type="email" name="hs_email" class="form-control form-control-premium" placeholder="name@example.com" required>
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Phone Number</label>
                <input type="tel" name="hs_phone" class="form-control form-control-premium" placeholder="e.g. +91 98765 43210" required>
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Your Message</label>
                <textarea name="hs_message" class="form-control form-control-premium" rows="5" placeholder="Type your inquiry here..." required></textarea>
              </div>
              
              <div class="col-md-12 text-end">
                <button type="submit" name="submit" class="btn btn-premium btn-lg w-100">Send Message</button>
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