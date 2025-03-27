<?php
// Assignment list page
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
         <p>Go to <a href="/WebCourses/setup_db.php">Database Setup</a> to create the database and tables.</p>
         </div>');
}

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để xem bài tập.";
    header("Location: " . BASE_URL . "app/views/product/home.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';

// Check if assignment_submissions table exists
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'assignment_submissions'");
if ($checkTable && $checkTable->num_rows > 0) {
    $tableExists = true;
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter by status
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$status_condition = '';

if ($status == 'pending') {
    $status_condition = " AND (a.due_date >= NOW() OR a.due_date IS NULL)";
} elseif ($status == 'overdue') {
    $status_condition = " AND a.due_date < NOW()";
}

// Filter by course
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$course_condition = '';

if ($course_id > 0) {
    $course_condition = " AND c.id = " . $course_id;
}

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $search_condition = " AND (a.title LIKE ? OR a.description LIKE ?)";
}

// Sort by
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'due_date_asc';
$order_clause = "a.due_date ASC";

if ($sort_by == 'due_date_desc') {
    $order_clause = "a.due_date DESC";
} elseif ($sort_by == 'title_asc') {
    $order_clause = "a.title ASC";
} elseif ($sort_by == 'title_desc') {
    $order_clause = "a.title DESC";
} elseif ($sort_by == 'course_asc') {
    $order_clause = "c.title ASC, a.due_date ASC";
}

// Get assignments
try {
    if ($tableExists) {
        // Use the submission count if the table exists
        $sql = "
            SELECT 
                a.id as assignment_id, 
                a.title, 
                a.description, 
                a.due_date, 
                a.max_points,
                c.title as course_title, 
                c.id as course_id,
                (SELECT s.id FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.user_id = ? LIMIT 1) as submission_id,
                (SELECT s.grade FROM assignment_submissions s WHERE s.assignment_id = a.id AND s.user_id = ? LIMIT 1) as grade
            FROM 
                assignments a
            JOIN 
                courses c ON a.course_id = c.id
            JOIN 
                enrollments e ON c.id = e.course_id
            WHERE 
                e.user_id = ? AND e.status = 'active'
                $status_condition
                $course_condition
        ";
        
        if (!empty($search)) {
            $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
            $stmt = $conn->prepare($sql . " ORDER BY $order_clause LIMIT ? OFFSET ?");
            $search_param = "%$search%";
            $stmt->bind_param("iiissii", $user_id, $user_id, $user_id, $search_param, $search_param, $limit, $offset);
        } else {
            $stmt = $conn->prepare($sql . " ORDER BY $order_clause LIMIT ? OFFSET ?");
            $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $limit, $offset);
        }
    } else {
        // Without submission data if the table doesn't exist yet
        $sql = "
            SELECT a.id as assignment_id, a.title, a.description, a.due_date, a.max_points,
                c.title as course_title, c.id as course_id,
                NULL as submission_id, NULL as grade
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            JOIN enrollments e ON c.id = e.course_id
            WHERE e.user_id = ? AND e.status = 'active'
                $status_condition
                $course_condition
        ";
        
        if (!empty($search)) {
            $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
            $stmt = $conn->prepare($sql . " ORDER BY $order_clause LIMIT ? OFFSET ?");
            $search_param = "%$search%";
            $stmt->bind_param("issii", $user_id, $search_param, $search_param, $limit, $offset);
        } else {
            $stmt = $conn->prepare($sql . " ORDER BY $order_clause LIMIT ? OFFSET ?");
            $stmt->bind_param("iii", $user_id, $limit, $offset);
        }
    }

    $stmt->execute();
    $assignments = $stmt->get_result();
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN enrollments e ON c.id = e.course_id
        WHERE e.user_id = ? AND e.status = 'active'
            $status_condition
            $course_condition
    ";
    
    if (!empty($search)) {
        $count_sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
        $count_stmt = $conn->prepare($count_sql);
        $search_param = "%$search%";
        $count_stmt->bind_param("iss", $user_id, $search_param, $search_param);
    } else {
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $user_id);
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_assignments = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_assignments / $limit);
    
} catch (Exception $e) {
    $error_message = "Đã xảy ra lỗi khi tải danh sách bài tập: " . $e->getMessage();
    $assignments = false;
    $total_pages = 0;
}

// Get courses for filter dropdown
$courses_query = "
    SELECT c.id as course_id, c.title, COUNT(a.id) as assignment_count
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
    <title>Danh Sách Bài Tập - Học Tập Trực Tuyến</title>
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

        /* Main container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        /* Sidebar */
        .sidebar {
            flex: 1;
            min-width: 250px;
            max-width: 300px;
        }

        /* Main content */
        .main-content {
            flex: 3;
            min-width: 0;
        }

        .page-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #1e3c72;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 3px solid #FFC107;
        }

        /* Card styling */
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 1.3rem;
            color: #1e3c72;
            margin-bottom: 15px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Filter options */
        .filter-section {
            margin-bottom: 10px;
        }

        .filter-section label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e3c72;
        }

        .filter-section select,
        .filter-section input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Nunito', sans-serif;
            margin-bottom: 15px;
        }

        .filter-button {
            width: 100%;
            padding: 10px;
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .filter-button:hover {
            background: linear-gradient(90deg, #2a5298, #1e3c72);
            transform: translateY(-2px);
        }

        /* Assignment list */
        .assignment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .assignment-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .assignment-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .assignment-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 5px;
            font-family: 'Montserrat', sans-serif;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 2.8em;
        }

        .assignment-course {
            display: inline-block;
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: #1e3c72;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .assignment-body {
            padding: 15px 20px;
        }

        .assignment-description {
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 4.8em;
            color: #555;
        }

        .assignment-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
        }

        .assignment-meta i {
            margin-right: 5px;
        }

        .assignment-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #FFC107;
            color: #000;
        }

        .badge-submitted {
            background: #28a745;
            color: #fff;
        }

        .badge-graded {
            background: #17a2b8;
            color: #fff;
        }

        .badge-overdue {
            background: #dc3545;
            color: #fff;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2a5298, #1e3c72);
            transform: translateY(-2px);
        }

        /* Status indicator */
        .status-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .indicator-pending {
            background: #FFC107;
        }

        .indicator-submitted {
            background: #28a745;
        }

        .indicator-graded {
            background: #17a2b8;
        }

        .indicator-overdue {
            background: #dc3545;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            margin-top: 30px;
        }

        .pagination li {
            margin: 0 5px;
        }

        .pagination li a,
        .pagination li span {
            display: block;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            color: #1e3c72;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .pagination li a:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .pagination li.active span {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #adb5bd;
        }

        .empty-state p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                max-width: 100%;
            }
            
            .assignment-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
                    <li><a href="student_dashboard.php">Dashboard</a></li>
                    <li><a href="assignments.php">Bài Tập</a></li>
                    <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Sidebar with filters -->
        <aside class="sidebar">
            <div class="card">
                <h3 class="card-title">Bộ Lọc</h3>
                <form action="" method="GET">
                    <div class="filter-section">
                        <label for="status">Trạng thái</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Chưa đến hạn</option>
                            <option value="overdue" <?php echo $status == 'overdue' ? 'selected' : ''; ?>>Quá hạn</option>
                        </select>
                    </div>
                    
                    <div class="filter-section">
                        <label for="course_id">Khóa học</label>
                        <select name="course_id" id="course_id">
                            <option value="0">Tất cả khóa học</option>
                            <?php if ($courses): while ($course = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $course['course_id']; ?>" <?php echo $course_id == $course['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['title']); ?> (<?php echo $course['assignment_count']; ?>)
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    
                    <div class="filter-section">
                        <label for="sort">Sắp xếp theo</label>
                        <select name="sort" id="sort">
                            <option value="due_date_asc" <?php echo $sort_by == 'due_date_asc' ? 'selected' : ''; ?>>Hạn nộp (gần nhất)</option>
                            <option value="due_date_desc" <?php echo $sort_by == 'due_date_desc' ? 'selected' : ''; ?>>Hạn nộp (xa nhất)</option>
                            <option value="title_asc" <?php echo $sort_by == 'title_asc' ? 'selected' : ''; ?>>Tên (A-Z)</option>
                            <option value="title_desc" <?php echo $sort_by == 'title_desc' ? 'selected' : ''; ?>>Tên (Z-A)</option>
                            <option value="course_asc" <?php echo $sort_by == 'course_asc' ? 'selected' : ''; ?>>Khóa học</option>
                        </select>
                    </div>
                    
                    <div class="filter-section">
                        <label for="search">Tìm kiếm</label>
                        <input type="text" name="search" id="search" placeholder="Nhập từ khóa..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <button type="submit" class="filter-button">Áp dụng bộ lọc</button>
                </form>
            </div>
        </aside>
        
        <!-- Main content -->
        <main class="main-content">
            <h1 class="page-title">Danh Sách Bài Tập</h1>
            
            <div style="margin-bottom: 20px;">
                <a href="<?php echo BASE_URL; ?>app/views/product/assignment_list.php" style="display: inline-block; padding: 10px 15px; background-color: #1e3c72; color: white; text-decoration: none; border-radius: 5px; font-weight: 600;">
                    <i class="fas fa-th-list"></i> Xem giao diện danh sách bài tập mới
                </a>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($assignments && $assignments->num_rows > 0): ?>
                <div class="assignment-grid">
                    <?php while ($assignment = $assignments->fetch_assoc()): 
                        $is_submitted = !is_null($assignment['submission_id']);
                        $is_graded = !is_null($assignment['grade']);
                        $is_overdue = strtotime($assignment['due_date']) < time();
                        
                        // Determine status for styling
                        $status_class = 'pending';
                        $status_text = 'Chưa nộp';
                        
                        if ($is_graded) {
                            $status_class = 'graded';
                            $status_text = 'Đã chấm';
                        } elseif ($is_submitted) {
                            $status_class = 'submitted';
                            $status_text = 'Đã nộp';
                        } elseif ($is_overdue) {
                            $status_class = 'overdue';
                            $status_text = 'Quá hạn';
                        }
                    ?>
                        <div class="assignment-card">
                            <div class="status-indicator indicator-<?php echo $status_class; ?>"></div>
                            
                            <div class="assignment-header">
                                <span class="assignment-course"><?php echo htmlspecialchars($assignment['course_title']); ?></span>
                                <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                            </div>
                            
                            <div class="assignment-body">
                                <div class="assignment-description">
                                    <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                                </div>
                                
                                <div class="assignment-meta">
                                    <div><i class="fas fa-calendar-alt"></i> Hạn nộp: <?php echo date('d/m/Y', strtotime($assignment['due_date'])); ?></div>
                                    <div><i class="fas fa-star"></i> Điểm: <?php echo $is_graded ? $assignment['grade'] : '-'; ?>/<?php echo $assignment['max_points']; ?></div>
                                </div>
                            </div>
                            
                            <div class="assignment-footer">
                                <span class="status-badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                <a href="submit_assignment.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-primary">
                                    <?php echo $is_submitted ? 'Xem bài nộp' : 'Nộp bài'; ?>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li><a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status != 'all' ? '&status=' . $status : ''; ?><?php echo $course_id > 0 ? '&course_id=' . $course_id : ''; ?><?php echo $sort_by != 'due_date_asc' ? '&sort=' . $sort_by : ''; ?>">Đầu</a></li>
                            <li><a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status != 'all' ? '&status=' . $status : ''; ?><?php echo $course_id > 0 ? '&course_id=' . $course_id : ''; ?><?php echo $sort_by != 'due_date_asc' ? '&sort=' . $sort_by : ''; ?>">Trước</a></li>
                        <?php endif; ?>
                        
                        <?php
                        // Show limited page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php if ($i == $page): ?>
                                    <span><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status != 'all' ? '&status=' . $status : ''; ?><?php echo $course_id > 0 ? '&course_id=' . $course_id : ''; ?><?php echo $sort_by != 'due_date_asc' ? '&sort=' . $sort_by : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li><a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status != 'all' ? '&status=' . $status : ''; ?><?php echo $course_id > 0 ? '&course_id=' . $course_id : ''; ?><?php echo $sort_by != 'due_date_asc' ? '&sort=' . $sort_by : ''; ?>">Tiếp</a></li>
                            <li><a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status != 'all' ? '&status=' . $status : ''; ?><?php echo $course_id > 0 ? '&course_id=' . $course_id : ''; ?><?php echo $sort_by != 'due_date_asc' ? '&sort=' . $sort_by : ''; ?>">Cuối</a></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tasks"></i>
                    <p>Không tìm thấy bài tập nào phù hợp với bộ lọc hiện tại.</p>
                    <a href="assignments.php" class="btn btn-primary">Xem tất cả bài tập</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Footer -->
    <footer>
        <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
    </footer>
</body>
</html> 