<?php
session_start();
require_once '/var/www/html/pratice/hoperise_medical/dbcon.php';

// Simulate active Admin session
$_SESSION['admin_id'] = 10;
$_SESSION['role_id'] = 1; // Admin role

// Generate token
$token = generate_csrf_token();
echo "Generated CSRF Token: $token\n";

// Set up simulated POST status update request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf_token' => $token,
    'action' => 'update_status',
    'id' => 1,
    'status' => 'Contacted'
];

// Capture output/redirection of the admin controller
ob_start();
try {
    include '/var/www/html/pratice/hoperise_medical/admin/demo_requests.php';
} catch (Exception $e) {
    echo "Caught exception: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

// Check DB updated status
$query = "SELECT status FROM hospital_demo_requests WHERE id = 1";
$res = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($res)) {
    echo "Status after update: " . $row['status'] . "\n";
} else {
    echo "Error: Row not found!\n";
}
?>
