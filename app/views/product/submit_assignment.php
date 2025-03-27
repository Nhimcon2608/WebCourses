<?php
// Assignment submission form
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
    $_SESSION['loginError'] = "Vui lòng đăng nhập với vai trò sinh viên để nộp bài tập.";
    header("Location: " . BASE_URL . "app/views/product/home.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Check if assignment_submissions table exists
$tableExists = false;
$checkTable = $conn->query("SHOW TABLES LIKE 'assignment_submissions'");
if ($checkTable && $checkTable->num_rows > 0) {
    $tableExists = true;
} else {
    // Create the table if it doesn't exist
    $conn->query("
        CREATE TABLE assignment_submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            assignment_id INT NOT NULL,
            submission_text TEXT,
            file_path VARCHAR(255),
            grade INT DEFAULT NULL,
            feedback TEXT,
            submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (assignment_id) REFERENCES assignments(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $tableExists = true;
}

// Get assignment ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . BASE_URL . "app/views/product/assignments.php");
    exit();
}

$assignment_id = intval($_GET['id']);

// Check if the assignment exists and belongs to a course the student is enrolled in
$stmt = $conn->prepare("
    SELECT a.id as assignment_id, a.title, a.description, a.due_date, a.max_points, c.title as course_title
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE a.id = ? AND e.user_id = ? AND e.status = 'active'
");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: " . BASE_URL . "app/views/product/assignments.php");
    exit();
}

$assignment = $result->fetch_assoc();

// Check if the student has already submitted this assignment
$stmt = $conn->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$submissionResult = $stmt->get_result();
$hasSubmitted = $submissionResult->num_rows > 0;
$submission = $hasSubmitted ? $submissionResult->fetch_assoc() : null;

// Check if the assignment is past due date
$isPastDue = strtotime($assignment['due_date']) < time();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $submissionText = trim($_POST['submission_text']);
    $filePath = null;
    
    // File upload handling
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
        $uploadDir = __DIR__ . '/../../uploads/assignments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . $_FILES['submission_file']['name'];
        $filePath = 'uploads/assignments/' . $fileName;
        $uploadFile = $uploadDir . $fileName;
        
        // Check file size (5MB max)
        if ($_FILES['submission_file']['size'] > 5 * 1024 * 1024) {
            $error_message = "Kích thước file không được vượt quá 5MB.";
        } 
        // Check file type
        elseif (!in_array($_FILES['submission_file']['type'], ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/zip'])) {
            $error_message = "Chỉ chấp nhận các file có định dạng PDF, DOC, DOCX, TXT hoặc ZIP.";
        }
        // Try to upload the file
        elseif (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $uploadFile)) {
            $error_message = "Có lỗi xảy ra khi tải file lên. Vui lòng thử lại sau.";
        }
    }
    
    // If no errors, save the submission
    if (empty($error_message)) {
        if ($hasSubmitted) {
            // Update existing submission
            $stmt = $conn->prepare("
                UPDATE assignment_submissions 
                SET submission_text = ?, file_path = COALESCE(?, file_path), updated_at = NOW()
                WHERE assignment_id = ? AND user_id = ?
            ");
            $stmt->bind_param("ssii", $submissionText, $filePath, $assignment_id, $user_id);
        } else {
            // Create new submission
            $stmt = $conn->prepare("
                INSERT INTO assignment_submissions (user_id, assignment_id, submission_text, file_path)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiss", $user_id, $assignment_id, $submissionText, $filePath);
        }
        
        if ($stmt->execute()) {
            $success_message = "Bài tập đã được nộp thành công!";
            // Refresh submission data
            $hasSubmitted = true;
            $stmt = $conn->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $assignment_id, $user_id);
            $stmt->execute();
            $submissionResult = $stmt->get_result();
            $submission = $submissionResult->fetch_assoc();
        } else {
            $error_message = "Có lỗi xảy ra khi lưu bài nộp. Vui lòng thử lại sau.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nộp Bài Tập - Học Tập Trực Tuyến</title>
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
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
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
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1);
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .assignment-title {
            font-size: 1.8rem;
            color: #1e3c72;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .assignment-course {
            background: #f0f0f0;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e3c72;
            display: inline-block;
            margin-bottom: 15px;
        }

        .assignment-meta {
            margin-bottom: 20px;
        }

        .assignment-meta p {
            margin-bottom: 8px;
            color: #555;
        }

        .assignment-status {
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 700;
            display: inline-block;
            margin-left: 10px;
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

        /* Form styling */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e3c72;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Nunito', sans-serif;
            font-size: 1rem;
            min-height: 200px;
            resize: vertical;
        }

        input[type="file"] {
            display: block;
            margin-top: 8px;
        }

        .file-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 0.9rem;
        }

        .file-info li {
            margin-bottom: 5px;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: 'Nunito', sans-serif;
            cursor: pointer;
            border: none;
            font-size: 1rem;
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
            background: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Submission details */
        .submission-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 25px;
            border-left: 4px solid #28a745;
        }

        .submission-details h3 {
            color: #1e3c72;
            margin-bottom: 15px;
            font-family: 'Montserrat', sans-serif;
        }

        .submission-date {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .submission-text {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }

        .submission-file {
            display: inline-block;
            padding: 8px 15px;
            background: #e9ecef;
            border-radius: 6px;
            color: #1e3c72;
            text-decoration: none;
            margin-top: 10px;
        }

        .submission-file:hover {
            background: #dee2e6;
        }

        /* Due date warning */
        .due-date-warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
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
            .assignment-header {
                flex-direction: column;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
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
        <h1 class="page-title">Nộp Bài Tập</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="assignment-header">
                <div>
                    <h2 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h2>
                    <span class="assignment-course"><?php echo htmlspecialchars($assignment['course_title']); ?></span>
                </div>
                <div>
                    <?php if ($hasSubmitted): ?>
                        <span class="assignment-status status-submitted">Đã nộp</span>
                    <?php elseif ($isPastDue): ?>
                        <span class="assignment-status status-overdue">Quá hạn</span>
                    <?php else: ?>
                        <span class="assignment-status status-pending">Đang chờ</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="assignment-meta">
                <p><strong>Mô tả:</strong> <?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                <p><strong>Hạn nộp:</strong> <?php echo date('d/m/Y H:i', strtotime($assignment['due_date'])); ?></p>
                <p><strong>Điểm tối đa:</strong> <?php echo $assignment['max_points']; ?> điểm</p>
            </div>
            
            <?php if ($isPastDue && !$hasSubmitted): ?>
                <div class="due-date-warning">
                    <strong><i class="fas fa-exclamation-triangle"></i> Cảnh báo:</strong> Bài tập này đã quá hạn nộp. Bạn vẫn có thể nộp, nhưng có thể bị trừ điểm.
                </div>
            <?php endif; ?>
            
            <?php if ($hasSubmitted): ?>
                <div class="submission-details">
                    <h3>Bài đã nộp</h3>
                    <p class="submission-date">Đã nộp vào: <?php echo date('d/m/Y H:i', strtotime($submission['submission_date'])); ?></p>
                    
                    <?php if (!empty($submission['submission_text'])): ?>
                        <div class="submission-text">
                            <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($submission['file_path'])): ?>
                        <p><strong>File đính kèm:</strong></p>
                        <a href="<?php echo BASE_URL . $submission['file_path']; ?>" class="submission-file" target="_blank">
                            <i class="fas fa-file"></i> Tải xuống file
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($submission['grade'] !== null): ?>
                        <div class="grade-info" style="margin-top: 20px;">
                            <p><strong>Điểm số:</strong> <?php echo $submission['grade']; ?>/<?php echo $assignment['max_points']; ?></p>
                            <?php if (!empty($submission['feedback'])): ?>
                                <p><strong>Nhận xét:</strong></p>
                                <div class="feedback-text" style="background: #e9ecef; padding: 15px; border-radius: 6px;">
                                    <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p style="margin-top: 15px; font-style: italic;">Bài tập đang chờ chấm điểm.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Resubmit form -->
                <form method="post" action="" enctype="multipart/form-data" style="margin-top: 30px;">
                    <h3>Nộp lại bài</h3>
                    <div class="form-group">
                        <label for="submission_text">Nội dung bài làm:</label>
                        <textarea id="submission_text" name="submission_text"><?php echo $submission['submission_text']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="submission_file">File đính kèm:</label>
                        <input type="file" id="submission_file" name="submission_file">
                        <div class="file-info">
                            <p><strong>Lưu ý:</strong></p>
                            <ul style="margin-left: 20px;">
                                <li>Kích thước file tối đa: 5MB</li>
                                <li>Định dạng file hỗ trợ: PDF, DOC, DOCX, TXT, ZIP</li>
                                <li>Nếu không chọn file mới, file cũ sẽ được giữ lại</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" name="submit" class="btn btn-primary">Nộp lại bài</button>
                        <a href="assignments.php" class="btn btn-secondary">Quay lại danh sách</a>
                    </div>
                </form>
            <?php else: ?>
                <!-- First submission form -->
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="submission_text">Nội dung bài làm:</label>
                        <textarea id="submission_text" name="submission_text" placeholder="Nhập nội dung bài làm của bạn ở đây..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="submission_file">File đính kèm:</label>
                        <input type="file" id="submission_file" name="submission_file">
                        <div class="file-info">
                            <p><strong>Lưu ý:</strong></p>
                            <ul style="margin-left: 20px;">
                                <li>Kích thước file tối đa: 5MB</li>
                                <li>Định dạng file hỗ trợ: PDF, DOC, DOCX, TXT, ZIP</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" name="submit" class="btn btn-primary">Nộp bài tập</button>
                        <a href="assignments.php" class="btn btn-secondary">Quay lại danh sách</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
    </footer>
</body>
</html> 