<?php
// Test file for user registration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'app/config/connect.php';

echo "<h1>User Registration Test</h1>";

// Check database connection
try {
    echo "<h2>Database Connection</h2>";
    echo "<p>Connected to database: " . $conn->query("SELECT DATABASE()")->fetchColumn() . "</p>";
    
    // Check users table
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result && $result->rowCount() > 0) {
        echo "<p>Users table exists</p>";
        
        // Get users table structure
        echo "<h3>Users Table Structure:</h3>";
        $columns = $conn->query("DESCRIBE users");
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count existing users
        $count = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>Total users: $count</p>";
    } else {
        echo "<p style='color:red'>Users table does not exist!</p>";
    }
    
    // Test user registration
    echo "<h2>Test User Registration</h2>";
    
    // Include User model
    if (file_exists('app/models/User.php')) {
        require_once 'app/models/User.php';
        
        $userModel = new User($conn);
        
        // Test data
        $username = "testuser_" . time();
        $email = "testuser_" . time() . "@example.com";
        $password = "password123";
        
        echo "<p>Attempting to register user: <br>";
        echo "Username: $username<br>";
        echo "Email: $email<br>";
        echo "Password: $password</p>";
        
        // Call register method
        $result = $userModel->register($username, $email, $password, 'student');
        
        echo "<h3>Registration Result:</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        // Check if user was actually created
        if (isset($result['success']) && $result['success']) {
            $user = $conn->query("SELECT * FROM users WHERE username = '$username'")->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                echo "<p style='color:green'>User was successfully created in the database!</p>";
                echo "<pre>";
                print_r($user);
                echo "</pre>";
            } else {
                echo "<p style='color:red'>Error: User was not found in the database despite successful registration result.</p>";
            }
        }
    } else {
        echo "<p style='color:red'>Error: User.php model not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?> 