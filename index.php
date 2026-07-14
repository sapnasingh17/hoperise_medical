<?php
include_once("includes/main_header.php");

// Handle inline booking requests securely
if(isset($_POST['submit'])) {
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $category_id = intval($_POST['category_id']);
    $sub_category_id = intval($_POST['sub_category_id']);
    $service_id = intval($_POST['service_id']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $appointment_description = $_POST['appointment_description'];
    
    $stmt = mysqli_prepare($conn, "INSERT INTO hs_appointment(patient_id, doctor_id, category_id, sub_category_id, service_id, appointment_date, appointment_time, appointment_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iiiiisss", $patient_id, $doctor_id, $category_id, $sub_category_id, $service_id, $appointment_date, $appointment_time, $appointment_description);
        $query = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($query) {
            $new_app_id = mysqli_insert_id($conn);
            
            // Log to timeline
            $t_sql = "INSERT INTO hs_appointment_timeline (appointment_id, status_action, action_by, comments) VALUES (?, 'Booked', 'patient', 'Appointment requested by patient')";
            $t_stmt = mysqli_prepare($conn, $t_sql);
            if ($t_stmt) {
                mysqli_stmt_bind_param($t_stmt, "i", $new_app_id);
                mysqli_stmt_execute($t_stmt);
                mysqli_stmt_close($t_stmt);
            }
            
            // Trigger email notification
            include_once('email_helper.php');
            send_appointment_email($conn, $new_app_id, 'booked');

            echo "<script>alert('Appointment Requested Successfully! Redirecting to payment...'); window.location.href='payment.php?appointment_id=" . $new_app_id . "';</script>";
            exit();
        }
    }
}
?>
<body>
<div class="page-wrapper">
  <!-- Top Headers -->
  <?php include("includes/top_header.php"); ?>
  <?php include("includes/upper_header.php"); ?>
  
  <!-- Glass Navbar -->
  <?php include("includes/navbar.php"); ?>

  <!-- Premium Hero Section -->
  <section class="py-5 position-relative bg-primary-light overflow-hidden" style="min-height: 80vh; display: flex; align-items: center;">
    <div class="container py-5 position-relative z-1">
      <div class="row align-items-center g-5">
        <div class="col-lg-6 animate-fade-in-up">
          <span class="badge bg-primary text-white px-3 py-2 rounded-pill mb-3 fw-semibold"><i class="bi bi-shield-check me-2"></i>Certified Medical Experts</span>
          <h1 class="display-3 fw-extrabold text-dark mb-4" style="line-height: 1.1;">Your Health, <br><span class="text-primary">Our Ultimate Mission</span></h1>
          <p class="fs-5 text-secondary mb-5">Hope Rise Medical provides cutting-edge diagnostics, advanced orthopedic procedures, and round-the-clock emergency assistance tailored to your wellness.</p>
          <div class="d-flex flex-wrap gap-3">
            <a href="add_appointment.php" class="btn btn-premium btn-lg px-4 py-3"><i class="bi bi-calendar-check me-2"></i>Schedule Visit</a>
            <a href="about.php" class="btn btn-premium-outline btn-lg px-4 py-3">Learn More</a>
          </div>
        </div>
        <div class="col-lg-6 text-center animate-fade-in-up" style="animation-delay: 0.2s;">
          <div class="position-relative d-inline-block">
            <img src="images/slider/img2.png" alt="Clinical Center" class="img-fluid rounded shadow-lg border border-white" style="max-height: 480px; object-fit: cover; border-width: 10px !important;">
            <div class="position-absolute bottom-0 start-0 translate-middle-x glass-card p-3 text-center d-flex flex-column align-items-center gap-2 mb-4 ms-5" style="min-width: 220px;">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="bi bi-telephone-inbound-fill fs-5"></i>
              </div>
              <div>
                <div class="small text-muted fw-semibold mb-1" style="font-size: 14px !important;">24/7 Helpline</div>
                <div class="fw-bold text-dark text-nowrap" style="font-size: 16px !important;">+91 2215 463 895</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Decorative Blur Background -->
    <div class="position-absolute end-0 top-0 translate-middle bg-primary opacity-10 rounded-circle" style="width: 500px; height: 500px; filter: blur(100px);"></div>
  </section>

  <!-- Emergency & Service CTAs -->
  <section class="py-5" style="margin-top: -60px;">
    <div class="container position-relative z-3">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="glass-card p-4 border-start border-4 border-danger h-100">
            <h5 class="fw-bold text-dark"><i class="bi bi-telephone-fill text-danger me-2"></i>Emergency Cases</h5>
            <p class="text-secondary small mt-2">Critical care support response active 24/7. Get in touch with our triage team.</p>
            <a href="tel:3265896532" class="text-danger fw-bold text-decoration-none">+91 3265 896 532</a>
          </div>
        </div>
        <div class="col-md-4">
          <div class="glass-card p-4 border-start border-4 border-primary h-100">
            <h5 class="fw-bold text-dark"><i class="bi bi-clock-fill text-primary me-2"></i>Working Hours</h5>
            <ul class="list-unstyled small text-secondary mt-2 mb-0 d-flex flex-column gap-1">
              <li class="d-flex justify-content-between"><span>Mon - Fri:</span> <span>8:00 AM - 5:00 PM</span></li>
              <li class="d-flex justify-content-between"><span>Saturday:</span> <span>8:00 AM - 12:00 PM</span></li>
              <li class="d-flex justify-content-between"><span>Sunday:</span> <span class="text-danger fw-semibold">Emergency Only</span></li>
            </ul>
          </div>
        </div>
        <div class="col-md-4">
          <div class="glass-card p-4 border-start border-4 border-accent h-100">
            <h5 class="fw-bold text-dark"><i class="bi bi-activity text-accent me-2"></i>Primary Care</h5>
            <p class="text-secondary small mt-2">Preventive checkups, specialist consultation, and diagnostic reviews.</p>
            <a href="service.php" class="text-accent fw-bold text-decoration-none">Explore Services <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <section class="py-5">
    <div class="container">
      <div class="section-title text-center mb-5">
        <span class="text-primary fw-semibold uppercase tracking-wider">Clinical Specialty</span>
        <h2 class="fw-bold text-dark mt-2">Our Healthcare Specialties</h2>
        <!-- <p class="text-muted col-lg-6 mx-auto mt-2">We provide advanced clinical capabilities handled by qualified practitioners using modern equipment.</p> -->
      </div>

      <div class="row g-4">
        <!-- Specialty Card 1 -->
        <div class="col-lg-3 col-md-6">
          <div class="glass-card p-4 text-center h-100">
            <div class="bg-primary-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-4" style="width: 60px; height: 60px;">
              <i class="bi bi-layers-fill fs-3"></i>
            </div>
            <h5 class="fw-bold text-dark mb-3">Orthopedics</h5>
            <p class="text-secondary small mb-0">Sports injury recovery, ligament repairs, knee reconstructions, and joint arthritis procedures.</p>
          </div>
        </div>
        <!-- Specialty Card 2 -->
        <div class="col-lg-3 col-md-6">
          <div class="glass-card p-4 text-center h-100">
            <div class="bg-primary-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-4" style="width: 60px; height: 60px;">
              <i class="bi bi-bandaid-fill fs-3"></i>
            </div>
            <h5 class="fw-bold text-dark mb-3">General Surgery</h5>
            <p class="text-secondary small mb-0">Minimally invasive keyhole procedures, appendectomies, and abdominal soft tissue repairs.</p>
          </div>
        </div>
        <!-- Specialty Card 3 -->
        <div class="col-lg-3 col-md-6">
          <div class="glass-card p-4 text-center h-100">
            <div class="bg-primary-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-4" style="width: 60px; height: 60px;">
              <i class="bi bi-heart-pulse-fill fs-3"></i>
            </div>
            <h5 class="fw-bold text-dark mb-3">Cardiology</h5>
            <p class="text-secondary small mb-0">Heart rate tracking, electrocardiograms, hypertension checks, and lipid balance monitoring.</p>
          </div>
        </div>
        <!-- Specialty Card 4 -->
        <div class="col-lg-3 col-md-6">
          <div class="glass-card p-4 text-center h-100">
            <div class="bg-primary-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-4" style="width: 60px; height: 60px;">
              <i class="bi bi-capsule fs-3"></i>
            </div>
            <h5 class="fw-bold text-dark mb-3">Diagnostics</h5>
            <p class="text-secondary small mb-0">Advanced laboratory screenings, diagnostic imaging scans, and pathology evaluations.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Interactive Statistics -->
  <section class="py-5 bg-primary text-white position-relative">
    <div class="container py-4">
      <div class="row text-center g-4">
        <div class="col-md-3 col-6">
          <h2 class="display-4 fw-bold text-white mb-1">15k+</h2>
          <div class="fw-bold text-uppercase small" style="letter-spacing: 1px; color: #f1f5f9 !important;">Happy Patients</div>
        </div>
        <div class="col-md-3 col-6">
          <h2 class="display-4 fw-bold text-white mb-1">50+</h2>
          <div class="fw-bold text-uppercase small" style="letter-spacing: 1px; color: #f1f5f9 !important;">Top Doctors</div>
        </div>
        <div class="col-md-3 col-6">
          <h2 class="display-4 fw-bold text-white mb-1">25+</h2>
          <div class="fw-bold text-uppercase small" style="letter-spacing: 1px; color: #f1f5f9 !important;">Departments</div>
        </div>
        <div class="col-md-3 col-6">
          <h2 class="display-4 fw-bold text-white mb-1">10+</h2>
          <div class="fw-bold text-uppercase small" style="letter-spacing: 1px; color: #f1f5f9 !important;">Years Service</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Expert Doctors Section -->
  <section class="py-5">
    <div class="container">
      <div class="section-title text-center mb-5">
        <span class="text-primary fw-semibold">Clinical Staff</span>
        <h2 class="fw-bold text-dark mt-2">Meet Our Expert Doctors</h2>
        <!-- <p class="text-muted col-lg-6 mx-auto mt-2">A dedicated team of board-certified clinical specialists delivering patient-centric recoveries.</p> -->
      </div>

      <div class="row g-4">
        <?php
        $doctor_query = mysqli_query($conn, "SELECT * FROM hs_doctor LIMIT 3");
        if ($doctor_query && mysqli_num_rows($doctor_query) > 0) {
            while($doctor_row = mysqli_fetch_array($doctor_query)) {
                // Dynamically fetch doctor profile image if uploaded, else load placeholder
                $doc_img = "images/team/doctor_img.webp";
                if (!empty($doctor_row['doctor_thumb']) && file_exists(__DIR__ . "/admin/uploads/" . $doctor_row['doctor_thumb'])) {
                    $doc_img = "admin/uploads/" . $doctor_row['doctor_thumb'];
                }
        ?>
        <div class="col-lg-4 col-md-6">
          <div class="glass-card overflow-hidden text-center h-100">
            <img src="<?= $doc_img ?>" alt="Doctor picture" class="img-fluid w-100" style="height: 280px; object-fit: cover;">
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-1">Dr. <?= htmlspecialchars($doctor_row['doctor_fname'] . " " . $doctor_row['doctor_lname']) ?></h5>
              <p class="text-primary small mb-3"><?= htmlspecialchars($doctor_row['doctor_phone']) ?></p>
              <a href="team.php" class="btn btn-premium btn-sm w-100">View Roster Profile</a>
            </div>
          </div>
        </div>
        <?php
            }
        }
        ?>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="py-5 bg-light-emphasis bg-opacity-10">
    <div class="container">
      <div class="section-title text-center mb-5">
        <span class="text-primary fw-semibold">Patient FAQ</span>
        <h2 class="fw-bold text-dark mt-2">Frequently Asked Questions</h2>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="accordion" id="faqAccordion">
            <div class="accordion-item glass-card mb-3 border-0">
              <h2 class="accordion-header" id="faqOne">
                <button class="accordion-button bg-transparent fw-bold text-dark collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                  How can I book an appointment online?
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">
                  You can register a patient profile on our Registration screen, sign in, select the "Book Appointment" button on your dashboard, and pick a preferred date and department.
                </div>
              </div>
            </div>
            
            <div class="accordion-item glass-card mb-3 border-0">
              <h2 class="accordion-header" id="faqTwo">
                <button class="accordion-button bg-transparent fw-bold text-dark collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  What are the emergency hours at Hope Rise?
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-secondary small">
                  Our critical care units, triage networks, and ambulances are active 24 hours a day, 365 days a year. Call our emergency helpline directly for assistance.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Booking Form Area -->
  <section class="py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="glass-card p-5">
            <h3 class="fw-bold mb-4 text-center text-dark"><i class="bi bi-calendar-check-fill text-primary me-2"></i>Request Consultation</h3>
            
            <form action="" method="post" class="row g-4">
              <!-- Patient Select Dropdown -->
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary">Select Patient</label>
                <select name="patient_id" class="form-select form-control-premium" required>
                  <option value="" disabled selected>Choose Patient Profile</option>
                  <?php
                  $patient_data = mysqli_query($conn, "SELECT * FROM hs_patient");
                  while($patient_row = mysqli_fetch_array($patient_data)) {
                  ?>
                  <option value="<?= $patient_row['patient_id']; ?>"><?= htmlspecialchars($patient_row['patient_fname'] . " " . $patient_row['patient_lname']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <!-- Doctor Select Dropdown -->
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary">Assign Doctor</label>
                <select name="doctor_id" class="form-select form-control-premium" required>
                  <option value="" disabled selected>Choose Medical Practitioner</option>
                  <?php
                  $doctor_data = mysqli_query($conn, "SELECT * FROM hs_doctor");
                  while($doctor_row = mysqli_fetch_array($doctor_data)) {
                  ?>
                  <option value="<?= $doctor_row['doctor_id']; ?>">Dr. <?= htmlspecialchars($doctor_row['doctor_fname'] . " " . $doctor_row['doctor_lname']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <!-- Specialty Category Dropdown -->
              <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">Specialty</label>
                <select name="category_id" class="form-select form-control-premium" required>
                  <option value="" disabled selected>Choose Specialty</option>
                  <?php
                  $category_data = mysqli_query($conn, "SELECT * FROM hs_category");
                  while($category_row = mysqli_fetch_array($category_data)) {
                  ?>
                  <option value="<?= $category_row['category_id']; ?>"><?= htmlspecialchars($category_row['category_title']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <!-- Sub Specialty Category Dropdown -->
              <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">Sub-Specialty</label>
                <select name="sub_category_id" class="form-select form-control-premium" required>
                  <option value="" disabled selected>Choose Sub-Specialty</option>
                  <?php
                  $sub_data = mysqli_query($conn, "SELECT * FROM hs_sub_category");
                  while($sub_row = mysqli_fetch_array($sub_data)) {
                  ?>
                  <option value="<?= $sub_row['sub_category_id']; ?>"><?= htmlspecialchars($sub_row['sub_category_title']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <!-- Services Dropdown -->
              <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary">Requested Service</label>
                <select name="service_id" class="form-select form-control-premium" required>
                  <option value="" disabled selected>Choose Service</option>
                  <?php
                  $service_data = mysqli_query($conn, "SELECT * FROM hs_service");
                  while($service_row = mysqli_fetch_array($service_data)) {
                  ?>
                  <option value="<?= $service_row['service_id']; ?>"><?= htmlspecialchars($service_row['service_title']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <!-- Appointment Date -->
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary">Preferred Date</label>
                <input type="date" name="appointment_date" class="form-control form-control-premium" required>
              </div>

              <!-- Appointment Time -->
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary">Preferred Time</label>
                <input type="time" name="appointment_time" class="form-control form-control-premium" required>
              </div>

              <!-- Description -->
              <div class="col-md-12">
                <label class="form-label fw-semibold text-secondary">Symtoms / Notes</label>
                <textarea name="appointment_description" class="form-control form-control-premium" rows="3" placeholder="Describe symptoms briefly..." required></textarea>
              </div>

              <!-- Submit -->
              <div class="col-md-12 mt-4 text-center">
                <button type="submit" name="submit" class="btn btn-premium btn-lg w-100">Submit Booking Request</button>
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
