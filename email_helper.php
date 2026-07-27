<?php
include_once(__DIR__ . '/dbcon.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/plugins/PHPMailer/Exception.php';
require_once __DIR__ . '/plugins/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/plugins/PHPMailer/SMTP.php';

if (!function_exists('send_appointment_email')) {
    function send_appointment_email($conn, $appointment_id, $action, $comments = '') {
        $appointment_id = intval($appointment_id);
        
        // Fetch appointment details
        $sql = "SELECT a.*, 
                       p.patient_fname, p.patient_lname, p.patient_email,
                       d.doctor_fname, d.doctor_lname, d.doctor_email,
                       c.category_title, 
                       s.service_title
                FROM hs_appointment a
                LEFT JOIN hs_patient p ON a.patient_id = p.patient_id
                LEFT JOIN hs_doctor d ON a.doctor_id = d.doctor_id
                LEFT JOIN hs_category c ON a.category_id = c.category_id
                LEFT JOIN hs_service s ON a.service_id = s.service_id
                WHERE a.appointment_id = $appointment_id";
                
        $res = mysqli_query($conn, $sql);
        if (!$res || mysqli_num_rows($res) == 0) {
            return false;
        }
        
        $data = mysqli_fetch_assoc($res);
        $patient_name = htmlspecialchars($data['patient_fname'] . ' ' . $data['patient_lname']);
        $patient_email = $data['patient_email'];
        $doctor_name = htmlspecialchars('Dr. ' . $data['doctor_fname'] . ' ' . $data['doctor_lname']);
        $doctor_email = $data['doctor_email'];
        $app_date = htmlspecialchars($data['appointment_date']);
        $app_time = htmlspecialchars(date('h:i A', strtotime($data['appointment_time'])));
        $specialty = htmlspecialchars($data['category_title']);
        $service = htmlspecialchars($data['service_title']);
        $status = htmlspecialchars($data['appointment_status']);
        
        // Subject based on action
        switch ($action) {
            case 'booked':
                $subject = "Appointment Requested - Hope Rise Medical";
                $header_title = "Appointment Requested";
                $message_intro = "Dear $patient_name, your appointment request has been successfully submitted and is currently pending review.";
                break;
            case 'status_updated':
                $subject = "Appointment Status Updated - Hope Rise Medical";
                $header_title = "Appointment Status: " . ucfirst($status);
                $message_intro = "Dear $patient_name, your appointment status has been updated to <strong>$status</strong> by the medical team.";
                break;
            case 'rescheduled':
                $subject = "Appointment Rescheduled - Hope Rise Medical";
                $header_title = "Appointment Rescheduled";
                $message_intro = "Dear $patient_name, your appointment has been successfully rescheduled to a new slot.";
                break;
            case 'cancelled':
                $subject = "Appointment Cancelled - Hope Rise Medical";
                $header_title = "Appointment Cancelled";
                $message_intro = "Dear $patient_name, your appointment has been cancelled.";
                break;
            default:
                $subject = "Appointment Notification - Hope Rise Medical";
                $header_title = "Appointment Update";
                $message_intro = "Dear $patient_name, there has been an update regarding your appointment.";
        }

        if (!empty($comments)) {
            $message_intro .= "<br><br><strong>Note:</strong> " . htmlspecialchars($comments);
        }

        // Beautiful Premium HTML Email Template
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $subject . '</title>
            <style>
                body { font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; color: #1f2937; }
                .email-wrapper { width: 100%; background-color: #f3f4f6; padding: 20px 0; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
                .header { background-color: #2563eb; padding: 30px; text-align: center; color: #ffffff; }
                .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
                .content { padding: 30px; }
                .intro-text { font-size: 16px; line-height: 1.6; margin-bottom: 24px; color: #4b5563; }
                .details-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
                .details-title { font-size: 18px; font-weight: 600; margin-top: 0; margin-bottom: 16px; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
                .details-grid { display: grid; gap: 12px; }
                .details-row { display: flex; justify-content: space-between; font-size: 14px; line-height: 1.5; }
                .details-label { color: #64748b; font-weight: 500; }
                .details-val { color: #0f172a; font-weight: 600; text-align: right; }
                .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
                .badge-pending { background-color: #fef3c7; color: #d97706; }
                .badge-accept { background-color: #dcfce7; color: #16a34a; }
                .badge-reject { background-color: #fee2e2; color: #dc2626; }
                .badge-cancel { background-color: #f3f4f6; color: #4b5563; }
                .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
                .footer a { color: #2563eb; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="container">
                    <div class="header">
                        <h1>' . $header_title . '</h1>
                    </div>
                    <div class="content">
                        <div class="intro-text">
                            ' . $message_intro . '
                        </div>
                        <div class="details-card">
                            <h3 class="details-title">Consultation Details</h3>
                            <div class="details-grid">
                                <div class="details-row">
                                    <span class="details-label">Appointment ID</span>
                                    <span class="details-val">#' . $appointment_id . '</span>
                                </div>
                                <div class="details-row">
                                    <span class="details-label">Specialist</span>
                                    <span class="details-val">' . $doctor_name . '</span>
                                </div>
                                <div class="details-row">
                                    <span class="details-label">Department</span>
                                    <span class="details-val">' . $specialty . '</span>
                                </div>
                                <div class="details-row">
                                    <span class="details-label">Service</span>
                                    <span class="details-val">' . $service . '</span>
                                </div>
                                <div class="details-row">
                                    <span class="details-label">Date</span>
                                    <span class="details-val">' . $app_date . '</span>
                                </div>
                                <div class="details-row">
                                    <span class="details-label">Time</span>
                                    <span class="details-val">' . $app_time . '</span>
                                </div>
                                <div class="details-row">
                                    <span class="details-label">Current Status</span>
                                    <span class="details-val">
                                        <span class="badge badge-' . strtolower(strcasecmp($status, 'Accept') === 0 ? 'accept' : (strcasecmp($status, 'Reject') === 0 ? 'reject' : (strcasecmp($status, 'Cancel') === 0 ? 'cancel' : 'pending'))) . '">' . ($status ? $status : 'Pending') . '</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="footer">
                        <p>This is an automated message from Hope Rise Medical Center.</p>
                        <p>&copy; ' . date('Y') . ' Hope Rise Medical. All rights reserved.</p>
                        <p><a href="mailto:support@hoperise.com">support@hoperise.com</a> | Helpline: +91 2215 463 895</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';

        // Retrieve company/admin email and name dynamically from global settings
        $company_email = isset($GLOBALS['company_settings']['email']) && !empty($GLOBALS['company_settings']['email']) ? $GLOBALS['company_settings']['email'] : 'notifications@hoperise.com';
        $company_name = isset($GLOBALS['company_settings']['hospital_name']) && !empty($GLOBALS['company_settings']['hospital_name']) ? $GLOBALS['company_settings']['hospital_name'] : 'Hope Rise Medical';

        // Try to send email to patient
        $sent_patient = send_custom_email($patient_email, $subject, $message, $company_email, $company_name, $conn);

        // Try to send email to doctor as well if doctor_email is valid
        if (!empty($doctor_email)) {
            $doc_subject = "Appointment Update Notification - " . $doctor_name;
            send_custom_email($doctor_email, $doc_subject, $message, $company_email, $company_name, $conn);
        }

        return $sent_patient;
    }
}

if (!function_exists('send_custom_email')) {
    function send_custom_email($to, $subject, $message, $from_email = 'notifications@hoperise.com', $from_name = 'Hope Rise Medical', $conn = null) {
        $smtp_host = defined('SMTP_HOST') ? SMTP_HOST : (getenv('SMTP_HOST') ?: 'smtp.gmail.com');
        $smtp_port = defined('SMTP_PORT') ? intval(SMTP_PORT) : intval(getenv('SMTP_PORT') ?: 587);
        $smtp_user = defined('SMTP_USER') ? SMTP_USER : (getenv('SMTP_USER') ?: '');
        $smtp_pass = defined('SMTP_PASS') ? SMTP_PASS : (getenv('SMTP_PASS') ?: '');
        $smtp_secure = defined('SMTP_SECURE') ? SMTP_SECURE : (getenv('SMTP_SECURE') ?: 'tls');

        $success = false;

        $smtp_configured = !empty($smtp_pass) && $smtp_pass !== 'YOUR_GMAIL_APP_PASSWORD_HERE' && $smtp_pass !== 'your16charapppassword';

        if ($smtp_configured) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtp_host;
                $mail->SMTPAuth   = !empty($smtp_user);
                $mail->Username   = $smtp_user;
                $mail->Password   = $smtp_pass;
                $mail->Port       = $smtp_port;

                if ($smtp_secure === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } else if ($smtp_secure === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPSecure = '';
                    $mail->SMTPAutoTLS = false;
                }

                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                $mail->Timeout = 10;

                $sender_email = !empty($smtp_user) ? $smtp_user : $from_email;
                $mail->setFrom($sender_email, $from_name);
                $mail->addAddress($to);
                $mail->addReplyTo($from_email, $from_name);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $message;
                $mail->AltBody = strip_tags($message);
                $mail->CharSet = 'UTF-8';

                $success = $mail->send();
            } catch (\Exception $e) {
                error_log("PHPMailer SMTP Exception: " . $e->getMessage());
                $success = false;
            }
        }

        // Secondary Attempt: Local SMTP server (e.g. 127.0.0.1:1025)
        if (!$success) {
            try {
                $socket = @fsockopen('127.0.0.1', 1025, $errno, $errstr, 2);
                if ($socket) {
                    fclose($socket);
                    $local_mail = new PHPMailer(true);
                    $local_mail->isSMTP();
                    $local_mail->Host       = '127.0.0.1';
                    $local_mail->Port       = 1025;
                    $local_mail->SMTPAuth   = false;
                    $local_mail->SMTPSecure = '';
                    $local_mail->SMTPAutoTLS = false;
                    $local_mail->Timeout    = 5;

                    $local_mail->setFrom($from_email, $from_name);
                    $local_mail->addAddress($to);
                    $local_mail->addReplyTo($from_email, $from_name);

                    $local_mail->isHTML(true);
                    $local_mail->Subject = $subject;
                    $local_mail->Body    = $message;
                    $local_mail->AltBody = strip_tags($message);
                    $local_mail->CharSet = 'UTF-8';

                    $success = $local_mail->send();
                }
            } catch (\Exception $local_ex) {
                error_log("Local SMTP Exception: " . $local_ex->getMessage());
            }
        }

        // Fallback: PHPMailer native mail driver or standard mail()
        if (!$success) {
            try {
                $fallback_mail = new PHPMailer(true);
                $fallback_mail->isMail();
                $fallback_mail->setFrom($from_email, $from_name);
                $fallback_mail->addAddress($to);
                $fallback_mail->addReplyTo($from_email, $from_name);
                $fallback_mail->isHTML(true);
                $fallback_mail->Subject = $subject;
                $fallback_mail->Body    = $message;
                $fallback_mail->AltBody = strip_tags($message);
                $fallback_mail->CharSet = 'UTF-8';

                $success = $fallback_mail->send();
            } catch (\Exception $fallback_ex) {
                $headers  = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: ' . $from_name . ' <' . $from_email . '>' . "\r\n";
                $headers .= 'Reply-To: ' . $from_email . "\r\n";
                $headers .= 'X-Mailer: PHP/' . phpversion();

                $success = @mail($to, $subject, $message, $headers);
            }
        }

        // Log to database if connection is provided
        if ($conn) {
            try {
                $log_status = $success ? 'sent' : 'failed';
                $log_sql = "INSERT INTO hs_email_log (recipient_email, subject, message, status) VALUES (?, ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_sql);
                if ($log_stmt) {
                    mysqli_stmt_bind_param($log_stmt, "ssss", $to, $subject, $message, $log_status);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                }
            } catch (\Exception $e) {
                error_log("Database email log failed: " . $e->getMessage());
            }
        }

        return $success;
    }
}
?>
