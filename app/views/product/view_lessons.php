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

// Lấy ID khóa học từ tham số URL
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$user_id = $_SESSION['user_id'];

// Kiểm tra xem học viên đã đăng ký khóa học chưa
$stmt = $conn->prepare("
    SELECT e.enrollment_id 
    FROM Enrollments e 
    WHERE e.user_id = ? AND e.course_id = ? AND e.status = 'active'
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
$enrollment = $result->fetch_assoc();
$stmt->close();

if (!$enrollment) {
    $_SESSION['error'] = "Bạn chưa đăng ký khóa học này hoặc đăng ký chưa được kích hoạt.";
    header("Location: student_dashboard.php");
    exit();
}

// Lấy thông tin khóa học
$stmt = $conn->prepare("
    SELECT c.title, c.description 
    FROM Courses c 
    WHERE c.course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    $_SESSION['error'] = "Khóa học không tồn tại.";
    header("Location: student_dashboard.php");
    exit();
}

// Khởi tạo Lesson model để lấy danh sách bài học
require_once '../../models/Lesson.php';
$lessonModel = new Lesson($conn);
$lessons = $lessonModel->getByCourse($course_id);

// Lấy ID bài học từ URL nếu có, mặc định là bài học đầu tiên
$selectedLessonId = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
$selectedLesson = null;

// Nếu không có lesson_id trong URL hoặc lesson_id không hợp lệ, chọn bài học đầu tiên
if ($selectedLessonId == 0 && !empty($lessons)) {
    $selectedLesson = $lessons[0];
    $selectedLessonId = $selectedLesson['lesson_id'];
} else {
    // Tìm bài học đã chọn trong danh sách
    foreach ($lessons as $lesson) {
        if ($lesson['lesson_id'] == $selectedLessonId) {
            $selectedLesson = $lesson;
            break;
        }
    }
}

// Tìm bài học tiếp theo và bài học trước đó
$nextLesson = null;
$prevLesson = null;

if (!empty($lessons)) {
    for ($i = 0; $i < count($lessons); $i++) {
        if ($lessons[$i]['lesson_id'] == $selectedLessonId) {
            if ($i > 0) {
                $prevLesson = $lessons[$i - 1];
            }
            if ($i < count($lessons) - 1) {
                $nextLesson = $lessons[$i + 1];
            }
            break;
        }
    }
}

// Kiểm tra xem bảng AssignmentSubmissions có tồn tại
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'AssignmentSubmissions'");
if ($checkTable && $checkTable->num_rows > 0) {
    $tableExists = true;
}

// Lấy danh sách bài tập liên quan đến bài học này
if ($selectedLessonId > 0) {
    $assignments_query = "
        SELECT a.assignment_id, a.title, a.description, a.due_date, a.max_points
        FROM Assignments a
        WHERE a.course_id = ? AND a.lesson_id = ?
        ORDER BY a.due_date ASC
    ";
    $assignments_stmt = $conn->prepare($assignments_query);
    $assignments_stmt->bind_param("ii", $course_id, $selectedLessonId);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Bài Học</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        /* Layout */
        .lesson-container {
            display: flex;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
        }
        
        /* Sidebar danh sách bài học */
        .lessons-sidebar {
            width: 300px;
            background-color: #f0f4f8;
            border-right: 1px solid #ddd;
            padding: 20px 0;
            overflow-y: auto;
            height: calc(100vh - 60px);
            position: fixed;
            left: 0;
            top: 60px;
        }
        
        .lesson-list {
            list-style: none;
            padding: 0;
        }
        
        .lesson-list-item {
            padding: 12px 20px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .lesson-list-item:hover {
            background-color: #e3e8ef;
        }
        
        .lesson-list-item.active {
            background-color: #4aa1ff;
            color: white;
        }
        
        .lesson-list-item .lesson-title {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .lesson-list-item .lesson-duration {
            font-size: 12px;
            color: #666;
        }
        
        .lesson-list-item.active .lesson-duration {
            color: rgba(255, 255, 255, 0.8);
        }
        
        /* Nội dung bài học */
        .lesson-content {
            flex: 1;
            padding: 30px;
            margin-left: 300px;
        }
        
        .lesson-header {
            margin-bottom: 30px;
        }
        
        .lesson-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .lesson-info {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .lesson-info span {
            margin-right: 20px;
        }
        
        .lesson-info i {
            margin-right: 5px;
        }
        
        .lesson-text {
            line-height: 1.7;
            color: #333;
            font-size: 16px;
        }
        
        /* Navigation */
        .lesson-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .nav-button {
            padding: 10px 15px;
            background-color: #4aa1ff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        .nav-button:hover {
            background-color: #3a8fd8;
        }
        
        .nav-button.disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2c3e50;
            color: white;
            padding: 0 20px;
            height: 60px;
            z-index: 1000;
        }
        
        .back-to-course {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-to-course:hover {
            color: #3498db;
        }
        
        .course-title {
            font-weight: 600;
            font-size: 18px;
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
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Assignments section */
        .assignments-section {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 30px;
        }
        
        .assignments-section h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .assignment-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .assignment-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .assignment-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .assignment-title {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }
        
        .assignment-body {
            padding: 15px;
        }
        
        .assignment-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .assignment-meta {
            font-size: 13px;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .assignment-footer {
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .assignment-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #ffc107;
            color: #212529;
        }
        
        .status-overdue {
            background: #dc3545;
            color: white;
        }
        
        .status-submitted {
            background: #28a745;
            color: white;
        }
        
        .status-graded {
            background: #17a2b8;
            color: white;
        }
        
        .assignment-btn {
            display: inline-block;
            padding: 6px 12px;
            background: #4aa1ff;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .assignment-btn:hover {
            background: #3a8fd8;
        }
        
        .no-assignments {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="student_dashboard.php" class="back-to-course">
            <i class="fas fa-arrow-left"></i> Quay lại Dashboard
        </a>
        <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
        <div class="user-info">
            <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="<?php echo BASE_URL; ?>auth/logout" class="logout-btn">Đăng xuất</a>
        </div>
    </header>

    <!-- Debug Info -->
    <?php if (isset($_GET['debug'])): ?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border-radius: 5px;">
        <h3>Debug Info:</h3>
        <p>Course ID: <?php echo $course_id; ?></p>
        <p>Selected Lesson ID: <?php echo $selectedLessonId; ?></p>
        <p>Total Lessons: <?php echo count($lessons); ?></p>
        <?php if ($selectedLesson): ?>
            <p>Selected Lesson Title: <?php echo htmlspecialchars($selectedLesson['title']); ?></p>
        <?php else: ?>
            <p>No lesson selected or lesson not found.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Main container -->
    <div class="lesson-container">
        <!-- Sidebar danh sách bài học -->
        <div class="lessons-sidebar">
            <h3 class="lessons-heading">Danh Sách Bài Học</h3>
            <?php if (empty($lessons)): ?>
                <div class="no-lessons">
                    <p>Khóa học này chưa có bài học nào.</p>
                </div>
            <?php else: ?>
                <ul class="lesson-list">
                    <?php foreach ($lessons as $lesson): ?>
                        <li class="lesson-list-item <?php echo ($lesson['lesson_id'] == $selectedLessonId) ? 'active' : ''; ?>">
                            <a href="view_lessons.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson['lesson_id']; ?>" style="display: block; text-decoration: none; color: inherit;">
                                <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                <div class="lesson-duration"><?php echo $lesson['duration']; ?> phút</div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Nội dung bài học -->
        <div class="lesson-content">
            <?php if ($selectedLesson): ?>
                <div class="lesson-header">
                    <h1 class="lesson-title"><?php echo htmlspecialchars($selectedLesson['title']); ?></h1>
                    <div class="lesson-info">
                        <span><i class="fas fa-clock"></i> Thời lượng: <?php echo $selectedLesson['duration']; ?> phút</span>
                        <span><i class="fas fa-sort"></i> Bài học #<?php echo $selectedLesson['order_index'] + 1; ?></span>
                    </div>
                </div>

                <div class="lesson-text">
                    <?php echo nl2br(htmlspecialchars($selectedLesson['content'])); ?>
                </div>
                
                <!-- Phần bài tập của bài học -->
                <div class="assignments-section">
                    <h2><i class="fas fa-tasks"></i> Bài Tập</h2>
                    
                    <?php if (isset($assignments) && $assignments->num_rows > 0): ?>
                        <div class="assignment-list">
                            <?php while ($assignment = $assignments->fetch_assoc()): 
                                // Kiểm tra trạng thái nộp bài
                                $is_submitted = false;
                                $is_graded = false;
                                $grade = null;
                                
                                // Kiểm tra trạng thái nộp bài
                                if ($tableExists) {
                                    $submission_query = $conn->prepare("
                                        SELECT submission_id, grade 
                                        FROM AssignmentSubmissions 
                                        WHERE assignment_id = ? AND user_id = ?
                                    ");
                                    $submission_query->bind_param("ii", $assignment['assignment_id'], $user_id);
                                    $submission_query->execute();
                                    $submission_result = $submission_query->get_result();
                                    
                                    if ($submission_result->num_rows > 0) {
                                        $submission = $submission_result->fetch_assoc();
                                        $is_submitted = true;
                                        if ($submission['grade'] !== null) {
                                            $is_graded = true;
                                            $grade = $submission['grade'];
                                        }
                                    }
                                    $submission_query->close();
                                }
                                
                                // Kiểm tra nếu quá hạn
                                $is_overdue = strtotime($assignment['due_date']) < time();
                                
                                // Xác định trạng thái để hiển thị
                                $status_class = 'pending';
                                $status_text = 'Chưa nộp';
                                
                                if ($is_graded) {
                                    $status_class = 'graded';
                                    $status_text = 'Đã chấm: ' . $grade . '/' . $assignment['max_points'];
                                } elseif ($is_submitted) {
                                    $status_class = 'submitted';
                                    $status_text = 'Đã nộp';
                                } elseif ($is_overdue) {
                                    $status_class = 'overdue';
                                    $status_text = 'Quá hạn';
                                }
                            ?>
                                <div class="assignment-card">
                                    <div class="assignment-header">
                                        <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    </div>
                                    
                                    <div class="assignment-body">
                                        <div class="assignment-description">
                                            <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                                        </div>
                                        
                                        <div class="assignment-meta">
                                            <div>
                                                <i class="fas fa-calendar"></i> Hạn nộp: <?php echo date('d/m/Y', strtotime($assignment['due_date'])); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-star"></i> Điểm tối đa: <?php echo $assignment['max_points']; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="assignment-footer">
                                        <span class="assignment-status status-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <a href="submit_assignment.php?id=<?php echo $assignment['assignment_id']; ?>" class="assignment-btn">
                                            <?php echo $is_submitted ? 'Xem bài nộp' : 'Nộp bài'; ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-assignments">
                            <p>Không có bài tập nào cho bài học này.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="lesson-navigation">
                    <?php if ($prevLesson): ?>
                        <a href="view_lessons.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $prevLesson['lesson_id']; ?>" class="nav-button">
                            <i class="fas fa-arrow-left"></i> Bài trước
                        </a>
                    <?php else: ?>
                        <div class="nav-button disabled">
                            <i class="fas fa-arrow-left"></i> Bài trước
                        </div>
                    <?php endif; ?>

                    <?php if ($nextLesson): ?>
                        <a href="view_lessons.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $nextLesson['lesson_id']; ?>" class="nav-button">
                            Bài tiếp theo <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <a href="student_dashboard.php" class="nav-button">
                            Hoàn thành <i class="fas fa-check"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-lesson-selected">
                    <h2>Không có bài học nào được chọn hoặc bài học không tồn tại.</h2>
                    <p>Vui lòng chọn một bài học từ danh sách bài học bên trái.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Script để hiển thị trạng thái nộp bài
        document.addEventListener('DOMContentLoaded', function() {
            // Đánh dấu bài học hiện tại trong sidebar
            const currentLesson = document.querySelector('.lesson-list-item.active');
            if (currentLesson) {
                currentLesson.scrollIntoView({ block: 'center' });
            }
        });
    </script>
</body>
</html> 