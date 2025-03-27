<?php
// Script to describe the Assignments table structure
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

// Connect to database
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Assignments Table Structure\n";
echo "==========================\n\n";

// Check if table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'Assignments'");
if ($tableExists->num_rows == 0) {
    echo "Assignments table does not exist!\n";
    exit;
}

// Get table structure
$result = $conn->query("DESCRIBE Assignments");
if ($result) {
    echo sprintf("%-20s %-30s %-5s %-5s %-10s %-10s\n", "Field", "Type", "Null", "Key", "Default", "Extra");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-30s %-5s %-5s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL', 
            $row['Extra']);
    }
} else {
    echo "Error describing table: " . $conn->error . "\n";
}
?> 