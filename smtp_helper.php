<?php
require_once __DIR__ . '/smtp_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// PHPMailer Class Autoload / Inclusion Mechanism
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    } elseif (file_exists(__DIR__ . '/plugins/PHPMailer/PHPMailer.php')) {
        require_once __DIR__ . '/plugins/PHPMailer/Exception.php';
        require_once __DIR__ . '/plugins/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/plugins/PHPMailer/SMTP.php';
    } else {
        die("PHPMailer library not found. Please install via Composer or include files in plugins/PHPMailer.");
    }
}

/**
 * Safely log SMTP errors to smtp_errors.log and PHP system error log
 */
if (!function_exists('log_smtp_issue')) {
    function log_smtp_issue($msg) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$msg}\n";
        $log_path = defined('SMTP_DEBUG_LOG') ? SMTP_DEBUG_LOG : __DIR__ . '/smtp_errors.log';
        
        @file_put_contents($log_path, $log_entry, FILE_APPEND);
        error_log("SMTP Error: " . $msg);
    }
}

/**
 * Send email using PHPMailer via SMTP
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject line
 * @param string $message Email body (HTML format supported)
 * @param string|null $from_email Sender or Reply-To email address
 * @param string|null $from_name Sender display name
 * @param string|null &$error_message Output variable for failure details
 * @return bool True on success, false on failure
 */
if (!function_exists('send_smtp_email')) {
    function send_smtp_email($to, $subject, $message, $from_email = null, $from_name = null, &$error_message = null) {
        $host = defined('SMTP_HOST') ? SMTP_HOST : (getenv('SMTP_HOST') ?: 'smtp.gmail.com');
        $port = defined('SMTP_PORT') ? intval(SMTP_PORT) : intval(getenv('SMTP_PORT') ?: 587);
        $user = defined('SMTP_USER') ? SMTP_USER : (getenv('SMTP_USER') ?: '');
        $pass = defined('SMTP_PASS') ? SMTP_PASS : (getenv('SMTP_PASS') ?: '');
        $secure_setting = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : strtolower(getenv('SMTP_SECURE') ?: 'tls');

        $from_email = $from_email ?: (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'sapnnna988@gmail.com');
        $from_name = $from_name ?: (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Hope Rise Medical');

        // Clean subject from line breaks (Header Injection Defense)
        $subject = str_replace(["\r", "\n"], '', $subject);

        // 1. Recipient Validation
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid recipient email address: '$to'";
            log_smtp_issue("Validation Error: {$error_message}");
            return false;
        }

        // 2. Local Mock SMTP exception check
        $is_local_mock = ($host === '127.0.0.1' || $host === 'localhost') && ($port === 1025 || $port === 25);

        // 3. Credential validation for remote SMTP
        if (!$is_local_mock && (empty($pass) || $pass === 'YOUR_GMAIL_APP_PASSWORD_HERE' || $pass === 'your16charapppassword')) {
            $error_message = "SMTP Authentication Password not configured in .env or smtp_config.php.";
            log_smtp_issue("Config Error: Gmail App Password / SMTP_PASS is missing or set to default placeholder.");
            return false;
        }

        // 4. Resolve Encryption Type
        if ($port === 465) {
            $encryption = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($port === 587) {
            $encryption = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($secure_setting === 'ssl') {
            $encryption = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($secure_setting === 'tls') {
            $encryption = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $encryption = '';
        }

        try {
            $mail = new PHPMailer(true);
            
            // Strictly enforce SMTP driver (do NOT use mail())
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->Port       = $port;
            $mail->SMTPAuth   = !empty($user) && !$is_local_mock;
            $mail->Username   = $user;
            $mail->Password   = $pass;

            if (!empty($encryption)) {
                $mail->SMTPSecure = $encryption;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            // SSL stream options for local certificate verification edge cases
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Debug Configuration based on SMTP_DEBUG_MODE
            if (defined('SMTP_DEBUG_MODE') && SMTP_DEBUG_MODE) {
                $mail->SMTPDebug = 2; // SMTP::DEBUG_SERVER
                $mail->Debugoutput = (php_sapi_name() === 'cli') ? 'echo' : 'html';
            } else {
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
            }

            $mail->Timeout   = 15;
            $mail->CharSet   = 'UTF-8';

            // Ensure 'From' matches SMTP authenticated username for Gmail compliance
            $sender_address = (!empty($user) && filter_var($user, FILTER_VALIDATE_EMAIL)) ? $user : $from_email;
            $mail->setFrom($sender_address, $from_name);
            $mail->addAddress($to);

            // Add Reply-To address
            if (!empty($from_email) && filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($from_email, $from_name);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);

            if ($mail->send()) {
                return true;
            } else {
                $error_message = !empty($mail->ErrorInfo) ? $mail->ErrorInfo : "PHPMailer send returned false without exception.";
                log_smtp_issue("Send Failed: Recipient [{$to}] - {$error_message}");
                return false;
            }
        } catch (Exception $e) {
            $error_message = !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
            log_smtp_issue("PHPMailer Exception [{$to}]: " . $error_message);
            return false;
        } catch (\Throwable $e) {
            $error_message = isset($mail) && !empty($mail->ErrorInfo) ? $mail->ErrorInfo : $e->getMessage();
            log_smtp_issue("System Throwable [{$to}]: " . $error_message);
            return false;
        }
    }
}
