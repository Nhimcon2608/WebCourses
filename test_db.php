<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include 'app/config/connect.php';
    
    echo "Database Connection Test\n";
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "Connected successfully\n";
    
    // Check if the courses table exists
    $result = $conn->query("SHOW TABLES LIKE 'courses'");
    if ($result->num_rows > 0) {
        echo "Courses table exists\n";
    } else {
        echo "Courses table does not exist\n";
    }
    
    // Check if the lessons table exists
    $result = $conn->query("SHOW TABLES LIKE 'lessons'");
    if ($result->num_rows > 0) {
        echo "Lessons table exists\n";
    } else {
        echo "Lessons table does not exist\n";
    }
    
    // Check if the lesson_materials table exists
    $result = $conn->query("SHOW TABLES LIKE 'lesson_materials'");
    if ($result->num_rows > 0) {
        echo "Lesson_materials table exists\n";
    } else {
        echo "Lesson_materials table does not exist\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 