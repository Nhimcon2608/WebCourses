<?php
// Set the proper include path
define('BASE_URL', '/WebCourses/');
session_start();

// Check if user is admin or teacher before allowing this script to run
if (!isset($_SESSION['user_id']) || !($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'teacher')) {
    echo '<div style="color:red; padding:20px; font-family:Arial; background:#f8d7da; border-radius:5px; margin:20px;">
         <h2>Không có quyền truy cập</h2>
         <p>Bạn phải đăng nhập với vai trò quản trị viên hoặc giảng viên để sử dụng công cụ này.</p>
         <a href="' . BASE_URL . 'app/views/product/home.php">Quay lại trang chủ</a>
         </div>';
    exit;
}

$message = '';
$success = false;

// Check if the form was submitted
if (isset($_POST['populate'])) {
    ob_start();
    include 'populate_assignments.php';
    $result = ob_get_clean();
    
    $message = nl2br($result);
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo Dữ Liệu Bài Tập Mẫu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', 'Quicksand', sans-serif;
            background-color: rgb(255, 255, 255);
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            font-family: 'Montserrat', sans-serif;
            color: #1e3c72;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 3px solid #FFC107;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: linear-gradient(90deg, #2a5298, #1e3c72);
            transform: translateY(-2px);
        }
        
        .result {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: monospace;
        }
        
        .success {
            color: #28a745;
        }
        
        .warning {
            color: #ffc107;
        }
        
        .error {
            color: #dc3545;
        }
        
        .navigation {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        a {
            color: #1e3c72;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Tạo Dữ Liệu Bài Tập Mẫu</h1>
    
    <div class="card">
        <h2>Thông tin</h2>
        <p>Công cụ này sẽ tạo các bài tập mẫu trong cơ sở dữ liệu để sinh viên có thể trải nghiệm tính năng nộp bài tập.</p>
        <p>Nó cũng sẽ tạo bảng <code>AssignmentSubmissions</code> nếu bảng này chưa tồn tại.</p>
        
        <form method="post" action="">
            <button type="submit" name="populate" class="btn">Tạo dữ liệu mẫu</button>
        </form>
        
        <?php if (!empty($message)): ?>
            <div class="result <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="navigation">
        <a href="<?php echo BASE_URL; ?>app/views/product/teacher_dashboard.php">Dashboard Giảng Viên</a>
        <a href="<?php echo BASE_URL; ?>app/views/product/home.php">Trang Chủ</a>
    </div>
</body>
</html> 