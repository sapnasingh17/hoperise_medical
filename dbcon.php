<?php
// Centralized Security Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Session Inactivity Timeout (30 minutes)
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'admin');
define('DB_PASS', 'Inextrix@1234');
define('DB_NAME', 'hoperise');

// Gemini API Configuration
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
}

// SMTP Mail Configuration
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', getenv('SMTP_USER') ?: 'sapnnna988@gmail.com');
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', getenv('SMTP_PASS') ?: 'YOUR_GMAIL_APP_PASSWORD_HERE');
}
if (!defined('SMTP_SECURE')) {
    define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
}
if (!defined('SMTP_DEBUG_MODE')) {
    define('SMTP_DEBUG_MODE', false);
}
if (!defined('SMTP_DEBUG_DISPLAY')) {
    define('SMTP_DEBUG_DISPLAY', false);
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection Error !! " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Helper function for prepared statements
if (!function_exists('db_query_prepared')) {
    function db_query_prepared($conn, $sql, $params = [], $types = '') {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            die("Statement preparation failed: " . mysqli_error($conn));
        }
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return [$stmt, $result];
    }
}

// CSRF Protection Helpers
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('get_csrf_input_html')) {
    function get_csrf_input_html() {
        return '<input type="hidden" name="csrf_token" value="' . esc(generate_csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Output Escaping Helper for XSS Protection
if (!function_exists('esc')) {
    function esc($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Strict File Upload Validation Helper
if (!function_exists('validate_file_upload')) {
    function validate_file_upload($file_array, $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']) {
        if (!isset($file_array) || $file_array['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error or no file selected.'];
        }
        
        $filename = $file_array['name'];
        $tmp_name = $file_array['tmp_name'];
        $file_size = $file_array['size'];
        
        // Check file extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            return ['success' => false, 'message' => 'Invalid file extension. Only ' . implode(', ', $allowed_extensions) . ' are allowed.'];
        }
        
        // Check file size (limit to 5MB)
        if ($file_size > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size exceeds maximum limit of 5MB.'];
        }
        
        // Verify MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/pjpeg', 'image/x-png'];
        if (!in_array($mime, $allowed_mimes)) {
            return ['success' => false, 'message' => 'Invalid file MIME type.'];
        }
        
        // Generate a safe unique name
        $new_name = md5(uniqid(rand(), true)) . '.' . $ext;
        return ['success' => true, 'new_name' => $new_name];
    }
}

// Exception and Error Logging Config
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (!function_exists('secure_error_handler')) {
    function secure_error_handler($errno, $errstr, $errfile, $errline) {
        $log_msg = "[" . date('Y-m-d H:i:s') . "] Error [$errno]: $errstr in $errfile on line $errline\n";
        error_log($log_msg, 3, __DIR__ . '/error.log');
        if ($errno === E_USER_ERROR || $errno === E_COMPILE_ERROR || $errno === E_CORE_ERROR) {
            die("A system error occurred. Please contact administrator.");
        }
        return true;
    }
}
set_error_handler('secure_error_handler');

if (!function_exists('secure_exception_handler')) {
    function secure_exception_handler($exception) {
        $log_msg = "[" . date('Y-m-d H:i:s') . "] Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\nStack trace:\n" . $exception->getTraceAsString() . "\n";
        error_log($log_msg, 3, __DIR__ . '/error.log');
        die("A system exception occurred. Please contact administrator.");
    }
}
set_exception_handler('secure_exception_handler');
?>