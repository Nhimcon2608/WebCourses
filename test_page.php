<?php
// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('BASE_URL', '/WebCourses/');
define('ROOT_DIR', __DIR__);

// Check PHP version and extensions
echo "<h1>PHP Environment Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";

// Test session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Session ID: " . session_id() . "</p>";
$_SESSION['test'] = 'Working';
echo "<p>Session Test: " . ($_SESSION['test'] ?? 'Not set') . "</p>";

// Path testing
echo "<h2>Path Testing</h2>";
echo "<p>BASE_URL: " . BASE_URL . "</p>";
echo "<p>ROOT_DIR: " . ROOT_DIR . "</p>";
echo "<p>app/views/product/manage_lessons.php exists: " . (file_exists(ROOT_DIR . '/app/views/product/manage_lessons.php') ? 'Yes' : 'No') . "</p>";
echo "<p>public/css/instructor_dashboard.css exists: " . (file_exists(ROOT_DIR . '/public/css/instructor_dashboard.css') ? 'Yes' : 'No') . "</p>";

// Database connection test
echo "<h2>Database Connection Test</h2>";
try {
    include ROOT_DIR . '/app/config/connect.php';
    echo "<p>Database connection: " . (isset($conn) ? 'Success' : 'Failed') . "</p>";
    
    if (isset($conn)) {
        // Test tables
        $tables = ['lessons', 'lesson_materials', 'courses', 'notifications'];
        echo "<ul>";
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            echo "<li>Table '$table' exists: " . ($result->num_rows > 0 ? 'Yes' : 'No') . "</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Navigation Test
echo "<h2>Navigation Test</h2>";
echo "<p><a href='" . BASE_URL . "app/views/product/instructor_dashboard.php'>Go to Instructor Dashboard</a></p>";
echo "<p><a href='" . BASE_URL . "app/views/product/manage_lessons.php'>Go to Manage Lessons</a></p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        h1, h2 {
            color: #3498db;
        }
        p {
            margin: 5px 0;
        }
        ul {
            list-style-type: none;
            padding-left: 20px;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <!-- Content already output in PHP above -->
</body>
</html> 