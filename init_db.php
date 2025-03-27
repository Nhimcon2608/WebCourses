<?php
// Database initialization script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'webcourses';

try {
    // Connect to MySQL without selecting a database
    $conn = new PDO("mysql:host=$host", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Database Initialization</h1>";
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$database`");
    echo "<p>Database '$database' created or already exists</p>";
    
    // Select the database
    $conn->exec("USE `$database`");
    echo "<p>Using database: $database</p>";
    
    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `users` (
        `user_id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `is_locked` TINYINT(1) DEFAULT 0,
        `role` ENUM('student', 'instructor', 'admin') DEFAULT 'student',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p>Users table created or already exists</p>";
    
    // Create courses table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `courses` (
        `course_id` INT AUTO_INCREMENT PRIMARY KEY,
        `instructor_id` INT NOT NULL,
        `category_id` INT,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `price` DECIMAL(10,2) DEFAULT 0,
        `image` VARCHAR(255),
        `level` VARCHAR(50),
        `video` VARCHAR(255),
        `duration` VARCHAR(50),
        `status` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p>Courses table created or already exists</p>";
    
    // Create reviews table correctly with proper foreign keys
    $sql = "CREATE TABLE IF NOT EXISTS `reviews` (
        `review_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `course_id` INT NOT NULL,
        `review_text` TEXT NOT NULL,
        `rating` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`user_id`),
        INDEX (`course_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p>Reviews table created or already exists</p>";
    
    // Try to add foreign keys separately (this may fail if referenced tables don't exist yet)
    try {
        $conn->exec("ALTER TABLE `reviews` ADD CONSTRAINT `fk_review_user` 
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE");
        echo "<p>Added foreign key for reviews.user_id</p>";
    } catch (PDOException $e) {
        echo "<p>Warning: Could not add foreign key for users: " . $e->getMessage() . "</p>";
    }
    
    try {
        $conn->exec("ALTER TABLE `reviews` ADD CONSTRAINT `fk_review_course` 
                    FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE");
        echo "<p>Added foreign key for reviews.course_id</p>";
    } catch (PDOException $e) {
        echo "<p>Warning: Could not add foreign key for courses: " . $e->getMessage() . "</p>";
    }
    
    // Insert admin user if it doesn't exist
    $stmt = $conn->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO `users` (username, email, password, role, created_at) 
                               VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['admin', 'admin@example.com', $hashed_password, 'admin']);
        echo "<p>Admin user created</p>";
    } else {
        echo "<p>Admin user already exists</p>";
    }
    
    echo "<h2>Database Setup Complete</h2>";
    echo "<p><a href='test_register.php'>Test User Registration</a></p>";
    
} catch (PDOException $e) {
    die("<p>Database initialization error: " . $e->getMessage() . "</p>");
}
?> 