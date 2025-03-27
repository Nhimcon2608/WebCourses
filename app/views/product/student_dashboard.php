<?php
// student_dashboard.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để truy cập dashboard.";
  header("Location: " . BASE_URL . "app/views/product/home.php");
  exit();
}
$user_id = $_SESSION['user_id'];

// Check if AssignmentSubmissions table exists before attempting to query it
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'AssignmentSubmissions'");
if ($checkTable && $checkTable->rowCount() > 0) {
    $tableExists = true;
}

// Phân trang cho khóa học đã đăng ký (động)
$limit = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT c.course_id, c.title, c.description, e.status 
    FROM Enrollments e 
    JOIN Courses c ON e.course_id = c.course_id 
    WHERE e.user_id = ? AND e.status = 'active'
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $limit, PDO::PARAM_INT);
$stmt->bindParam(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$enrollments = $stmt;

$resultTotal = $conn->query("
    SELECT COUNT(*) AS total 
    FROM Enrollments 
    WHERE user_id = $user_id AND status = 'active'
");
$totalData = $resultTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalData / $limit);

// Just count total assignments for reference
try {
    $totalAssignmentsResult = $conn->query("
        SELECT COUNT(*) AS total 
        FROM Assignments a
        JOIN Courses c ON a.course_id = c.course_id
        JOIN Enrollments e ON c.course_id = e.course_id
        WHERE e.user_id = $user_id AND e.status = 'active'
    ");
    $totalAssignments = $totalAssignmentsResult ? $totalAssignmentsResult->fetch(PDO::FETCH_ASSOC)['total'] : 0;
} catch (Exception $e) {
    $totalAssignments = 0;
}

// Helper function to display time ago
function getTimeAgo($timestamp) {
    $timeAgo = time() - strtotime($timestamp);
    
    if ($timeAgo < 60) {
        return 'Vừa xong';
    } else if ($timeAgo < 3600) {
        return floor($timeAgo / 60) . ' phút trước';
    } else if ($timeAgo < 86400) {
        return floor($timeAgo / 3600) . ' giờ trước';
    } else if ($timeAgo < 604800) {
        return floor($timeAgo / 86400) . ' ngày trước';
    } else if ($timeAgo < 2592000) {
        return floor($timeAgo / 604800) . ' tuần trước';
    } else if ($timeAgo < 31536000) {
        return floor($timeAgo / 2592000) . ' tháng trước';
    } else {
        return floor($timeAgo / 31536000) . ' năm trước';
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Sinh Viên - Học Tập Trực Tuyến</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font từ Google -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Reset mặc định */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Nunito', 'Quicksand', sans-serif;
        background-color: rgb(255, 255, 255);
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

    /* Định dạng logo */
    .logo {
        font-size: 2.5rem;
        /* Kích thước chữ lớn */
        font-weight: 800;
        /* Chữ đậm */
        background: linear-gradient(90deg, #F9D423, #FF4E50);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        /* Màu gradient */
        font-family: 'Montserrat', sans-serif;
        /* Font chữ hiện đại */
        display: inline-block;
        /* Để áp dụng animation */
        cursor: pointer;
        /* Con trỏ tay khi hover */
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        letter-spacing: -0.5px;
    }

    /* Hiệu ứng nảy lên khi hover */
    .logo:hover {
        animation: bounce 0.8s ease-in-out;
        /* Chạy animation khi hover */
    }

    /* Keyframes cho hiệu ứng nảy lên */
    @keyframes bounce {
        0% {
            transform: scale(1);
            /* Kích thước ban đầu */
        }

        20% {
            transform: scale(1.2);
            /* Phóng to */
        }

        40% {
            transform: scale(0.9);
            /* Thu nhỏ */
        }

        60% {
            transform: scale(1.1);
            /* Phóng to nhẹ */
        }

        80% {
            transform: scale(0.95);
            /* Thu nhỏ nhẹ */
        }

        100% {
            transform: scale(1);
            /* Trở về kích thước ban đầu */
        }
    }

    /* Responsive (tùy chọn) */
    @media (max-width: 768px) {
        .logo {
            font-size: 2rem;
            /* Giảm kích thước trên mobile */
        }
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

    /* Layout: sidebar + main content */
    .dashboard-container {
        display: flex;
        width: 90%;
        max-width: 1200px;
        margin: 40px auto;
        gap: 25px;
        opacity: 0;
        animation: fadeIn 1s ease forwards;
        overflow: visible;
    }

    .sidebar {
        width: 250px;
        background: linear-gradient(180deg, #1e3c72, #2a5298);
        color: #ecf0f1;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        max-height: 100vh;
        position: sticky;
        top: 100px;
        overflow-y: auto;
          }

    .sidebar h2 {
        margin-bottom: 25px;
        font-size: 1.6rem;
        text-align: center;
        animation: fadeInDown 1s ease;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        color: #FFC107;
    }

    .sidebar ul {
        list-style: none;
    }

    .sidebar ul li {
        margin-bottom: 12px;
        transform: translateX(-20px);
        opacity: 0;
        animation: slideIn 0.5s ease forwards;
        animation-delay: calc(0.1s * var(--i));
    }

    .sidebar ul li a {
        color: #fff;
        text-decoration: none;
        font-size: 1.05rem;
        display: block;
        padding: 12px 15px;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        letter-spacing: 0.2px;
    }

    .sidebar ul li a:hover {
        background: linear-gradient(90deg, #FF8008, #FFA100);
        transform: translateX(5px);
        color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .main-content {
        flex: 1;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        overflow: visible;
    }

    .main-content h2 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.8rem;
        color: #1e3c72;
        margin-bottom: 25px;
        border-bottom: 3px solid #FFC107;
        padding-bottom: 12px;
        animation: fadeInDown 1s ease;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    /* Thẻ khóa học */
    .course-card {
        background: #fff;
        border: none;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        transform: translateY(10px);
        opacity: 0;
        animation: slideUp 0.5s ease forwards;
        animation-delay: calc(0.1s * var(--i));
        display: flex;
        /* Thêm flex để căn chỉnh nội dung */
        flex-direction: column;
        /* Sắp xếp theo cột */
        justify-content: space-between;
        /* Đẩy nút xuống dưới */
        min-height: 220px;
        /* Đảm bảo chiều cao tối thiểu để nút thẳng hàng */
        border-top: 4px solid #FFC107;
    }

    .course-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-5px);
    }

    .course-card h3 {
        margin: 0 0 12px 0;
        font-size: 1.4rem;
        background: linear-gradient(90deg, #FF8008, #FFA100);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        letter-spacing: 0.3px;
    }

    .course-card p {
        color: #555;
        line-height: 1.6;
        margin-bottom: 15px;
        font-family: 'Nunito', sans-serif;
        font-weight: 400;
        font-size: 1rem;
    }

    .course-card strong {
        font-weight: 700;
        color: #1e3c72;
    }

    .course-card ul li {
        font-family: 'Nunito', sans-serif;
        margin-bottom: 5px;
        color: #555;
    }

    .course-btn {
        display: inline-block;
        background: linear-gradient(90deg, #FF8008, #FFA100);
        color: #fff;
        padding: 10px 18px;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-weight: 700;
        text-align: center;
        align-self: flex-start;
        /* Căn nút về bên trái */
        font-family: 'Nunito', sans-serif;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        box-shadow: 0 4px 8px rgba(255, 128, 8, 0.2);
        margin-right: 10px;
    }

    .course-btn:hover {
        background: linear-gradient(90deg, #FFA100, #FF8008);
        transform: translateY(-3px) scale(1.03);
        box-shadow: 0 6px 12px rgba(255, 128, 8, 0.3);
    }

    .btn-primary {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        box-shadow: 0 4px 8px rgba(30, 60, 114, 0.3);
    }
    
    .btn-primary:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        box-shadow: 0 6px 12px rgba(30, 60, 114, 0.4);
    }
    
    .btn-start {
        background: linear-gradient(90deg, #FF8008, #FFC837);
        box-shadow: 0 4px 12px rgba(255, 128, 8, 0.3);
        padding: 12px 20px;
        font-size: 1.05rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .btn-start:hover {
        background: linear-gradient(90deg, #FFC837, #FF8008);
        box-shadow: 0 6px 15px rgba(255, 128, 8, 0.4);
        transform: translateY(-4px) scale(1.05);
    }
    
    .button-group {
        display: flex;
        gap: 12px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    /* Tiêu đề section với hiệu ứng gradient */
    .section-title {
        position: relative;
        font-family: 'Montserrat', sans-serif;
        font-size: 1.8rem;
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        margin-bottom: 25px;
        padding-bottom: 12px;
        animation: fadeInDown 1s ease;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.05);
        display: inline-block;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #FF8008, #FFC837);
        border-radius: 2px;
    }
    
    /* Tiêu đề khóa học với hiệu ứng gradient */
    .course-title {
        margin: 0 0 12px 0;
        font-size: 1.4rem;
        background: linear-gradient(90deg, #FF8008, #FFC837);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        letter-spacing: 0.3px;
        transition: all 0.3s ease;
    }
    
    .course-card:hover .course-title {
        background: linear-gradient(90deg, #FF8008, #FF4E50);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        transform: translateX(3px);
    }

    /* Phân trang */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 25px;
    }

    .pagination a {
        margin: 0 6px;
        padding: 10px 15px;
        background: linear-gradient(90deg, #FF8008, #FFA100);
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(255, 128, 8, 0.2);
    }

    .pagination a.active,
    .pagination a:hover {
        background: linear-gradient(90deg, #FFA100, #FF8008);
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 10px rgba(255, 128, 8, 0.3);
    }

    /* Grid cho khóa học tĩnh */
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }

    /* Styles for assignment section */
    .stats-container {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }
    
    .stat-card {
        flex: 1;
        min-width: 200px;
        background: white;
        border-radius: 10px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        animation: fadeIn 0.5s ease forwards;
        animation-delay: calc(0.1s * var(--i));
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .pending-icon {
        background: #FFC107;
        color: black;
    }
    
    .overdue-icon {
        background: #dc3545;
        color: white;
    }
    
    .completed-icon {
        background: #28a745;
        color: white;
    }
    
    .stat-info h3 {
        font-size: 0.9rem;
        margin-bottom: 5px;
        color: #6c757d;
    }
    
    .stat-info p {
        font-size: 1.6rem;
        font-weight: 700;
        color: #343a40;
        margin: 0;
    }
    
    .assignment-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        border-left: 4px solid #FF8008;
        position: relative;
        transition: all 0.3s ease;
        animation: slideIn 0.5s ease forwards;
        animation-delay: calc(0.1s * var(--i));
    }
    
    .assignment-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }
    
    .assignment-title {
        color: #1e3c72;
        font-size: 1.4rem;
        margin-bottom: 10px;
        font-family: 'Montserrat', sans-serif;
    }
    
    .assignment-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 0.9rem;
    }
    
    .assignment-course {
        display: inline-block;
        background: #f0f0f0;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 600;
        color: #1e3c72;
    }
    
    .assignment-due {
        color: #555;
    }
    
    .assignment-description {
        margin-bottom: 15px;
        color: #555;
    }
    
    .assignment-status {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    
    .status-pending {
        background: #FFC107;
        color: black;
    }
    
    .status-overdue {
        background: #dc3545;
        color: white;
    }
    
    .status-submitted {
        background: #28a745;
        color: white;
    }
    
    .assignment-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .btn {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        text-align: center;
        color: white;
    }
    
    .btn-primary {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
    }
    
    .btn-primary:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: linear-gradient(90deg, #FF8008, #FFA100);
    }
    
    .btn-secondary:hover {
        background: linear-gradient(90deg, #FFA100, #FF8008);
        transform: translateY(-2px);
    }
    
    .days-left {
        font-size: 0.9rem;
        color: #dc3545;
        font-weight: 700;
    }
    
    .view-all-link {
        text-align: center;
        margin: 20px 0 10px 0;
    }
    
    .no-assignments {
        background: #f8f9fa;
        padding: 40px 20px;
        text-align: center;
        border-radius: 10px;
        color: #6c757d;
        font-size: 1.2rem;
        margin: 20px 0;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        animation: fadeIn 1s ease;
    }
    
    .error-message {
        background: #fff8f8;
        border-left: 4px solid #dc3545;
    }
    
    .error-message h3 {
        color: #dc3545;
    }

    footer {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        text-align: center;
        padding: 25px 0;
        margin-top: 40px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
        font-family: 'Nunito', sans-serif;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    /* Keyframes cho hiệu ứng */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-container {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            margin-bottom: 25px;
        }

        .main-content {
            width: 100%;
        }

        nav ul {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
    }

    /* Section styling */
    .section {
        margin-bottom: 50px;
        padding-top: 20px;
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
          <li><a href="student_dashboard.php#courses" class="btn">Khoá Học Của Tôi</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout" class="btn btn-primary">Đăng xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <!-- Layout chính: sidebar + nội dung -->
  <div class="dashboard-container">
    <aside class="sidebar">
      <h2>Menu Sinh Viên</h2>
      <ul>
        <li style="--i:1"><a href="#courses" onclick="document.getElementById('courses').scrollIntoView({behavior: 'smooth'}); return false;">Khóa Học Của Tôi</a></li>
        <li style="--i:2"><a href="#static-courses" onclick="document.getElementById('static-courses').scrollIntoView({behavior: 'smooth'}); return false;">Khóa Học Tham Khảo Thêm</a></li>
        <li style="--i:3"><a href="#assignments" onclick="document.getElementById('assignments').scrollIntoView({behavior: 'smooth'}); return false;">Bài Tập Của Tôi</a></li>
        <li style="--i:4"><a href="#quizzes" onclick="document.getElementById('quizzes').scrollIntoView({behavior: 'smooth'}); return false;">Trắc Nghiệm</a></li>
        <li style="--i:5"><a href="#discussions" onclick="document.getElementById('discussions').scrollIntoView({behavior: 'smooth'}); return false;">Diễn Đàn</a></li>
      </ul>
    </aside>

    <!-- Khu vực nội dung chính -->
    <main class="main-content">
      <!-- PHẦN 1: Khóa học đăng ký (động) -->
      <section id="courses" class="section">
        <h2 class="section-title">Khóa Học Của Tôi</h2>
        <?php if ($enrollments->rowCount() > 0): ?>
          <?php 
          $i = 0;
          while ($enrollment = $enrollments->fetch(PDO::FETCH_ASSOC)): 
            $i++;
          ?>
            <div class="course-card" style="--i:<?php echo $i; ?>">
              <h3 class="course-title"><?php echo htmlspecialchars($enrollment['title']); ?></h3>
              <p><?php echo htmlspecialchars($enrollment['description']); ?></p>
              <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($enrollment['status']); ?></p>
              <div class="button-group">
                <a href="course_detail.php?course_id=<?php echo $enrollment['course_id']; ?>" class="course-btn">Xem chi tiết</a>
                <a href="view_lessons.php?course_id=<?php echo $enrollment['course_id']; ?>" class="course-btn btn-start">Học Ngay</a>
              </div>
            </div>
          <?php endwhile; ?>
          <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="?page=<?php echo $i; ?>" <?php if ($i == $page) echo 'class="active"'; ?>>
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php else: ?>
          <div class="course-card" style="--i:1">
            <h3 class="course-title">Bạn chưa đăng ký khóa học nào</h3>
            <p>Hãy khám phá các khóa học của chúng tôi và đăng ký ngay!</p>
            <a href="course_catalog.php" class="course-btn btn-start">Bắt Đầu Ngay</a>
          </div>
        <?php endif; ?>
      </section>

      <!-- PHẦN 2: 10 Khóa học tĩnh (Google Drive) -->
      <section id="static-courses" class="section">
        <h2 class="section-title">Khóa Học Tham Khảo Thêm</h2>
        <div class="courses-grid">
          <?php 
          // Mảng chứa tên của 10 khóa học (theo Google Drive)
          $staticCourses = [
              1 => "Xây dựng hệ thống bảo vệ thông tin",
              2 => "Phân tích tài chính",
              3 => "Mô hình hóa phần mềm",
              4 => "Lý thuyết mật mã",
              5 => "Lý thuyết cơ sỡ dữ liệu",
              6 => "Lập trình nhúng",
              7 => "Kinh tế vĩ mô",
              8 => "Hệ thống thiết bị di động",
              9 => "Cơ sở dữ liệu phân tán và hướng đối tượng",
              10 => "Cảm biến và kỹ thuật đo lường"
          ];
          foreach ($staticCourses as $id => $title):
          ?>
          <div class="course-card" style="--i:<?php echo $id; ?>">
            <h3 class="course-title"><?php echo $title; ?></h3>
            <p>Hiển thị video và tài liệu PDF từ Google Drive</p>
            <a href="static_course_detail.php?course_id=<?php echo $id; ?>" class="course-btn">Xem chi tiết</a>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
      
      <!-- PHẦN 3: Bài tập -->
      <section id="assignments" class="section">
        <h2 class="section-title">Bài Tập Của Tôi</h2>
        <div class="course-card" style="--i:1">
          <h3 class="course-title">Danh sách bài tập</h3>
          <p>Xem tất cả các bài tập được giao cho bạn trong các khóa học.</p>
          <?php if ($totalAssignments > 0): ?>
            <p><strong><?php echo $totalAssignments; ?> bài tập</strong> đang chờ bạn xem và hoàn thành.</p>
          <?php else: ?>
            <p>Hiện tại bạn chưa có bài tập nào.</p>
          <?php endif; ?>
          <div class="button-group">
            <a href="assignments.php" class="course-btn btn-start">Xem tất cả bài tập <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      </section>
      
      <!-- PHẦN 4: Trắc nghiệm -->
      <section id="quizzes" class="section">
        <h2 class="section-title">Trắc Nghiệm</h2>
        <div class="course-card" style="--i:1">
          <h3 class="course-title">Bài trắc nghiệm sắp tới</h3>
          <p>Dưới đây là danh sách các bài trắc nghiệm sắp diễn ra:</p>
          <ul style="margin-left: 20px; margin-bottom: 15px;">
            <li>Chưa có bài trắc nghiệm nào</li>
          </ul>
          <a href="quizzes.php" class="course-btn">Xem tất cả bài trắc nghiệm</a>
        </div>
      </section>
      
      <!-- PHẦN 5: Diễn đàn -->
      <section id="discussions" class="section">
        <h2 class="section-title">Diễn Đàn Thảo Luận</h2>
        <div class="course-card" style="--i:1">
          <h3 class="course-title">Các chủ đề thảo luận gần đây</h3>
          <p>Tham gia thảo luận với giảng viên và các sinh viên khác:</p>
          <ul style="margin-left: 20px; margin-bottom: 15px;">
            <li>Chưa có chủ đề thảo luận nào</li>
          </ul>
          <a href="forum.php" class="course-btn">Truy cập diễn đàn</a>
        </div>
      </section>
    </main>
  </div>

  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>

  <script>
    // Đảm bảo tất cả các liên kết trong sidebar hoạt động
    document.addEventListener('DOMContentLoaded', function() {
      // Lấy tất cả các liên kết trong sidebar
      const sidebarLinks = document.querySelectorAll('.sidebar a');
      
      // Thêm sự kiện click cho mỗi liên kết
      sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          const href = this.getAttribute('href');
          
          // Nếu là liên kết đến một phần trong trang
          if (href.startsWith('#')) {
            const targetId = href.substring(1);
            const targetElement = document.getElementById(targetId);
            
            // Nếu phần tử tồn tại, cuộn đến nó
            if (targetElement) {
              e.preventDefault();
              targetElement.scrollIntoView({behavior: 'smooth'});
            } 
          }
        });
      });
    });
  </script>
</body>
</html>
