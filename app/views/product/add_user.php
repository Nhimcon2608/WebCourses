<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra phân quyền admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//     header("Location: home.php");
//     exit();
// }

// Xử lý form thêm người dùng
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Kiểm tra username đã tồn tại chưa
    $check_username = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $username_result = $check_username->get_result();
    $check_username->close();
    
    // Kiểm tra email đã tồn tại chưa
    $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $email_result = $check_email->get_result();
    $check_email->close();
    
    if($username_result->num_rows > 0) {
        $error = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
    } elseif($email_result->num_rows > 0) {
        $error = "Email này đã được sử dụng. Vui lòng sử dụng email khác.";
    } else {
        // Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Thêm người dùng mới
        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if($insert_stmt->execute()) {
            header("Location: manage_users.php?success=Thêm người dùng thành công");
            exit();
        } else {
            $error = "Lỗi khi thêm người dùng: " . $conn->error;
        }
        
        $insert_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Người Dùng Mới - Học Tập Trực Tuyến</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .add-form {
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
            box-shadow: 0  0 5px rgba(74, 161, 255, 0.3);
        }
        
        .btn-submit {
            background: #4CAF50;
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
            background: #388E3C;
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
            border-left: 4px solid #f44336;
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
            <h2>Thêm Người Dùng Mới</h2>
            <p>Tạo tài khoản mới cho người dùng trong hệ thống</p>
            
            <div class="add-form">
                <?php if(isset($error)): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập:</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mật khẩu:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Vai trò:</label>
                        <select id="role" name="role" required>
                            <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Học viên</option>
                            <option value="instructor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'instructor') ? 'selected' : ''; ?>>Giảng viên</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Quản trị viên</option>
                        </select>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn-submit"><i class="fas fa-user-plus"></i> Thêm Người Dùng</button>
                        <a href="manage_users.php" class="btn-cancel"><i class="fas fa-times"></i> Hủy</a>
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
