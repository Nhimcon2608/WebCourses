<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 2)); 
}
include ROOT_DIR . '/app/config/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $message = trim($_POST['message']);
    
    // Kiểm tra dữ liệu rỗng
    if (empty($name) || empty($email) || empty($message)) {
        $contactError = "Vui lòng điền đầy đủ thông tin.";
    } else {
        // Chuẩn bị câu lệnh INSERT để lưu thông tin liên hệ vào bảng contacts
        $stmt = $conn->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $name, $email, $message);
            if ($stmt->execute()) {
                $contactSuccess = "Tin nhắn của bạn đã được gửi thành công.";
            } else {
                $contactError = "Gửi tin nhắn thất bại. Vui lòng thử lại.";
            }
            $stmt->close();
        } else {
            $contactError = "Có lỗi trong quá trình xử lý.";
        }
    }
}
?>
