<?php
// 
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để truy cập trang bài tập.";
  header("Location: " . BASE_URL . "app/views/product/home.php");
  exit();
}
$user_id = $_SESSION['user_id'];

// Check if AssignmentSubmissions table exists
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'AssignmentSubmissions'");
if ($checkTable && $checkTable->num_rows > 0) {
    $tableExists = true;
}

// Lọc theo loại bài tập
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$type_condition = "";
if (!empty($type_filter)) {
    // Tạo điều kiện tùy theo loại bài tập (có thể điều chỉnh theo nhu cầu)
    if ($type_filter == 'ltweb') {
        $type_condition = " AND (c.title LIKE '%Web%' OR c.title LIKE '%HTML%' OR c.title LIKE '%CSS%')";
    } elseif ($type_filter == 'ltc') {
        $type_condition = " AND (c.title LIKE '%C%' OR c.title LIKE '%Programming%')";
    } elseif ($type_filter == 'html') {
        $type_condition = " AND (c.title LIKE '%HTML%')";
    }
}

// Lọc theo trạng thái
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$status_condition = "";
if ($status_filter == 'danglam') {
    // Bài tập đang làm: chưa nộp và chưa quá hạn
    $status_condition = " AND (a.due_date >= CURDATE())";
} elseif ($status_filter == 'hoanthanh') {
    // Bài tập hoàn thành: đã nộp 
    if ($tableExists) {
        $status_condition = " AND EXISTS (SELECT 1 FROM AssignmentSubmissions s WHERE s.assignment_id = a.assignment_id AND s.user_id = $user_id)";
    }
}

// Lấy danh sách bài tập
try {
    if ($tableExists) {
        // Use the submission count if the table exists
        $sql = "
            SELECT a.assignment_id, a.title, a.description, a.due_date,
                c.title as course_title, c.course_id,
                (SELECT COUNT(*) FROM AssignmentSubmissions s WHERE s.assignment_id = a.assignment_id AND s.user_id = ?) as submitted
            FROM Assignments a
            JOIN Courses c ON a.course_id = c.course_id
            JOIN Enrollments e ON c.course_id = e.course_id
            WHERE e.user_id = ? AND e.status = 'active'
            $status_condition
            $type_condition
            ORDER BY a.due_date ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
    } else {
        // Without submission count if the table doesn't exist yet
        $sql = "
            SELECT a.assignment_id, a.title, a.description, a.due_date,
                c.title as course_title, c.course_id,
                0 as submitted
            FROM Assignments a
            JOIN Courses c ON a.course_id = c.course_id
            JOIN Enrollments e ON c.course_id = e.course_id
            WHERE e.user_id = ? AND e.status = 'active'
            $status_condition
            $type_condition
            ORDER BY a.due_date ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $assignments = $stmt->get_result();
} catch (Exception $e) {
    // Fallback to empty array if query fails
    $assignments = [];
}

// Nhóm bài tập theo loại khóa học
$assignment_categories = [];
while ($assignment = $assignments->fetch_assoc()) {
    $course_type = 'other';
    
    // Xác định loại khóa học
    if (stripos($assignment['course_title'], 'HTML') !== false || 
        stripos($assignment['course_title'], 'CSS') !== false) {
        $course_type = 'HTML';
    } elseif (stripos($assignment['course_title'], 'Web') !== false) {
        $course_type = 'Web';
    } elseif (stripos($assignment['course_title'], 'C') !== false) {
        $course_type = 'C';
    }
    
    if (!isset($assignment_categories[$course_type])) {
        $assignment_categories[$course_type] = [];
    }
    
    $assignment_categories[$course_type][] = $assignment;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Học Tập - Bài Tập Của Tôi</title>
  <!-- Google Fonts (tùy chọn) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link 
    href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" 
    rel="stylesheet"
  >
  <style>
    /* Reset CSS cơ bản (tùy chọn) */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html, body {
      font-family: 'Roboto', sans-serif;
      background-color: #f7f8fa;
      color: #333;
    }
    /* Giúp căn giữa nội dung, đặt độ rộng max */
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
    }
    /* Header (thanh điều hướng) */
    .header {
      background-color: #0d6efd; /* Màu xanh dương */
      padding: 10px 0;
    }
    .header-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .logo a {
      font-size: 1.5rem;
      font-weight: 700;
      color: #fff;
      text-decoration: none;
    }
    .nav-menu ul {
      list-style: none;
      display: flex;
      gap: 20px;
    }
    .nav-menu a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }
    .nav-menu a:hover {
      color: #ffc107; /* Màu vàng nhạt khi hover */
    }
    /* Tiêu đề trang */
    .page-title {
      font-size: 2rem;
      margin: 20px 0;
      text-align: center;
    }
    /* Khu vực lọc */
    .filter-section {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
      margin-bottom: 30px;
      padding: 15px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .filter-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    .filter-item label {
      font-weight: 500;
    }
    .filter-item select {
      padding: 8px 12px;
      border: 1px solid #ccc;
      border-radius: 4px;
      min-width: 150px;
    }
    .btn-filter {
      background-color: #198754; /* Màu xanh lá */
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 500;
      transition: background-color 0.2s;
      align-self: flex-end;
    }
    .btn-filter:hover {
      background-color: #157347;
    }
    /* Danh sách bài tập */
    .assignment-list {
      margin-bottom: 40px;
    }
    .assignment-list h2 {
      font-size: 1.5rem;
      margin-bottom: 20px;
      padding-bottom: 8px;
      border-bottom: 2px solid #0d6efd;
      color: #0d6efd;
    }
    .assignment-card {
      background-color: #fff;
      border-left: 4px solid #0d6efd;
      border-radius: 6px;
      padding: 16px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .assignment-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .assignment-card h3 {
      margin-bottom: 10px;
      font-size: 1.2rem;
      color: #0d6efd;
    }
    .assignment-card p {
      margin-bottom: 8px;
      color: #555;
    }
    .assignment-meta {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
      font-size: 0.9rem;
    }
    .assignment-course {
      background-color: #e9ecef;
      padding: 3px 8px;
      border-radius: 12px;
      font-weight: 500;
    }
    .btn-detail {
      display: inline-block;
      padding: 8px 12px;
      background-color: #0d6efd;
      color: #fff;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.2s;
    }
    .btn-detail:hover {
      background-color: #0a58ca;
    }
    .btn-submit {
      display: inline-block;
      padding: 8px 12px;
      background-color: #198754;
      color: #fff;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.2s;
      margin-left: 10px;
    }
    .btn-submit:hover {
      background-color: #157347;
    }
    .btn-view {
      display: inline-block;
      padding: 8px 12px;
      background-color: #6c757d;
      color: #fff;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.2s;
      margin-left: 10px;
    }
    .btn-view:hover {
      background-color: #5a6268;
    }
    /* Trạng thái */
    .status {
      font-weight: 600;
    }
    .status.pending {
      color: #ff9800; /* Màu cam */
    }
    .status.completed {
      color: #198754; /* Màu xanh lá */
    }
    .status.overdue {
      color: #dc3545; /* Màu đỏ */
    }
    /* Thông báo không có bài tập */
    .no-assignments {
      text-align: center;
      margin: 30px 0;
      padding: 30px;
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .no-assignments h3 {
      color: #6c757d;
      margin-bottom: 10px;
    }
    .no-assignments p {
      color: #adb5bd;
      font-size: 1.1rem;
    }
    /* Nút quay lại Dashboard */
    .back-dashboard {
      text-align: center;
      margin-bottom: 40px;
    }
    .btn-back {
      display: inline-block;
      padding: 10px 20px;
      background-color: #0d6efd; 
      color: #fff;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
      transition: background-color 0.2s;
    }
    .btn-back:hover {
      background-color: #0a58ca;
    }
    /* Footer */
    .footer {
      background-color: #f1f1f1;
      padding: 15px 0;
      text-align: center;
      font-size: 0.9rem;
      color: #666;
      margin-top: 40px;
      border-top: 1px solid #ddd;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .nav-menu ul {
        gap: 10px;
      }
      .filter-section {
        flex-direction: column;
        align-items: stretch;
      }
      .assignment-meta {
        flex-direction: column;
        gap: 5px;
      }
      .assignment-card .btn-detail,
      .assignment-card .btn-submit,
      .assignment-card .btn-view {
        display: block;
        width: 100%;
        margin: 5px 0;
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <!-- Thanh điều hướng (Header) -->
  <header class="header">
    <div class="container header-container">
      <div class="logo">
        <a href="<?php echo BASE_URL; ?>app/views/product/home.php">Học Tập</a>
      </div>
      <nav class="nav-menu">
        <ul>
          <li><a href="<?php echo BASE_URL; ?>app/views/product/home.php">Trang Chủ</a></li>
          <li><a href="<?php echo BASE_URL; ?>app/views/product/student_dashboard.php">Dashboard</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <!-- Nội dung chính -->
  <main class="main-content container">
    <h1 class="page-title">Bài Tập Của Tôi</h1>

    <!-- Khu vực lọc bài tập -->
    <form method="GET" action="" class="filter-section">
      <div class="filter-item">
        <label for="status">Trạng Thái:</label>
        <select id="status" name="status">
          <option value="">Tất cả</option>
          <option value="danglam" <?php echo $status_filter == 'danglam' ? 'selected' : ''; ?>>Đang Làm</option>
          <option value="hoanthanh" <?php echo $status_filter == 'hoanthanh' ? 'selected' : ''; ?>>Hoàn Thành</option>
        </select>
      </div>
      <div class="filter-item">
        <label for="type">Loại Bài Tập:</label>
        <select id="type" name="type">
          <option value="">Tất cả</option>
          <option value="ltweb" <?php echo $type_filter == 'ltweb' ? 'selected' : ''; ?>>Lập Trình Web</option>
          <option value="ltc" <?php echo $type_filter == 'ltc' ? 'selected' : ''; ?>>Lập Trình C</option>
          <option value="html" <?php echo $type_filter == 'html' ? 'selected' : ''; ?>>HTML</option>
        </select>
      </div>
      <button type="submit" class="btn-filter">Lọc Bài Tập</button>
    </form>

    <?php if (count($assignment_categories) > 0): ?>
        <?php foreach ($assignment_categories as $category => $category_assignments): ?>
            <!-- Danh sách bài tập theo loại -->
            <section class="assignment-list">
              <h2>Bài tập <?php echo htmlspecialchars($category); ?></h2>
              
              <?php foreach ($category_assignments as $assignment): 
                  $is_submitted = $assignment['submitted'] > 0;
                  $is_overdue = strtotime($assignment['due_date']) < time();
                  
                  if ($is_submitted) {
                      $status_class = "completed";
                      $status_text = "Hoàn thành";
                  } elseif ($is_overdue) {
                      $status_class = "overdue";
                      $status_text = "Quá hạn";
                  } else {
                      $status_class = "pending";
                      $status_text = "Đang làm";
                  }
              ?>
                <div class="assignment-card">
                  <div class="assignment-meta">
                    <span class="assignment-course"><?php echo htmlspecialchars($assignment['course_title']); ?></span>
                    <span>Hạn nộp: <?php echo date('d/m/Y', strtotime($assignment['due_date'])); ?></span>
                  </div>
                  <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                  <p>Trạng thái: <span class="status <?php echo $status_class; ?>"><?php echo $status_text; ?></span></p>
                  <p><?php echo substr(htmlspecialchars($assignment['description']), 0, 100) . '...'; ?></p>
                  <div class="assignment-actions">
                    <a href="assignment_detail.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn-detail">Xem chi tiết</a>
                    <?php if (!$is_submitted): ?>
                        <a href="submit_assignment.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn-submit">Nộp bài</a>
                    <?php else: ?>
                        <a href="view_submission.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn-view">Xem bài đã nộp</a>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-assignments">
          <h3>Không tìm thấy bài tập nào</h3>
          <p>Hãy thử thay đổi bộ lọc hoặc kiểm tra lại sau.</p>
        </div>
    <?php endif; ?>

    <!-- Nút quay lại Dashboard -->
    <div class="back-dashboard">
      <a href="<?php echo BASE_URL; ?>app/views/product/student_dashboard.php" class="btn-back">Quay Lại Dashboard</a>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <p>© 2025 Học Tập Trực Tuyến, All Rights Reserved.</p>
    </div>
  </footer>

</body>
</html> 