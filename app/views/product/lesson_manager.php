<?php
// Basic page setup
session_start();

// Set default BASE_URL if not defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WebCourses/');
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
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-color: #f0f0f0;
            padding: 0;
            margin: 0;
        }
        
        .header {
            background-color: #3498db;
            color: white;
            padding: 15px 0;
            text-align: center;
            width: 100%;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .content-box {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .lesson-list {
            margin-top: 30px;
        }
        
        .lesson-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: white;
        }
        
        .lesson-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .lesson-description {
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .lesson-actions {
            margin-top: 10px;
        }
        
        .file-input {
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Quản Lý Bài Giảng</h2>
    </div>
    
    <div class="container">
        <div class="content-box">
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
                    <input type="file" id="attachment" name="attachment" class="form-control file-input">
                </div>
                
                <button type="submit" name="add_lesson" class="btn btn-primary">Thêm Bài Giảng</button>
            </form>
        </div>
        
        <div class="lesson-list">
            <h3>Danh Sách Bài Giảng</h3>
            
            <?php foreach ($lessons as $lesson): ?>
            <div class="lesson-item">
                <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                <div class="lesson-description"><?php echo htmlspecialchars($lesson['description']); ?></div>
                <div class="lesson-actions">
                    <a href="#" class="btn btn-success">Sửa</a>
                    <a href="#" class="btn btn-danger">Xóa</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 