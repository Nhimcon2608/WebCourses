<?php
// create_discussion.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  $_SESSION['loginError'] = "Vui lòng đăng nhập để tạo chủ đề thảo luận.";
  header("Location: " . BASE_URL . "app/views/product/home.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';
$selectedCourse = '';

// Lấy danh sách khóa học mà người dùng có quyền truy cập
if ($role == 'student') {
  $courseStmt = $conn->prepare("
    SELECT c.course_id, c.title 
    FROM courses c
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.user_id = ? AND e.status = 'active'
    ORDER BY c.title ASC
  ");
  $courseStmt->bind_param("i", $user_id);
} else if ($role == 'instructor') {
  $courseStmt = $conn->prepare("
    SELECT course_id, title 
    FROM courses 
    WHERE instructor_id = ?
    ORDER BY title ASC
  ");
  $courseStmt->bind_param("i", $user_id);
} else { // admin
  $courseStmt = $conn->prepare("
    SELECT course_id, title 
    FROM courses 
    ORDER BY title ASC
  ");
}

$courseStmt->execute();
$courses = $courseStmt->get_result();

// Kiểm tra nếu có course_id trong query string
if (isset($_GET['course_id']) && !empty($_GET['course_id'])) {
  $selectedCourse = intval($_GET['course_id']);
  
  // Kiểm tra quyền truy cập khóa học
  if ($role == 'student') {
    $checkStmt = $conn->prepare("
      SELECT c.course_id 
      FROM courses c
      JOIN enrollments e ON c.course_id = e.course_id
      WHERE c.course_id = ? AND e.user_id = ? AND e.status = 'active'
    ");
    $checkStmt->bind_param("ii", $selectedCourse, $user_id);
  } else if ($role == 'instructor') {
    $checkStmt = $conn->prepare("
      SELECT course_id 
      FROM courses 
      WHERE course_id = ? AND instructor_id = ?
    ");
    $checkStmt->bind_param("ii", $selectedCourse, $user_id);
  } else { // admin
    $checkStmt = $conn->prepare("
      SELECT course_id 
      FROM courses 
      WHERE course_id = ?
    ");
    $checkStmt->bind_param("i", $selectedCourse);
  }
  
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();
  
  if ($checkResult->num_rows == 0) {
    $error = "Bạn không có quyền tạo thảo luận cho khóa học này.";
    $selectedCourse = '';
  }
}

// Xử lý khi người dùng gửi form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_discussion'])) {
  $title = trim($_POST['title']);
  $content = trim($_POST['content']);
  $course_id = intval($_POST['course_id']);
  
  // Validate input
  if (empty($title)) {
    $error = "Vui lòng nhập tiêu đề thảo luận.";
  } else if (empty($content)) {
    $error = "Vui lòng nhập nội dung thảo luận.";
  } else if (empty($course_id)) {
    $error = "Vui lòng chọn khóa học.";
  } else {
    // Kiểm tra quyền tạo thảo luận cho khóa học
    if ($role == 'student') {
      $checkStmt = $conn->prepare("
        SELECT c.course_id 
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id
        WHERE c.course_id = ? AND e.user_id = ? AND e.status = 'active'
      ");
      $checkStmt->bind_param("ii", $course_id, $user_id);
    } else if ($role == 'instructor') {
      $checkStmt = $conn->prepare("
        SELECT course_id 
        FROM courses 
        WHERE course_id = ? AND instructor_id = ?
      ");
      $checkStmt->bind_param("ii", $course_id, $user_id);
    } else { // admin
      $checkStmt = $conn->prepare("
        SELECT course_id 
        FROM courses 
        WHERE course_id = ?
      ");
      $checkStmt->bind_param("i", $course_id);
    }
    
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
      // Chèn thảo luận mới
      $insertStmt = $conn->prepare("
        INSERT INTO discussion (course_id, user_id, title, content, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
      ");
      $insertStmt->bind_param("iiss", $course_id, $user_id, $title, $content);
      
      if ($insertStmt->execute()) {
        $discussion_id = $conn->insert_id;
        
        // Redirect to the new discussion page
        header("Location: discussion_detail.php?id=$discussion_id&success=1");
        exit();
      } else {
        $error = "Không thể tạo thảo luận. Vui lòng thử lại.";
      }
    } else {
      $error = "Bạn không có quyền tạo thảo luận cho khóa học này.";
    }
  }
  
  // Nếu có lỗi, giữ lại giá trị đã nhập
  $selectedCourse = $course_id;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Tạo Chủ Đề Thảo Luận Mới</title>
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
        max-width: 900px;
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

    /* Page title */
    .page-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 2rem;
        color: #1e3c72;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 3px solid #FFC107;
        animation: fadeInDown 1s ease;
    }

    /* Create discussion form */
    .create-discussion-form {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 1.1rem;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        font-family: 'Nunito', sans-serif;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #1e3c72;
        box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 200px;
        line-height: 1.6;
    }

    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%231e3c72' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 16px;
        padding-right: 40px;
    }

    /* Button styles */
    .btn {
        display: inline-block;
        padding: 12px 24px;
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

    .btn-secondary {
        background: #6c757d;
        color: white;
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(108, 117, 125, 0.3);
    }

    /* Form actions */
    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
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

    .alert-info {
        background-color: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
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

    /* Required field indicator */
    .required:after {
        content: " *";
        color: #e74c3c;
    }

    /* Guidelines box */
    .guidelines {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .guidelines h3 {
        color: #1e3c72;
        font-size: 1.2rem;
        margin-bottom: 10px;
    }

    .guidelines ul {
        padding-left: 20px;
    }

    .guidelines li {
        margin-bottom: 8px;
        color: #555;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-container {
            flex-direction: column;
        }
        
        nav ul {
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .form-actions {
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            width: 100%;
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
          <li><a href="forum.php">Diễn Đàn</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
      <a href="<?php echo $role == 'student' ? 'student_dashboard.php' : ($role == 'instructor' ? 'instructor_dashboard.php' : 'admin_dashboard.php'); ?>">Dashboard</a> &gt;
      <a href="forum.php">Diễn Đàn</a> &gt;
      <a href="create_discussion.php">Tạo Chủ Đề Mới</a>
    </div>

    <h1 class="page-title">Tạo Chủ Đề Thảo Luận Mới</h1>
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($message)): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    
    <!-- Guidelines for posting -->
    <div class="guidelines">
      <h3>Hướng dẫn đăng bài thảo luận</h3>
      <ul>
        <li>Đặt tiêu đề rõ ràng và súc tích, phản ánh nội dung chính của thảo luận.</li>
        <li>Cung cấp đầy đủ thông tin và nội dung trong phần mô tả.</li>
        <li>Hãy lịch sự, tôn trọng ý kiến của người khác.</li>
        <li>Kiểm tra xem đã có chủ đề tương tự chưa trước khi tạo mới.</li>
        <li>Tránh viết tắt hoặc sử dụng ngôn ngữ khó hiểu.</li>
      </ul>
    </div>
    
    <!-- Create discussion form -->
    <div class="create-discussion-form">
      <form action="" method="post">
        <div class="form-group">
          <label for="course_id" class="form-label required">Khóa học</label>
          <select name="course_id" id="course_id" class="form-control" required>
            <option value="">-- Chọn khóa học --</option>
            <?php while ($course = $courses->fetch_assoc()): ?>
              <option value="<?php echo $course['course_id']; ?>" <?php echo ($selectedCourse == $course['course_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($course['title']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="title" class="form-label required">Tiêu đề</label>
          <input type="text" name="title" id="title" class="form-control" required maxlength="255" placeholder="Nhập tiêu đề thảo luận" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
        </div>
        
        <div class="form-group">
          <label for="content" class="form-label required">Nội dung</label>
          <textarea name="content" id="content" class="form-control" required placeholder="Nhập nội dung chi tiết của chủ đề thảo luận"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
        </div>
        
        <div class="alert alert-info">
          Lưu ý: Các trường đánh dấu * là bắt buộc.
        </div>
        
        <div class="form-actions">
          <a href="forum.php" class="btn btn-secondary">Hủy</a>
          <button type="submit" name="create_discussion" class="btn btn-primary">Tạo chủ đề</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>
</body>
</html> 