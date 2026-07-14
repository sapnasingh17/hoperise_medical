<?php
include('common/css.php');

$service_id = intval($_GET['service_id'] ?? 0);

// Fetch the existing service details
list($select_stmt, $select_service) = db_query_prepared($conn, "SELECT * FROM hs_service WHERE service_id = ?", [$service_id]);
$service_row = mysqli_fetch_array($select_service);
if ($select_stmt) {
    mysqli_stmt_close($select_stmt);
}

if (!$service_row) {
    echo "<script>alert('Service not found.'); window.location.href='display_service.php';</script>";
    exit();
}

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $category_id = intval($_POST['category_id']);
    $sub_category_id = intval($_POST['sub_category_id']);
    $service_title = $_POST['service_title'];
    $service_description = $_POST['service_description'];

    $service_thumb_org = $service_row['service_thumb'];

    // Check if a new image is uploaded
    if (!empty($_FILES['service_thumb']['name'])) {
        $file_validation = validate_file_upload($_FILES['service_thumb']);
        if (!$file_validation['success']) {
            echo "<script>alert('" . esc($file_validation['message']) . "'); window.history.back();</script>";
            exit();
        }
        $service_thumb_org = $file_validation['new_name'];
        $path = "uploads/";
        $imagepath = $path . $service_thumb_org;
        move_uploaded_file($_FILES['service_thumb']['tmp_name'], $imagepath);
    }

    $update_stmt = mysqli_prepare($conn, "UPDATE hs_service SET category_id = ?, sub_category_id = ?, service_title = ?, service_description = ?, service_thumb = ? WHERE service_id = ?");
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "iisssi", $category_id, $sub_category_id, $service_title, $service_description, $service_thumb_org, $service_id);
        $query = mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        if ($query) {
            echo "<script>alert('Service updated successfully.'); window.location.href='display_service.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating service.');</script>";
        }
    }
}
?>
<body class="fixed-navbar">
    <div class="page-wrapper">
    <?php
            include('common/header.php');
        ?>
        <!-- END HEADER-->
        <!-- START SIDEBAR-->
        <?php
            include('common/sidebar.php');
        ?>
        <!-- END SIDEBAR-->
        <div class="content-wrapper">
            <!-- START PAGE CONTENT-->
            <div class="page-heading">
                <h1 class="page-title">service Form</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="la la-home font-20"></i></a>
                    </li>
                    <li class="breadcrumb-item">service Form</li>
                </ol>
            </div>
            <div class="page-content fade-in-up">
                <div class="row">
                    <div class="col-md-6">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">service form</div>
                                <div class="ibox-tools">
                                    <a class="ibox-collapse"><i class="fa fa-minus"></i></a>
                                    <a class="dropdown-toggle" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item">option 1</a>
                                        <a class="dropdown-item">option 2</a>
                                    </div>
                                </div>
                            </div>
                            <div class="ibox-body">
                            <form action="" method="post" enctype="multipart/form-data">
                                    <!-- CSRF Token -->
                                    <?php echo get_csrf_input_html(); ?>

                                    <div class="row">
                                       <div class="col-sm-12 form-group">
                                            <label>category name</label>
                                            <select class="form-control" name="category_id" required>
                                                <?php
                                                list($cat_stmt, $category_data) = db_query_prepared($conn, "SELECT * FROM hs_category");
                                                while($category_row = mysqli_fetch_array($category_data))
                                                {
                                                ?>
                                                <option value="<?php echo esc($category_row['category_id']); ?>" <?php if($category_row['category_id'] == $service_row['category_id']) echo 'selected'; ?>><?php echo esc($category_row['category_title']); ?></option>
                                                <?php
                                                }
                                                if ($cat_stmt) {
                                                    mysqli_stmt_close($cat_stmt);
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>sub_category name</label>
                                            <select class="form-control" name="sub_category_id" required>
                                                <?php
                                                list($scat_stmt, $sub_category_data) = db_query_prepared($conn, "SELECT * FROM hs_sub_category");
                                                while($sub_category_row = mysqli_fetch_array($sub_category_data))
                                                {
                                                ?>
                                                <option value="<?php echo esc($sub_category_row['sub_category_id']); ?>" <?php if($sub_category_row['sub_category_id'] == $service_row['sub_category_id']) echo 'selected'; ?>><?php echo esc($sub_category_row['sub_category_title']); ?></option>
                                                <?php
                                                }
                                                if ($scat_stmt) {
                                                    mysqli_stmt_close($scat_stmt);
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>service Title</label>
                                            <input class="form-control" type="text" placeholder="service Title" value="<?php echo esc($service_row['service_title']);?>" name="service_title" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>service Description</label>
                                            <input class="form-control" type="text" placeholder="service description" value="<?php echo esc($service_row['service_description']);?>" name="service_description" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>service Thumb</label>
                                            <input class="form-control" type="file" placeholder="service thumb" name="service_thumb">
                                            <small>Current Image: <?php echo esc($service_row['service_thumb']); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button class="btn btn-default" name="submit" type="submit">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                include('common/footer.php');
            ?>
        </div>
    </div>
    <!-- BEGIN THEME CONFIG PANEL-->
    <?php
        include('common/configue_panel.php');
    ?>
    <!-- END THEME CONFIG PANEL-->
    <!-- BEGIN PAGA BACKDROPS-->
    <?php
        include('common/preloader.php');
    ?>
    <!-- END PAGA BACKDROPS-->
    <!-- CORE PLUGINS-->
   <?php
    include('common/main_footer.php');
   ?>
</body>
</html>