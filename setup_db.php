<?php
/**
 * Database Setup Script
 * 
 * This script helps create the online_courses database and required tables
 */

// Display header
echo "<h1>Database Setup for Online Courses</h1>";

// Database credentials
$db_host = 'localhost';
$db_user = 'root';  // For XAMPP default user
$db_password = '';  // For XAMPP default password

try {
    // Connect to MySQL server without specifying a database
    $conn = new mysqli($db_host, $db_user, $db_password);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>✅ Connected to MySQL server successfully.</p>";
    
    // Read SQL file content
    $sql_file = 'database/setup_database.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL commands
    $commands = explode(';', $sql_content);
    
    echo "<p>🔄 Running SQL commands...</p>";
    echo "<ul>";
    
    // Execute each command
    foreach ($commands as $command) {
        // Skip empty commands
        $command = trim($command);
        if (empty($command)) continue;
        
        // Execute command
        if ($conn->query($command . ';')) {
            // Extract first line of the command for display
            $first_line = strtok($command, "\n");
            $first_line = str_replace('-- ', '', $first_line);
            echo "<li>✅ " . htmlspecialchars($first_line) . "...</li>";
        } else {
            echo "<li>❌ Error executing command: " . htmlspecialchars($command) . "<br>Error: " . $conn->error . "</li>";
        }
    }
    
    echo "</ul>";
    echo "<p>✅ Database setup completed successfully!</p>";
    
    // Update config file
    $config_file = 'config/db_connect.php';
    
    if (file_exists($config_file)) {
        echo "<p>✅ Database configuration file exists.</p>";
    } else {
        // Create config directory if it doesn't exist
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        // Create config file
        $config_content = '<?php
/**
 * Database Connection Configuration
 * 
 * This file establishes a connection to the MySQL database
 */

// Database credentials
$db_host = \'localhost\';
$db_user = \'root\';  // For XAMPP default user
$db_password = \'\';  // For XAMPP default password
$db_name = \'online_courses\';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Error<br><br>".
        "Unknown database \'" . $db_name . "\'<br><br>" .
        "Please make sure the MySQL service is running in XAMPP Control Panel and the \"" . $db_name . "\" database exists.");
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Uncomment for debugging
// echo "Database connection successful!";
?>';
        
        // Save config file
        file_put_contents($config_file, $config_content);
        echo "<p>✅ Created database configuration file.</p>";
    }
    
    echo "<p>🎉 Setup complete! You can now access your website.</p>";
    echo "<p>➡️ <a href='app/views/product/assignments.php'>Go to Assignments Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure MySQL server is running in XAMPP Control Panel.</p>";
}
?> 