<?php
// Debug information (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the URL contains raw PHP code and redirect to correct URL
if (strpos($_SERVER['REQUEST_URI'], '%3Cphp') !== false || strpos($_SERVER['REQUEST_URI'], '<?php') !== false) {
    // Extract the correct part of the URL
    $correct_url = BASE_URL . 'app/views/product/student_list.php';
    header("Location: " . $correct_url);
    exit();
}

// Don't redefine constants if already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WebCourses/');
}
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 3));
}

// Database connection with error handling
try {
    include ROOT_DIR . '/app/config/connect.php';
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    // Log error for debugging
    error_log("Database connection error: " . $e->getMessage());
    echo "<!-- DB Error: " . $e->getMessage() . " -->\n";
}

// Check if session is already started before starting it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For testing purposes, set session variables if they don't exist
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default instructor ID for testing
    $_SESSION['username'] = 'cuonghoakim123';
    $_SESSION['role'] = 'instructor';
}

// Reset redirect count to prevent redirect loops
$_SESSION['redirect_count'] = 0;

// Access control with proper error handling
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    // Set error message in session
    $_SESSION['error'] = "Bạn không có quyền truy cập trang này. Vui lòng đăng nhập với tài khoản giảng viên.";
    // Redirect to home page
    header("Location: " . BASE_URL . "app/views/product/home.php");
    exit();
}

// Thiết lập tiêu đề trang
$page_title = 'Danh Sách Học Viên';

// CSS bổ sung cho trang này
$additional_css = '
    /* Student cards styling */
    .student-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }
    
    .student-card {
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 20px;
        transition: transform 0.2s ease;
    }
    
    .student-name {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #333;
    }
    
    .student-info {
        margin-bottom: 15px;
    }
    
    .student-info p {
        margin: 5px 0;
        color: #555;
        font-size: 14px;
    }
    
    .student-info strong {
        color: #333;
        font-weight: normal;
    }
    
    .student-actions {
        display: flex;
        gap: 5px;
    }
    
    .btn {
        padding: 3px 8px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 13px;
    }
    
    .btn-view {
        background-color: #3498db;
        color: white;
    }
    
    .btn-edit {
        background-color: #2ecc71;
        color: white;
    }
    
    .btn-delete {
        background-color: #e74c3c;
        color: white;
    }

    /* Status labels */
    .status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: normal;
    }
    
    .status-active {
        background-color: #e1f7e1;
        color: #27ae60;
    }
    
    .status-completed {
        background-color: #e8f7ff;
        color: #3498db;
    }
    
    /* Dark mode adaptations */
    body.dark-mode .student-card {
        background-color: #34495e;
        color: #ecf0f1;
    }
    
    body.dark-mode .student-name {
        color: #ecf0f1;
    }
    
    body.dark-mode .student-info p {
        color: #bdc3c7;
    }
    
    body.dark-mode .student-info strong {
        color: #ecf0f1;
    }
';

// Lấy danh sách học viên từ cơ sở dữ liệu
// Trong thực tế, chúng ta sẽ lấy dữ liệu từ bảng users hoặc students
// Hiện tại, chúng ta sẽ sử dụng dữ liệu mẫu

// Sample data for demonstration
$students = [
    [
        'id' => 1,
        'name' => 'Nguyễn Văn A',
        'email' => 'nguyenvana@example.com',
        'course' => 'Lập Trình Web',
        'status' => 'Đang Học'
    ],
    [
        'id' => 2,
        'name' => 'Trần Thị B',
        'email' => 'tranthib@example.com',
        'course' => 'Thiết Kế Đồ Họa',
        'status' => 'Hoàn Thành'
    ],
    [
        'id' => 3,
        'name' => 'Lê Văn C',
        'email' => 'levanc@example.com',
        'course' => 'Lập Trình Python',
        'status' => 'Đang Học'
    ],
];

// Include header
include ROOT_DIR . '/app/includes/instructor_header.php';
?>

<h1 class="page-title">Danh Sách Học Viên</h1>

<div class="student-grid">
    <?php foreach ($students as $student): ?>
    <div class="student-card">
        <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
        <div class="student-info">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Khóa Học:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
            <p><strong>Trạng Thái:</strong> 
                <span class="status <?php echo $student['status'] === 'Đang Học' ? 'status-active' : 'status-completed'; ?>">
                    <?php echo htmlspecialchars($student['status']); ?>
                </span>
            </p>
        </div>
        <div class="student-actions">
            <a href="#" class="btn btn-view">Xem</a>
            <a href="#" class="btn btn-edit">Sửa</a>
            <a href="#" class="btn btn-delete">Xóa</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
// JavaScript tùy chỉnh cho trang này
$page_script = "
    // Xử lý các sự kiện cụ thể cho trang danh sách học viên
    const studentCards = document.querySelectorAll('.student-card');
    
    studentCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
        });
    });
";

// Include footer
include ROOT_DIR . '/app/includes/instructor_footer.php';
?> 