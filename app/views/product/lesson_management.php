<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra xem người dùng đã đăng nhập và có vai trò giảng viên chưa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: home.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Kiểm tra xem khóa học có thuộc về giảng viên không
$stmt = $conn->prepare("SELECT c.course_id, c.title, c.description FROM courses c WHERE c.course_id = ? AND c.instructor_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: instructor_dashboard.php");
    exit();
}

// Khởi tạo model Lesson
require_once '../../models/Lesson.php';
$lessonModel = new Lesson($conn);

// Xử lý các yêu cầu tạo, cập nhật hoặc xóa bài học
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tạo bài học mới
    if (isset($_POST['create_lesson'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $order_index = intval($_POST['order_index']);
        $duration = intval($_POST['duration']);
        
        $result = $lessonModel->create($course_id, $title, $content, $order_index, $duration);
        if ($result['success']) {
            $_SESSION['lessonSuccess'] = $result['message'];
        } else {
            $_SESSION['lessonError'] = $result['message'];
        }
    }
    
    // Cập nhật bài học
    if (isset($_POST['update_lesson'])) {
        $lesson_id = intval($_POST['lesson_id']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $order_index = intval($_POST['order_index']);
        $duration = intval($_POST['duration']);
        
        $result = $lessonModel->update($lesson_id, $course_id, $title, $content, $order_index, $duration);
        if ($result['success']) {
            $_SESSION['lessonSuccess'] = $result['message'];
        } else {
            $_SESSION['lessonError'] = $result['message'];
        }
    }
    
    // Xóa bài học
    if (isset($_POST['delete_lesson'])) {
        $lesson_id = intval($_POST['lesson_id']);
        
        $result = $lessonModel->delete($lesson_id, $course_id);
        if ($result['success']) {
            $_SESSION['lessonSuccess'] = $result['message'];
        } else {
            $_SESSION['lessonError'] = $result['message'];
        }
    }
    
    // Chuyển hướng để tránh gửi lại form khi refresh trang
    header("Location: lesson_management.php?course_id=$course_id");
    exit();
}

// Lấy danh sách bài học của khóa học
$lessons = $lessonModel->getByCourse($course_id);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Bài Học - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        /* CSS cho quản lý bài học */
        .lesson-container {
            margin-top: 20px;
            border-radius: 10px;
            background: white;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .lesson-list {
            margin-top: 20px;
        }
        .lesson-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #4aa1ff;
            position: relative;
            transition: all 0.3s ease;
        }
        .lesson-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .lesson-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .lesson-details {
            display: flex;
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .lesson-details span {
            margin-right: 20px;
        }
        .lesson-actions {
            display: flex;
            gap: 10px;
        }
        .lesson-actions button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        .edit-btn {
            background: #4aa1ff;
            color: white;
        }
        .edit-btn:hover {
            background: #3a8fd8;
        }
        .delete-btn {
            background: #ff4a4a;
            color: white;
        }
        .delete-btn:hover {
            background: #d83a3a;
        }
        .lesson-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .lesson-form h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        .submit-btn {
            background: #4aa1ff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .submit-btn:hover {
            background: #3a8fd8;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        .back-btn:hover {
            background: #e0e0e0;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
            position: relative;
            font-weight: 500;
        }
        .alert-success {
            background-color: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        .alert-error {
            background-color: rgba(255, 68, 68, 0.2);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.3);
        }
        .no-lessons {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Quản Lý Giảng Viên</div>
        <div class="teacher-name">Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></div>
        <a href="<?php echo BASE_URL; ?>auth/logout" class="logout-btn">Đăng xuất</a>
    </header>

    <div class="sidebar">
        <ul>
            <li><a href="instructor_dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="course_management.php?course_id=<?php echo $course_id; ?>"><i class="fa fa-book"></i> Quản lý khóa học</a></li>
            <li><a href="create_course.php"><i class="fa fa-plus-circle"></i> Tạo khóa học mới</a></li>
            <li><a href="home.php"><i class="fa fa-home"></i> Trang chủ</a></li>
        </ul>
    </div>

    <div class="main-content">
        <a href="course_management.php?course_id=<?php echo $course_id; ?>" class="back-btn">
            <i class="fa fa-arrow-left"></i> Quay lại quản lý khóa học
        </a>
        
        <h2>Quản Lý Bài Học: <?php echo htmlspecialchars($course['title']); ?></h2>
        
        <?php if (isset($_SESSION['lessonSuccess'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['lessonSuccess']); ?>
                <?php unset($_SESSION['lessonSuccess']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['lessonError'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['lessonError']); ?>
                <?php unset($_SESSION['lessonError']); ?>
            </div>
        <?php endif; ?>

        <!-- Form tạo bài học mới -->
        <div class="lesson-form">
            <h3>Thêm Bài Học Mới</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Tiêu đề bài học</label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="content">Nội dung bài học</label>
                    <textarea id="content" name="content" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="order_index">Thứ tự hiển thị</label>
                    <input type="number" id="order_index" name="order_index" class="form-control" min="0" value="0" required>
                </div>
                <div class="form-group">
                    <label for="duration">Thời lượng (phút)</label>
                    <input type="number" id="duration" name="duration" class="form-control" min="0" value="0" required>
                </div>
                <button type="submit" name="create_lesson" class="submit-btn">Thêm bài học</button>
            </form>
        </div>
        
        <!-- Danh sách bài học -->
        <div class="lesson-container">
            <h3>Danh Sách Bài Học</h3>
            
            <?php if (empty($lessons)): ?>
                <div class="no-lessons">
                    <p>Chưa có bài học nào. Vui lòng thêm bài học mới.</p>
                </div>
            <?php else: ?>
                <div class="lesson-list">
                    <?php foreach ($lessons as $lesson): ?>
                        <div class="lesson-item" id="lesson-<?php echo $lesson['lesson_id']; ?>">
                            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                            <div class="lesson-details">
                                <span><i class="fa fa-sort"></i> Thứ tự: <?php echo $lesson['order_index']; ?></span>
                                <span><i class="fa fa-clock"></i> Thời lượng: <?php echo $lesson['duration']; ?> phút</span>
                            </div>
                            <div class="lesson-actions">
                                <button class="edit-btn" onclick="editLesson(<?php echo $lesson['lesson_id']; ?>)">
                                    <i class="fa fa-edit"></i> Sửa
                                </button>
                                <button class="delete-btn" onclick="deleteLesson(<?php echo $lesson['lesson_id']; ?>, '<?php echo htmlspecialchars($lesson['title']); ?>')">
                                    <i class="fa fa-trash"></i> Xóa
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Form modal chỉnh sửa bài học -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="auth-form">
                <span class="close">&times;</span>
                <div class="auth-form-box">
                    <form id="editLessonForm" method="POST" action="" class="auth-form-content">
                        <h2 class="auth-title">Chỉnh Sửa Bài Học</h2>
                        <input type="hidden" id="edit_lesson_id" name="lesson_id">
                        <div class="auth-input-group">
                            <i class="fas fa-heading"></i>
                            <input type="text" id="edit_title" name="title" class="auth-input" placeholder="Tiêu đề bài học" required maxlength="100">
                        </div>
                        <div class="auth-input-group">
                            <i class="fas fa-file-alt"></i>
                            <textarea id="edit_content" name="content" class="auth-input" placeholder="Nội dung bài học" required style="height: 150px;"></textarea>
                        </div>
                        <div class="auth-input-group">
                            <i class="fas fa-sort-numeric-down"></i>
                            <input type="number" id="edit_order_index" name="order_index" class="auth-input" placeholder="Thứ tự hiển thị" min="0" required>
                        </div>
                        <div class="auth-input-group">
                            <i class="fas fa-clock"></i>
                            <input type="number" id="edit_duration" name="duration" class="auth-input" placeholder="Thời lượng (phút)" min="0" required>
                        </div>
                        <button type="submit" name="update_lesson" class="auth-submit">Cập Nhật</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Form modal xác nhận xóa bài học -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="auth-form">
                <span class="close">&times;</span>
                <div class="auth-form-box">
                    <form id="deleteLessonForm" method="POST" action="" class="auth-form-content">
                        <h2 class="auth-title">Xác Nhận Xóa</h2>
                        <p style="text-align: center; margin-bottom: 20px;">Bạn có chắc chắn muốn xóa bài học "<span id="delete_lesson_title"></span>"?</p>
                        <input type="hidden" id="delete_lesson_id" name="lesson_id">
                        <button type="submit" name="delete_lesson" class="auth-submit" style="background-color: #ff4a4a;">Xóa Bài Học</button>
                        <button type="button" id="cancel_delete" class="auth-submit" style="background-color: #999; margin-top: 10px;">Hủy Bỏ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Lưu trữ dữ liệu bài học
        const lessons = <?php echo json_encode($lessons); ?>;
        
        // Modal đối tượng
        const editModal = document.getElementById("editModal");
        const deleteModal = document.getElementById("deleteModal");
        const closeButtons = document.querySelectorAll(".close");
        const cancelDelete = document.getElementById("cancel_delete");
        
        // Đóng modal khi nhấp vào nút đóng
        closeButtons.forEach(button => {
            button.addEventListener("click", function() {
                editModal.style.display = "none";
                deleteModal.style.display = "none";
            });
        });
        
        // Đóng modal khi nhấp vào bên ngoài
        window.addEventListener("click", function(event) {
            if (event.target === editModal) {
                editModal.style.display = "none";
            }
            if (event.target === deleteModal) {
                deleteModal.style.display = "none";
            }
        });
        
        // Đóng modal xóa khi nhấp vào nút hủy
        cancelDelete.addEventListener("click", function() {
            deleteModal.style.display = "none";
        });
        
        // Mở modal chỉnh sửa và điền dữ liệu
        function editLesson(lessonId) {
            const lesson = lessons.find(l => l.lesson_id == lessonId);
            if (lesson) {
                document.getElementById("edit_lesson_id").value = lesson.lesson_id;
                document.getElementById("edit_title").value = lesson.title;
                document.getElementById("edit_content").value = lesson.content;
                document.getElementById("edit_order_index").value = lesson.order_index;
                document.getElementById("edit_duration").value = lesson.duration;
                editModal.style.display = "flex";
            }
        }
        
        // Mở modal xóa và hiển thị thông tin
        function deleteLesson(lessonId, lessonTitle) {
            document.getElementById("delete_lesson_id").value = lessonId;
            document.getElementById("delete_lesson_title").textContent = lessonTitle;
            deleteModal.style.display = "flex";
        }
    </script>
</body>
</html> 