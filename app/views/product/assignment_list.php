<?php
session_start();
define('BASE_URL', '/WebCourses/');

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

// Connect to database
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
         <p>Go to <a href="/WebCourses/setup_db.php">Database Setup</a> to create the database and tables.</p>
         </div>');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['loginError'] = "Vui lòng đăng nhập để xem bài tập.";
    header("Location: " . BASE_URL . "app/views/product/home.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$course_id = isset($_GET['course']) ? intval($_GET['course']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date-desc';
$deadline = isset($_GET['deadline']) ? $_GET['deadline'] : '';

// Build SQL query conditions
$conditions = [];
$params = [];
$types = '';

// Always filter by user enrollment
$conditions[] = "e.user_id = ?";
$params[] = $user_id;
$types .= 'i';

// Status filter
if ($status == 'done') {
    $conditions[] = "(SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.user_id = ?) > 0";
    $params[] = $user_id;
    $types .= 'i';
} elseif ($status == 'pending') {
    $conditions[] = "(SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.user_id = ?) = 0";
    $params[] = $user_id;
    $types .= 'i';
}

// Course filter
if ($course_id > 0) {
    $conditions[] = "c.id = ?";
    $params[] = $course_id;
    $types .= 'i';
}

// Deadline filter
if (!empty($deadline)) {
    $conditions[] = "a.due_date <= ?";
    $params[] = $deadline . ' 23:59:59';
    $types .= 's';
}

// Build the WHERE clause
$where_clause = implode(' AND ', $conditions);

// Build the ORDER BY clause
$order_clause = "a.due_date DESC"; // Default
if ($sort == 'date-asc') {
    $order_clause = "a.due_date ASC";
} elseif ($sort == 'deadline') {
    $order_clause = "a.due_date ASC";
}

// Fetch assignments
$sql = "
    SELECT 
        a.id as assignment_id, 
        a.title, 
        a.description, 
        a.due_date, 
        a.max_points,
        c.title as course_title, 
        c.id as course_id,
        (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.user_id = ?) > 0 as is_submitted
    FROM 
        assignments a
    JOIN 
        courses c ON a.course_id = c.id
    JOIN 
        enrollments e ON c.id = e.course_id
    WHERE 
        $where_clause
    ORDER BY 
        $order_clause
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types . 'i', ...$params, $user_id);
$stmt->execute();
$assignments = $stmt->get_result();

// Get all courses for filter dropdown
$courses_query = "
    SELECT c.id, c.title, COUNT(a.id) as assignment_count
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id 
    LEFT JOIN assignments a ON c.id = a.course_id
    WHERE e.user_id = ? AND e.status = 'active'
    GROUP BY c.id
    ORDER BY c.title
";

$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("i", $user_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Danh Sách Bài Tập</title>
  <!-- Google Fonts (tùy chọn) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link 
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Nunito:wght@400;500;600;700&display=swap" 
    rel="stylesheet"
  >
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Fallback for Font Awesome if CDN fails -->
  <script>
    (function() {
      var css = document.createElement('link');
      css.href = 'https://kit.fontawesome.com/a076d05399.js';
      css.rel = 'stylesheet';
      css.type = 'text/css';
      document.getElementsByTagName('head')[0].appendChild(css);
    })();
  </script>

  <style>
    /* RESET CƠ BẢN */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Nunito', 'Montserrat', sans-serif;
      background-color: #f8f9fa;
      color: #333;
    }

    /* HEADER */
    header {
      background-color: #0d47a1; /* Màu xanh header */
      padding: 15px 0;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .header-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .logo {
      font-size: 1.8rem;
      font-weight: 700;
      color: #ffc107; /* Màu vàng nổi bật cho logo */
      text-transform: uppercase;
      text-decoration: none;
    }
    nav ul {
      list-style: none;
      display: flex;
      gap: 30px;
    }
    nav ul li a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }
    nav ul li a:hover {
      color: #ffc107; /* Hover chuyển sang màu vàng */
    }

    /* MAIN */
    .main-container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 250px 1fr; /* Cột trái 250px, cột phải chiếm phần còn lại */
      gap: 20px;
    }

    /* SIDEBAR BỘ LỌC */
    .filter-sidebar {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
      align-self: start; /* Giúp sidebar bám lên trên */
    }
    .filter-sidebar h3 {
      font-size: 1.2rem;
      margin-bottom: 15px;
      color: #0d47a1;
    }
    .filter-group {
      margin-bottom: 15px;
    }
    .filter-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      font-size: 0.95rem;
      color: #333;
    }
    .filter-group select,
    .filter-group input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-family: inherit;
      font-size: 1rem;
      outline: none;
    }
    .apply-filter-btn {
      display: inline-block;
      margin-top: 10px;
      padding: 10px 20px;
      background-color: #0d47a1;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      text-align: center;
      transition: background 0.3s ease;
      border: none;
      cursor: pointer;
      width: 100%;
    }
    .apply-filter-btn:hover {
      background-color: #08306f;
    }

    /* DANH SÁCH BÀI TẬP */
    .exercise-list-container {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
      padding: 20px;
      min-height: 400px; /* Chiều cao tối thiểu */
      display: flex;
      flex-direction: column;
    }
    .exercise-list-container h2 {
      font-size: 1.5rem;
      margin-bottom: 15px;
      color: #0d47a1;
    }

    /* DANH SÁCH BÀI TẬP CỤ THỂ */
    .exercise-items {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
      /* 
        Nếu muốn chia thành nhiều cột trên màn hình lớn, 
        có thể dùng: grid-template-columns: 1fr 1fr; 
        hoặc auto-fit, auto-fill tùy ý
      */
    }
    .exercise-item {
      border: 1px solid #eee;
      padding: 15px;
      border-radius: 8px;
      background-color: #fafafa;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .exercise-item:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    .exercise-item h3 {
      margin-bottom: 10px;
      font-size: 1.2rem;
      color: #0d47a1;
    }
    .exercise-item p {
      margin-bottom: 5px;
      line-height: 1.4;
    }
    .submit-btn {
      display: inline-block;
      margin-top: 10px;
      padding: 8px 15px;
      background-color: #ffc107;
      color: #333;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      transition: background 0.3s ease;
    }
    .submit-btn:hover {
      background-color: #ffb300;
    }
    
    .submitted-btn {
      background-color: #4CAF50;
      color: white;
    }
    .submitted-btn:hover {
      background-color: #388E3C;
    }

    .overdue {
      color: #d32f2f;
      font-weight: 600;
    }
    
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 300px;
      text-align: center;
    }
    
    .empty-state i {
      font-size: 4rem;
      color: #ccc;
      margin-bottom: 20px;
    }
    
    .empty-state p {
      color: #666;
      font-size: 1.1rem;
      margin-bottom: 20px;
    }
    
    .view-all-btn {
      display: inline-block;
      padding: 10px 20px;
      background-color: #0d47a1;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      transition: background 0.3s ease;
    }
    
    .view-all-btn:hover {
      background-color: #08306f;
    }

    /* FOOTER */
    footer {
      background-color: #0d47a1;
      color: #fff;
      padding: 15px 20px;
      text-align: center;
      margin-top: 20px;
    }
    footer p {
      margin: 0;
      font-size: 0.9rem;
    }

    /* Status badges */
    .status-badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 10px;
    }
    
    .status-pending {
      background-color: #FFC107;
      color: #333;
    }
    
    .status-submitted {
      background-color: #4CAF50;
      color: white;
    }
    
    .status-overdue {
      background-color: #F44336;
      color: white;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .main-container {
        grid-template-columns: 1fr; /* Trên màn hình nhỏ, bộ lọc sẽ nằm trên, danh sách bên dưới */
      }
      .filter-sidebar {
        margin-bottom: 20px;
      }
      .header-container {
        flex-direction: column;
      }
      nav ul {
        margin-top: 15px;
        gap: 15px;
      }
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header>
    <div class="header-container">
      <a href="<?php echo BASE_URL; ?>app/views/product/home.php" class="logo">Học Tập</a>
      <nav>
        <ul>
          <li><a href="<?php echo BASE_URL; ?>app/views/product/home.php">Trang Chủ</a></li>
          <li><a href="<?php echo BASE_URL; ?>app/views/product/student_dashboard.php">Dashboard</a></li>
          <li><a href="<?php echo BASE_URL; ?>app/views/product/assignment_list.php">Bài Tập</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout.php">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <!-- MAIN -->
  <div class="main-container">
    <!-- SIDEBAR BỘ LỌC -->
    <aside class="filter-sidebar">
      <h3>Bộ Lọc</h3>
      <form action="" method="GET">
        <div class="filter-group">
          <label for="status">Trạng thái</label>
          <select id="status" name="status">
            <option value="" <?php echo $status == '' ? 'selected' : ''; ?>>Tất cả</option>
            <option value="done" <?php echo $status == 'done' ? 'selected' : ''; ?>>Đã hoàn thành</option>
            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Chưa hoàn thành</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="course">Khóa học</label>
          <select id="course" name="course">
            <option value="0">Tất cả</option>
            <?php while ($course = $courses->fetch_assoc()): ?>
              <option value="<?php echo $course['id']; ?>" <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($course['title']); ?> (<?php echo $course['assignment_count']; ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="filter-group">
          <label for="sort">Sắp xếp theo</label>
          <select id="sort" name="sort">
            <option value="date-desc" <?php echo $sort == 'date-desc' ? 'selected' : ''; ?>>Mới nhất</option>
            <option value="date-asc" <?php echo $sort == 'date-asc' ? 'selected' : ''; ?>>Cũ nhất</option>
            <option value="deadline" <?php echo $sort == 'deadline' ? 'selected' : ''; ?>>Gần hạn nộp</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="deadline">Hạn nộp (đến ngày)</label>
          <input type="date" id="deadline" name="deadline" value="<?php echo $deadline; ?>">
        </div>
        <button type="submit" class="apply-filter-btn">Áp dụng bộ lọc</button>
      </form>
    </aside>

    <!-- DANH SÁCH BÀI TẬP -->
    <section class="exercise-list-container">
      <h2>Danh Sách Bài Tập</h2>

      <!-- DANH SÁCH BÀI TẬP -->
      <?php if ($assignments && $assignments->num_rows > 0): ?>
        <div class="exercise-items">
          <?php while ($assignment = $assignments->fetch_assoc()): 
            $is_submitted = $assignment['is_submitted'];
            $is_overdue = strtotime($assignment['due_date']) < time();
            
            // Determine status for displaying
            $status_class = '';
            $status_text = '';
            $btn_class = '';
            $btn_text = 'Nộp bài';
            
            if ($is_submitted) {
              $status_class = 'status-submitted';
              $status_text = 'Đã nộp';
              $btn_class = 'submitted-btn';
              $btn_text = 'Xem bài nộp';
            } elseif ($is_overdue) {
              $status_class = 'status-overdue';
              $status_text = 'Quá hạn';
            } else {
              $status_class = 'status-pending';
              $status_text = 'Chưa nộp';
            }
          ?>
            <div class="exercise-item">
              <h3>
                <?php echo htmlspecialchars($assignment['title']); ?>
                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
              </h3>
              <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($assignment['description']); ?></p>
              <p><strong>Khóa học:</strong> <?php echo htmlspecialchars($assignment['course_title']); ?></p>
              <p><strong>Hạn nộp:</strong> 
                <span class="<?php echo $is_overdue ? 'overdue' : ''; ?>">
                  <?php echo date('d/m/Y', strtotime($assignment['due_date'])); ?>
                  <?php if ($is_overdue && !$is_submitted): ?>
                    (Đã quá hạn)
                  <?php endif; ?>
                </span>
              </p>
              <a href="<?php echo BASE_URL; ?>app/views/product/submit_assignment.php?id=<?php echo $assignment['assignment_id']; ?>" 
                 class="submit-btn <?php echo $btn_class; ?>">
                <?php echo $btn_text; ?>
              </a>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-folder-open"></i>
          <p>Không tìm thấy bài tập nào phù hợp với bộ lọc hiện tại</p>
          <a href="<?php echo BASE_URL; ?>app/views/product/assignment_list.php" class="view-all-btn">Xem lại tất cả bài tập</a>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <!-- FOOTER -->
  <footer>
    <p>&copy; <?php echo date('Y'); ?> Học Tập Trực Tuyến, All Rights Reserved.</p>
  </footer>

</body>
</html> 