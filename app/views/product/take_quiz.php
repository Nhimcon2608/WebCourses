<?php
// take_quiz.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để làm bài trắc nghiệm.";
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
$attempt_id = null;
$is_completed = false;
$is_submitted = false;
$error_message = '';
$success_message = '';
$questions = array();
$attempt = null;
$selected_answers = array();
$time_remaining = 0;

// Lấy thông tin bài trắc nghiệm
$stmt = $conn->prepare("
    SELECT q.quiz_id, q.title, q.time_limit, q.total_questions,
           c.title as course_title, c.course_id,
           (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.quiz_id) as actual_questions_count
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE q.quiz_id = ? AND e.user_id = ? AND e.status = 'active'
    LIMIT 1
");
$stmt->bind_param("ii", $quiz_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Kiểm tra nếu không tìm thấy bài trắc nghiệm hoặc sinh viên không có quyền làm
if ($result->num_rows == 0) {
  header("Location: quizzes.php");
  exit();
}

$quiz = $result->fetch_assoc();

// Kiểm tra nếu bài trắc nghiệm chưa có câu hỏi
if ($quiz['actual_questions_count'] == 0) {
  $_SESSION['quizError'] = "Bài trắc nghiệm này chưa có câu hỏi nào.";
  header("Location: quiz_detail.php?id=$quiz_id");
  exit();
}

// Kiểm tra lần làm bài hiện tại
$attemptStmt = $conn->prepare("
    SELECT attempt_id, start_time, end_time, completed, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(start_time, INTERVAL ? MINUTE)) as time_remaining
    FROM quiz_attempts
    WHERE quiz_id = ? AND user_id = ? AND completed = 0
    ORDER BY attempt_id DESC
    LIMIT 1
");
$attemptStmt->bind_param("iii", $quiz['time_limit'], $quiz_id, $user_id);
$attemptStmt->execute();
$attemptResult = $attemptStmt->get_result();

// Kiểm tra nếu có lần làm bài nào đã hoàn thành
$completedStmt = $conn->prepare("
    SELECT attempt_id FROM quiz_attempts
    WHERE quiz_id = ? AND user_id = ? AND completed = 1
    ORDER BY attempt_id DESC
    LIMIT 1
");
$completedStmt->bind_param("ii", $quiz_id, $user_id);
$completedStmt->execute();
$completedResult = $completedStmt->get_result();
$is_completed = $completedResult->num_rows > 0;

// Nếu đã hoàn thành bài trắc nghiệm trước đó
if ($is_completed) {
  $_SESSION['quizInfo'] = "Bạn đã hoàn thành bài trắc nghiệm này trước đó.";
  header("Location: quiz_results.php?id=$quiz_id");
  exit();
}

// Nếu chưa có lần làm bài nào đang diễn ra, tạo mới
if ($attemptResult->num_rows == 0) {
  $createAttemptStmt = $conn->prepare("
      INSERT INTO quiz_attempts (quiz_id, user_id, start_time)
      VALUES (?, ?, NOW())
  ");
  $createAttemptStmt->bind_param("ii", $quiz_id, $user_id);
  $createAttemptStmt->execute();
  $attempt_id = $conn->insert_id;
  $time_remaining = $quiz['time_limit'] * 60;
} else {
  $attempt = $attemptResult->fetch_assoc();
  $attempt_id = $attempt['attempt_id'];
  $time_remaining = max(0, $attempt['time_remaining']);
  
  // Nếu hết thời gian, chuyển hướng đến trang nộp bài
  if ($time_remaining <= 0) {
    header("Location: submit_quiz.php?id=$quiz_id&attempt_id=$attempt_id&timeout=1");
    exit();
  }
}

// Lấy danh sách câu hỏi
$questionStmt = $conn->prepare("
    SELECT question_id, question_text, option_a, option_b, option_c, option_d, score
    FROM quiz_questions
    WHERE quiz_id = ?
    ORDER BY question_id
");
$questionStmt->bind_param("i", $quiz_id);
$questionStmt->execute();
$questionsResult = $questionStmt->get_result();
while ($row = $questionsResult->fetch_assoc()) {
    $questions[] = $row;
}

// Lấy câu trả lời đã chọn (nếu có)
if ($attempt_id) {
  $answerStmt = $conn->prepare("
      SELECT question_id, selected_option
      FROM quiz_attempt_answers
      WHERE attempt_id = ?
  ");
  $answerStmt->bind_param("i", $attempt_id);
  $answerStmt->execute();
  $answersResult = $answerStmt->get_result();
  while ($row = $answersResult->fetch_assoc()) {
      $selected_answers[$row['question_id']] = $row['selected_option'];
  }
}

// Xử lý khi sinh viên lưu câu trả lời
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_answers'])) {
  // Lưu câu trả lời cho từng câu hỏi
  foreach ($questions as $question) {
    $question_id = $question['question_id'];
    $answer_key = 'question_' . $question_id;
    
    if (isset($_POST[$answer_key])) {
      $selected_option = $_POST[$answer_key];
      
      // Kiểm tra xem đã có câu trả lời cho câu hỏi này chưa
      $checkAnswerStmt = $conn->prepare("
          SELECT answer_id FROM quiz_attempt_answers
          WHERE attempt_id = ? AND question_id = ?
      ");
      $checkAnswerStmt->bind_param("ii", $attempt_id, $question_id);
      $checkAnswerStmt->execute();
      $checkResult = $checkAnswerStmt->get_result();
      
      if ($checkResult->num_rows > 0) {
        // Cập nhật câu trả lời
        $updateAnswerStmt = $conn->prepare("
            UPDATE quiz_attempt_answers SET selected_option = ?
            WHERE attempt_id = ? AND question_id = ?
        ");
        $updateAnswerStmt->bind_param("sii", $selected_option, $attempt_id, $question_id);
        $updateAnswerStmt->execute();
      } else {
        // Thêm câu trả lời mới
        $insertAnswerStmt = $conn->prepare("
            INSERT INTO quiz_attempt_answers (attempt_id, question_id, selected_option)
            VALUES (?, ?, ?)
        ");
        $insertAnswerStmt->bind_param("iis", $attempt_id, $question_id, $selected_option);
        $insertAnswerStmt->execute();
      }
      
      // Cập nhật mảng câu trả lời đã chọn
      $selected_answers[$question_id] = $selected_option;
    }
  }
  
  $success_message = "Đã lưu câu trả lời của bạn!";
}

// Xử lý khi sinh viên nộp bài
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
  // Lưu câu trả lời hiện tại trước khi nộp bài
  foreach ($questions as $question) {
    $question_id = $question['question_id'];
    $answer_key = 'question_' . $question_id;
    
    if (isset($_POST[$answer_key])) {
      $selected_option = $_POST[$answer_key];
      
      // Kiểm tra xem đã có câu trả lời cho câu hỏi này chưa
      $checkAnswerStmt = $conn->prepare("
          SELECT answer_id FROM quiz_attempt_answers
          WHERE attempt_id = ? AND question_id = ?
      ");
      $checkAnswerStmt->bind_param("ii", $attempt_id, $question_id);
      $checkAnswerStmt->execute();
      $checkResult = $checkAnswerStmt->get_result();
      
      if ($checkResult->num_rows > 0) {
        // Cập nhật câu trả lời
        $updateAnswerStmt = $conn->prepare("
            UPDATE quiz_attempt_answers SET selected_option = ?
            WHERE attempt_id = ? AND question_id = ?
        ");
        $updateAnswerStmt->bind_param("sii", $selected_option, $attempt_id, $question_id);
        $updateAnswerStmt->execute();
      } else {
        // Thêm câu trả lời mới
        $insertAnswerStmt = $conn->prepare("
            INSERT INTO quiz_attempt_answers (attempt_id, question_id, selected_option)
            VALUES (?, ?, ?)
        ");
        $insertAnswerStmt->bind_param("iis", $attempt_id, $question_id, $selected_option);
        $insertAnswerStmt->execute();
      }
    }
  }
  
  // Chuyển hướng đến trang nộp bài
  header("Location: submit_quiz.php?id=$quiz_id&attempt_id=$attempt_id");
  exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Làm Bài Trắc Nghiệm: <?php echo htmlspecialchars($quiz['title']); ?></title>
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
        padding-bottom: 80px;
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

    .quiz-info {
        display: flex;
        align-items: center;
    }

    .timer {
        margin-left: 25px;
        font-size: 1.2rem;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.2);
        padding: 5px 15px;
        border-radius: 20px;
        display: flex;
        align-items: center;
    }

    .timer-icon {
        margin-right: 8px;
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
        font-size: 1.8rem;
        color: #1e3c72;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }

    /* Questions list */
    .questions-container {
        margin-bottom: 60px;
    }

    .question-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.5s forwards;
        position: relative;
        overflow: hidden;
    }

    .question-card.active {
        display: block;
    }

    .question-card.inactive {
        display: none;
    }

    .question-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .question-number {
        font-weight: 700;
        color: #1e3c72;
        font-size: 1.1rem;
    }

    .question-score {
        font-weight: 600;
        color: #6c757d;
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .question-text {
        font-size: 1.25rem;
        margin-bottom: 25px;
        line-height: 1.6;
        font-weight: 600;
        color: #333;
    }

    /* Options styling */
    .options-list {
        list-style: none;
        margin-bottom: 20px;
    }

    .option-item {
        margin-bottom: 15px;
    }

    .option-label {
        display: flex;
        align-items: flex-start;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .option-label:hover {
        border-color: #a1c4fd;
        background: rgba(161, 196, 253, 0.05);
        transform: translateX(5px);
    }

    .option-input {
        margin-right: 15px;
        margin-top: 3px;
    }

    .option-text {
        flex: 1;
    }

    .option-item input[type="radio"]:checked + .option-label {
        border-color: #1e3c72;
        background: rgba(30, 60, 114, 0.05);
        box-shadow: 0 2px 8px rgba(30, 60, 114, 0.2);
    }

    /* Animation for option selection */
    @keyframes selectedOption {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }

    .option-item input[type="radio"]:checked + .option-label {
        animation: selectedOption 0.3s forwards;
    }

    /* Navigation buttons */
    .quiz-navigation {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
    }

    .nav-btn {
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-family: 'Nunito', sans-serif;
    }

    .btn-prev {
        background: #f1f3f5;
        color: #495057;
    }

    .btn-prev:hover {
        background: #e9ecef;
        transform: translateX(-5px);
    }

    .btn-next {
        background: #1e3c72;
        color: white;
    }

    .btn-next:hover {
        background: #2a5298;
        transform: translateX(5px);
    }

    .quiz-actions {
        display: flex;
        justify-content: center;
        margin-top: 40px;
        gap: 20px;
    }

    .action-btn {
        min-width: 180px;
        padding: 15px 25px;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-family: 'Nunito', sans-serif;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-save {
        background: linear-gradient(90deg, #4facfe, #00f2fe);
        color: white;
        box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
    }

    .btn-save:hover {
        box-shadow: 0 8px 25px rgba(79, 172, 254, 0.5);
        transform: translateY(-5px);
    }

    .btn-submit {
        background: linear-gradient(90deg, #FF416C, #FF4B2B);
        color: white;
        box-shadow: 0 4px 15px rgba(255, 65, 108, 0.4);
    }

    .btn-submit:hover {
        box-shadow: 0 8px 25px rgba(255, 65, 108, 0.5);
        transform: translateY(-5px);
    }

    /* Feedback message */
    .feedback {
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        opacity: 0;
        transform: translateY(-20px);
        animation: fadeInDown 0.5s forwards;
    }

    .success-message {
        background: rgba(40, 167, 69, 0.1);
        border: 1px solid rgba(40, 167, 69, 0.3);
        color: #28a745;
    }

    .error-message {
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid rgba(220, 53, 69, 0.3);
        color: #dc3545;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Confetti effect */
    .confetti {
        position: fixed;
        width: 10px;
        height: 10px;
        background-color: #f00;
        top: -10px;
        animation: confetti-fall 3s linear infinite;
    }

    @keyframes confetti-fall {
        0% {
            transform: translateY(0) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotate(360deg);
            opacity: 0;
        }
    }

    /* Progress tracker */
    .quiz-progress {
        position: sticky;
        top: 90px;
        z-index: 100;
        background: white;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .timer-display {
        display: flex;
        align-items: center;
        font-weight: 700;
        color: #1e3c72;
    }
    
    .timer-icon {
        margin-right: 8px;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    
    .timer-warning {
        color: #FFA000;
    }
    
    .timer-danger {
        color: #F44336;
        animation: shake 0.5s infinite;
    }
    
    @keyframes shake {
        0% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        50% { transform: translateX(0); }
        75% { transform: translateX(5px); }
        100% { transform: translateX(0); }
    }
    
    .progress-indicators {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    
    .question-indicator {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        background-color: #e9ecef;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .question-indicator:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .question-indicator.active {
        background-color: #1e3c72;
        color: white;
        box-shadow: 0 2px 8px rgba(30, 60, 114, 0.4);
    }
    
    .question-indicator.answered {
        background-color: #a1c4fd;
        color: #1e3c72;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-container {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .quiz-info {
            margin-top: 15px;
        }

        .action-container {
            flex-direction: column;
            gap: 15px;
        }

        .status-text {
            text-align: center;
        }

        .btn-container {
            justify-content: center;
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
          <li><a href="quizzes.php">Bài Trắc Nghiệm</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="quiz-container">
    <h1 class="page-title"><?php echo htmlspecialchars($quiz['title']); ?></h1>
    <div class="quiz-subtitle">Khóa học: <?php echo htmlspecialchars($quiz['course_title']); ?></div>
    
    <?php if ($error_message): ?>
      <div class="feedback error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
      <div class="feedback success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <!-- Progress tracker -->
    <div class="quiz-progress">
      <div class="progress-header">
        <div class="timer-display">
          <span class="timer-icon">⏱️</span>
          <span id="time-remaining">
            <?php 
              $minutes = floor($time_remaining / 60);
              $seconds = $time_remaining % 60;
              echo sprintf("%02d:%02d", $minutes, $seconds);
            ?>
          </span>
        </div>
        <div class="questions-count">
          <span id="answered-count">0</span>/<?php echo count($questions); ?> câu đã làm
        </div>
      </div>
      
      <div class="progress-bar-container">
        <div class="progress-bar" id="quiz-progress-bar" style="width: 0%"></div>
      </div>
      
      <div class="progress-indicators">
        <?php foreach ($questions as $index => $question): ?>
          <div class="question-indicator <?php echo isset($selected_answers[$question['question_id']]) ? 'answered' : ''; ?> <?php echo $index === 0 ? 'active' : ''; ?>" 
               data-question="<?php echo $index; ?>" 
               onclick="showQuestion(<?php echo $index; ?>)">
            <?php echo $index + 1; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <form id="quiz-form" method="POST" action="">
      <?php foreach ($questions as $index => $question): ?>
        <div class="question-card <?php echo $index === 0 ? 'active' : 'inactive'; ?>" id="question-<?php echo $index; ?>">
          <div class="question-header">
            <div class="question-number">Câu hỏi <?php echo $index + 1; ?>/<?php echo count($questions); ?></div>
            <div class="question-score"><?php echo $question['score']; ?> điểm</div>
          </div>
          
          <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
          
          <ul class="options-list">
            <?php 
              $options = [
                'a' => $question['option_a'],
                'b' => $question['option_b'],
                'c' => $question['option_c'],
                'd' => $question['option_d']
              ];
              
              $question_id = $question['question_id'];
              $input_name = "question_" . $question_id;
              $selected_option = isset($selected_answers[$question_id]) ? $selected_answers[$question_id] : '';
            ?>
            
            <?php foreach ($options as $option_key => $option_text): ?>
              <li class="option-item">
                <input type="radio" id="<?php echo $input_name . '_' . $option_key; ?>" 
                       name="<?php echo $input_name; ?>" 
                       value="<?php echo $option_key; ?>" 
                       class="option-input" 
                       <?php echo $selected_option === $option_key ? 'checked' : ''; ?> 
                       onchange="updateQuestionIndicator(<?php echo $index; ?>)">
                <label for="<?php echo $input_name . '_' . $option_key; ?>" class="option-label">
                  <span class="option-marker"><?php echo strtoupper($option_key); ?></span>
                  <span class="option-text"><?php echo htmlspecialchars($option_text); ?></span>
                </label>
              </li>
            <?php endforeach; ?>
          </ul>
          
          <div class="quiz-navigation">
            <?php if ($index > 0): ?>
              <button type="button" class="nav-btn btn-prev" onclick="showQuestion(<?php echo $index - 1; ?>)">❮ Câu trước</button>
            <?php else: ?>
              <div></div>
            <?php endif; ?>
            
            <?php if ($index < count($questions) - 1): ?>
              <button type="button" class="nav-btn btn-next" onclick="showQuestion(<?php echo $index + 1; ?>)">Câu tiếp theo ❯</button>
            <?php else: ?>
              <div></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      
      <div class="quiz-actions">
        <button type="submit" name="save_answers" class="action-btn btn-save">
          <span>💾</span> Lưu câu trả lời
        </button>
        <button type="submit" name="submit_quiz" class="action-btn btn-submit">
          <span>✅</span> Nộp bài
        </button>
      </div>
    </form>
  </div>

  <script>
    // Current question index
    let currentQuestion = 0;
    
    // Timer setup
    let timeRemaining = <?php echo $time_remaining; ?>;
    const timerElement = document.getElementById('time-remaining');
    const timerIcon = document.querySelector('.timer-icon');
    
    // Update count of answered questions
    function updateAnsweredCount() {
      const answered = document.querySelectorAll('.question-indicator.answered').length;
      const total = <?php echo count($questions); ?>;
      document.getElementById('answered-count').textContent = answered;
      
      // Update progress bar
      const percentage = (answered / total) * 100;
      document.getElementById('quiz-progress-bar').style.width = percentage + '%';
    }
    
    // Mark question as answered
    function updateQuestionIndicator(questionIndex) {
      const indicator = document.querySelector(`.question-indicator[data-question="${questionIndex}"]`);
      indicator.classList.add('answered');
      updateAnsweredCount();
      
      // Add subtle animation effect
      const option = document.querySelector(`#question-${questionIndex} input[type="radio"]:checked + .option-label`);
      if (option) {
        option.style.animation = 'none';
        setTimeout(() => {
          option.style.animation = 'selectedOption 0.3s forwards';
        }, 10);
      }
    }
    
    // Show specific question
    function showQuestion(index) {
      // Hide current question
      document.getElementById(`question-${currentQuestion}`).classList.remove('active');
      document.getElementById(`question-${currentQuestion}`).classList.add('inactive');
      
      // Show new question
      document.getElementById(`question-${index}`).classList.remove('inactive');
      document.getElementById(`question-${index}`).classList.add('active');
      
      // Update indicators
      document.querySelector(`.question-indicator[data-question="${currentQuestion}"]`).classList.remove('active');
      document.querySelector(`.question-indicator[data-question="${index}"]`).classList.add('active');
      
      // Update current question
      currentQuestion = index;
      
      // Scroll to question
      window.scrollTo({
        top: document.querySelector('.quiz-progress').offsetTop - 100,
        behavior: 'smooth'
      });
    }
    
    // Initialize timer
    function startTimer() {
      const timer = setInterval(() => {
        timeRemaining--;
        
        // Format time as MM:SS
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Warning when less than 5 minutes remaining
        if (timeRemaining < 300 && timeRemaining > 60) {
          timerElement.classList.add('timer-warning');
          timerIcon.textContent = '⚠️';
        }
        
        // Danger when less than 1 minute remaining
        if (timeRemaining <= 60) {
          timerElement.classList.remove('timer-warning');
          timerElement.classList.add('timer-danger');
          timerIcon.textContent = '🔥';
        }
        
        // Auto-submit when time runs out
        if (timeRemaining <= 0) {
          clearInterval(timer);
          document.querySelector('button[name="submit_quiz"]').click();
        }
      }, 1000);
    }
    
    // Initialize the quiz page
    document.addEventListener('DOMContentLoaded', () => {
      // Start the timer
      startTimer();
      
      // Calculate initial answered count
      updateAnsweredCount();
      
      // Initialize question indicators
      <?php foreach ($selected_answers as $question_id => $option): ?>
        <?php
          // Find the index for this question_id
          $index = array_search($question_id, array_column($questions, 'question_id'));
          if ($index !== false):
        ?>
          updateQuestionIndicator(<?php echo $index; ?>);
        <?php endif; ?>
      <?php endforeach; ?>
      
      // Auto-save every 30 seconds
      setInterval(() => {
        const saveButton = document.querySelector('button[name="save_answers"]');
        const saveEvent = new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        });
        saveButton.dispatchEvent(saveEvent);
      }, 30000);
    });
  </script>
</body>
</html> 