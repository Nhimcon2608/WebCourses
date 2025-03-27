<?php
// view_submission.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
  $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để xem bài tập đã nộp.";
  header("Location: " . BASE_URL . "app/views/product/home.php");
  exit();
}

// Kiểm tra ID bài tập
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: assignments.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$assignment_id = intval($_GET['id']);

// Lấy thông tin bài tập và bài nộp
$stmt = $conn->prepare("
    SELECT a.assignment_id, a.title as assignment_title, a.description as assignment_description, 
           a.due_date, a.max_score, c.title as course_title, c.course_id,
           s.submission_id, s.content, s.file_path, s.submitted_at, s.score, s.feedback
    FROM Assignments a
    JOIN Courses c ON a.course_id = c.course_id
    JOIN Enrollments e ON c.course_id = e.course_id
    JOIN AssignmentSubmissions s ON a.assignment_id = s.assignment_id
    WHERE a.assignment_id = ? AND e.user_id = ? AND s.user_id = ? AND e.status = 'active'
    LIMIT 1
");
$stmt->bind_param("iii", $assignment_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Kiểm tra nếu không tìm thấy bài tập hoặc bài nộp
if ($result->num_rows == 0) {
  $_SESSION['assignmentError'] = "Không tìm thấy bài nộp cho bài tập này.";
  header("Location: assignment_detail.php?id=$assignment_id");
  exit();
}

$submission = $result->fetch_assoc();
$is_overdue = strtotime($submission['due_date']) < strtotime($submission['submitted_at']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Xem Bài Nộp - <?php echo htmlspecialchars($submission['assignment_title']); ?></title>
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
        max-width: 900px;
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

    /* Card sections */
    .card {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    }

    .card-primary {
        border-left: 4px solid #1e3c72;
    }

    .card-secondary {
        border-left: 4px solid #FF8008;
    }

    .card-success {
        border-left: 4px solid #28a745;
    }

    .card-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.4rem;
        color: #1e3c72;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    /* Meta information */
    .meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .meta-item {
        margin-bottom: 10px;
    }

    .meta-label {
        font-weight: 700;
        color: #1e3c72;
        display: block;
        margin-bottom: 5px;
    }

    .meta-value {
        color: #555;
    }

    /* Submission status */
    .submission-status {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.9rem;
        margin-top: 10px;
    }

    .status-ontime {
        background: #28a745;
        color: white;
    }

    .status-late {
        background: #dc3545;
        color: white;
    }

    /* Content sections */
    .content-section {
        margin-top: 20px;
        line-height: 1.8;
    }

    .content-section h3 {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.2rem;
        color: #1e3c72;
        margin-bottom: 10px;
    }

    .content-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin: 15px 0;
        white-space: pre-wrap;
    }

    /* Score display */
    .score-display {
        display: flex;
        align-items: center;
        margin: 20px 0;
        padding: 15px;
        background: rgba(40, 167, 69, 0.1);
        border-radius: 8px;
    }

    .score-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #28a745;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        font-weight: 700;
        margin-right: 20px;
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    .score-details {
        flex: 1;
    }

    .score-label {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 5px;
    }

    .score-text {
        color: #555;
    }

    /* File attachment */
    .file-attachment {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #f0f8ff;
        border-radius: 8px;
        margin: 15px 0;
    }

    .file-icon {
        margin-right: 15px;
        font-size: 2rem;
        color: #1e3c72;
    }

    .file-details {
        flex: 1;
    }

    .file-name {
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 5px;
    }

    .file-meta {
        font-size: 0.9rem;
        color: #555;
    }

    .file-download {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .file-download:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(30, 60, 114, 0.3);
    }

    /* Buttons */
    .btn-container {
        display: flex;
        gap: 15px;
        margin-top: 25px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-block;
        padding: 12px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        text-align: center;
        min-width: 150px;
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
        .meta-grid {
            grid-template-columns: 1fr;
        }

        .score-display {
            flex-direction: column;
            text-align: center;
        }

        .score-circle {
            margin-right: 0;
            margin-bottom: 15px;
        }

        .file-attachment {
            flex-direction: column;
            text-align: center;
        }

        .file-icon {
            margin-right: 0;
            margin-bottom: 10px;
        }

        .btn-container {
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
          <li><a href="assignments.php">Bài Tập</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <h1 class="page-title">Bài Tập Đã Nộp</h1>
    
    <!-- Assignment info -->
    <div class="card card-primary">
      <h2 class="card-title">Thông Tin Bài Tập</h2>
      <div class="meta-grid">
        <div class="meta-item">
          <span class="meta-label">Bài tập:</span>
          <span class="meta-value"><?php echo htmlspecialchars($submission['assignment_title']); ?></span>
        </div>
        
        <div class="meta-item">
          <span class="meta-label">Khóa học:</span>
          <span class="meta-value"><?php echo htmlspecialchars($submission['course_title']); ?></span>
        </div>
        
        <div class="meta-item">
          <span class="meta-label">Hạn nộp:</span>
          <span class="meta-value"><?php echo date('d/m/Y H:i', strtotime($submission['due_date'])); ?></span>
        </div>
        
        <div class="meta-item">
          <span class="meta-label">Điểm tối đa:</span>
          <span class="meta-value"><?php echo $submission['max_score']; ?></span>
        </div>
      </div>
      
      <div class="content-section">
        <h3>Mô tả bài tập:</h3>
        <div class="content-box">
          <?php echo nl2br(htmlspecialchars($submission['assignment_description'])); ?>
        </div>
      </div>
    </div>
    
    <!-- Submission details -->
    <div class="card card-success">
      <h2 class="card-title">Thông Tin Bài Nộp</h2>
      
      <div class="meta-grid">
        <div class="meta-item">
          <span class="meta-label">Thời gian nộp:</span>
          <span class="meta-value"><?php echo date('d/m/Y H:i:s', strtotime($submission['submitted_at'])); ?></span>
          
          <?php if ($is_overdue): ?>
            <span class="submission-status status-late">Nộp muộn</span>
          <?php else: ?>
            <span class="submission-status status-ontime">Nộp đúng hạn</span>
          <?php endif; ?>
        </div>
        
        <?php if (isset($submission['score'])): ?>
        <div class="meta-item">
          <span class="meta-label">Trạng thái chấm:</span>
          <span class="meta-value">Đã chấm điểm</span>
        </div>
        <?php else: ?>
        <div class="meta-item">
          <span class="meta-label">Trạng thái chấm:</span>
          <span class="meta-value">Chưa chấm điểm</span>
        </div>
        <?php endif; ?>
      </div>
      
      <?php if (isset($submission['score'])): ?>
      <div class="score-display">
        <div class="score-circle"><?php echo $submission['score']; ?></div>
        <div class="score-details">
          <div class="score-label">Điểm số</div>
          <div class="score-text"><?php echo $submission['score']; ?> / <?php echo $submission['max_score']; ?> điểm</div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($submission['content'])): ?>
      <div class="content-section">
        <h3>Nội dung bài làm:</h3>
        <div class="content-box">
          <?php echo nl2br(htmlspecialchars($submission['content'])); ?>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($submission['file_path'])): ?>
      <div class="file-attachment">
        <div class="file-icon">📎</div>
        <div class="file-details">
          <div class="file-name">Tệp đính kèm</div>
          <div class="file-meta">
            <?php 
            $file_extension = pathinfo($submission['file_path'], PATHINFO_EXTENSION);
            echo strtoupper($file_extension); 
            ?>
          </div>
        </div>
        <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($submission['file_path']); ?>" class="file-download" target="_blank">Tải xuống</a>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($submission['feedback'])): ?>
      <div class="content-section">
        <h3>Nhận xét của giảng viên:</h3>
        <div class="content-box">
          <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- Actions -->
    <div class="btn-container">
      <?php if (strtotime($submission['due_date']) > time()): ?>
      <a href="submit_assignment.php?id=<?php echo $assignment_id; ?>" class="btn btn-secondary">Cập nhật bài nộp</a>
      <?php endif; ?>
      
      <a href="assignment_detail.php?id=<?php echo $assignment_id; ?>" class="btn btn-primary">Xem chi tiết bài tập</a>
      <a href="assignments.php" class="btn btn-primary">Quay lại danh sách</a>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>
</body>
</html> 