<nav class="navbar navbar-expand-lg sticky-top glass-navbar py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <img src="images/img.png" alt="Hope Rise Medical Logo" height="40" class="d-inline-block align-top">
      <span class="fs-4 fw-bold text-primary">Hope Rise</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-2 align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active fw-semibold text-primary' : '' ?>" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active fw-semibold text-primary' : '' ?>" href="about.php">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'service.php') ? 'active fw-semibold text-primary' : '' ?>" href="service.php">Service</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'team.php') ? 'active fw-semibold text-primary' : '' ?>" href="team.php">Doctors</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'add_appointment.php') ? 'active fw-semibold text-primary' : '' ?>" href="add_appointment.php">Appointment</a>
        </li>
        <li class="nav-item">
          <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active fw-semibold text-primary' : '' ?>" href="contact.php">Contact</a>
        </li>
        <?php if(isset($_SESSION['patient_id'])): ?>
          <li class="nav-item">
            <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'feedback.php') ? 'active fw-semibold text-primary' : '' ?>" href="feedback.php">Feedback</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'my_appointment.php') ? 'active fw-semibold text-primary' : '' ?>" href="my_appointment.php">Profile</a>
          </li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-premium btn-sm" href="logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link px-3 <?= (basename($_SERVER['PHP_SELF']) == 'patient_reg.php') ? 'active fw-semibold text-primary' : '' ?>" href="patient_reg.php">Register</a>
          </li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-premium btn-sm" href="login.php">Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
