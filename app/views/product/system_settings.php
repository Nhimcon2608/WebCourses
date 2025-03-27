<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra phân quyền admin (có thể bỏ comment nếu cần)
/*if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: home.php");
    exit();
}*/

// Tạo mảng cấu hình mặc định
$settings = [
    'site_name' => 'EduHub - Học Tập Trực Tuyến',
    'site_description' => 'Nền tảng học tập trực tuyến hàng đầu Việt Nam',
    'admin_email' => 'admin@eduhub.com',
    'items_per_page' => 10,
    'maintenance_mode' => 'off',
    'allow_registrations' => 'on',
    'theme_color' => '#1e3c72',
    'footer_text' => '© 2025 Học Tập Trực Tuyến. All Rights Reserved.'
];

// Kiểm tra xem bảng settings có tồn tại không
$table_exists = $conn->query("SHOW TABLES LIKE 'settings'")->num_rows > 0;

// Nếu bảng tồn tại, lấy các cài đặt từ cơ sở dữ liệu
if ($table_exists) {
    $result = $conn->query("SELECT * FROM settings");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy các giá trị từ form
    $site_name = $_POST['site_name'];
    $site_description = $_POST['site_description'];
    $admin_email = $_POST['admin_email'];
    $items_per_page = intval($_POST['items_per_page']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 'on' : 'off';
    $allow_registrations = isset($_POST['allow_registrations']) ? 'on' : 'off';
    $theme_color = $_POST['theme_color'];
    $footer_text = $_POST['footer_text'];
    
    // Cập nhật mảng settings
    $settings['site_name'] = $site_name;
    $settings['site_description'] = $site_description;
    $settings['admin_email'] = $admin_email;
    $settings['items_per_page'] = $items_per_page;
    $settings['maintenance_mode'] = $maintenance_mode;
    $settings['allow_registrations'] = $allow_registrations;
    $settings['theme_color'] = $theme_color;
    $settings['footer_text'] = $footer_text;
    
    // Nếu bảng settings tồn tại, cập nhật vào cơ sở dữ liệu
    if ($table_exists) {
        // Sử dụng REPLACE INTO thay vì UPDATE và INSERT riêng biệt
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $key, $value);
                $stmt->execute();
                $stmt->close();
            } else {
                $error_message = "Lỗi khi chuẩn bị câu lệnh SQL: " . $conn->error;
            }
        }
        
        if (!isset($error_message)) {
            $success_message = "Cài đặt đã được lưu thành công!";
        }
    } else {
        // Nếu bảng không tồn tại, tạo bảng và thêm dữ liệu
        $create_table_result = $conn->query("
            CREATE TABLE settings (
                setting_id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(255) NOT NULL UNIQUE,
                setting_value TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        if ($create_table_result) {
            $all_success = true;
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ss", $key, $value);
                    if (!$stmt->execute()) {
                        $all_success = false;
                        $error_message = "Lỗi khi thêm cài đặt: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $all_success = false;
                    $error_message = "Lỗi khi chuẩn bị câu lệnh SQL: " . $conn->error;
                }
                
                if (!$all_success) break;
            }
            
            if ($all_success) {
                $success_message = "Bảng settings đã được tạo và cài đặt đã được lưu thành công!";
            }
        } else {
            $error_message = "Lỗi khi tạo bảng settings: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài Đặt Hệ Thống - Học Tập Trực Tuyến</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .settings-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .settings-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="color"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: #1e3c72;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: #0c2461;
            transform: translateY(-2px);
        }
        
        .submit-button {
            grid-column: 1 / -1;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-button:hover {
            background: #388E3C;
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .settings-info {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #1e3c72;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
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
            <h2>Cài Đặt Hệ Thống</h2>
            <p>Quản lý và cấu hình hệ thống website</p>
            
            <a href="admin_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Quay lại Dashboard
            </a>
            
            <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="settings-container">
                <form action="" method="POST" class="settings-form">
                    <div class="form-group">
                        <label for="site_name">Tên Website:</label>
                        <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Email Quản Trị:</label>
                        <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="items_per_page">Số mục trên mỗi trang:</label>
                        <input type="number" id="items_per_page" name="items_per_page" value="<?php echo intval($settings['items_per_page']); ?>" min="5" max="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="theme_color">Màu chủ đạo:</label>
                        <input type="color" id="theme_color" name="theme_color" value="<?php echo htmlspecialchars($settings['theme_color']); ?>">
                    </div>
                    
                    <div class="form-group checkbox-wrapper">
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $settings['maintenance_mode'] === 'on' ? 'checked' : ''; ?> onchange="checkboxToggle(this, 'allow_registrations')">
                        <label for="maintenance_mode">Chế độ bảo trì</label>
                    </div>
                    
                    <div class="form-group checkbox-wrapper">
                        <input type="checkbox" id="allow_registrations" name="allow_registrations" <?php echo $settings['allow_registrations'] === 'on' ? 'checked' : ''; ?> onchange="checkboxToggle(this, 'maintenance_mode')">
                        <label for="allow_registrations">Cho phép đăng ký</label>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="site_description">Mô tả Website:</label>
                        <textarea id="site_description" name="site_description" required><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="footer_text">Nội dung Footer:</label>
                        <textarea id="footer_text" name="footer_text"><?php echo htmlspecialchars($settings['footer_text']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-button">
                        <i class="fas fa-save"></i> Lưu Cài Đặt
                    </button>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p><?php echo htmlspecialchars($settings['footer_text']); ?></p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/ScrollTrigger.min.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/script.js"></script>
    <script>
        // Hàm để đảm bảo chỉ có một checkbox được chọn tại một thời điểm
        function checkboxToggle(checkbox, otherCheckboxId) {
            if (checkbox.checked) {
                document.getElementById(otherCheckboxId).checked = false;
            }
        }
        
        // Kiểm tra khi trang tải để đảm bảo không có xung đột
        document.addEventListener('DOMContentLoaded', function() {
            const maintenanceMode = document.getElementById('maintenance_mode');
            const allowRegistrations = document.getElementById('allow_registrations');
            
            if (maintenanceMode.checked && allowRegistrations.checked) {
                // Nếu cả hai đều được chọn khi tải trang, ưu tiên chế độ bảo trì
                allowRegistrations.checked = false;
            }
        });
    </script>
</body>
</html>
