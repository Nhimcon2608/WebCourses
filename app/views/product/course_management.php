<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: home.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Kiểm tra xem khóa học có thuộc về giảng viên không
$stmt = $conn->prepare("SELECT course_id, title, description, category_id, price, image, level, language FROM courses WHERE course_id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: instructor_dashboard.php");
    exit();
}

// Xử lý cập nhật khóa học
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : NULL;
    $price = !empty($_POST['price']) ? floatval($_POST['price']) : 0;
    $image = trim($_POST['image']);
    $level = trim($_POST['level']);
    $language = trim($_POST['language']);

    $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, category_id = ?, price = ?, image = ?, level = ?, language = ? WHERE course_id = ? AND instructor_id = ?");
    $stmt->bind_param("sssidssii", $title, $description, $category_id, $price, $image, $level, $language, $course_id, $user_id);

    if ($stmt->execute()) {
        $success = "Khóa học đã được cập nhật!";
        // Cập nhật lại dữ liệu hiển thị
        $stmt->execute();
        $course = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Cập nhật thất bại.";
    }
}

// Xử lý xóa khóa học
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ? AND instructor_id = ?");
    $stmt->bind_param("ii", $course_id, $user_id);
    if ($stmt->execute()) {
        header("Location: instructor_dashboard.php");
        exit();
    } else {
        $error = "Xóa thất bại.";
    }
}

$categories = $conn->query("SELECT category_id, name FROM categories WHERE status = 1");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khóa Học</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>

<body>
    <header>
        <div class="container header-container">
            <div class="logo">EduHub</div>
            <nav>
                <ul>
                    <li><a href="instructor_dashboard.php">Trang Chủ</a></li>
                    <li><a href="logout.php" class="btn">Đăng Xuất</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="course-management">
        <div class="container">
            <h2>Quản Lý Khóa Học: <?php echo htmlspecialchars($course['title']); ?></h2>
            <?php if (isset($success)): ?>
            <p class="success-message"><?php echo $success; ?></p>
            <?php endif; ?>
            <?php if (isset($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="POST" class="auth-form-content">
                <div class="auth-input-group">
                    <input type="text" name="title" class="auth-input"
                        value="<?php echo htmlspecialchars($course['title']); ?>" required>
                </div>
                <div class="auth-input-group">
                    <textarea name="description" class="auth-input"
                        required><?php echo htmlspecialchars($course['description']); ?></textarea>
                </div>
                <div class="auth-input-group">
                    <select name="category_id" class="auth-input">
                        <option value="">Chọn danh mục</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>"
                            <?php echo $cat['category_id'] == $course['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="auth-input-group">
                    <input type="number" step="0.01" name="price" class="auth-input"
                        value="<?php echo htmlspecialchars($course['price']); ?>">
                </div>
                <div class="auth-input-group">
                    <input type="text" name="image" class="auth-input"
                        value="<?php echo htmlspecialchars($course['image']); ?>">
                </div>
                <div class="auth-input-group">
                    <input type="text" name="level" class="auth-input"
                        value="<?php echo htmlspecialchars($course['level']); ?>">
                </div>
                <div class="auth-input-group">
                    <input type="text" name="language" class="auth-input"
                        value="<?php echo htmlspecialchars($course['language']); ?>">
                </div>
                <div class="course-actions">
                    <button type="submit" name="update" class="auth-submit">Cập Nhật Khóa Học</button>
                    <div style="margin-top: 20px;">
                        <a href="lesson_management.php?course_id=<?php echo $course_id; ?>" class="auth-submit"
                            style="display: inline-block; text-decoration: none; text-align: center; background-color: #4caf50;">
                            <i class="fas fa-book-open"></i> Quản Lý Bài Học
                        </a>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" name="delete" class="auth-submit" style="background-color: #ff4444;"
                            onclick="return confirm('Bạn có chắc chắn muốn xóa khóa học này?');">Xóa Khóa Học</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
        </div>
    </footer>
</body>

</html>