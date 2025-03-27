<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra xem người dùng đã đăng nhập và có vai trò giảng viên chưa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: home.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Kiểm tra ID bài trắc nghiệm
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: manage_quizzes.php");
  exit();
}

$quiz_id = intval($_GET['id']);

// Kiểm tra xem bài trắc nghiệm có thuộc về giảng viên không
$quizStmt = $conn->prepare("
    SELECT q.*, c.title as course_title
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    WHERE q.quiz_id = ? AND c.instructor_id = ?
    LIMIT 1
");
$quizStmt->bind_param("ii", $quiz_id, $user_id);
$quizStmt->execute();
$quiz = $quizStmt->get_result()->fetch_assoc();

if (!$quiz) {
    $_SESSION['error_message'] = "Bạn không có quyền chỉnh sửa bài trắc nghiệm này.";
    header("Location: manage_quizzes.php");
    exit();
}

// Xử lý thêm câu hỏi mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_question') {
        $question_text = trim($_POST['question_text']);
        $option_a = trim($_POST['option_a']);
        $option_b = trim($_POST['option_b']);
        $option_c = trim($_POST['option_c']);
        $option_d = trim($_POST['option_d']);
        $correct_answer = $_POST['correct_answer'];
        $points = floatval($_POST['points']);
        $feedback = trim($_POST['feedback']);
        $difficulty = $_POST['difficulty'];
        
        if (empty($question_text) || empty($option_a) || empty($option_b) || empty($correct_answer)) {
            $error_message = "Vui lòng điền đầy đủ thông tin cần thiết.";
        } else {
            // Thêm câu hỏi mới
            $stmt = $conn->prepare("
                INSERT INTO quiz_questions 
                (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, points, feedback, difficulty)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssssdss", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $points, $feedback, $difficulty);
            
            if ($stmt->execute()) {
                // Cập nhật số lượng câu hỏi trong bài trắc nghiệm
                $conn->query("UPDATE quizzes SET total_questions = total_questions + 1 WHERE quiz_id = $quiz_id");
                $success_message = "Đã thêm câu hỏi thành công!";
            } else {
                $error_message = "Có lỗi xảy ra khi thêm câu hỏi: " . $conn->error;
            }
        }
    }
    
    if ($_POST['action'] == 'delete_question' && isset($_POST['question_id'])) {
        $question_id = intval($_POST['question_id']);
        
        // Xác minh câu hỏi thuộc về bài trắc nghiệm của giảng viên
        $checkQuestion = $conn->prepare("
            SELECT qq.question_id 
            FROM quiz_questions qq
            JOIN quizzes q ON qq.quiz_id = q.quiz_id
            JOIN courses c ON q.course_id = c.course_id
            WHERE qq.question_id = ? AND qq.quiz_id = ? AND c.instructor_id = ?
        ");
        $checkQuestion->bind_param("iii", $question_id, $quiz_id, $user_id);
        $checkQuestion->execute();
        
        if ($checkQuestion->get_result()->num_rows == 0) {
            $error_message = "Bạn không có quyền xóa câu hỏi này.";
        } else {
            // Xóa câu hỏi
            $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE question_id = ?");
            $stmt->bind_param("i", $question_id);
            
            if ($stmt->execute()) {
                // Cập nhật số lượng câu hỏi trong bài trắc nghiệm
                $conn->query("UPDATE quizzes SET total_questions = total_questions - 1 WHERE quiz_id = $quiz_id");
                $success_message = "Đã xóa câu hỏi thành công!";
            } else {
                $error_message = "Có lỗi xảy ra khi xóa câu hỏi: " . $conn->error;
            }
        }
    }
    
    // Cập nhật thông tin bài trắc nghiệm
    if ($_POST['action'] == 'update_quiz') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $time_limit = intval($_POST['time_limit']);
        $passing_score = floatval($_POST['passing_score']);
        $shuffle_questions = isset($_POST['shuffle_questions']) ? 1 : 0;
        $show_answers = isset($_POST['show_answers']) ? 1 : 0;
        
        if (empty($title)) {
            $error_message = "Vui lòng nhập tiêu đề cho bài trắc nghiệm.";
        } else {
            $stmt = $conn->prepare("
                UPDATE quizzes 
                SET title = ?, description = ?, time_limit = ?, passing_score = ?, shuffle_questions = ?, show_answers = ?
                WHERE quiz_id = ?
            ");
            $stmt->bind_param("ssiiiiii", $title, $description, $time_limit, $passing_score, $shuffle_questions, $show_answers, $quiz_id);
            
            if ($stmt->execute()) {
                $success_message = "Đã cập nhật thông tin bài trắc nghiệm thành công!";
                // Tải lại thông tin bài trắc nghiệm
                $quizStmt->execute();
                $quiz = $quizStmt->get_result()->fetch_assoc();
            } else {
                $error_message = "Có lỗi xảy ra khi cập nhật bài trắc nghiệm: " . $conn->error;
            }
        }
    }
}

// Lấy danh sách các câu hỏi
$questionsStmt = $conn->prepare("
    SELECT * FROM quiz_questions 
    WHERE quiz_id = ?
    ORDER BY question_id ASC
");
$questionsStmt->bind_param("i", $quiz_id);
$questionsStmt->execute();
$questions = $questionsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chỉnh Sửa Bài Trắc Nghiệm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font từ Google -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* Reset và styling cơ bản - giống file manage_quizzes.php */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Nunito', 'Quicksand', sans-serif;
      background-color: #f5f7fa;
      line-height: 1.6;
      color: #333;
    }
    
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
    }
    
    .container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }
    
    .page-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 2.5rem;
      color: #1e3c72;
      margin-bottom: 30px;
      text-align: center;
      font-weight: 700;
    }
    
    .alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 600;
    }
    
    .alert-success {
      background-color: rgba(40, 167, 69, 0.15);
      color: #28a745;
      border-left: 4px solid #28a745;
    }
    
    .alert-error {
      background-color: rgba(220, 53, 69, 0.15);
      color: #dc3545;
      border-left: 4px solid #dc3545;
    }
    
    .card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
      overflow: hidden;
    }
    
    .card-header {
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      color: white;
      padding: 15px 20px;
      font-family: 'Montserrat', sans-serif;
    }
    
    .card-body {
      padding: 30px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #444;
    }
    
    .form-input,
    .form-select,
    .form-textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-family: 'Nunito', sans-serif;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-button {
      background: linear-gradient(90deg, #1e3c72, #2a5298);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Nunito', sans-serif;
      font-size: 1rem;
      display: inline-block;
      text-decoration: none;
    }
    
    .question-list {
      margin-top: 20px;
    }
    
    .question-item {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 15px;
      position: relative;
      border-left: 4px solid #1e3c72;
    }
    
    .question-text {
      font-weight: 600;
      margin-bottom: 15px;
      font-size: 1.1rem;
    }
    
    .question-options {
      margin-bottom: 15px;
    }
    
    .option-item {
      display: flex;
      margin-bottom: 8px;
      padding: 8px;
      border-radius: 6px;
    }
    
    .option-item.correct {
      background-color: rgba(40, 167, 69, 0.15);
    }
    
    .option-label {
      font-weight: 600;
      width: 30px;
    }
    
    .question-meta {
      display: flex;
      justify-content: space-between;
      font-size: 0.9rem;
      color: #777;
      border-top: 1px solid #ddd;
      padding-top: 10px;
      margin-top: 10px;
    }
    
    .delete-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      color: #dc3545;
      background: none;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .delete-btn:hover {
      color: #bd2130;
      transform: scale(1.1);
    }
    
    .tabs {
      display: flex;
      margin-bottom: 30px;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .tab {
      flex: 1;
      text-align: center;
      padding: 15px 20px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      border-bottom: 3px solid transparent;
    }
    
    .tab.active {
      background-color: #f8f9fa;
      border-bottom: 3px solid #1e3c72;
      color: #1e3c72;
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }
    
    .quiz-info {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .quiz-info-item {
      flex: 1;
      min-width: 150px;
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      text-align: center;
    }
    
    .info-label {
      display: block;
      color: #777;
      font-size: 0.9rem;
      margin-bottom: 5px;
    }
    
    .info-value {
      font-size: 1.2rem;
      font-weight: 700;
      color: #1e3c72;
    }
    
    .breadcrumbs {
      display: flex;
      margin-bottom: 20px;
      font-size: 0.95rem;
    }
    
    .breadcrumbs a {
      color: #1e3c72;
      text-decoration: none;
      margin-right: 10px;
    }
    
    .breadcrumbs span {
      margin-right: 10px;
      color: #777;
    }
    
    .no-questions {
      text-align: center;
      padding: 40px 20px;
    }
    
    .no-questions-icon {
      font-size: 3rem;
      color: #ddd;
      margin-bottom: 20px;
    }
    
    .no-questions-text {
      font-size: 1.2rem;
      color: #777;
      margin-bottom: 20px;
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
          <li><a href="instructor_dashboard.php">Dashboard</a></li>
          <li><a href="course_management.php">Khóa Học</a></li>
          <li><a href="manage_quizzes.php">Trắc Nghiệm</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <div class="breadcrumbs">
      <a href="instructor_dashboard.php">Dashboard</a>
      <span>&gt;</span>
      <a href="manage_quizzes.php">Quản Lý Trắc Nghiệm</a>
      <span>&gt;</span>
      <span>Chỉnh Sửa Trắc Nghiệm</span>
    </div>
    
    <h1 class="page-title">Chỉnh Sửa Bài Trắc Nghiệm</h1>
    
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="card">
      <div class="card-header">
        <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
        <div><?php echo htmlspecialchars($quiz['course_title']); ?></div>
      </div>
      
      <div class="card-body">
        <div class="quiz-info">
          <div class="quiz-info-item">
            <span class="info-label">Số câu hỏi</span>
            <span class="info-value"><?php echo $quiz['total_questions']; ?></span>
          </div>
          
          <div class="quiz-info-item">
            <span class="info-label">Thời gian làm bài</span>
            <span class="info-value"><?php echo $quiz['time_limit']; ?> phút</span>
          </div>
          
          <div class="quiz-info-item">
            <span class="info-label">Điểm đạt</span>
            <span class="info-value"><?php echo $quiz['passing_score']; ?>%</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs">
      <div class="tab active" data-tab="questions">Câu Hỏi</div>
      <div class="tab" data-tab="add-question">Thêm Câu Hỏi</div>
      <div class="tab" data-tab="settings">Cài Đặt</div>
    </div>
    
    <!-- Tab content -->
    <div id="questions" class="tab-content active">
      <?php if ($questions->num_rows > 0): ?>
        <div class="question-list">
          <?php while ($question = $questions->fetch_assoc()): ?>
            <div class="question-item">
              <form method="post" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?');">
                <input type="hidden" name="action" value="delete_question">
                <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                <button type="submit" class="delete-btn" title="Xóa câu hỏi"><i class="fas fa-trash"></i></button>
              </form>
              
              <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>
              
              <div class="question-options">
                <div class="option-item <?php echo $question['correct_answer'] == 'A' ? 'correct' : ''; ?>">
                  <div class="option-label">A.</div>
                  <div class="option-text"><?php echo htmlspecialchars($question['option_a']); ?></div>
                </div>
                
                <div class="option-item <?php echo $question['correct_answer'] == 'B' ? 'correct' : ''; ?>">
                  <div class="option-label">B.</div>
                  <div class="option-text"><?php echo htmlspecialchars($question['option_b']); ?></div>
                </div>
                
                <?php if (!empty($question['option_c'])): ?>
                <div class="option-item <?php echo $question['correct_answer'] == 'C' ? 'correct' : ''; ?>">
                  <div class="option-label">C.</div>
                  <div class="option-text"><?php echo htmlspecialchars($question['option_c']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($question['option_d'])): ?>
                <div class="option-item <?php echo $question['correct_answer'] == 'D' ? 'correct' : ''; ?>">
                  <div class="option-label">D.</div>
                  <div class="option-text"><?php echo htmlspecialchars($question['option_d']); ?></div>
                </div>
                <?php endif; ?>
              </div>
              
              <?php if (!empty($question['feedback'])): ?>
              <div class="question-feedback">
                <strong>Phản hồi:</strong> <?php echo htmlspecialchars($question['feedback']); ?>
              </div>
              <?php endif; ?>
              
              <div class="question-meta">
                <div>Điểm: <?php echo $question['points']; ?></div>
                <div>Độ khó: <?php echo $question['difficulty'] == 'easy' ? 'Dễ' : ($question['difficulty'] == 'medium' ? 'Trung bình' : 'Khó'); ?></div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="no-questions">
          <div class="no-questions-icon"><i class="fas fa-question-circle"></i></div>
          <div class="no-questions-text">Chưa có câu hỏi nào trong bài trắc nghiệm này.</div>
          <button class="form-button tab-trigger" data-tab="add-question">Thêm Câu Hỏi Ngay</button>
        </div>
      <?php endif; ?>
    </div>
    
    <div id="add-question" class="tab-content">
      <div class="card">
        <div class="card-header">
          <h2>Thêm Câu Hỏi Mới</h2>
        </div>
        
        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="action" value="add_question">
            
            <div class="form-group">
              <label for="question_text" class="form-label">Nội dung câu hỏi *</label>
              <textarea id="question_text" name="question_text" class="form-textarea" required rows="3" placeholder="Nhập nội dung câu hỏi"></textarea>
            </div>
            
            <div class="form-group">
              <label for="option_a" class="form-label">Đáp án A *</label>
              <input type="text" id="option_a" name="option_a" class="form-input" required placeholder="Nhập đáp án A">
            </div>
            
            <div class="form-group">
              <label for="option_b" class="form-label">Đáp án B *</label>
              <input type="text" id="option_b" name="option_b" class="form-input" required placeholder="Nhập đáp án B">
            </div>
            
            <div class="form-group">
              <label for="option_c" class="form-label">Đáp án C (tùy chọn)</label>
              <input type="text" id="option_c" name="option_c" class="form-input" placeholder="Nhập đáp án C">
            </div>
            
            <div class="form-group">
              <label for="option_d" class="form-label">Đáp án D (tùy chọn)</label>
              <input type="text" id="option_d" name="option_d" class="form-input" placeholder="Nhập đáp án D">
            </div>
            
            <div class="form-group">
              <label for="correct_answer" class="form-label">Đáp án đúng *</label>
              <select id="correct_answer" name="correct_answer" class="form-select" required>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="points" class="form-label">Điểm cho câu hỏi</label>
              <input type="number" id="points" name="points" class="form-input" value="1" min="0.1" step="0.1">
            </div>
            
            <div class="form-group">
              <label for="difficulty" class="form-label">Độ khó</label>
              <select id="difficulty" name="difficulty" class="form-select">
                <option value="easy">Dễ</option>
                <option value="medium" selected>Trung bình</option>
                <option value="hard">Khó</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="feedback" class="form-label">Phản hồi (hiển thị sau khi làm bài)</label>
              <textarea id="feedback" name="feedback" class="form-textarea" rows="2" placeholder="Nhập phản hồi hoặc giải thích cho câu hỏi này"></textarea>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="form-button">Thêm Câu Hỏi</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <div id="settings" class="tab-content">
      <div class="card">
        <div class="card-header">
          <h2>Cài Đặt Bài Trắc Nghiệm</h2>
        </div>
        
        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="action" value="update_quiz">
            
            <div class="form-group">
              <label for="title" class="form-label">Tiêu đề bài trắc nghiệm *</label>
              <input type="text" id="title" name="title" class="form-input" required value="<?php echo htmlspecialchars($quiz['title']); ?>">
            </div>
            
            <div class="form-group">
              <label for="description" class="form-label">Mô tả</label>
              <textarea id="description" name="description" class="form-textarea" rows="3"><?php echo htmlspecialchars($quiz['description']); ?></textarea>
            </div>
            
            <div class="form-group">
              <label for="time_limit" class="form-label">Thời gian làm bài (phút)</label>
              <input type="number" id="time_limit" name="time_limit" class="form-input" value="<?php echo $quiz['time_limit']; ?>" min="1" max="180">
            </div>
            
            <div class="form-group">
              <label for="passing_score" class="form-label">Điểm đạt (%)</label>
              <input type="number" id="passing_score" name="passing_score" class="form-input" value="<?php echo $quiz['passing_score']; ?>" min="0" max="100">
            </div>
            
            <div class="form-group">
              <div class="checkbox-group">
                <input type="checkbox" id="shuffle_questions" name="shuffle_questions" <?php echo $quiz['shuffle_questions'] ? 'checked' : ''; ?>>
                <label for="shuffle_questions">Trộn câu hỏi</label>
              </div>
              
              <div class="checkbox-group">
                <input type="checkbox" id="show_answers" name="show_answers" <?php echo $quiz['show_answers'] ? 'checked' : ''; ?>>
                <label for="show_answers">Hiển thị đáp án sau khi làm xong</label>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="form-button">Cập Nhật Cài Đặt</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Tab switching
    document.addEventListener('DOMContentLoaded', function() {
      const tabs = document.querySelectorAll('.tab');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          const tabId = tab.getAttribute('data-tab');
          
          // Remove active class from all tabs and contents
          tabs.forEach(t => t.classList.remove('active'));
          tabContents.forEach(c => c.classList.remove('active'));
          
          // Add active class to clicked tab and corresponding content
          tab.classList.add('active');
          document.getElementById(tabId).classList.add('active');
        });
      });
      
      // Also handle tab switching from buttons with tab-trigger class
      const tabTriggers = document.querySelectorAll('.tab-trigger');
      tabTriggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
          const tabId = trigger.getAttribute('data-tab');
          
          // Trigger click on corresponding tab
          document.querySelector(`.tab[data-tab="${tabId}"]`).click();
        });
      });
    });
  </script>
</body>
</html> 