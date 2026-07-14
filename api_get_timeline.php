<?php
header('Content-Type: application/json');
include_once(__DIR__ . '/dbcon.php');

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit();
}

// Fetch timeline entries
$sql = "SELECT * FROM hs_appointment_timeline WHERE appointment_id = ? ORDER BY created_at ASC";
$stmt = mysqli_prepare($conn, $sql);
$entries = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['created_at_formatted'] = date('M d, Y h:i A', strtotime($row['created_at']));
        $entries[] = $row;
    }
    mysqli_stmt_close($stmt);
}

echo json_encode(['success' => true, 'timeline' => $entries]);
?>
