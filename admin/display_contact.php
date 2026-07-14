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
                <h1 class="page-title">Contact Details</h1>
               
            </div>
            <div class="page-content fade-in-up">
                <div class="ibox">
                    <div class="ibox-head">
                        <div class="ibox-title">All Contact Details</div>
                    </div>
                    <div class="ibox-body anyClass">
                        <table class="table table-striped table-bordered table-hover" id="example-table" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Message</th>  
                                    <th>Delete</th>
            
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Message</th>
                                   <th>Delete</th>  
                                </tr>
                            </tfoot>
                            <tbody>
                                <?php
                                // Get all contact records
                                list($contact_stmt, $role_data) = db_query_prepared($conn, "SELECT * FROM hs_contact");
                                while($role_row = mysqli_fetch_array($role_data))
                                {
                                ?>
                                <tr>
                                    
                                    <td><?php echo esc($role_row['hs_name']);?></td>
                                    <td><?php echo esc($role_row['hs_phone']);?></td>
                                    <td><?php echo esc($role_row['hs_email']);?></td>
                                    <td><?php echo esc($role_row['hs_message']);?></td>
                                    <td><a href="delete_contact.php?hs_id=<?php echo esc($role_row['hs_id']);?>&csrf_token=<?php echo generate_csrf_token(); ?>" onclick="return confirm('Are you sure you want to delete this contact?');"><i class="fa fa-trash" aria-hidden="true"></i></a></td>
                                    
                                </tr>
                                <?php
                                }
                                if ($contact_stmt) {
                                    mysqli_stmt_close($contact_stmt);
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