<?php
// Định nghĩa ROOT_DIR và BASE_URL
define('ROOT_DIR', __DIR__);
define('BASE_URL', '/WebCourses/');

// Load cấu hình và khởi tạo session
require_once ROOT_DIR . '/app/config/config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối database và load controllers
require_once ROOT_DIR . '/app/config/connect.php';
require_once ROOT_DIR . '/app/controllers/AuthController.php';

// Khởi tạo controller
$authController = new AuthController($conn);

// Xử lý request
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = parse_url(BASE_URL, PHP_URL_PATH);
$path = substr($request_uri, strlen($base_path));
$path = trim($path, '/');

// Xử lý các route auth
if (strpos($path, 'auth/') === 0) {
    $action = substr($path, 5);
    switch ($action) {
        case 'logout':
            $authController->logout();
            exit;
        case 'login':
            $authController->login();
            exit;
        case 'register':
            $authController->register();
            exit;
    }
}

// Mặc định chuyển hướng đến home.php
if (empty($path)) {
    require_once ROOT_DIR . '/app/views/product/home.php';
    exit;
}

// Nếu path là URL trực tiếp đến file trong thư mục product
if (strpos($path, 'app/views/product/') === 0) {
    $file = ROOT_DIR . '/' . $path;
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// Nếu không tìm thấy file, chuyển hướng đến trang lỗi
header("Location: " . BASE_URL . "app/views/product/error.php?message=Page not found");
exit;
