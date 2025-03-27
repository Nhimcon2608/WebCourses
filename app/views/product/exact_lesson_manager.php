<?php
// Basic setup
session_start();

// Sample data for the lesson list
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
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        
        .header {
            background-color: #3498db;
            color: white;
            padding: 20px 0;
            text-align: center;
            width: 100%;
        }
        
        .header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: normal;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .add-lesson-box {
            background-color: white;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .add-lesson-box h3 {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: normal;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .lesson-list-title {
            font-size: 18px;
            margin: 20px 0 15px 0;
            font-weight: bold;
            color: #333;
        }
        
        .lesson-item {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .lesson-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .lesson-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .edit-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Quản Lý Bài Giảng</h2>
    </div>
    
    <div class="container">
        <div class="add-lesson-box">
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
                    <input type="file" id="attachment" name="attachment">
                </div>
                
                <button type="submit" name="add_lesson" class="submit-btn">Thêm Bài Giảng</button>
            </form>
        </div>
        
        <h3 class="lesson-list-title">Danh Sách Bài Giảng</h3>
        
        <?php foreach ($lessons as $lesson): ?>
        <div class="lesson-item">
            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
            <div class="lesson-description">Mô tả ngắn về <?php echo htmlspecialchars($lesson['title']); ?></div>
            <div class="action-btns">
                <a href="#" class="edit-btn">Sửa</a>
                <a href="#" class="delete-btn">Xóa</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html> 