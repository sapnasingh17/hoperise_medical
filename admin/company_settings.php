<?php
include('common/css.php');

// Fetch latest settings from database
$settings_query = mysqli_query($conn, "SELECT * FROM company_settings LIMIT 1");
$settings = mysqli_fetch_assoc($settings_query);
if (!$settings) {
    $settings = [
        'hospital_name' => 'HopeRise Medical',
        'tagline' => 'Your Health, Our Ultimate Mission',
        'logo' => NULL,
        'favicon' => NULL,
        'address' => '123 Medical Way',
        'phone' => '9876543210',
        'email' => 'info@hoperise.com',
        'website' => 'www.hoperise.com',
        'copyright_text' => '© 2026 HopeRise Medical. All Rights Reserved.'
    ];
}
?>
<body class="fixed-navbar">
    <div class="page-wrapper">
        <?php include('common/header.php'); ?>
        <?php include('common/sidebar.php'); ?>
        
        <div class="content-wrapper">
            <div class="page-heading">
                <h1 class="page-title">Company Settings</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="fa fa-home"></i></a>
                    </li>
                    <li class="breadcrumb-item">Settings</li>
                    <li class="breadcrumb-item active">Company Settings</li>
                </ol>
            </div>
            
            <div class="page-content fade-in-up">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> Company settings updated successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?php echo esc($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title"><i class="fa fa-cogs text-primary"></i> Hospital Information Settings</div>
                            </div>
                            <div class="ibox-body">
                                <form action="update_company.php" method="post" enctype="multipart/form-data">
                                    <!-- CSRF Token -->
                                    <?php echo get_csrf_input_html(); ?>
                                    
                                    <div class="row">
                                        <!-- Left Column: Form Details -->
                                        <div class="col-md-8">
                                            <div class="row">
                                                <div class="col-sm-6 form-group mb-3">
                                                    <label class="form-label fw-bold">Hospital Name <span class="text-danger">*</span></label>
                                                    <input class="form-control" type="text" name="hospital_name" value="<?php echo esc($settings['hospital_name']); ?>" required placeholder="Enter Hospital Name">
                                                </div>
                                                <div class="col-sm-6 form-group mb-3">
                                                    <label class="form-label fw-bold">Hospital Tagline</label>
                                                    <input class="form-control" type="text" name="tagline" value="<?php echo esc($settings['tagline']); ?>" placeholder="Enter Hospital Tagline">
                                                </div>
                                            </div>
                                            
                                            <div class="form-group mb-3">
                                                <label class="form-label fw-bold">Address</label>
                                                <textarea class="form-control" name="address" rows="3" placeholder="Enter Hospital Address"><?php echo esc($settings['address']); ?></textarea>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-sm-6 form-group mb-3">
                                                    <label class="form-label fw-bold">Phone Number</label>
                                                    <input class="form-control" type="text" name="phone" value="<?php echo esc($settings['phone']); ?>" placeholder="Enter Phone Number">
                                                </div>
                                                <div class="col-sm-6 form-group mb-3">
                                                    <label class="form-label fw-bold">Email Address</label>
                                                    <input class="form-control" type="email" name="email" value="<?php echo esc($settings['email']); ?>" placeholder="Enter Email Address">
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-sm-6 form-group mb-3">
                                                    <label class="form-label fw-bold">Website URL</label>
                                                    <input class="form-control" type="text" name="website" value="<?php echo esc($settings['website']); ?>" placeholder="Enter Website URL (e.g. www.hoperise.com)">
                                                </div>
                                                <div class="col-sm-6 form-group mb-3">
                                                    <label class="form-label fw-bold">Copyright Text</label>
                                                    <input class="form-control" type="text" name="copyright_text" value="<?php echo esc($settings['copyright_text']); ?>" placeholder="Enter Copyright Text">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column: Logo & Favicon Uploads -->
                                        <div class="col-md-4 border-start">
                                            <div class="form-group mb-4 text-center">
                                                <label class="form-label fw-bold d-block text-start mb-2">Hospital Logo</label>
                                                <div class="mb-3 d-flex justify-content-center align-items-center border rounded bg-light" style="height: 150px; overflow: hidden; position: relative;">
                                                    <?php if (!empty($settings['logo'])): ?>
                                                        <img id="logo-preview" src="../uploads/company/<?php echo esc($settings['logo']); ?>" alt="Logo Preview" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                                    <?php else: ?>
                                                        <img id="logo-preview" src="../images/img.png" alt="Default Logo" style="max-height: 100%; max-width: 100%; object-fit: contain; opacity: 0.6;">
                                                    <?php endif; ?>
                                                </div>
                                                <input class="form-control" type="file" name="logo" id="logo-input" accept="image/png, image/jpeg, image/jpg, image/svg+xml">
                                                <span class="form-text text-muted small d-block text-start mt-1">Allowed: PNG, JPG, JPEG, SVG. Max: 2 MB.</span>
                                            </div>
                                            
                                            <div class="form-group mb-4 text-center">
                                                <label class="form-label fw-bold d-block text-start mb-2">Favicon (Optional)</label>
                                                <div class="mb-3 d-flex justify-content-center align-items-center border rounded bg-light" style="height: 80px; width: 80px; margin: 0 auto; overflow: hidden; position: relative;">
                                                    <?php if (!empty($settings['favicon'])): ?>
                                                        <img id="favicon-preview" src="../uploads/company/<?php echo esc($settings['favicon']); ?>" alt="Favicon Preview" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                                    <?php else: ?>
                                                        <img id="favicon-preview" src="../images/img.png" alt="Default Favicon" style="max-height: 100%; max-width: 100%; object-fit: contain; opacity: 0.6;">
                                                    <?php endif; ?>
                                                </div>
                                                <input class="form-control" type="file" name="favicon" id="favicon-input" accept="image/png, image/jpeg, image/jpg, image/x-icon, image/svg+xml">
                                                <span class="form-text text-muted small d-block text-start mt-1">Allowed: PNG, JPG, ICO, SVG. Max: 2 MB.</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="form-group mt-3">
                                        <button class="btn btn-primary px-4 py-2" type="submit" name="submit">
                                            <i class="fa fa-check me-2"></i> Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include('common/footer.php'); ?>
        </div>
    </div>
    
    <?php include('common/configue_panel.php'); ?>
    <?php include('common/preloader.php'); ?>
    <?php include('common/main_footer.php'); ?>
    
    <script>
        // Image preview logic
        document.getElementById('logo-input').addEventListener('change', function(event) {
            const [file] = this.files;
            if (file) {
                document.getElementById('logo-preview').src = URL.createObjectURL(file);
                document.getElementById('logo-preview').style.opacity = '1';
            }
        });
        
        document.getElementById('favicon-input').addEventListener('change', function(event) {
            const [file] = this.files;
            if (file) {
                document.getElementById('favicon-preview').src = URL.createObjectURL(file);
                document.getElementById('favicon-preview').style.opacity = '1';
            }
        });
    </script>
</body>
</html>
