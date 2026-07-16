<?php
    include('common/css.php');
?>

<body class="fixed-navbar">
    <div class="page-wrapper">
        <!-- START HEADER-->
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
                <h1 class="page-title">Doctors</h1>
             
            </div>
            <div class="page-content fade-in-up">
                <div class="ibox">
                    <div class="ibox-head">
                        <div class="ibox-title">All Doctors</div>
                    </div>
                    <div class="ibox-body anyClass">
                        <table class="table table-striped table-bordered table-hover" id="example-table" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                 
                                    <th>Category Name</th>
                                    <th>Sub Category Name</th>
                                    <th>Service Title</th>
                                    <th>Doctor Fname</th>
                                    <th>Doctor Mname</th>
                                    <th>Doctor Lname</th>
                                    <th>Doctor Email</th>
                                    <th>Doctor Password</th>
                                    <th>Doctor Phone</th>
                                    <th>Doctor Address</th>
                                    <th>Doctor Dob</th>
                                    <th>Doctor Doj</th>
                                    <th>Doctor Gender</th>
                                    <th>Doctor Thumb</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                    
            
                                </tr>
                            </thead>
                           
                            <tbody>
                                <?php
                                // Old Logic:
                                // $doctor_data=mysqli_query($conn,"select * from hs_doctor");
                                // while($doctor_row=mysqli_fetch_array($doctor_data))
                                // {
                                //     $role_data=mysqli_query($conn,"select * from hs_role where role_id=$doctor_row[role_id]");
                                //     $role_row=mysqli_fetch_array($role_data);
                                // 
                                // $category_data=mysqli_query($conn,"select * from hs_category where category_id=$doctor_row[category_id]");
                                // $category_row=mysqli_fetch_array($category_data);
                                // 
                                // $sub_category_data=mysqli_query($conn,"select * from hs_sub_category where sub_category_id=$doctor_row[sub_category_id]");
                                // $sub_category_row=mysqli_fetch_array($sub_category_data);
                                // 
                                // $service_data=mysqli_query($conn,"select * from hs_service where service_id=$doctor_row[service_id]");
                                // $service_row=mysqli_fetch_array($service_data);
                                
                                // New Optimized Logic with LEFT JOINs:
                                list($doc_stmt, $doctor_data) = db_query_prepared($conn, "
                                    SELECT d.*, 
                                           c.category_title, 
                                           sc.sub_category_title, 
                                           s.service_title, 
                                           r.role_title
                                    FROM hs_doctor d
                                    LEFT JOIN hs_category c ON d.category_id = c.category_id
                                    LEFT JOIN hs_sub_category sc ON d.sub_category_id = sc.sub_category_id
                                    LEFT JOIN hs_service s ON d.service_id = s.service_id
                                    LEFT JOIN hs_role r ON d.role_id = r.role_id
                                ");
                                while($doctor_row = mysqli_fetch_array($doctor_data))
                                {
                                    $category_row = ['category_title' => $doctor_row['category_title']];
                                    $sub_category_row = ['sub_category_title' => $doctor_row['sub_category_title']];
                                    $service_row = ['service_title' => $doctor_row['service_title']];
                                    $role_row = ['role_title' => $doctor_row['role_title']];
                                ?>
                                <tr>
                                    
                                    <td><?php echo esc($category_row ? ($category_row['category_title'] ?? '') : '');?></td>
                                    <td><?php echo esc($sub_category_row ? ($sub_category_row['sub_category_title'] ?? '') : '');?></td>
                                    <td><?php echo esc($service_row ? ($service_row['service_title'] ?? '') : '');?></td>
                                    <td><?php echo esc($doctor_row['doctor_fname']);?></td>
                                    <td><?php echo esc($doctor_row['doctor_mname']);?></td>
                                    <td><?php echo esc($doctor_row['doctor_lname']);?></td>
                                    <td><?php echo esc($doctor_row['doctor_email']);?></td>
                                    <td>[Encrypted]</td>
                                    <td><?php echo esc($doctor_row['doctor_phone']);?></td>
                                    <td><?php echo esc($doctor_row['doctor_address']);?></td>
                                    <td><?php echo esc($doctor_row['doctor_dob']);?></td>
                                    <td><?php echo esc($doctor_row['doctor_doj']);?></td>
                                    <td><?php echo esc($doctor_row['doctor_gender']);?></td>
                                    <td><img height="100" width="100" src="uploads/<?php echo esc($doctor_row['doctor_thumb']);?>"/></td>
                                    <td><a href="edit_doctor.php?doctor_id=<?php echo esc($doctor_row['doctor_id']);?>"><i class="fa fa-edit"></i></a></td>
                                    <td><a href="delete_doctor.php?doctor_id=<?php echo esc($doctor_row['doctor_id']);?>&csrf_token=<?php echo generate_csrf_token(); ?>" onclick="return confirm('Are you sure you want to delete this doctor?');"><i class="fa fa-trash" aria-hidden="true"></i></a></td>
                                    
                                </tr>
                                <?php
                                }
                                if ($doc_stmt) {
                                    mysqli_stmt_close($doc_stmt);
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
            <!-- END PAGE CONTENT-->
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