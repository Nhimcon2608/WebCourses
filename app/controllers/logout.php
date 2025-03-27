<?php
// File: app/controllers/logout.php
session_start();

// Define BASE_URL constant if not defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WebCourses/');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to the home page
header("Location: " . BASE_URL . "app/views/product/home.php");
exit();
?>