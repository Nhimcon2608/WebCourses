<?php
// Database display script
// This script displays all tables and their structure from the online_courses database

// Database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'webcourses';

// Create connection without selecting a database
$conn = new mysqli($host, $user, $password);

// Set page title and add some basic styling
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #2980b9;
            margin-top: 30px;
            background-color: #f8f9fa;
            padding: 8px;
            border-left: 4px solid #3498db;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .table-container {
            margin-bottom: 40px;
        }
        .relationships {
            margin-top: 10px;
            color: #27ae60;
            font-style: italic;
        }
        .sample-data {
            margin-top: 20px;
        }
        .toggle-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        .toggle-btn:hover {
            background: #2980b9;
        }
        .log {
            background-color: #f8f9fa;
            border-left: 4px solid #e74c3c;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
        }
        .warning {
            color: #e67e22;
        }
        .success {
            color: #27ae60;
        }
        .error {
            color: #e74c3c;
        }
        .nav {
            margin-bottom: 20px;
        }
        .nav a {
            display: inline-block;
            margin-right: 15px;
            color: #3498db;
            text-decoration: none;
        }
        .nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Database Schema Setup & Viewer</h1>
    <div class="nav">
        <a href="database_tools.html">← Back to Database Tools</a>
        <a href="index.php">← Back to Main Site</a>
    </div>';

// Check connection
if ($conn->connect_error) {
    die("<p>Connection failed: " . $conn->connect_error . "</p></body></html>");
}

echo "<p>✅ Connected to MySQL server</p>";

// Check if reset parameter is present
$reset = isset($_GET['reset']) && $_GET['reset'] === 'true';

if ($reset) {
    // Drop database if it exists and reset
    $conn->query("DROP DATABASE IF EXISTS $database");
    echo "<p class='warning'>⚠️ Database '$database' has been dropped.</p>";
}

// Create the database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
echo "<p>✅ Database '$database' created or already exists</p>";

// Select the database
$conn->select_db($database);
echo "<p>✅ Selected database: <strong>$database</strong></p>";

// Import SQL schema from the SQL file
echo "<h2>Importing Database Schema</h2>";
echo "<div class='log'>";

// Path to the SQL file
$sqlFile = 'database/online_cources.sql';

if (!file_exists($sqlFile)) {
    die("<p>Error: SQL file not found at $sqlFile</p></div></body></html>");
}

// Read the SQL file
$sql = file_get_contents($sqlFile);

// Split SQL by semicolon
$commands = explode(';', $sql);

// Execute each command
foreach ($commands as $command) {
    $command = trim($command);
    if (empty($command)) continue;
    
    // Skip commands that are comments or just whitespace
    if (preg_match('/^--/', $command) || empty(trim($command))) {
        continue;
    }
    
    // Add a semicolon back
    $command .= ';';
    
    // Extract first few characters for display
    $displayCommand = substr($command, 0, 80) . (strlen($command) > 80 ? '...' : '');
    
    try {
        if ($conn->query($command)) {
            echo "<p class='success'>✅ Executed: " . htmlspecialchars($displayCommand) . "</p>";
        } else {
            echo "<p class='error'>❌ Error in command: " . htmlspecialchars($displayCommand) . "<br>Error: " . $conn->error . "</p>";
        }
    } catch (mysqli_sql_exception $e) {
        // Check if it's a "table already exists" error
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p class='warning'>⚠️ " . htmlspecialchars($e->getMessage()) . " - Skipping</p>";
        } else {
            echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

echo "</div>";

// Get all tables
$tables_result = $conn->query("SHOW TABLES");

if ($tables_result->num_rows > 0) {
    echo "<h2>Database Tables</h2>";
    echo "<p>The database contains " . $tables_result->num_rows . " tables:</p>";
    echo "<ul>";
    
    $tables = [];
    while($table_row = $tables_result->fetch_array()) {
        $table_name = $table_row[0];
        $tables[] = $table_name;
        echo "<li><a href='#table-{$table_name}'>{$table_name}</a></li>";
    }
    echo "</ul>";
    
    // Display each table structure
    foreach($tables as $table_name) {
        echo "<div class='table-container' id='table-{$table_name}'>";
        echo "<h2>Table: {$table_name}</h2>";
        
        // Get table structure
        $structure_result = $conn->query("DESCRIBE {$table_name}");
        
        if ($structure_result->num_rows > 0) {
            echo "<h3>Structure</h3>";
            echo "<table>";
            echo "<tr>
                <th>Field</th>
                <th>Type</th>
                <th>Null</th>
                <th>Key</th>
                <th>Default</th>
                <th>Extra</th>
            </tr>";
            
            while($structure_row = $structure_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $structure_row['Field'] . "</td>";
                echo "<td>" . $structure_row['Type'] . "</td>";
                echo "<td>" . $structure_row['Null'] . "</td>";
                echo "<td>" . $structure_row['Key'] . "</td>";
                echo "<td>" . ($structure_row['Default'] === NULL ? "NULL" : $structure_row['Default']) . "</td>";
                echo "<td>" . $structure_row['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Get foreign keys
            $foreign_keys_result = $conn->query("
                SELECT 
                    COLUMN_NAME, 
                    REFERENCED_TABLE_NAME, 
                    REFERENCED_COLUMN_NAME
                FROM 
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE 
                    TABLE_SCHEMA = '{$database}' AND
                    TABLE_NAME = '{$table_name}' AND
                    REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if ($foreign_keys_result->num_rows > 0) {
                echo "<div class='relationships'>";
                echo "<h3>Foreign Keys</h3>";
                echo "<ul>";
                
                while($fk_row = $foreign_keys_result->fetch_assoc()) {
                    echo "<li>" . 
                        $fk_row['COLUMN_NAME'] . " references " . 
                        $fk_row['REFERENCED_TABLE_NAME'] . "(" . $fk_row['REFERENCED_COLUMN_NAME'] . ")
                    </li>";
                }
                
                echo "</ul>";
                echo "</div>";
            }
            
            // Display sample data button and container
            echo "<div class='sample-data'>";
            echo "<button class='toggle-btn' onclick='toggleData(\"{$table_name}\")'>Show/Hide Sample Data</button>";
            echo "<div id='data-{$table_name}' style='display:none;'>";
            
            // Get sample data (limit to 5 rows)
            $data_result = $conn->query("SELECT * FROM {$table_name} LIMIT 5");
            
            if ($data_result && $data_result->num_rows > 0) {
                echo "<h3>Sample Data (up to 5 rows)</h3>";
                echo "<table>";
                
                // Table headers
                echo "<tr>";
                $fields = $data_result->fetch_fields();
                foreach ($fields as $field) {
                    echo "<th>" . $field->name . "</th>";
                }
                echo "</tr>";
                
                // Reset data pointer
                $data_result->data_seek(0);
                
                // Table data
                while($data_row = $data_result->fetch_assoc()) {
                    echo "<tr>";
                    foreach($data_row as $value) {
                        echo "<td>" . (($value === NULL) ? "NULL" : htmlspecialchars($value)) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No data in this table.</p>";
            }
            
            echo "</div>"; // Close data container
            echo "</div>"; // Close sample-data div
        } else {
            echo "<p>Unable to retrieve table structure.</p>";
        }
        
        echo "</div>"; // Close table-container
    }
} else {
    echo "<p>No tables found in the database.</p>";
}

// Add JavaScript for toggling data display
echo "
<script>
function toggleData(tableName) {
    var dataDiv = document.getElementById('data-' + tableName);
    if (dataDiv.style.display === 'none') {
        dataDiv.style.display = 'block';
    } else {
        dataDiv.style.display = 'none';
    }
}
</script>
";

// Close connection
$conn->close();

echo "</body></html>";
?> 