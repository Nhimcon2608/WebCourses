<?php
// Don't redefine constants if already defined
if (!defined('BASE_URL')) {
define('BASE_URL', '/WebCourses/');
}
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 3));
}

// Debug information (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include ROOT_DIR . '/app/config/connect.php';

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
} catch (Exception $e) {
    // Ignore notification errors
}

// Check if lesson_id is provided and valid
if (!isset($_GET['lesson_id']) || !is_numeric($_GET['lesson_id'])) {
    $_SESSION['error'] = "ID bài giảng không hợp lệ.";
    header("Location: " . BASE_URL . "app/views/product/manage_lessons.php");
    exit();
}

$lesson_id = intval($_GET['lesson_id']);

// Verify the lesson exists and belongs to this instructor's course
try {
    $lesson_check = $conn->prepare("
        SELECT l.*, c.title as course_title, c.course_id
        FROM lessons l
        JOIN courses c ON l.course_id = c.course_id
        WHERE l.lesson_id = ? AND c.instructor_id = ?
    ");
    $lesson_check->bind_param("ii", $lesson_id, $user_id);
    $lesson_check->execute();
    $lesson_result = $lesson_check->get_result();

    if ($lesson_result->num_rows === 0) {
        $_SESSION['error'] = "Bài giảng không tồn tại hoặc bạn không có quyền chỉnh sửa.";
        header("Location: " . BASE_URL . "app/views/product/manage_lessons.php");
        exit();
    }

    $lesson = $lesson_result->fetch_assoc();
    $course_id = $lesson['course_id'];
    $course_title = $lesson['course_title'];
} catch (Exception $e) {
    $_SESSION['error'] = "Lỗi khi truy vấn thông tin bài giảng: " . $e->getMessage();
    header("Location: " . BASE_URL . "app/views/product/manage_lessons.php");
    exit();
}

// Get lesson attachment if any
$attachment = null;
try {
    $attachment_query = $conn->prepare("
        SELECT * FROM lesson_materials 
        WHERE lesson_id = ? 
        ORDER BY material_id DESC 
        LIMIT 1
    ");
    $attachment_query->bind_param("i", $lesson_id);
    $attachment_query->execute();
    $attachment_result = $attachment_query->get_result();
    if ($attachment_result->num_rows > 0) {
        $attachment = $attachment_result->fetch_assoc();
    }
} catch (Exception $e) {
    // Ignore attachment errors
    error_log("Error fetching attachment: " . $e->getMessage());
}

// Handle form submission for updating lesson
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_lesson'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (!empty($title)) {
        // Update lesson
        $update_stmt = $conn->prepare("
            UPDATE lessons 
            SET title = ?, description = ?, updated_at = NOW()
            WHERE lesson_id = ?
        ");
        $update_stmt->bind_param("ssi", $title, $description, $lesson_id);
        
        $success = false;
        if ($update_stmt->execute()) {
            $success = true;
            
            // Handle file upload if a new file was submitted
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
                $filename = basename($_FILES['attachment']['name']);
                $filetype = $_FILES['attachment']['type'];
                $filesize = $_FILES['attachment']['size'];
                $temp_file = $_FILES['attachment']['tmp_name'];
                
                // Generate a unique filename to prevent overwriting
                $filepath = 'public/uploads/lessons/' . time() . '_' . $filename;
                $full_path = ROOT_DIR . '/' . $filepath;
                
                if (move_uploaded_file($temp_file, $full_path)) {
                    // If there was an existing attachment, delete it
                    if ($attachment) {
                        $old_path = ROOT_DIR . '/' . $attachment['filepath'];
                        if (file_exists($old_path)) {
                            unlink($old_path);
                        }
                        
                        // Update the existing record
                        $file_update = $conn->prepare("
                            UPDATE lesson_materials 
                            SET filename = ?, filepath = ?, filesize = ?, filetype = ?
                            WHERE material_id = ?
                        ");
                        $file_update->bind_param("ssisi", $filename, $filepath, $filesize, $filetype, $attachment['material_id']);
                        $file_update->execute();
                    } else {
                        // Insert new file info
                        $file_stmt = $conn->prepare("
                            INSERT INTO lesson_materials (lesson_id, filename, filepath, filesize, filetype) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $file_stmt->bind_param("issis", $lesson_id, $filename, $filepath, $filesize, $filetype);
                        $file_stmt->execute();
                    }
                } else {
                    $error = "Không thể tải lên tệp đính kèm.";
                    $success = false;
                }
            }
            
            if ($success) {
                $_SESSION['success'] = "Bài giảng đã được cập nhật thành công!";
                header("Location: " . BASE_URL . "app/views/product/manage_lessons.php?course_id=" . $course_id);
                exit();
            }
        } else {
            $error = "Không thể cập nhật bài giảng: " . $conn->error;
        }
    } else {
        $error = "Vui lòng nhập tiêu đề bài giảng.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Bài Giảng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/instructor_dashboard.css">
    <style>
        .page-header {
            background: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
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
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .buttons {
            margin-top: 20px;
            display: flex;
        }
        
        .course-info {
            margin-bottom: 20px;
            color: #666;
        }
        
        .current-file {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .current-file a {
            color: #3498db;
            text-decoration: none;
        }
        
        .current-file a:hover {
            text-decoration: underline;
        }
        
        .file-input-container {
            margin-top: 10px;
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
            <h2>Chỉnh Sửa Bài Giảng</h2>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="content-wrapper">
            <div class="course-info">
                <strong>Khóa học:</strong> <?php echo htmlspecialchars($course_title); ?>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Tiêu Đề</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($lesson['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Mô Tả</label>
                    <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($lesson['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Tệp Đính Kèm</label>
                    <?php if ($attachment): ?>
                    <div class="current-file">
                        <span><i class="fas fa-file"></i> <?php echo htmlspecialchars($attachment['filename']); ?></span>
                        <a href="<?php echo BASE_URL . $attachment['filepath']; ?>" target="_blank">Xem tệp</a>
                    </div>
                    <?php else: ?>
                    <p>Chưa có tệp đính kèm.</p>
                    <?php endif; ?>
                    
                    <div class="file-input-container">
                        <label for="attachment">Tải lên tệp mới (sẽ thay thế tệp hiện tại)</label>
                        <input type="file" id="attachment" name="attachment" class="form-control">
                    </div>
                </div>
                
                <div class="buttons">
                    <a href="<?php echo BASE_URL; ?>app/views/product/manage_lessons.php?course_id=<?php echo $course_id; ?>" class="btn-secondary">Hủy</a>
                    <button type="submit" name="update_lesson" class="btn-primary">Cập Nhật Bài Giảng</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>public/js/instructor_dashboard.js"></script>
</body>
</html> 