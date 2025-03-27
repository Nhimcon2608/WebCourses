<?php
define('BASE_URL', '/WebCourses/');
session_start();

// Direct database connection to avoid include path issues
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

// Connect to database
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in and is a student
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$isStudent = false;

if ($currentUserId > 0) {
    $userQuery = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $userQuery->bind_param("i", $currentUserId);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    
    if ($userResult->num_rows > 0) {
        $userData = $userResult->fetch_assoc();
        $isStudent = ($userData['role'] == 'student');
    }
}

// Function to enroll in a course if not already enrolled
function enrollInCourse($conn, $userId, $courseId) {
    // Check if already enrolled
    $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $userId, $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return true; // Already enrolled
    }
    
    // Enroll in the course
    $today = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, enrolled_date, status) VALUES (?, ?, ?, 'active')");
    $stmt->bind_param("iis", $userId, $courseId, $today);
    
    return $stmt->execute();
}

// Initialize counters
$successCount = 0;
$errors = [];

// If user is logged in and is a student, enroll them in all available courses
if ($isStudent && $currentUserId > 0) {
    // Get all courses
    $courseQuery = $conn->query("SELECT course_id FROM courses");
    
    while ($course = $courseQuery->fetch_assoc()) {
        if (enrollInCourse($conn, $currentUserId, $course['course_id'])) {
            $successCount++;
        } else {
            $errors[] = "Failed to enroll in course ID: " . $course['course_id'];
        }
    }
} else {
    // Create a sample student user and enroll them in courses
    // Check if we have a sample student
    $studentQuery = $conn->query("SELECT user_id FROM users WHERE role = 'student' LIMIT 1");
    $studentId = 0;
    
    if ($studentQuery->num_rows > 0) {
        $studentData = $studentQuery->fetch_assoc();
        $studentId = $studentData['user_id'];
    } else {
        // Create a sample student
        $hashedPassword = password_hash('student123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
        $username = "student";
        $email = "student@example.com";
        $stmt->bind_param("sss", $username, $email, $hashedPassword);
        
        if ($stmt->execute()) {
            $studentId = $conn->insert_id;
        } else {
            $errors[] = "Failed to create sample student user.";
        }
    }
    
    // Enroll the student in all courses
    if ($studentId > 0) {
        $courseQuery = $conn->query("SELECT course_id FROM courses");
        
        while ($course = $courseQuery->fetch_assoc()) {
            if (enrollInCourse($conn, $studentId, $course['course_id'])) {
                $successCount++;
            } else {
                $errors[] = "Failed to enroll in course ID: " . $course['course_id'];
            }
        }
    }
}

// Check if the AssignmentSubmissions table exists
$tableExists = false;
$checkTableQuery = $conn->query("SHOW TABLES LIKE 'assignmentsubmissions'");
if ($checkTableQuery->num_rows > 0) {
    $tableExists = true;
} else {
    // Create the AssignmentSubmissions table
    $createTableSQL = "CREATE TABLE `assignmentsubmissions` (
        `submission_id` int(11) NOT NULL AUTO_INCREMENT,
        `assignment_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `content` text DEFAULT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `score` float DEFAULT NULL,
        `feedback` text DEFAULT NULL,
        PRIMARY KEY (`submission_id`),
        KEY `assignment_id` (`assignment_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($createTableSQL)) {
        $tableExists = true;
    } else {
        $errors[] = "Failed to create AssignmentSubmissions table: " . $conn->error;
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sample Enrollments - WebCourses</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sample Enrollments Setup</h1>
        
        <div class="result <?php echo ($successCount > 0) ? 'success' : 'error'; ?>">
            <p><strong>Result:</strong> Created <?php echo $successCount; ?> course enrollments.</p>
            
            <?php if ($tableExists): ?>
                <div class="info">
                    <p>The AssignmentSubmissions table is ready.</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <p><strong>Errors:</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div>
            <p>Now you can view and manage your assignments:</p>
            <a href="<?php echo BASE_URL; ?>app/views/product/assignments.php" class="btn">Go to Assignments</a>
            <a href="<?php echo BASE_URL; ?>app/views/product/student_dashboard.php" class="btn">Go to Dashboard</a>
        </div>
    </div>
</body>
</html> 