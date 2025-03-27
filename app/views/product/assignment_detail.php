<?php
// 
define('BASE_URL', '/WebCourses/');
// Direct database connection to avoid include path issues
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

// Connect to database
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// Phân trang cho danh sách bài tập
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Lọc theo trạng thái (nếu có)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_condition = "";
if ($status_filter == 'pending') {
    $status_condition = " AND (a.due_date >= CURDATE())";
} elseif ($status_filter == 'overdue') {
    $status_condition = " AND (a.due_date < CURDATE())";
}

// Lọc theo khóa học (nếu có)
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$course_condition = $course_filter > 0 ? " AND a.course_id = $course_filter" : "";

// Lấy danh sách bài tập
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'due_date_asc';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Tạo điều kiện tìm kiếm
$search_condition = "";
if (!empty($search_query)) {
    $search_term = '%' . $conn->real_escape_string($search_query) . '%';
    $search_condition = " AND (a.title LIKE '$search_term' OR a.description LIKE '$search_term')";
}

// Xác định thứ tự sắp xếp
$order_clause = "a.due_date ASC"; // Mặc định sắp xếp theo ngày hạn nộp (gần nhất trước)
if ($sort_by == 'due_date_desc') {
    $order_clause = "a.due_date DESC";
} elseif ($sort_by == 'title_asc') {
    $order_clause = "a.title ASC";
} elseif ($sort_by == 'title_desc') {
    $order_clause = "a.title DESC";
} elseif ($sort_by == 'course_asc') {
    $order_clause = "c.title ASC, a.due_date ASC";
}

if ($tableExists) {
    // Use the submission count if the table exists
    $sql = "
        SELECT 
            a.assignment_id, 
            a.title, 
            a.description, 
            a.due_date, 
            a.max_points,
            c.title as course_title, 
            c.course_id,
            (SELECT COUNT(*) FROM AssignmentSubmissions s WHERE s.assignment_id = a.assignment_id AND s.user_id = ?) as submitted
        FROM 
            Assignments a
        JOIN 
            Courses c ON a.course_id = c.course_id
        JOIN 
            Enrollments e ON c.course_id = e.course_id
        WHERE 
            e.user_id = ? AND e.status = 'active'
            $status_condition
            $course_condition
            $search_condition
        ORDER BY 
            $order_clause
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $user_id, $limit, $offset);
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
        $course_condition
        ORDER BY a.due_date ASC
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $limit, $offset);
}

$stmt->execute();
$assignments = $stmt->get_result();

// Tổng số bài tập
$resultTotal = $conn->query("
    SELECT COUNT(*) AS total 
    FROM Assignments a
    JOIN Courses c ON a.course_id = c.course_id
    JOIN Enrollments e ON c.course_id = e.course_id
    WHERE e.user_id = $user_id AND e.status = 'active'
    $status_condition
    $course_condition
");
$totalData = $resultTotal->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);

// Lấy danh sách khóa học để lọc
$coursesQuery = $conn->query("
    SELECT c.course_id, c.title
    FROM Courses c
    JOIN Enrollments e ON c.course_id = e.course_id
    WHERE e.user_id = $user_id AND e.status = 'active'
    ORDER BY c.title
");
$courses = $coursesQuery->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Bài Tập Của Tôi - Học Tập Trực Tuyến</title>
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
        background-color: rgb(255, 255, 255);
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
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        animation: fadeIn 1s ease forwards;
    }

    /* Page title */
    .page-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 2rem;
        color: #1e3c72;
        margin-bottom: 25px;
        padding-bottom: 12px;
        border-bottom: 3px solid #FFC107;
        animation: fadeInDown 1s ease;
    }

    /* Filter container */
    .filters {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }

    .filter-label {
        font-weight: 700;
        color: #1e3c72;
    }

    .filter-select {
        padding: 8px 15px;
        border-radius: 6px;
        border: 1px solid #ddd;
        font-family: 'Nunito', sans-serif;
        background: white;
    }

    .filter-btn {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        cursor: pointer;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .filter-btn:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        transform: translateY(-2px);
    }

    /* Assignment cards */
    .assignment-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        border-left: 4px solid #FF8008;
        position: relative;
        transition: all 0.3s ease;
        animation: slideIn 0.5s ease forwards;
        animation-delay: calc(0.1s * var(--i));
    }

    .assignment-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    .assignment-title {
        color: #1e3c72;
        font-size: 1.4rem;
        margin-bottom: 10px;
        font-family: 'Montserrat', sans-serif;
    }

    .assignment-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 0.9rem;
    }

    .assignment-course {
        display: inline-block;
        background: #f0f0f0;
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 600;
        color: #1e3c72;
    }

    .assignment-due {
        color: #555;
    }

    .assignment-description {
        margin-bottom: 15px;
        color: #555;
    }

    .assignment-status {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .status-pending {
        background: #FFC107;
        color: black;
    }

    .status-overdue {
        background: #dc3545;
        color: white;
    }

    .status-submitted {
        background: #28a745;
        color: white;
    }

    .assignment-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .btn {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        text-align: center;
    }

    .btn-primary {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: linear-gradient(90deg, #FF8008, #FFA100);
        color: white;
    }

    .btn-secondary:hover {
        background: linear-gradient(90deg, #FFA100, #FF8008);
        transform: translateY(-2px);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
    }

    .pagination a {
        display: inline-block;
        padding: 8px 15px;
        background: linear-gradient(90deg, #FF8008, #FFA100);
        color: white;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .pagination a.active,
    .pagination a:hover {
        background: linear-gradient(90deg, #FFA100, #FF8008);
        transform: translateY(-3px);
    }

    /* No assignments message */
    .no-assignments {
        background: #f8f9fa;
        padding: 40px 20px;
        text-align: center;
        border-radius: 10px;
        color: #6c757d;
        font-size: 1.2rem;
        margin: 20px 0;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
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

    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-group {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .assignment-meta {
            flex-direction: column;
            gap: 10px;
        }
        
        .assignment-status {
            position: static;
            display: inline-block;
            margin-top: 10px;
        }
        
        .assignment-actions {
            flex-direction: column;
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
          <li><a href="student_dashboard.php">Dashboard</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <h1 class="page-title">Bài Tập Của Tôi</h1>
    
    <!-- Filters -->
    <div class="filters">
      <form method="GET" action="">
        <div class="filter-group">
          <div class="search-box">
            <span class="filter-label">Tìm kiếm:</span>
            <input type="text" name="search" placeholder="Nhập từ khóa..." class="filter-input" value="<?php echo htmlspecialchars($search_query); ?>">
          </div>
          
          <div>
            <span class="filter-label">Trạng thái:</span>
            <select name="status" class="filter-select">
              <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Tất cả</option>
              <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Đang chờ</option>
              <option value="overdue" <?php echo $status_filter == 'overdue' ? 'selected' : ''; ?>>Quá hạn</option>
            </select>
          </div>
          
          <div>
            <span class="filter-label">Khóa học:</span>
            <select name="course_id" class="filter-select">
              <option value="0">Tất cả khóa học</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course['title']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <span class="filter-label">Sắp xếp:</span>
            <select name="sort" class="filter-select">
              <option value="due_date_asc" <?php echo $sort_by == 'due_date_asc' ? 'selected' : ''; ?>>Hạn nộp - gần nhất trước</option>
              <option value="due_date_desc" <?php echo $sort_by == 'due_date_desc' ? 'selected' : ''; ?>>Hạn nộp - xa nhất trước</option>
              <option value="title_asc" <?php echo $sort_by == 'title_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
              <option value="title_desc" <?php echo $sort_by == 'title_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
              <option value="course_asc" <?php echo $sort_by == 'course_asc' ? 'selected' : ''; ?>>Khóa học</option>
            </select>
          </div>
          
          <button type="submit" class="filter-btn">Lọc bài tập</button>
          <a href="assignments.php" class="filter-reset">Xóa bộ lọc</a>
        </div>
      </form>
    </div>
    
    <!-- Thống kê tổng quan -->
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-icon pending-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
          <h3>Đang chờ</h3>
          <p><?php 
            try {
              if ($tableExists) {
                $pendingQuery = "SELECT COUNT(*) as count FROM Assignments a 
                  JOIN Enrollments e ON a.course_id = e.course_id 
                  LEFT JOIN AssignmentSubmissions s ON a.assignment_id = s.assignment_id AND s.user_id = $user_id 
                  WHERE e.user_id = $user_id AND e.status = 'active' AND s.submission_id IS NULL AND a.due_date >= CURDATE()";
                $pendingResult = $conn->query($pendingQuery);
                if ($pendingResult) {
                  $pendingCount = $pendingResult->fetch_assoc()['count'];
                } else {
                  // If query fails, count assignments without submission status
                  $pendingCount = $conn->query("SELECT COUNT(*) as count FROM Assignments a 
                    JOIN Enrollments e ON a.course_id = e.course_id 
                    WHERE e.user_id = $user_id AND e.status = 'active' AND a.due_date >= CURDATE()")->fetch_assoc()['count'];
                }
              } else {
                // If table doesn't exist, just count pending assignments
                $pendingCount = $conn->query("SELECT COUNT(*) as count FROM Assignments a 
                  JOIN Enrollments e ON a.course_id = e.course_id 
                  WHERE e.user_id = $user_id AND e.status = 'active' AND a.due_date >= CURDATE()")->fetch_assoc()['count'];
              }
            } catch (Exception $e) {
              $pendingCount = 0; // Fallback if query fails
            }
            echo $pendingCount;
          ?></p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon overdue-icon">
          <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-info">
          <h3>Quá hạn</h3>
          <p><?php 
            try {
              if ($tableExists) {
                $overdueQuery = "SELECT COUNT(*) as count FROM Assignments a 
                  JOIN Enrollments e ON a.course_id = e.course_id 
                  LEFT JOIN AssignmentSubmissions s ON a.assignment_id = s.assignment_id AND s.user_id = $user_id 
                  WHERE e.user_id = $user_id AND e.status = 'active' AND s.submission_id IS NULL AND a.due_date < CURDATE()";
                $overdueResult = $conn->query($overdueQuery);
                if ($overdueResult) {
                  $overdueCount = $overdueResult->fetch_assoc()['count'];
                } else {
                  // If query fails, count assignments without submission status
                  $overdueCount = $conn->query("SELECT COUNT(*) as count FROM Assignments a 
                    JOIN Enrollments e ON a.course_id = e.course_id 
                    WHERE e.user_id = $user_id AND e.status = 'active' AND a.due_date < CURDATE()")->fetch_assoc()['count'];
                }
              } else {
                // If table doesn't exist, just count overdue assignments
                $overdueCount = $conn->query("SELECT COUNT(*) as count FROM Assignments a 
                  JOIN Enrollments e ON a.course_id = e.course_id 
                  WHERE e.user_id = $user_id AND e.status = 'active' AND a.due_date < CURDATE()")->fetch_assoc()['count'];
              }
            } catch (Exception $e) {
              $overdueCount = 0; // Fallback if query fails
            }
            echo $overdueCount;
          ?></p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon completed-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <h3>Đã nộp</h3>
          <p><?php 
            $completedCount = 0;
            try {
              if ($tableExists) {
                $completedResult = $conn->query("SELECT COUNT(*) as count FROM AssignmentSubmissions WHERE user_id = $user_id");
                if ($completedResult) {
                  $completedCount = $completedResult->fetch_assoc()['count'];
                }
              }
            } catch (Exception $e) {
              // Fallback if query fails
            }
            echo $completedCount;
          ?></p>
        </div>
      </div>
    </div>
    
    <!-- Assignment List -->
    <?php if ($assignments->num_rows > 0): ?>
      <?php 
      $i = 0;
      while ($assignment = $assignments->fetch_assoc()): 
        $i++;
        $is_submitted = $assignment['submitted'] > 0;
        $is_overdue = strtotime($assignment['due_date']) < time();
        
        if ($is_submitted) {
          $status_class = "status-submitted";
          $status_text = "Đã nộp";
        } elseif ($is_overdue) {
          $status_class = "status-overdue";
          $status_text = "Quá hạn";
        } else {
          $status_class = "status-pending";
          $status_text = "Đang chờ";
        }
        
        // Tính số ngày còn lại
        $days_left = "";
        if (!$is_submitted && !$is_overdue) {
          $today = new DateTime();
          $due_date = new DateTime($assignment['due_date']);
          $interval = $today->diff($due_date);
          $days_left = $interval->days;
        }
      ?>
        <div class="assignment-card" style="--i:<?php echo $i; ?>">
          <span class="assignment-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
          <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
          
          <div class="assignment-meta">
            <span class="assignment-course"><?php echo htmlspecialchars($assignment['course_title']); ?></span>
            <span class="assignment-due">
              <strong>Hạn nộp:</strong> <?php echo date('d/m/Y', strtotime($assignment['due_date'])); ?>
              <?php if (!empty($days_left)): ?>
                <span class="days-left">(còn <?php echo $days_left; ?> ngày)</span>
              <?php endif; ?>
            </span>
          </div>
          
          <p class="assignment-description"><?php echo htmlspecialchars($assignment['description']); ?></p>
          
          <?php if (isset($assignment['max_points']) && $assignment['max_points'] > 0): ?>
          <div class="assignment-progress">
            <div class="progress-info">
              <span>Điểm: 
                <?php if ($is_submitted): ?>
                  <?php
                    // Get grade with a separate query instead of in the main query
                    $grade = null;
                    if ($tableExists) {
                      $gradeQuery = "SELECT grade FROM AssignmentSubmissions 
                                    WHERE assignment_id = ? AND user_id = ? 
                                    ORDER BY submission_date DESC LIMIT 1";
                      $gradeStmt = $conn->prepare($gradeQuery);
                      $gradeStmt->bind_param("ii", $assignment['assignment_id'], $user_id);
                      $gradeStmt->execute();
                      $gradeResult = $gradeStmt->get_result();
                      if ($gradeResult && $gradeResult->num_rows > 0) {
                        $grade = $gradeResult->fetch_assoc()['grade'];
                      }
                    }
                  ?>
                  <strong><?php echo $grade !== null ? $grade : '-'; ?>/<?php echo $assignment['max_points']; ?></strong>
                <?php else: ?>
                  <strong>-/<?php echo $assignment['max_points']; ?></strong>
                <?php endif; ?>
              </span>
            </div>
            <?php if ($is_submitted && $grade !== null): ?>
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo ($grade / $assignment['max_points']) * 100; ?>%"></div>
              </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          
          <div class="assignment-actions">
            <a href="assignment_detail.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-primary">Xem chi tiết</a>
            <?php if (!$is_submitted): ?>
              <a href="submit_assignment.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-secondary">Nộp bài</a>
            <?php else: ?>
              <a href="view_submission.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-secondary">Xem bài đã nộp</a>
            <?php endif; ?>
            <button class="btn-bookmark" data-id="<?php echo $assignment['assignment_id']; ?>" title="Đánh dấu bài tập">⭐</button>
          </div>
        </div>
      <?php endwhile; ?>
      
      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status_filter; ?>&course_id=<?php echo $course_filter; ?>&search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>" class="pagination-arrow">«</a>
          <?php endif; ?>
          
          <?php
          // Hiển thị tối đa 5 trang
          $start_page = max(1, $page - 2);
          $end_page = min($totalPages, $start_page + 4);
          if ($end_page - $start_page < 4 && $start_page > 1) {
            $start_page = max(1, $end_page - 4);
          }
          
          for ($i = $start_page; $i <= $end_page; $i++): 
          ?>
            <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&course_id=<?php echo $course_filter; ?>&search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>" 
               <?php if ($i == $page) echo 'class="active"'; ?>>
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
          
          <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status_filter; ?>&course_id=<?php echo $course_filter; ?>&search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>" class="pagination-arrow">»</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
    <?php else: ?>
      <div class="no-assignments">
        <h3>Không tìm thấy bài tập nào</h3>
        <p>Hãy thử thay đổi bộ lọc hoặc kiểm tra lại sau.</p>
      </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px; text-align: center;">
      <a href="student_dashboard.php" class="btn btn-primary">Quay lại Dashboard</a>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>
  
  <!-- Thêm Font Awesome để sử dụng icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    /* Các style mới thêm vào */
    .filter-input {
      padding: 8px 15px;
      border-radius: 6px;
      border: 1px solid #ddd;
      font-family: 'Nunito', sans-serif;
      width: 250px;
      background: white;
    }
    
    .filter-reset {
      padding: 8px 15px;
      border-radius: 6px;
      background: #f8f9fa;
      color: #6c757d;
      text-decoration: none;
      font-weight: 600;
      border: 1px solid #ddd;
      transition: all 0.3s ease;
    }
    
    .filter-reset:hover {
      background: #e9ecef;
      color: #343a40;
    }
    
    .stats-container {
      display: flex;
      gap: 20px;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }
    
    .stat-card {
      flex: 1;
      min-width: 200px;
      background: white;
      border-radius: 10px;
      padding: 15px;
      display: flex;
      align-items: center;
      gap: 15px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    }
    
    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }
    
    .pending-icon {
      background: #FFC107;
      color: black;
    }
    
    .overdue-icon {
      background: #dc3545;
      color: white;
    }
    
    .completed-icon {
      background: #28a745;
      color: white;
    }
    
    .stat-info h3 {
      font-size: 0.9rem;
      margin-bottom: 5px;
      color: #6c757d;
    }
    
    .stat-info p {
      font-size: 1.6rem;
      font-weight: 700;
      color: #343a40;
      margin: 0;
    }
    
    .days-left {
      font-size: 0.9rem;
      color: #dc3545;
      font-weight: 700;
    }
    
    .assignment-progress {
      margin: 15px 0;
    }
    
    .progress-info {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
      font-size: 0.9rem;
    }
    
    .progress-bar {
      height: 8px;
      background: #f0f0f0;
      border-radius: 4px;
      overflow: hidden;
    }
    
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #1e3c72, #2a5298);
    }
    
    .btn-bookmark {
      background: none;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      color: #FFC107;
      padding: 5px;
      margin-left: auto;
      transition: all 0.3s ease;
    }
    
    .btn-bookmark:hover {
      transform: scale(1.2);
    }
    
    .pagination-arrow {
      font-weight: 800;
    }
    
    /* Thiết bị nhỏ hơn */
    @media (max-width: 576px) {
      .filter-input {
        width: 100%;
      }
      
      .stats-container {
        flex-direction: column;
      }
      
      .stat-card {
        min-width: 100%;
      }
      
      .days-left {
        display: block;
      }
    }
  </style>
  
  <script>
    // JavaScript để đánh dấu bài tập yêu thích
    document.addEventListener('DOMContentLoaded', function() {
      // Lấy danh sách đánh dấu từ localStorage
      let bookmarks = JSON.parse(localStorage.getItem('assignment_bookmarks')) || [];
      
      // Cập nhật trạng thái ban đầu của các nút bookmark
      document.querySelectorAll('.btn-bookmark').forEach(btn => {
        const assignmentId = btn.getAttribute('data-id');
        if (bookmarks.includes(assignmentId)) {
          btn.style.color = '#FFC107';
          btn.textContent = '★';
        } else {
          btn.style.color = '#ccc';
          btn.textContent = '☆';
        }
        
        // Thêm sự kiện click
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const id = this.getAttribute('data-id');
          
          // Toggle trạng thái đánh dấu
          if (bookmarks.includes(id)) {
            bookmarks = bookmarks.filter(item => item !== id);
            this.style.color = '#ccc';
            this.textContent = '☆';
          } else {
            bookmarks.push(id);
            this.style.color = '#FFC107';
            this.textContent = '★';
          }
          
          // Lưu vào localStorage
          localStorage.setItem('assignment_bookmarks', JSON.stringify(bookmarks));
        });
      });
    });
  </script>
</body>
</html> 