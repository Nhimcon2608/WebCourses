<?php
// Script to create the AssignmentSubmissions table
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';

try {
    // Check if table exists
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'AssignmentSubmissions'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $tableExists = true;
        echo "<p>Table AssignmentSubmissions already exists.</p>";
    }

    if (!$tableExists) {
        // Create the table
        $sql = "CREATE TABLE AssignmentSubmissions (
            submission_id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT NOT NULL,
            user_id INT NOT NULL,
            submission_text TEXT,
            file_path VARCHAR(255),
            submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            grade DECIMAL(5,2) DEFAULT NULL,
            feedback TEXT
        )";

        if ($conn->query($sql) === TRUE) {
            echo "<p>Table AssignmentSubmissions created successfully</p>";
        } else {
            echo "<p>Error creating table: " . $conn->error . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<div style="margin: 30px; text-align: center;">
    <h1>Database Table Setup</h1>
    <p>This script has attempted to create the AssignmentSubmissions table.</p>
    <p><a href="<?php echo BASE_URL; ?>app/views/product/assignments.php">Return to Assignments Page</a></p>
</div> 