<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Đăng ký người dùng
    public function register($username, $email, $password, $role = 'student') {
        try {
            // Kiểm tra xem username hoặc email đã tồn tại chưa
            $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            if (!$stmt) {
                error_log("Prepare statement error");
                return ['success' => false, 'message' => 'Lỗi cơ sở dữ liệu khi chuẩn bị truy vấn.'];
            }
            
            $stmt->bindParam(1, $username, PDO::PARAM_STR);
            $stmt->bindParam(2, $email, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                error_log("Execute error: " . $stmt->errorInfo()[2]);
                return ['success' => false, 'message' => 'Lỗi khi thực thi truy vấn kiểm tra người dùng.'];
            }
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Tên đăng nhập hoặc email đã tồn tại.'];
            }

            // Mã hóa mật khẩu và thêm người dùng vào database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            if (!$stmt) {
                error_log("Prepare statement error for insert");
                return ['success' => false, 'message' => 'Lỗi cơ sở dữ liệu khi chuẩn bị thêm người dùng.'];
            }
            
            $stmt->bindParam(1, $username, PDO::PARAM_STR);
            $stmt->bindParam(2, $email, PDO::PARAM_STR);
            $stmt->bindParam(3, $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(4, $role, PDO::PARAM_STR);
            
            $success = $stmt->execute();
            if (!$success) {
                error_log("Insert error: " . $stmt->errorInfo()[2]);
                return ['success' => false, 'message' => 'Đăng ký thất bại. Lỗi: ' . $stmt->errorInfo()[2]];
            }
            
            error_log("User registered successfully: $username, $email, $role");
            return ['success' => true, 'message' => 'Đăng ký thành công!'];
        } catch (Exception $e) {
            error_log("Registration exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    // Đăng nhập
    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT user_id, username, password, role, is_locked FROM users WHERE username = ?");
            $stmt->bindParam(1, $username, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                if (password_verify($password, $user['password'])) {
                    return ['success' => true, 'data' => $user];
                }
            }
            return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    // Đặt lại mật khẩu
    public function resetPassword($user_id, $new_password) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bindParam(1, $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            return ['success' => $success, 'message' => $success ? 'Đặt lại mật khẩu thành công!' : 'Đặt lại mật khẩu thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    // Lấy thông tin người dùng theo ID
    public function getById($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT user_id, username, email, role, created_at FROM users WHERE user_id = ?");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }

    // Lấy tất cả người dùng (cho admin)
    public function getAllUsers($limit = 10, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
            $stmt->bindParam(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }

    /**
     * Khóa hoặc mở tài khoản người dùng
     * @param int $user_id ID người dùng
     * @param bool $is_locked Trạng thái khóa (true = khóa, false = mở)
     * @return array Kết quả (success, message)
     */
    public function toggleLockUser($user_id, $is_locked) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET is_locked = ? WHERE user_id = ?");
            $stmt->bindParam(1, $is_locked, PDO::PARAM_BOOL);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            $status = $is_locked ? 'khóa' : 'mở';
            $this->logAction($this->getCurrentAdminId(), 'lock_user', $user_id, "Đã $status tài khoản ID $user_id");
            $this->sendNotificationOnAction($user_id, $is_locked ? 'lock' : 'unlock');
            return ['success' => $success, 'message' => $success ? "Tài khoản đã được $status thành công!" : "Thao tác $status thất bại."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * Phân quyền cho người dùng
     * @param int $user_id ID người dùng
     * @param string $role Vai trò mới (student, instructor, admin)
     * @return array Kết quả (success, message)
     */
    public function changeRole($user_id, $role) {
        try {
            $valid_roles = ['student', 'instructor', 'admin'];
            if (!in_array($role, $valid_roles)) {
                return ['success' => false, 'message' => 'Vai trò không hợp lệ.'];
            }

            $stmt = $this->conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->bindParam(1, $role, PDO::PARAM_STR);
            $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            $this->logAction($this->getCurrentAdminId(), 'change_role', $user_id, "Đã thay đổi vai trò của ID $user_id thành $role");
            return ['success' => $success, 'message' => $success ? "Phân quyền thành công thành $role!" : 'Phân quyền thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }


    /**
     * Cập nhật lại rating trung bình của khóa học sau khi xóa đánh giá
     * @param int $review_id ID đánh giá vừa xóa
     * @return void
     */
    private function updateCourseRating($review_id) {
        $stmt = $this->conn->prepare("SELECT course_id FROM reviews WHERE review_id = ?");
        $stmt->bindParam(1, $review_id, PDO::PARAM_INT);
        $stmt->execute();
        $course_id = $stmt->fetch(PDO::FETCH_ASSOC)['course_id'];
        if ($course_id) {
            $courseModel = new Course($this->conn);
            $courseModel->updateRating($course_id);
        }
    }

    /**
     * Xóa tài khoản người dùng (bao gồm lịch sử liên quan)
     * @param int $user_id ID người dùng
     * @return array Kết quả (success, message)
     */
    public function deleteUser($user_id) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("DELETE FROM enrollments WHERE user_id = ?");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM reviews WHERE user_id = ?");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM comments WHERE user_id = ?");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM discussion WHERE user_id = ?");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM chatbots WHERE user_id = ?");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $success = $stmt->execute();

            $this->logAction($this->getCurrentAdminId(), 'delete_user', $user_id, "Đã xóa tài khoản ID $user_id");
            $this->conn->commit();
            return ['success' => $success, 'message' => $success ? 'Tài khoản đã được xóa!' : 'Xóa tài khoản thất bại.'];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * Gửi thông báo khi khóa/mở tài khoản
     * @param int $user_id ID người dùng
     * @param string $action Hành động (lock/unlock)
     * @return array Kết quả (success, message)
     */
    public function sendNotificationOnAction($user_id, $action) {
        try {
            $notificationModel = new Notification($this->conn); // Giả định có Model Notification
            $message = ($action === 'lock') ? 'Tài khoản của bạn đã bị khóa.' : 'Tài khoản của bạn đã được mở.';
            $notificationModel->create($user_id, 'Thông báo hệ thống', $message, 'warning');
            return ['success' => true, 'message' => 'Thông báo đã được gửi!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi gửi thông báo: ' . $e->getMessage()];
        }
    }

    /**
     * Lấy lịch sử hành động của admin
     * @param int $limit Số bản ghi tối đa
     * @param int $offset Bắt đầu từ bản ghi nào
     * @return array Danh sách lịch sử hành động
     */
    public function getUserLogs($limit = 10, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("SELECT log_id, user_id, action, target_user_id, details, created_at FROM user_logs ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
            $stmt->bindParam(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $logs;
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }


    /**
     * Ghi log hành động của admin
     * @param int $admin_id ID admin thực hiện hành động
     * @param string $action Hành động
     * @param int|null $target_user_id ID người dùng bị ảnh hưởng
     * @param string $details Chi tiết hành động
     * @return void
     */
    public function logAction($admin_id, $action, $target_user_id, $details) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO user_logs (user_id, action, target_user_id, details) VALUES (?, ?, ?, ?)");
            $stmt->bindParam(1, $admin_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $action, PDO::PARAM_STR);
            $stmt->bindParam(3, $target_user_id, PDO::PARAM_INT);
            $stmt->bindParam(4, $details, PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Lỗi ghi log: " . $e->getMessage());
        }
    }

    /**
     * Lấy ID của admin hiện tại (giả định từ session)
     * @return int|null ID admin hoặc null nếu không xác định
     */
    public function getCurrentAdminId() {
        $admin_id = $_SESSION['user_id'] ?? null;
        if (!$admin_id || $this->getById($admin_id)['role'] !== 'admin') {
            throw new Exception("Không có quyền admin.");
        }
        return $admin_id;
    }

    public function getUserById($userId) {
        $stmt = $this->conn->prepare("
            SELECT id, name, email, phone
            FROM users
            WHERE id = :user_id
        ");
        
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateUser($userId, $data) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET name = :name,
                    email = :email,
                    phone = :phone
                WHERE id = :user_id
            ");
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => $data['phone']
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}