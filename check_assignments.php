<?php
// Script to check assignments and enrollments for current user

// Database connection details
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Assignment System Diagnostic</h2>";
    
    // Start session to check user
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        echo "<div style='color:red'>You are not logged in. Please <a href='http://localhost:8080/WebCourses/app/views/product/login.php'>log in</a> first.</div>";
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    echo "<div>Logged in as User ID: {$user_id}, Role: {$role}</div>";
    
    // Check user details
    $userCheck = $conn->prepare("SELECT * FROM Users WHERE user_id = ?");
    $userCheck->bind_param("i", $user_id);
    $userCheck->execute();
    $userResult = $userCheck->get_result();
    
    if ($userResult->num_rows == 0) {
        echo "<div style='color:red'>User not found in database.</div>";
        exit;
    }
    
    $userData = $userResult->fetch_assoc();
    echo "<div>User details: {$userData['fullname']} ({$userData['email']})</div>";
    
    // Check if student
    if ($role != 'student') {
        echo "<div style='color:orange'>Note: You are not logged in as a student. Only students can view assignments.</div>";
    }
    
    // Check courses and enrollments
    $coursesQuery = "
        SELECT c.course_id, c.title, c.teacher_id, e.status, e.enrollment_date  
        FROM Courses c
        LEFT JOIN Enrollments e ON c.course_id = e.course_id AND e.user_id = ?
        ORDER BY c.course_id
    ";
    
    $coursesStmt = $conn->prepare($coursesQuery);
    $coursesStmt->bind_param("i", $user_id);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
    
    echo "<h3>Available Courses:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse'>";
    echo "<tr><th>Course ID</th><th>Title</th><th>Teacher ID</th><th>Enrollment Status</th><th>Enrollment Date</th></tr>";
    
    $enrolledCount = 0;
    
    if ($coursesResult->num_rows > 0) {
        while($course = $coursesResult->fetch_assoc()) {
            $status = isset($course['status']) ? $course['status'] : 'Not enrolled';
            $date = isset($course['enrollment_date']) ? $course['enrollment_date'] : '-';
            
            echo "<tr>
                <td>{$course['course_id']}</td>
                <td>{$course['title']}</td>
                <td>{$course['teacher_id']}</td>
                <td style='color:" . ($status === 'active' ? 'green' : 'red') . "'>{$status}</td>
                <td>{$date}</td>
            </tr>";
            
            if ($status === 'active') {
                $enrolledCount++;
            }
        }
    } else {
        echo "<tr><td colspan='5'>No courses found in database.</td></tr>";
    }
    
    echo "</table>";
    
    if ($enrolledCount == 0) {
        echo "<div style='color:red'>You are not actively enrolled in any courses. Please enroll in a course first.</div>";
        
        // Create enrollment if needed
        echo "<h3>Fix: Enroll in available courses</h3>";
        echo "<form method='post'>";
        echo "<input type='submit' name='fix_enrollment' value='Enroll in all courses'>";
        echo "</form>";
        
        if (isset($_POST['fix_enrollment'])) {
            // Reset the result pointer
            $coursesStmt->execute();
            $coursesResult = $coursesStmt->get_result();
            
            $enrollStmt = $conn->prepare("INSERT INTO Enrollments (user_id, course_id, enrollment_date, status) VALUES (?, ?, NOW(), 'active') ON DUPLICATE KEY UPDATE status = 'active'");
            
            while($course = $coursesResult->fetch_assoc()) {
                $enrollStmt->bind_param("ii", $user_id, $course['course_id']);
                $enrollStmt->execute();
                echo "<div>Enrolled in course: {$course['title']}</div>";
            }
            
            echo "<div style='color:green'>Enrollment complete. <a href='check_assignments.php'>Refresh page</a> to see updates.</div>";
        }
    }
    
    // Check assignments
    echo "<h3>Available Assignments:</h3>";
    
    // Check if AssignmentSubmissions table exists
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'AssignmentSubmissions'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $tableExists = true;
        echo "<div>AssignmentSubmissions table exists.</div>";
    } else {
        echo "<div style='color:orange'>AssignmentSubmissions table does not exist.</div>";
        
        // Create table if needed
        echo "<h3>Fix: Create AssignmentSubmissions table</h3>";
        echo "<form method='post'>";
        echo "<input type='submit' name='create_table' value='Create AssignmentSubmissions table'>";
        echo "</form>";
        
        if (isset($_POST['create_table'])) {
            $conn->query("
                CREATE TABLE AssignmentSubmissions (
                    submission_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    assignment_id INT NOT NULL,
                    submission_text TEXT,
                    file_path VARCHAR(255),
                    grade INT DEFAULT NULL,
                    feedback TEXT,
                    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES Users(user_id),
                    FOREIGN KEY (assignment_id) REFERENCES Assignments(assignment_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            echo "<div style='color:green'>AssignmentSubmissions table created. <a href='check_assignments.php'>Refresh page</a> to see updates.</div>";
            $tableExists = true;
        }
    }
    
    // Get assignments from courses user is enrolled in
    if ($enrolledCount > 0) {
        $assignmentsQuery = "
            SELECT a.assignment_id, a.title, a.description, a.due_date, a.max_points,
                c.title as course_title, c.course_id
            FROM Assignments a
            JOIN Courses c ON a.course_id = c.course_id
            JOIN Enrollments e ON c.course_id = e.course_id
            WHERE e.user_id = ? AND e.status = 'active'
            ORDER BY a.due_date ASC
        ";
        
        $assignmentsStmt = $conn->prepare($assignmentsQuery);
        $assignmentsStmt->bind_param("i", $user_id);
        $assignmentsStmt->execute();
        $assignmentsResult = $assignmentsStmt->get_result();
        
        if ($assignmentsResult->num_rows > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse'>";
            echo "<tr><th>ID</th><th>Title</th><th>Course</th><th>Due Date</th><th>Points</th><th>Status</th></tr>";
            
            while($assignment = $assignmentsResult->fetch_assoc()) {
                $status = "Pending";
                $statusColor = "orange";
                
                if ($tableExists) {
                    // Check submission status
                    $submissionCheck = $conn->prepare("SELECT * FROM AssignmentSubmissions WHERE assignment_id = ? AND user_id = ?");
                    $submissionCheck->bind_param("ii", $assignment['assignment_id'], $user_id);
                    $submissionCheck->execute();
                    $submissionResult = $submissionCheck->get_result();
                    
                    if ($submissionResult->num_rows > 0) {
                        $submission = $submissionResult->fetch_assoc();
                        if ($submission['grade'] !== null) {
                            $status = "Graded: " . $submission['grade'] . "/" . $assignment['max_points'];
                            $statusColor = "blue";
                        } else {
                            $status = "Submitted";
                            $statusColor = "green";
                        }
                    }
                }
                
                // Check if overdue
                if ($status === "Pending" && strtotime($assignment['due_date']) < time()) {
                    $status = "Overdue";
                    $statusColor = "red";
                }
                
                echo "<tr>
                    <td>{$assignment['assignment_id']}</td>
                    <td>{$assignment['title']}</td>
                    <td>{$assignment['course_title']}</td>
                    <td>" . date('Y-m-d', strtotime($assignment['due_date'])) . "</td>
                    <td>{$assignment['max_points']}</td>
                    <td style='color:{$statusColor}'>{$status}</td>
                </tr>";
            }
            
            echo "</table>";
            
            echo "<div style='margin-top:20px'><a href='http://localhost:8080/WebCourses/app/views/product/assignments.php' style='padding:10px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Go to Assignments Page</a></div>";
        } else {
            echo "<div>No assignments found for enrolled courses.</div>";
            
            // Create assignments if needed
            echo "<h3>Fix: Create sample assignments</h3>";
            echo "<form method='post'>";
            echo "<input type='submit' name='create_assignments' value='Create sample assignments'>";
            echo "</form>";
            
            if (isset($_POST['create_assignments'])) {
                // Get available courses for linking assignments
                $coursesForAssignments = $conn->query("
                    SELECT course_id, title 
                    FROM Courses 
                    JOIN Enrollments ON Courses.course_id = Enrollments.course_id 
                    WHERE Enrollments.user_id = {$user_id} AND Enrollments.status = 'active'
                ");
                
                $courses = [];
                while ($row = $coursesForAssignments->fetch_assoc()) {
                    $courses[] = $row;
                }
                
                if (count($courses) > 0) {
                    // Sample assignments
                    $sampleAssignments = [
                        [
                            'title' => 'Bài tập lập trình cơ bản với PHP',
                            'description' => 'Viết một chương trình PHP đơn giản thực hiện các phép tính cơ bản và hiển thị kết quả ra màn hình.',
                            'due_date' => date('Y-m-d', strtotime('+7 days')),
                            'max_points' => 10,
                            'course_id' => $courses[0]['course_id']
                        ],
                        [
                            'title' => 'Thiết kế giao diện người dùng',
                            'description' => 'Xây dựng một trang web đơn giản với HTML/CSS theo mẫu thiết kế được cung cấp.',
                            'due_date' => date('Y-m-d', strtotime('+5 days')),
                            'max_points' => 15,
                            'course_id' => isset($courses[1]) ? $courses[1]['course_id'] : $courses[0]['course_id']
                        ],
                        [
                            'title' => 'Báo cáo nghiên cứu về trí tuệ nhân tạo',
                            'description' => 'Tìm hiểu và viết báo cáo về các ứng dụng của trí tuệ nhân tạo trong lĩnh vực giáo dục.',
                            'due_date' => date('Y-m-d', strtotime('-2 days')),
                            'max_points' => 30,
                            'course_id' => isset($courses[2]) ? $courses[2]['course_id'] : $courses[0]['course_id']
                        ]
                    ];
                    
                    // Insert sample assignments
                    $stmt = $conn->prepare("
                        INSERT INTO Assignments (title, description, due_date, max_points, course_id, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    foreach ($sampleAssignments as $assignment) {
                        $stmt->bind_param("ssidi", 
                            $assignment['title'],
                            $assignment['description'],
                            $assignment['due_date'],
                            $assignment['max_points'],
                            $assignment['course_id']
                        );
                        
                        if ($stmt->execute()) {
                            echo "<div>Added assignment: " . $assignment['title'] . "</div>";
                        } else {
                            echo "<div style='color:red'>Error adding assignment: " . $stmt->error . "</div>";
                        }
                    }
                    
                    echo "<div style='color:green'>Sample assignments added. <a href='check_assignments.php'>Refresh page</a> to see updates.</div>";
                }
            }
        }
    } else {
        echo "<div>Please enroll in courses first to see assignments.</div>";
    }
    
    echo "<h3>Database Connection Information:</h3>";
    echo "<div>MySQL Server Version: " . $conn->server_info . "</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red'>Error: " . $e->getMessage() . "</div>";
}
?> 