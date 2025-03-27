<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

define('ROOT_DIR', dirname(__DIR__, 2)); 

include ROOT_DIR . '/app/config/connect.php';

require_once ROOT_DIR . '/src/PHPMailer.php';
require_once ROOT_DIR . '/src/Exception.php';
require_once ROOT_DIR . '/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Initialize message variables
$loginError = isset($_SESSION['loginError']) ? $_SESSION['loginError'] : null;
$loginSuccess = isset($_SESSION['loginSuccess']) ? $_SESSION['loginSuccess'] : null;
$registerError = isset($_SESSION['registerError']) ? $_SESSION['registerError'] : null;
$registerSuccess = isset($_SESSION['registerSuccess']) ? $_SESSION['registerSuccess'] : null;
$forgotError = isset($_SESSION['forgotError']) ? $_SESSION['forgotError'] : null;
$forgotSuccess = isset($_SESSION['forgotSuccess']) ? $_SESSION['forgotSuccess'] : null;
$resetError = isset($_SESSION['resetError']) ? $_SESSION['resetError'] : null;
$resetSuccess = isset($_SESSION['resetSuccess']) ? $_SESSION['resetSuccess'] : null;

// Clear session messages once retrieved
unset($_SESSION['loginError'], $_SESSION['loginSuccess'], $_SESSION['registerError'], $_SESSION['registerSuccess'],
    $_SESSION['forgotError'], $_SESSION['forgotSuccess'], $_SESSION['resetError'], $_SESSION['resetSuccess']);

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  $stmt = $conn->prepare("SELECT user_id, username, password, role FROM Users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['loginSuccess'] = "Đăng nhập thành công!";
      
      // Chuyển hướng theo vai trò với tham số thời gian để tránh form resubmission
      $timestamp = time();
      switch ($user['role']) {
        case 'student':
          header("Location: student_dashboard.php?t=$timestamp");
          exit();
        case 'instructor':
          header("Location: instructor_dashboard.php?t=$timestamp");
          exit();
        case 'admin':
          header("Location: admin_dashboard.php?t=$timestamp");
          exit();
      }
    } else {
      $loginError = "Mật khẩu không đúng.";
    }
  } else {
    $loginError = "Tên đăng nhập không tồn tại.";
  }
  $stmt->close();
  
  // Chuyển hướng về trang đăng nhập với tham số thời gian
  if ($loginError) {
    $_SESSION['loginError'] = $loginError;
    header("Location: login.php?error=1&t=" . time());
    exit();
  }
}

// Xử lý quên mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['forgot_email']);
    $stmt = $conn->prepare("SELECT user_id, username FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows == 1) {
      $user = $result->fetch_assoc();
      $verification_code = sprintf("%05d", rand(0, 99999));
  
      $_SESSION['forgot_user_id'] = $user['user_id'];
      $_SESSION['verification_code'] = $verification_code;
      $_SESSION['reset_email'] = $email;
      $_SESSION['code_expiry'] = time() + 300; // 5 phút 
  
      $mail = new PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hoctap435@gmail.com';
        $mail->Password = 'vznk pkkp iety fzkm';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
  
        $mail->setFrom('hoctap435@gmail.com', 'Học Tập Trực Tuyến');
        $mail->addAddress($email);
  
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Mã xác nhận khôi phục mật khẩu - Học Tập Trực Tuyến";
        $mail->Body = "<h2>Mã xác nhận khôi phục mật khẩu</h2><p>Mã xác nhận của bạn là: <strong>{$verification_code}</strong></p><p>Mã này có hiệu lực trong 5 phút.</p><p>Nếu bạn không yêu cầu khôi phục mật khẩu, vui lòng bỏ qua email này.</p>";
        $mail->AltBody = "Mã xác nhận khôi phục mật khẩu\n\nMã xác nhận của bạn là: {$verification_code}\nMã này có hiệu lực trong 5 phút.\nNếu bạn không yêu cầu khôi phục mật khẩu, vui lòng bỏ qua email này.";
  
        $mail->send();
        $_SESSION['forgotSuccess'] = "Mã xác nhận đã được gửi đến email của bạn.";
        header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php?action=forgot&t=' . time());
        exit();
      } catch (Exception $e) {
        $forgotError = "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
        $_SESSION['forgotError'] = $forgotError;
        header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php?error=mail&t=' . time());
        exit();
      }
    } else {
      $forgotError = "Email không tồn tại trong hệ thống.";
      $_SESSION['forgotError'] = $forgotError;
      header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php?error=email&t=' . time());
      exit();
    }
}
  
// Xử lý đặt lại mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset-password'])) {
  $code = trim($_POST['verification_code']);
  $new_password = trim($_POST['new_password']);

  if (isset($_SESSION['code_expiry']) && time() > $_SESSION['code_expiry']) {
    $resetError = "Mã xác nhận đã hết hạn. Vui lòng yêu cầu mã mới.";
    $_SESSION['resetError'] = $resetError;
    unset($_SESSION['forgot_user_id'], $_SESSION['verification_code'], $_SESSION['reset_email'], $_SESSION['code_expiry']);
    header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php?error=expired&t=' . time());
    exit();
  } elseif ($code === $_SESSION['verification_code']) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE Users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashed_password, $_SESSION['forgot_user_id']);

    if ($stmt->execute()) {
      $_SESSION['resetSuccess'] = "Đặt lại mật khẩu thành công. Vui lòng đăng nhập.";
      unset($_SESSION['forgot_user_id'], $_SESSION['verification_code'], $_SESSION['reset_email'], $_SESSION['code_expiry']);
      header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php?action=reset_success&t=' . time());
      exit();
    } else {
      $resetError = "Đặt lại mật khẩu thất bại.";
      $_SESSION['resetError'] = $resetError;
      header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php?error=reset_failed&t=' . time());
      exit();
    }
    
  } else {
    $resetError = "Mã xác nhận không đúng.";
    $_SESSION['resetError'] = $resetError;
    header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php?error=wrong_code&t=' . time());
    exit();
  }
}