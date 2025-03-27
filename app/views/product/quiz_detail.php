<?php
// quiz_detail.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để xem chi tiết bài trắc nghiệm.";
  header("Location: " . BASE_URL . "app/views/product/home.php");
  exit();
}

// Kiểm tra ID bài trắc nghiệm
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: quizzes.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['id']);

// Lấy thông tin bài trắc nghiệm
$stmt = $conn->prepare("
    SELECT q.quiz_id, q.title, q.time_limit, q.total_questions,
           c.title as course_title, c.course_id, l.title as lesson_title, l.lesson_id,
           (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.quiz_id) as actual_questions_count
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    LEFT JOIN lessons l ON q.lesson_id = l.lesson_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE q.quiz_id = ? AND e.user_id = ? AND e.status = 'active'
    LIMIT 1
");
$stmt->bind_param("ii", $quiz_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Kiểm tra nếu không tìm thấy bài trắc nghiệm hoặc sinh viên không có quyền xem
if ($result->num_rows == 0) {
  header("Location: quizzes.php");
  exit();
}

$quiz = $result->fetch_assoc();

// Kiểm tra trạng thái của bài trắc nghiệm
$attemptStmt = $conn->prepare("
    SELECT attempt_id, start_time, end_time, score, max_score, completed
    FROM quiz_attempts
    WHERE quiz_id = ? AND user_id = ?
    ORDER BY attempt_id DESC
    LIMIT 1
");
$attemptStmt->bind_param("ii", $quiz_id, $user_id);
$attemptStmt->execute();
$attemptResult = $attemptStmt->get_result();
$has_attempts = false;
$last_attempt = null;
$is_completed = false;

if ($attemptResult->num_rows > 0) {
    $has_attempts = true;
    $last_attempt = $attemptResult->fetch_assoc();
    $is_completed = $last_attempt['completed'] == 1;
}

// Kiểm tra nếu bài trắc nghiệm chưa có câu hỏi
$has_questions = $quiz['actual_questions_count'] > 0;

// Đếm tổng số lần đã làm bài
$attemptsCountStmt = $conn->prepare("
    SELECT COUNT(*) as attempts_count 
    FROM quiz_attempts 
    WHERE quiz_id = ? AND user_id = ?
");
$attemptsCountStmt->bind_param("ii", $quiz_id, $user_id);
$attemptsCountStmt->execute();
$attempts_count = $attemptsCountStmt->get_result()->fetch_assoc()['attempts_count'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($quiz['title']); ?> - Chi Tiết Bài Trắc Nghiệm</title>
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
        margin: 40px auto;
        padding: 0 20px;
        animation: fadeIn 1s ease forwards;
    }

    /* Quiz header */
    .quiz-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 25px;
    }

    .quiz-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 2rem;
        color: #1e3c72;
        margin-bottom: 10px;
        padding-bottom: 12px;
        border-bottom: 3px solid #FFC107;
        animation: fadeInDown 1s ease;
    }

    .quiz-status {
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.9rem;
        text-align: center;
        margin-top: 5px;
    }

    .status-not-started {
        background: #FFC107;
        color: black;
    }

    .status-in-progress {
        background: #17a2b8;
        color: white;
    }

    .status-completed {
        background: #28a745;
        color: white;
    }

    /* Quiz info card */
    .quiz-info-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 25px;
    }

    .info-item {
        margin-bottom: 15px;
    }

    .info-label {
        font-weight: 700;
        color: #1e3c72;
        display: block;
        margin-bottom: 5px;
    }

    .info-value {
        color: #555;
        font-size: 1.1rem;
    }

    /* Divider */
    .divider {
        height: 1px;
        background: #e0e0e0;
        margin: 20px 0;
    }

    /* Rules section */
    .rules-section {
        background: #f9f9f9;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border-left: 4px solid #17a2b8;
    }

    .section-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.4rem;
        color: #1e3c72;
        margin-bottom: 15px;
    }

    .rules-list {
        list-style: none;
    }

    .rules-list li {
        position: relative;
        padding-left: 30px;
        margin-bottom: 12px;
    }

    .rules-list li:before {
        content: "•";
        color: #17a2b8;
        font-size: 1.5rem;
        position: absolute;
        left: 10px;
        top: -2px;
    }

    /* Previous attempts section */
    .attempts-section {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .attempt-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .attempt-info {
        flex: 1;
    }

    .attempt-date {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .attempt-score {
        display: flex;
        align-items: center;
    }

    .score-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #28a745;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        margin-left: 15px;
    }

    /* Action buttons */
    .action-buttons {
        display: flex;
        gap: 20px;
        margin-top: 30px;
    }

    .btn {
        flex: 1;
        display: inline-block;
        padding: 15px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 700;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        text-align: center;
        font-size: 1.1rem;
    }

    .btn-primary {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
        box-shadow: 0 4px 8px rgba(30, 60, 114, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(30, 60, 114, 0.4);
    }

    .btn-secondary {
        background: linear-gradient(90deg, #FF8008, #FFA100);
        color: white;
        box-shadow: 0 4px 8px rgba(255, 128, 8, 0.3);
    }

    .btn-secondary:hover {
        background: linear-gradient(90deg, #FFA100, #FF8008);
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(255, 128, 8, 0.4);
    }

    .btn-disabled {
        background: #6c757d;
        color: white;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .btn-disabled:hover {
        transform: none;
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
    }

    /* Alert boxes */
    .alert {
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        font-weight: 600;
    }

    .alert-warning {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
    }

    .alert-info {
        background-color: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    /* Score display */
    .score-display {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 20px 0;
    }

    .big-score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: #28a745;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        margin-right: 30px;
        box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
    }

    .score-details {
        text-align: left;
    }

    .score-label {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 5px;
    }

    .score-text {
        font-size: 1.1rem;
        color: #555;
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

    /* Responsive */
    @media (max-width: 768px) {
        .quiz-header {
            flex-direction: column;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .score-display {
            flex-direction: column;
            text-align: center;
        }
        
        .big-score-circle {
            margin-right: 0;
            margin-bottom: 20px;
        }
        
        .score-details {
            text-align: center;
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
          <li><a href="quizzes.php">Trắc Nghiệm</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <div class="quiz-header">
      <h1 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h1>
      
      <?php 
      if ($is_completed) {
          echo '<span class="quiz-status status-completed">Đã hoàn thành</span>';
      } elseif ($has_attempts && !$is_completed) {
          echo '<span class="quiz-status status-in-progress">Đang làm</span>';
      } else {
          echo '<span class="quiz-status status-not-started">Chưa làm</span>';
      }
      ?>
    </div>
    
    <?php if (!$has_questions): ?>
    <div class="alert alert-warning">
      Bài trắc nghiệm này chưa có câu hỏi nào. Vui lòng quay lại sau!
    </div>
    <?php endif; ?>
    
    <div class="quiz-info-card">
      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Khóa học:</span>
          <span class="info-value"><?php echo htmlspecialchars($quiz['course_title']); ?></span>
        </div>
        
        <?php if (!empty($quiz['lesson_title'])): ?>
        <div class="info-item">
          <span class="info-label">Bài học:</span>
          <span class="info-value"><?php echo htmlspecialchars($quiz['lesson_title']); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="info-item">
          <span class="info-label">Thời gian làm bài:</span>
          <span class="info-value"><?php echo $quiz['time_limit']; ?> phút</span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Số câu hỏi:</span>
          <span class="info-value"><?php echo $quiz['actual_questions_count']; ?> câu</span>
        </div>
        
        <div class="info-item">
          <span class="info-label">Lần làm bài:</span>
          <span class="info-value"><?php echo $attempts_count; ?> lần</span>
        </div>
      </div>
      
      <?php if ($is_completed && $last_attempt): ?>
      <div class="divider"></div>
      
      <div class="score-display">
        <div class="big-score-circle"><?php echo $last_attempt['score']; ?></div>
        <div class="score-details">
          <div class="score-label">Điểm số của bạn</div>
          <div class="score-text"><?php echo $last_attempt['score']; ?> / <?php echo $last_attempt['max_score']; ?> điểm</div>
          <div class="score-text">Hoàn thành lúc: <?php echo date('d/m/Y H:i', strtotime($last_attempt['end_time'])); ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
    
    <div class="rules-section">
      <h2 class="section-title">Hướng dẫn làm bài</h2>
      <ul class="rules-list">
        <li>Bài trắc nghiệm có thời gian giới hạn <?php echo $quiz['time_limit']; ?> phút. Sau khi hết thời gian, bài làm sẽ tự động nộp.</li>
        <li>Hãy đảm bảo bạn có kết nối internet ổn định trong suốt quá trình làm bài.</li>
        <li>Không được thoát khỏi trang làm bài khi chưa hoàn thành, việc này có thể dẫn đến mất dữ liệu bài làm.</li>
        <li>Mỗi câu hỏi chỉ có một đáp án đúng. Chọn câu trả lời bằng cách nhấp vào nút radio bên cạnh đáp án.</li>
        <li>Bạn có thể xem lại và thay đổi câu trả lời trước khi nộp bài.</li>
        <li>Sau khi hoàn thành, nhấn nút "Nộp bài" để kết thúc bài làm và nhận kết quả.</li>
      </ul>
    </div>
    
    <?php if ($has_attempts && !$is_completed): ?>
    <div class="alert alert-info">
      Bạn đang có một bài làm chưa hoàn thành. Hãy tiếp tục bài làm để hoàn thành nó!
    </div>
    <?php endif; ?>
    
    <div class="action-buttons">
      <a href="quizzes.php" class="btn btn-primary">Quay lại danh sách</a>
      
      <?php if (!$has_questions): ?>
        <span class="btn btn-disabled">Chưa có câu hỏi</span>
      <?php elseif ($is_completed): ?>
        <a href="quiz_results.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Xem kết quả chi tiết</a>
      <?php elseif ($has_attempts && !$is_completed): ?>
        <a href="take_quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Tiếp tục làm bài</a>
      <?php else: ?>
        <a href="take_quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Bắt đầu làm bài</a>
      <?php endif; ?>
    </div>
    
    <?php if (!empty($quiz['lesson_id'])): ?>
    <div class="action-buttons" style="margin-top: 15px;">
      <a href="lesson_page.php?lesson_id=<?php echo $quiz['lesson_id']; ?>" class="btn btn-primary">Đến bài học</a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>
</body>
</html> 