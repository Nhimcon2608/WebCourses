<?php
// quizzes.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để xem trắc nghiệm.";
  header("Location: " . BASE_URL . "app/views/product/home.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Kiểm tra và tạo các bảng cơ sở dữ liệu cần thiết
try {
  // Tạo bảng quizzes nếu chưa tồn tại
  $conn->query("
    CREATE TABLE IF NOT EXISTS quizzes (
      quiz_id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      description TEXT,
      course_id INT NOT NULL,
      lesson_id INT,
      time_limit INT DEFAULT 30,
      total_questions INT DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (course_id),
      INDEX (lesson_id)
    ) ENGINE=InnoDB
  ");
  
  // Tạo bảng quiz_questions nếu chưa tồn tại
  $conn->query("
    CREATE TABLE IF NOT EXISTS quiz_questions (
      question_id INT AUTO_INCREMENT PRIMARY KEY,
      quiz_id INT NOT NULL,
      question_text TEXT NOT NULL,
      option_a TEXT NOT NULL,
      option_b TEXT NOT NULL,
      option_c TEXT,
      option_d TEXT,
      correct_option CHAR(1) NOT NULL,
      points FLOAT DEFAULT 1.0,
      INDEX (quiz_id),
      FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
    ) ENGINE=InnoDB
  ");
  
  // Kiểm tra bảng quiz_attempts có tồn tại chưa
  $tableExists = false;
  $checkTable = $conn->query("SHOW TABLES LIKE 'quiz_attempts'");
  if ($checkTable && $checkTable->rowCount() > 0) {
      $tableExists = true;
  }
  
  // Tạo bảng nếu chưa tồn tại - sử dụng IF NOT EXISTS để tránh lỗi
  if (!$tableExists) {
    $conn->query("
      CREATE TABLE IF NOT EXISTS quiz_attempts (
        attempt_id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        user_id INT NOT NULL,
        start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        end_time DATETIME NULL,
        score FLOAT NULL,
        max_score FLOAT NOT NULL DEFAULT 0,
        completed BOOLEAN DEFAULT FALSE,
        INDEX (quiz_id),
        INDEX (user_id),
        FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
      ) ENGINE=InnoDB
    ");
  }
  
  // Tạo bảng quiz_attempt_answers nếu chưa tồn tại
  $tableExists = false;
  $checkTable = $conn->query("SHOW TABLES LIKE 'quiz_attempt_answers'");
  if ($checkTable && $checkTable->rowCount() > 0) {
      $tableExists = true;
  }
  
  if (!$tableExists) {
    $conn->query("
      CREATE TABLE IF NOT EXISTS quiz_attempt_answers (
        answer_id INT AUTO_INCREMENT PRIMARY KEY,
        attempt_id INT NOT NULL,
        question_id INT NOT NULL,
        selected_option CHAR(1),
        is_correct BOOLEAN,
        score FLOAT DEFAULT 0,
        INDEX (attempt_id),
        INDEX (question_id),
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(attempt_id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id) ON DELETE CASCADE
      ) ENGINE=InnoDB
    ");
  }
} catch (PDOException $e) {
  echo "<div class='alert alert-danger'>Lỗi khi tạo bảng dữ liệu: " . $e->getMessage() . "</div>";
}

// Handle gracefully if tables don't exist
try {
  // Xử lý bộ lọc
  $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
  $course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
  
  // Tính tổng số trang
  $itemsPerPage = 10;
  $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
  $page = max(1, $page); // Đảm bảo page ít nhất là 1
  $offset = ($page - 1) * $itemsPerPage;
  
  // Kiểm tra bảng quizzes tồn tại
  $checkQuizzes = $conn->query("SHOW TABLES LIKE 'quizzes'");
  $quizzesExist = ($checkQuizzes && $checkQuizzes->rowCount() > 0);
  
  // Lấy các khóa học mà sinh viên đã đăng ký
  $coursesResult = [];
  $quizzes = [];
  $totalPages = 0;
  
  if ($quizzesExist) {
    $coursesStmt = $conn->prepare("
        SELECT c.course_id, c.title 
        FROM Courses c
        JOIN Enrollments e ON c.course_id = e.course_id
        WHERE e.user_id = ? AND e.status = 'active'
        ORDER BY c.title
    ");
    $coursesStmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt;
    
    // Xây dựng câu truy vấn lấy trắc nghiệm
    $query = "
        SELECT q.quiz_id, q.title, q.time_limit, q.total_questions, 
               c.title as course_title, c.course_id,
               l.title as lesson_title, l.lesson_id,
               CASE 
                   WHEN qa.completed = 1 THEN 'completed'
                   WHEN qa.completed = 0 AND qa.attempt_id IS NOT NULL THEN 'in_progress'
                   ELSE 'not_started'
               END as status,
               qa.score, qa.max_score
        FROM quizzes q
        JOIN courses c ON q.course_id = c.course_id
        LEFT JOIN lessons l ON q.lesson_id = l.lesson_id
        JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN (
            SELECT qa1.* 
            FROM quiz_attempts qa1
            INNER JOIN (
                SELECT quiz_id, user_id, MAX(attempt_id) as latest_attempt_id
                FROM quiz_attempts
                WHERE user_id = ?
                GROUP BY quiz_id, user_id
            ) qa2 ON qa1.quiz_id = qa2.quiz_id AND qa1.attempt_id = qa2.latest_attempt_id AND qa1.user_id = qa2.user_id
        ) qa ON q.quiz_id = qa.quiz_id
        WHERE e.user_id = ? AND e.status = 'active'
    ";
    
    // Áp dụng các bộ lọc
    if ($course_filter > 0) {
        $query .= " AND c.course_id = ?";
    }
    
    if ($status_filter == 'completed') {
        $query .= " AND qa.completed = 1";
    } else if ($status_filter == 'not_started') {
        $query .= " AND (qa.attempt_id IS NULL)";
    } else if ($status_filter == 'in_progress') {
        $query .= " AND (qa.completed = 0 AND qa.attempt_id IS NOT NULL)";
    }
    
    // Đếm tổng số bản ghi để phân trang
    $countQuery = str_replace("SELECT q.quiz_id, q.title, q.time_limit, q.total_questions, 
               c.title as course_title, c.course_id,
               l.title as lesson_title, l.lesson_id,
               CASE 
                   WHEN qa.completed = 1 THEN 'completed'
                   WHEN qa.completed = 0 AND qa.attempt_id IS NOT NULL THEN 'in_progress'
                   ELSE 'not_started'
               END as status,
               qa.score, qa.max_score", "SELECT COUNT(*)", $query);
    
    $stmt = $conn->prepare($countQuery);
    if ($course_filter > 0) {
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $course_filter, PDO::PARAM_INT);
    } else {
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $totalCount = $stmt->fetch(PDO::FETCH_NUM)[0];
    $totalPages = ceil($totalCount / $itemsPerPage);
    
    // Hoàn thiện query với ORDER BY và LIMIT
    $query .= " ORDER BY CASE 
                    WHEN qa.completed = 0 AND qa.attempt_id IS NOT NULL THEN 1
                    WHEN qa.attempt_id IS NULL THEN 2
                    ELSE 3
                END, 
                q.quiz_id DESC 
                LIMIT ?, ?";
    
    // Lấy danh sách trắc nghiệm
    $stmt = $conn->prepare($query);
    if ($course_filter > 0) {
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $course_filter, PDO::PARAM_INT);
        $stmt->bindParam(4, $offset, PDO::PARAM_INT);
        $stmt->bindParam(5, $itemsPerPage, PDO::PARAM_INT);
    } else {
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->bindParam(4, $itemsPerPage, PDO::PARAM_INT);
    }
    $stmt->execute();
    $quizzes = $stmt;
  } else {
    echo "<div class='alert alert-warning'>Chưa có bài trắc nghiệm nào trong hệ thống. Vui lòng quay lại sau!</div>";
  }
} catch (PDOException $e) {
  echo "<div class='alert alert-danger'>Lỗi khi truy vấn dữ liệu: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Bài Trắc Nghiệm Của Tôi</title>
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
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    /* Page title */
    .page-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 2.5rem;
        color: #1e3c72;
        margin-bottom: 30px;
        text-align: center;
        font-weight: 700;
        animation: fadeIn 1s ease-in-out;
    }

    /* Filter section */
    .filter-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .filter-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.2rem;
        margin-bottom: 15px;
        color: #1e3c72;
    }

    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 15px;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
    }

    .filter-select {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-family: 'Nunito', sans-serif;
        transition: all 0.3s ease;
    }

    .filter-select:focus {
        border-color: #1e3c72;
        outline: none;
        box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.2);
    }

    .filter-button {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        box-shadow: 0 4px 8px rgba(30, 60, 114, 0.2);
    }

    .filter-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(30, 60, 114, 0.3);
    }

    /* Quiz cards */
    .quiz-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .quiz-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .quiz-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .quiz-status {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        z-index: 2;
    }

    .status-not-started {
        background: #FFC107;
        color: #000;
    }

    .status-in-progress {
        background: #17a2b8;
        color: #fff;
    }

    .status-completed {
        background: #28a745;
        color: #fff;
    }

    .quiz-header {
        background: #f8f9fa;
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    .quiz-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.2rem;
        margin-bottom: 5px;
        color: #1e3c72;
        padding-right: 80px; /* Space for status badge */
    }

    .quiz-course {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .quiz-body {
        padding: 15px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .quiz-info {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }

    .info-item {
        flex: 1;
        min-width: 120px;
        margin-bottom: 10px;
    }

    .info-label {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 3px;
        display: block;
    }

    .info-value {
        font-weight: 600;
        color: #444;
    }

    .quiz-score {
        display: flex;
        align-items: center;
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }

    .score-badge {
        width: 50px;
        height: 50px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #1e3c72;
        margin-right: 15px;
        border: 2px solid #1e3c72;
    }

    .quiz-footer {
        padding: 15px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
    }

    .quiz-btn {
        flex: 1;
        padding: 10px 15px;
        border-radius: 6px;
        text-align: center;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
    }

    .btn-primary {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
        margin-right: 10px;
        box-shadow: 0 2px 5px rgba(30, 60, 114, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(30, 60, 114, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(90deg, #FF8008, #FFA100);
        color: white;
        box-shadow: 0 2px 5px rgba(255, 128, 8, 0.3);
    }

    .btn-secondary:hover {
        background: linear-gradient(90deg, #FFA100, #FF8008);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(255, 128, 8, 0.4);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        list-style: none;
        margin: 30px 0;
    }

    .pagination li {
        margin: 0 5px;
    }

    .pagination a, .pagination span {
        display: block;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.3s ease;
        color: #1e3c72;
        background: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        font-weight: 600;
    }

    .pagination a:hover {
        background: #1e3c72;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .pagination .active span {
        background: #1e3c72;
        color: white;
    }

    /* No quizzes message */
    .no-quizzes {
        text-align: center;
        padding: 50px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .no-quizzes-icon {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }

    .no-quizzes-text {
        font-size: 1.2rem;
        color: #777;
        margin-bottom: 20px;
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

    /* Responsive */
    @media (max-width: 768px) {
        .quiz-grid {
            grid-template-columns: 1fr;
        }

        .filter-row {
            flex-direction: column;
            gap: 15px;
        }

        .filter-group {
            width: 100%;
        }

        .quiz-footer {
            flex-direction: column;
            gap: 10px;
        }

        .quiz-btn {
            margin-right: 0;
            margin-bottom: 10px;
        }
    }

    /* Quiz info card */
    .quiz-info-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    /* New styles for gamification elements */
    .badges-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .badges-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5rem;
        color: #1e3c72;
        margin-bottom: 20px;
        text-align: center;
    }

    .badges-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }

    .badge-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 120px;
    }

    .badge-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #f5f7fa, #e5e9f0);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        font-size: 2rem;
        color: #6c757d;
        position: relative;
        transition: all 0.3s ease;
    }

    .badge-icon.earned {
        background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
        color: #1e3c72;
    }

    .badge-icon.earned:after {
        content: '✓';
        position: absolute;
        top: -5px;
        right: -5px;
        background: #28a745;
        color: white;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        border: 2px solid white;
    }

    .badge-name {
        font-weight: 600;
        font-size: 0.9rem;
        text-align: center;
        color: #6c757d;
    }

    .badge-description {
        font-size: 0.8rem;
        text-align: center;
        color: #6c757d;
    }

    .badge-icon.earned + .badge-name,
    .badge-icon.earned ~ .badge-description {
        color: #1e3c72;
    }

    .progress-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .progress-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5rem;
        color: #1e3c72;
        margin-bottom: 20px;
        text-align: center;
    }

    .progress-bar-container {
        width: 100%;
        height: 25px;
        background-color: #e9ecef;
        border-radius: 12px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #4facfe, #00f2fe);
        border-radius: 12px;
        transition: width 1s ease;
        position: relative;
    }

    .progress-stats {
        display: flex;
        justify-content: space-between;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .stat-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .confetti-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 9999;
        display: none;
    }
    
    /* Quiz card hover animation */
    .quiz-card {
        transition: all 0.4s ease;
        transform-style: preserve-3d;
    }
    
    .quiz-card:hover {
        transform: translateY(-8px) rotateY(5deg);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }
    
    /* Pulse animation for status badges */
    .status-in-progress {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(23, 162, 184, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(23, 162, 184, 0); }
        100% { box-shadow: 0 0 0 0 rgba(23, 162, 184, 0); }
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
    <h1 class="page-title">Bài Trắc Nghiệm Của Tôi</h1>
    
    <!-- Progress tracking section -->
    <div class="progress-section">
      <h2 class="progress-title">Tiến độ học tập của bạn</h2>
      <?php
      // Lấy tổng số trắc nghiệm
      $totalQuizzesStmt = $conn->prepare("
        SELECT COUNT(*) as total FROM quizzes q
        JOIN courses c ON q.course_id = c.course_id
        JOIN enrollments e ON c.course_id = e.course_id
        WHERE e.user_id = ? AND e.status = 'active'
      ");
      $totalQuizzesStmt->bindParam(1, $user_id, PDO::PARAM_INT);
      $totalQuizzesStmt->execute();
      $totalQuizzes = $totalQuizzesStmt->fetch(PDO::FETCH_ASSOC)['total'];
      
      // Lấy số trắc nghiệm đã hoàn thành
      $completedQuizzesStmt = $conn->prepare("
        SELECT COUNT(DISTINCT q.quiz_id) as completed 
        FROM quizzes q
        JOIN courses c ON q.course_id = c.course_id
        JOIN enrollments e ON c.course_id = e.course_id
        JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id AND qa.user_id = e.user_id
        WHERE e.user_id = ? AND e.status = 'active' AND qa.completed = 1
      ");
      $completedQuizzesStmt->bindParam(1, $user_id, PDO::PARAM_INT);
      $completedQuizzesStmt->execute();
      $completedQuizzes = $completedQuizzesStmt->fetch(PDO::FETCH_ASSOC)['completed'];
      
      // Tính phần trăm hoàn thành
      $completionPercentage = $totalQuizzes > 0 ? round(($completedQuizzes / $totalQuizzes) * 100) : 0;
      ?>
      
      <div class="progress-bar-container">
        <div class="progress-bar" style="width: <?php echo $completionPercentage; ?>%"></div>
      </div>
      <div class="progress-stats">
        <span>Đã hoàn thành: <?php echo $completedQuizzes; ?>/<?php echo $totalQuizzes; ?> bài trắc nghiệm</span>
        <span><?php echo $completionPercentage; ?>%</span>
      </div>
      
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value"><?php echo $completedQuizzes; ?></div>
          <div class="stat-label">Bài đã hoàn thành</div>
        </div>
        
        <?php
        // Tính điểm trung bình
        $avgScoreStmt = $conn->prepare("
          SELECT AVG(qa.score/qa.max_score*10) as avg_score
          FROM quiz_attempts qa
          WHERE qa.user_id = ? AND qa.completed = 1
        ");
        $avgScoreStmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $avgScoreStmt->execute();
        $avgScore = $avgScoreStmt->fetch(PDO::FETCH_ASSOC)['avg_score'];
        $avgScore = $avgScore ? round($avgScore, 1) : 0;
        ?>
        <div class="stat-card">
          <div class="stat-value"><?php echo $avgScore; ?></div>
          <div class="stat-label">Điểm trung bình</div>
        </div>
        
        <?php
        // Đếm bài đang làm
        $inProgressStmt = $conn->prepare("
          SELECT COUNT(DISTINCT q.quiz_id) as in_progress
          FROM quizzes q
          JOIN courses c ON q.course_id = c.course_id
          JOIN enrollments e ON c.course_id = e.course_id
          JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id AND qa.user_id = e.user_id
          WHERE e.user_id = ? AND e.status = 'active' AND qa.completed = 0
        ");
        $inProgressStmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $inProgressStmt->execute();
        $inProgress = $inProgressStmt->fetch(PDO::FETCH_ASSOC)['in_progress'];
        ?>
        <div class="stat-card">
          <div class="stat-value"><?php echo $inProgress; ?></div>
          <div class="stat-label">Bài đang làm</div>
        </div>
        
        <?php
        // Đếm bài chưa làm
        $notStarted = $totalQuizzes - $completedQuizzes - $inProgress;
        ?>
        <div class="stat-card">
          <div class="stat-value"><?php echo $notStarted; ?></div>
          <div class="stat-label">Bài chưa làm</div>
        </div>
      </div>
    </div>
    
    <!-- Badges section -->
    <div class="badges-section">
      <h2 class="badges-title">Thành tích của bạn</h2>
      
      <div class="badges-container">
        <?php
        // Kiểm tra các huy hiệu
        $hasFirstQuiz = $completedQuizzes > 0;
        $has5Quizzes = $completedQuizzes >= 5;
        $has10Quizzes = $completedQuizzes >= 10;
        
        // Kiểm tra điểm cao
        $hasHighScore = false;
        $perfectScore = false;
        
        $highScoreStmt = $conn->prepare("
          SELECT COUNT(*) as count
          FROM quiz_attempts qa
          WHERE qa.user_id = ? AND qa.completed = 1 AND (qa.score/qa.max_score) >= 0.8
        ");
        $highScoreStmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $highScoreStmt->execute();
        $hasHighScore = $highScoreStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        $perfectScoreStmt = $conn->prepare("
          SELECT COUNT(*) as count
          FROM quiz_attempts qa
          WHERE qa.user_id = ? AND qa.completed = 1 AND qa.score = qa.max_score
        ");
        $perfectScoreStmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $perfectScoreStmt->execute();
        $perfectScore = $perfectScoreStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        ?>
        
        <!-- First Quiz Badge -->
        <div class="badge-item">
          <div class="badge-icon <?php echo $hasFirstQuiz ? 'earned' : ''; ?>">🏆</div>
          <div class="badge-name">Khởi đầu</div>
          <div class="badge-description">Hoàn thành bài trắc nghiệm đầu tiên</div>
        </div>
        
        <!-- 5 Quizzes Badge -->
        <div class="badge-item">
          <div class="badge-icon <?php echo $has5Quizzes ? 'earned' : ''; ?>">🔥</div>
          <div class="badge-name">Chăm chỉ</div>
          <div class="badge-description">Hoàn thành 5 bài trắc nghiệm</div>
        </div>
        
        <!-- 10 Quizzes Badge -->
        <div class="badge-item">
          <div class="badge-icon <?php echo $has10Quizzes ? 'earned' : ''; ?>">⭐</div>
          <div class="badge-name">Siêu sao</div>
          <div class="badge-description">Hoàn thành 10 bài trắc nghiệm</div>
        </div>
        
        <!-- High Score Badge -->
        <div class="badge-item">
          <div class="badge-icon <?php echo $hasHighScore ? 'earned' : ''; ?>">🎯</div>
          <div class="badge-name">Xuất sắc</div>
          <div class="badge-description">Đạt điểm cao (>80%)</div>
        </div>
        
        <!-- Perfect Score Badge -->
        <div class="badge-item">
          <div class="badge-icon <?php echo $perfectScore ? 'earned' : ''; ?>">💯</div>
          <div class="badge-name">Hoàn hảo</div>
          <div class="badge-description">Đạt điểm tuyệt đối</div>
        </div>
      </div>
    </div>
    
    <!-- Filter section -->
    <div class="filter-section">
      <h2 class="filter-title">Lọc bài trắc nghiệm</h2>
      <form action="" method="GET">
        <div class="filter-row">
          <div class="filter-group">
            <label for="status" class="filter-label">Trạng thái</label>
            <select name="status" id="status" class="filter-select">
              <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Tất cả</option>
              <option value="not_started" <?php echo $status_filter == 'not_started' ? 'selected' : ''; ?>>Chưa làm</option>
              <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>Đang làm</option>
              <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Đã hoàn thành</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="course_id" class="filter-label">Khóa học</label>
            <select name="course_id" id="course_id" class="filter-select">
              <option value="0">Tất cả khóa học</option>
              <?php while ($course = $coursesResult->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course['title']); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          
          <button type="submit" class="filter-button">Lọc</button>
        </div>
      </form>
    </div>
    
    <!-- Quiz list -->
    <?php if ($quizzes->rowCount() > 0): ?>
      <div class="quiz-grid">
        <?php while ($quiz = $quizzes->fetch(PDO::FETCH_ASSOC)): ?>
          <div class="quiz-card">
            <span class="quiz-status status-<?php echo $quiz['status']; ?>">
              <?php 
                if ($quiz['status'] == 'completed') echo 'Đã hoàn thành';
                else if ($quiz['status'] == 'in_progress') echo 'Đang làm';
                else echo 'Chưa làm';
              ?>
            </span>
            
            <div class="quiz-header">
              <h3 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h3>
              <div class="quiz-course">Khóa học: <?php echo htmlspecialchars($quiz['course_title']); ?></div>
            </div>
            
            <div class="quiz-body">
              <div class="quiz-info">
                <div class="info-item">
                  <span class="info-label">Thời gian:</span>
                  <span class="info-value"><?php echo $quiz['time_limit']; ?> phút</span>
                </div>
                
                <div class="info-item">
                  <span class="info-label">Số câu hỏi:</span>
                  <span class="info-value"><?php echo $quiz['total_questions']; ?> câu</span>
                </div>
              </div>
              
              <?php if ($quiz['status'] == 'completed' && isset($quiz['score'])): ?>
                <div class="quiz-score">
                  <div class="score-badge"><?php echo $quiz['score']; ?></div>
                  <div>
                    <div class="info-label">Điểm số của bạn:</div>
                    <div class="info-value"><?php echo $quiz['score']; ?>/<?php echo $quiz['max_score']; ?></div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="quiz-footer">
              <?php if ($quiz['status'] == 'not_started'): ?>
                <a href="quiz_detail.php?id=<?php echo $quiz['quiz_id']; ?>" class="quiz-btn btn-primary">Xem chi tiết</a>
                <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="quiz-btn btn-secondary">Bắt đầu làm</a>
              <?php elseif ($quiz['status'] == 'in_progress'): ?>
                <a href="quiz_detail.php?id=<?php echo $quiz['quiz_id']; ?>" class="quiz-btn btn-primary">Xem chi tiết</a>
                <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="quiz-btn btn-secondary">Tiếp tục làm</a>
              <?php else: ?>
                <a href="quiz_detail.php?id=<?php echo $quiz['quiz_id']; ?>" class="quiz-btn btn-primary">Xem chi tiết</a>
                <a href="quiz_results.php?id=<?php echo $quiz['quiz_id']; ?>" class="quiz-btn btn-secondary">Xem kết quả</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
      
      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <ul class="pagination">
          <?php if ($page > 1): ?>
            <li><a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&course_id=<?php echo $course_filter; ?>">«</a></li>
          <?php endif; ?>
          
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="<?php echo $i == $page ? 'active' : ''; ?>">
              <?php if ($i == $page): ?>
                <span><?php echo $i; ?></span>
              <?php else: ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&course_id=<?php echo $course_filter; ?>"><?php echo $i; ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>
          
          <?php if ($page < $totalPages): ?>
            <li><a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&course_id=<?php echo $course_filter; ?>">»</a></li>
          <?php endif; ?>
        </ul>
      <?php endif; ?>
    <?php else: ?>
      <div class="no-quizzes">
        <div class="no-quizzes-icon">📝</div>
        <div class="no-quizzes-text">Không tìm thấy bài trắc nghiệm nào phù hợp với bộ lọc.</div>
        <a href="quizzes.php" class="filter-button">Xem tất cả bài trắc nghiệm</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>
  
  <!-- Confetti container for celebrations -->
  <div class="confetti-container" id="confetti-container"></div>
  
  <!-- JavaScript for interactive elements -->
  <script>
    // Animate progress bar on page load
    document.addEventListener('DOMContentLoaded', function() {
      const progressBar = document.querySelector('.progress-bar');
      const currentWidth = progressBar.style.width;
      progressBar.style.width = '0%';
      
      setTimeout(() => {
        progressBar.style.width = currentWidth;
      }, 300);
      
      // Check if user has earned a new badge recently
      const newBadgeEarned = <?php echo isset($_SESSION['new_badge_earned']) && $_SESSION['new_badge_earned'] ? 'true' : 'false'; ?>;
      
      if (newBadgeEarned) {
        showConfetti();
        <?php unset($_SESSION['new_badge_earned']); ?>
      }
    });
    
    // Function to show confetti animation
    function showConfetti() {
      const confettiContainer = document.getElementById('confetti-container');
      confettiContainer.style.display = 'block';
      
      // Create and append confetti pieces
      for (let i = 0; i < 100; i++) {
        const confetti = document.createElement('div');
        confetti.style.position = 'absolute';
        confetti.style.width = Math.random() * 10 + 5 + 'px';
        confetti.style.height = Math.random() * 10 + 5 + 'px';
        confetti.style.backgroundColor = getRandomColor();
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.top = -20 + 'px';
        confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
        confetti.style.opacity = Math.random() + 0.5;
        
        confettiContainer.appendChild(confetti);
        
        // Animate the confetti
        const animation = confetti.animate([
          { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
          { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
        ], {
          duration: Math.random() * 3000 + 2000,
          easing: 'cubic-bezier(0.4, 0.0, 0.2, 1)'
        });
        
        animation.onfinish = () => {
          confetti.remove();
          if (confettiContainer.children.length === 0) {
            confettiContainer.style.display = 'none';
          }
        };
      }
    }
    
    function getRandomColor() {
      const colors = ['#FF4E50', '#F9D423', '#4361EE', '#7209B7', '#3A5298', '#00F2FE'];
      return colors[Math.floor(Math.random() * colors.length)];
    }
  </script>
</body>
</html> 