<?php

if (!defined('ROOT_DIR')) {
  define('ROOT_DIR', dirname(__DIR__, 2)); 
}
include ROOT_DIR . '/app/config/connect.php';

require_once '../../src/PHPMailer.php';
require_once '../../src/Exception.php';
require_once '../../src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $_POST['name'] ?? '';
    $email   = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    $mail = new PHPMailer(true);

    try {
        // Cấu hình SMTP (ví dụ dùng Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hoctap435@gmail.com';
        $mail->Password   = 'vznk pkkp iety fzkm';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;
        
        // Thiết lập charset cho email để hỗ trợ tiếng Việt
        $mail->CharSet = 'UTF-8';

        // Người gửi - người nhận
        // Nếu bạn muốn sử dụng email người dùng làm từ, Gmail có thể chặn, do đó bạn nên đặt từ là tài khoản của bạn và addReplyTo để trả lời email của người dùng.
        $mail->setFrom('hoctap435@gmail.com', 'Học Tập Trực Tuyến');
        $mail->addReplyTo($email, $name);
        $mail->addAddress('hoctap435@gmail.com'); // Email nhận chính

        // Nội dung mail
        $mail->Subject = 'Liên hệ từ khách hàng: ' . $name;
        $mail->Body    = "Họ và tên: $name\nEmail: $email\nNội dung:\n$message";

        
        
    // Gửi email và chuyển hướng về trang home.php sau khi gửi thành công
    $mail->send();
    echo "<script>
            alert('Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm.');
            window.location.href = 'home.php';
          </script>";
} catch (Exception $e) {
    echo "<script>
            alert('Không thể gửi mail. Lỗi: " . $mail->ErrorInfo . "');
            window.location.href = 'home.php';
          </script>";

}
}
?>
