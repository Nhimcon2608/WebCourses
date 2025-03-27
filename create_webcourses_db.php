<?php
// Script to create webcourses database using a simplified schema

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Drop database if it exists to start fresh
$conn->query("DROP DATABASE IF EXISTS $database");
echo "Dropped existing database (if any).<br>";

// Create the database
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
echo "Database '$database' created.<br>";

// Select the database
$conn->select_db($database);
echo "Selected database: $database.<br>";

// Path to the simplified SQL file
$sqlFile = __DIR__ . '/database/simple_schema.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at: $sqlFile. Please check the database directory.");
}

echo "Found SQL file: $sqlFile<br>";

// Read the SQL file
$sql = file_get_contents($sqlFile);

// Split SQL by semicolon
$commands = explode(';', $sql);

// Execute each command
echo "<strong>Executing SQL commands:</strong><br>";
echo "<ul>";

$count = 0;
foreach ($commands as $command) {
    $command = trim($command);
    if (empty($command)) continue;
    
    // Skip comments
    if (preg_match('/^--/', $command) || empty(trim($command))) {
        continue;
    }
    
    // Add a semicolon back
    $command .= ';';
    $count++;
    
    try {
        if ($conn->query($command)) {
            echo "<li>✅ Command #$count executed successfully.</li>";
        } else {
            echo "<li>❌ Error executing command #$count: " . $conn->error . "</li>";
        }
    } catch (mysqli_sql_exception $e) {
        echo "<li>❌ Error in command #$count: " . $e->getMessage() . "</li>";
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