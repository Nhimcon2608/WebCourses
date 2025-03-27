<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 2));
}
include ROOT_DIR . '/app/config/connect.php';

// Hàm giả định cho CSRF token
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Khởi tạo các Controller
require_once ROOT_DIR . '/app/controllers/ContactController.php';
require_once ROOT_DIR . '/app/controllers/AuthController.php';
require_once ROOT_DIR . '/app/controllers/ReviewController.php';
require_once ROOT_DIR . '/app/controllers/ChatbotController.php';
require_once ROOT_DIR . '/app/controllers/AdminController.php';
require_once ROOT_DIR . '/app/controllers/CategoryController.php';
require_once ROOT_DIR . '/app/controllers/CourseController.php';
require_once ROOT_DIR . '/app/controllers/LessonController.php';
require_once ROOT_DIR . '/app/controllers/AssignmentController.php';
require_once ROOT_DIR . '/app/controllers/QuizController.php';
require_once ROOT_DIR . '/app/controllers/QuizQuestionController.php';
require_once ROOT_DIR . '/app/controllers/DiscussionController.php';
require_once ROOT_DIR . '/app/controllers/NotificationController.php';
require_once ROOT_DIR . '/app/controllers/EnrollmentController.php';

$contactController = new ContactController($conn);
$authController = new AuthController($conn);
$reviewController = new ReviewController($conn);
$chatbotController = new ChatbotController($conn);
$adminController = new AdminController($conn);
$categoryController = new CategoryController($conn);
$courseController = new CourseController($conn);
$lessonController = new LessonController($conn);
$assignmentController = new AssignmentController($conn);
$quizController = new QuizController($conn);
$quizQuestionController = new QuizQuestionController($conn);
$discussionController = new DiscussionController($conn);
$notificationController = new NotificationController($conn);
$enrollmentController = new EnrollmentController($conn);

// Định tuyến
if (isset($_GET['controller']) && isset($_GET['action'])) {
    $controller = $_GET['controller'];
    $action = $_GET['action'];
    $controllerClass = ucfirst($controller) . 'Controller';

    if (class_exists($controllerClass)) {
        $controllerObj = new $controllerClass($conn);
        if (method_exists($controllerObj, $action)) {
            $controllerObj->$action();
        } else {
            header("Location: " . BASE_URL . "app/views/product/home.php?error=InvalidAction");
        }
    } else {
        header("Location: " . BASE_URL . "app/views/product/home.php?error=InvalidController");
    }
}

class Router {
    private $routes = [
        'GET' => [],
        'POST' => []
    ];

    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }

    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove base path from URI
        $basePath = parse_url(BASE_URL, PHP_URL_PATH);
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Remove leading/trailing slashes
        $uri = trim($uri, '/');
        
        // If URI is empty, treat as root
        if (empty($uri)) {
            $uri = '/';
        } else {
            $uri = '/' . $uri;
        }

        // Check if route exists
        if (isset($this->routes[$method][$uri])) {
            $callback = $this->routes[$method][$uri];
            call_user_func($callback);
            return;
        }

        // If no route found, check if it's a file in views/product
        $viewPath = ROOT_DIR . '/app/views/product/' . trim($uri, '/');
        if (file_exists($viewPath)) {
            require_once $viewPath;
            return;
        }

        // No route found
        header("HTTP/1.0 404 Not Found");
        require_once ROOT_DIR . '/app/views/product/error.php';
    }
}