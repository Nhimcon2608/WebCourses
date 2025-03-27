<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "webcourses";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful!<br>";

// Check if tables exist
$result = $conn->query("SHOW TABLES");

if ($result->num_rows > 0) {
    echo "<h3>Tables in database:</h3>";
    echo "<ul>";
    while($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "No tables found in the database.";
}

$conn->close();
?> 