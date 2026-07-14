<?php
include_once("dbcon.php");

// Redirect guest users to login page to prevent booking/viewing without an account
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['toast_error'] = 'CSRF Verification Failed';
        header("Location: add_appointment.php");
        exit();
    }

    $doctor_id = intval($_POST['doctor_id']);
    $category_id = intval($_POST['category_id']);
    $sub_category_id = intval($_POST['sub_category_id']);
    $service_id = intval($_POST['service_id']);
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $appointment_description = $_POST['appointment_description'];

    // 1. Backend Validation: Verify slot availability to prevent double-booking
    $day_of_week = date('l', strtotime($appointment_date));
    $schedule_sql = "SELECT start_time, end_time, slot_duration, status FROM hs_doctor_schedule WHERE doctor_id = ? AND day_of_week = ? AND status = 1";
    $sched_stmt = mysqli_prepare($conn, $schedule_sql);
    $start_time = '09:00:00';
    $end_time = '17:00:00';
    $slot_duration = 30;
    $is_available_day = true;

    if ($sched_stmt) {
        mysqli_stmt_bind_param($sched_stmt, "is", $doctor_id, $day_of_week);
        mysqli_stmt_execute($sched_stmt);
        $res = mysqli_stmt_get_result($sched_stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $start_time = $row['start_time'];
            $end_time = $row['end_time'];
            $slot_duration = intval($row['slot_duration']);
        } else {
            if ($day_of_week === 'Saturday' || $day_of_week === 'Sunday') {
                $is_available_day = false;
            }
        }
        mysqli_stmt_close($sched_stmt);
    }
    
    $is_past = false;
    if ($appointment_date < date('Y-m-d')) {
        $is_past = true;
    } elseif ($appointment_date === date('Y-m-d')) {
        if (strtotime($appointment_date . ' ' . $appointment_time) <= time()) {
            $is_past = true;
        }
    }

    $booked = false;
    $booking_sql = "SELECT 1 FROM hs_appointment WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND appointment_status NOT IN ('Reject', 'Cancel')";
    $booking_stmt = mysqli_prepare($conn, $booking_sql);
    if ($booking_stmt) {
        $norm_time = date('H:i:s', strtotime($appointment_time));
        mysqli_stmt_bind_param($booking_stmt, "iss", $doctor_id, $appointment_date, $norm_time);
        mysqli_stmt_execute($booking_stmt);
        mysqli_stmt_store_result($booking_stmt);
        if (mysqli_stmt_num_rows($booking_stmt) > 0) {
            $booked = true;
        }
        mysqli_stmt_close($booking_stmt);
    }

    if (!$is_available_day || $is_past || $booked) {
        $_SESSION['toast_error'] = 'Error: The selected time slot is no longer available. Please select another slot.';
        header("Location: add_appointment.php");
        exit();
    }
    
    // Insert using prepared statement to prevent SQL Injection
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

            $_SESSION['toast_success'] = 'Appointment Requested Successfully!';
            header("Location: payment.php?appointment_id=" . $new_app_id);
            exit();
        }
    }
}

$selected_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

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
      <h1 class="display-5 fw-bold text-dark mb-2">Book Appointment</h1>
      <p class="text-secondary mb-0">Select your preferred date, department, and doctor to request a clinical consultation.</p>
    </div>
  </section>

  <!-- Appointment Booking Form Workspace -->
  <section class="pb-5">
    <div class="container">
      <div class="row g-5">
        <!-- Booking Form Card -->
        <div class="col-lg-7">
          <div class="glass-card p-5">
            <h3 class="fw-bold text-dark mb-4"><i class="bi bi-calendar-check-fill text-primary me-2"></i>Consultation Details</h3>
            
            <form action="" method="post" class="row g-4">
              <!-- CSRF Token -->
              <?php echo get_csrf_input_html(); ?>

              <!-- Doctor Select -->
              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Assign Doctor</label>
                <select name="doctor_id" id="doctor_id_select" class="form-select form-control-premium" required>
                  <option value="" disabled <?= $selected_doctor_id == 0 ? 'selected' : '' ?>>Choose Practitioner</option>
                  <?php
                  list($stmt_d, $doctor_data) = db_query_prepared($conn, "SELECT * FROM hs_doctor");
                  while($doctor_row = mysqli_fetch_array($doctor_data)) {
                      $sel = ($doctor_row['doctor_id'] == $selected_doctor_id) ? 'selected' : '';
                  ?>
                  <option value="<?= esc($doctor_row['doctor_id']); ?>" <?= $sel ?>
                          data-category="<?= esc($doctor_row['category_id']); ?>"
                          data-subcategory="<?= esc($doctor_row['sub_category_id']); ?>"
                          data-service="<?= esc($doctor_row['service_id']); ?>">Dr. <?= esc($doctor_row['doctor_fname'] . " " . $doctor_row['doctor_lname']); ?></option>
                  <?php 
                  }
                  if ($stmt_d) mysqli_stmt_close($stmt_d);
                  ?>
                </select>
              </div>

              <!-- Category Select -->
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Specialty</label>
                <select name="category_id" id="category_id_select" class="form-select form-control-premium" required>
                  <option value="" disabled selected>Choose Specialty</option>
                  <?php
                  list($stmt_c, $category_data) = db_query_prepared($conn, "SELECT * FROM hs_category");
                  while($category_row = mysqli_fetch_array($category_data)) {
                  ?>
                  <option value="<?= esc($category_row['category_id']); ?>"><?= esc($category_row['category_title']); ?></option>
                  <?php 
                  }
                  if ($stmt_c) mysqli_stmt_close($stmt_c);
                  ?>
                </select>
              </div>

              <!-- Sub-Category Select -->
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Sub-Specialty</label>
                <select name="sub_category_id" id="sub_category_id_select" class="form-select form-control-premium" required>
                  <option value="" disabled selected>Choose Sub-Specialty</option>
                  <?php
                  list($stmt_sc, $sub_data) = db_query_prepared($conn, "SELECT * FROM hs_sub_category");
                  while($sub_row = mysqli_fetch_array($sub_data)) {
                  ?>
                  <option value="<?= esc($sub_row['sub_category_id']); ?>" data-category="<?= esc($sub_row['category_id']); ?>"><?= esc($sub_row['sub_category_title']); ?></option>
                  <?php 
                  }
                  if ($stmt_sc) mysqli_stmt_close($stmt_sc);
                  ?>
                </select>
              </div>

              <!-- Service Select -->
              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Requested Service</label>
                <select name="service_id" id="service_id_select" class="form-select form-control-premium" required>
                  <option value="" disabled selected>Choose Service</option>
                  <?php
                  list($stmt_s, $service_data) = db_query_prepared($conn, "SELECT * FROM hs_service");
                  while($service_row = mysqli_fetch_array($service_data)) {
                  ?>
                  <option value="<?= esc($service_row['service_id']); ?>"
                          data-category="<?= esc($service_row['category_id']); ?>"
                          data-subcategory="<?= esc($service_row['sub_category_id']); ?>"><?= esc($service_row['service_title']); ?></option>
                  <?php 
                  }
                  if ($stmt_s) mysqli_stmt_close($stmt_s);
                  ?>
                </select>
              </div>

              <!-- Date Picker -->
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Preferred Date</label>
                <input type="date" id="appointment_date" name="appointment_date" class="form-control form-control-premium" min="<?= date('Y-m-d') ?>" required>
              </div>

              <!-- Time Picker Slot Grid -->
              <input type="hidden" name="appointment_time" id="appointment_time_input" required>
              
              <div class="col-md-12" id="slots_container_wrapper" style="display:none;">
                <label class="form-label small fw-semibold text-secondary">Available Time Slots</label>
                <div class="d-flex flex-wrap gap-2" id="slots_grid"></div>
              </div>

              <!-- Description -->
              <div class="col-md-12">
                <label class="form-label small fw-semibold text-secondary">Symptoms / Notes</label>
                <textarea name="appointment_description" class="form-control form-control-premium" rows="3" placeholder="Describe symptoms or reasons for visit..." required></textarea>
              </div>

              <!-- Submit -->
              <div class="col-md-12 text-end">
                <button type="submit" name="submit" class="btn btn-premium btn-lg w-100">Submit Booking Request</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Banner / Info Area -->
        <div class="col-lg-5">
          <div class="position-relative h-100">
            <img src="images/background/appoinment.jpg" alt="Clinical Visit Info" class="img-fluid rounded shadow-lg border border-white h-100" style="object-fit: cover; border-width: 8px !important; min-height: 380px;">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const doctorSelect = document.getElementById('doctor_id_select');
    const categorySelect = document.getElementById('category_id_select');
    const subCategorySelect = document.getElementById('sub_category_id_select');
    const serviceSelect = document.getElementById('service_id_select');
    const dateInput = document.getElementById('appointment_date');
    const slotsWrapper = document.getElementById('slots_container_wrapper');
    const slotsGrid = document.getElementById('slots_grid');
    const timeInput = document.getElementById('appointment_time_input');

    // Keep copies of the original option elements to restore/filter them dynamically
    const originalSubCategoryOptions = Array.from(subCategorySelect.options);
    const originalServiceOptions = Array.from(serviceSelect.options);
    const originalDoctorOptions = Array.from(doctorSelect.options);

    let isUpdatingSelection = false;

    function filterSelectOptions(selectElement, originalOptions, filterFn) {
        const currentValue = selectElement.value;
        selectElement.innerHTML = '';
        
        originalOptions.forEach(opt => {
            if (opt.value === "" || filterFn(opt)) {
                selectElement.appendChild(opt.cloneNode(true));
            }
        });
        
        const optionExists = Array.from(selectElement.options).some(opt => opt.value === currentValue);
        if (optionExists && currentValue !== "") {
            selectElement.value = currentValue;
        } else {
            selectElement.value = "";
        }
    }

    function fetchSlots() {
        const doctorId = doctorSelect.value;
        const dateVal = dateInput.value;

        if (!doctorId || !dateVal) {
            slotsWrapper.style.display = 'none';
            return;
        }

        // Render premium shimmering skeleton loading slots
        slotsGrid.innerHTML = `
            <div class="d-flex flex-wrap gap-2 w-100 py-1">
                <div class="skeleton-slot"></div>
                <div class="skeleton-slot"></div>
                <div class="skeleton-slot"></div>
                <div class="skeleton-slot"></div>
            </div>
        `;
        slotsWrapper.style.display = 'block';
        timeInput.value = '';

        fetch('api_get_slots.php?doctor_id=' + doctorId + '&date=' + dateVal)
            .then(response => response.json())
            .then(data => {
                slotsGrid.innerHTML = '';
                if (data.success) {
                    if (data.slots && data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = slot.available ? 'btn btn-outline-primary btn-sm py-2 px-3 fw-semibold' : 'btn btn-outline-secondary btn-sm py-2 px-3 fw-semibold';
                            btn.textContent = slot.time_formatted;
                            btn.style.borderRadius = '20px';
                            if (!slot.available) {
                                btn.disabled = true;
                                btn.style.opacity = '0.35';
                                btn.title = slot.reason === 'booked' ? 'Already Booked' : 'Past Time';
                            } else {
                                btn.addEventListener('click', function() {
                                    document.querySelectorAll('#slots_grid .btn-primary').forEach(b => {
                                        b.classList.remove('btn-primary');
                                        b.classList.add('btn-outline-primary');
                                    });
                                    btn.classList.remove('btn-outline-primary');
                                    btn.classList.add('btn-primary');
                                    timeInput.value = slot.time;
                                });
                            }
                            slotsGrid.appendChild(btn);
                        });
                    } else {
                        slotsGrid.innerHTML = '<div class="text-danger small py-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Doctor is not available on ' + (data.day || 'this day') + '.</div>';
                    }
                } else {
                    slotsGrid.innerHTML = '<div class="text-danger small py-2">Error loading slots.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                slotsGrid.innerHTML = '<div class="text-danger small py-2">Error connecting to server.</div>';
            });
    }

    categorySelect.addEventListener('change', function() {
        if (isUpdatingSelection) return;
        
        const catId = categorySelect.value;
        isUpdatingSelection = true;
        
        if (catId) {
            filterSelectOptions(subCategorySelect, originalSubCategoryOptions, opt => opt.dataset.category === catId);
            filterSelectOptions(serviceSelect, originalServiceOptions, opt => opt.dataset.category === catId);
            filterSelectOptions(doctorSelect, originalDoctorOptions, opt => opt.dataset.category === catId);
        } else {
            filterSelectOptions(subCategorySelect, originalSubCategoryOptions, () => true);
            filterSelectOptions(serviceSelect, originalServiceOptions, () => true);
            filterSelectOptions(doctorSelect, originalDoctorOptions, () => true);
        }
        
        isUpdatingSelection = false;
        fetchSlots();
    });

    subCategorySelect.addEventListener('change', function() {
        if (isUpdatingSelection) return;
        
        const subcatId = subCategorySelect.value;
        isUpdatingSelection = true;
        
        if (subcatId) {
            const selectedOption = subCategorySelect.options[subCategorySelect.selectedIndex];
            const catId = selectedOption.dataset.category;
            
            if (categorySelect.value !== catId) {
                categorySelect.value = catId;
                filterSelectOptions(subCategorySelect, originalSubCategoryOptions, opt => opt.dataset.category === catId);
                subCategorySelect.value = subcatId;
            }
            
            filterSelectOptions(serviceSelect, originalServiceOptions, opt => opt.dataset.category === catId && opt.dataset.subcategory === subcatId);
            filterSelectOptions(doctorSelect, originalDoctorOptions, opt => opt.dataset.category === catId && opt.dataset.subcategory === subcatId);
        } else {
            const catId = categorySelect.value;
            if (catId) {
                filterSelectOptions(serviceSelect, originalServiceOptions, opt => opt.dataset.category === catId);
                filterSelectOptions(doctorSelect, originalDoctorOptions, opt => opt.dataset.category === catId);
            } else {
                filterSelectOptions(serviceSelect, originalServiceOptions, () => true);
                filterSelectOptions(doctorSelect, originalDoctorOptions, () => true);
            }
        }
        
        isUpdatingSelection = false;
        fetchSlots();
    });

    serviceSelect.addEventListener('change', function() {
        if (isUpdatingSelection) return;
        
        const serviceId = serviceSelect.value;
        isUpdatingSelection = true;
        
        if (serviceId) {
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const catId = selectedOption.dataset.category;
            const subcatId = selectedOption.dataset.subcategory;
            
            categorySelect.value = catId;
            filterSelectOptions(subCategorySelect, originalSubCategoryOptions, opt => opt.dataset.category === catId);
            subCategorySelect.value = subcatId;
            
            filterSelectOptions(serviceSelect, originalServiceOptions, opt => opt.dataset.category === catId && opt.dataset.subcategory === subcatId);
            serviceSelect.value = serviceId;
            
            filterSelectOptions(doctorSelect, originalDoctorOptions, opt => opt.dataset.category === catId && opt.dataset.subcategory === subcatId && opt.dataset.service === serviceId);
        } else {
            const catId = categorySelect.value;
            const subcatId = subCategorySelect.value;
            if (catId && subcatId) {
                filterSelectOptions(doctorSelect, originalDoctorOptions, opt => opt.dataset.category === catId && opt.dataset.subcategory === subcatId);
            } else if (catId) {
                filterSelectOptions(doctorSelect, originalDoctorOptions, opt => opt.dataset.category === catId);
            } else {
                filterSelectOptions(doctorSelect, originalDoctorOptions, () => true);
            }
        }
        
        isUpdatingSelection = false;
        fetchSlots();
    });

    doctorSelect.addEventListener('change', function() {
        if (isUpdatingSelection) return;
        
        const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
        if (selectedOption && selectedOption.value !== "") {
            const catId = selectedOption.dataset.category;
            const subcatId = selectedOption.dataset.subcategory;
            const serviceId = selectedOption.dataset.service;
            
            isUpdatingSelection = true;
            
            categorySelect.value = catId;
            filterSelectOptions(subCategorySelect, originalSubCategoryOptions, opt => opt.dataset.category === catId);
            subCategorySelect.value = subcatId;
            
            filterSelectOptions(serviceSelect, originalServiceOptions, opt => opt.dataset.category === catId && opt.dataset.subcategory === subcatId);
            serviceSelect.value = serviceId;
            
            isUpdatingSelection = false;
        }
        
        fetchSlots();
    });

    dateInput.addEventListener('change', fetchSlots);

    if (doctorSelect.value) {
        const today = new Date().toISOString().split('T')[0];
        if (!dateInput.value) {
            dateInput.value = today;
        }
        doctorSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<!-- Premium Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index: 1060;">
  <div id="premiumToast" class="toast toast-premium hide align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex p-3 align-items-center">
      <div class="toast-icon-container me-3 text-primary fs-4">
        <i class="bi bi-info-circle-fill" id="premiumToastIcon"></i>
      </div>
      <div class="toast-body p-0 flex-grow-1">
        <h6 class="mb-0 fw-bold text-dark" id="premiumToastTitle">Notification</h6>
        <span class="small text-secondary" id="premiumToastMsg"></span>
      </div>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script>
function showToast(message, type = 'success', title = '') {
    const toastEl = document.getElementById('premiumToast');
    const toastIcon = document.getElementById('premiumToastIcon');
    const toastTitle = document.getElementById('premiumToastTitle');
    const toastMsg = document.getElementById('premiumToastMsg');
    
    toastMsg.textContent = message;
    toastTitle.textContent = title || (type === 'success' ? 'Success' : 'Error');
    
    toastIcon.className = '';
    if (type === 'success') {
        toastIcon.className = 'bi bi-check-circle-fill text-success';
        toastEl.style.borderLeft = '4px solid #10b981';
    } else {
        toastIcon.className = 'bi bi-exclamation-triangle-fill text-danger';
        toastEl.style.borderLeft = '4px solid #ef4444';
    }
    
    const toastObj = new bootstrap.Toast(toastEl, { delay: 5000 });
    toastObj.show();
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['toast_success'])): ?>
        showToast(<?= json_encode($_SESSION['toast_success']) ?>, 'success', 'Success');
        <?php unset($_SESSION['toast_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['toast_error'])): ?>
        showToast(<?= json_encode($_SESSION['toast_error']) ?>, 'error', 'Error');
        <?php unset($_SESSION['toast_error']); ?>
    <?php endif; ?>
});
</script>
</body>
</html>