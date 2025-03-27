<?php
// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('BASE_URL', '/WebCourses/');
define('ROOT_DIR', __DIR__);

// Test direct inclusion of manage_lessons.php
echo "<h2>Directly Including manage_lessons.php:</h2>";
echo "<hr>";

// Store the current output buffer
ob_start();

// Include the file
include_once ROOT_DIR . '/app/views/product/manage_lessons.php';

// Get and clean the buffer
$output = ob_get_clean();

// Display the output
echo $output;
?> 