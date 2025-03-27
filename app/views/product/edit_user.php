<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra phân quyền admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//     header("Location: home.php");
//     exit();
// }

// Lấy thông tin người dùng cần sửa
if(isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    
    $stmt = $conn->prepare("SELECT user_id, username, email, password, role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        header("Location: manage_users.php?error=Không tìm thấy người dùng");
        exit();
    }
    
    $stmt->close();
} else {
    header("Location: manage_users.php?error=Thiếu ID người dùng");
    exit();
}

// Xử lý form cập nhật
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Kiểm tra nếu mật khẩu được cập nhật
    if(!empty($password)) {
        // Mã hóa mật khẩu mới
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE user_id = ?");
        $update_stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $user_id);
    } else {
        // Giữ nguyên mật khẩu cũ
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE user_id = ?");
        $update_stmt->bind_param("sssi", $username, $email, $role, $user_id);
    }
    
    if($update_stmt->execute()) {
        header("Location: manage_users.php?success=Cập nhật thông tin người dùng thành công");
        exit();
    } else {
        $error = "Lỗi khi cập nhật thông tin: " . $conn->error;
    }
    
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Thông Tin Người Dùng - Học Tập Trực Tuyến</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: #4aa1ff;
            outline: none;
            box-shadow: 0 0 5px rgba(74, 161, 255, 0.3);
        }
        
        .btn-submit {
            background: #4aa1ff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #1e88e5;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background: #f44336;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-cancel:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        
        .error-message {
            color: #f44336;
            margin-top: 20px;
            padding: 10px;
            background: #ffebee;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">EduHub</div>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Trang Chủ</a></li>
                    <li><a href="manage_users.php" class="btn">Quản lý Người Dùng</a></li>
                    <li><a href="manage_courses.php" class="btn">Quản lý Khóa Học</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/controllers/logout.php" class="btn">Đăng xuất</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="dashboard">
        <div class="container">
            <h2>Sửa Thông Tin Người Dùng</h2>
            <p>Cập nhật thông tin cho người dùng ID: <?php echo $user_id; ?></p>
            
            <div class="edit-form">
                <?php if(isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu mới (để trống nếu không thay đổi):</label>
                        <input type="password" id="password" name="password">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Vai trò:</label>
                        <select id="role" name="role" required>
                            <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Học viên</option>
                            <option value="teacher" <?php echo $user['role'] == 'teacher' ? 'selected' : ''; ?>>Giảng viên</option>
                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Mật khẩu hiện tại (đã mã hóa):</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['password']); ?>" readonly>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn-submit">Lưu Thay Đổi</button>
                        <a href="manage_users.php" class="btn-cancel">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/ScrollTrigger.min.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/script.js"></script>
</body>
</html>
