<?php
// forum.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  $_SESSION['loginError'] = "Vui lòng đăng nhập để xem diễn đàn.";
  header("Location: " . BASE_URL . "app/views/product/home.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

// Kiểm tra và tạo các bảng cơ sở dữ liệu cần thiết
try {
  // Tạo bảng discussion nếu chưa tồn tại
  $conn->query("
    CREATE TABLE IF NOT EXISTS discussion (
      discussion_id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      content TEXT NOT NULL,
      user_id INT NOT NULL,
      course_id INT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX (user_id),
      INDEX (course_id),
      FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
      FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
    ) ENGINE=InnoDB
  ");
  
  // Tạo bảng comments nếu chưa tồn tại
  $conn->query("
    CREATE TABLE IF NOT EXISTS comments (
      comment_id INT AUTO_INCREMENT PRIMARY KEY,
      discussion_id INT NOT NULL,
      user_id INT NOT NULL,
      content TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
      INDEX (discussion_id),
      INDEX (user_id),
      FOREIGN KEY (discussion_id) REFERENCES discussion(discussion_id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB
  ");
} catch (PDOException $e) {
  $error = "Lỗi khi tạo bảng dữ liệu: " . $e->getMessage();
}

// Xử lý phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10; // Số chủ đề hiển thị trên mỗi trang
$offset = ($page - 1) * $limit;

// Xử lý lọc theo khóa học
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Lấy danh sách khóa học mà người dùng có quyền truy cập
if ($role == 'student') {
  $courseStmt = $conn->prepare("
    SELECT c.course_id, c.title 
    FROM courses c
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.user_id = ? AND e.status = 'active'
    ORDER BY c.title ASC
  ");
  $courseStmt->bindParam(1, $user_id, PDO::PARAM_INT);
} else if ($role == 'instructor') {
  $courseStmt = $conn->prepare("
    SELECT course_id, title 
    FROM courses 
    WHERE instructor_id = ?
    ORDER BY title ASC
  ");
  $courseStmt->bindParam(1, $user_id, PDO::PARAM_INT);
} else { // admin
  $courseStmt = $conn->prepare("
    SELECT course_id, title 
    FROM courses 
    ORDER BY title ASC
  ");
}

$courseStmt->execute();
$courses = $courseStmt;

// Lấy tổng số chủ đề thảo luận (cho phân trang)
if ($role == 'student') {
  if ($course_filter > 0) {
    $countStmt = $conn->prepare("
      SELECT COUNT(*) as total
      FROM discussion d
      JOIN courses c ON d.course_id = c.course_id
      JOIN enrollments e ON c.course_id = e.course_id
      WHERE e.user_id = ? AND e.status = 'active' AND d.course_id = ?
    ");
    $countStmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $countStmt->bindParam(2, $course_filter, PDO::PARAM_INT);
  } else {
    $countStmt = $conn->prepare("
      SELECT COUNT(*) as total
      FROM discussion d
      JOIN courses c ON d.course_id = c.course_id
      JOIN enrollments e ON c.course_id = e.course_id
      WHERE e.user_id = ? AND e.status = 'active'
    ");
    $countStmt->bindParam(1, $user_id, PDO::PARAM_INT);
  }
} else if ($role == 'instructor') {
  if ($course_filter > 0) {
    $countStmt = $conn->prepare("
      SELECT COUNT(*) as total
      FROM discussion d
      JOIN courses c ON d.course_id = c.course_id
      WHERE c.instructor_id = ? AND d.course_id = ?
    ");
    $countStmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $countStmt->bindParam(2, $course_filter, PDO::PARAM_INT);
  } else {
    $countStmt = $conn->prepare("
      SELECT COUNT(*) as total
      FROM discussion d
      JOIN courses c ON d.course_id = c.course_id
      WHERE c.instructor_id = ?
    ");
    $countStmt->bindParam(1, $user_id, PDO::PARAM_INT);
  }
} else { // admin
  if ($course_filter > 0) {
    $countStmt = $conn->prepare("
      SELECT COUNT(*) as total
      FROM discussion
      WHERE course_id = ?
    ");
    $countStmt->bindParam(1, $course_filter, PDO::PARAM_INT);
  } else {
    $countStmt = $conn->prepare("
      SELECT COUNT(*) as total
      FROM discussion
    ");
  }
}

$countStmt->execute();
$countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalDiscussions = $countResult['total'];
$totalPages = ceil($totalDiscussions / $limit);

// Lấy danh sách thảo luận với phân trang và lọc
if ($role == 'student') {
  if ($course_filter > 0) {
    $stmt = $conn->prepare("
      SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
             u.username, u.role as author_role, c.title as course_title,
             (SELECT COUNT(*) FROM comments WHERE discussion_id = d.discussion_id AND status = 'approved') as comment_count
      FROM discussion d
      JOIN users u ON d.user_id = u.user_id
      JOIN courses c ON d.course_id = c.course_id
      JOIN enrollments e ON c.course_id = e.course_id
      WHERE e.user_id = ? AND e.status = 'active' AND d.course_id = ?
      ORDER BY d.updated_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $course_filter, PDO::PARAM_INT);
    $stmt->bindParam(3, $limit, PDO::PARAM_INT);
    $stmt->bindParam(4, $offset, PDO::PARAM_INT);
  } else {
    $stmt = $conn->prepare("
      SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
             u.username, u.role as author_role, c.title as course_title,
             (SELECT COUNT(*) FROM comments WHERE discussion_id = d.discussion_id AND status = 'approved') as comment_count
      FROM discussion d
      JOIN users u ON d.user_id = u.user_id
      JOIN courses c ON d.course_id = c.course_id
      JOIN enrollments e ON c.course_id = e.course_id
      WHERE e.user_id = ? AND e.status = 'active'
      ORDER BY d.updated_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
  }
} else if ($role == 'instructor') {
  if ($course_filter > 0) {
    $stmt = $conn->prepare("
      SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
             u.username, u.role as author_role, c.title as course_title,
             (SELECT COUNT(*) FROM comments WHERE discussion_id = d.discussion_id) as comment_count
      FROM discussion d
      JOIN users u ON d.user_id = u.user_id
      JOIN courses c ON d.course_id = c.course_id
      WHERE c.instructor_id = ? AND d.course_id = ?
      ORDER BY d.updated_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $course_filter, PDO::PARAM_INT);
    $stmt->bindParam(3, $limit, PDO::PARAM_INT);
    $stmt->bindParam(4, $offset, PDO::PARAM_INT);
  } else {
    $stmt = $conn->prepare("
      SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
             u.username, u.role as author_role, c.title as course_title,
             (SELECT COUNT(*) FROM comments WHERE discussion_id = d.discussion_id) as comment_count
      FROM discussion d
      JOIN users u ON d.user_id = u.user_id
      JOIN courses c ON d.course_id = c.course_id
      WHERE c.instructor_id = ?
      ORDER BY d.updated_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
  }
} else { // admin
  if ($course_filter > 0) {
    $stmt = $conn->prepare("
      SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
             u.username, u.role as author_role, c.title as course_title,
             (SELECT COUNT(*) FROM comments WHERE discussion_id = d.discussion_id) as comment_count
      FROM discussion d
      JOIN users u ON d.user_id = u.user_id
      JOIN courses c ON d.course_id = c.course_id
      WHERE d.course_id = ?
      ORDER BY d.updated_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $course_filter, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
  } else {
    $stmt = $conn->prepare("
      SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
             u.username, u.role as author_role, c.title as course_title,
             (SELECT COUNT(*) FROM comments WHERE discussion_id = d.discussion_id) as comment_count
      FROM discussion d
      JOIN users u ON d.user_id = u.user_id
      JOIN courses c ON d.course_id = c.course_id
      ORDER BY d.updated_at DESC
      LIMIT ? OFFSET ?
    ");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->bindParam(2, $offset, PDO::PARAM_INT);
  }
}

$stmt->execute();
$discussions = $stmt;

// Thông báo thành công
if (isset($_GET['success']) && $_GET['success'] == 1) {
  $message = "Thao tác thành công!";
}

// Thông báo khi tạo mới thảo luận
if (isset($_GET['created']) && $_GET['created'] == 1) {
  $message = "Chủ đề thảo luận đã được tạo thành công!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Diễn Đàn - Forum</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font từ Google -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Reset mặc định */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Nunito', 'Quicksand', sans-serif;
        background-color: #f5f7fa;
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        color: #333;
    }

    /* Header */
    header {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        padding: 20px 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Logo styling */
    .logo {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(90deg, #F9D423, #FF4E50);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        font-family: 'Montserrat', sans-serif;
        display: inline-block;
        cursor: pointer;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        letter-spacing: -0.5px;
    }

    /* Bounce animation for logo */
    .logo:hover {
        animation: bounce 0.8s ease-in-out;
    }

    @keyframes bounce {
        0% { transform: scale(1); }
        20% { transform: scale(1.2); }
        40% { transform: scale(0.9); }
        60% { transform: scale(1.1); }
        80% { transform: scale(0.95); }
        100% { transform: scale(1); }
    }

    nav ul {
        list-style: none;
        display: flex;
        gap: 25px;
    }

    nav ul li a {
        color: #fff;
        text-decoration: none;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        letter-spacing: 0.3px;
        font-size: 1.05rem;
    }

    nav ul li a:hover {
        color: #FFC107;
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    /* Main container */
    .container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
        animation: fadeIn 1s ease forwards;
    }

    /* Breadcrumbs */
    .breadcrumbs {
        display: flex;
        margin-bottom: 20px;
        font-size: 0.9rem;
        color: #555;
    }

    .breadcrumbs a {
        color: #1e3c72;
        text-decoration: none;
        margin: 0 5px;
    }

    .breadcrumbs a:first-child {
        margin-left: 0;
    }

    .breadcrumbs a:hover {
        text-decoration: underline;
    }

    /* Page title and controls */
    .forum-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .page-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 2rem;
        color: #1e3c72;
        margin: 0;
        padding-bottom: 5px;
        border-bottom: 3px solid #FFC107;
        animation: fadeInDown 1s ease;
    }

    /* Create discussion button */
    .btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        text-align: center;
        text-decoration: none;
        border: none;
        font-size: 1rem;
    }

    .btn-primary {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
        box-shadow: 0 4px 8px rgba(30, 60, 114, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(30, 60, 114, 0.3);
    }

    /* Forum top section */
    .forum-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    /* Filter form */
    .filter-form {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        align-items: center;
    }

    .filter-label {
        font-weight: 600;
        margin-right: 10px;
        color: #333;
    }

    .filter-select {
        padding: 8px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        font-family: 'Nunito', sans-serif;
        background-color: white;
        min-width: 200px;
    }

    .filter-select:focus {
        outline: none;
        border-color: #1e3c72;
    }

    .filter-btn {
        padding: 8px 15px;
        background: #1e3c72;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .filter-btn:hover {
        background: #0f2a5c;
        transform: translateY(-2px);
    }

    /* Alert boxes */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 600;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    /* Forum list */
    .forum-list {
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .forum-list-header {
        background: #f8f9fa;
        padding: 15px 20px;
        display: grid;
        grid-template-columns: 3fr 1fr 1fr 1fr;
        font-weight: 700;
        border-bottom: 2px solid #eee;
    }

    .forum-item {
        display: grid;
        grid-template-columns: 3fr 1fr 1fr 1fr;
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        transition: all 0.3s ease;
    }

    .forum-item:hover {
        background-color: #f8f9fa;
    }

    .forum-item:last-child {
        border-bottom: none;
    }

    .topic-info {
        display: flex;
        flex-direction: column;
    }

    .topic-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }

    .topic-title a {
        color: #1e3c72;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .topic-title a:hover {
        color: #0f2a5c;
        text-decoration: underline;
    }

    .topic-course {
        display: inline-block;
        background: #e0f0ff;
        color: #1e3c72;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 5px;
        max-width: fit-content;
    }

    .topic-author {
        display: flex;
        flex-direction: column;
    }

    .author-name {
        font-weight: 600;
    }

    .author-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 0.7rem;
        margin-top: 5px;
        font-weight: 600;
        max-width: fit-content;
    }

    .badge-instructor {
        background: #e0f0ff;
        color: #1e3c72;
    }

    .badge-admin {
        background: #f8d7da;
        color: #721c24;
    }

    .topic-date {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .topic-comments {
        font-weight: 600;
        color: #1e3c72;
    }

    /* No discussions message */
    .no-discussions {
        padding: 40px;
        text-align: center;
        color: #6c757d;
    }

    .no-discussions p {
        margin-bottom: 20px;
        font-size: 1.1rem;
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 30px;
        gap: 5px;
    }

    .page-item {
        display: inline-block;
    }

    .page-link {
        display: inline-block;
        padding: 8px 15px;
        background: white;
        color: #1e3c72;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .page-link:hover {
        background: #f5f7fa;
        transform: translateY(-2px);
    }

    .page-item.active .page-link {
        background: #1e3c72;
        color: white;
    }

    .page-item.disabled .page-link {
        color: #b0b0b0;
        pointer-events: none;
        background: #f5f5f5;
    }

    /* Modal for new topic */
    .modal {
        display: none;
        position: fixed;
        z-index: 1100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.6);
        animation: fadeIn 0.3s ease;
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 25px;
        border-radius: 10px;
        width: 80%;
        max-width: 800px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
        animation: slideInDown 0.4s ease;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #eee;
        padding-bottom: 15px;
    }

    .modal-title {
        font-size: 1.5rem;
        color: #1e3c72;
        font-weight: 700;
        font-family: 'Montserrat', sans-serif;
    }

    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .close:hover {
        color: #1e3c72;
    }

    /* Footer */
    footer {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        text-align: center;
        padding: 25px 0;
        margin-top: 40px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideInDown {
        from { opacity: 0; transform: translateY(-50px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Truncate long text */
    .text-truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    /* Responsive */
    @media (max-width: 991px) {
        .forum-list-header, .forum-item {
            grid-template-columns: 2fr 1fr 1fr;
        }
        
        .forum-list-header div:nth-child(2), .forum-item div:nth-child(2) {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .header-container {
            flex-direction: column;
        }
        
        nav ul {
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .forum-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .forum-top {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .filter-form {
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .filter-select {
            width: 100%;
        }
        
        .forum-list-header, .forum-item {
            grid-template-columns: 3fr 1fr;
        }
        
        .forum-list-header div:nth-child(2), .forum-item div:nth-child(2),
        .forum-list-header div:nth-child(3), .forum-item div:nth-child(3) {
            display: none;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="header-container">
      <div class="logo">Học Tập</div>
      <nav>
        <ul>
          <li><a href="home.php">Trang Chủ</a></li>
          <li><a href="<?php echo $role == 'student' ? 'student_dashboard.php' : ($role == 'instructor' ? 'instructor_dashboard.php' : 'admin_dashboard.php'); ?>">Dashboard</a></li>
          <?php if ($role == 'student'): ?>
            <li><a href="assignments.php">Bài Tập</a></li>
            <li><a href="quizzes.php">Trắc Nghiệm</a></li>
          <?php endif; ?>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
      <a href="<?php echo $role == 'student' ? 'student_dashboard.php' : ($role == 'instructor' ? 'instructor_dashboard.php' : 'admin_dashboard.php'); ?>">Dashboard</a> &gt;
      <a href="forum.php">Diễn Đàn</a>
    </div>
    
    <h1 class="page-title">Diễn Đàn Thảo Luận</h1>
    
    <?php if (!empty($message)): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <!-- Filter container -->
    <div class="filter-container">
      <h2 class="filter-title">Bộ lọc và tìm kiếm</h2>
      <form class="filter-form" action="" method="get">
        <div class="form-group">
          <label for="course_id" class="form-label">Khóa học</label>
          <select name="course_id" id="course_id" class="form-control">
            <option value="">Tất cả khóa học</option>
            <?php while ($course = $courses->fetch(PDO::FETCH_ASSOC)): ?>
              <option value="<?php echo $course['course_id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($course['title']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="filter-buttons">
          <button type="submit" class="btn btn-primary">Lọc kết quả</button>
          <a href="create_discussion.php" class="btn btn-create">Tạo chủ đề mới</a>
        </div>
      </form>
    </div>
    
    <!-- Topic listing -->
    <div class="topic-list">
      <?php if ($discussions->rowCount() > 0): ?>
        <?php while ($topic = $discussions->fetch(PDO::FETCH_ASSOC)): ?>
          <div class="topic-card">
            <div class="topic-header">
              <a href="discussion_detail.php?id=<?php echo $topic['discussion_id']; ?>" class="topic-title">
                <?php echo htmlspecialchars($topic['title']); ?>
              </a>
              <span class="topic-course"><?php echo htmlspecialchars($topic['course_title']); ?></span>
            </div>
            
            <div class="topic-meta">
              <span>Đăng lúc: <?php echo date('d/m/Y H:i', strtotime($topic['created_at'])); ?></span>
            </div>
            
            <div class="topic-excerpt">
              <?php 
                $excerpt = strip_tags($topic['content']);
                echo htmlspecialchars(mb_substr($excerpt, 0, 200)) . (mb_strlen($excerpt) > 200 ? '...' : '');
              ?>
            </div>
            
            <div class="topic-footer">
              <div class="topic-author">
                Tác giả: <?php echo htmlspecialchars($topic['username']); ?>
              </div>
              <div class="topic-comment-count">
                <?php echo $topic['comment_count']; ?> bình luận
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-topics">
          <h3>Không có chủ đề thảo luận nào</h3>
          <p>Hãy tạo chủ đề thảo luận đầu tiên để trao đổi với giảng viên và các sinh viên khác!</p>
          <a href="create_discussion.php" class="btn btn-create">Tạo chủ đề mới</a>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['course_id']) ? '&course_id=' . $_GET['course_id'] : ''; ?>">&laquo;</a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <?php if ($i == $page): ?>
            <span class="current"><?php echo $i; ?></span>
          <?php else: ?>
            <a href="?page=<?php echo $i; ?><?php echo isset($_GET['course_id']) ? '&course_id=' . $_GET['course_id'] : ''; ?>"><?php echo $i; ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['course_id']) ? '&course_id=' . $_GET['course_id'] : ''; ?>">&raquo;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  
  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>
</body>
</html> 