<?php
// Direct script to add assignments - XAMPP version
// Avoid relative includes by directly setting up database connection

// Database connection
$server   = 'localhost';
$user     = 'root';
$pass     = '';
$database = 'online_courses';

$conn = new mysqli($server, $user, $pass, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Function to add an assignment if it doesn't exist
function addAssignment($conn, $title, $description, $course_id, $due_date, $difficulty = 'Cơ bản', $max_points = 10) {
    // Check if assignment with this title already exists
    $stmt = $conn->prepare("SELECT assignment_id FROM Assignments WHERE title = ? AND course_id = ?");
    $stmt->bind_param("si", $title, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ["status" => false, "message" => "Assignment '$title' already exists"];
    }
    
    // Get creator ID for assignment
    $creatorQuery = $conn->query("SELECT created_by FROM Courses WHERE course_id = $course_id LIMIT 1");
    $created_by = 1; // Default to admin user ID
    if ($creatorQuery && $creatorQuery->num_rows > 0) {
        $created_by = $creatorQuery->fetch_assoc()['created_by'];
    }
    
    // Add the assignment
    $stmt = $conn->prepare("
        INSERT INTO Assignments 
        (title, description, course_id, due_date, created_by, difficulty, max_points, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("ssisssi", $title, $description, $course_id, $due_date, $created_by, $difficulty, $max_points);
    
    if ($stmt->execute()) {
        return ["status" => true, "message" => "Assignment '$title' added successfully"];
    } else {
        return ["status" => false, "message" => "Error adding '$title': " . $conn->error];
    }
}

// Ensure necessary columns exist
$tableCheck = $conn->query("SHOW COLUMNS FROM Assignments LIKE 'difficulty'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $conn->query("ALTER TABLE Assignments ADD COLUMN difficulty VARCHAR(20) DEFAULT 'Cơ bản' AFTER due_date");
    echo "Added difficulty column<br>";
}

$tableCheck = $conn->query("SHOW COLUMNS FROM Assignments LIKE 'max_points'");
if ($tableCheck && $tableCheck->num_rows == 0) {
    $conn->query("ALTER TABLE Assignments ADD COLUMN max_points INT DEFAULT 10 AFTER difficulty");
    echo "Added max_points column<br>";
}

// Get all courses
$coursesQuery = $conn->query("SELECT * FROM Courses");
if ($coursesQuery->num_rows == 0) {
    die("No courses found. Please add courses first.");
}

$courses = [];
while ($row = $coursesQuery->fetch_assoc()) {
    $courses[] = $row;
}

// Set due dates
$now = new DateTime();
$oneWeekLater = (clone $now)->modify('+1 week')->format('Y-m-d H:i:s');
$twoWeeksLater = (clone $now)->modify('+2 weeks')->format('Y-m-d H:i:s');
$threeWeeksLater = (clone $now)->modify('+3 weeks')->format('Y-m-d H:i:s');

// List of assignments to add
$assignments = [];

// For each course, add some assignments
foreach ($courses as $index => $course) {
    $course_id = $course['course_id'];
    $assignments[] = [
        'title' => 'Bài tập cơ bản - ' . $course['title'],
        'description' => 'Đây là bài tập cơ bản cho khóa học ' . $course['title'] . '. Hãy hoàn thành các yêu cầu sau: 
1. Tạo một tài liệu tóm tắt nội dung chính của khóa học.
2. Giải quyết các bài tập được giao.
3. Nộp bài đúng hạn.',
        'course_id' => $course_id,
        'due_date' => $oneWeekLater,
        'difficulty' => 'Cơ bản',
        'max_points' => 10
    ];
    
    $assignments[] = [
        'title' => 'Bài tập nâng cao - ' . $course['title'],
        'description' => 'Đây là bài tập nâng cao cho khóa học ' . $course['title'] . '. Hãy hoàn thành các yêu cầu sau:
1. Phân tích chuyên sâu nội dung khóa học.
2. Áp dụng kiến thức vào một dự án thực tế.
3. Viết báo cáo chi tiết về kết quả thực hiện.',
        'course_id' => $course_id,
        'due_date' => $twoWeeksLater,
        'difficulty' => 'Nâng cao',
        'max_points' => 20
    ];
    
    $assignments[] = [
        'title' => 'Bài tập tổng hợp - ' . $course['title'],
        'description' => 'Đây là bài tập tổng hợp cho khóa học ' . $course['title'] . '. Hãy hoàn thành các yêu cầu sau:
1. Tổng hợp toàn bộ kiến thức đã học.
2. Thực hiện dự án cuối khóa.
3. Thuyết trình kết quả và kiến thức đã học được.',
        'course_id' => $course_id,
        'due_date' => $threeWeeksLater,
        'difficulty' => 'Trung bình',
        'max_points' => 15
    ];
}

// Add more specific assignments for different subject areas
// Web development assignments
$webDevCourses = [];
$webQuery = $conn->query("SELECT course_id FROM Courses WHERE title LIKE '%Web%' OR title LIKE '%HTML%' OR title LIKE '%CSS%' OR title LIKE '%JavaScript%' LIMIT 1");
if ($webQuery && $webQuery->num_rows > 0) {
    $webDevCourse = $webQuery->fetch_assoc();
    $webDevCourseId = $webDevCourse['course_id'];
    
    $assignments[] = [
        'title' => 'Tạo trang web cá nhân',
        'description' => 'Thiết kế và phát triển một trang web cá nhân đơn giản với HTML và CSS. Trang web nên có các phần sau: Header, About Me, Skills, Portfolio, Contact Form, và Footer.',
        'course_id' => $webDevCourseId,
        'due_date' => $oneWeekLater,
        'difficulty' => 'Cơ bản',
        'max_points' => 10
    ];
    
    $assignments[] = [
        'title' => 'Tạo ứng dụng Todo List',
        'description' => 'Xây dựng ứng dụng Todo List sử dụng HTML, CSS và JavaScript. Ứng dụng cần có các chức năng: thêm, sửa, xóa và đánh dấu công việc hoàn thành.',
        'course_id' => $webDevCourseId,
        'due_date' => $twoWeeksLater,
        'difficulty' => 'Trung bình',
        'max_points' => 15
    ];
}

// Programming assignments
$progCourses = [];
$progQuery = $conn->query("SELECT course_id FROM Courses WHERE title LIKE '%Programming%' OR title LIKE '%Java%' OR title LIKE '%Python%' OR title LIKE '%C#%' LIMIT 1");
if ($progQuery && $progQuery->num_rows > 0) {
    $progCourse = $progQuery->fetch_assoc();
    $progCourseId = $progCourse['course_id'];
    
    $assignments[] = [
        'title' => 'Giải thuật sắp xếp',
        'description' => 'Cài đặt các thuật toán sắp xếp (bubble sort, insertion sort, merge sort) và so sánh hiệu suất của chúng với các kích thước mảng khác nhau.',
        'course_id' => $progCourseId,
        'due_date' => $oneWeekLater,
        'difficulty' => 'Trung bình',
        'max_points' => 15
    ];
    
    $assignments[] = [
        'title' => 'Quản lý sinh viên',
        'description' => 'Xây dựng một ứng dụng quản lý sinh viên có các chức năng: thêm, sửa, xóa, tìm kiếm và sắp xếp sinh viên theo các tiêu chí khác nhau.',
        'course_id' => $progCourseId,
        'due_date' => $twoWeeksLater,
        'difficulty' => 'Nâng cao',
        'max_points' => 20
    ];
}

// Database assignments
$dbCourses = [];
$dbQuery = $conn->query("SELECT course_id FROM Courses WHERE title LIKE '%Database%' OR title LIKE '%SQL%' OR title LIKE '%MySQL%' LIMIT 1");
if ($dbQuery && $dbQuery->num_rows > 0) {
    $dbCourse = $dbQuery->fetch_assoc();
    $dbCourseId = $dbCourse['course_id'];
    
    $assignments[] = [
        'title' => 'Thiết kế cơ sở dữ liệu',
        'description' => 'Thiết kế một cơ sở dữ liệu cho hệ thống quản lý thư viện. Cần có các bảng: Sách, Tác giả, Thể loại, Độc giả, Mượn sách và các mối quan hệ giữa chúng.',
        'course_id' => $dbCourseId,
        'due_date' => $oneWeekLater,
        'difficulty' => 'Trung bình',
        'max_points' => 15
    ];
    
    $assignments[] = [
        'title' => 'Truy vấn nâng cao',
        'description' => 'Viết các truy vấn SQL nâng cao: joins, subqueries, aggregation, window functions và stored procedures cho cơ sở dữ liệu đã cho.',
        'course_id' => $dbCourseId,
        'due_date' => $twoWeeksLater,
        'difficulty' => 'Nâng cao',
        'max_points' => 20
    ];
}

// Track success and error counts
$success_count = 0;
$error_count = 0;
$error_messages = [];

// Add all assignments
foreach ($assignments as $assignment) {
    $result = addAssignment(
        $conn,
        $assignment['title'],
        $assignment['description'],
        $assignment['course_id'],
        $assignment['due_date'],
        $assignment['difficulty'],
        $assignment['max_points']
    );
    
    if ($result['status']) {
        $success_count++;
    } else {
        $error_count++;
        $error_messages[] = $result['message'];
    }
}

// Display results
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm bài tập - Kết quả</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .result-box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .error-list {
            background: #ffeeee;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #1a73e8;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="result-box">
        <h1>Kết quả thêm bài tập</h1>
        <p class="success">Số bài tập đã thêm thành công: <?php echo $success_count; ?></p>
        <p class="error">Số bài tập lỗi: <?php echo $error_count; ?></p>
        
        <?php if (!empty($error_messages)): ?>
            <div class="error-list">
                <h3>Chi tiết lỗi:</h3>
                <ul>
                    <?php foreach ($error_messages as $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div>
        <a href="/WebCourses/app/views/product/assignments.php" class="btn">Đến trang bài tập</a>
        <a href="/WebCourses/app/views/product/student_dashboard.php" class="btn">Về Dashboard</a>
    </div>
</body>
</html> 