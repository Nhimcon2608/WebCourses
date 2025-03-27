<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 2));
}

require_once ROOT_DIR . '/app/models/Review.php';
require_once ROOT_DIR . '/app/models/Course.php';
require_once ROOT_DIR . '/app/models/Notification.php';

class ReviewController {
    private $db;
    private $reviewModel;
    private $courseModel;
    private $notificationModel;

    public function __construct($db) {
        $this->db = $db;
        $this->reviewModel = new Review($db);
        $this->courseModel = new Course($db);
        $this->notificationModel = new Notification($db);
    }

    public function getReviews() {
        // Lấy tất cả các reviews mà không cần kiểm tra bảng
        // Phương thức getAllReviews() sẽ trả về mảng trống nếu bảng không tồn tại
        return $this->reviewModel->getAllReviews();
    }

    public function submitReview() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['reviewError'] = "Bạn phải đăng nhập để gửi đánh giá";
            header("Location: " . BASE_URL . "home#reviews");
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit'])) {
            // Kiểm tra CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['reviewError'] = "Lỗi xác thực, vui lòng thử lại";
                header("Location: " . BASE_URL . "home#reviews");
                exit();
            }
            
            $comment = trim($_POST['comment'] ?? '');
            $rating = intval($_POST['rating'] ?? 5);
            
            if (empty($comment)) {
                $_SESSION['reviewError'] = "Vui lòng nhập nội dung bình luận";
                header("Location: " . BASE_URL . "home#reviews");
                exit();
            }
            
            if ($rating < 1 || $rating > 5) {
                $rating = 5; // Default to 5 stars if invalid
            }
            
            // Lưu review vào database
            $userId = $_SESSION['user_id'];
            $result = $this->reviewModel->addReview($userId, $comment, $rating);
            
            if ($result) {
                $_SESSION['reviewSuccess'] = "Cảm ơn bạn đã gửi đánh giá!";
            } else {
                $_SESSION['reviewError'] = "Có lỗi xảy ra khi lưu đánh giá. Vui lòng thử lại.";
            }
            
            header("Location: " . BASE_URL . "home#reviews");
            exit();
        }
    }

    public function deleteReview($reviewId) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        $userId = $_SESSION['user_id'];
        
        return $this->reviewModel->deleteReview($reviewId, $userId, $isAdmin);
    }
}