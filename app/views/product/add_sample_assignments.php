<?php
// Script to add sample assignments to the database
define('BASE_URL', '/WebCourses/');

// Database connection details
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
         </div>');
}

// Count existing assignments
$count_query = "SELECT COUNT(*) as count FROM assignments";
$count_result = $conn->query($count_query);
$existing_count = $count_result->fetch_assoc()['count'];

// Get available courses
$courses_query = "SELECT id, title FROM courses";
$courses_result = $conn->query($courses_query);

if ($courses_result->num_rows == 0) {
    die('<div style="color:red; padding:20px; font-family:Arial; background:#f8d7da; border-radius:5px; margin:20px;">
         <h2>Không tìm thấy khóa học</h2>
         <p>Vui lòng thêm khóa học vào hệ thống trước.</p>
         </div>');
}

// Collect course IDs
$course_ids = array();
$course_titles = array();
while ($course = $courses_result->fetch_assoc()) {
    $course_ids[] = $course['id'];
    $course_titles[$course['id']] = $course['title'];
}

// Sample assignments data
$assignments = [
    // Web Development Assignments
    [
        'title' => 'Xây dựng Landing Page với HTML và CSS',
        'description' => "Thiết kế và triển khai một trang landing page đẹp mắt sử dụng HTML và CSS thuần.
        
Yêu cầu:
- Trang phải responsive và hoạt động tốt trên cả desktop và mobile
- Sử dụng Flexbox hoặc Grid để bố cục
- Có ít nhất một form liên hệ với validation
- Tối ưu hóa hình ảnh và hiệu suất trang",
        'max_points' => 100,
        'days_ahead' => 14
    ],
    [
        'title' => 'Ứng dụng Todo List với JavaScript',
        'description' => "Phát triển một ứng dụng Todo List sử dụng JavaScript thuần (không framework).
        
Yêu cầu:
- Cho phép thêm, sửa, xóa và đánh dấu hoàn thành công việc
- Lưu dữ liệu vào localStorage để duy trì sau khi refresh trang
- Bổ sung tính năng lọc (tất cả, hoàn thành, chưa hoàn thành)
- Giao diện người dùng trực quan và dễ sử dụng",
        'max_points' => 85,
        'days_ahead' => 10
    ],
    [
        'title' => 'Thiết kế Responsive với Bootstrap',
        'description' => "Sử dụng Bootstrap framework để xây dựng một trang web responsive có nhiều thành phần.
        
Yêu cầu:
- Sử dụng hệ thống grid của Bootstrap
- Implement các component: navbar, card, modal, carousel
- Tùy chỉnh theme Bootstrap mặc định
- Đảm bảo tương thích trên nhiều kích thước màn hình",
        'max_points' => 75,
        'days_ahead' => 7
    ],
    [
        'title' => 'REST API với Node.js và Express',
        'description' => "Xây dựng RESTful API sử dụng Node.js và Express framework.
        
Yêu cầu:
- Thiết kế API cho một ứng dụng quản lý sản phẩm
- Implement các endpoint CRUD (Create, Read, Update, Delete)
- Sử dụng middleware cho authentication
- Implement validation và error handling
- Viết documentation cho API",
        'max_points' => 95,
        'days_ahead' => 21
    ],
    
    // Database Assignments
    [
        'title' => 'Thiết kế Cơ sở dữ liệu cho Hệ thống Quản lý Thư viện',
        'description' => "Thiết kế và triển khai một cơ sở dữ liệu cho hệ thống quản lý thư viện.
        
Yêu cầu:
- Tạo ER Diagram cho hệ thống
- Viết SQL script để tạo các bảng với ràng buộc và khóa ngoại
- Viết các câu query cho các tình huống thông thường của hệ thống
- Tối ưu hóa cấu trúc cơ sở dữ liệu và chỉ mục",
        'max_points' => 90,
        'days_ahead' => 15
    ],
    [
        'title' => 'Stored Procedures và Triggers trong MySQL',
        'description' => "Phát triển stored procedures và triggers để tự động hóa các quy trình trong hệ thống quản lý sinh viên.
        
Yêu cầu:
- Viết stored procedures cho các tác vụ phức tạp
- Tạo triggers để duy trì tính toàn vẹn dữ liệu
- Implement transaction handling
- Tối ưu hiệu suất của stored procedures",
        'max_points' => 85,
        'days_ahead' => 12
    ],
    
    // Mobile Development Assignments
    [
        'title' => 'Ứng dụng Weather App với React Native',
        'description' => "Phát triển ứng dụng thời tiết di động sử dụng React Native.
        
Yêu cầu:
- Tích hợp với Weather API
- Hiển thị thời tiết hiện tại và dự báo cho 5 ngày
- Cho phép tìm kiếm theo thành phố
- Thiết kế UI hấp dẫn với animations",
        'max_points' => 90,
        'days_ahead' => 18
    ],
    [
        'title' => 'Ứng dụng Ghi chú với Flutter',
        'description' => "Phát triển ứng dụng ghi chú đa nền tảng sử dụng Flutter.
        
Yêu cầu:
- Tạo, chỉnh sửa, xóa ghi chú với rich text
- Lưu trữ dữ liệu với SQLite hoặc Firebase
- Implement tính năng chia sẻ ghi chú
- Thiết kế UI theo Material Design",
        'max_points' => 85,
        'days_ahead' => 15
    ],
    
    // UI/UX Design Assignments
    [
        'title' => 'Thiết kế UI/UX cho Ứng dụng Đặt đồ ăn',
        'description' => "Thiết kế giao diện người dùng cho ứng dụng đặt đồ ăn trực tuyến.
        
Yêu cầu:
- Tạo wireframes và mockups cho toàn bộ flow
- Thiết kế hệ thống màu sắc và typography
- Tạo prototype tương tác
- Trình bày quyết định thiết kế và research người dùng",
        'max_points' => 95,
        'days_ahead' => 20
    ],
    [
        'title' => 'Redesign Website cho Doanh nghiệp vừa và nhỏ',
        'description' => "Thực hiện redesign cho website của một doanh nghiệp vừa và nhỏ để cải thiện trải nghiệm người dùng.
        
Yêu cầu:
- Phân tích website hiện tại và xác định các vấn đề
- Tạo design mới với cải tiến UX
- Tối ưu hóa cho conversion
- Cung cấp style guide cho brand",
        'max_points' => 85,
        'days_ahead' => 16
    ],
    
    // AI and Machine Learning Assignments
    [
        'title' => 'Phân tích Dữ liệu với Python và Pandas',
        'description' => "Sử dụng Python và thư viện Pandas để phân tích bộ dữ liệu và rút ra các insights.
        
Yêu cầu:
- Làm sạch và chuẩn bị dữ liệu
- Thực hiện EDA (Exploratory Data Analysis)
- Tạo visualizations có ý nghĩa
- Rút ra insights từ dữ liệu",
        'max_points' => 80,
        'days_ahead' => 14
    ],
    [
        'title' => 'Xây dựng Mô hình Machine Learning cho Dự đoán',
        'description' => "Phát triển mô hình machine learning để dự đoán giá nhà dựa trên các đặc điểm.
        
Yêu cầu:
- Tiền xử lý dữ liệu và feature engineering
- Thử nghiệm và đánh giá các mô hình khác nhau
- Tinh chỉnh hyperparameters
- Đánh giá hiệu suất và giải thích mô hình",
        'max_points' => 95,
        'days_ahead' => 25
    ],
    
    // DevOps Assignments
    [
        'title' => 'CI/CD Pipeline với GitHub Actions',
        'description' => "Thiết lập CI/CD pipeline sử dụng GitHub Actions cho một ứng dụng web.
        
Yêu cầu:
- Tự động hóa testing, building và deployment
- Thiết lập multiple environments (dev, staging, production)
- Implement security scanning
- Tạo documentation cho pipeline",
        'max_points' => 90,
        'days_ahead' => 20
    ],
    [
        'title' => 'Containerization với Docker',
        'description' => "Containerize một ứng dụng web sử dụng Docker và docker-compose.
        
Yêu cầu:
- Tạo Dockerfile tối ưu cho ứng dụng
- Thiết lập multi-container setup với docker-compose
- Implement best practices cho security
- Tối ưu hóa kích thước image và build time",
        'max_points' => 85,
        'days_ahead' => 17
    ],
    
    // Language Assignments (PHP & Laravel)
    [
        'title' => 'Xây dựng Blog với PHP',
        'description' => "Phát triển một hệ thống blog đơn giản sử dụng PHP thuần.
        
Yêu cầu:
- Tạo, chỉnh sửa, và xóa bài viết
- Hệ thống bình luận
- Quản lý người dùng và phân quyền
- Tối ưu hóa hiệu suất và bảo mật",
        'max_points' => 85,
        'days_ahead' => 10
    ],
    [
        'title' => 'Ứng dụng Web với Laravel',
        'description' => "Xây dựng ứng dụng web hoàn chỉnh sử dụng Laravel framework.
        
Yêu cầu:
- Thiết kế database migrations và seeds
- Implement authentication và authorization với Laravel Sanctum
- Xây dựng REST API
- Triển khai blade templates và components",
        'max_points' => 90,
        'days_ahead' => 18
    ],
    
    // Frontend Framework Assignments
    [
        'title' => 'Single Page Application với React',
        'description' => "Phát triển một ứng dụng web single-page sử dụng React.
        
Yêu cầu:
- Sử dụng React Router cho routing
- State management với Redux hoặc Context API
- Fetch data từ API
- Implement lazy loading và optimization",
        'max_points' => 95,
        'days_ahead' => 20
    ],
    [
        'title' => 'Xây dựng Dashboard với Vue.js',
        'description' => "Tạo một dashboard hiển thị dữ liệu với nhiều biểu đồ sử dụng Vue.js.
        
Yêu cầu:
- Sử dụng Vue Router và Vuex
- Tích hợp thư viện biểu đồ (Chart.js hoặc D3.js)
- Thiết kế responsive và thân thiện người dùng
- Kết nối với backend API",
        'max_points' => 90,
        'days_ahead' => 15
    ],
    
    // Project Assignments
    [
        'title' => 'E-commerce Platform',
        'description' => "Phát triển một nền tảng e-commerce hoàn chỉnh với đầy đủ tính năng.
        
Yêu cầu:
- Front-end sử dụng React hoặc Vue.js
- Back-end với Node.js hoặc Laravel
- Database thiết kế và triển khai
- Tích hợp payment gateway
- Authentication và authorization
- Responsive design cho tất cả thiết bị",
        'max_points' => 100,
        'days_ahead' => 30
    ],
    [
        'title' => 'Social Media Platform',
        'description' => "Thiết kế và phát triển một nền tảng mạng xã hội cộng đồng.
        
Yêu cầu:
- User profiles và authentication
- News feed với real-time updates
- Tính năng messaging và notifications
- Media sharing
- Responsive và mobile-friendly design
- RESTful API backend",
        'max_points' => 100,
        'days_ahead' => 35
    ],
    
    // Problem-Solving Assignments
    [
        'title' => 'Giải quyết Thuật toán và Cấu trúc Dữ liệu',
        'description' => "Giải quyết một loạt bài tập về thuật toán và cấu trúc dữ liệu.
        
Yêu cầu:
- Implement các cấu trúc dữ liệu cơ bản (Linked List, Stack, Queue, etc.)
- Giải quyết các bài toán sắp xếp và tìm kiếm
- Tối ưu hóa thuật toán về thời gian và không gian
- Phân tích độ phức tạp",
        'max_points' => 90,
        'days_ahead' => 14
    ],
    [
        'title' => 'Tối ưu hóa Hiệu suất Ứng dụng Web',
        'description' => "Phân tích và tối ưu hóa hiệu suất của một ứng dụng web hiện có.
        
Yêu cầu:
- Sử dụng công cụ đo lường hiệu suất (Lighthouse, WebPageTest)
- Tối ưu hóa loading time
- Implement lazy loading và code splitting
- Tối ưu hóa assets (hình ảnh, CSS, JavaScript)
- Cải thiện Core Web Vitals",
        'max_points' => 85,
        'days_ahead' => 10
    ]
];

// Number of assignments to add per course
$num_added = 0;
$max_to_add = 50; // Limit the total number of assignments to add

// Start HTML output
echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm Bài Tập Mẫu - Học Tập Trực Tuyến</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Nunito", "Quicksand", sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
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
            font-family: "Montserrat", sans-serif;
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
        nav ul li a:hover {
            color: #FFC107;
            background: rgba(255, 255, 255, 0.1);
        }
        h1 {
            color: #1e3c72;
            margin-bottom: 30px;
            font-family: "Montserrat", sans-serif;
            font-size: 2.2rem;
            text-align: center;
            border-bottom: 3px solid #FFC107;
            padding-bottom: 15px;
        }
        .stats {
            background: #f0f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .stat-item {
            padding: 0 15px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3c72;
            display: block;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        .assignment-list {
            margin-top: 30px;
        }
        .assignment-item {
            background: #f8f9fa;
            border-left: 4px solid #1e3c72;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 4px 4px 0;
            transition: all 0.3s;
        }
        .assignment-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .assignment-title {
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 5px;
        }
        .assignment-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
            margin-top: 8px;
        }
        .assignment-meta span {
            display: inline-flex;
            align-items: center;
        }
        .assignment-meta i {
            margin-right: 5px;
        }
        .course-badge {
            display: inline-block;
            padding: 3px 8px;
            background: #e3f2fd;
            color: #0d47a1;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1e3c72;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #2a5298, #1e3c72);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #FFC107;
            color: #333;
        }
        .btn-secondary:hover {
            background: #ffca28;
            transform: translateY(-2px);
        }
        .message {
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            padding-left: 55px;
        }
        .message:before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
        }
        .message-success {
            background: #28a745;
        }
        .message-success:before {
            content: "\\f00c";
        }
        .message-warning {
            background: #ffc107;
            color: #333;
        }
        .message-warning:before {
            content: "\\f071";
        }
        .message p {
            margin: 0;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading p {
            margin-top: 10px;
            color: #666;
        }
        .spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1e3c72;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">Học Tập</div>
            <nav>
                <ul>
                    <li><a href="home.php">Trang Chủ</a></li>
                    <li><a href="student_dashboard.php">Dashboard</a></li>
                    <li><a href="assignments.php">Bài Tập</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>Thêm Bài Tập Mẫu</h1>';

// Display stats
echo '<div class="stats">
        <div class="stat-item">
            <span class="stat-number">' . count($course_ids) . '</span>
            <span class="stat-label">Khóa học</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">' . $existing_count . '</span>
            <span class="stat-label">Bài tập hiện có</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">' . count($assignments) . '</span>
            <span class="stat-label">Mẫu bài tập</span>
        </div>
      </div>';

// Insert assignments into the database
$added_assignments = array();

// Prepare statement
$stmt = $conn->prepare("INSERT INTO assignments (title, description, course_id, max_points, due_date) VALUES (?, ?, ?, ?, ?)");

// Shuffle assignments to randomize them
shuffle($assignments);

foreach ($assignments as $assignment) {
    if ($num_added >= $max_to_add) {
        break;
    }
    
    // Distribute assignments across courses
    foreach ($course_ids as $course_id) {
        if ($num_added >= $max_to_add) {
            break;
        }
        
        // Calculate due date with slight variation
        $variation = rand(-2, 3);
        $due_date = date('Y-m-d H:i:s', strtotime('+' . ($assignment['days_ahead'] + $variation) . ' days'));
        
        // Bind parameters and execute
        $stmt->bind_param("ssids", 
            $assignment['title'], 
            $assignment['description'], 
            $course_id, 
            $assignment['max_points'], 
            $due_date
        );
        
        if ($stmt->execute()) {
            $added_assignments[] = [
                'title' => $assignment['title'],
                'course_id' => $course_id,
                'course_title' => $course_titles[$course_id],
                'due_date' => $due_date,
                'max_points' => $assignment['max_points']
            ];
            $num_added++;
        }
        
        // Not all assignments for all courses - add some randomness
        if (rand(1, 4) > 1) {
            break;
        }
    }
}

if ($num_added > 0) {
    echo '<div class="message message-success">
            <p>Đã thêm thành công ' . $num_added . ' bài tập mới vào hệ thống!</p>
          </div>';
    
    echo '<div class="assignment-list">';
    
    foreach (array_slice($added_assignments, 0, 10) as $item) {
        echo '<div class="assignment-item">
                <div class="assignment-title">' . htmlspecialchars($item['title']) . '</div>
                <span class="course-badge">' . htmlspecialchars($item['course_title']) . '</span>
                <div class="assignment-meta">
                    <span><i class="fas fa-calendar-alt"></i> Hạn nộp: ' . date('d/m/Y', strtotime($item['due_date'])) . '</span>
                    <span><i class="fas fa-star"></i> Điểm tối đa: ' . $item['max_points'] . '</span>
                </div>
              </div>';
    }
    
    if (count($added_assignments) > 10) {
        echo '<p style="text-align:center; color:#666; font-style:italic; margin:15px 0;">... và ' . (count($added_assignments) - 10) . ' bài tập khác</p>';
    }
    
    echo '</div>';
} else {
    echo '<div class="message message-warning">
            <p>Không thêm được bài tập nào. Vui lòng kiểm tra lại hệ thống.</p>
          </div>';
}

echo '<div class="actions">
        <a href="' . BASE_URL . 'app/views/product/assignments.php" class="btn btn-primary">Xem danh sách bài tập</a>
        <a href="' . BASE_URL . 'app/views/product/student_dashboard.php" class="btn btn-secondary">Quay lại Dashboard</a>
      </div>';

echo '</div>'; // Close container

// Close statement and connection
$stmt->close();
$conn->close();
?>
</body>
</html> 