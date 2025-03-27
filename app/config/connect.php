<?php
// Chỉ định nghĩa class MyPDO nếu chưa tồn tại
if (!class_exists('MyPDO')) {
    // Extend PDO class to add close method for backward compatibility
    class MyPDO extends PDO {
        public function close() {
            // PDO doesn't have a close method, but we need this for backward compatibility
            // with mysqli code that calls $conn->close()
            // This method does nothing in PDO as connections are closed automatically
        }
    }
}

// Include cấu hình
require_once __DIR__ . '/config.php';

// Thông tin kết nối database
$server   = 'localhost';
$user     = 'root';
$pass     = '';
$database = 'webcourses';

try {
    // Tạo kết nối PDO
    $dsn = "mysql:host=$server;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $conn = new MyPDO($dsn, $user, $pass, $options);
    
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Lỗi kết nối cơ sở dữ liệu. Vui lòng kiểm tra lại thông tin kết nối.");
}
?>