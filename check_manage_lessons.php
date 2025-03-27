<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the URL to check
$url = 'http://localhost/WebCourses/app/views/product/manage_lessons.php';

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the request
$response = curl_exec($ch);

// Get status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get headers and body
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

// Close the cURL session
curl_close($ch);

// Output the results
echo "<h1>Checking URL: $url</h1>";
echo "<h2>Status Code: $httpCode</h2>";

// Output headers
echo "<h2>Response Headers:</h2>";
echo "<pre>" . htmlspecialchars($header) . "</pre>";

// Check if the response has the expected content
$hasTitle = (strpos($body, 'Quản Lý Bài Giảng') !== false);
$hasSidebar = (strpos($body, '<div class="sidebar"') !== false);
$hasForm = (strpos($body, '<form method="POST" enctype="multipart/form-data">') !== false);

echo "<h2>Content Check:</h2>";
echo "<p>Has title 'Quản Lý Bài Giảng': " . ($hasTitle ? 'Yes' : 'No') . "</p>";
echo "<p>Has sidebar: " . ($hasSidebar ? 'Yes' : 'No') . "</p>";
echo "<p>Has form: " . ($hasForm ? 'Yes' : 'No') . "</p>";

// Sample the beginning of the body content
echo "<h2>Sample Content (first 300 chars):</h2>";
echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "...</pre>";

// Output information about included files (including instructor_dashboard.css)
echo "<h2>File Checks:</h2>";
$cssPath = __DIR__ . '/public/css/instructor_dashboard.css';
echo "<p>instructor_dashboard.css exists: " . (file_exists($cssPath) ? 'Yes' : 'No') . "</p>";
echo "<p>instructor_dashboard.css size: " . (file_exists($cssPath) ? filesize($cssPath) . ' bytes' : 'N/A') . "</p>";

$jsPath = __DIR__ . '/public/js/instructor_dashboard.js';
echo "<p>instructor_dashboard.js exists: " . (file_exists($jsPath) ? 'Yes' : 'No') . "</p>";
echo "<p>instructor_dashboard.js size: " . (file_exists($jsPath) ? filesize($jsPath) . ' bytes' : 'N/A') . "</p>";

$phpPath = __DIR__ . '/app/views/product/manage_lessons.php';
echo "<p>manage_lessons.php exists: " . (file_exists($phpPath) ? 'Yes' : 'No') . "</p>";
echo "<p>manage_lessons.php size: " . (file_exists($phpPath) ? filesize($phpPath) . ' bytes' : 'N/A') . "</p>";
?>

<p>This script tests whether the Manage Lessons page is accessible via HTTP. If you're seeing this, the script ran successfully.</p> 