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
            padding: 15px 0;
            text-align: center;
            width: 100%;
        }
        
        .header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: normal;
        }
        
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 0;
        }
        
        .white-box {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        h3 {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #333;
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
            min-height: 80px;
        }
        
        .file-input {
            margin-top: 5px;
        }
        
        .btn-blue {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 7px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .lesson-item {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .lesson-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .lesson-description {
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .btn-container {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Quản Lý Bài Giảng</h2>
    </div>
    
    <div class="container">
        <div class="white-box">
            <h3>Thêm Bài Giảng Mới</h3>
            
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
                <div class="file-input">
                    <input type="file" id="attachment" name="attachment">
                </div>
            </div>
            
            <button type="submit" class="btn-blue">Thêm Bài Giảng</button>
        </div>
        
        <h3>Danh Sách Bài Giảng</h3>
        
        <div class="lesson-item">
            <div class="lesson-title">Bài Giảng 1</div>
            <div class="lesson-description">Mô tả ngắn về bài giảng 1</div>
            <div class="btn-container">
                <a href="#" class="btn-edit">Sửa</a>
                <a href="#" class="btn-delete">Xóa</a>
            </div>
        </div>
        
        <div class="lesson-item">
            <div class="lesson-title">Bài Giảng 2</div>
            <div class="lesson-description">Mô tả ngắn về bài giảng 2</div>
            <div class="btn-container">
                <a href="#" class="btn-edit">Sửa</a>
                <a href="#" class="btn-delete">Xóa</a>
            </div>
        </div>
    </div>
</body>
</html> 