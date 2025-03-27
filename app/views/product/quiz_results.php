<?php
// quiz_results.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để xem kết quả trắc nghiệm.";
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
           c.title as course_title, c.course_id
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
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

// Lấy thông tin lần làm bài gần nhất đã hoàn thành
$attemptStmt = $conn->prepare("
    SELECT attempt_id, start_time, end_time, score, max_score, completed,
           TIMESTAMPDIFF(MINUTE, start_time, end_time) as duration
    FROM quiz_attempts
    WHERE quiz_id = ? AND user_id = ? AND completed = 1
    ORDER BY attempt_id DESC
    LIMIT 1
");
$attemptStmt->bind_param("ii", $quiz_id, $user_id);
$attemptStmt->execute();
$attemptResult = $attemptStmt->get_result();

// Nếu chưa có lần làm bài nào đã hoàn thành
if ($attemptResult->num_rows == 0) {
  $_SESSION['quizError'] = "Bạn chưa có kết quả nào cho bài trắc nghiệm này.";
  header("Location: quiz_detail.php?id=$quiz_id");
  exit();
}

$attempt = $attemptResult->fetch_assoc();
$attempt_id = $attempt['attempt_id'];

// Lấy chi tiết câu trả lời và câu hỏi
$questionsStmt = $conn->prepare("
    SELECT q.question_id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, 
           q.correct_option, q.score, 
           a.selected_option, a.is_correct, a.score as earned_score
    FROM quiz_questions q
    LEFT JOIN quiz_attempt_answers a ON q.question_id = a.question_id AND a.attempt_id = ?
    WHERE q.quiz_id = ?
    ORDER BY q.question_id
");
$questionsStmt->bind_param("ii", $attempt_id, $quiz_id);
$questionsStmt->execute();
$questionsResult = $questionsStmt->get_result();

$questions = array();
$correct_count = 0;
$incorrect_count = 0;
$unanswered_count = 0;

while ($row = $questionsResult->fetch_assoc()) {
    $questions[] = $row;
    
    if (!isset($row['selected_option']) || $row['selected_option'] === null) {
        $unanswered_count++;
    } else if ($row['is_correct']) {
        $correct_count++;
    } else {
        $incorrect_count++;
    }
}

// Hiển thị thông báo từ submit_quiz.php nếu có
$info_message = isset($_SESSION['quizInfo']) ? $_SESSION['quizInfo'] : '';
unset($_SESSION['quizInfo']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Kết Quả Bài Trắc Nghiệm: <?php echo htmlspecialchars($quiz['title']); ?></title>
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

    /* Quiz title */
    .quiz-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 2rem;
        color: #1e3c72;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 3px solid #FFC107;
        animation: fadeInDown 1s ease;
    }

    /* Results summary card */
    .summary-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .summary-header {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
        padding: 20px;
        text-align: center;
    }

    .summary-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .summary-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .summary-content {
        padding: 30px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        align-items: center;
        gap: 30px;
    }

    /* Score display */
    .score-display {
        text-align: center;
    }

    .score-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin: 0 auto 15px;
        box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
    }

    .score-value {
        font-size: 3rem;
        line-height: 1;
    }

    .score-max {
        font-size: 1.2rem;
        opacity: 0.8;
    }

    .score-percent {
        font-size: 1.2rem;
        color: #333;
        font-weight: 600;
    }

    /* Stats display */
    .stats-display {
        flex: 1;
        min-width: 280px;
    }

    .stats-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .stats-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.2rem;
        color: white;
    }

    .icon-correct {
        background: #28a745;
    }

    .icon-incorrect {
        background: #dc3545;
    }

    .icon-unanswered {
        background: #6c757d;
    }

    .icon-time {
        background: #17a2b8;
    }

    .stats-text {
        flex: 1;
    }

    .stats-label {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .stats-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #333;
    }

    /* Alert box */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .alert-info {
        background-color: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }

    /* Questions section */
    .questions-section {
        margin-top: 40px;
    }

    .section-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5rem;
        color: #1e3c72;
        margin-bottom: 20px;
    }

    .question-card {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    }

    .question-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .question-number {
        font-weight: 700;
        color: #1e3c72;
        font-size: 1.1rem;
    }

    .question-status {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .status-correct {
        background: #d4edda;
        color: #155724;
    }

    .status-incorrect {
        background: #f8d7da;
        color: #721c24;
    }

    .status-unanswered {
        background: #e2e3e5;
        color: #383d41;
    }

    .question-text {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .options-list {
        list-style: none;
    }

    .option-item {
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 10px;
        position: relative;
    }

    .option-letter {
        font-weight: 700;
        margin-right: 10px;
        display: inline-block;
        width: 25px;
    }

    .option-text {
        display: inline;
    }

    .option-correct {
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    .option-incorrect {
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .option-selected {
        border-width: 2px;
        border-color: #1e3c72;
    }

    .option-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .icon-check {
        background: #28a745;
    }

    .icon-times {
        background: #dc3545;
    }

    .question-points {
        margin-top: 15px;
        text-align: right;
        font-weight: 600;
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
        .summary-content {
            flex-direction: column;
        }
        
        .question-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .question-status {
            margin-top: 10px;
        }
        
        .action-buttons {
            flex-direction: column;
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
    <h1 class="quiz-title">Kết Quả: <?php echo htmlspecialchars($quiz['title']); ?></h1>
    
    <?php if (!empty($info_message)): ?>
    <div class="alert alert-info">
      <?php echo htmlspecialchars($info_message); ?>
    </div>
    <?php endif; ?>
    
    <!-- Summary card -->
    <div class="summary-card">
      <div class="summary-header">
        <div class="summary-title">Tổng Kết Bài Trắc Nghiệm</div>
        <div class="summary-subtitle"><?php echo htmlspecialchars($quiz['course_title']); ?></div>
      </div>
      
      <div class="summary-content">
        <div class="score-display">
          <div class="score-circle">
            <div class="score-value"><?php echo $attempt['score']; ?></div>
            <div class="score-max">/ <?php echo $attempt['max_score']; ?></div>
          </div>
          <div class="score-percent">
            <?php echo round(($attempt['score'] / $attempt['max_score']) * 100, 1); ?>%
          </div>
        </div>
        
        <div class="stats-display">
          <div class="stats-item">
            <div class="stats-icon icon-correct">✓</div>
            <div class="stats-text">
              <div class="stats-label">Đúng</div>
              <div class="stats-value"><?php echo $correct_count; ?> câu</div>
            </div>
          </div>
          
          <div class="stats-item">
            <div class="stats-icon icon-incorrect">✗</div>
            <div class="stats-text">
              <div class="stats-label">Sai</div>
              <div class="stats-value"><?php echo $incorrect_count; ?> câu</div>
            </div>
          </div>
          
          <div class="stats-item">
            <div class="stats-icon icon-unanswered">–</div>
            <div class="stats-text">
              <div class="stats-label">Không trả lời</div>
              <div class="stats-value"><?php echo $unanswered_count; ?> câu</div>
            </div>
          </div>
          
          <div class="stats-item">
            <div class="stats-icon icon-time">⏱️</div>
            <div class="stats-text">
              <div class="stats-label">Thời gian làm bài</div>
              <div class="stats-value"><?php echo $attempt['duration']; ?> phút</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Questions section -->
    <div class="questions-section">
      <h2 class="section-title">Chi Tiết Câu Trả Lời</h2>
      
      <?php foreach ($questions as $index => $question): ?>
        <?php
        $status = '';
        $statusClass = '';
        
        if (!isset($question['selected_option']) || $question['selected_option'] === null) {
            $status = 'Không trả lời';
            $statusClass = 'status-unanswered';
        } else if ($question['is_correct']) {
            $status = 'Đúng';
            $statusClass = 'status-correct';
        } else {
            $status = 'Sai';
            $statusClass = 'status-incorrect';
        }
        ?>
        
        <div class="question-card">
          <div class="question-header">
            <div class="question-number">Câu <?php echo $index + 1; ?></div>
            <div class="question-status <?php echo $statusClass; ?>"><?php echo $status; ?></div>
          </div>
          
          <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
          
          <ul class="options-list">
            <?php
            $options = [
                'A' => $question['option_a'],
                'B' => $question['option_b'],
                'C' => $question['option_c'],
                'D' => $question['option_d']
            ];
            
            foreach ($options as $letter => $text):
                $optionClass = '';
                $icon = '';
                
                // Nếu đây là lựa chọn đúng
                if ($letter == $question['correct_option']) {
                    $optionClass = 'option-correct';
                    $icon = '<div class="option-icon icon-check">✓</div>';
                }
                
                // Nếu đây là lựa chọn của người dùng
                if ($letter == $question['selected_option']) {
                    $optionClass .= ' option-selected';
                    
                    // Nếu người dùng chọn sai
                    if ($letter != $question['correct_option']) {
                        $optionClass .= ' option-incorrect';
                        $icon = '<div class="option-icon icon-times">✗</div>';
                    }
                }
            ?>
            <li class="option-item <?php echo $optionClass; ?>">
              <span class="option-letter"><?php echo $letter; ?>.</span>
              <span class="option-text"><?php echo htmlspecialchars($text); ?></span>
              <?php echo $icon; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          
          <div class="question-points">
            <?php if (isset($question['earned_score'])): ?>
              Điểm: <?php echo $question['earned_score']; ?> / <?php echo $question['score']; ?>
            <?php else: ?>
              Điểm: 0 / <?php echo $question['score']; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <div class="action-buttons">
      <a href="quiz_detail.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary">Quay lại chi tiết bài trắc nghiệm</a>
      <a href="quizzes.php" class="btn btn-primary">Quay lại danh sách</a>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>
</body>
</html> 