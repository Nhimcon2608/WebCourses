<?php
// Debug information (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Don't redefine constants if already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WebCourses/');
}
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 3));
}

// Output debug info in HTML comments for troubleshooting
echo "<!-- ROOT_DIR: " . ROOT_DIR . " -->\n";
echo "<!-- Current file: " . __FILE__ . " -->\n";
echo "<!-- Connect path: " . ROOT_DIR . '/app/config/connect.php' . " -->\n";

// Database connection with error handling
try {
    include ROOT_DIR . '/app/config/connect.php';
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    // Log error for debugging
    error_log("Database connection error: " . $e->getMessage());
    echo "<!-- DB Error: " . $e->getMessage() . " -->\n";
}

// Check if session is already started before starting it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For testing purposes, set session variables if they don't exist
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default instructor ID for testing
    $_SESSION['username'] = 'Instructor';
    $_SESSION['role'] = 'instructor';
}

// Reset redirect count to prevent redirect loops
$_SESSION['redirect_count'] = 0;

// Access control with proper error handling
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    // Set error message in session
    $_SESSION['error'] = "Bạn không có quyền truy cập trang này. Vui lòng đăng nhập với tài khoản giảng viên.";
    // Redirect to home page
    header("Location: " . BASE_URL . "app/views/product/home.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Lấy số lượng thông báo chưa đọc
$unreadNotifs = 0; // Default to 0
try {
    if (isset($conn) && $conn) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $notifResult = $stmt->get_result();
            $notifData = $notifResult->fetch_assoc();
            $unreadNotifs = $notifData['unread_count'] ?? 0;
        }
    }
} catch (Exception $e) {
    // Ignore notification errors
    error_log("Notification error: " . $e->getMessage());
}

// Lấy danh sách các khóa học của giảng viên
$courses = [];
try {
    if (isset($conn) && $conn) {
        $stmt = $conn->prepare("
            SELECT course_id, title 
            FROM courses 
            WHERE instructor_id = ? 
            ORDER BY title ASC
        ");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $coursesResult = $stmt->get_result();
            
            // Store courses in an array for later use
            while ($row = $coursesResult->fetch_assoc()) {
                $courses[] = $row;
            }
        }
    }
} catch (Exception $e) {
    // Log error
    error_log("Error fetching courses: " . $e->getMessage());
    echo "<!-- Courses Error: " . $e->getMessage() . " -->\n";
}

// Get the selected course
$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$course_title = '';

if ($selected_course_id > 0) {
    foreach ($courses as $course) {
        if ($course['course_id'] == $selected_course_id) {
            $course_title = $course['title'];
            break;
        }
    }
    
    if (empty($course_title)) {
        // Course not found or doesn't belong to this instructor
        $error = "Khóa học không tồn tại hoặc bạn không có quyền truy cập.";
        $selected_course_id = 0;
    }
}

// Upload directory for lesson materials
$upload_dir = ROOT_DIR . '/public/uploads/lessons/';
if (!file_exists($upload_dir)) {
    // Create directory if it doesn't exist
    try {
        mkdir($upload_dir, 0777, true);
        echo "<!-- Created upload directory: $upload_dir -->\n";
    } catch (Exception $e) {
        error_log("Error creating upload directory: " . $e->getMessage());
        echo "<!-- Error creating upload directory: " . $e->getMessage() . " -->\n";
    }
}

// Handle adding a new lesson
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_lesson']) && $selected_course_id > 0) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (!empty($title)) {
        try {
            // Insert the lesson
            $lesson_stmt = $conn->prepare("INSERT INTO lessons (course_id, title, description, created_at) VALUES (?, ?, ?, NOW())");
            $lesson_stmt->bind_param("iss", $selected_course_id, $title, $description);
            
            if ($lesson_stmt->execute()) {
                $lesson_id = $conn->insert_id;
                
                // Handle file upload if a file was submitted
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
                    $filename = basename($_FILES['attachment']['name']);
                    $filetype = $_FILES['attachment']['type'];
                    $filesize = $_FILES['attachment']['size'];
                    $temp_file = $_FILES['attachment']['tmp_name'];
                    
                    // Generate a unique filename to prevent overwriting
                    $filepath = 'public/uploads/lessons/' . time() . '_' . $filename;
                    $full_path = ROOT_DIR . '/' . $filepath;
                    
                    if (move_uploaded_file($temp_file, $full_path)) {
                        // Insert file info into database
                        $file_stmt = $conn->prepare("
                            INSERT INTO lesson_materials (lesson_id, filename, filepath, filesize, filetype) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $file_stmt->bind_param("issis", $lesson_id, $filename, $filepath, $filesize, $filetype);
                        $file_stmt->execute();
                    } else {
                        $error = "Không thể tải lên tệp đính kèm.";
                        error_log("File upload error: " . error_get_last()['message']);
                    }
                }
                
                $success = "Bài giảng đã được thêm thành công!";
            } else {
                $error = "Không thể thêm bài giảng: " . $conn->error;
                error_log("Error adding lesson: " . $conn->error);
            }
        } catch (Exception $e) {
            $error = "Lỗi: " . $e->getMessage();
            error_log("Exception adding lesson: " . $e->getMessage());
        }
    } else {
        $error = "Vui lòng nhập tiêu đề bài giảng.";
    }
}

// Handle lesson deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['lesson_id'])) {
    $lesson_id = intval($_GET['lesson_id']);
    
    try {
        // Verify the lesson belongs to a course owned by this instructor
        $check_stmt = $conn->prepare("
            SELECT l.lesson_id, l.course_id
            FROM lessons l
            JOIN courses c ON l.course_id = c.course_id
            WHERE l.lesson_id = ? AND c.instructor_id = ?
        ");
        $check_stmt->bind_param("ii", $lesson_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $lesson_data = $check_result->fetch_assoc();
            
            // Get attached files to delete them
            $files_stmt = $conn->prepare("SELECT filepath FROM lesson_materials WHERE lesson_id = ?");
            $files_stmt->bind_param("i", $lesson_id);
            $files_stmt->execute();
            $files_result = $files_stmt->get_result();
            
            while ($file = $files_result->fetch_assoc()) {
                $full_path = ROOT_DIR . '/' . $file['filepath'];
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            }
            
            // Delete the lesson (cascade will delete materials)
            $delete_stmt = $conn->prepare("DELETE FROM lessons WHERE lesson_id = ?");
            $delete_stmt->bind_param("i", $lesson_id);
            
            if ($delete_stmt->execute()) {
                $success = "Bài giảng đã được xóa thành công!";
            } else {
                $error = "Không thể xóa bài giảng: " . $conn->error;
                error_log("Error deleting lesson: " . $conn->error);
            }
            
            // Ensure we stay on the same course page
            header("Location: " . BASE_URL . "app/views/product/manage_lessons.php?course_id=" . $lesson_data['course_id']);
            exit();
        } else {
            $error = "Bạn không có quyền xóa bài giảng này!";
        }
    } catch (Exception $e) {
        $error = "Lỗi: " . $e->getMessage();
        error_log("Exception deleting lesson: " . $e->getMessage());
    }
}

// Get lessons for the selected course
$lessons = [];
if ($selected_course_id > 0) {
    try {
        $lessons_stmt = $conn->prepare("
            SELECT l.lesson_id, l.title, l.description, l.created_at,
                   COUNT(m.material_id) as attachment_count
            FROM lessons l
            LEFT JOIN lesson_materials m ON l.lesson_id = m.lesson_id
            WHERE l.course_id = ?
            GROUP BY l.lesson_id
            ORDER BY l.lesson_id DESC
        ");
        if ($lessons_stmt) {
            $lessons_stmt->bind_param("i", $selected_course_id);
            $lessons_stmt->execute();
            $lessons_result = $lessons_stmt->get_result();
            
            while ($row = $lessons_result->fetch_assoc()) {
                $lessons[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching lessons: " . $e->getMessage());
        echo "<!-- Lessons Error: " . $e->getMessage() . " -->\n";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Bài Giảng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/instructor_dashboard.css">
    <style>
        .page-header {
            background: #6c5ce7;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 0;
        }
        
        .content-wrapper {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #6c5ce7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-primary {
            background: #6c5ce7;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #5549d9;
        }
        
        .course-selector {
            margin-bottom: 20px;
        }
        
        .course-selector select {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
        }
        
        .lesson-list {
            margin-top: 30px;
        }
        
        .lesson-item {
            background: #fff;
            border: 1px solid #eee;
            padding: 18px;
            margin-bottom: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .lesson-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #333;
        }
        
        .lesson-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .lesson-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-edit {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .btn-edit:hover {
            background: #27ae60;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #777;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .file-input-container {
            margin-top: 5px;
        }
        
        h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        
        /* Add error and success message styling */
        .error-message {
            background-color: #ffecec;
            color: #e74c3c;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .success-message {
            background-color: #e7ffe7;
            color: #2ecc71;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #2ecc71;
            display: flex;
            align-items: center;
        }
        
        .success-message i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        /* Additional header styles to match the screenshot */
        .header {
            background: #6c5ce7;
            color: white;
            padding: 10px 20px;
        }
        
        .logo {
            font-size: 20px;
            font-weight: bold;
        }
        
        .logo i {
            margin-right: 8px;
        }
        
        .teacher-name {
            margin-right: 15px;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            padding: 6px 12px;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .notification-icon {
            margin-right: 15px;
        }
    </style>
</head>
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
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotifs > 0): ?>
                <span class="badge"><?php echo $unreadNotifs; ?></span>
                <?php endif; ?>
            </div>
            <button class="mode-toggle" id="mode-toggle">
                <i class="fas fa-moon"></i>
            </button>
            <div class="teacher-name">Xin chào, <strong><?php echo htmlspecialchars($username); ?></strong></div>
            <form method="post" action="<?php echo BASE_URL; ?>app/controllers/logout.php" style="display:inline;">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                </button>
            </form>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/instructor_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tổng Quan</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/create_course.php"><i class="fas fa-plus-circle"></i> Thêm Khoá Học</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/manage_lessons.php" class="active"><i class="fas fa-book"></i> Quản Lý Bài Giảng</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Danh Sách Học Viên</a></li>
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
    <div class="main-content">
        <div class="page-header">
            <h2>Quản Lý Bài Giảng</h2>
        </div>
        
        <?php if (isset($success)): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="course-selector">
            <form method="GET" action="">
                <select name="course_id" class="form-control" onchange="this.form.submit()">
                    <option value="0">-- Chọn khóa học --</option>
                    <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['course_id']; ?>" <?php echo $selected_course_id == $course['course_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <?php if ($selected_course_id > 0): ?>
        <div class="content-wrapper">
            <h3>Thêm Bài Giảng Mới</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Tiêu Đề</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Nhập tiêu đề bài giảng" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Mô Tả</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Nhập mô tả bài giảng"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="attachment">Tệp Đính Kèm</label>
                    <div class="file-input-container">
                        <input type="file" id="attachment" name="attachment">
                    </div>
                </div>
                
                <button type="submit" name="add_lesson" class="btn-primary">Thêm Bài Giảng</button>
            </form>
        </div>
        
        <div class="lesson-list">
            <h3>Danh Sách Bài Giảng</h3>
            
            <?php if (count($lessons) > 0): ?>
                <?php foreach ($lessons as $lesson): ?>
                <div class="lesson-item">
                    <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                    <div class="lesson-description"><?php echo htmlspecialchars($lesson['description'] ?: 'Mô tả ngắn về bài giảng ' . $lesson['title']); ?></div>
                    <div class="lesson-actions">
                        <a href="<?php echo BASE_URL; ?>app/views/product/edit_lesson.php?lesson_id=<?php echo $lesson['lesson_id']; ?>" class="btn-edit">Sửa</a>
                        <a href="?course_id=<?php echo $selected_course_id; ?>&action=delete&lesson_id=<?php echo $lesson['lesson_id']; ?>" class="btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa bài giảng này?')">Xóa</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Chưa có bài giảng nào cho khóa học này.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php elseif (isset($_GET['course_id']) && $_GET['course_id'] == '0'): ?>
        <div class="empty-state">
            <p>Vui lòng chọn một khóa học để quản lý bài giảng.</p>
        </div>
        <?php endif; ?>
    </div>

    <script src="<?php echo BASE_URL; ?>public/js/instructor_dashboard.js"></script>
</body>
</html> 