<?php
include('common/css.php');

if(isset($_POST['submit']))
{
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<script>alert('CSRF Verification Failed'); window.history.back();</script>";
        exit();
    }

    $category_id = intval($_POST['category_id']);
    $sub_category_title = $_POST['sub_category_title'];
    $sub_category_description = $_POST['sub_category_description'];
    
    // Strict file upload check
    $file_validation = validate_file_upload($_FILES['sub_category_thumb']);
    if (!$file_validation['success']) {
        echo "<script>alert('" . esc($file_validation['message']) . "'); window.history.back();</script>";
        exit();
    }
    
    $sub_category_thumb_org = $file_validation['new_name'];
    $path = "uploads/";
    $imagepath = $path . $sub_category_thumb_org;
    move_uploaded_file($_FILES['sub_category_thumb']['tmp_name'], $imagepath);

    $stmt = mysqli_prepare($conn, "INSERT INTO hs_sub_category (category_id, sub_category_title, sub_category_description, sub_category_thumb) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $category_id, $sub_category_title, $sub_category_description, $sub_category_thumb_org);
        $query = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if($query)
        { 
            echo "<script>alert('Sub-Category Inserted Successfully!'); window.location.href='display_sub_category.php';</script>";
            exit();
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
                <h1 class="page-title">Sub_Category</h1>
            </div>
            <div class="page-content fade-in-up">
                <div class="row">
                    <div class="col-md-6">
                        <div class="ibox">
                            <div class="ibox-head">
                                <div class="ibox-title">Add Sub_Category </div>
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
                                            <label>Category name</label>
                                            <select class="form-control" name="category_id" required>
                                                <?php
                                                list($cat_stmt, $category_data) = db_query_prepared($conn, "SELECT * FROM hs_category");
                                                while($category_row = mysqli_fetch_array($category_data))
                                                {
                                                ?>
                                                <option value="<?php echo esc($category_row['category_id']); ?>"><?php echo esc($category_row['category_title']); ?></option>
                                                <?php
                                                }
                                                if ($cat_stmt) {
                                                    mysqli_stmt_close($cat_stmt);
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Sub_Category Title</label>
                                            <input class="form-control" type="text" placeholder="sub_category Title" name="sub_category_title" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Sub_Category Description</label>
                                            <input class="form-control" type="text" placeholder="sub_category description" name="sub_category_description" required>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Sub_Category Thumb</label>
                                            <input class="form-control" type="file" placeholder="sub_category thumb" name="sub_category_thumb" required>
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