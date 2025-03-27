<?php
/**
 * Script to add web programming assignments to the database
 */

// Database credentials
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

// Connect to database
try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<!DOCTYPE html>
    <html lang='vi'>
    <head>
        <meta charset='UTF-8'>
        <title>Thêm Bài Tập Web Programming</title>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 1000px;
                margin: 0 auto;
                padding: 20px;
            }
            h1 {
                color: #1e3c72;
                border-bottom: 3px solid #FFC107;
                padding-bottom: 10px;
            }
            .success {
                color: #28a745;
                margin-bottom: 10px;
            }
            .error {
                color: #dc3545;
                margin-bottom: 10px;
            }
            ul {
                list-style-type: none;
                padding: 0;
            }
            li {
                margin-bottom: 15px;
                padding: 10px;
                border-radius: 5px;
            }
            li.success {
                background-color: #d4edda;
                border-left: 5px solid #28a745;
            }
            li.error {
                background-color: #f8d7da;
                border-left: 5px solid #dc3545;
            }
            .summary {
                background-color: #e9ecef;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background-color: #1e3c72;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 15px;
                transition: background-color 0.3s;
            }
            .btn:hover {
                background-color: #2a5298;
            }
        </style>
    </head>
    <body>
        <h1>Thêm Bài Tập Web Programming</h1>";
    
    // Load SQL file
    $sqlFile = 'database/add_web_assignments.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($statement) {
            return !empty($statement);
        }
    );
    
    // Execute statements
    echo "<ul>";
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if ($conn->query($statement . ';')) {
            // Get title of assignment from statement
            preg_match("/\'([^\']+)\'/", $statement, $matches);
            $title = isset($matches[1]) ? $matches[1] : "Unknown";
            
            echo "<li class='success'>✓ Đã thêm bài tập: <strong>" . htmlspecialchars($title) . "</strong></li>";
            $successCount++;
        } else {
            echo "<li class='error'>✗ Lỗi khi thêm bài tập: " . $conn->error . "</li>";
            $errorCount++;
        }
    }
    
    echo "</ul>";
    
    // Count assignments
    $result = $conn->query("SELECT COUNT(*) as total FROM assignments WHERE course_id = 1");
    $count = $result->fetch_assoc()['total'];
    
    echo "<div class='summary'>";
    echo "<p>Đã thêm thành công <strong>$successCount</strong> bài tập mới.</p>";
    echo "<p>Tổng số bài tập Web Programming trong hệ thống: <strong>$count</strong></p>";
    echo "</div>";
    
    echo "<div>";
    echo "<a href='app/views/product/assignments.php' class='btn'>Xem Danh Sách Bài Tập</a>";
    echo "</div>";
    
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; font-family: Arial; background: #f8d7da; border-radius: 5px; margin: 20px;'>";
    echo "<h2>Lỗi</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Vui lòng kiểm tra kết nối cơ sở dữ liệu và đảm bảo database đã được tạo.</p>";
    echo "<p><a href='setup_db.php'>Chạy Database Setup</a></p>";
    echo "</div>";
}
?> 