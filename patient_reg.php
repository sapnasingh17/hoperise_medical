<?php
include_once("dbcon.php");

if (isset($_SESSION['patient_id'])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$old = [];

if (isset($_SESSION['reg_errors'])) {
    $errors = $_SESSION['reg_errors'];
    unset($_SESSION['reg_errors']);
}
if (isset($_SESSION['reg_old'])) {
    $old = $_SESSION['reg_old'];
    unset($_SESSION['reg_old']);
}

if(isset($_POST['submit']))
{
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['reg_errors'] = ["CSRF Verification Failed. Please reload the page and try again."];
        $_SESSION['reg_old'] = $_POST;
        header("Location: patient_reg.php");
        exit();
    }

    $errors = [];

    // Trim inputs
    $patient_fname = trim($_POST['patient_fname'] ?? '');
    $patient_mname = trim($_POST['patient_mname'] ?? '');
    $patient_lname = trim($_POST['patient_lname'] ?? '');
    $patient_email = trim($_POST['patient_email'] ?? '');
    $patient_password = $_POST['patient_password'] ?? '';
    $patient_gender = trim($_POST['patient_gender'] ?? '');
    $patient_address = trim($_POST['patient_address'] ?? '');
    $patient_phone = trim($_POST['patient_phone'] ?? '');
    $patient_dob = trim($_POST['patient_dob'] ?? '');

    // Name validations (only alphabetic characters and spaces)
    if (!preg_match("/^[a-zA-Z\s]{1,50}$/", $patient_fname)) {
        $errors[] = "First name must contain only letters and spaces (1-50 characters).";
    }
    if ($patient_mname !== "" && !preg_match("/^[a-zA-Z\s]{0,50}$/", $patient_mname)) {
        $errors[] = "Middle name must contain only letters and spaces (max 50 characters).";
    }
    if (!preg_match("/^[a-zA-Z\s]{1,50}$/", $patient_lname)) {
        $errors[] = "Last name must contain only letters and spaces (1-50 characters).";
    }

    // Email validation
    if (!filter_var($patient_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    // Password validation (min 8 chars, 1 uppercase, 1 lowercase, 1 digit, 1 special char)
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $patient_password)) {
        $errors[] = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&).";
    }

    // Gender validation
    if ($patient_gender !== 'male' && $patient_gender !== 'female') {
        $errors[] = "Please select a valid gender option.";
    }

    // Phone validation (numeric, 10-15 chars)
    if (!preg_match("/^[0-9\+\-\s]{10,15}$/", $patient_phone)) {
        $errors[] = "Phone number must be between 10 and 15 digits.";
    }

    // Date of Birth validation (valid past date)
    $dob_timestamp = strtotime($patient_dob);
    if (!$dob_timestamp || $dob_timestamp > time()) {
        $errors[] = "Please select a valid date of birth in the past.";
    }

    if (!empty($errors)) {
        $_SESSION['reg_errors'] = $errors;
        $_SESSION['reg_old'] = $_POST;
        header("Location: patient_reg.php");
        exit();
    }
    
    // Check if email already exists
    list($check_stmt, $check_result) = db_query_prepared($conn, "SELECT patient_id FROM hs_patient WHERE patient_email = ?", [$patient_email]);
    if(mysqli_num_rows($check_result) > 0)
    {
        if ($check_stmt) mysqli_stmt_close($check_stmt);
        $_SESSION['reg_errors'] = ["Email address is already registered."];
        $_SESSION['reg_old'] = $_POST;
        header("Location: patient_reg.php");
        exit();
    }
    if ($check_stmt) mysqli_stmt_close($check_stmt);

    // Format DOB to standard YYYY-MM-DD
    $patient_dob_formatted = date('Y-m-d', $dob_timestamp);

    // Hash password securely with BCrypt
    $hashed_password = password_hash($patient_password, PASSWORD_BCRYPT);
    
    // Insert with prepared statement
    $stmt = mysqli_prepare($conn, "INSERT INTO hs_patient(patient_fname,patient_lname,patient_mname,patient_email,patient_password,patient_gender,patient_address,patient_phone,patient_dob) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssssss", $patient_fname, $patient_lname, $patient_mname, $patient_email, $hashed_password, $patient_gender, $patient_address, $patient_phone, $patient_dob_formatted);
    $query = mysqli_stmt_execute($stmt);
    
    if($query)
    {
        $to = $patient_email;
        $subject = "Hoperise medical";
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base_url = $protocol . $host . rtrim($dir, '/') . '/';

        $message = '<html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0;">
            <style>
                body { margin: 0; padding: 0; min-width: 100%; width: 100% !important; height: 100% !important;}
                body, table, td, div, p, a { -webkit-font-smoothing: antialiased; text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; line-height: 100%; }
                table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse !important; border-spacing: 0; }
                img { border: 0; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
            </style>
        </head>
        <body style="background-color: #f8fafc;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="padding: 40px 0;">
                        <table width="600" border="0" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                            <tr>
                                <td style="background-color: #2563eb; padding: 30px; text-align: center; color: #ffffff;">
                                    <h2 style="margin: 0; font-family: sans-serif;">Welcome to Hope Rise Medical</h2>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 40px 30px; font-family: sans-serif; color: #334155; line-height: 1.6;">
                                    <p>Dear ' . htmlspecialchars($patient_fname) . ',</p>
                                    <p>Your registration was successful. Welcome to our premium clinical care family.</p>
                                    <p>You can now log in to schedule consultation appointments, download invoice receipts, and review treatment plans.</p>
                                    <p style="margin-top: 30px;"><a href="' . htmlspecialchars($base_url) . 'login.php" style="background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">Log In Now</a></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
        
        include_once("email_helper.php");
        send_custom_email($to, $subject, $message, 'sapnnna988@gmail.com', 'Hope Rise Medical', $conn);
        
        $_SESSION['login_success'] = "Registration successful! Welcome to Hope Rise Medical. You can now log in.";
        header("Location: login.php");
        exit();
    }
    else
    {
        $_SESSION['reg_errors'] = ["Registration failed due to a database error. Please try again."];
        $_SESSION['reg_old'] = $_POST;
        header("Location: patient_reg.php");
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
      <h1 class="display-5 fw-bold text-dark mb-2">Create Account</h1>
      <p class="text-secondary mb-0">Join Hope Rise Medical to schedule consultations and access diagnostics history.</p>
    </div>
  </section>

  <!-- Register Form Form -->
  <section class="pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="glass-card p-5">
            <h3 class="fw-bold mb-4 text-center text-dark">Register Profile</h3>
            
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger border-0 rounded-3 mb-4 shadow-sm" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following validation errors:</div>
                <ul class="mb-0 ps-3">
                  <?php foreach ($errors as $error): ?>
                    <li class="small"><?= htmlspecialchars($error) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form action="" method="post" id="patientRegForm" class="row g-4" novalidate>
              <!-- CSRF Token -->
              <?php echo get_csrf_input_html(); ?>

              <!-- Name fields -->
              <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">First Name</label>
                <input type="text" id="reg_patient_fname" name="patient_fname" class="form-control form-control-premium" placeholder="First Name" value="<?= htmlspecialchars($old['patient_fname'] ?? '') ?>" required>
                <div class="invalid-feedback">First name must contain only letters and spaces (1-50 characters).</div>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">Middle Name</label>
                <input type="text" id="reg_patient_mname" name="patient_mname" class="form-control form-control-premium" placeholder="Middle Name" value="<?= htmlspecialchars($old['patient_mname'] ?? '') ?>">
                <div class="invalid-feedback">Middle name must contain only letters and spaces (max 50 characters).</div>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">Last Name</label>
                <input type="text" id="reg_patient_lname" name="patient_lname" class="form-control form-control-premium" placeholder="Last Name" value="<?= htmlspecialchars($old['patient_lname'] ?? '') ?>" required>
                <div class="invalid-feedback">Last name must contain only letters and spaces (1-50 characters).</div>
              </div>

              <!-- Email and Password -->
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary">Email Address</label>
                <input type="email" id="reg_patient_email" name="patient_email" class="form-control form-control-premium" placeholder="name@example.com" value="<?= htmlspecialchars($old['patient_email'] ?? '') ?>" required>
                <div class="invalid-feedback">Please enter a valid email address.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary">Password</label>
                <input type="password" id="reg_patient_password" name="patient_password" class="form-control form-control-premium" placeholder="Create Secure Password" required>
                <div class="invalid-feedback">Password must be at least 8 characters long, contain an uppercase, a lowercase, a number, and a special character (@$!%*?&).</div>
                
                <!-- Password Strength Indicator -->
                <div class="password-strength-wrapper mt-2 d-none" id="pw-strength-wrapper">
                  <div class="progress" style="height: 6px;">
                    <div id="pw-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <div id="pw-strength-text" class="small mt-1 fw-semibold text-muted">Password strength</div>
                </div>
              </div>

              <!-- Gender -->
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary d-block">Gender</label>
                <div class="d-flex gap-4 mt-2">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="patient_gender" id="genderMale" value="male" <?= (isset($old['patient_gender']) && $old['patient_gender'] === 'male') ? 'checked' : '' ?> required>
                    <label class="form-check-label text-dark" for="genderMale">Male</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="patient_gender" id="genderFemale" value="female" <?= (isset($old['patient_gender']) && $old['patient_gender'] === 'female') ? 'checked' : '' ?> required>
                    <label class="form-check-label text-dark" for="genderFemale">Female</label>
                  </div>
                </div>
              </div>
              
              <!-- Date of Birth -->
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary">Date of Birth</label>
                <input type="date" id="reg_patient_dob" name="patient_dob" class="form-control form-control-premium" value="<?= htmlspecialchars($old['patient_dob'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>
                <div class="invalid-feedback">Please select a valid date of birth in the past.</div>
              </div>

              <!-- Phone number -->
              <div class="col-md-12">
                <label class="form-label fw-semibold text-secondary">Phone Number</label>
                <input type="tel" id="reg_patient_phone" name="patient_phone" class="form-control form-control-premium" placeholder="e.g. +91 98765 43210" value="<?= htmlspecialchars($old['patient_phone'] ?? '') ?>" required>
                <div class="invalid-feedback">Phone number must be between 10 and 15 digits (may include +, -, and spaces).</div>
              </div>

              <!-- Address -->
              <div class="col-md-12">
                <label class="form-label fw-semibold text-secondary">Residential Address</label>
                <textarea id="reg_patient_address" name="patient_address" class="form-control form-control-premium" rows="3" placeholder="Enter Full Street Address" required><?= htmlspecialchars($old['patient_address'] ?? '') ?></textarea>
                <div class="invalid-feedback">Please enter your residential address (10-150 characters).</div>
              </div>

              <!-- Submit -->
              <div class="col-md-12 text-end mt-4">
                <button type="submit" name="submit" class="btn btn-premium btn-lg w-100">Create Patient Account</button>
              </div>
            </form>
            
            <div class="text-center mt-4">
              <span class="text-muted">Already registered?</span> <a href="login.php" class="text-decoration-none fw-semibold">Sign In here</a>
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

<!-- Client-side Interactive Form Validations -->
<script>
$(document).ready(function() {
    const $form = $('#patientRegForm');
    const $fname = $('#reg_patient_fname');
    const $mname = $('#reg_patient_mname');
    const $lname = $('#reg_patient_lname');
    const $email = $('#reg_patient_email');
    const $password = $('#reg_patient_password');
    const $dob = $('#reg_patient_dob');
    const $phone = $('#reg_patient_phone');
    const $address = $('#reg_patient_address');
    
    const nameRegex = /^[a-zA-Z\s]{1,50}$/;
    const mnameRegex = /^[a-zA-Z\s]{0,50}$/;
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const phoneRegex = /^[0-9\+\-\s]{10,15}$/;
    const pwRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

    function markField(field, isValid) {
        if (isValid) {
            field.removeClass('is-invalid').addClass('is-valid');
        } else {
            field.removeClass('is-valid').addClass('is-invalid');
        }
    }

    // Name validations
    $fname.on('input blur', function() {
        markField($fname, nameRegex.test($(this).val().trim()));
    });
    
    $mname.on('input blur', function() {
        const val = $(this).val().trim();
        markField($mname, val === "" || mnameRegex.test(val));
    });

    $lname.on('input blur', function() {
        markField($lname, nameRegex.test($(this).val().trim()));
    });

    // Email validation
    $email.on('input blur', function() {
        markField($email, emailRegex.test($(this).val().trim()));
    });

    // Phone validation
    $phone.on('input blur', function() {
        markField($phone, phoneRegex.test($(this).val().trim()));
    });

    // Address validation
    $address.on('input blur', function() {
        const len = $(this).val().trim().length;
        markField($address, len >= 10 && len <= 200);
    });

    // DOB validation
    $dob.on('input blur change', function() {
        const dobVal = $(this).val();
        if (!dobVal) {
            markField($dob, false);
            return;
        }
        const selectedDate = new Date(dobVal);
        const today = new Date();
        markField($dob, selectedDate < today);
    });

    // Password strength & validation
    $password.on('focus', function() {
        $('#pw-strength-wrapper').removeClass('d-none');
    });

    $password.on('input blur', function() {
        const val = $(this).val();
        let score = 0;
        
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[@$!%*?&]/.test(val)) score++;

        const $bar = $('#pw-strength-bar');
        const $text = $('#pw-strength-text');

        // Update progress bar
        const width = (score / 5) * 100;
        $bar.css('width', width + '%');

        if (score <= 2) {
            $bar.removeClass('bg-warning bg-success').addClass('bg-danger');
            $text.text('Weak password').removeClass('text-warning text-success').addClass('text-danger');
        } else if (score <= 4) {
            $bar.removeClass('bg-danger bg-success').addClass('bg-warning');
            $text.text('Medium strength').removeClass('text-danger text-success').addClass('text-warning');
        } else {
            $bar.removeClass('bg-danger bg-warning').addClass('bg-success');
            $text.text('Strong password').removeClass('text-danger text-warning').addClass('text-success');
        }

        markField($password, pwRegex.test(val));
    });

    // Handle submit
    $form.on('submit', function(e) {
        $fname.trigger('blur');
        $mname.trigger('blur');
        $lname.trigger('blur');
        $email.trigger('blur');
        $phone.trigger('blur');
        $address.trigger('blur');
        $dob.trigger('blur');
        $password.trigger('blur');

        if ($form.find('.is-invalid').length > 0) {
            e.preventDefault();
            // Scroll to the first invalid field
            $('html, body').animate({
                scrollTop: $form.find('.is-invalid').first().offset().top - 120
            }, 300);
        }
    });
});
</script>
</body>
</html>