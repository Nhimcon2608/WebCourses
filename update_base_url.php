<?php
// Script to update all direct URLs from localhost to localhost:8080
echo "<h1>Updating URLs in PHP files</h1>";

// Directory to scan
$baseDir = __DIR__;
$fileCount = 0;
$updatedCount = 0;

function updateURLsInFile($filePath) {
    global $updatedCount;
    
    // Skip this file
    if (basename($filePath) == 'update_base_url.php') {
        return false;
    }
    
    // Read the file
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Update absolute URLs
    $content = str_replace('http://localhost/WebCourses', 'http://localhost:8080/WebCourses', $content);
    $content = str_replace('"/WebCourses/app', '"http://localhost:8080/WebCourses/app', $content);
    $content = str_replace("'/WebCourses/app", "'http://localhost:8080/WebCourses/app", $content);
    
    // Save changes if the content was modified
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $updatedCount++;
        return true;
    }
    
    return false;
}

function scanDirectory($dir) {
    global $fileCount;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $fileCount++;
            $updated = updateURLsInFile($path);
            
            if ($updated) {
                echo "<p>Updated file: " . str_replace($GLOBALS['baseDir'], '', $path) . "</p>";
            }
        }
    }
}

// Start scanning
echo "<p>Starting scan in: " . $baseDir . "</p>";
scanDirectory($baseDir);

echo "<h2>Update Complete</h2>";
echo "<p>Scanned $fileCount PHP files.</p>";
echo "<p>Updated URLs in $updatedCount files.</p>";
echo "<p><a href='http://localhost:8080/WebCourses/check_assignments.php'>Go to Assignments Checker</a></p>";

?> 