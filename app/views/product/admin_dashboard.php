<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra xem người dùng đã đăng nhập và có vai trò giảng viên chưa
/*if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: home.php");
    exit();
}*/

// Thêm các truy vấn thống kê
$stats = [
    // Thống kê người dùng
    'new_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch(PDO::FETCH_ASSOC)['count'],
    
    // Doanh thu trong tháng
    'monthly_revenue' => 0,
    
    // Tỷ lệ hoàn thành khóa học
    'completion_rate' => $conn->query("
        SELECT ROUND(
            (COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0) / COUNT(*), 
            1
        ) as rate 
        FROM enrollments")->fetch(PDO::FETCH_ASSOC)['rate'],
        
];

// Truy vấn số lượng người dùng
$result_users = $conn->query("SELECT COUNT(*) AS total FROM users");
$user_count = $result_users->fetch(PDO::FETCH_ASSOC)['total'];

// Truy vấn số lượng khóa học
$result_courses = $conn->query("SELECT COUNT(*) AS total FROM courses");
$course_count = $result_courses->fetch(PDO::FETCH_ASSOC)['total'];

// Truy vấn số lượng đơn đặt hàng
$result_orders = $conn->query("SELECT COUNT(*) AS total FROM enrollments");
$order_count = $result_orders->fetch(PDO::FETCH_ASSOC)['total'];

// Thêm vào phần PHP ở đầu file
try {
    $notifications = $conn->query("
        SELECT n.*, u.username 
        FROM notifications n 
        LEFT JOIN users u ON n.user_id = u.user_id 
        WHERE n.is_read = 0 
        ORDER BY n.created_at DESC 
        LIMIT 5
    ");
} catch (PDOException $e) {
    // If table doesn't exist, create it
    $conn->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )
    ");
    $notifications = $conn->query("
        SELECT n.*, u.username 
        FROM notifications n 
        LEFT JOIN users u ON n.user_id = u.user_id 
        WHERE n.is_read = 0 
        ORDER BY n.created_at DESC 
        LIMIT 5
    ");
}

// Bỏ qua truy vấn doanh thu nếu bảng không tồn tại
$monthly_revenue = 0; // Giá trị mặc định nếu không có bảng
try {
    $result_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())");
    $monthly_revenue = $result_revenue->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    // Xử lý lỗi nếu bảng không tồn tại
    error_log("Lỗi truy vấn doanh thu: " . $e->getMessage());
}

// Cập nhật mảng stats
$stats['monthly_revenue'] = $monthly_revenue;

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Quản Trị - Học Tập Trực Tuyến</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Open+Sans:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <h2>Quản Lý Hệ Thống</h2>
            <p>Chào mừng Quản trị viên! Quản lý người dùng, khóa học và các chức năng khác ở đây.</p>

            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Danh mục</th>
                        <th>Số lượng</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Người dùng</td>
                        <td><?php echo $user_count; ?></td>
                    </tr>
                    <tr>
                        <td>Khóa học</td>
                        <td><?php echo $course_count; ?></td>
                    </tr>
                    <tr>
                        <td>Học Sinh</td>
                        <td><?php echo $order_count; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="admin-dashboard-grid">
    <!-- Thống kê tổng quan -->
    <div class="stats-container">
        <div class="stat-card">
            <i class="fas fa-user-plus"></i>
            <h3>Người dùng mới</h3>
            <p><?php echo $stats['new_users']; ?></p>
        </div>
        <div class="stat-card">
            <i class="fas fa-money-bill-wave"></i>
            <h3>Doanh thu tháng này</h3>
            <p><?php echo number_format($stats['monthly_revenue'], 0, ',', '.'); ?>đ</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-graduation-cap"></i>
            <h3>Tỷ lệ hoàn thành</h3>
            <p><?php echo $stats['completion_rate']; ?>%</p>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="quick-actions">
        <h3>Thao Tác Nhanh</h3>
        <div class="action-buttons">
            <a href="create_course.php" class="action-btn">
                <i class="fas fa-plus"></i>
                <span>Thêm Khóa Học Mới</span>
            </a>
            <a href="manage_users.php" class="action-btn">
                <i class="fas fa-users"></i>
                <span>Quản Lý Người Dùng</span>
            </a>
            <a href="<?php echo BASE_URL; ?>app/views/product/home.php#reviews" class="action-btn">
                <i class="fas fa-chart-bar"></i>
                <span>Xem Bình Luận</span>
            </a>
            <a href="system_settings.php" class="action-btn">
                <i class="fas fa-cog"></i>
                <span>Cài Đặt Hệ Thống</span>
            </a>
        </div>
    </div>

    </section>

    <div class="notifications-panel">
        <h3>Thông Báo Mới</h3>
        <div class="notifications-list">
            <?php if ($notifications->rowCount() > 0): ?>
                <?php while ($notification = $notifications->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="notification-content">
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></small>
                        </div>
                        <button class="mark-read" data-id="<?php echo $notification['id']; ?>">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-notifications">Không có thông báo mới</p>
            <?php endif; ?>
        </div>
    </div>
    
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