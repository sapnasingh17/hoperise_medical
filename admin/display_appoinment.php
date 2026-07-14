<?php
    include('common/css.php');
?>
<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 20px;
    list-style: none;
    margin-top: 10px;
}
.timeline::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    left: 5px;
    width: 2px;
    background: #e2e8f0;
}
.timeline-item {
    position: relative;
    margin-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    top: 5px;
    left: -20px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #2563eb;
    border: 2px solid #fff;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
}
.timeline-item.cancelled .timeline-marker,
.timeline-item.rejected .timeline-marker,
.timeline-item.cancel .timeline-marker,
.timeline-item.reject .timeline-marker {
    background: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
}
.timeline-item.confirmed .timeline-marker,
.timeline-item.accept .timeline-marker,
.timeline-item.booked .timeline-marker {
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
}
.timeline-content {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    margin-left: 10px;
}
.timeline-title {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 2px;
    color: #0f172a;
}
.timeline-date {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 4px;
}
.timeline-body {
    font-size: 12px;
    color: #475569;
    line-height: 1.4;
}
</style>

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
                <h1 class="page-title">Appointments</h1>
            </div>
            <div class="page-content fade-in-up">
                <div class="ibox">
                    <div class="ibox-head" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="ibox-title">All Appointments</div>
                    </div>
                    <div class="ibox-body anyClass">
                        <table class="table table-striped table-bordered table-hover" id="example-table" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Sub Category Name</th>
                                    <th>Service Name</th>
                                    <th>Patient Name</th>
                                    <th>Doctor Name</th>
                                    <th>Appointment Date</th>
                                    <th>Appointment Time</th>
                                    <th>Appointment Description</th>
                                    <th>Appointment Status</th> 
                                    <th>Payment</th>
                                    <th>Timeline</th> 
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Sub Category Name</th>
                                    <th>Service Name</th>
                                    <th>Patient Name</th>
                                    <th>Doctor Name</th>
                                    <th>Appointment Date</th>
                                    <th>Appointment Time</th>
                                    <th>Appointment Description</th>
                                    <th>Appointment Status</th>
                                    <th>Payment</th>
                                    <th>Timeline</th> 
                                </tr>
                            </tfoot>
                            <tbody>
                                <?php
                                $select_query = "
                                    SELECT a.*, 
                                           c.category_title, 
                                           sc.sub_category_title, 
                                           s.service_title, 
                                           d.doctor_fname, d.doctor_lname,
                                           p.patient_fname, p.patient_lname
                                    FROM hs_appointment a
                                    LEFT JOIN hs_category c ON a.category_id = c.category_id
                                    LEFT JOIN hs_sub_category sc ON a.sub_category_id = sc.sub_category_id
                                    LEFT JOIN hs_service s ON a.service_id = s.service_id
                                    LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id
                                    LEFT JOIN hs_patient p ON a.patient_id = p.patient_id
                                ";

                                $select_params = [];
                                $select_types = '';

                                if ($role == 'doctor') {
                                    $select_query .= " WHERE a.doctor_id = ?";
                                    $select_params[] = $admin_id;
                                    $select_types .= 'i';
                                }

                                list($appt_stmt, $appoinment_data) = db_query_prepared($conn, $select_query, $select_params, $select_types);
                                
                                if (mysqli_num_rows($appoinment_data) == 0) {
                                    echo "<tr><td colspan='10' class='text-center py-4 text-secondary'><i class='fa fa-calendar-times-o fa-2x d-block mb-2'></i>No appointments found.</td></tr>";
                                } else {
                                    while($appoinment_row = mysqli_fetch_array($appoinment_data))
                                    {
                                        $category_row = ['category_title' => $appoinment_row['category_title']];
                                        $sub_category_row = ['sub_category_title' => $appoinment_row['sub_category_title']];
                                        $service_row = ['service_title' => $appoinment_row['service_title']];
                                        $doctor_row = ['doctor_fname' => $appoinment_row['doctor_fname'], 'doctor_lname' => $appoinment_row['doctor_lname']];
                                        $patient_row = ['patient_fname' => $appoinment_row['patient_fname'], 'patient_lname' => $appoinment_row['patient_lname']];
                                    ?>
                                    <tr>
                                        <td><?php echo esc($category_row ? ($category_row['category_title'] ?? '') : '');?></td>
                                        <td><?php echo esc($sub_category_row ? ($sub_category_row['sub_category_title'] ?? '') : '');?></td>
                                        <td><?php echo esc($service_row ? ($service_row['service_title'] ?? '') : '');?></td>
                                        <td><?php echo esc($patient_row ? (($patient_row['patient_fname'] ?? '') . " " . ($patient_row['patient_lname'] ?? '')) : '');?></td>
                                        <td><?php echo esc($doctor_row ? (($doctor_row['doctor_fname'] ?? '') . " " . ($doctor_row['doctor_lname'] ?? '')) : '');?></td>
                                        <td><?php echo esc($appoinment_row['appointment_date']);?></td>
                                        <td><?php echo esc($appoinment_row['appointment_time']);?></td>
                                        <td><?php echo esc($appoinment_row['appointment_description']);?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <select class="form-select form-select-sm select-appt-status" data-id="<?php echo esc($appoinment_row['appointment_id']); ?>" style="width: 110px; font-size: 13px; font-weight: 600; padding: 2px 6px; border-radius: 4px;">
                                                    <?php
                                                    $st = strtolower($appoinment_row['appointment_status'] ?? '');
                                                    ?>
                                                    <option value="Pending" class="text-warning font-weight-bold" <?php if ($st === 'pending' || $st === '') echo 'selected'; ?>>Pending</option>
                                                    <option value="Accept" class="text-success font-weight-bold" <?php if ($st === 'accept' || $st === 'confirmed') echo 'selected'; ?>>Accept</option>
                                                    <option value="Reject" class="text-danger font-weight-bold" <?php if ($st === 'reject') echo 'selected'; ?>>Reject</option>
                                                    <option value="Cancel" class="text-secondary font-weight-bold" <?php if ($st === 'cancel') echo 'selected'; ?>>Cancel</option>
                                                </select>
                                                <span class="status-indicator" id="indicator-<?php echo esc($appoinment_row['appointment_id']); ?>" style="display:none; font-size: 13px;">
                                                    <i class="fa fa-spinner fa-spin text-primary"></i>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($appoinment_row['appointment_payment_status'] == 1): ?>
                                                <span class="badge badge-success px-2 py-1" style="font-size: 11px; font-weight: 700; border-radius: 4px;"><i class="fa fa-check-circle mr-1"></i>Paid</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger px-2 py-1" style="font-size: 11px; font-weight: 700; border-radius: 4px;"><i class="fa fa-times-circle mr-1"></i>Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-info btn-xs btn-timeline-appt-toggle" data-id="<?php echo esc($appoinment_row['appointment_id']); ?>">
                                                <i class="fa fa-clock-o"></i> History
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                    }
                                }
                                if ($appt_stmt) {
                                    mysqli_stmt_close($appt_stmt);
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

    <script>
    $(document).ready(function() {
        // Retrieve standard DataTable instance
        var table = $('#example-table').DataTable();

        // Handle inline status dropdown changes via AJAX
        $('.select-appt-status').on('change', function() {
            var selectEl = $(this);
            var apptId = selectEl.data('id');
            var statusVal = selectEl.val();
            var indicator = $('#indicator-' + apptId);

            indicator.html('<i class="fa fa-spinner fa-spin text-primary"></i>').show();

            $.ajax({
                url: 'api_update_appointment_status.php',
                type: 'POST',
                data: {
                    appointment_id: apptId,
                    appointment_status: statusVal
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        indicator.html('<i class="fa fa-check text-success"></i>').delay(1500).fadeOut();
                    } else {
                        indicator.html('<i class="fa fa-times text-danger" title="' + response.message + '"></i>');
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    indicator.html('<i class="fa fa-times text-danger" title="Network/Server error"></i>');
                    alert('An error occurred while updating the status.');
                }
            });
        });

        // Handle timeline toggle button using DataTables child rows
        $('#example-table tbody').on('click', '.btn-timeline-appt-toggle', function() {
            var btn = $(this);
            var apptId = btn.data('id');
            var tr = btn.closest('tr');
            var row = table.row(tr);

            if (row.child.isShown()) {
                // Child row is already open, close it
                row.child.hide();
                tr.removeClass('shown');
                btn.html('<i class="fa fa-clock-o"></i> History');
                btn.removeClass('btn-info').addClass('btn-outline-info');
            } else {
                // Open child row
                var childContent = '<div class="px-4 py-3 bg-light border rounded" style="margin-left: 20px;">' +
                                   '  <h6 class="font-weight-bold text-primary mb-3"><i class="fa fa-clock-o"></i> Appointment Timeline History</h6>' +
                                   '  <ul class="timeline" id="timeline_list_' + apptId + '">' +
                                   '    <div class="text-center py-2 text-muted"><i class="fa fa-spinner fa-spin mr-1"></i>Loading history...</div>' +
                                   '  </ul>' +
                                   '</div>';
                row.child(childContent).show();
                tr.addClass('shown');
                btn.html('<i class="fa fa-chevron-up"></i> Close');
                btn.removeClass('btn-outline-info').addClass('btn-info');

                // Load history dynamically via AJAX
                $.ajax({
                    url: '../api_get_timeline.php',
                    type: 'GET',
                    data: { appointment_id: apptId },
                    dataType: 'json',
                    success: function(data) {
                        var $list = $('#timeline_list_' + apptId);
                        $list.empty();
                        if (data.success && data.timeline && data.timeline.length > 0) {
                            $.each(data.timeline, function(index, item) {
                                var actionClass = item.status_action.toLowerCase();
                                var li = '<li class="timeline-item ' + actionClass + '">' +
                                         '  <div class="timeline-marker"></div>' +
                                         '  <div class="timeline-content">' +
                                         '    <div class="timeline-title">' + item.status_action + '</div>' +
                                         '    <div class="timeline-date">' + item.created_at_formatted + ' by ' + item.action_by.toUpperCase() + '</div>' +
                                         '    <div class="timeline-body">' + (item.comments || 'No comments left.') + '</div>' +
                                         '  </div>' +
                                         '</li>';
                                $list.append(li);
                            });
                        } else {
                            $list.html('<div class="text-center py-2 text-muted">No history timeline events found.</div>');
                        }
                    },
                    error: function() {
                        $('#timeline_list_' + apptId).html('<div class="text-center py-2 text-danger">Error loading history.</div>');
                    }
                });
            }
        });
    });
    </script>
</body>
</html>