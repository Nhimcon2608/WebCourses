<?php
// Database connection parameters
$server = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL server without selecting a database
    $conn = new PDO("mysql:host=$server", $user, $pass);
    
    // Set PDO to throw exceptions on error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Setup Script</h2>";
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS webcourses");
    echo "<p>✓ Database 'webcourses' created or already exists</p>";
    
    // Select the database
    $conn->exec("USE webcourses");
    
    // Create Users table
    $conn->exec("CREATE TABLE IF NOT EXISTS Users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('student', 'instructor', 'admin') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✓ Users table created</p>";
    
    // Create Courses table
    $conn->exec("CREATE TABLE IF NOT EXISTS Courses (
        course_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        instructor_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (instructor_id) REFERENCES Users(user_id)
    )");
    echo "<p>✓ Courses table created</p>";
    
    // Create Enrollments table
    $conn->exec("CREATE TABLE IF NOT EXISTS Enrollments (
        enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        status ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES Users(user_id),
        FOREIGN KEY (course_id) REFERENCES Courses(course_id)
    )");
    echo "<p>✓ Enrollments table created</p>";
    
    // Create Lessons table
    $conn->exec("CREATE TABLE IF NOT EXISTS Lessons (
        lesson_id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        order_num INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES Courses(course_id)
    )");
    echo "<p>✓ Lessons table created</p>";
    
    // Create Assignments table
    $conn->exec("CREATE TABLE IF NOT EXISTS Assignments (
        assignment_id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        due_date DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES Courses(course_id)
    )");
    echo "<p>✓ Assignments table created</p>";
    
    // Create AssignmentSubmissions table
    $conn->exec("CREATE TABLE IF NOT EXISTS AssignmentSubmissions (
        submission_id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id INT NOT NULL,
        user_id INT NOT NULL,
        submission_text TEXT,
        file_path VARCHAR(255),
        grade DECIMAL(5,2),
        feedback TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (assignment_id) REFERENCES Assignments(assignment_id),
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
    )");
    echo "<p>✓ AssignmentSubmissions table created</p>";
    
    // Check if sample data should be added
    $check_admin = $conn->query("SELECT COUNT(*) as count FROM Users WHERE username = 'admin'");
    $admin_exists = $check_admin->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    if (!$admin_exists) {
        // Insert sample admin user (password hash for 'password123')
        $conn->exec("INSERT INTO Users (username, email, password, full_name, role)
        VALUES ('admin', 'admin@example.com', '$2y$10$8KsRftBtX7gTY6S9MzpsVu6bpP7DP0xV.qqP5TQsQiBaVz2kQQg2a', 'Admin User', 'admin')");
        
        // Insert sample instructor
        $conn->exec("INSERT INTO Users (username, email, password, full_name, role)
        VALUES ('instructor', 'instructor@example.com', '$2y$10$8KsRftBtX7gTY6S9MzpsVu6bpP7DP0xV.qqP5TQsQiBaVz2kQQg2a', 'Instructor User', 'instructor')");
        
        // Insert sample student
        $conn->exec("INSERT INTO Users (username, email, password, full_name, role)
        VALUES ('student', 'student@example.com', '$2y$10$8KsRftBtX7gTY6S9MzpsVu6bpP7DP0xV.qqP5TQsQiBaVz2kQQg2a', 'Student User', 'student')");
        
        // Insert sample course
        $conn->exec("INSERT INTO Courses (title, description, instructor_id)
        VALUES ('Introduction to Web Development', 'Learn the basics of HTML, CSS, and JavaScript to create modern websites.', 2)");
        
        // Enroll sample student in the course
        $conn->exec("INSERT INTO Enrollments (user_id, course_id, status)
        VALUES (3, 1, 'active')");
        
        // Add sample lessons
        $conn->exec("INSERT INTO Lessons (course_id, title, content, order_num)
        VALUES 
        (1, 'HTML Basics', 'Introduction to HTML structure and common elements.', 1),
        (1, 'CSS Styling', 'Learn how to style your HTML with CSS.', 2),
        (1, 'JavaScript Fundamentals', 'Introduction to JavaScript programming.', 3)");
        
        // Add sample assignment
        $conn->exec("INSERT INTO Assignments (course_id, title, description, due_date)
        VALUES (1, 'Create a Simple Webpage', 'Create a webpage using HTML and CSS based on the provided design.', DATE_ADD(NOW(), INTERVAL 7 DAY))");
        
        echo "<p>✓ Sample data added to database</p>";
    } else {
        echo "<p>✓ Sample data already exists in database</p>";
    }
    
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
    echo "<h3 style='color: #3c763d;'>Database setup completed successfully!</h3>";
    echo "<p>You can now navigate to <a href='/WebCourses/app/views/product/home.php'>Home Page</a> and login with:</p>";
    echo "<ul>";
    echo "<li><strong>Student:</strong> student@example.com / password123</li>";
    echo "<li><strong>Instructor:</strong> instructor@example.com / password123</li>";
    echo "<li><strong>Admin:</strong> admin@example.com / password123</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #f2dede; border: 1px solid #ebccd1; border-radius: 4px;'>";
    echo "<h3 style='color: #a94442;'>Database setup error!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

$conn = null; // Close connection
?> 