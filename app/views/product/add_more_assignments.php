<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Bạn cần đăng nhập với quyền quản trị để thêm bài tập mẫu.";
    header("Location: " . BASE_URL . "app/views/product/login.php");
    exit();
}

// Check if Assignments table has the necessary columns
function ensureColumnsExist($conn) {
    $columnsToCheck = ['difficulty', 'max_points'];
    $columnResults = [];
    
    foreach ($columnsToCheck as $column) {
        $checkColumn = $conn->query("SHOW COLUMNS FROM Assignments LIKE '$column'");
        
        if ($checkColumn && $checkColumn->num_rows == 0) {
            // Column doesn't exist, add it
            if ($column === 'difficulty') {
                $conn->query("ALTER TABLE Assignments ADD COLUMN difficulty VARCHAR(20) DEFAULT 'Cơ bản' AFTER due_date");
                $columnResults[] = "Đã thêm cột 'difficulty' vào bảng Assignments";
            } elseif ($column === 'max_points') {
                $conn->query("ALTER TABLE Assignments ADD COLUMN max_points INT DEFAULT 10 AFTER difficulty");
                $columnResults[] = "Đã thêm cột 'max_points' vào bảng Assignments";
            }
        }
    }
    
    return $columnResults;
}

// Function to add an assignment if it doesn't already exist
function addAssignment($conn, $title, $description, $course_id, $lesson_id, $due_date, $difficulty, $max_points) {
    // Check if assignment already exists
    $stmt = $conn->prepare("SELECT assignment_id FROM Assignments WHERE title = ? AND course_id = ?");
    $stmt->bind_param("si", $title, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Assignment doesn't exist, add it
        $insertStmt = $conn->prepare("
            INSERT INTO Assignments (title, description, course_id, lesson_id, due_date, difficulty, max_points, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insertStmt->bind_param("ssiissi", $title, $description, $course_id, $lesson_id, $due_date, $difficulty, $max_points);
        $success = $insertStmt->execute();
        $insertStmt->close();
        
        return $success;
    }
    
    return false; // Assignment already exists
}

// Initialize messages array
$messages = [];

// Ensure columns exist
$columnResults = ensureColumnsExist($conn);
if (!empty($columnResults)) {
    $messages = array_merge($messages, $columnResults);
}

// Check for existing courses and lessons
$courseQuery = $conn->query("SELECT course_id, title FROM Courses");
$courses = [];
while ($row = $courseQuery->fetch_assoc()) {
    $courses[$row['title']] = $row['course_id'];
}

$lessonQuery = $conn->query("SELECT lesson_id, title, course_id FROM Lessons");
$lessons = [];
while ($row = $lessonQuery->fetch_assoc()) {
    if (!isset($lessons[$row['course_id']])) {
        $lessons[$row['course_id']] = [];
    }
    $lessons[$row['course_id']][$row['title']] = $row['lesson_id'];
}

// If we don't have any courses, we can't add assignments
if (empty($courses)) {
    $messages[] = "Không tìm thấy khóa học nào. Vui lòng thêm khóa học trước khi thêm bài tập.";
} else {
    // Define dates
    $now = new DateTime();
    $oneWeekLater = (clone $now)->modify('+1 week')->format('Y-m-d H:i:s');
    $twoWeeksLater = (clone $now)->modify('+2 weeks')->format('Y-m-d H:i:s');
    $threeWeeksLater = (clone $now)->modify('+3 weeks')->format('Y-m-d H:i:s');
    $fourWeeksLater = (clone $now)->modify('+4 weeks')->format('Y-m-d H:i:s');
    
    // Array of sample assignments
    $assignments = [
        // HTML & CSS Assignments
        [
            'title' => 'Xây dựng trang web cá nhân đơn giản',
            'description' => "Hãy tạo một trang web cá nhân đơn giản với các thành phần sau:
1. Header với tên và ảnh đại diện
2. Phần giới thiệu bản thân
3. Danh sách kỹ năng
4. Thông tin liên hệ
5. Footer

Yêu cầu:
- Sử dụng HTML5 semantic tags
- Áp dụng CSS để tạo bố cục và định dạng
- Responsive trên cả desktop và mobile",
            'course' => 'HTML & CSS',
            'lesson' => 'HTML Cơ bản',
            'due_date' => $oneWeekLater,
            'difficulty' => 'Cơ bản',
            'max_points' => 20
        ],
        [
            'title' => 'Thiết kế Landing Page cho sản phẩm',
            'description' => "Thiết kế một landing page hoàn chỉnh cho một sản phẩm tưởng tượng với các phần sau:
1. Header với logo và navigation
2. Hero section với CTA button
3. Phần giới thiệu tính năng sản phẩm
4. Testimonials
5. Pricing section
6. FAQ section
7. Footer với thông tin liên hệ và form đăng ký

Yêu cầu:
- Sử dụng CSS Flexbox hoặc Grid để layout
- Animation và hover effects
- Thiết kế responsive
- Tối ưu hóa cho SEO",
            'course' => 'HTML & CSS',
            'lesson' => 'CSS Nâng cao',
            'due_date' => $twoWeeksLater,
            'difficulty' => 'Trung bình',
            'max_points' => 30
        ],
        [
            'title' => 'Tái tạo giao diện của Netflix',
            'description' => "Hãy tái tạo lại trang chủ của Netflix với các thành phần chính sau:
1. Navigation bar với logo, menu và nút đăng nhập
2. Hero banner với trailer nổi bật
3. Rows của các bộ phim theo danh mục
4. Footer đầy đủ

Yêu cầu:
- Sử dụng CSS preprocessor (SASS/SCSS)
- Thiết kế responsive cho mobile, tablet và desktop
- Hover effects cho các phần tử tương tác
- Tối thiểu 1 animation khi load trang
- Đảm bảo accessibility",
            'course' => 'HTML & CSS',
            'lesson' => 'CSS Nâng cao',
            'due_date' => $threeWeeksLater,
            'difficulty' => 'Nâng cao',
            'max_points' => 40
        ],
        
        // JavaScript Assignments
        [
            'title' => 'Xây dựng ứng dụng Todo List đơn giản',
            'description' => "Tạo một ứng dụng Todo List cơ bản với JavaScript với các chức năng:
1. Thêm công việc mới
2. Đánh dấu công việc đã hoàn thành
3. Xóa công việc
4. Lưu danh sách công việc vào localStorage

Yêu cầu:
- Sử dụng vanilla JavaScript (không dùng thư viện)
- CSS cơ bản để styling
- Responsive design
- Validation cho form nhập liệu",
            'course' => 'JavaScript',
            'lesson' => 'JavaScript Cơ bản',
            'due_date' => $oneWeekLater,
            'difficulty' => 'Cơ bản',
            'max_points' => 25
        ],
        [
            'title' => 'Xây dựng ứng dụng Weather App',
            'description' => "Tạo một ứng dụng dự báo thời tiết sử dụng API với các chức năng:
1. Tìm kiếm thành phố
2. Hiển thị thông tin thời tiết hiện tại (nhiệt độ, độ ẩm, tốc độ gió...)
3. Dự báo 5 ngày tiếp theo
4. Thay đổi giao diện theo điều kiện thời tiết

Yêu cầu:
- Sử dụng fetch API để lấy dữ liệu từ OpenWeatherMap API
- Xử lý lỗi khi API không phản hồi
- Thiết kế responsive
- Loading states và error handling",
            'course' => 'JavaScript',
            'lesson' => 'JavaScript Nâng cao',
            'due_date' => $twoWeeksLater,
            'difficulty' => 'Trung bình',
            'max_points' => 35
        ],
        [
            'title' => 'Xây dựng trò chơi Tetris',
            'description' => "Phát triển trò chơi Tetris hoàn chỉnh bằng JavaScript với các chức năng:
1. Điều khiển khối gạch (di chuyển, xoay, rơi nhanh)
2. Tính điểm khi hoàn thành dòng
3. Tăng tốc độ theo thời gian chơi
4. Hiển thị khối tiếp theo
5. Game over logic
6. Lưu điểm cao

Yêu cầu:
- Sử dụng HTML5 Canvas
- Xử lý sự kiện bàn phím
- Thiết kế responsive và chơi được trên mobile
- Game sound effects
- Animations",
            'course' => 'JavaScript',
            'lesson' => 'JavaScript Nâng cao',
            'due_date' => $fourWeeksLater,
            'difficulty' => 'Nâng cao',
            'max_points' => 50
        ],
        
        // PHP & MySQL Assignments
        [
            'title' => 'Xây dựng hệ thống đăng nhập/đăng ký',
            'description' => "Tạo một hệ thống đăng nhập/đăng ký với PHP và MySQL:
1. Form đăng ký với validation
2. Form đăng nhập
3. Trang profile cơ bản
4. Chức năng đăng xuất
5. Password reset

Yêu cầu:
- Sử dụng PDO hoặc MySQLi với prepared statements
- Password hashing
- Input validation và sanitization
- Session management
- CSRF protection",
            'course' => 'PHP & MySQL',
            'lesson' => 'PHP Cơ bản',
            'due_date' => $twoWeeksLater,
            'difficulty' => 'Trung bình',
            'max_points' => 30
        ],
        [
            'title' => 'Xây dựng REST API đơn giản',
            'description' => "Phát triển một REST API đơn giản với PHP:
1. CRUD operations cho một resource (ví dụ: products)
2. Authentication với JWT
3. Pagination và filtering
4. Rate limiting
5. API documentation

Yêu cầu:
- RESTful design principles
- JSON response format
- HTTP status codes phù hợp
- Error handling
- Unit tests cơ bản",
            'course' => 'PHP & MySQL',
            'lesson' => 'PHP Nâng cao',
            'due_date' => $threeWeeksLater,
            'difficulty' => 'Nâng cao',
            'max_points' => 45
        ],
        
        // C Programming Assignments
        [
            'title' => 'Viết chương trình quản lý sinh viên đơn giản',
            'description' => "Viết một chương trình C để quản lý thông tin sinh viên với các chức năng:
1. Thêm sinh viên mới (tên, ID, điểm)
2. Hiển thị danh sách sinh viên
3. Tìm kiếm sinh viên theo ID
4. Cập nhật thông tin sinh viên
5. Xóa sinh viên
6. Sắp xếp sinh viên theo điểm

Yêu cầu:
- Sử dụng struct để lưu thông tin sinh viên
- Menu-driven interface
- Input validation
- File I/O để lưu dữ liệu
- Memory management phù hợp",
            'course' => 'C Programming',
            'lesson' => 'C Cơ bản',
            'due_date' => $oneWeekLater,
            'difficulty' => 'Cơ bản',
            'max_points' => 20
        ],
        [
            'title' => 'Viết chương trình mô phỏng cấu trúc dữ liệu',
            'description' => "Viết chương trình mô phỏng các cấu trúc dữ liệu cơ bản:
1. Linked List với các operations: insert, delete, search, display
2. Stack với push, pop, peek
3. Queue với enqueue, dequeue, display
4. Binary Search Tree với insert, delete, search, traversal

Yêu cầu:
- Cài đặt từng cấu trúc dữ liệu với C structs và pointers
- Menu interface để test các operations
- Memory management phù hợp (không memory leaks)
- Xử lý edge cases
- Báo cáo phân tích độ phức tạp của từng operation",
            'course' => 'C Programming',
            'lesson' => 'C Nâng cao',
            'due_date' => $threeWeeksLater,
            'difficulty' => 'Nâng cao',
            'max_points' => 40
        ]
    ];
    
    // Add assignments to database
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($assignments as $assignment) {
        // Get course ID
        $course_id = isset($courses[$assignment['course']]) ? $courses[$assignment['course']] : null;
        
        // If course exists
        if ($course_id) {
            // Get lesson ID if provided
            $lesson_id = null;
            if (isset($assignment['lesson']) && isset($lessons[$course_id][$assignment['lesson']])) {
                $lesson_id = $lessons[$course_id][$assignment['lesson']];
            }
            
            // Add assignment
            $result = addAssignment(
                $conn,
                $assignment['title'],
                $assignment['description'],
                $course_id,
                $lesson_id,
                $assignment['due_date'],
                $assignment['difficulty'],
                $assignment['max_points']
            );
            
            if ($result) {
                $successCount++;
            } else {
                $errorCount++;
            }
        } else {
            $messages[] = "Không tìm thấy khóa học '{$assignment['course']}' cho bài tập '{$assignment['title']}'";
            $errorCount++;
        }
    }
    
    $messages[] = "Đã thêm thành công $successCount bài tập mới. $errorCount bài tập không được thêm (đã tồn tại hoặc lỗi).";
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm bài tập mẫu - WebCourses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        .message-container {
            margin: 20px 0;
        }
        .success {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .error {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f5c2c7;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Thêm bài tập mẫu</h2>
                    </div>
                    <div class="card-body">
                        <div class="message-container">
                            <?php foreach ($messages as $message): ?>
                                <div class="<?php echo strpos($message, 'thành công') !== false ? 'success' : 'error'; ?>">
                                    <?php echo $message; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>app/views/product/assignments.php" class="btn btn-secondary">Quay lại danh sách bài tập</a>
                            <a href="<?php echo BASE_URL; ?>app/views/admin/dashboard.php" class="btn btn-primary">Quay lại trang quản trị</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 