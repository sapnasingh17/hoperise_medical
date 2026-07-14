<?php
// Secure AJAX Handler: Return JSON/HTML dynamically based on filters
if (isset($_GET['ajax'])) {
    include_once("dbcon.php");
    $where = ["1=1"];
    $params = [];
    $types = "";

    if (!empty($_GET['search'])) {
        $where[] = "(doctor_fname LIKE ? OR doctor_lname LIKE ?)";
        $search = "%" . $_GET['search'] . "%";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    if (!empty($_GET['category'])) {
        $where[] = "d.category_id = ?";
        $params[] = intval($_GET['category']);
        $types .= "i";
    }
    if (!empty($_GET['gender'])) {
        $where[] = "d.doctor_gender = ?";
        $params[] = $_GET['gender'];
        $types .= "s";
    }

    $sql = "SELECT d.*, c.category_title 
            FROM hs_doctor d 
            LEFT JOIN hs_category c ON d.category_id = c.category_id 
            WHERE " . implode(" AND ", $where);
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $doc_img = "images/team/doctor_img.webp";
                if (!empty($row['doctor_thumb']) && file_exists(__DIR__ . "/admin/uploads/" . $row['doctor_thumb'])) {
                    $doc_img = "admin/uploads/" . $row['doctor_thumb'];
                }
                ?>
                <div class="col-lg-4 col-md-6 animate-fade-in-up">
                  <div class="glass-card overflow-hidden text-center h-100">
                    <img src="<?= $doc_img ?>" alt="Doctor picture" class="img-fluid w-100" style="height: 280px; object-fit: cover;">
                    <div class="p-4">
                      <h5 class="fw-bold text-dark mb-1">Dr. <?= htmlspecialchars($row['doctor_fname'] . " " . $row['doctor_lname']) ?></h5>
                      <span class="badge bg-primary-light text-primary px-3 py-2 rounded-pill mb-3 fw-normal"><?= htmlspecialchars($row['category_title'] ?? 'General Specialist') ?></span>
                      <p class="text-secondary small mb-3"><i class="bi bi-telephone-fill text-primary me-2"></i><?= htmlspecialchars($row['doctor_phone']) ?></p>
                      <a href="add_appointment.php?doctor_id=<?= $row['doctor_id']; ?>" class="btn btn-premium btn-sm w-100">Book Appointment</a>
                    </div>
                  </div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="col-12 text-center text-muted py-5">
              <i class="bi bi-emoji-neutral fs-1 mb-2 d-block"></i>
              <span>No doctors found matching your criteria.</span>
            </div>
            <?php
        }
        mysqli_stmt_close($stmt);
    }
    exit();
}

include_once("dbcon.php");
include("includes/main_header.php");
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
      <h1 class="display-5 fw-bold text-dark mb-2">Our Medical Specialists</h1>
      <p class="text-secondary mb-0">Search and filter our board-certified medical staff and roster schedules.</p>
    </div>
  </section>

  <!-- Interactive Search / Filter Workspace -->
  <section class="pb-5">
    <div class="container">
      <div class="glass-card p-4 mb-5">
        <div class="row g-3">
          <!-- Text Search -->
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Search Doctor</label>
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" id="searchName" class="form-control border-start-0 ps-0" placeholder="Type doctor name...">
            </div>
          </div>
          
          <!-- Category Filter -->
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Specialty</label>
            <select id="filterCategory" class="form-select">
              <option value="">All Specialties</option>
              <?php
              $cat_res = mysqli_query($conn, "SELECT * FROM hs_category");
              while ($cat_row = mysqli_fetch_array($cat_res)) {
                  echo "<option value='{$cat_row['category_id']}'>" . htmlspecialchars($cat_row['category_title']) . "</option>";
              }
              ?>
            </select>
          </div>

          <!-- Gender Filter -->
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Gender</label>
            <select id="filterGender" class="form-select">
              <option value="">All Genders</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Doctor Output Grid -->
      <div class="row g-4" id="doctorsGrid">
        <!-- Render initially by reusing local AJAX query logic -->
        <?php
        $sql = "SELECT d.*, c.category_title FROM hs_doctor d LEFT JOIN hs_category c ON d.category_id = c.category_id";
        $doctor_query = mysqli_query($conn, $sql);
        if ($doctor_query && mysqli_num_rows($doctor_query) > 0) {
            while ($row = mysqli_fetch_array($doctor_query)) {
                $doc_img = "images/team/doctor_img.webp";
                if (!empty($row['doctor_thumb']) && file_exists(__DIR__ . "/admin/uploads/" . $row['doctor_thumb'])) {
                    $doc_img = "admin/uploads/" . $row['doctor_thumb'];
                }
        ?>
        <div class="col-lg-4 col-md-6 animate-fade-in-up">
          <div class="glass-card overflow-hidden text-center h-100">
            <img src="<?= $doc_img ?>" alt="Doctor picture" class="img-fluid w-100" style="height: 280px; object-fit: cover;">
            <div class="p-4">
              <h5 class="fw-bold text-dark mb-1">Dr. <?= htmlspecialchars($row['doctor_fname'] . " " . $row['doctor_lname']) ?></h5>
              <span class="badge bg-primary-light text-primary px-3 py-2 rounded-pill mb-3 fw-normal"><?= htmlspecialchars($row['category_title'] ?? 'General Specialist') ?></span>
              <p class="text-secondary small mb-3"><i class="bi bi-telephone-fill text-primary me-2"></i><?= htmlspecialchars($row['doctor_phone']) ?></p>
              <a href="add_appointment.php?doctor_id=<?= $row['doctor_id']; ?>" class="btn btn-premium btn-sm w-100">Book Appointment</a>
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

  <!-- Reusable modular Footer components -->
  <?php include("includes/footer.php"); ?>
</div>

<!-- JS imports -->
<?php include("includes/js.php"); ?>

<!-- Interactive AJAX filtering Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchName = document.getElementById("searchName");
    const filterCategory = document.getElementById("filterCategory");
    const filterGender = document.getElementById("filterGender");
    const doctorsGrid = document.getElementById("doctorsGrid");

    function fetchDoctors() {
        // Show skeleton loading / simple spinner
        doctorsGrid.innerHTML = `
          <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading roster...</span>
            </div>
          </div>`;

        const params = new URLSearchParams({
            ajax: 1,
            search: searchName.value,
            category: filterCategory.value,
            gender: filterGender.value
        });

        fetch("team.php?" + params.toString())
            .then(response => response.text())
            .then(html => {
                doctorsGrid.innerHTML = html;
            })
            .catch(err => {
                doctorsGrid.innerHTML = `
                  <div class="col-12 text-center text-danger py-5">
                    <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                    <p class="mt-2">Failed to load specialists. Please check connection.</p>
                  </div>`;
            });
    }

    // Bind event listeners with light debouncing logic
    let debounceTimer;
    searchName.addEventListener("input", function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchDoctors, 300);
    });

    filterCategory.addEventListener("change", fetchDoctors);
    filterGender.addEventListener("change", fetchDoctors);
});
</script>
</body>
</html>