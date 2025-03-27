<?php
// Script to check database status and fix issues

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
    
    echo "Connected to database successfully.<br>";
    
    // Check if Courses table has any records
    $result = $conn->query("SELECT COUNT(*) as count FROM Courses");
    $courseCount = $result->fetch_assoc()['count'];
    
    echo "Current course count: " . $courseCount . "<br>";
    
    // If no courses exist, create sample ones
    if ($courseCount == 0) {
        echo "No courses found. Adding sample courses...<br>";
        
        // Sample courses
        $sampleCourses = [
            [
                'title' => 'Lập trình PHP cơ bản',
                'description' => 'Khóa học giới thiệu về ngôn ngữ lập trình PHP và cách xây dựng ứng dụng web đơn giản.',
                'price' => 300000,
                'image_path' => 'uploads/courses/php_basic.jpg',
                'teacher_id' => 1 // Assuming teacher with ID 1 exists
            ],
            [
                'title' => 'Thiết kế web với HTML/CSS',
                'description' => 'Học cách tạo giao diện web đẹp mắt sử dụng HTML5 và CSS3 với các kỹ thuật hiện đại.',
                'price' => 250000,
                'image_path' => 'uploads/courses/html_css.jpg',
                'teacher_id' => 1
            ],
            [
                'title' => 'JavaScript và jQuery',
                'description' => 'Tìm hiểu JavaScript và thư viện jQuery để tạo các tương tác động trên trang web.',
                'price' => 350000,
                'image_path' => 'uploads/courses/javascript.jpg',
                'teacher_id' => 1
            ]
        ];
        
        // Insert sample courses
        $stmt = $conn->prepare("
            INSERT INTO Courses (title, description, price, image_path, teacher_id, created_at, updated_at, status) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW(), 'active')
        ");
        
        foreach ($sampleCourses as $course) {
            $stmt->bind_param("ssdsi", 
                $course['title'],
                $course['description'],
                $course['price'],
                $course['image_path'],
                $course['teacher_id']
            );
            
            if ($stmt->execute()) {
                echo "Added course: " . $course['title'] . "<br>";
            } else {
                echo "Error adding course: " . $stmt->error . "<br>";
            }
        }
    }
    
    // Check if Assignments table has any records
    $result = $conn->query("SELECT COUNT(*) as count FROM Assignments");
    $assignmentCount = $result->fetch_assoc()['count'];
    
    echo "Current assignment count: " . $assignmentCount . "<br>";
    
    // If we have less than 5 assignments, add some sample ones
    if ($assignmentCount < 5) {
        echo "Adding sample assignments...<br>";
        
        // Get available courses for linking assignments
        $coursesResult = $conn->query("SELECT course_id, title FROM Courses");
        $courses = [];
        while ($row = $coursesResult->fetch_assoc()) {
            $courses[] = $row;
        }
        
        if (count($courses) == 0) {
            echo "No courses found. Please add courses first.<br>";
            exit;
        }
        
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
                'title' => 'Phân tích yêu cầu phần mềm',
                'description' => 'Phân tích các yêu cầu cho hệ thống quản lý thư viện và lập tài liệu đặc tả yêu cầu.',
                'due_date' => date('Y-m-d', strtotime('+10 days')),
                'max_points' => 20,
                'course_id' => isset($courses[2]) ? $courses[2]['course_id'] : $courses[0]['course_id']
            ],
            [
                'title' => 'Bài tập về cơ sở dữ liệu',
                'description' => 'Thiết kế cơ sở dữ liệu cho hệ thống quản lý sinh viên, bao gồm các bảng, mối quan hệ và ràng buộc.',
                'due_date' => date('Y-m-d', strtotime('+3 days')),
                'max_points' => 25,
                'course_id' => isset($courses[1]) ? $courses[1]['course_id'] : $courses[0]['course_id']
            ],
            [
                'title' => 'Báo cáo nghiên cứu về trí tuệ nhân tạo',
                'description' => 'Tìm hiểu và viết báo cáo về các ứng dụng của trí tuệ nhân tạo trong lĩnh vực giáo dục.',
                'due_date' => date('Y-m-d', strtotime('-2 days')), // This one is overdue
                'max_points' => 30,
                'course_id' => isset($courses[2]) ? $courses[2]['course_id'] : $courses[0]['course_id']
            ],
            [
                'title' => 'Bài tập về mạng máy tính',
                'description' => 'Thiết kế một mô hình mạng cục bộ cho một doanh nghiệp nhỏ và mô tả cách triển khai.',
                'due_date' => date('Y-m-d', strtotime('+15 days')),
                'max_points' => 20,
                'course_id' => isset($courses[0]) ? $courses[0]['course_id'] : $courses[0]['course_id']
            ],
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
                echo "Added assignment: " . $assignment['title'] . "<br>";
            } else {
                echo "Error adding assignment: " . $stmt->error . "<br>";
            }
        }
        
        echo "Sample assignments added successfully.<br>";
    } else {
        echo "There are already assignments in the database. No need to add samples.<br>";
    }
    
    // Check if the AssignmentSubmissions table exists
    $result = $conn->query("SHOW TABLES LIKE 'AssignmentSubmissions'");
    if ($result->num_rows == 0) {
        echo "Creating AssignmentSubmissions table...<br>";
        
        // Create the table if it doesn't exist
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
        
        echo "AssignmentSubmissions table created successfully.<br>";
    } else {
        echo "AssignmentSubmissions table already exists.<br>";
    }
    
    // Check if there are student enrollments
    $result = $conn->query("SELECT COUNT(*) as count FROM Enrollments WHERE status = 'active'");
    $enrollmentCount = $result->fetch_assoc()['count'];
    
    echo "Active enrollments count: " . $enrollmentCount . "<br>";
    
    if ($enrollmentCount == 0) {
        echo "No active enrollments found. Make sure students are enrolled in courses.<br>";
        
        // Get student users
        $studentsResult = $conn->query("SELECT user_id FROM Users WHERE role = 'student' LIMIT 5");
        $students = [];
        while ($row = $studentsResult->fetch_assoc()) {
            $students[] = $row;
        }
        
        // If we have students and courses, create enrollments
        if (count($students) > 0 && count($courses) > 0) {
            echo "Creating sample enrollments...<br>";
            
            $stmt = $conn->prepare("
                INSERT INTO Enrollments (user_id, course_id, enrollment_date, status)
                VALUES (?, ?, NOW(), 'active')
            ");
            
            foreach ($students as $student) {
                foreach ($courses as $course) {
                    $stmt->bind_param("ii", $student['user_id'], $course['course_id']);
                    
                    if ($stmt->execute()) {
                        echo "Enrolled student " . $student['user_id'] . " in course " . $course['title'] . "<br>";
                    }
                }
            }
        }
    }
    
    echo "<br>Check completed. The database should now have courses and assignments.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 