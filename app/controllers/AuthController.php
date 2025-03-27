<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 2));
}

require_once ROOT_DIR . '/app/Router.php';
require_once ROOT_DIR . '/app/models/User.php';
require_once ROOT_DIR . '/app/models/Notification.php';
require_once ROOT_DIR . '/src/PHPMailer.php';
require_once ROOT_DIR . '/src/Exception.php';
require_once ROOT_DIR . '/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController {
    private $conn;
    private $userModel;
    private $notificationModel;

    public function __construct($db) {
        $this->conn = $db;
        $this->userModel = new User($db);
        $this->notificationModel = new Notification($db);
    }

    public function index() {
        $this->redirect('home.php');
    }

    // Xử lý cho route /auth/login
    public function loginPage() {
        // Chuyển hướng đến trang home
        $this->redirect('home.php');
    }

    public function register() {
        // Ghi log tất cả dữ liệu POST
        error_log("Register method called with data: " . print_r($_SERVER, true));
        error_log("POST data: " . print_r($_POST, true));
        error_log("Register button exists: " . (isset($_POST['register']) ? 'Yes' : 'No'));
        
        // Kiểm tra phương thức request và tham số register
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            error_log("Register method exited: NOT a POST request");
            return;
        }
        
        if (!isset($_POST['register'])) {
            error_log("Register method exited: 'register' parameter missing");
            return;
        }

        try {
            // Kiểm tra CSRF token
            if (!isset($_POST['csrf_token'])) {
                error_log("Register failed: CSRF token missing");
                $_SESSION['registerError'] = "Yêu cầu không hợp lệ (CSRF token missing).";
                $this->redirect('home.php');
                return;
            }
            
            if (!verifyCsrfToken($_POST['csrf_token'])) {
                error_log("Register failed: Invalid CSRF token");
                $_SESSION['registerError'] = "Yêu cầu không hợp lệ (Invalid CSRF token).";
                $this->redirect('home.php');
                return;
            }

            // Lấy và kiểm tra dữ liệu đầu vào
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $role = isset($_POST['role']) ? $_POST['role'] : 'student';

            // Ghi log thông tin đăng ký
            error_log("Register data - Username: [$username], Email: [$email], Role: [$role], Password length: " . strlen($password));

            // Kiểm tra thông tin đầu vào
            if (empty($username)) {
                error_log("Register failed: Empty username");
                $_SESSION['registerError'] = "Vui lòng nhập tên đăng nhập.";
                $this->redirect('home.php');
                return;
            }
            
            if (empty($email)) {
                error_log("Register failed: Empty email");
                $_SESSION['registerError'] = "Vui lòng nhập địa chỉ email.";
                $this->redirect('home.php');
                return;
            }
            
            if (empty($password)) {
                error_log("Register failed: Empty password");
                $_SESSION['registerError'] = "Vui lòng nhập mật khẩu.";
                $this->redirect('home.php');
                return;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("Register failed: Invalid email format: $email");
                $_SESSION['registerError'] = "Email không hợp lệ.";
                $this->redirect('home.php');
                return;
            }
            
            if (strlen($password) < 8) {
                error_log("Register failed: Password too short: " . strlen($password) . " characters");
                $_SESSION['registerError'] = "Mật khẩu phải có ít nhất 8 ký tự.";
                $this->redirect('home.php');
                return;
            }

            // Thực hiện đăng ký
            $result = $this->userModel->register($username, $email, $password, $role);
            error_log("Register result: " . print_r($result, true));
            
            if ($result['success']) {
                $_SESSION['registerSuccess'] = $result['message'];
                
                // Thử tạo thông báo cho admin nếu có
                try {
                    $adminUser = $this->conn->query("SELECT user_id FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    if ($adminUser) {
                        $this->notificationModel->create($adminUser['user_id'], null, 'Người dùng mới', "Người dùng $username ($email) vừa đăng ký với vai trò $role.");
                    }
                } catch (Exception $e) {
                    // Bỏ qua lỗi tạo thông báo, vẫn cho phép đăng ký thành công
                    error_log("Lỗi khi tạo thông báo: " . $e->getMessage());
                }
                
                // Tự động đăng nhập người dùng sau khi đăng ký thành công
                $user_id = $this->conn->insert_id;
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['loginSuccess'] = "Đăng ký và đăng nhập thành công!";
                
                // Chuyển hướng đến trang chủ dựa trên vai trò người dùng
                error_log("Register successful, redirecting to role-based dashboard");
                $this->redirectBasedOnRole($role);
                return;
            } else {
                error_log("Register failed: " . $result['message']);
                $_SESSION['registerError'] = $result['message'];
                $this->redirect('home.php');
                return;
            }
        } catch (Exception $e) {
            error_log("Register exception: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['registerError'] = "Có lỗi xảy ra. Vui lòng thử lại sau.";
            $this->redirect('home.php');
            return;
        }
    }

    public function login() {
        // Nếu là GET request, hiển thị trang đăng nhập
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->loginPage();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['login'])) {
            // Nếu không phải request POST, chuyển hướng đến trang đăng nhập
            $this->redirect('home.php');
            return;
        }

        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            $_SESSION['loginError'] = "Yêu cầu không hợp lệ.";
            $this->redirect('home.php');
        }

        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        error_log("Attempting login for user: $username");
        
        $result = $this->userModel->login($username, $password);
        if ($result['success']) {
            $user = $result['data'];
            if ($user['is_locked']) {
                $_SESSION['loginError'] = "Tài khoản của bạn đã bị khóa. Vui lòng liên hệ admin.";
                $this->redirect('home.php');
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Tạo thông báo tùy chỉnh theo vai trò
                if ($user['role'] === 'instructor') {
                    $_SESSION['loginSuccess'] = "Chào mừng giảng viên {$user['username']} đã đăng nhập thành công!";
                } else {
                    $_SESSION['loginSuccess'] = "Đăng nhập thành công!";
                }
                
                error_log("Login successful for user: $username with role: {$user['role']}");
                $this->redirectBasedOnRole($user['role']);
            }
        } else {
            error_log("Login failed for user: $username - {$result['message']}");
            $_SESSION['loginError'] = $result['message'];
            $this->redirect('home.php');
        }
    }

    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['forgot_password'])) {
            return;
        }

        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            $_SESSION['forgotError'] = "Yêu cầu không hợp lệ.";
            $this->redirect('home.php');
        }

        $email = trim($_POST['forgot_email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['forgotError'] = "Email không hợp lệ.";
            $this->redirect('home.php');
            return;
        }

        $stmt = $this->conn->prepare("SELECT user_id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $verification_code = sprintf("%05d", rand(0, 99999));
            $_SESSION['forgot_user_id'] = $user['user_id'];
            $_SESSION['verification_code'] = $verification_code;
            $_SESSION['reset_email'] = $email;
            $_SESSION['code_expiry'] = time() + 300; // Tăng thời gian hết hạn lên 5 phút

            $result = $this->sendVerificationEmail($email, $verification_code);
            $_SESSION['forgot' . ($result['success'] ? 'Success' : 'Error')] = $result['message'];
        } else {
            $_SESSION['forgotError'] = "Email không tồn tại trong hệ thống.";
            $this->redirect('home.php');
            return;
        }
        // Chuyển hướng không kèm tham số
        $this->redirect('home.php');
    }

    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['reset-password'])) {
            return;
        }

        if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
            $_SESSION['resetError'] = "Yêu cầu không hợp lệ.";
            $this->redirect('home.php');
        }

        $code = trim($_POST['verification_code']);
        $new_password = trim($_POST['new_password']);

        if (!isset($_SESSION['code_expiry']) || time() > $_SESSION['code_expiry']) {
            $_SESSION['resetError'] = "Mã xác nhận đã hết hạn. Vui lòng yêu cầu mã mới.";
            $this->cleanupSession();
        } elseif ($code !== $_SESSION['verification_code']) {
            $_SESSION['resetError'] = "Mã xác nhận không đúng.";
        } elseif (strlen($new_password) < 6) {
            $_SESSION['resetError'] = "Mật khẩu mới phải ít nhất 6 ký tự.";
        } else {
            $result = $this->userModel->resetPassword($_SESSION['forgot_user_id'], $new_password);
            $_SESSION['reset' . ($result['success'] ? 'Success' : 'Error')] = $result['message'];
            if ($result['success']) {
                $this->cleanupSession();
                // Redirect to login form after successful password reset
                $this->redirect('home.php');
                return;
            }
        }
        // Chuyển hướng không kèm tham số
        $this->redirect('home.php');
    }

    public function logout() {
        // Đảm bảo session đã được khởi động
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Xóa tất cả dữ liệu session
        $_SESSION = array();

        // Xóa cookie session nếu có
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Hủy session
        session_destroy();

        // Chuyển hướng về trang chủ
        $this->redirect('home.php');
    }

    private function sendVerificationEmail($email, $code) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'hoctap435@gmail.com';
            $mail->Password = 'vznk pkkp iety fzkm';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('hoctap435@gmail.com', 'Học Tập Trực Tuyến');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Mã xác nhận khôi phục mật khẩu";
            $mail->Body = "<h2>Mã xác nhận</h2><p>Mã của bạn là: <strong>{$code}</strong></p><p>Hiệu lực: 5 phút.</p>";
            $mail->AltBody = "Mã xác nhận: {$code}\nHiệu lực: 5 phút.";
            $mail->send();
            return ['success' => true, 'message' => "Mã xác nhận đã được gửi đến email của bạn."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Không thể gửi email. Lỗi: {$mail->ErrorInfo}"];
        }
    }

    private function cleanupSession() {
        unset($_SESSION['forgot_user_id'], $_SESSION['verification_code'], $_SESSION['reset_email'], $_SESSION['code_expiry']);
    }

    private function redirect($path) {
        // Theo dõi số lần chuyển hướng để phát hiện vòng lặp
        $_SESSION['redirect_count'] = ($_SESSION['redirect_count'] ?? 0) + 1;

        if ($_SESSION['redirect_count'] > 5) {
            error_log("Phát hiện vòng lặp chuyển hướng. Dừng chuyển hướng đến: " . BASE_URL . "$path");
            die("Lỗi: Quá nhiều lần chuyển hướng. Vui lòng xóa cookie trình duyệt và thử lại.");
        }

        // Loại bỏ các query parameter không cần thiết
        $cleanPath = preg_replace('/\?.*/', '', $path);
        
        // Nếu path chứa thư mục app/views/product, sử dụng đúng đường dẫn
        if (strpos($cleanPath, 'app/views/product/') === 0) {
            $cleanPath = $cleanPath;
        } else if (strpos($cleanPath, 'app/views/') === 0) {
            $cleanPath = $cleanPath;
        } else {
            $cleanPath = "app/views/product/$cleanPath";
        }
        
        error_log("Chuyển hướng đến: " . BASE_URL . $cleanPath);
        header("Location: " . BASE_URL . $cleanPath);
        exit();
    }

    private function redirectBasedOnRole($role) {
        error_log("Redirecting user based on role: $role");
        
        switch ($role) {
            case 'student':
                $this->redirect('student_dashboard.php');
                break;
            case 'instructor':
                // Thêm thông báo chào mừng giảng viên
                $_SESSION['loginSuccess'] = "Chào mừng giảng viên đã đăng nhập thành công!";
                $this->redirect('instructor_dashboard.php');
                break;
            case 'admin':
                $this->redirect('admin_dashboard.php');
                break;
            default:
                // Nếu vai trò không hợp lệ, chuyển hướng về trang chủ
                $_SESSION['loginError'] = "Vai trò người dùng không hợp lệ. Vui lòng liên hệ quản trị viên.";
                $this->redirect('home.php');
        }
    }
}