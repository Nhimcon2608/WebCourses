<?php
// Chỉ cấu hình session nếu session chưa active
if (session_status() == PHP_SESSION_NONE) {
    // Cấu hình session trước khi session_start()
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
}

// Cấu hình cơ bản
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 2));
}
if (!defined('APP_DIR')) {
    define('APP_DIR', ROOT_DIR . '/app');
}

// Load routes
$routes = require_once __DIR__ . '/routes.php';

// Xác định môi trường
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // 'development' hoặc 'production'
}

// URL cơ sở - Định nghĩa trực tiếp
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . $host . '/WebCourses/');
}

// Cấu hình thời gian
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình bảo mật
if (!defined('CSRF_TOKEN_NAME')) {
    define('CSRF_TOKEN_NAME', 'csrf_token');
}
if (!defined('CSRF_TOKEN_LENGTH')) {
    define('CSRF_TOKEN_LENGTH', 32);
}

// Cấu hình upload
if (!defined('UPLOAD_MAX_SIZE')) {
    define('UPLOAD_MAX_SIZE', 64 * 1024 * 1024); // 64MB
}
if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_DIR . '/public/uploads/');
}

// Debug mode
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Cấu hình log
ini_set('log_errors', 1);
ini_set('error_log', ROOT_DIR . '/error.log');

// Helper function để lấy URL
function url($path) {
    global $routes;
    $parts = explode('.', $path);
    $current = $routes;
    
    foreach ($parts as $part) {
        if (isset($current[$part])) {
            $current = $current[$part];
        } else {
            return BASE_URL . $path;
        }
    }
    
    return $current;
} 