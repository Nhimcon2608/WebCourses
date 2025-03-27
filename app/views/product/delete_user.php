<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra phân quyền admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//     header("Location: home.php");
//     exit();
// }

// Kiểm tra có user_id không
if (!isset($_GET['user_id'])) {
    header("Location: manage_users.php?error=Không tìm thấy ID người dùng");
    exit();
}

$user_id = intval($_GET['user_id']);

// Xác minh người dùng tồn tại
$check_user = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$check_user->bind_param("i", $user_id);
$check_user->execute();
$result = $check_user->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_users.php?error=Người dùng không tồn tại");
    exit();
}

// Kiểm tra không xóa chính mình
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
    header("Location: manage_users.php?error=Bạn không thể xóa tài khoản của chính mình");
    exit();
}

// Thực hiện xóa người dùng
$delete_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$delete_user->bind_param("i", $user_id);

if ($delete_user->execute()) {
    // Xóa thành công
    header("Location: manage_users.php?success=Xóa người dùng thành công");
    exit();
} else {
    // Xóa thất bại
    header("Location: manage_users.php?error=Lỗi khi xóa người dùng: " . $conn->error);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xóa Người Dùng</title>
    <link rel="stylesheet" href="path/to/your/css/style.css">
</head>
<body>
    <h2>Xóa Người Dùng</h2>
    <form action="process_delete_user.php" method="POST">
        <label for="user_id">ID Người Dùng:</label>
        <input type="text" id="user_id" name="user_id" required>
        
        <button type="submit">Xóa Người Dùng</button>
    </form>
</body>
</html>
