<?php
include_once("includes/main_header.php");
?>
<body>
<div class="page-wrapper">
  <!-- Headers -->
  <?php include("includes/top_header.php"); ?>
  <?php include("includes/upper_header.php"); ?>
  <?php include("includes/navbar.php"); ?>

  <!-- Page Title -->
  <section class="py-5 bg-primary-light text-primary position-relative overflow-hidden mb-5">
    <div class="container py-4 position-relative z-1 text-center">
      <h1 class="display-5 fw-bold text-dark mb-2">Our Services</h1>
      <p class="text-secondary mb-0">Explore our comprehensive range of premium clinical treatments and care options.</p>
    </div>
  </section>

  <!-- Service Overview -->
  <section class="py-5">
    <div class="container">
      <div class="row g-5 align-items-center mb-5">
        <div class="col-lg-6">
          <span class="badge bg-primary text-white px-3 py-2 rounded-pill mb-3 fw-semibold">Patient Education</span>
          <h2 class="fw-bold text-dark mb-4">Clinical & Medical Education</h2>
          <p class="text-secondary mb-4">Clinical medical training is essential in enabling our practitioners to deliver efficient, safe, and modern care. We host tutorials, clinical electives, and hands-on skill workshops regularly.</p>
          
          <ul class="list-unstyled d-flex flex-column gap-2 text-secondary mb-4">
            <li class="d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3"></i> Trains health professionals for high-quality care</li>
            <li class="d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3"></i> Focuses on patient-centric problems and solutions</li>
            <li class="d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3"></i> Promotes modern medical practices and procedures</li>
          </ul>
          
          <a href="add_appointment.php" class="btn btn-premium btn-lg">Schedule Consultation</a>
        </div>
        <div class="col-lg-6">
          <div class="glass-card p-4">
            <h4 class="fw-bold text-dark mb-4"><i class="bi bi-info-circle-fill text-primary me-2"></i>Frequently Asked Questions</h4>
            <div class="accordion" id="serviceAccordion">
              <div class="accordion-item bg-transparent border-0 mb-3">
                <h2 class="accordion-header" id="faqOne">
                  <button class="accordion-button bg-transparent fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    Why choose Hope Rise Medical?
                  </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#serviceAccordion">
                  <div class="accordion-body text-secondary small px-0">
                    We offer dedicated, board-certified care. Our clinics are equipped with modern diagnostics, and patients get easy invoice downloads and history tracking.
                  </div>
                </div>
              </div>
              <div class="accordion-item bg-transparent border-0 mb-3">
                <h2 class="accordion-header" id="faqTwo">
                  <button class="accordion-button bg-transparent fw-bold text-dark collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    What are the visiting hours?
                  </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#serviceAccordion">
                  <div class="accordion-body text-secondary small px-0">
                    Visiting hours are restricted between 12:00 PM to 1:00 PM and 5:00 PM to 6:00 PM daily. Only one visitor is allowed per patient.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Provided Services Section -->
  <section class="py-5 bg-light-emphasis bg-opacity-10">
    <div class="container">
      <div class="section-title text-center mb-5">
        <span class="text-primary fw-semibold">Provided Services</span>
        <h2 class="fw-bold text-dark mt-2">Comprehensive Treatment Services</h2>
        <!-- <p class="text-muted col-lg-6 mx-auto mt-2">Explore the dynamic list of clinical treatments handled in-house by our care departments.</p> -->
      </div>

      <div class="row g-4">
        <?php
        $service_query = mysqli_query($conn, "SELECT * FROM hs_service");
        if ($service_query && mysqli_num_rows($service_query) > 0) {
            while ($service_row = mysqli_fetch_array($service_query)) {
        ?>
        <div class="col-lg-4 col-md-6">
          <div class="glass-card overflow-hidden h-100 d-flex flex-column" id="service<?= $service_row['service_id']; ?>">
            <?php
            $service_img = "images/gallery/1.jpg";
            if (!empty($service_row['service_thumb']) && file_exists(__DIR__ . "/admin/uploads/" . $service_row['service_thumb'])) {
                $service_img = "admin/uploads/" . $service_row['service_thumb'];
            }
            ?>
            <img src="<?= $service_img; ?>" alt="Service banner" class="img-fluid w-100" style="height: 200px; object-fit: cover;">
            <div class="p-4 d-flex flex-column flex-grow-1">
              <h5 class="fw-bold text-dark mb-3"><?= htmlspecialchars($service_row['service_title']); ?></h5>
              <p class="text-secondary small flex-grow-1"><?= htmlspecialchars($service_row['service_description']); ?></p>
              <div class="mt-4">
                <a href="add_appointment.php" class="btn btn-premium btn-sm w-100">Book Service Consultation</a>
              </div>
            </div>
          </div>
        </div>
        <?php
            }
        } else {
        ?>
        <div class="col-12 text-center text-muted py-5">
          <i class="bi bi-folder-x fs-1 mb-2 d-block"></i>
          <span>No services registered in the clinic catalog.</span>
        </div>
        <?php } ?>
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