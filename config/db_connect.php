<?php
/**
 * Database Connection Configuration
 * 
 * This file establishes a connection to the MySQL database
 */

// Database credentials
$db_host = 'localhost';
$db_user = 'root';  // For XAMPP default user
$db_password = '';  // For XAMPP default password
$db_name = 'online_courses';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Error<br><br>".
        "Unknown database '" . $db_name . "'<br><br>" .
        "Please make sure the MySQL service is running in XAMPP Control Panel and the \"" . $db_name . "\" database exists.");
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Uncomment for debugging
// echo "Database connection successful!";
?> 