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

// Kiểm tra bảng quizzes có tồn tại chưa
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'quizzes'");
if ($checkTable && $checkTable->num_rows > 0) {
    $tableExists = true;
}

// Tạo bảng quizzes nếu chưa tồn tại
if (!$tableExists) {
  $conn->query("
    CREATE TABLE quizzes (
      quiz_id INT AUTO_INCREMENT PRIMARY KEY,
      course_id INT NOT NULL,
      lesson_id INT NULL,
      title VARCHAR(255) NOT NULL,
      description TEXT,
      time_limit INT DEFAULT 30,
      total_questions INT DEFAULT 0,
      passing_score FLOAT DEFAULT 60,
      shuffle_questions BOOLEAN DEFAULT TRUE,
      show_answers BOOLEAN DEFAULT TRUE,
      active BOOLEAN DEFAULT TRUE,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (course_id) REFERENCES courses(course_id),
      FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id)
    )
  ");
}

// Kiểm tra bảng quiz_questions có tồn tại chưa
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'quiz_questions'");
if ($checkTable && $checkTable->num_rows > 0) {
    $tableExists = true;
}

// Tạo bảng quiz_questions nếu chưa tồn tại
if (!$tableExists) {
  $conn->query("
    CREATE TABLE quiz_questions (
      question_id INT AUTO_INCREMENT PRIMARY KEY,
      quiz_id INT NOT NULL,
      question_text TEXT NOT NULL,
      option_a TEXT NOT NULL,
      option_b TEXT NOT NULL,
      option_c TEXT,
      option_d TEXT,
      correct_answer CHAR(1) NOT NULL,
      points FLOAT DEFAULT 1,
      feedback TEXT,
      question_type ENUM('single', 'multiple', 'true_false') DEFAULT 'single',
      difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
      media_url VARCHAR(255),
      FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
    )
  ");
}

// Xử lý tạo bài trắc nghiệm mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'create_quiz') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $course_id = intval($_POST['course_id']);
        $lesson_id = !empty($_POST['lesson_id']) ? intval($_POST['lesson_id']) : NULL;
        $time_limit = intval($_POST['time_limit']);
        $passing_score = floatval($_POST['passing_score']);
        $shuffle_questions = isset($_POST['shuffle_questions']) ? 1 : 0;
        $show_answers = isset($_POST['show_answers']) ? 1 : 0;
        
        // Xác minh khóa học thuộc về giảng viên
        $checkCourse = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND instructor_id = ?");
        $checkCourse->bind_param("ii", $course_id, $user_id);
        $checkCourse->execute();
        $courseResult = $checkCourse->get_result();
        
        if ($courseResult->num_rows == 0) {
            $error_message = "Bạn không có quyền tạo trắc nghiệm cho khóa học này.";
        } else if (empty($title)) {
            $error_message = "Vui lòng nhập tiêu đề cho bài trắc nghiệm.";
        } else {
            // Tạo bài trắc nghiệm mới
            $stmt = $conn->prepare("
                INSERT INTO quizzes (course_id, lesson_id, title, description, time_limit, passing_score, shuffle_questions, show_answers)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iissiiii", $course_id, $lesson_id, $title, $description, $time_limit, $passing_score, $shuffle_questions, $show_answers);
            
            if ($stmt->execute()) {
                $quiz_id = $conn->insert_id;
                $success_message = "Đã tạo bài trắc nghiệm thành công! Bây giờ bạn có thể thêm câu hỏi.";
                header("Location: edit_quiz.php?id=" . $quiz_id);
                exit();
            } else {
                $error_message = "Có lỗi xảy ra khi tạo bài trắc nghiệm: " . $conn->error;
            }
        }
    }
    
    if ($_POST['action'] == 'delete_quiz' && isset($_POST['quiz_id'])) {
        $quiz_id = intval($_POST['quiz_id']);
        
        // Xác minh bài trắc nghiệm thuộc về khóa học của giảng viên
        $checkQuiz = $conn->prepare("
            SELECT q.quiz_id FROM quizzes q
            JOIN courses c ON q.course_id = c.course_id
            WHERE q.quiz_id = ? AND c.instructor_id = ?
        ");
        $checkQuiz->bind_param("ii", $quiz_id, $user_id);
        $checkQuiz->execute();
        $quizResult = $checkQuiz->get_result();
        
        if ($quizResult->num_rows == 0) {
            $error_message = "Bạn không có quyền xóa bài trắc nghiệm này.";
        } else {
            // Xóa bài trắc nghiệm
            $stmt = $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ?");
            $stmt->bind_param("i", $quiz_id);
            
            if ($stmt->execute()) {
                $success_message = "Đã xóa bài trắc nghiệm thành công!";
            } else {
                $error_message = "Có lỗi xảy ra khi xóa bài trắc nghiệm: " . $conn->error;
            }
        }
    }
}

// Lấy danh sách các khóa học của giảng viên
$courseStmt = $conn->prepare("
    SELECT course_id, title 
    FROM courses 
    WHERE instructor_id = ?
    ORDER BY title ASC
");
$courseStmt->bind_param("i", $user_id);
$courseStmt->execute();
$courses = $courseStmt->get_result();

// Lấy danh sách bài trắc nghiệm của giảng viên
$quizStmt = $conn->prepare("
    SELECT q.quiz_id, q.title, q.description, q.time_limit, q.total_questions, q.created_at,
           c.title as course_title, c.course_id,
           IFNULL(l.title, 'Không có bài học') as lesson_title,
           (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.quiz_id) as attempts_count
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    LEFT JOIN lessons l ON q.lesson_id = l.lesson_id
    WHERE c.instructor_id = ?
    ORDER BY q.created_at DESC
");
$quizStmt->bind_param("i", $user_id);
$quizStmt->execute();
$quizzes = $quizStmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản Lý Bài Trắc Nghiệm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font từ Google -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

    /* Alert messages */
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

    /* Tabs */
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

    .tab:hover:not(.active) {
        background-color: #f8f9fa;
        color: #2a5298;
    }

    /* Tab content */
    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.6s ease-in-out;
    }

    /* Form styling */
    .form-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-bottom: 30px;
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

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        border-color: #1e3c72;
        outline: none;
        box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.2);
    }

    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .checkbox-input {
        margin-right: 10px;
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

    .form-button:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(30, 60, 114, 0.3);
    }

    .form-button-secondary {
        background: linear-gradient(90deg, #FF8008, #FFA100);
    }

    .form-button-secondary:hover {
        background: linear-gradient(90deg, #FFA100, #FF8008);
    }

    .form-button-delete {
        background: linear-gradient(90deg, #d33, #dc3545);
    }

    .form-button-delete:hover {
        background: linear-gradient(90deg, #dc3545, #d33);
    }

    /* Quiz card grid */
    .quiz-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .quiz-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .quiz-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .quiz-header {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: white;
        padding: 15px 20px;
    }

    .quiz-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.25rem;
        margin-bottom: 5px;
    }

    .quiz-course {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .quiz-body {
        padding: 20px;
        flex-grow: 1;
    }

    .quiz-desc {
        margin-bottom: 15px;
        color: #555;
        font-size: 0.95rem;
    }

    .quiz-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }

    .quiz-stat {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 6px;
        font-size: 0.9rem;
        flex: 1;
        min-width: 120px;
        text-align: center;
    }

    .stat-label {
        display: block;
        color: #777;
        margin-bottom: 5px;
        font-size: 0.8rem;
    }

    .stat-value {
        font-weight: 700;
        color: #1e3c72;
        font-size: 1.1rem;
    }

    .quiz-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        display: flex;
        justify-content: space-between;
        border-top: 1px solid #eee;
    }

    .quiz-actions form {
        display: inline;
    }

    /* Loader and animations */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
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

    /* Responsive */
    @media (max-width: 768px) {
        .tabs {
            flex-direction: column;
        }
        
        .quiz-grid {
            grid-template-columns: 1fr;
        }
        
        .quiz-footer {
            flex-direction: column;
            gap: 15px;
        }
        
        .quiz-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
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
          <li><a href="instructor_dashboard.php">Dashboard</a></li>
          <li><a href="course_management.php">Khóa Học</a></li>
          <li><a href="manage_quizzes.php">Trắc Nghiệm</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <h1 class="page-title">Quản Lý Bài Trắc Nghiệm</h1>
    
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div class="tabs">
      <div class="tab active" data-tab="quizzes">Danh Sách Trắc Nghiệm</div>
      <div class="tab" data-tab="create">Tạo Trắc Nghiệm Mới</div>
    </div>
    
    <!-- Tab content -->
    <div id="quizzes" class="tab-content active">
      <?php if ($quizzes->num_rows > 0): ?>
        <div class="quiz-grid">
          <?php while ($quiz = $quizzes->fetch_assoc()): ?>
            <div class="quiz-card">
              <div class="quiz-header">
                <h3 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                <div class="quiz-course"><?php echo htmlspecialchars($quiz['course_title']); ?></div>
              </div>
              
              <div class="quiz-body">
                <div class="quiz-desc">
                  <?php echo !empty($quiz['description']) ? htmlspecialchars(substr($quiz['description'], 0, 100)) . '...' : 'Không có mô tả'; ?>
                </div>
                
                <div class="quiz-stats">
                  <div class="quiz-stat">
                    <span class="stat-label">Câu hỏi</span>
                    <span class="stat-value"><?php echo $quiz['total_questions']; ?></span>
                  </div>
                  
                  <div class="quiz-stat">
                    <span class="stat-label">Thời gian</span>
                    <span class="stat-value"><?php echo $quiz['time_limit']; ?> phút</span>
                  </div>
                  
                  <div class="quiz-stat">
                    <span class="stat-label">Lượt làm</span>
                    <span class="stat-value"><?php echo $quiz['attempts_count']; ?></span>
                  </div>
                </div>
              </div>
              
              <div class="quiz-footer">
                <div class="quiz-date">
                  <?php echo date('d/m/Y', strtotime($quiz['created_at'])); ?>
                </div>
                
                <div class="quiz-actions">
                  <a href="edit_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="form-button">Sửa</a>
                  
                  <form method="post" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài trắc nghiệm này?');">
                    <input type="hidden" name="action" value="delete_quiz">
                    <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                    <button type="submit" class="form-button form-button-delete">Xóa</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="no-quizzes">
          <div class="no-quizzes-icon"><i class="fas fa-file-alt"></i></div>
          <div class="no-quizzes-text">Bạn chưa có bài trắc nghiệm nào. Hãy tạo bài trắc nghiệm đầu tiên!</div>
          <button class="form-button tab-trigger" data-tab="create">Tạo Trắc Nghiệm Ngay</button>
        </div>
      <?php endif; ?>
    </div>
    
    <div id="create" class="tab-content">
      <div class="form-card">
        <form method="post" action="">
          <input type="hidden" name="action" value="create_quiz">
          
          <div class="form-group">
            <label for="title" class="form-label">Tiêu đề bài trắc nghiệm *</label>
            <input type="text" id="title" name="title" class="form-input" required placeholder="Nhập tiêu đề bài trắc nghiệm">
          </div>
          
          <div class="form-group">
            <label for="description" class="form-label">Mô tả</label>
            <textarea id="description" name="description" class="form-textarea" placeholder="Nhập mô tả cho bài trắc nghiệm"></textarea>
          </div>
          
          <div class="form-group">
            <label for="course_id" class="form-label">Khóa học *</label>
            <select id="course_id" name="course_id" class="form-select" required>
              <option value="">Chọn khóa học</option>
              <?php while ($course = $courses->fetch_assoc()): ?>
                <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="lesson_id" class="form-label">Bài học (tùy chọn)</label>
            <select id="lesson_id" name="lesson_id" class="form-select">
              <option value="">Không thuộc bài học nào</option>
              <!-- Các bài học sẽ được tải bằng JavaScript khi chọn khóa học -->
            </select>
          </div>
          
          <div class="form-group">
            <label for="time_limit" class="form-label">Thời gian làm bài (phút)</label>
            <input type="number" id="time_limit" name="time_limit" class="form-input" value="30" min="1" max="180">
          </div>
          
          <div class="form-group">
            <label for="passing_score" class="form-label">Điểm đạt (%)</label>
            <input type="number" id="passing_score" name="passing_score" class="form-input" value="60" min="0" max="100">
          </div>
          
          <div class="form-group">
            <div class="checkbox-group">
              <input type="checkbox" id="shuffle_questions" name="shuffle_questions" class="checkbox-input" checked>
              <label for="shuffle_questions">Trộn câu hỏi</label>
            </div>
            
            <div class="checkbox-group">
              <input type="checkbox" id="show_answers" name="show_answers" class="checkbox-input" checked>
              <label for="show_answers">Hiển thị đáp án sau khi làm xong</label>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="form-button">Tạo Bài Trắc Nghiệm</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Tab switching
    document.addEventListener('DOMContentLoaded', function() {
      // Tab switching
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
      
      // Course-lesson dynamic loading
      const courseSelect = document.getElementById('course_id');
      const lessonSelect = document.getElementById('lesson_id');
      
      courseSelect.addEventListener('change', function() {
        const courseId = this.value;
        lessonSelect.innerHTML = '<option value="">Không thuộc bài học nào</option>';
        
        if (courseId) {
          // Fetch lessons for the selected course
          fetch(`get_lessons.php?course_id=${courseId}`)
            .then(response => response.json())
            .then(data => {
              data.forEach(lesson => {
                const option = document.createElement('option');
                option.value = lesson.lesson_id;
                option.textContent = lesson.title;
                lessonSelect.appendChild(option);
              });
            })
            .catch(error => console.error('Error fetching lessons:', error));
        }
      });
    });
  </script>
</body>
</html> 