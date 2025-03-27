<?php
// Script to modify the Assignments table to add lesson_id field

// Database connection details
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

try {
    // Connect to database
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Database Schema Update</h2>";
    
    // Check if lesson_id column already exists in Assignments table
    $checkColumn = $conn->query("SHOW COLUMNS FROM Assignments LIKE 'lesson_id'");
    
    if ($checkColumn->num_rows == 0) {
        // Column doesn't exist, so add it
        echo "<p>Adding 'lesson_id' column to Assignments table...</p>";
        
        // Add lesson_id column
        $alterSql = "ALTER TABLE Assignments ADD COLUMN lesson_id INT NULL AFTER course_id";
        
        if ($conn->query($alterSql)) {
            echo "<p style='color:green'>Successfully added lesson_id column.</p>";
            
            // Add foreign key constraint
            $fkSql = "ALTER TABLE Assignments ADD CONSTRAINT fk_lesson_id 
                    FOREIGN KEY (lesson_id) REFERENCES Lessons(lesson_id) 
                    ON DELETE SET NULL";
            
            if ($conn->query($fkSql)) {
                echo "<p style='color:green'>Successfully added foreign key constraint.</p>";
            } else {
                echo "<p style='color:orange'>Warning: Could not add foreign key constraint. This is not critical if you're sure the Lessons table exists. Error: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color:red'>Error: Could not add lesson_id column. " . $conn->error . "</p>";
        }
    } else {
        echo "<p>The 'lesson_id' column already exists in the Assignments table.</p>";
    }
    
    // Update existing assignments to link them with lessons if possible
    echo "<h3>Linking Existing Assignments to Lessons</h3>";
    
    // Get courses that have both lessons and assignments
    $coursesSql = "SELECT DISTINCT c.course_id, c.title 
                  FROM Courses c
                  JOIN Lessons l ON c.course_id = l.course_id
                  JOIN Assignments a ON c.course_id = a.course_id
                  WHERE a.lesson_id IS NULL";
    
    $coursesResult = $conn->query($coursesSql);
    
    if ($coursesResult->num_rows > 0) {
        echo "<p>Found " . $coursesResult->num_rows . " courses with unlinked assignments.</p>";
        
        while ($course = $coursesResult->fetch_assoc()) {
            echo "<div style='margin-left:20px'>";
            echo "<p>Processing course: " . htmlspecialchars($course['title']) . " (ID: " . $course['course_id'] . ")</p>";
            
            // Get assignments for this course
            $assignmentsSql = "SELECT assignment_id, title FROM Assignments 
                             WHERE course_id = ? AND lesson_id IS NULL";
            $stmtAssignments = $conn->prepare($assignmentsSql);
            $stmtAssignments->bind_param("i", $course['course_id']);
            $stmtAssignments->execute();
            $assignmentsResult = $stmtAssignments->get_result();
            
            if ($assignmentsResult->num_rows > 0) {
                echo "<p>Found " . $assignmentsResult->num_rows . " assignments to link.</p>";
                
                // Get lessons for this course
                $lessonsSql = "SELECT lesson_id, title FROM Lessons 
                             WHERE course_id = ? 
                             ORDER BY order_index ASC";
                $stmtLessons = $conn->prepare($lessonsSql);
                $stmtLessons->bind_param("i", $course['course_id']);
                $stmtLessons->execute();
                $lessonsResult = $stmtLessons->get_result();
                
                if ($lessonsResult->num_rows > 0) {
                    $lessons = array();
                    while ($lesson = $lessonsResult->fetch_assoc()) {
                        $lessons[] = $lesson;
                    }
                    
                    // Distribute assignments among lessons
                    $lessonIndex = 0;
                    $lessonCount = count($lessons);
                    
                    if ($lessonCount > 0) {
                        $updateStmt = $conn->prepare("UPDATE Assignments SET lesson_id = ? WHERE assignment_id = ?");
                        
                        while ($assignment = $assignmentsResult->fetch_assoc()) {
                            // Rotate through lessons if there are more assignments than lessons
                            $currentLesson = $lessons[$lessonIndex % $lessonCount];
                            
                            $updateStmt->bind_param("ii", $currentLesson['lesson_id'], $assignment['assignment_id']);
                            
                            if ($updateStmt->execute()) {
                                echo "<p style='color:green'>Linked assignment '" . 
                                     htmlspecialchars($assignment['title']) . 
                                     "' to lesson '" . htmlspecialchars($currentLesson['title']) . "'</p>";
                            } else {
                                echo "<p style='color:red'>Failed to link assignment '" . 
                                     htmlspecialchars($assignment['title']) . 
                                     "' to lesson. Error: " . $conn->error . "</p>";
                            }
                            
                            $lessonIndex++;
                        }
                    } else {
                        echo "<p style='color:orange'>No lessons found for this course.</p>";
                    }
                } else {
                    echo "<p style='color:orange'>No lessons found for this course.</p>";
                }
            } else {
                echo "<p>No unlinked assignments found for this course.</p>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>No courses with unlinked assignments found.</p>";
    }
    
    echo "<h3>Schema Update Complete</h3>";
    echo "<p>The assignment system is now updated to link assignments with specific lessons.</p>";
    echo "<p><a href='http://localhost:8080/WebCourses/check_assignments.php'>Go to Assignment Checker</a> | <a href='http://localhost:8080/WebCourses/app/views/product/student_dashboard.php'>Go to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:20px; font-family:Arial; background:#f8d7da; border-radius:5px; margin:20px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 