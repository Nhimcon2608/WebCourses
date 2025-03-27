<?php
// Define constants
define('BASE_URL', '/WebCourses/');
define('ROOT_DIR', __DIR__);

// Check if session is already started before starting it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to the create_course.php file
header("Location: " . BASE_URL . "app/views/product/create_course.php");
exit();
?> 