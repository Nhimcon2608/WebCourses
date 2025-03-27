<?php
// Script to create webcourses database using the same schema as online_courses

// Database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'webcourses';

// Create connection without selecting a database
$conn = new mysqli($host, $user, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to MySQL server successfully.<br>";

// Create the database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
echo "Database '$database' created or already exists.<br>";

// Select the database
$conn->select_db($database);
echo "Selected database: $database.<br>";

// Path to the SQL file
$sqlFile = 'database/online_cources.sql';

if (!file_exists($sqlFile)) {
    // Try alternative filename
    $sqlFile = 'database/online_courses.sql';
    if (!file_exists($sqlFile)) {
        die("Error: SQL file not found. Please check the database directory.");
    }
}

echo "Found SQL file: $sqlFile<br>";

// Read the SQL file
$sql = file_get_contents($sqlFile);

// Split SQL by semicolon
$commands = explode(';', $sql);

// Execute each command
echo "<strong>Executing SQL commands:</strong><br>";
echo "<ul>";

foreach ($commands as $command) {
    $command = trim($command);
    if (empty($command)) continue;
    
    // Skip commands that are comments or just whitespace
    if (preg_match('/^--/', $command) || empty(trim($command))) {
        continue;
    }
    
    // Add a semicolon back
    $command .= ';';
    
    try {
        if ($conn->query($command)) {
            echo "<li>✅ Command executed successfully.</li>";
        } else {
            echo "<li>❌ Error executing command: " . $conn->error . "</li>";
        }
    } catch (mysqli_sql_exception $e) {
        // Check if it's a "table already exists" error - which is okay
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<li>⚠️ " . $e->getMessage() . " - Skipping</li>";
        } else {
            echo "<li>❌ Error: " . $e->getMessage() . "</li>";
        }
    }
}

echo "</ul>";

// Now let's check all the tables
$tables_result = $conn->query("SHOW TABLES");

if ($tables_result->num_rows > 0) {
    echo "<strong>Tables created:</strong><br>";
    echo "<ol>";
    
    while($table_row = $tables_result->fetch_array()) {
        $table_name = $table_row[0];
        echo "<li>" . $table_name . "</li>";
    }
    
    echo "</ol>";
} else {
    echo "No tables were created.<br>";
}

// Close connection
$conn->close();

echo "<p>Database setup completed.</p>";
echo "<p><a href='app/views/product/home.php'>Try accessing home.php now</a></p>";
?> 