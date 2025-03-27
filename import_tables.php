<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'app/config/connect.php';

echo "Executing SQL to create tables...\n";

// Get SQL from file
$sql = file_get_contents('create_lesson_tables.sql');

// In PDO we need to execute statements one by one
// Split the SQL file into individual statements
$statements = explode(';', $sql);

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $conn->exec($statement);
        }
    }
    
    // Commit transaction
    $conn->commit();
    echo "Tables created successfully!\n";
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo "Error executing SQL: " . $e->getMessage() . "\n";
}

// Check if the lessons table exists
try {
    $result = $conn->query("SHOW TABLES LIKE 'lessons'");
    if ($result->rowCount() > 0) {
        echo "Lessons table exists\n";
    } else {
        echo "Lessons table does not exist\n";
    }
} catch (PDOException $e) {
    echo "Error checking lessons table: " . $e->getMessage() . "\n";
}

// Check if the lesson_materials table exists
try {
    $result = $conn->query("SHOW TABLES LIKE 'lesson_materials'");
    if ($result->rowCount() > 0) {
        echo "Lesson_materials table exists\n";
    } else {
        echo "Lesson_materials table does not exist\n";
    }
} catch (PDOException $e) {
    echo "Error checking lesson_materials table: " . $e->getMessage() . "\n";
}

// No need to close connection with PDO
// $conn is destroyed automatically when script ends
?> 