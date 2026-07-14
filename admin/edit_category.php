<?php
include('common/css.php');

$category_id = intval($_GET['category_id'] ?? 0);

list($select_stmt, $select_category) = db_query_prepared($conn, "SELECT * FROM hs_category WHERE category_id = ?", [$category_id]);
$category_row = mysqli_fetch_array($select_category);
if ($select_stmt) {
    mysqli_stmt_close($select_stmt);
}

if (!$category_row) {
    echo "<script>alert('Category not found.'); window.location.href='display_category.php';</script>";
    exit();
}

if (isset($_POST['submit'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $category_title = $_POST['category_title'];
    $category_description = $_POST['category_description'];

    $category_thumb_org = $category_row['category_thumb'];
    
    // Check if a new file is uploaded
    if (!empty($_FILES['category_thumb']['name'])) {
        $file_validation = validate_file_upload($_FILES['category_thumb']);
        if (!$file_validation['success']) {
            echo "<script>alert('" . esc($file_validation['message']) . "'); window.history.back();</script>";
            exit();
        }
        $category_thumb_org = $file_validation['new_name'];
        $path = "uploads/";
        $imagepath = $path . $category_thumb_org;
        move_uploaded_file($_FILES['category_thumb']['tmp_name'], $imagepath);
    } 

    $update_stmt = mysqli_prepare($conn, "UPDATE hs_category SET category_title=?, category_description=?, category_thumb=? WHERE category_id = ?");
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "sssi", $category_title, $category_description, $category_thumb_org, $category_id);
        $query = mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
        if ($query) {
            echo "<script>alert('Category updated successfully.'); window.location.href='display_category.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error updating category.');</script>";
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
                <h1 class="page-title">Category Form</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php"><i class="la la-home font-20"></i></a>
                    </li>
                    <li class="breadcrumb-item">Category Form</li>
                </ol>
            </div>
            <div class="page-content fade-in-up">
                <div class="row">
                    <div class="col-md-6">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Category Form</div>
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
                                            <label>Category Title</label>
                                            <input class="form-control" type="text" placeholder="Category Title" value="<?php echo esc($category_row['category_title']); ?>" name="category_title" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Category Description</label>
                                            <input class="form-control" type="text" placeholder="Category Description" value="<?php echo esc($category_row['category_description']); ?>" name="category_description" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Category Thumb</label>
                                            <input class="form-control" type="file" placeholder="Category Thumb" name="category_thumb">
                                            <small>Current Image: <?php echo esc($category_row['category_thumb']); ?></small>
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
