<?php
header('Content-Type: application/json');
include_once(__DIR__ . '/dbcon.php');

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($doctor_id <= 0 || empty($date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters. Please provide doctor_id and date.']);
    exit();
}

// Validate date format YYYY-MM-DD
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit();
}

// Get day of the week (e.g. 'Monday')
$day_of_week = date('l', strtotime($date));

// Fetch doctor's availability schedule for this day of week
$schedule_sql = "SELECT start_time, end_time, slot_duration, status 
                 FROM hs_doctor_schedule 
                 WHERE doctor_id = ? AND day_of_week = ? AND status = 1";
$stmt = mysqli_prepare($conn, $schedule_sql);

$start_time = '09:00:00';
$end_time = '17:00:00';
$slot_duration = 30;
$is_available_day = true;

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $day_of_week);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];
        $slot_duration = intval($row['slot_duration']);
    } else {
        // Fallback default: Monday to Friday active, Sat/Sun inactive by default if not set
        if ($day_of_week === 'Saturday' || $day_of_week === 'Sunday') {
            $is_available_day = false;
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$is_available_day) {
    echo json_encode(['success' => true, 'slots' => [], 'message' => 'Doctor is not available on ' . $day_of_week . 's.']);
    exit();
}

// Generate slots
$slots = [];
$start_timestamp = strtotime($start_time);
$end_timestamp = strtotime($end_time);
$current_time_stamp = time();
$is_today = ($date === date('Y-m-d'));

// Fetch already booked appointments for this doctor on this date
$booked_times = [];
$booking_sql = "SELECT appointment_time FROM hs_appointment 
                WHERE doctor_id = ? AND appointment_date = ? 
                AND appointment_status NOT IN ('Reject', 'Cancel')";
$booking_stmt = mysqli_prepare($conn, $booking_sql);
if ($booking_stmt) {
    mysqli_stmt_bind_param($booking_stmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($booking_stmt);
    $booking_res = mysqli_stmt_get_result($booking_stmt);
    while ($b_row = mysqli_fetch_assoc($booking_res)) {
        // Normalize time to H:i format
        $booked_time_formatted = date('H:i', strtotime($b_row['appointment_time']));
        $booked_times[] = $booked_time_formatted;
    }
    mysqli_stmt_close($booking_stmt);
}

// Generate the time increments
$loop_time = $start_timestamp;
while ($loop_time < $end_timestamp) {
    $time_h_i = date('H:i', $loop_time);
    $time_formatted = date('h:i A', $loop_time);
    
    // Check if booked
    $is_booked = in_array($time_h_i, $booked_times);
    
    // Check if slot is in the past (if date is today)
    $is_past = false;
    if ($is_today) {
        // Build full timestamp for comparison
        $slot_full_timestamp = strtotime($date . ' ' . $time_h_i);
        if ($slot_full_timestamp <= $current_time_stamp) {
            $is_past = true;
        }
    }
    
    $available = !$is_booked && !$is_past;
    
    $slots[] = [
        'time' => $time_h_i . ':00', // standard database format
        'time_formatted' => $time_formatted,
        'available' => $available,
        'reason' => $is_booked ? 'booked' : ($is_past ? 'past' : 'available')
    ];
    
    // Advance by slot duration minutes
    $loop_time = strtotime('+' . $slot_duration . ' minutes', $loop_time);
}

echo json_encode([
    'success' => true,
    'date' => $date,
    'day' => $day_of_week,
    'slots' => $slots
]);
?>
