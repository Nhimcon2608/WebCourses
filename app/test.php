<?php
// Load configuration
require_once dirname(__DIR__) . '/app/config/config.php';

// Connect to database
require_once ROOT_DIR . '/app/config/connect.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Test query using PDO
    $stmt = $conn->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['test'] == 1) {
        echo "<p style='color:green'>✓ PDO connection successful!</p>";
    } else {
        echo "<p style='color:red'>✗ PDO query failed</p>";
    }
    
    // Test categories
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM categories");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total categories in database: " . $result['total'] . "</p>";
    
    // Test courses
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total courses in database: " . $result['total'] . "</p>";
    
    // Check if Reviews table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'reviews'");
    $reviewsExist = $stmt && $stmt->rowCount() > 0;
    
    if ($reviewsExist) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total reviews in database: " . $result['total'] . "</p>";
    } else {
        echo "<p>Reviews table does not exist yet</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='" . BASE_URL . "app/views/product/home.php'>Go to Homepage</a></p>"; 