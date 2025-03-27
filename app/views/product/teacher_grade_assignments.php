<?php
// Teacher interface for grading assignments
define('BASE_URL', '/WebCourses/');
// Direct database connection to avoid include path issues
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

// Connect to database with error handling
try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die('<div style="color:red; padding:20px; font-family:Arial; background:#f8d7da; border-radius:5px; margin:20px;">
         <h2>Database Connection Error</h2>
         <p>' . $e->getMessage() . '</p>
         <p>Please make sure the MySQL service is running in XAMPP Control Panel and the "online_courses" database exists.</p>
         </div>');
}

session_start();

// Check if user is logged in as teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò giảng viên để chấm điểm bài tập.";
    header("Location: " . BASE_URL . "app/views/product/home.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// Check if AssignmentSubmissions table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'AssignmentSubmissions'");
if (!$checkTable || $checkTable->num_rows == 0) {
    $error_message = "Bảng dữ liệu bài nộp chưa được tạo. Vui lòng liên hệ quản trị viên.";
}

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = intval($_POST['submission_id']);
    $grade = intval($_POST['grade']);
    $feedback = trim($_POST['feedback']);
    $max_points = intval($_POST['max_points']);
    
    // Validate grade
    if ($grade < 0 || $grade > $max_points) {
        $error_message = "Điểm số phải nằm trong khoảng từ 0 đến $max_points.";
    } else {
        // Update the submission with grade and feedback
        $stmt = $conn->prepare("
            UPDATE AssignmentSubmissions 
            SET grade = ?, feedback = ?
            WHERE submission_id = ?
        ");
        $stmt->bind_param("isi", $grade, $feedback, $submission_id);
        
        if ($stmt->execute()) {
            $success_message = "Bài tập đã được chấm điểm thành công!";
        } else {
            $error_message = "Có lỗi xảy ra khi lưu điểm: " . $conn->error;
        }
    }
}

// Get submission details if viewing a specific submission
$submission = null;
$assignment = null;
$student = null;

if (isset($_GET['submission_id']) && is_numeric($_GET['submission_id'])) {
    $submission_id = intval($_GET['submission_id']);
    
    // Get submission details with related information
    $stmt = $conn->prepare("
        SELECT s.*, a.title as assignment_title, a.description as assignment_description, 
               a.due_date, a.max_points, c.title as course_title,
               u.fullname as student_name, u.email as student_email
        FROM AssignmentSubmissions s
        JOIN Assignments a ON s.assignment_id = a.assignment_id
        JOIN Users u ON s.user_id = u.user_id
        JOIN Courses c ON a.course_id = c.course_id
        WHERE s.submission_id = ? AND c.teacher_id = ?
    ");
    $stmt->bind_param("ii", $submission_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $submission = $result->fetch_assoc();
    } else {
        $error_message = "Không tìm thấy bài nộp hoặc bạn không có quyền chấm điểm bài này.";
    }
}

// Get list of submissions based on the active tab
$submissions = [];
$where_clause = "c.teacher_id = ?";
$params = [$teacher_id];
$types = "i";

if ($active_tab == 'pending') {
    $where_clause .= " AND s.grade IS NULL";
} elseif ($active_tab == 'graded') {
    $where_clause .= " AND s.grade IS NOT NULL";
}

if (isset($_GET['course_id']) && $_GET['course_id'] > 0) {
    $course_id = intval($_GET['course_id']);
    $where_clause .= " AND c.course_id = ?";
    $params[] = $course_id;
    $types .= "i";
}

$sql = "
    SELECT s.submission_id, s.submission_date, s.grade, 
           a.title as assignment_title, a.max_points,
           c.title as course_title, c.course_id,
           u.fullname as student_name
    FROM AssignmentSubmissions s
    JOIN Assignments a ON s.assignment_id = a.assignment_id
    JOIN Courses c ON a.course_id = c.course_id
    JOIN Users u ON s.user_id = u.user_id
    WHERE $where_clause
    ORDER BY s.submission_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
}

// Get teacher's courses for filter
$courses_stmt = $conn->prepare("
    SELECT course_id, title 
    FROM Courses 
    WHERE teacher_id = ?
    ORDER BY title
");
$courses_stmt->bind_param("i", $teacher_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();
$courses = [];

while ($course = $courses_result->fetch_assoc()) {
    $courses[] = $course;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chấm Điểm Bài Tập - Học Tập Trực Tuyến</title>
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
            background-color: #f8f9fa;
            line-height: 1.6;
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

        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Two column layout */
        .dashboard-container {
            display: flex;
            gap: 30px;
        }

        .sidebar {
            width: 280px;
            flex-shrink: 0;
        }

        .main-content {
            flex: 1;
        }

        /* Sidebar */
        .sidebar-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-title {
            font-size: 1.2rem;
            color: #1e3c72;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FFC107;
            font-family: 'Montserrat', sans-serif;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu li a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            border-radius: 6px;
            transition: all 0.3s;
            font-weight: 600;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li.active a {
            background: #f0f0f0;
            color: #1e3c72;
            transform: translateX(5px);
        }

        .sidebar-menu li.active a {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
        }

        /* Course filter */
        .filter-form {
            margin-top: 15px;
        }

        .filter-form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .filter-form button {
            width: 100%;
            padding: 10px;
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filter-form button:hover {
            background: linear-gradient(90deg, #2a5298, #1e3c72);
            transform: translateY(-2px);
        }

        /* Main content */
        .page-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #1e3c72;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 3px solid #FFC107;
        }

        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Submission list */
        .submission-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .submission-table {
            width: 100%;
            border-collapse: collapse;
        }

        .submission-table th,
        .submission-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .submission-table th {
            background: #f8f9fa;
            color: #1e3c72;
            font-weight: 700;
        }

        .submission-table tr:hover {
            background: #f8f9fa;
        }

        .submission-table tr:last-child td {
            border-bottom: none;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2a5298, #1e3c72);
            transform: translateY(-2px);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-graded {
            background: #d1e7dd;
            color: #0f5132;
        }

        /* Submission detail */
        .submission-detail {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-title {
            color: #1e3c72;
            font-size: 1.5rem;
            margin-bottom: 5px;
            font-family: 'Montserrat', sans-serif;
        }

        .detail-meta {
            color: #666;
            font-size: 0.9rem;
        }

        .detail-meta span {
            margin-right: 10px;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section h3 {
            color: #1e3c72;
            font-size: 1.3rem;
            margin-bottom: 15px;
            font-family: 'Montserrat', sans-serif;
        }

        .detail-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .submission-text {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .submission-file {
            display: inline-block;
            padding: 8px 15px;
            background: #e9ecef;
            border-radius: 6px;
            color: #1e3c72;
            text-decoration: none;
            margin-top: 10px;
        }

        .submission-file:hover {
            background: #dee2e6;
        }

        /* Grading form */
        .grading-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e3c72;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Nunito', sans-serif;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        button.btn {
            border: none;
            cursor: pointer;
            padding: 10px 20px;
        }

        /* No submissions message */
        .no-submissions {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-submissions i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #adb5bd;
        }

        .no-submissions p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: #fff;
            text-align: center;
            padding: 25px 0;
            margin-top: 40px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .submission-table {
                display: block;
                overflow-x: auto;
            }
            
            nav ul {
                flex-direction: column;
                align-items: center;
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
                    <li><a href="teacher_dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Chấm Điểm Bài Tập</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-container">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Bộ Lọc</h3>
                    <ul class="sidebar-menu">
                        <li class="<?php echo $active_tab === 'all' ? 'active' : ''; ?>">
                            <a href="teacher_grade_assignments.php?tab=all<?php echo isset($_GET['course_id']) ? '&course_id=' . $_GET['course_id'] : ''; ?>">
                                <i class="fas fa-list"></i> Tất cả bài nộp
                            </a>
                        </li>
                        <li class="<?php echo $active_tab === 'pending' ? 'active' : ''; ?>">
                            <a href="teacher_grade_assignments.php?tab=pending<?php echo isset($_GET['course_id']) ? '&course_id=' . $_GET['course_id'] : ''; ?>">
                                <i class="fas fa-hourglass-half"></i> Chưa chấm điểm
                            </a>
                        </li>
                        <li class="<?php echo $active_tab === 'graded' ? 'active' : ''; ?>">
                            <a href="teacher_grade_assignments.php?tab=graded<?php echo isset($_GET['course_id']) ? '&course_id=' . $_GET['course_id'] : ''; ?>">
                                <i class="fas fa-check-circle"></i> Đã chấm điểm
                            </a>
                        </li>
                    </ul>
                    
                    <?php if (count($courses) > 0): ?>
                    <div class="filter-form">
                        <form action="" method="GET">
                            <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                            <div class="form-group">
                                <label for="course_id">Lọc theo khóa học:</label>
                                <select name="course_id" id="course_id">
                                    <option value="0">Tất cả khóa học</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn">Áp dụng</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Thống Kê</h3>
                    <p>Tổng số bài nộp: <strong><?php echo count($submissions); ?></strong></p>
                    <p>Chưa chấm điểm: <strong>
                        <?php 
                            $pending_count = 0;
                            foreach ($submissions as $s) {
                                if ($s['grade'] === null) {
                                    $pending_count++;
                                }
                            }
                            echo $pending_count;
                        ?>
                    </strong></p>
                    <p>Đã chấm điểm: <strong><?php echo count($submissions) - $pending_count; ?></strong></p>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="main-content">
                <?php if (isset($submission)): ?>
                    <!-- Submission detail view -->
                    <div class="submission-detail">
                        <div class="detail-header">
                            <div>
                                <h2 class="detail-title"><?php echo htmlspecialchars($submission['assignment_title']); ?></h2>
                                <div class="detail-meta">
                                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($submission['course_title']); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($submission['student_name']); ?></span>
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($submission['student_email']); ?></span>
                                </div>
                            </div>
                            <div>
                                <a href="teacher_grade_assignments.php?tab=<?php echo $active_tab; ?><?php echo isset($_GET['course_id']) ? '&course_id=' . $_GET['course_id'] : ''; ?>" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Quay lại danh sách
                                </a>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3>Thông tin bài tập</h3>
                            <div class="detail-card">
                                <p><strong>Mô tả:</strong> <?php echo nl2br(htmlspecialchars($submission['assignment_description'])); ?></p>
                                <p><strong>Hạn nộp:</strong> <?php echo date('d/m/Y H:i', strtotime($submission['due_date'])); ?></p>
                                <p><strong>Điểm tối đa:</strong> <?php echo $submission['max_points']; ?> điểm</p>
                                <p><strong>Thời gian nộp:</strong> <?php echo date('d/m/Y H:i', strtotime($submission['submission_date'])); ?></p>
                                <?php if (strtotime($submission['due_date']) < strtotime($submission['submission_date'])): ?>
                                    <p><strong style="color: #dc3545;">Nộp trễ: </strong> 
                                    <?php 
                                        $due = new DateTime($submission['due_date']);
                                        $submitted = new DateTime($submission['submission_date']);
                                        $diff = $due->diff($submitted);
                                        echo $diff->days . ' ngày ' . $diff->h . ' giờ ' . $diff->i . ' phút';
                                    ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3>Nội dung bài nộp</h3>
                            <?php if (!empty($submission['submission_text'])): ?>
                                <div class="submission-text">
                                    <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                </div>
                            <?php else: ?>
                                <p><em>Không có nội dung văn bản.</em></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($submission['file_path'])): ?>
                                <div style="margin-top: 15px;">
                                    <strong>File đính kèm:</strong>
                                    <a href="<?php echo BASE_URL . $submission['file_path']; ?>" class="submission-file" target="_blank">
                                        <i class="fas fa-file"></i> Tải xuống file
                                    </a>
                                </div>
                            <?php else: ?>
                                <p><em>Không có file đính kèm.</em></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="detail-section">
                            <h3>Chấm điểm</h3>
                            <form method="post" action="" class="grading-form">
                                <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                <input type="hidden" name="max_points" value="<?php echo $submission['max_points']; ?>">
                                
                                <div class="form-group">
                                    <label for="grade">Điểm số (tối đa <?php echo $submission['max_points']; ?> điểm):</label>
                                    <input type="number" id="grade" name="grade" min="0" max="<?php echo $submission['max_points']; ?>" value="<?php echo $submission['grade'] !== null ? $submission['grade'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="feedback">Nhận xét:</label>
                                    <textarea id="feedback" name="feedback"><?php echo $submission['feedback'] ?? ''; ?></textarea>
                                </div>
                                
                                <div class="button-group">
                                    <button type="submit" name="grade_submission" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu điểm
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Submissions list view -->
                    <div class="submission-list">
                        <?php if (count($submissions) > 0): ?>
                            <table class="submission-table">
                                <thead>
                                    <tr>
                                        <th>Sinh viên</th>
                                        <th>Bài tập</th>
                                        <th>Khóa học</th>
                                        <th>Thời gian nộp</th>
                                        <th>Trạng thái</th>
                                        <th>Tác vụ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['assignment_title']); ?></td>
                                            <td><?php echo htmlspecialchars($item['course_title']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($item['submission_date'])); ?></td>
                                            <td>
                                                <?php if ($item['grade'] === null): ?>
                                                    <span class="status-badge badge-pending">Chưa chấm</span>
                                                <?php else: ?>
                                                    <span class="status-badge badge-graded">
                                                        <?php echo $item['grade']; ?>/<?php echo $item['max_points']; ?> điểm
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="teacher_grade_assignments.php?tab=<?php echo $active_tab; ?>&submission_id=<?php echo $item['submission_id']; ?><?php echo isset($_GET['course_id']) ? '&course_id=' . $_GET['course_id'] : ''; ?>" class="btn btn-primary">
                                                        <?php echo $item['grade'] === null ? 'Chấm điểm' : 'Chỉnh sửa'; ?>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-submissions">
                                <i class="fas fa-inbox"></i>
                                <p>Không có bài nộp nào trong danh sách này.</p>
                                <?php if ($active_tab !== 'all' || isset($_GET['course_id'])): ?>
                                    <a href="teacher_grade_assignments.php" class="btn btn-primary">Xem tất cả bài nộp</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
    </footer>
</body>
</html> 