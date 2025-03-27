<?php
/**
 * Script to add more assignments to the database
 */

// Database credentials
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

// Connect to database
try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h1>Adding More Assignments</h1>";
    
    // Load SQL file
    $sqlFile = 'database/add_assignments.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($statement) {
            return !empty($statement);
        }
    );
    
    // Execute statements
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>";
    echo "<ul>";
    
    foreach ($statements as $statement) {
        if ($conn->query($statement . ';')) {
            // Get first line of statement for display
            $firstLine = strtok($statement, "\n");
            echo "<li style='color: green; margin-bottom: 10px;'>✓ Successfully executed: " . htmlspecialchars(substr($firstLine, 0, 100)) . "...</li>";
        } else {
            echo "<li style='color: red; margin-bottom: 10px;'>✗ Error executing: " . htmlspecialchars(substr($statement, 0, 100)) . "... Error: " . $conn->error . "</li>";
        }
    }
    
    echo "</ul>";
    
    // Count assignments
    $result = $conn->query("SELECT COUNT(*) as total FROM assignments");
    $count = $result->fetch_assoc()['total'];
    
    echo "<p style='font-size: 18px;'>Total assignments in database now: <strong>$count</strong></p>";
    
    echo "<p><a href='app/views/product/assignments.php' style='display: inline-block; padding: 10px 20px; background-color: #1e3c72; color: white; text-decoration: none; border-radius: 5px;'>Go to Assignments Page</a></p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; font-family: Arial; background: #f8d7da; border-radius: 5px; margin: 20px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Please make sure the database connection information is correct and the database exists.</p>";
    echo "<p><a href='setup_db.php'>Run Database Setup</a></p>";
    echo "</div>";
}
?> 