<?php

if (!defined('ROOT_DIR')) {
  define('ROOT_DIR', dirname(__DIR__, 2)); 
}
include ROOT_DIR . '/app/config/connect.php';

$registerError = '';
$registerSuccess = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $role = $_POST['role'] ?? 'student';

  if (empty($username) || empty($email) || empty($password)) {
    $registerError = "Vui lòng điền đầy đủ thông tin.";
  } else {
    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $registerError = "Tên đăng nhập hoặc email đã được sử dụng.";
    } else {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO Users (username, email, password, role) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
      if ($stmt->execute()) {
        $registerSuccess = "Đăng ký thành công! Vui lòng đăng nhập.";
        $_SESSION['registerSuccess'] = $registerSuccess;
        header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php');
        exit();
      } else {
        $registerError = "Đăng ký thất bại. Vui lòng thử lại.";
      }
    }
    $stmt->close();
  }
}

if (!empty($registerError)) {
  $_SESSION['registerError'] = $registerError;
  header("Location: " . 'http://localhost:8080/WebCourses/app/views/product/home.php');
  exit();
}