<?php
include_once(__DIR__ . '/../dbcon.php');

echo "Starting database schema update...\n";

// 1. Create hs_doctor_schedule table
$sql_schedule = "CREATE TABLE IF NOT EXISTS `hs_doctor_schedule` (
  `schedule_id` INT AUTO_INCREMENT PRIMARY KEY,
  `doctor_id` INT NOT NULL,
  `day_of_week` VARCHAR(15) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `slot_duration` INT NOT NULL DEFAULT 30,
  `status` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `doc_day` (`doctor_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (mysqli_query($conn, $sql_schedule)) {
    echo "Table hs_doctor_schedule created or verified successfully.\n";
} else {
    echo "Error creating hs_doctor_schedule: " . mysqli_error($conn) . "\n";
}

// 2. Create hs_appointment_timeline table
$sql_timeline = "CREATE TABLE IF NOT EXISTS `hs_appointment_timeline` (
  `timeline_id` INT AUTO_INCREMENT PRIMARY KEY,
  `appointment_id` INT NOT NULL,
  `status_action` VARCHAR(50) NOT NULL,
  `action_by` VARCHAR(20) NOT NULL,
  `comments` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (mysqli_query($conn, $sql_timeline)) {
    echo "Table hs_appointment_timeline created or verified successfully.\n";
} else {
    echo "Error creating hs_appointment_timeline: " . mysqli_error($conn) . "\n";
}

// 3. Create hs_email_log table
$sql_email_log = "CREATE TABLE IF NOT EXISTS `hs_email_log` (
  `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
  `recipient_email` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if (mysqli_query($conn, $sql_email_log)) {
    echo "Table hs_email_log created or verified successfully.\n";
} else {
    echo "Error creating hs_email_log: " . mysqli_error($conn) . "\n";
}

// 4. Seed default schedules for all existing doctors
$doctors_res = mysqli_query($conn, "SELECT doctor_id FROM hs_doctor");
$weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$default_start = '09:00:00';
$default_end = '17:00:00';
$default_duration = 30;

$seeded_count = 0;
while ($doc = mysqli_fetch_assoc($doctors_res)) {
    $doctor_id = $doc['doctor_id'];
    foreach ($weekdays as $day) {
        $check_sql = "SELECT 1 FROM hs_doctor_schedule WHERE doctor_id = $doctor_id AND day_of_week = '$day'";
        $check_res = mysqli_query($conn, $check_sql);
        if (mysqli_num_rows($check_res) == 0) {
            $insert_sql = "INSERT INTO hs_doctor_schedule (doctor_id, day_of_week, start_time, end_time, slot_duration, status) 
                           VALUES ($doctor_id, '$day', '$default_start', '$default_end', $default_duration, 1)";
            if (mysqli_query($conn, $insert_sql)) {
                $seeded_count++;
            }
        }
    }
}
echo "Seeded $seeded_count default schedule slots for doctors.\n";
echo "Database schema update finished.\n";
?>
