<?php
// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "webcourses";

// Connect to database
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get SQL file content
$sql_file = file_get_contents('modified_orders_tables.sql');

// Execute the SQL commands
if ($conn->multi_query($sql_file)) {
    echo "Orders tables created successfully.";
} else {
    echo "Error creating tables: " . $conn->error;
}

// Close the connection
$conn->close();
?> 