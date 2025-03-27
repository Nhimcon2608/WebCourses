<?php
// get_lessons.php - Endpoint API để lấy danh sách bài học cho khóa học
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Kiểm tra tham số course_id
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing or invalid course_id']);
    exit();
}

$course_id = intval($_GET['course_id']);

// Xác minh quyền truy cập vào khóa học
$accessCheck = false;

if ($role == 'instructor') {
    // Giảng viên chỉ có thể truy cập khóa học của họ
    $checkStmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND instructor_id = ?");
    $checkStmt->bind_param("ii", $course_id, $user_id);
    $checkStmt->execute();
    $accessCheck = $checkStmt->get_result()->num_rows > 0;
} elseif ($role == 'student') {
    // Học viên chỉ có thể truy cập khóa học đã đăng ký
    $checkStmt = $conn->prepare("
        SELECT c.course_id 
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id
        WHERE c.course_id = ? AND e.user_id = ? AND e.status = 'active'
    ");
    $checkStmt->bind_param("ii", $course_id, $user_id);
    $checkStmt->execute();
    $accessCheck = $checkStmt->get_result()->num_rows > 0;
} elseif ($role == 'admin') {
    // Admin có thể truy cập tất cả khóa học
    $accessCheck = true;
}

if (!$accessCheck) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied to this course']);
    exit();
}

// Lấy danh sách bài học
$stmt = $conn->prepare("
    SELECT lesson_id, title 
    FROM lessons 
    WHERE course_id = ? 
    ORDER BY lesson_order ASC, lesson_id ASC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

$lessons = [];
while ($row = $result->fetch_assoc()) {
    $lessons[] = [
        'lesson_id' => $row['lesson_id'],
        'title' => $row['title']
    ];
}

// Trả về kết quả dạng JSON
header('Content-Type: application/json');
echo json_encode($lessons); 