<?php
define('BASE_URL', '/WebCourses/');
// Direct database connection to avoid include path issues
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'online_courses';

// Connect to database
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if instructor exists or create one
$adminResult = $conn->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1");
$instructorId = 0;

if ($adminResult && $adminResult->num_rows > 0) {
    $adminRow = $adminResult->fetch_assoc();
    $instructorId = $adminRow['user_id'];
} else {
    // Create a default admin/instructor if none exists
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, email, password, role) 
                 VALUES ('admin', 'admin@example.com', '$hashedPassword', 'admin')");
    $instructorId = $conn->insert_id;
}

// Function to add a course if it doesn't exist
function addCourse($conn, $instructorId, $title, $description, $category_id, $level, $price, $image) {
    // Check if course already exists
    $stmt = $conn->prepare("SELECT course_id FROM courses WHERE title = ?");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['course_id']; // Course already exists
    }
    
    // Course doesn't exist, add it
    $stmt = $conn->prepare("INSERT INTO courses (instructor_id, category_id, title, description, level, price, image) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssds", $instructorId, $category_id, $title, $description, $level, $price, $image);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    } else {
        return false;
    }
}

// Check if we have categories, add some if not
$categoryResult = $conn->query("SELECT category_id FROM categories LIMIT 1");
$categoryIds = [];

if ($categoryResult && $categoryResult->num_rows == 0) {
    // Add categories
    $categories = [
        ["Web Development", "Courses related to web development technologies"],
        ["Programming", "General programming and coding courses"],
        ["Database", "Database design and management courses"],
        ["Mobile Development", "Mobile app development courses"],
        ["AI & Machine Learning", "Artificial intelligence and machine learning courses"]
    ];
    
    foreach ($categories as $category) {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $category[0], $category[1]);
        $stmt->execute();
        $categoryIds[$category[0]] = $conn->insert_id;
    }
} else {
    // Load existing categories
    $categoryResult = $conn->query("SELECT category_id, name FROM categories");
    while ($row = $categoryResult->fetch_assoc()) {
        $categoryIds[$row['name']] = $row['category_id'];
    }
    
    // Ensure we have the basic categories
    $requiredCategories = ["Web Development", "Programming", "Database", "Mobile Development", "AI & Machine Learning"];
    foreach ($requiredCategories as $catName) {
        if (!isset($categoryIds[$catName])) {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $description = "Courses related to " . $catName;
            $stmt->bind_param("ss", $catName, $description);
            $stmt->execute();
            $categoryIds[$catName] = $conn->insert_id;
        }
    }
}

// Sample courses to add
$sampleCourses = [
    [
        "title" => "HTML & CSS Fundamentals",
        "description" => "Learn the basics of HTML and CSS to build your first website. This course covers everything from basic tags to responsive layouts.",
        "category" => "Web Development",
        "level" => "Beginner",
        "price" => 0.00, // Free course
        "image" => "html_css.jpg"
    ],
    [
        "title" => "JavaScript for Beginners",
        "description" => "Start your journey with JavaScript, the language of the web. Learn variables, functions, DOM manipulation and more.",
        "category" => "Web Development", 
        "level" => "Beginner",
        "price" => 19.99,
        "image" => "javascript.jpg"
    ],
    [
        "title" => "Introduction to PHP",
        "description" => "Learn server-side programming with PHP. Build dynamic websites and connect to databases.",
        "category" => "Web Development",
        "level" => "Intermediate",
        "price" => 29.99,
        "image" => "php.jpg"
    ],
    [
        "title" => "C Programming Basics",
        "description" => "Start with the fundamentals of C programming. Learn syntax, data types, control structures, functions, and basic memory management.",
        "category" => "Programming",
        "level" => "Beginner",
        "price" => 24.99,
        "image" => "c_programming.jpg"
    ],
    [
        "title" => "Advanced C Programming",
        "description" => "Take your C skills to the next level with pointers, dynamic memory allocation, file I/O, and data structures.",
        "category" => "Programming",
        "level" => "Advanced",
        "price" => 39.99,
        "image" => "advanced_c.jpg"
    ],
    [
        "title" => "SQL Database Design",
        "description" => "Learn to design and query relational databases using SQL. Covers database normalization, joins, indexes, and optimization.",
        "category" => "Database",
        "level" => "Intermediate",
        "price" => 34.99,
        "image" => "sql_database.jpg"
    ],
    [
        "title" => "Python Programming",
        "description" => "Learn Python programming from scratch. Perfect for beginners who want to start coding with a versatile language.",
        "category" => "Programming",
        "level" => "Beginner",
        "price" => 19.99,
        "image" => "python.jpg"
    ],
    [
        "title" => "Mobile App Development with React Native",
        "description" => "Build cross-platform mobile apps that work on both iOS and Android using JavaScript and React Native.",
        "category" => "Mobile Development",
        "level" => "Intermediate",
        "price" => 49.99,
        "image" => "react_native.jpg"
    ]
];

// Add the courses
$successCount = 0;
$errors = [];

foreach ($sampleCourses as $course) {
    $categoryId = isset($categoryIds[$course["category"]]) ? $categoryIds[$course["category"]] : 1;
    
    $courseId = addCourse(
        $conn, 
        $instructorId, 
        $course["title"], 
        $course["description"], 
        $categoryId, 
        $course["level"], 
        $course["price"], 
        $course["image"]
    );
    
    if ($courseId) {
        $successCount++;
    } else {
        $errors[] = "Failed to add course: " . $course["title"];
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sample Courses - WebCourses</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sample Courses Setup</h1>
        
        <div class="result <?php echo ($successCount > 0) ? 'success' : 'error'; ?>">
            <p><strong>Result:</strong> Added <?php echo $successCount; ?> courses to the database.</p>
            
            <?php if (!empty($errors)): ?>
                <p><strong>Errors:</strong></p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div>
            <a href="<?php echo BASE_URL; ?>app/views/product/add_assignments_direct.php" class="btn">Add Sample Assignments</a>
            <a href="<?php echo BASE_URL; ?>app/views/product/assignments.php" class="btn">Go to Assignments</a>
            <a href="<?php echo BASE_URL; ?>app/views/product/student_dashboard.php" class="btn">Go to Dashboard</a>
        </div>
    </div>
</body>
</html> 