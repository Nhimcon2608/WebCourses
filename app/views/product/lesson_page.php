<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò học viên để xem bài học.";
    header("Location: " . BASE_URL . "app/views/product/home.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách khóa học đã đăng ký
$stmt = $conn->prepare("
    SELECT c.course_id, c.title as course_title, c.image_url, c.description, 
    (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.course_id) as lesson_count
    FROM Enrollments e 
    JOIN Courses c ON e.course_id = c.course_id 
    WHERE e.user_id = ? AND e.status = 'active'
    ORDER BY e.enrollment_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$courses = $stmt->get_result();
$courseCount = $courses->num_rows;

// Lấy thông tin bài học gần đây nếu có
$recentLessons = [];
if ($courseCount > 0) {
    // Lấy 5 bài học gần đây nhất từ các khóa học đã đăng ký
    $stmt = $conn->prepare("
        SELECT l.lesson_id, l.title, l.content, l.duration, l.order_index,
        c.course_id, c.title as course_title, c.image_url
        FROM lessons l
        JOIN courses c ON l.course_id = c.course_id
        JOIN enrollments e ON c.course_id = e.course_id
        WHERE e.user_id = ? AND e.status = 'active'
        ORDER BY l.created_at DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentLessons[] = $row;
    }
}

// Lấy tổng số bài học
$totalLessonsStmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM lessons l
    JOIN courses c ON l.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.user_id = ? AND e.status = 'active'
");
$totalLessonsStmt->bind_param("i", $user_id);
$totalLessonsStmt->execute();
$totalLessons = $totalLessonsStmt->get_result()->fetch_assoc()['total'];

// Lấy tổng thời lượng học tập
$totalDurationStmt = $conn->prepare("
    SELECT SUM(l.duration) as total_duration
    FROM lessons l
    JOIN courses c ON l.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.user_id = ? AND e.status = 'active'
");
$totalDurationStmt->bind_param("i", $user_id);
$totalDurationStmt->execute();
$result = $totalDurationStmt->get_result()->fetch_assoc();
$totalDuration = $result['total_duration'] ? $result['total_duration'] : 0;

// Số khóa học chưa hoàn thành
$incompleteCoursesStmt = $conn->prepare("
    SELECT COUNT(*) as incomplete
    FROM enrollments e
    WHERE e.user_id = ? AND e.status = 'active' AND e.progress < 100
");
$incompleteCoursesStmt->bind_param("i", $user_id);
$incompleteCoursesStmt->execute();
$incompleteCourses = $incompleteCoursesStmt->get_result()->fetch_assoc()['incomplete'];

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trung tâm Bài Học - Học Tập Trực Tuyến</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --dark: #1b263b;
            --light: #f8f9fa;
            --gray: #e9ecef;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
            --border-radius: 10px;
            --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f7fe;
            color: #333;
            line-height: 1.6;
        }

        /* Header styles */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: 0.5px;
        }

        .nav-links {
            display: flex;
            gap: 25px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 5px 0;
            position: relative;
        }

        .nav-links a:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background-color: white;
            transition: var(--transition);
        }

        .nav-links a:hover:after {
            width: 100%;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .username {
            font-weight: 500;
        }

        .logout-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Main container */
        .container {
            margin-top: 80px;
            padding: 30px 50px;
            display: flex;
            gap: 30px;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 100px;
            height: calc(100vh - 130px);
            padding: 25px;
        }

        .sidebar-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }

        .sidebar-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 8px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .sidebar-menu a:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .sidebar-menu a.active {
            background-color: var(--primary);
            color: white;
        }

        .sidebar-menu i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        /* Main content */
        .main-content {
            flex: 1;
        }

        /* Dashboard cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stat-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .icon-blue {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        .icon-purple {
            background-color: rgba(114, 9, 183, 0.15);
            color: var(--secondary);
        }

        .icon-green {
            background-color: rgba(76, 175, 80, 0.15);
            color: var(--success);
        }

        .icon-orange {
            background-color: rgba(255, 152, 0, 0.15);
            color: var(--warning);
        }

        .stat-value {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Section titles */
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
            margin: 30px 0 20px;
            color: var(--dark);
        }

        /* Recent lessons */
        .recent-lessons {
            margin-bottom: 30px;
        }

        .lesson-item {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
        }

        .lesson-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .lesson-image {
            width: 120px;
            height: 80px;
            border-radius: var(--border-radius);
            overflow: hidden;
            background-color: var(--gray);
            flex-shrink: 0;
        }

        .lesson-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .lesson-content {
            flex: 1;
        }

        .lesson-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 5px;
        }

        .lesson-course {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .lesson-duration {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 0.85rem;
        }

        .lesson-duration i {
            margin-right: 5px;
        }

        .lesson-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .lesson-excerpt {
            color: #666;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        /* Courses section */
        .courses-section {
            margin-bottom: 30px;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .course-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .course-thumbnail {
            height: 180px;
            overflow: hidden;
        }

        .course-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .course-card:hover .course-thumbnail img {
            transform: scale(1.05);
        }

        .course-info {
            padding: 20px;
        }

        .course-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .course-stats {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            background-color: #f9f9f9;
            color: #666;
            font-size: 0.85rem;
        }

        .course-stat {
            display: flex;
            align-items: center;
        }

        .course-stat i {
            margin-right: 5px;
        }

        .course-action {
            display: block;
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .course-action:hover {
            background-color: var(--primary-dark);
        }

        .no-courses {
            text-align: center;
            background-color: white;
            padding: 40px 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .no-courses h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .no-courses p {
            color: #666;
            margin-bottom: 20px;
        }

        .explore-btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .explore-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            background-color: var(--dark);
            color: white;
            padding: 50px 30px 20px;
            margin-top: 50px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .footer-logo {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .footer-section h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-section h3:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 30px;
            height: 2px;
            background-color: var(--primary);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            transition: var(--transition);
        }

        .social-links a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #999;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
                padding: 20px;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                margin-bottom: 30px;
                position: static;
            }
            
            .dashboard-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 15px;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .container {
                margin-top: 130px;
            }
            
            .courses-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .lesson-item {
                flex-direction: column;
            }
            
            .lesson-image {
                width: 100%;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">Học Tập Trực Tuyến</div>
        <nav class="nav-links">
            <a href="student_dashboard.php">Trang Chủ</a>
            <a href="lesson_page.php" class="active">Bài Học</a>
            <a href="assignment_page.php">Bài Tập</a>
            <a href="quiz_page.php">Trắc Nghiệm</a>
            <a href="discussion_page.php">Diễn Đàn</a>
        </nav>
        <div class="user-info">
            <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="<?php echo BASE_URL; ?>auth/logout" class="logout-btn">Đăng xuất</a>
        </div>
    </header>

    <!-- Main container -->
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2 class="sidebar-title">Bài Học</h2>
            <ul class="sidebar-menu">
                <li><a href="lesson_page.php" class="active"><i class="fas fa-home"></i> Tổng Quan</a></li>
                <li><a href="#"><i class="fas fa-book"></i> Nội Dung Bài Học</a></li>
                <li><a href="#"><i class="fas fa-bookmark"></i> Đã Lưu</a></li>
                <li><a href="#"><i class="fas fa-history"></i> Lịch Sử Học Tập</a></li>
                <li><a href="#"><i class="fas fa-certificate"></i> Chứng Chỉ</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Tiến Độ</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Cài Đặt</a></li>
            </ul>
        </aside>

        <!-- Main content -->
        <main class="main-content">
            <!-- Dashboard cards -->
            <div class="dashboard-cards">
                <div class="card stat-card">
                    <div class="stat-icon icon-blue">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-value"><?php echo $courseCount; ?></div>
                    <div class="stat-label">Khóa Học Đăng Ký</div>
                </div>

                <div class="card stat-card">
                    <div class="stat-icon icon-purple">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalLessons; ?></div>
                    <div class="stat-label">Tổng Số Bài Học</div>
                </div>

                <div class="card stat-card">
                    <div class="stat-icon icon-green">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalDuration; ?></div>
                    <div class="stat-label">Tổng Phút Học Tập</div>
                </div>

                <div class="card stat-card">
                    <div class="stat-icon icon-orange">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-value"><?php echo $incompleteCourses; ?></div>
                    <div class="stat-label">Khóa Học Chưa Hoàn Thành</div>
                </div>
            </div>

            <!-- Recent lessons -->
            <h2 class="section-title">Bài Học Gần Đây</h2>
            <div class="recent-lessons">
                <?php if (!empty($recentLessons)): ?>
                    <?php foreach ($recentLessons as $lesson): ?>
                        <a href="view_lessons.php?course_id=<?php echo $lesson['course_id']; ?>&lesson_id=<?php echo $lesson['lesson_id']; ?>" class="lesson-item">
                            <div class="lesson-image">
                                <img src="<?php echo !empty($lesson['image_url']) ? $lesson['image_url'] : BASE_URL . 'public/images/course-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($lesson['title']); ?>">
                            </div>
                            <div class="lesson-content">
                                <div class="lesson-info">
                                    <span class="lesson-course"><?php echo htmlspecialchars($lesson['course_title']); ?></span>
                                    <span class="lesson-duration"><i class="fas fa-clock"></i> <?php echo $lesson['duration']; ?> phút</span>
                                </div>
                                <h3 class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></h3>
                                <p class="lesson-excerpt"><?php echo substr(strip_tags($lesson['content']), 0, 150) . '...'; ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card">
                        <p>Chưa có bài học nào gần đây. Bắt đầu học ngay bằng cách chọn một khóa học bên dưới!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Courses -->
            <h2 class="section-title">Khóa Học Của Bạn</h2>
            <div class="courses-section">
                <?php if ($courses->num_rows > 0): ?>
                    <div class="courses-grid">
                        <?php 
                        $courses->data_seek(0); // Reset cursor to start of result set
                        while ($course = $courses->fetch_assoc()): 
                        ?>
                            <div class="course-card">
                                <div class="course-thumbnail">
                                    <img src="<?php echo !empty($course['image_url']) ? $course['image_url'] : BASE_URL . 'public/images/course-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($course['course_title']); ?>">
                                </div>
                                <div class="course-info">
                                    <h3 class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></h3>
                                    <p><?php echo substr(htmlspecialchars($course['description']), 0, 100) . '...'; ?></p>
                                </div>
                                <div class="course-stats">
                                    <div class="course-stat">
                                        <i class="fas fa-book"></i>
                                        <?php echo $course['lesson_count']; ?> bài học
                                    </div>
                                    <div class="course-stat">
                                        <i class="fas fa-clock"></i>
                                        Đang học
                                    </div>
                                </div>
                                <a href="view_lessons.php?course_id=<?php echo $course['course_id']; ?>" class="course-action">Tiếp Tục Học</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-courses">
                        <h3>Bạn chưa đăng ký khóa học nào</h3>
                        <p>Hãy khám phá các khóa học của chúng tôi và bắt đầu hành trình học tập của bạn ngay hôm nay!</p>
                        <a href="home.php" class="explore-btn">Khám Phá Khóa Học</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <div class="footer-logo">Học Tập Trực Tuyến</div>
                <p>Nền tảng học tập trực tuyến hàng đầu Việt Nam, cung cấp các khóa học chất lượng cao với đội ngũ giảng viên uy tín.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Liên Kết Nhanh</h3>
                <ul class="footer-links">
                    <li><a href="#">Trang Chủ</a></li>
                    <li><a href="#">Khóa Học</a></li>
                    <li><a href="#">Giảng Viên</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Về Chúng Tôi</a></li>
                    <li><a href="#">Liên Hệ</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Hỗ Trợ</h3>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Điều Khoản Sử Dụng</a></li>
                    <li><a href="#">Chính Sách Bảo Mật</a></li>
                    <li><a href="#">Hỗ Trợ Kỹ Thuật</a></li>
                    <li><a href="#">Phản Hồi</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Liên Hệ</h3>
                <p><i class="fas fa-map-marker-alt"></i> 123 Đường ABC, Quận XYZ, TP. HCM</p>
                <p><i class="fas fa-phone"></i> +84 123 456 789</p>
                <p><i class="fas fa-envelope"></i> info@hoctap.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Học Tập Trực Tuyến. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
</body>
</html> 