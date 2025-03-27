<?php
// Don't redefine constants if already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WebCourses/');
}
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 3));
}

// Include configuration properly - load config first
require_once ROOT_DIR . '/app/config/config.php';
require_once ROOT_DIR . '/app/config/connect.php';

// Check if session is already started before starting it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Reset redirect count to prevent redirect loops
$_SESSION['redirect_count'] = 0;

// Add connection status indicator for PDO
$db_status = [
    'status' => isset($conn) ? 'connected' : 'disconnected',
    'host' => isset($conn) ? 'localhost' : 'Unknown',
    'version' => isset($conn) ? $conn->getAttribute(PDO::ATTR_SERVER_VERSION) : 'Unknown'
];

// Kiểm tra xem người dùng đã đăng nhập và có vai trò giảng viên chưa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    // Set error message in session
    $_SESSION['error'] = "Bạn không có quyền truy cập trang này. Vui lòng đăng nhập với tài khoản giảng viên.";
    // Redirect to home page
    header("Location: " . BASE_URL . "app/views/product/home.php");
    exit();
}

// Lấy thông tin user_id và username từ session
$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];

// Lấy danh sách khóa học do giảng viên này tạo - using PDO
$coursesQuery = "
    SELECT course_id, title, description, image, price, level, created_at 
    FROM Courses 
    WHERE instructor_id = ?
";
$coursesStmt = $conn->prepare($coursesQuery);
$coursesStmt->execute([$user_id]);
$courses = $coursesStmt->fetchAll();
$totalCourses = count($courses);

// Lấy tổng số học viên đăng ký các khóa học của giảng viên - using PDO
$studentQuery = "
    SELECT COUNT(DISTINCT e.user_id) as total_students
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE c.instructor_id = ?
";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->execute([$user_id]);
$studentData = $studentStmt->fetch();
$totalStudents = $studentData['total_students'] ?? 0;

// Lấy đánh giá trung bình - using PDO
$ratingQuery = "
    SELECT AVG(r.rating) as avg_rating
    FROM reviews r
    JOIN courses c ON r.course_id = c.course_id
    WHERE c.instructor_id = ?
";
$ratingStmt = $conn->prepare($ratingQuery);
$ratingStmt->execute([$user_id]);
$ratingData = $ratingStmt->fetch();
$avgRating = number_format($ratingData['avg_rating'] ?? 0, 1);

// Lấy số lượng thông báo chưa đọc - using PDO
// Table 'notifications' doesn't exist, so we'll set a default value
$unreadNotifs = 0;
/*
$notifQuery = "
    SELECT COUNT(*) as unread_count 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
";
$notifStmt = $conn->prepare($notifQuery);
$notifStmt->execute([$user_id]);
$notifData = $notifStmt->fetch();
$unreadNotifs = $notifData['unread_count'] ?? 0;
*/

// Lấy thống kê thu nhập - using PDO
$incomeQuery = "
    SELECT COALESCE(SUM(c.price), 0) as total_income 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE c.instructor_id = ?
";
$incomeStmt = $conn->prepare($incomeQuery);
$incomeStmt->execute([$user_id]);
$incomeData = $incomeStmt->fetch();
$totalIncome = number_format($incomeData['total_income'] ?? 0, 0, ',', '.');

// Dữ liệu cho biểu đồ (mẫu - trong thực tế sẽ lấy từ database)
$monthlyData = array(
    'enrollments' => [4, 7, 12, 15, 10, 16, 19, 25, 22, 20, 28, 35],
    'income' => [400000, 700000, 1200000, 1500000, 1000000, 1600000, 1900000, 2500000, 2200000, 2000000, 2800000, 3500000]
);
$chartData = json_encode($monthlyData);

// Set page title and specific variables for header
$page_title = 'Trang Quản Lý Giảng Viên';
$include_chart_js = true;
$page_specific_script = "
    // Logout function
    function handleLogout() {
        console.log('Handling instructor logout');
        // Fix the URL construction to avoid duplicate hostname
        const protocol = window.location.protocol;
        const host = window.location.host;
        // Remove leading slash from BASE_URL when combining with host
        const baseUrl = '" . BASE_URL . "'.replace(/^\\//, '');
        const logoutUrl = protocol + '//' + host + '/' + baseUrl + 'app/controllers/logout.php';
        console.log('Redirecting to: ' + logoutUrl);
        window.location.href = logoutUrl;
    }
";

// Include header
$header_path = ROOT_DIR . '/app/includes/header.php';
if (file_exists($header_path)) {
    include_once $header_path;
} else {
    // Try alternate path format
    $alt_header_path = dirname(__DIR__, 2) . '/includes/header.php';
    if (file_exists($alt_header_path)) {
        include_once $alt_header_path;
    } else {
        // Output a complete HTML header if the header file is not found
        echo '<!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $page_title . '</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <link rel="stylesheet" href="' . BASE_URL . 'public/css/instructor_dashboard.css">
            ';
        if ($include_chart_js) {
            echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        }
        if (!empty($page_specific_script)) {
            echo '<script>' . $page_specific_script . '</script>';
        }
        echo '</head>
        <body>
            <!-- Header -->
            <header class="header">
                <div class="mobile-menu-toggle" id="mobile-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Học Tập</span>
                </div>
                <div class="user-actions">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>';
                        if ($unreadNotifs > 0) {
                            echo '<span class="badge">' . $unreadNotifs . '</span>';
                        }
                    echo '</div>
                    <button class="mode-toggle" id="mode-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="teacher-name">Xin chào, <strong>' . htmlspecialchars($username) . '</strong></div>
                    <form method="post" action="' . BASE_URL . 'app/controllers/logout.php" style="display:inline;">
                        <button type="submit" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                        </button>
                    </form>
                </div>
            </header>

            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/instructor_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tổng Quan</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/create_course.php"><i class="fas fa-plus-circle"></i> Thêm Khoá Học</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/manage_lessons.php"><i class="fas fa-book"></i> Quản Lý Bài Giảng</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/student_list.php"><i class="fas fa-users"></i> Danh Sách Học Viên</a></li>
                    <li><a href="#"><i class="fas fa-tasks"></i> Bài Tập & Đánh Giá</a></li>
                    <li><a href="#"><i class="fas fa-comments"></i> Thảo Luận</a></li>
                    <li><a href="#"><i class="fas fa-certificate"></i> Chứng Chỉ</a></li>
                    <li><a href="#"><i class="fas fa-bell"></i> Thông Báo</a></li>
                    <li><a href="#"><i class="fas fa-chart-line"></i> Phân Tích Dữ Liệu</a></li>
                    <li><a href="#"><i class="fas fa-money-bill-wave"></i> Thu Nhập</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Cài Đặt</a></li>
                    <li><a href="#"><i class="fas fa-question-circle"></i> Hỗ Trợ</a></li>
                </ul>
                <div class="sidebar-footer">
                    © 2025 Học Tập Trực Tuyến
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">';
    }
}
?>

<!-- Custom CSS for Modern Dashboard -->
<style>
  :root {
    --primary-color: #6366f1;
    --primary-gradient: linear-gradient(135deg, #6366f1, #4f46e5);
    --text-light: #f8fafc;
    --text-dark: #1e293b;
    --bg-light: #f8fafc;
    --bg-dark: #0f172a;
    --card-light: #ffffff;
    --card-dark: #1e293b;
    --border-light: #e2e8f0;
    --border-dark: #334155;
    --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.05);
    --shadow-dark: 0 4px 15px rgba(0, 0, 0, 0.2);
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    background-color: var(--bg-light);
    color: var(--text-dark);
  }

  .dark-mode {
    background-color: var(--bg-dark);
    color: var(--text-light);
  }

  /* Header styling */
  .header {
    background: var(--primary-gradient);
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    color: var(--text-light);
  }

  .logo {
    display: flex;
    align-items: center;
    font-weight: 700;
    font-size: 1.25rem;
    color: white;
  }

  .logo i {
    margin-right: 10px;
    font-size: 1.5rem;
  }

  .user-actions {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .notification-icon {
    position: relative;
    cursor: pointer;
  }

  .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--danger);
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .mode-toggle {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1rem;
  }

  .teacher-name {
    font-size: 0.9rem;
  }

  .logout-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
  }

  .logout-btn:hover {
    background: rgba(255, 255, 255, 0.3);
  }

  /* Sidebar styling */
  .sidebar {
    position: fixed;
    top: 56px;
    left: 0;
    bottom: 0;
    width: 220px;
    background: var(--card-light);
    border-right: 1px solid var(--border-light);
    overflow-y: auto;
    z-index: 99;
    transition: all 0.3s ease;
  }

  .dark-mode .sidebar {
    background: var(--card-dark);
    border-right-color: var(--border-dark);
  }

  .sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .sidebar li {
    margin: 5px 0;
  }

  .sidebar a {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: var(--text-dark);
    text-decoration: none;
    border-radius: 6px;
    margin: 0 10px;
    transition: all 0.3s;
  }

  .dark-mode .sidebar a {
    color: var(--text-light);
  }

  .sidebar a i {
    margin-right: 10px;
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
  }

  .sidebar a.active {
    background: var(--primary-color);
    color: white;
  }

  .sidebar a:hover:not(.active) {
    background: rgba(99, 102, 241, 0.1);
  }

  .dark-mode .sidebar a:hover:not(.active) {
    background: rgba(99, 102, 241, 0.2);
  }

  .sidebar-footer {
    padding: 15px;
    text-align: center;
    font-size: 0.8rem;
    color: #64748b;
    border-top: 1px solid var(--border-light);
  }

  .dark-mode .sidebar-footer {
    color: #94a3b8;
    border-top-color: var(--border-dark);
  }

  /* Main content area */
  .main-content {
    margin-left: 220px;
    padding: 80px 20px 20px;
  }

  /* Responsive design */
  @media (max-width: 768px) {
    .sidebar {
      transform: translateX(-100%);
    }

    .sidebar.active {
      transform: translateX(0);
    }

    .main-content {
      margin-left: 0;
    }

    .mobile-menu-toggle {
      display: block;
    }
  }

  @media (min-width: 769px) {
    .mobile-menu-toggle {
      display: none;
    }
  }

  /* Dashboard cards styling */
  .stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
  }

  @media (max-width: 1200px) {
    .stats {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 640px) {
    .stats {
      grid-template-columns: 1fr;
    }
  }

  .stat-card {
    background: var(--card-light);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--shadow-light);
    display: flex;
    flex-direction: column;
    transition: all 0.3s;
  }

  .dark-mode .stat-card {
    background: var(--card-dark);
    box-shadow: var(--shadow-dark);
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  }

  .dark-mode .stat-card:hover {
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
  }

  .stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
  }

  .stat-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #64748b;
  }

  .dark-mode .stat-header h3 {
    color: #94a3b8;
  }

  .stat-header i {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .stat-card:nth-child(1) .stat-header i {
    background: rgba(99, 102, 241, 0.1);
    color: var(--primary-color);
  }

  .stat-card:nth-child(2) .stat-header i {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
  }

  .stat-card:nth-child(3) .stat-header i {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
  }

  .stat-card:nth-child(4) .stat-header i {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info);
  }

  .dark-mode .stat-card:nth-child(1) .stat-header i {
    background: rgba(99, 102, 241, 0.2);
  }

  .dark-mode .stat-card:nth-child(2) .stat-header i {
    background: rgba(16, 185, 129, 0.2);
  }

  .dark-mode .stat-card:nth-child(3) .stat-header i {
    background: rgba(245, 158, 11, 0.2);
  }

  .dark-mode .stat-card:nth-child(4) .stat-header i {
    background: rgba(59, 130, 246, 0.2);
  }

  .stat-card p {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 10px 0;
  }

  .stat-trend {
    display: flex;
    align-items: center;
    font-size: 0.8rem;
    margin-top: auto;
  }

  .stat-trend.up {
    color: var(--success);
  }

  .stat-trend.down {
    color: var(--danger);
  }

  .stat-trend i {
    margin-right: 5px;
  }

  /* Charts styling */
  .charts-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 30px;
  }

  @media (max-width: 1024px) {
    .charts-container {
      grid-template-columns: 1fr;
    }
  }

  .chart-card {
    background: var(--card-light);
    border-radius: 16px;
    padding: 20px;
    box-shadow: var(--shadow-light);
  }

  .dark-mode .chart-card {
    background: var(--card-dark);
    box-shadow: var(--shadow-dark);
  }

  .chart-card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 1.1rem;
    color: var(--text-dark);
  }

  .dark-mode .chart-card h3 {
    color: var(--text-light);
  }

  /* Section titles */
  .section-title {
    font-size: 1.3rem;
    margin: 30px 0 20px;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .dark-mode .section-title {
    color: var(--text-light);
  }

  .add-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 0.9rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
  }

  .add-btn:hover {
    background: #4f46e5;
  }

  /* Custom CSS for Database Status Indicator */
  .db-status-indicator {
    display: flex;
    align-items: center;
    padding: 18px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    animation: slideIn 0.6s ease-out;
    position: relative;
    overflow: hidden;
  }
  
  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .db-status-indicator::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
    z-index: 1;
    pointer-events: none;
  }
  
  .db-status-indicator::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
    opacity: 0;
    z-index: 2;
    animation: pulse 8s infinite;
    pointer-events: none;
  }
  
  @keyframes pulse {
    0% { transform: scale(0.9); opacity: 0; }
    50% { opacity: 0.1; }
    100% { transform: scale(1.3); opacity: 0; }
  }
  
  .db-status-indicator.connected {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.3));
    border-left: 5px solid #10b981;
  }
  
  .db-status-indicator.disconnected {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.3));
    border-left: 5px solid #ef4444;
  }
  
  .dark-mode .db-status-indicator.connected {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.25), rgba(16, 185, 129, 0.35));
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  }
  
  .dark-mode .db-status-indicator.disconnected {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.25), rgba(239, 68, 68, 0.35));
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  }
  
  .db-status-icon {
    position: relative;
    font-size: 2rem;
    margin-right: 20px;
    z-index: 5;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  }
  
  .dark-mode .db-status-icon {
    background: rgba(30, 41, 59, 0.8);
  }
  
  .connected .db-status-icon {
    color: #10b981;
    animation: float 3s ease-in-out infinite;
  }
  
  .disconnected .db-status-icon {
    color: #ef4444;
    animation: shake 5s ease-in-out infinite;
  }
  
  @keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-6px); }
    100% { transform: translateY(0px); }
  }
  
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    2%, 6% { transform: translateX(-3px); }
    4%, 8% { transform: translateX(3px); }
    10% { transform: translateX(0); }
  }
  
  .db-status-details {
    flex: 1;
    z-index: 5;
  }
  
  .db-status-title {
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
    color: var(--text-dark);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .db-status-badge {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 12px;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
  }
  
  .connected .db-status-badge {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
  }
  
  .disconnected .db-status-badge {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
  }
  
  .dark-mode .connected .db-status-badge {
    background: rgba(16, 185, 129, 0.3);
    border: 1px solid rgba(16, 185, 129, 0.4);
  }
  
  .dark-mode .disconnected .db-status-badge {
    background: rgba(239, 68, 68, 0.3);
    border: 1px solid rgba(239, 68, 68, 0.4);
  }
  
  .connected .db-status-badge::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    margin-right: 5px;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
    animation: pulse-dot 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
  }
  
  @keyframes pulse-dot {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
    70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }
  
  .disconnected .db-status-badge::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    background: #ef4444;
    border-radius: 50%;
    margin-right: 5px;
  }
  
  .dark-mode .db-status-title {
    color: var(--text-light);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  }
  
  .db-status-info {
    font-size: 0.95rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    color: var(--text-dark);
  }
  
  .db-uptime {
    display: inline-flex;
    align-items: center;
    background: rgba(16, 185, 129, 0.1);
    padding: 4px 12px;
    border-radius: 20px;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    color: #10b981;
  }
  
  .db-uptime i {
    margin-right: 6px;
    font-size: 0.8rem;
  }
  
  .db-error {
    color: #ef4444;
    font-weight: 500;
  }
  
  .db-host, .db-version {
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.6);
    padding: 4px 12px;
    border-radius: 20px;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }
  
  .dark-mode .db-host, 
  .dark-mode .db-version {
    background: rgba(30, 41, 59, 0.5);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }
  
  .db-host:hover, 
  .db-version:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
  
  .db-host:before {
    content: '\f233';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    margin-right: 8px;
    font-size: 0.9rem;
  }
  
  .db-version:before {
    content: '\f021';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    margin-right: 8px;
    font-size: 0.9rem;
  }
  
  .db-status-actions {
    display: flex;
    gap: 6px;
    margin-left: 15px;
    z-index: 5;
  }
  
  .db-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.8);
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  }
  
  .dark-mode .db-action-btn {
    background: rgba(30, 41, 59, 0.7);
    color: #94a3b8;
  }
  
  .db-action-btn:hover {
    background: #fff;
    color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
  
  .dark-mode .db-action-btn:hover {
    background: rgba(30, 41, 59, 0.9);
    color: #60a5fa;
  }
  
  .db-action-btn i {
    font-size: 0.9rem;
  }
  
  /* Animation for refresh button */
  .db-action-btn:active i.fa-sync-alt {
    animation: spin 0.5s linear;
  }
  
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
  
  .dark-mode .db-status-info {
    color: var(--text-light);
  }
  
  .connected .db-status-info {
    color: #047857;
  }
  
  .disconnected .db-status-info {
    color: #b91c1c;
  }
</style>

<h2>Bảng Điều Khiển Giảng Viên</h2>

<!-- Database Connection Status Indicator -->
<div class="db-status-indicator <?php echo $db_status['status']; ?>">
  <div class="db-status-icon">
    <i class="fas <?php echo $db_status['status'] === 'connected' ? 'fa-database' : 'fa-exclamation-triangle'; ?>"></i>
  </div>
  <div class="db-status-details">
    <div class="db-status-title">
      <?php echo $db_status['status'] === 'connected' ? 'Kết nối cơ sở dữ liệu ổn định' : 'Lỗi kết nối cơ sở dữ liệu'; ?>
      <span class="db-status-badge"><?php echo $db_status['status'] === 'connected' ? 'Online' : 'Offline'; ?></span>
    </div>
    <div class="db-status-info">
      <?php if ($db_status['status'] === 'connected'): ?>
        <span class="db-host"><?php echo htmlspecialchars($db_status['host']); ?></span>
        <span class="db-version">MySQL <?php echo htmlspecialchars($db_status['version']); ?></span>
        <span class="db-uptime"><i class="fas fa-clock"></i> Hoạt động</span>
      <?php else: ?>
        <span class="db-error">Không thể kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra lại.</span>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($db_status['status'] === 'connected'): ?>
  <div class="db-status-actions">
    <button class="db-action-btn" title="Làm mới kết nối"><i class="fas fa-sync-alt"></i></button>
    <button class="db-action-btn" title="Thông tin chi tiết"><i class="fas fa-info-circle"></i></button>
  </div>
  <?php endif; ?>
</div>
    
<!-- Khu vực thống kê -->
<div class="stats">
  <div class="stat-card">
    <div class="stat-header">
      <h3>Tổng Khoá Học</h3>
      <i class="fas fa-book"></i>
    </div>
    <p><?php echo $totalCourses; ?></p>
    <div class="stat-trend up">
      <i class="fas fa-arrow-up"></i> 12% so với tháng trước
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-header">
      <h3>Số Học Viên</h3>
      <i class="fas fa-users"></i>
    </div>
    <p><?php echo $totalStudents; ?></p>
    <div class="stat-trend up">
      <i class="fas fa-arrow-up"></i> 8% so với tháng trước
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-header">
      <h3>Đánh Giá Trung Bình</h3>
      <i class="fas fa-star"></i>
    </div>
    <p><?php echo $avgRating; ?>/5</p>
    <div class="stat-trend up">
      <i class="fas fa-arrow-up"></i> 0.2 so với tháng trước
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-header">
      <h3>Tổng Thu Nhập</h3>
      <i class="fas fa-money-bill-wave"></i>
    </div>
    <p><?php echo $totalIncome; ?> đ</p>
    <div class="stat-trend up">
      <i class="fas fa-arrow-up"></i> 15% so với tháng trước
    </div>
  </div>
</div>
    
<!-- Khu vực biểu đồ -->
<div class="charts-container">
  <div class="chart-card">
    <h3>Ghi Danh Theo Tháng</h3>
    <canvas id="courseActivityChart" height="300"></canvas>
  </div>
  
  <div class="chart-card">
    <h3>Thu Nhập Theo Tháng</h3>
    <canvas id="studentDistributionChart" height="300"></canvas>
  </div>
</div>

<!-- Khu vực khóa học -->
<h2 class="section-title">
  Khoá Học Của Bạn
</h2>
    
<div class="courses-container">
  <?php 
  if (count($courses) === 0): ?>
  <div class="empty-state">
    <i class="fas fa-book-open"></i>
    <p>Bạn chưa có khóa học nào. Hãy tạo khóa học đầu tiên!</p>
  </div>
  <?php else: ?>
  <?php foreach ($courses as $course): ?>
    <div class="course-card">
      <?php if (!empty($course['image'])): ?>
        <?php
          // Check if image path already contains BASE_URL
          $imagePath = $course['image'];
          if (strpos($imagePath, 'http') !== 0 && strpos($imagePath, '/') !== 0) {
            $imagePath = BASE_URL . $imagePath;
          }
        ?>
        <img src="<?php echo htmlspecialchars($imagePath); ?>" 
             alt="<?php echo htmlspecialchars($course['title']); ?>">
      <?php else: ?>
        <img src="<?php echo BASE_URL; ?>public/images/course-placeholder.jpg" alt="Course Thumbnail">
      <?php endif; ?>

      <div class="course-content">
        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
        
        <div class="course-meta">
          <div class="meta-item price">
            <i class="fas fa-tag"></i> <?php echo number_format($course['price'], 0, ',', '.'); ?> ₫
          </div>
          <div class="meta-item rating">
            <i class="fas fa-star"></i> <?php echo isset($course['rating']) ? $course['rating'] : '0.0'; ?>
          </div>
          <div class="meta-item students">
            <i class="fas fa-user-graduate"></i> <?php echo isset($course['students']) ? $course['students'] : 0; ?>
          </div>
        </div>
        
        <p><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
        
        <div class="course-actions">
          <a href="<?php echo BASE_URL; ?>app/views/product/course_management.php?course_id=<?php echo $course['course_id']; ?>" 
              class="course-btn">
            <i class="fas fa-cog"></i> Quản Lý
          </a>
          <a href="<?php echo BASE_URL; ?>app/views/product/lecture_management.php?course_id=<?php echo $course['course_id']; ?>" 
              class="course-btn">
            <i class="fas fa-book"></i> Bài Giảng
          </a>
          <a href="<?php echo BASE_URL; ?>app/views/product/course_analytics.php?course_id=<?php echo $course['course_id']; ?>" 
              class="course-btn outline">
            <i class="fas fa-chart-line"></i> Thống Kê
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Analytics Overview Section -->
<h2 class="section-title">Tổng Quan Phân Tích</h2>

<!-- Quick Actions Section -->
<div class="quick-actions">
  <h2 class="section-title">Thao Tác Nhanh</h2>
  <div class="action-buttons">
    <a href="<?php echo BASE_URL; ?>app/views/product/create_course.php" class="btn-action">
      <i class="fas fa-plus-circle"></i>
      <span>Tạo Khoá Học Mới</span>
    </a>
    <a href="<?php echo BASE_URL; ?>app/views/product/instructor_student_list.php" class="btn-action">
      <i class="fas fa-users"></i>
      <span>Quản Lý Học Viên</span>
    </a>
    <a href="<?php echo BASE_URL; ?>app/views/product/instructor_discussion.php" class="btn-action">
      <i class="fas fa-comments"></i>
      <span>Trò Chuyện & Thảo Luận</span>
    </a>
  </div>
</div>

<div class="analytics-summary">
    <h3>Phân Tích Khóa Học</h3>
    <div class="table-responsive">
        <table class="analytics-table">
            <thead>
                <tr>
                    <th>Khóa Học</th>
                    <th>Học Viên</th>
                    <th>Hoàn Thành</th>
                    <th>Đánh Giá</th>
                    <th>Doanh Thu</th>
                    <th>Chi Tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                    <td><?php echo isset($course['students']) ? $course['students'] : 0; ?></td>
                    <td><?php echo rand(5, max(5, isset($course['students']) ? $course['students'] : 5)); ?> (<?php echo rand(10, 90); ?>%)</td>
                    <td><?php echo number_format(isset($course['rating']) ? $course['rating'] : 0, 1); ?> <small>(<?php echo rand(5, 50); ?>)</small></td>
                    <td><?php echo number_format($course['price'] * (isset($course['students']) ? $course['students'] : 0), 0, ',', '.'); ?>đ</td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>app/views/product/course_analytics.php?course_id=<?php echo $course['course_id']; ?>" class="btn-analytics-small">
                            <i class="fas fa-chart-line"></i> Chi Tiết
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assignments & Assessments Section -->
<div class="assignments-section">
  <h2 class="section-title">
    Bài Tập & Đánh Giá
    <a href="<?php echo BASE_URL; ?>app/views/product/create_assignment.php" class="add-btn">
      <i class="fas fa-plus"></i> Tạo Bài Tập Mới
    </a>
  </h2>
  
  <div class="tabs">
    <button class="tab-btn active" data-tab="assignments">Bài Tập</button>
    <button class="tab-btn" data-tab="quizzes">Bài Kiểm Tra</button>
    <button class="tab-btn" data-tab="exams">Bài Thi</button>
  </div>
  
  <div class="tab-content active" id="assignments-tab">
    <div class="assignment-grid">
      <?php
      // Here we would normally fetch assignments from database
      // For now, just display placeholder content
      $placeholderAssignments = [
        ['id' => 1, 'title' => 'Bài tập: Xây dựng trang web cá nhân', 'course' => 'Lập trình Web', 'due_date' => '2023-12-15', 'submissions' => 24],
        ['id' => 2, 'title' => 'Bài tập: Thiết kế database', 'course' => 'Cơ sở dữ liệu', 'due_date' => '2023-12-20', 'submissions' => 18],
        ['id' => 3, 'title' => 'Bài tập: Giải thuật tìm kiếm', 'course' => 'Cấu trúc dữ liệu', 'due_date' => '2023-12-22', 'submissions' => 15]
      ];
      
      foreach ($placeholderAssignments as $assignment):
      ?>
      <div class="assignment-card">
        <div class="assignment-header">
          <h3><?php echo $assignment['title']; ?></h3>
          <div class="assignment-course"><?php echo $assignment['course']; ?></div>
        </div>
        <div class="assignment-details">
          <div class="detail-item">
            <i class="fas fa-calendar"></i> Hạn nộp: <?php echo $assignment['due_date']; ?>
          </div>
          <div class="detail-item">
            <i class="fas fa-users"></i> Đã nộp: <?php echo $assignment['submissions']; ?>
          </div>
        </div>
        <div class="assignment-actions">
          <a href="#" class="assignment-btn"><i class="fas fa-edit"></i> Chỉnh Sửa</a>
          <a href="#" class="assignment-btn"><i class="fas fa-eye"></i> Xem Bài Nộp</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  
  <div class="tab-content" id="quizzes-tab">
    <div class="assignment-grid">
      <?php
      // Placeholder quizzes
      $placeholderQuizzes = [
        ['id' => 1, 'title' => 'Kiểm tra: HTML & CSS cơ bản', 'course' => 'Lập trình Web', 'time_limit' => 30, 'questions' => 15, 'attempts' => 28],
        ['id' => 2, 'title' => 'Kiểm tra: SQL Queries', 'course' => 'Cơ sở dữ liệu', 'time_limit' => 45, 'questions' => 20, 'attempts' => 22]
      ];
      
      foreach ($placeholderQuizzes as $quiz):
      ?>
      <div class="assignment-card">
        <div class="assignment-header">
          <h3><?php echo $quiz['title']; ?></h3>
          <div class="assignment-course"><?php echo $quiz['course']; ?></div>
        </div>
        <div class="assignment-details">
          <div class="detail-item">
            <i class="fas fa-clock"></i> Thời gian: <?php echo $quiz['time_limit']; ?> phút
          </div>
          <div class="detail-item">
            <i class="fas fa-question-circle"></i> Câu hỏi: <?php echo $quiz['questions']; ?>
          </div>
          <div class="detail-item">
            <i class="fas fa-users"></i> Lượt làm: <?php echo $quiz['attempts']; ?>
          </div>
        </div>
        <div class="assignment-actions">
          <a href="#" class="assignment-btn"><i class="fas fa-edit"></i> Chỉnh Sửa</a>
          <a href="#" class="assignment-btn"><i class="fas fa-chart-bar"></i> Kết Quả</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  
  <div class="tab-content" id="exams-tab">
    <div class="assignment-grid">
      <?php
      // Placeholder exams
      $placeholderExams = [
        ['id' => 1, 'title' => 'Bài thi cuối kỳ: Lập trình Web', 'course' => 'Lập trình Web', 'time_limit' => 120, 'date' => '2023-12-25', 'participants' => 30],
        ['id' => 2, 'title' => 'Bài thi giữa kỳ: Cơ sở dữ liệu', 'course' => 'Cơ sở dữ liệu', 'time_limit' => 90, 'date' => '2023-11-15', 'participants' => 25]
      ];
      
      foreach ($placeholderExams as $exam):
      ?>
      <div class="assignment-card">
        <div class="assignment-header">
          <h3><?php echo $exam['title']; ?></h3>
          <div class="assignment-course"><?php echo $exam['course']; ?></div>
        </div>
        <div class="assignment-details">
          <div class="detail-item">
            <i class="fas fa-calendar"></i> Ngày thi: <?php echo $exam['date']; ?>
          </div>
          <div class="detail-item">
            <i class="fas fa-clock"></i> Thời gian: <?php echo $exam['time_limit']; ?> phút
          </div>
          <div class="detail-item">
            <i class="fas fa-users"></i> Thí sinh: <?php echo $exam['participants']; ?>
          </div>
        </div>
        <div class="assignment-actions">
          <a href="#" class="assignment-btn"><i class="fas fa-edit"></i> Chỉnh Sửa</a>
          <a href="#" class="assignment-btn"><i class="fas fa-chart-bar"></i> Kết Quả</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Define enrollmentData for the charts before loading the scripts -->
<script>
    // Dữ liệu cho biểu đồ của giảng viên
    const chartData = {
        labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
        enrollments: <?php echo json_encode($monthlyData['enrollments']); ?>,
        income: <?php echo json_encode($monthlyData['income']); ?>
    };
</script>

<!-- Include the necessary JavaScript files -->
<script src="<?= BASE_URL ?>public/js/instructor_dashboard.js"></script>

<script>
// Chart data and initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        // Sử dụng màu phù hợp với theme
        const getColors = () => {
            const isDark = document.body.classList.contains('dark-mode');
            return {
                gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)',
                textColor: isDark ? '#cbd5e1' : '#64748b',
                enrollmentColor: 'rgba(99, 102, 241, 0.8)',
                enrollmentBorder: 'rgba(99, 102, 241, 1)',
                incomeColor: 'rgba(16, 185, 129, 0.8)',
                incomeBorder: 'rgba(16, 185, 129, 1)',
                enrollmentBg: isDark ? 'rgba(99, 102, 241, 0.2)' : 'rgba(99, 102, 241, 0.1)',
                incomeBg: isDark ? 'rgba(16, 185, 129, 0.2)' : 'rgba(16, 185, 129, 0.1)'
            };
        };

        const colors = getColors();

        // Activity Chart - Ghi danh theo tháng
        const activityCtx = document.getElementById('courseActivityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Số Lượng Ghi Danh',
                    data: chartData.enrollments,
                    borderColor: colors.enrollmentBorder,
                    backgroundColor: colors.enrollmentBg,
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: colors.enrollmentBorder,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: colors.textColor,
                            font: {
                                size: 12,
                                family: "'Segoe UI', sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: colors.gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            color: colors.textColor,
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: colors.gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            color: colors.textColor,
                            font: {
                                size: 11
                            },
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Income Chart - Thu nhập theo tháng
        const incomeCtx = document.getElementById('studentDistributionChart').getContext('2d');
        new Chart(incomeCtx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Thu Nhập (VNĐ)',
                    data: chartData.income,
                    backgroundColor: colors.incomeColor,
                    borderColor: colors.incomeBorder,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: colors.textColor,
                            font: {
                                size: 12,
                                family: "'Segoe UI', sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND'
                                }).format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: colors.gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            color: colors.textColor,
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: colors.gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            color: colors.textColor,
                            font: {
                                size: 11
                            },
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN', {
                                    style: 'currency',
                                    currency: 'VND',
                                    maximumFractionDigits: 0
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php
// Include footer
$footer_path = ROOT_DIR . '/app/includes/footer.php';
if (file_exists($footer_path)) {
    include_once $footer_path;
} else {
    // Try alternate path format
    $alt_footer_path = dirname(__DIR__, 2) . '/includes/footer.php';
    if (file_exists($alt_footer_path)) {
        include_once $alt_footer_path;
    } else {
        // Add minimal footer if not found
        echo '</div>'; // Close main-content div
        echo '
        <footer class="footer">
            <div class="footer-content">
                <p>&copy; ' . date('Y') . ' WebCourses. Tất cả quyền được bảo lưu.</p>
            </div>
        </footer>
        ';
    }
}
?>
</body>
</html>
