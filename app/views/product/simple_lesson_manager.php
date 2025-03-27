<?php
// Basic session handling
session_start();

// Set default BASE_URL if not defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WebCourses/');
}

// Simple database connection
try {
    $conn = new mysqli("localhost", "root", "", "webcourses");
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Handle form submission for adding lessons
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_lesson'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (!empty($title)) {
        // In a real application, insert into database here
        $success = "Bài giảng đã được thêm thành công!";
    } else {
        $error = "Vui lòng nhập tiêu đề bài giảng.";
    }
}

// Sample data for demonstration
$lessons = [
    [
        'id' => 1,
        'title' => 'Bài Giảng 1',
        'description' => 'Mô tả ngắn về bài giảng 1'
    ],
    [
        'id' => 2,
        'title' => 'Bài Giảng 2',
        'description' => 'Mô tả ngắn về bài giảng 2'
    ]
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Bài Giảng</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .page-header {
            background: #3498db;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        
        .content-wrapper {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .lesson-list {
            margin-top: 30px;
        }
        
        .lesson-item {
            background: #f9f9f9;
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .lesson-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .lesson-description {
            color: #666;
            margin-bottom: 10px;
        }
        
        .lesson-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-edit {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h2>Quản Lý Bài Giảng</h2>
    </div>
    
    <div class="content-wrapper">
        <h3>Thêm Bài Giảng Mới</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Tiêu Đề</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="Nhập tiêu đề bài giảng">
            </div>
            
            <div class="form-group">
                <label for="description">Mô Tả</label>
                <textarea id="description" name="description" class="form-control" placeholder="Nhập mô tả bài giảng"></textarea>
            </div>
            
            <div class="form-group">
                <label for="attachment">Tệp Đính Kèm</label>
                <input type="file" id="attachment" name="attachment" class="form-control">
            </div>
            
            <button type="submit" name="add_lesson" class="btn-primary">Thêm Bài Giảng</button>
        </form>
    </div>
    
    <div class="lesson-list">
        <h3>Danh Sách Bài Giảng</h3>
        
        <?php foreach ($lessons as $lesson): ?>
        <div class="lesson-item">
            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
            <div class="lesson-description"><?php echo htmlspecialchars($lesson['description']); ?></div>
            <div class="lesson-actions">
                <a href="#" class="btn-edit">Sửa</a>
                <a href="#" class="btn-delete">Xóa</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html> 