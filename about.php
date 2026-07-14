<?php
include_once("dbcon.php");

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
            echo "<script>alert('Appointment Requested Successfully!'); window.location.href='about.php';</script>";
            exit();
        }
    }
}

include_once("includes/main_header.php");
?>
<!-- Google Fonts & AOS Linkages -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link href="css/about.css?v=<?= time(); ?>" rel="stylesheet" />

<body class="about-page-content">
<div class="page-wrapper">
  <!-- Headers -->
  <?php include("includes/top_header.php"); ?>
  <?php include("includes/upper_header.php"); ?>
  <?php include("includes/navbar.php"); ?>

  <!-- SECTION 1: PREMIUM HERO SECTION -->
  <section class="hero-about" style="background-image: url('images/slider/slider-bg-2.jpg');">
    <div class="hero-overlay"></div>
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6" data-aos="fade-right">
          <span class="badge badge-hero px-3 py-2 rounded-pill mb-3 fw-semibold">ABOUT HOPE RISE HOSPITAL</span>
          <h1 class="display-4 fw-bold text-white mb-4">Providing Trusted Healthcare for Every Patient</h1>
          <p class="text-white-50 fs-5 mb-5">Hope Rise Hospital is committed to providing advanced medical care with experienced doctors, modern technology, and compassionate patient services.</p>
          <div class="d-flex flex-wrap gap-3">
            <a href="add_appointment.php" class="btn btn-gradient-primary btn-lg px-4 py-3 rounded-pill fw-semibold"><i class="fa fa-calendar-check me-2"></i>Book Appointment</a>
            <a href="contact.php" class="btn btn-glass-light btn-lg px-4 py-3 rounded-pill fw-semibold"><i class="fa fa-paper-plane me-2"></i>Contact Us</a>
          </div>
        </div>
        <div class="col-lg-6 position-relative" data-aos="fade-left">
          <div class="floating-elements">
            <div class="floating-icon fi-1"><i class="fa fa-stethoscope animate-heartbeat"></i></div>
            <div class="floating-icon fi-2"><i class="fa fa-pills"></i></div>
            <div class="floating-icon fi-3"><i class="fa fa-heartpulse"></i></div>
            
            <div class="about-glass-card p-4 mx-auto text-center" style="max-width: 450px; border-radius: 24px; position: relative; z-index: 2;">
              <div class="position-relative overflow-hidden rounded-4 mb-4">
                <img src="images/team/doctor_img.webp" alt="Doctor Illustration" class="img-fluid rounded-4 animate-float" style="max-height: 280px; object-fit: cover;">
              </div>
              <h5 class="text-dark fw-bold mb-2">24/7 Virtual Diagnostics</h5>
              <p class="text-secondary small mb-0">Direct consultation with South Gujarat's top medical panels.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 2: WHO WE ARE -->
  <section class="py-5 bg-white">
    <div class="container py-5">
      <div class="row align-items-center g-5">
        <div class="col-lg-6" data-aos="fade-right">
          <img src="images/services/img-04.jpg" class="img-fluid rounded-4 shadow-lg border border-white" style="border-width: 10px !important;" alt="Who We Are">
        </div>
        <div class="col-lg-6" data-aos="fade-left">
          <span class="text-primary fw-bold text-uppercase tracking-wider d-block mb-2">WHO WE ARE</span>
          <h2 class="display-6 fw-bold text-dark mb-4">A Legacy of Clinical Care & Surgical Excellence</h2>
          <p class="text-secondary mb-5">At Hope Rise Medical, we place patient health and well-being at the core of all clinical departments. Led by internationally fellowship-trained joint specialist Dr. Krunal Shah, our teams utilize top-tier surgical techniques and evidence-based medicine to ensure safe patient rehabilitation.</p>
          
          <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-sm-6">
              <div class="feature-card">
                <div class="icon-box"><i class="fa fa-user-md"></i></div>
                <h5 class="fw-bold text-dark mb-2">Experienced Doctors</h5>
                <p class="text-secondary small mb-0">Our surgeons hold advanced credentials from premier European medical institutions.</p>
              </div>
            </div>
            <!-- Feature 2 -->
            <div class="col-sm-6">
              <div class="feature-card">
                <div class="icon-box"><i class="fa fa-truck-medical"></i></div>
                <h5 class="fw-bold text-dark mb-2">24/7 Emergency</h5>
                <p class="text-secondary small mb-0">Fully-equipped emergency beds and trauma specialists on active call.</p>
              </div>
            </div>
            <!-- Feature 3 -->
            <div class="col-sm-6">
              <div class="feature-card">
                <div class="icon-box"><i class="fa fa-microscope"></i></div>
                <h5 class="fw-bold text-dark mb-2">Advanced Tech</h5>
                <p class="text-secondary small mb-0">Computer-assisted orthopedic systems and state-of-the-art diagnostics.</p>
              </div>
            </div>
            <!-- Feature 4 -->
            <div class="col-sm-6">
              <div class="feature-card">
                <div class="icon-box"><i class="fa fa-hand-holding-heart"></i></div>
                <h5 class="fw-bold text-dark mb-2">Patient Centered</h5>
                <p class="text-secondary small mb-0">Comprehensive clinical workflows tailored to individual recovery paths.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  
 
  <!-- SECTION 4: MISSION AND VISION -->
  <section class="py-5 bg-light">
    <div class="container py-5">
      <div class="row g-4">
        <div class="col-md-6" data-aos="fade-right">
          <div class="about-glass-card p-5 h-100">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="fa fa-bullseye fs-3"></i></div>
              <h3 class="fw-bold text-dark mb-0">Our Mission</h3>
            </div>
            <p class="text-secondary mb-0 fs-5 leading-relaxed">To deliver patient-centric healthcare of the highest standards by combining leading medical minds, compassionate staff, and cutting-edge technology to assure affordable clinical recovery.</p>
          </div>
        </div>
        <div class="col-md-6" data-aos="fade-left">
          <div class="about-glass-card p-5 h-100">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="bg-accent text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: var(--accent-color) !important;"><i class="fa fa-eye fs-3"></i></div>
              <h3 class="fw-bold text-dark mb-0">Our Vision</h3>
            </div>
            <p class="text-secondary mb-0 fs-5 leading-relaxed">To emerge as the region's most trusted multispecialty hospital, recognized globally for clinical excellence, nursing commitment, ethical practices, and post-surgery patient rehabilitation.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 5: OUR VALUES -->
  <section class="py-5 bg-white">
    <div class="container py-5">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="text-primary fw-bold text-uppercase tracking-wider">OUR CORE VALUES</span>
        <h2 class="fw-bold text-dark mt-2">The Pillars of Hope Rise</h2>
      </div>
      
      <div class="row g-4">
        <!-- Value 1 -->
        <div class="col-lg-4 col-md-6" data-aos="fade-up">
          <div class="value-card">
            <div class="value-icon"><i class="fa fa-heart"></i></div>
            <h5 class="fw-bold text-dark mb-3">Compassion</h5>
            <p class="text-secondary mb-0">Treating every patient and relative with absolute kindness, empathy, and professional warmth.</p>
          </div>
        </div>
        <!-- Value 2 -->
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
          <div class="value-card">
            <div class="value-icon"><i class="fa fa-shield-halved"></i></div>
            <h5 class="fw-bold text-dark mb-3">Integrity</h5>
            <p class="text-secondary mb-0">Maintaining complete transparency, ethical medical operations, and absolute patient confidentiality.</p>
          </div>
        </div>
        <!-- Value 3 -->
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
          <div class="value-card">
            <div class="value-icon"><i class="fa fa-lightbulb"></i></div>
            <h5 class="fw-bold text-dark mb-3">Innovation</h5>
            <p class="text-secondary mb-0">Pioneering clinical excellence using computer-guided operations and digital health systems.</p>
          </div>
        </div>
        <!-- Value 4 -->
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
          <div class="value-card">
            <div class="value-icon"><i class="fa fa-hospital-user"></i></div>
            <h5 class="fw-bold text-dark mb-3">Patient First</h5>
            <p class="text-secondary mb-0">Aligning all clinical operations to optimize patient safety, healing speed, and general comfort.</p>
          </div>
        </div>
        <!-- Value 5 -->
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
          <div class="value-card">
            <div class="value-icon"><i class="fa fa-award"></i></div>
            <h5 class="fw-bold text-dark mb-3">Excellence</h5>
            <p class="text-secondary mb-0">Relentlessly pushing standards in care pathways, post-op support, and hygiene maintenance.</p>
          </div>
        </div>
        <!-- Value 6 -->
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
          <div class="value-card">
            <div class="value-icon"><i class="fa fa-users"></i></div>
            <h5 class="fw-bold text-dark mb-3">Trust</h5>
            <p class="text-secondary mb-0">Building lasting relationships with patients based on reliable results and clinical integrity.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 6: WHY CHOOSE US -->
  <section class="py-5 bg-light">
    <div class="container py-5">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="text-primary fw-bold text-uppercase tracking-wider">WHY CHOOSE US</span>
        <h2 class="fw-bold text-dark mt-2">Clinical Advantages We Provide</h2>
      </div>
      
      <div class="row g-4">
        <div class="col-lg-3 col-md-6" data-aos="zoom-in">
          <div class="choose-card text-center">
            <i class="fa fa-user-doctor mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Experienced Specialists</h5>
            <p class="text-secondary small mb-0">Fellowship-trained surgical consultants from top institutes.</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="100">
          <div class="choose-card text-center">
            <i class="fa fa-bed-pulse mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Modern Operation Theatre</h5>
            <p class="text-secondary small mb-0">Equipped with HEPA filters and laminar airflow systems.</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="200">
          <div class="choose-card text-center">
            <i class="fa fa-file-medical mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Digital Patient Records</h5>
            <p class="text-secondary small mb-0">Access diagnostics instantly via centralized secure portals.</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="300">
          <div class="choose-card text-center">
            <i class="fa fa-robot mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">AI Appointment System</h5>
            <p class="text-secondary small mb-0">Intelligent slots matching based on department queue sizes.</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="400">
          <div class="choose-card text-center">
            <i class="fa fa-flask-vial mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Advanced Laboratory</h5>
            <p class="text-secondary small mb-0">On-site hematology, pathology, and diagnostic screenings.</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="500">
          <div class="choose-card text-center">
            <i class="fa fa-truck-medical mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Emergency Ambulance</h5>
            <p class="text-secondary small mb-0">GPS-integrated trauma vans with advanced life support.</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="600">
          <div class="choose-card text-center">
            <i class="fa fa-laptop-medical mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Online Consultation</h5>
            <p class="text-secondary small mb-0">Virtual diagnostics for remote review and second opinion.</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6" data-aos="zoom-in" data-aos-delay="700">
          <div class="choose-card text-center">
            <i class="fa fa-wallet mb-3"></i>
            <h5 class="fw-bold text-dark mb-2">Affordable Healthcare</h5>
            <p class="text-secondary small mb-0">Fully compatible with regional and state insurance systems.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 7: OUR DEPARTMENTS -->
  <section class="py-5 bg-white">
    <div class="container py-5">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="text-primary fw-bold text-uppercase tracking-wider">OUR CLINICAL WINGS</span>
        <h2 class="fw-bold text-dark mt-2">Specialized Clinical Departments</h2>
      </div>
      
      <div class="row g-4">
        <!-- Dept 1 -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up">
          <div class="dept-card h-100">
            <div class="dept-img-wrapper">
              <img src="images/gallery/gallery-01.jpg" alt="Orthopedic">
              <div class="dept-icon-floating"><i class="fa fa-bone"></i></div>
            </div>
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-2">Orthopedics</h5>
              <p class="text-secondary small mb-0">Joint replacements, ligament repairs, and advanced trauma recovery.</p>
            </div>
          </div>
        </div>
        <!-- Dept 2 -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
          <div class="dept-card h-100">
            <div class="dept-img-wrapper">
              <img src="images/gallery/gallery-02.jpg" alt="Cardiology">
              <div class="dept-icon-floating"><i class="fa fa-heart-pulse"></i></div>
            </div>
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-2">Cardiology</h5>
              <p class="text-secondary small mb-0">Comprehensive heart diagnostics, coronary care, and vascular support.</p>
            </div>
          </div>
        </div>
        <!-- Dept 3 -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
          <div class="dept-card h-100">
            <div class="dept-img-wrapper">
              <img src="images/gallery/gallery-03.jpg" alt="Neurology">
              <div class="dept-icon-floating"><i class="fa fa-brain"></i></div>
            </div>
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-2">Neurology</h5>
              <p class="text-secondary small mb-0">Surgical and diagnostic support for brain, spine, and nerve disorders.</p>
            </div>
          </div>
        </div>
        <!-- Dept 4 -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
          <div class="dept-card h-100">
            <div class="dept-img-wrapper">
              <img src="images/gallery/gallery-04.jpg" alt="Pediatrics">
              <div class="dept-icon-floating"><i class="fa fa-child"></i></div>
            </div>
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-2">Pediatrics</h5>
              <p class="text-secondary small mb-0">Dedicated neonatal recovery chambers and clinical care for infants.</p>
            </div>
          </div>
        </div>
        <!-- Dept 5 -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
          <div class="dept-card h-100">
            <div class="dept-img-wrapper">
              <img src="images/gallery/gallery-05.jpg" alt="Dentistry">
              <div class="dept-icon-floating"><i class="fa fa-tooth"></i></div>
            </div>
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-2">Dentistry</h5>
              <p class="text-secondary small mb-0">Complete periodontal treatments, root canals, and cosmetic surgeries.</p>
            </div>
          </div>
        </div>
        <!-- Dept 6 -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="500">
          <div class="dept-card h-100">
            <div class="dept-img-wrapper">
              <img src="images/gallery/gallery-06.jpg" alt="Radiology">
              <div class="dept-icon-floating"><i class="fa fa-x-ray"></i></div>
            </div>
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-2">Radiology</h5>
              <p class="text-secondary small mb-0">High-resolution MRIs, ultra-precise CT scans, and digital X-rays.</p>
            </div>
          </div>
        </div>
        <!-- Dept 7 -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="600">
          <div class="dept-card h-100">
            <div class="dept-img-wrapper">
              <img src="images/gallery/gallery-07.jpg" alt="General Surgery">
              <div class="dept-icon-floating"><i class="fa fa-user-injured"></i></div>
            </div>
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-2">General Surgery</h5>
              <p class="text-secondary small mb-0">Minimally invasive laparoscopic surgeries and trauma operations.</p>
            </div>
          </div>
        </div>
        <!-- Dept 8 -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="700">
          <div class="dept-card h-100">
            <div class="dept-img-wrapper">
              <img src="images/gallery/gallery-08.jpg" alt="Emergency">
              <div class="dept-icon-floating"><i class="fa fa-kit-medical"></i></div>
            </div>
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-2">Emergency Care</h5>
              <p class="text-secondary small mb-0">Immediate resuscitation, trauma stabilization, and critical care units.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 8: MEET OUR SPECIALISTS (Database-Connected) -->
  <section class="py-5 bg-light">
    <div class="container py-5">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="text-primary fw-bold text-uppercase tracking-wider">OUR CONSULTANTS</span>
        <h2 class="fw-bold text-dark mt-2">Meet Our Specialists</h2>
      </div>
      
      <div class="row g-4 justify-content-center">
        <?php
        $doc_query = "SELECT d.*, c.category_title FROM hs_doctor d 
                      LEFT JOIN hs_category c ON d.category_id = c.category_id 
                      WHERE d.status = 1 LIMIT 4";
        $doc_res = mysqli_query($conn, $doc_query);
        $delay = 0;
        
        while ($doc_row = mysqli_fetch_assoc($doc_res)) {
            // Find DOJ and calculate experience years
            $doj_year = date('Y', strtotime($doc_row['doctor_doj']));
            $current_year = date('Y');
            $exp_years = max(2, $current_year - $doj_year);
            
            // Set doctor image safely
            $doc_img = 'images/team/doctor_img.webp';
            if (!empty($doc_row['doctor_thumb'])) {
                if (file_exists('uploads/' . $doc_row['doctor_thumb'])) {
                    $doc_img = 'uploads/' . $doc_row['doctor_thumb'];
                } elseif (file_exists('admin/uploads/' . $doc_row['doctor_thumb'])) {
                    $doc_img = 'admin/uploads/' . $doc_row['doctor_thumb'];
                }
            }
        ?>
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?= $delay; ?>">
          <div class="doctor-card h-100">
            <div class="doctor-img-box">
              <img src="<?= $doc_img; ?>" alt="Dr. <?= htmlspecialchars($doc_row['doctor_fname']); ?>">
              <div class="doctor-socials">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
              </div>
            </div>
            <div class="doctor-info-box text-center">
              <h5 class="fw-bold text-dark mb-1">Dr. <?= htmlspecialchars($doc_row['doctor_fname'] . ' ' . $doc_row['doctor_lname']); ?></h5>
              <p class="text-primary small mb-3"><?= htmlspecialchars($doc_row['category_title'] ?? 'Medical Consultant'); ?></p>
              
              <div class="d-flex justify-content-around text-start border-top pt-3 mb-4">
                <div>
                  <small class="text-muted d-block">Experience</small>
                  <strong class="text-dark small"><?= $exp_years; ?>+ Years</strong>
                </div>
                <div class="border-start ps-3">
                  <small class="text-muted d-block">Gender</small>
                  <strong class="text-dark small"><?= htmlspecialchars($doc_row['doctor_gender']); ?></strong>
                </div>
              </div>
              
              <a href="add_appointment.php" class="btn btn-outline-primary btn-sm w-100 rounded-pill"><i class="fa fa-calendar-check me-2"></i>Book Consultation</a>
            </div>
          </div>
        </div>
        <?php 
            $delay += 100;
        } 
        ?>
      </div>
    </div>
  </section>

 
 

  <!-- SECTION 10: PATIENT TESTIMONIALS -->
  <section class="py-5 bg-light">
    <div class="container py-5">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="text-primary fw-bold text-uppercase tracking-wider">PATIENT VOICES</span>
        <h2 class="fw-bold text-dark mt-2">What Our Patients Say</h2>
      </div>
      
      <div class="row">
        <div class="col-lg-12">
          <div class="testimonial-slider">
            <!-- Testimonial 1 -->
            <div class="testimonial-card">
              <i class="fa fa-quote-right quote-icon"></i>
              <div class="d-flex align-items-center gap-3 mb-4">
                <img src="images/testimonials/1.jpg" alt="Patient 1" class="testimonial-avatar">
                <div>
                  <h6 class="fw-bold text-dark mb-1">Ramesh Patel</h6>
                  <small class="text-muted">Surat</small>
                </div>
              </div>
              <div class="text-warning mb-3">
                <i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
              </div>
              <p class="text-secondary mb-0">"Dr. Krunal Shah performed a knee replacement surgery on my mother last year. The care, hygiene levels, and post-surgery rehabilitation sessions were outstanding. Today she is walking absolutely pain-free!"</p>
            </div>
            <!-- Testimonial 2 -->
            <div class="testimonial-card">
              <i class="fa fa-quote-right quote-icon"></i>
              <div class="d-flex align-items-center gap-3 mb-4">
                <img src="images/testimonials/2.png" alt="Patient 2" class="testimonial-avatar">
                <div>
                  <h6 class="fw-bold text-dark mb-1">Priya Sharma</h6>
                  <small class="text-muted">Navsari</small>
                </div>
              </div>
              <div class="text-warning mb-3">
                <i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
              </div>
              <p class="text-secondary mb-0">"The diagnostics department is extremely fast. I submitted my scans and booked an online consultation within minutes. The support staff handled the insurance files very cleanly."</p>
            </div>
            <!-- Testimonial 3 -->
            <div class="testimonial-card">
              <i class="fa fa-quote-right quote-icon"></i>
              <div class="d-flex align-items-center gap-3 mb-4">
                <img src="images/testimonials/3.png" alt="Patient 3" class="testimonial-avatar">
                <div>
                  <h6 class="fw-bold text-dark mb-1">Amit Desai</h6>
                  <small class="text-muted">Vapi</small>
                </div>
              </div>
              <div class="text-warning mb-3">
                <i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
              </div>
              <p class="text-secondary mb-0">"Hope Rise Medical is definitely the best in South Gujarat. The infrastructure is premium and feels very comfortable, unlike conventional crowded clinics."</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


  
  <!-- SECTION 12: HOSPITAL GALLERY -->
  <section class="py-5 bg-light">
    <div class="container py-5">
      <div class="text-center mb-5" data-aos="fade-up">
        <span class="text-primary fw-bold text-uppercase tracking-wider">HOSPITAL SHOTS</span>
        <h2 class="fw-bold text-dark mt-2">Facility & Ward Gallery</h2>
      </div>
      
      <div class="row g-4">
        <!-- Image 1 -->
        <div class="col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in">
          <div class="gallery-hover-item">
            <img src="images/gallery/gallery-01.jpg" alt="Outpatient consultation">
            <div class="gallery-overlay-box">
              <a href="images/gallery/gallery-01.jpg" data-fancybox="hospital-gallery" class="gallery-icon-btn"><i class="fa fa-expand"></i></a>
            </div>
          </div>
        </div>
        <!-- Image 2 -->
        <div class="col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="50">
          <div class="gallery-hover-item">
            <img src="images/gallery/gallery-02.jpg" alt="Diagnostics Desk">
            <div class="gallery-overlay-box">
              <a href="images/gallery/gallery-02.jpg" data-fancybox="hospital-gallery" class="gallery-icon-btn"><i class="fa fa-expand"></i></a>
            </div>
          </div>
        </div>
        <!-- Image 3 -->
        <div class="col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="100">
          <div class="gallery-hover-item">
            <img src="images/gallery/gallery-03.jpg" alt="Diagnostics Chambers">
            <div class="gallery-overlay-box">
              <a href="images/gallery/gallery-03.jpg" data-fancybox="hospital-gallery" class="gallery-icon-btn"><i class="fa fa-expand"></i></a>
            </div>
          </div>
        </div>
        <!-- Image 4 -->
        <div class="col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="150">
          <div class="gallery-hover-item">
            <img src="images/gallery/gallery-04.jpg" alt="Patient Inpatient beds">
            <div class="gallery-overlay-box">
              <a href="images/gallery/gallery-04.jpg" data-fancybox="hospital-gallery" class="gallery-icon-btn"><i class="fa fa-expand"></i></a>
            </div>
          </div>
        </div>
        <!-- Image 5 -->
        <div class="col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="200">
          <div class="gallery-hover-item">
            <img src="images/gallery/gallery-05.jpg" alt="Emergency wards">
            <div class="gallery-overlay-box">
              <a href="images/gallery/gallery-05.jpg" data-fancybox="hospital-gallery" class="gallery-icon-btn"><i class="fa fa-expand"></i></a>
            </div>
          </div>
        </div>
        <!-- Image 6 -->
        <div class="col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="250">
          <div class="gallery-hover-item">
            <img src="images/gallery/gallery-06.jpg" alt="Surgical prep rooms">
            <div class="gallery-overlay-box">
              <a href="images/gallery/gallery-06.jpg" data-fancybox="hospital-gallery" class="gallery-icon-btn"><i class="fa fa-expand"></i></a>
            </div>
          </div>
        </div>
        <!-- Image 7 -->
        <div class="col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="300">
          <div class="gallery-hover-item">
            <img src="images/gallery/gallery-07.jpg" alt="Critical care beds">
            <div class="gallery-overlay-box">
              <a href="images/gallery/gallery-07.jpg" data-fancybox="hospital-gallery" class="gallery-icon-btn"><i class="fa fa-expand"></i></a>
            </div>
          </div>
        </div>
        <!-- Image 8 -->
        <div class="col-lg-3 col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="350">
          <div class="gallery-hover-item">
            <img src="images/gallery/gallery-08.jpg" alt="Surgical Navigation">
            <div class="gallery-overlay-box">
              <a href="images/gallery/gallery-08.jpg" data-fancybox="hospital-gallery" class="gallery-icon-btn"><i class="fa fa-expand"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 13: OUR TEAM -->
  

  <!-- EXISTING FUNCTIONAL BOOKING WORKSTATION SECTION -->
  <section id="appointment-section" class="py-5 bg-light border-top">
    <div class="container py-5">
      <div class="row g-5">
        <!-- Visiting Hours & FAQ (Integrated from original page) -->
        <div class="col-lg-6" data-aos="fade-right">
          <h3 class="fw-bold text-dark mb-4"><i class="fa fa-circle-info text-primary me-2"></i>Visiting Hours & FAQs</h3>
          <div class="accordion" id="aboutAccordion">
            <div class="accordion-item about-glass-card border-0 mb-3 p-0">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button bg-transparent fw-bold text-dark border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                  Why Should I choose Hope Rise Medical?
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#aboutAccordion">
                <div class="accordion-body text-secondary small pt-0">
                  We blend orthopedic expertise, compassionate nursing support, and structured recovery care. Our chief physician brings international surgical training directly to patients in South Gujarat.
                </div>
              </div>
            </div>
            
            <div class="accordion-item about-glass-card border-0 mb-3 p-0">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button bg-transparent fw-bold text-dark collapsed border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  What are the visiting hours for inpatients?
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#aboutAccordion">
                <div class="accordion-body text-secondary small pt-0">
                  To minimize cross-infections, patients can receive visitors between 12:00 PM - 1:00 PM and 5:00 PM - 6:00 PM. Only one visitor at a time is allowed for a maximum of 10 minutes.
                </div>
              </div>
            </div>
            
            <div class="accordion-item about-glass-card border-0 mb-3 p-0">
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button bg-transparent fw-bold text-dark collapsed border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                  How many visitors are allowed?
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#aboutAccordion">
                <div class="accordion-body text-secondary small pt-0">
                  Maximum two visitors per patient during visiting hours. Children below 12 years are not allowed unless special social circumstances are approved by visitor services.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Dynamic Booking form (Exact functionality preserved) -->
        <div class="col-lg-6" data-aos="fade-left">
          <div class="about-glass-card p-4">
            <h3 class="fw-bold mb-4 text-dark"><i class="fa fa-calendar-plus text-primary me-2"></i>Request Appointment</h3>
            
            <form action="" method="post" class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Patient Profile</label>
                <select name="patient_id" class="form-select form-control-premium" required style="border-radius: 10px;">
                  <option value="" disabled selected>Choose Patient</option>
                  <?php
                  $patient_data = mysqli_query($conn, "SELECT * FROM hs_patient");
                  while($patient_row = mysqli_fetch_array($patient_data)) {
                  ?>
                  <option value="<?= $patient_row['patient_id']; ?>"><?= htmlspecialchars($patient_row['patient_fname'] . " " . $patient_row['patient_lname']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Assign Doctor</label>
                <select name="doctor_id" class="form-select form-control-premium" required style="border-radius: 10px;">
                  <option value="" disabled selected>Choose Doctor</option>
                  <?php
                  $doctor_data = mysqli_query($conn, "SELECT * FROM hs_doctor");
                  while($doctor_row = mysqli_fetch_array($doctor_data)) {
                  ?>
                  <option value="<?= $doctor_row['doctor_id']; ?>">Dr. <?= htmlspecialchars($doctor_row['doctor_fname'] . " " . $doctor_row['doctor_lname']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Specialty</label>
                <select name="category_id" class="form-select form-control-premium" required style="border-radius: 10px;">
                  <option value="" disabled selected>Choose Specialty</option>
                  <?php
                  $category_data = mysqli_query($conn, "SELECT * FROM hs_category");
                  while($category_row = mysqli_fetch_array($category_data)) {
                  ?>
                  <option value="<?= $category_row['category_id']; ?>"><?= htmlspecialchars($category_row['category_title']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Service</label>
                <select name="service_id" class="form-select form-control-premium" required style="border-radius: 10px;">
                  <option value="" disabled selected>Choose Service</option>
                  <?php
                  $service_data = mysqli_query($conn, "SELECT * FROM hs_service");
                  while($service_row = mysqli_fetch_array($service_data)) {
                  ?>
                  <option value="<?= $service_row['service_id']; ?>"><?= htmlspecialchars($service_row['service_title']); ?></option>
                  <?php } ?>
                </select>
              </div>

              <input type="hidden" name="sub_category_id" value="1">

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Date</label>
                <input type="date" name="appointment_date" class="form-control form-control-premium" required style="border-radius: 10px;">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Time</label>
                <input type="time" name="appointment_time" class="form-control form-control-premium" required style="border-radius: 10px;">
              </div>

              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Symptoms / Notes</label>
                <textarea name="appointment_description" class="form-control form-control-premium" rows="2" placeholder="Briefly describe your symptoms..." required style="border-radius: 10px;"></textarea>
              </div>

              <div class="col-md-12 text-center mt-4">
                <button type="submit" name="submit" class="btn btn-gradient-primary w-100 py-3 rounded-pill fw-semibold">Submit Booking Request</button>
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
<!-- AOS JS & Custom Scripts -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="js/about.js?v=<?= time(); ?>"></script>
</body>
</html>