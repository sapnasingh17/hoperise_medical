<?php
include('common/css.php');

$sub_category_id = intval($_GET['sub_category_id'] ?? 0);

// Fetch the existing sub-category details
list($select_stmt, $select_sub_category) = db_query_prepared($conn, "SELECT * FROM hs_sub_category WHERE sub_category_id = ?", [$sub_category_id]);
$sub_category_row = mysqli_fetch_array($select_sub_category);
if ($select_stmt) {
    mysqli_stmt_close($select_stmt);
}

if (!$sub_category_row) {
    echo "<script>alert('Sub-category not found.'); window.location.href='display_sub_category.php';</script>";
    exit();
}

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $category_id = intval($_POST['category_id']);
    $sub_category_title = $_POST['sub_category_title'];
    $sub_category_description = $_POST['sub_category_description'];
    
    $sub_category_thumb_org = $sub_category_row['sub_category_thumb'];

    // Check if a new image is uploaded
    if (!empty($_FILES['sub_category_thumb']['name'])) {
        $file_validation = validate_file_upload($_FILES['sub_category_thumb']);
        if (!$file_validation['success']) {
            echo "<script>alert('" . esc($file_validation['message']) . "'); window.history.back();</script>";
            exit();
        }
        $sub_category_thumb_org = $file_validation['new_name'];
        $path = "uploads/";
        $imagepath = $path . $sub_category_thumb_org;
        move_uploaded_file($_FILES['sub_category_thumb']['tmp_name'], $imagepath);
    }

    $update_stmt = mysqli_prepare($conn, "UPDATE hs_sub_category SET category_id = ?, sub_category_title = ?, sub_category_description = ?, sub_category_thumb = ? WHERE sub_category_id = ?");
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "isssi", $category_id, $sub_category_title, $sub_category_description, $sub_category_thumb_org, $sub_category_id);
        $query = mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        if ($query) {
            echo "<script>alert('Sub-category updated successfully.'); window.location.href='display_sub_category.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating sub-category.');</script>";
        }
    }
}
?>

<body class="fixed-navbar">
    <div class="page-wrapper">
        <?php include('common/header.php'); ?>
        <!-- END HEADER-->
        <!-- START SIDEBAR-->
        <?php include('common/sidebar.php'); ?>
        <!-- END SIDEBAR-->
        <div class="content-wrapper">
            <!-- START PAGE CONTENT-->
            <div class="page-heading">
                <h1 class="page-title">Sub-category Form</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="la la-home font-20"></i></a>
                    </li>
                    <li class="breadcrumb-item">Sub-category Form</li>
                </ol>
            </div>
            <div class="page-content fade-in-up">
                <div class="row">
                    <div class="col-md-6">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Sub-category Form</div>
                                <div class="ibox-tools">
                                    <a class="ibox-collapse"><i class="fa fa-minus"></i></a>
                                    <a class="dropdown-toggle" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item">Option 1</a>
                                        <a class="dropdown-item">Option 2</a>
                                    </div>
                                </div>
                            </div>
                            <div class="ibox-body">
                                <form action="" method="post" enctype="multipart/form-data">
                                    <!-- CSRF Token -->
                                    <?php echo get_csrf_input_html(); ?>

                                    <div class="row">
                                        <div class="col-sm-12 form-group">
                                            <label>Category Name</label>
                                            <select class="form-control" name="category_id" required>
                                                <?php
                                                    // Fetch all categories for the select input
                                                    list($cat_stmt, $category_data) = db_query_prepared($conn, "SELECT * FROM hs_category");
                                                    while ($category_row = mysqli_fetch_array($category_data)) {
                                                ?>
                                                <option value="<?php echo esc($category_row['category_id']); ?>" <?php echo ($category_row['category_id'] == $sub_category_row['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo esc($category_row['category_title']); ?>
                                                </option>
                                                <?php 
                                                    } 
                                                    if ($cat_stmt) mysqli_stmt_close($cat_stmt);
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Sub-category Title</label>
                                            <input class="form-control" type="text" placeholder="Sub-category Title" value="<?php echo esc($sub_category_row['sub_category_title']); ?>" name="sub_category_title" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Sub-category Description</label>
                                            <input class="form-control" type="text" placeholder="Sub-category Description" value="<?php echo esc($sub_category_row['sub_category_description']); ?>" name="sub_category_description" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Sub-category Thumb</label>
                                            <input class="form-control" type="file" name="sub_category_thumb">
                                            <small>Current Image: <?php echo esc($sub_category_row['sub_category_thumb']); ?></small>
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
            <?php include('common/footer.php'); ?>
        </div>
    </div>
    <!-- BEGIN THEME CONFIG PANEL-->
    <?php include('common/configue_panel.php'); ?>
    <!-- END THEME CONFIG PANEL-->
    <!-- BEGIN PAGA BACKDROPS-->
    <?php include('common/preloader.php'); ?>
    <!-- END PAGA BACKDROPS-->
    <!-- CORE PLUGINS-->
    <?php include('common/main_footer.php'); ?>
</body>
</html>
