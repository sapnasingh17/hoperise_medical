<?php
// Prevent direct access to this file
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit("Direct access not permitted.");
}

// Helper function to load .env file securely into environment
if (!function_exists('load_smtp_env')) {
    function load_smtp_env($env_path = null) {
        $env_path = $env_path ?: __DIR__ . '/.env';
        if (!file_exists($env_path)) {
            return false;
        }
        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $val) = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                if (strlen($val) >= 2) {
                    $first = substr($val, 0, 1);
                    $last = substr($val, -1);
                    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                        $val = substr($val, 1, -1);
                    }
                }
                if (getenv($key) === false || getenv($key) === '') {
                    putenv("{$key}={$val}");
                }
                if (!isset($_ENV[$key]) || $_ENV[$key] === '') {
                    $_ENV[$key] = $val;
                }
                if (!isset($_SERVER[$key]) || $_SERVER[$key] === '') {
                    $_SERVER[$key] = $val;
                }
            }
        }
        return true;
    }
}

// Load .env automatically
load_smtp_env();

// SMTP Configuration Constants
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', getenv('SMTP_PORT') ? intval(getenv('SMTP_PORT')) : 587);
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', getenv('SMTP_USER') ?: 'sapnnna988@gmail.com');
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', getenv('SMTP_PASS') !== false ? getenv('SMTP_PASS') : '');
}
if (!defined('SMTP_SECURE')) {
    define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
}
if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'sapnnna988@gmail.com');
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Hope Rise Medical');
}
if (!defined('SMTP_DEBUG_LOG')) {
    define('SMTP_DEBUG_LOG', __DIR__ . '/smtp_errors.log');
}
if (!defined('SMTP_DEBUG_MODE')) {
    define('SMTP_DEBUG_MODE', true); // Enabled for detailed debugging
}
