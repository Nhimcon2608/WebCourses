<?php
class Review {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->ensureTableExists(); // Tự động đảm bảo bảng tồn tại khi khởi tạo
    }

    /**
     * Đảm bảo bảng reviews tồn tại
     */
    private function ensureTableExists() {
        // Check if the courses table exists
        $result = $this->conn->query("SHOW TABLES LIKE 'courses'");
        $coursesExist = $result && $result->rowCount() > 0;
        
        if (!$coursesExist) {
            // Create the courses table first if it doesn't exist
            $sql = "CREATE TABLE IF NOT EXISTS courses (
                course_id INT AUTO_INCREMENT PRIMARY KEY,
                instructor_id INT NOT NULL,
                category_id INT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) DEFAULT 0,
                image VARCHAR(255),
                level VARCHAR(50),
                video VARCHAR(255),
                duration VARCHAR(50),
                status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $this->conn->query($sql);
        }
        
        // Now check for reviews table
        $result = $this->conn->query("SHOW TABLES LIKE 'reviews'");
        $tableExists = $result && $result->rowCount() > 0;
        
        if (!$tableExists) {
            // Tạo bảng nếu chưa tồn tại
            $sql = "CREATE TABLE IF NOT EXISTS reviews (
                review_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                course_id INT NOT NULL,
                review_text TEXT NOT NULL,
                rating INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $this->conn->query($sql);
            
            // Add indexes and foreign keys separately to handle possible errors
            $this->conn->query("ALTER TABLE reviews ADD INDEX (user_id)");
            $this->conn->query("ALTER TABLE reviews ADD INDEX (course_id)");
            
            // Try to add foreign keys if the referenced tables exist
            try {
                $this->conn->query("ALTER TABLE reviews ADD CONSTRAINT fk_review_user 
                                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE");
            } catch (PDOException $e) {
                // If error, just log it but continue
                error_log("Error adding foreign key for user_id: " . $e->getMessage());
            }
            
            try {
                $this->conn->query("ALTER TABLE reviews ADD CONSTRAINT fk_review_course 
                                    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE");
            } catch (PDOException $e) {
                // If error, just log it but continue
                error_log("Error adding foreign key for course_id: " . $e->getMessage());
            }
        } else {
            // Kiểm tra cấu trúc bảng để xem review_text hay content được sử dụng
            $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'content'");
            $contentExists = $result && $result->rowCount() > 0;
            
            $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'review_text'");
            $reviewTextExists = $result && $result->rowCount() > 0;
            
            if (!$contentExists && !$reviewTextExists) {
                // Nếu cả hai cột đều không tồn tại, thêm cột review_text
                $this->conn->query("ALTER TABLE reviews ADD COLUMN review_text TEXT NOT NULL AFTER course_id");
            }
        }
    }

    /**
     * Lấy tất cả các review từ database
     */
    public function getAllReviews() {
        // Kiểm tra bảng có tồn tại không
        $result = $this->conn->query("SHOW TABLES LIKE 'reviews'");
        $tableExists = $result && $result->rowCount() > 0;
        
        if (!$tableExists) {
            return []; // Trả về mảng trống nếu bảng không tồn tại
        }
        
        // Kiểm tra xem cột nào tồn tại (content hay review_text)
        $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'content'");
        $contentExists = $result && $result->rowCount() > 0;
        
        $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'review_text'");
        $reviewTextExists = $result && $result->rowCount() > 0;
        
        // Chuẩn bị câu truy vấn dựa trên cột tồn tại
        if ($contentExists) {
            $sql = "SELECT r.review_id, r.content as review_text, r.rating, r.created_at, u.username as author 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.user_id 
                    ORDER BY r.created_at DESC";
        } elseif ($reviewTextExists) {
            $sql = "SELECT r.review_id, r.review_text, r.rating, r.created_at, u.username as author 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.user_id 
                    ORDER BY r.created_at DESC";
        } else {
            return []; // Nếu không có cột nào tồn tại, trả về mảng trống
        }
        
        $result = $this->conn->query($sql);
        
        if ($result) {
            $reviews = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $reviews[] = [
                    'review_id' => $row['review_id'],
                    'review_text' => $row['review_text'],
                    'rating' => $row['rating'],
                    'author' => $row['author'],
                    'created_at' => $row['created_at']
                ];
            }
            return $reviews;
        }
        
        return [];
    }

    /**
     * Thêm một review mới
     */
    public function addReview($userId, $comment, $rating) {
        // Đảm bảo bảng tồn tại trước khi thêm
        $this->ensureTableExists();
        
        // Kiểm tra xem cột nào tồn tại (content hay review_text)
        $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'content'");
        $contentExists = $result && $result->rowCount() > 0;
        
        $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'review_text'");
        $reviewTextExists = $result && $result->rowCount() > 0;
        
        if ($contentExists) {
            $sql = "INSERT INTO reviews (user_id, content, rating) VALUES (?, ?, ?)";
        } elseif ($reviewTextExists) {
            $sql = "INSERT INTO reviews (user_id, review_text, rating) VALUES (?, ?, ?)";
        } else {
            return false; // Nếu không có cột nào tồn tại, không thể thêm review
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $comment, PDO::PARAM_STR);
            $stmt->bindParam(3, $rating, PDO::PARAM_INT);
            return $stmt->execute();
        }
        
        return false;
    }

    /**
     * Xóa một review
     */
    public function deleteReview($reviewId, $userId = null, $isAdmin = false) {
        // Kiểm tra bảng có tồn tại không
        $result = $this->conn->query("SHOW TABLES LIKE 'reviews'");
        $tableExists = $result && $result->rowCount() > 0;
        
        if (!$tableExists) {
            return false;
        }
        
        // Nếu là admin, có thể xóa bất kỳ review nào
        if ($isAdmin) {
            $sql = "DELETE FROM reviews WHERE review_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(1, $reviewId, PDO::PARAM_INT);
        } else {
            // Người dùng chỉ có thể xóa review của chính họ
            $sql = "DELETE FROM reviews WHERE review_id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(1, $reviewId, PDO::PARAM_INT);
            $stmt->bindParam(2, $userId, PDO::PARAM_INT);
        }
        
        if ($stmt) {
            return $stmt->execute();
        }
        
        return false;
    }

    /**
     * Lấy review theo ID
     */
    public function getReviewById($reviewId) {
        // Kiểm tra bảng có tồn tại không
        $result = $this->conn->query("SHOW TABLES LIKE 'reviews'");
        $tableExists = $result && $result->rowCount() > 0;
        
        if (!$tableExists) {
            return null;
        }
        
        // Kiểm tra xem cột nào tồn tại (content hay review_text)
        $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'content'");
        $contentExists = $result && $result->rowCount() > 0;
        
        $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'review_text'");
        $reviewTextExists = $result && $result->rowCount() > 0;
        
        if ($contentExists) {
            $sql = "SELECT r.*, r.content as review_text, u.username as author 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.user_id 
                    WHERE r.review_id = ?";
        } elseif ($reviewTextExists) {
            $sql = "SELECT r.*, u.username as author 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.user_id 
                    WHERE r.review_id = ?";
        } else {
            return null; // Nếu không có cột nào tồn tại, không thể lấy review
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bindParam(1, $reviewId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $review = [
                    'review_id' => $result['review_id'],
                    'review_text' => $result['review_text'],
                    'rating' => $result['rating'],
                    'author' => $result['author'],
                    'created_at' => $result['created_at'],
                    'user_id' => $result['user_id']
                ];
                return $review;
            }
        }
        
        return null;
    }

    /**
     * Lấy reviews theo course ID
     */
    public function getReviewsByCourse($courseId) {
        // Kiểm tra bảng có tồn tại không
        $result = $this->conn->query("SHOW TABLES LIKE 'reviews'");
        $tableExists = $result && $result->rowCount() > 0;
        
        if (!$tableExists) {
            return [];
        }
        
        // Kiểm tra xem cột nào tồn tại (content hay review_text)
        $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'content'");
        $contentExists = $result && $result->rowCount() > 0;
        
        $result = $this->conn->query("SHOW COLUMNS FROM reviews LIKE 'review_text'");
        $reviewTextExists = $result && $result->rowCount() > 0;
        
        if ($contentExists) {
            $sql = "SELECT r.review_id, r.content as review_text, r.rating, r.created_at, u.username as author 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.user_id 
                    WHERE r.course_id = ? 
                    ORDER BY r.created_at DESC";
        } elseif ($reviewTextExists) {
            $sql = "SELECT r.review_id, r.review_text, r.rating, r.created_at, u.username as author 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.user_id 
                    WHERE r.course_id = ? 
                    ORDER BY r.created_at DESC";
        } else {
            return []; // Nếu không có cột nào tồn tại, trả về mảng trống
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bindParam(1, $courseId, PDO::PARAM_INT);
            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $reviews;
        }
        
        return [];
    }

    // Thêm đánh giá
    public function add($user_id, $course_id, $review_text, $rating) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO reviews (course_id, user_id, review_text, rating, created_at) VALUES (:course_id, :user_id, :review_text, :rating, NOW())");
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':review_text', $review_text, PDO::PARAM_STR);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $success = $stmt->execute();
            return ['success' => $success, 'message' => $success ? 'Đánh giá đã được gửi!' : 'Gửi đánh giá thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    // Lấy danh sách đánh giá theo khóa học
    public function getByCourse($course_id) {
        try {
            $stmt = $this->conn->prepare("SELECT r.review_text, r.rating, u.username AS author 
                FROM reviews r 
                JOIN users u ON r.user_id = u.user_id 
                WHERE r.course_id = :course_id 
                ORDER BY r.created_at DESC");
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }

    public function delete($review_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM reviews WHERE review_id = :review_id");
            $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            $this->updateCourseRating($review_id); // Cập nhật rating sau khi xóa
            return ['success' => $success, 'message' => $success ? 'Đánh giá đã được xóa!' : 'Xóa đánh giá thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function approve($review_id, $status) {
        try {
            $valid_status = ['approved', 'rejected'];
            if (!in_array($status, $valid_status)) {
                return ['success' => false, 'message' => 'Trạng thái không hợp lệ.'];
            }
            $stmt = $this->conn->prepare("UPDATE reviews SET status = :status WHERE review_id = :review_id");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            if ($success && $status === 'approved') {
                $this->updateCourseRating($review_id); // Cập nhật rating khi phê duyệt
            }
            return ['success' => $success, 'message' => $success ? "Đánh giá đã được $status!" : "Phê duyệt thất bại."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function getPending() {
        try {
            $stmt = $this->conn->prepare("SELECT review_id, review_text, rating, user_id, course_id FROM reviews WHERE status = 'pending'");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {                                                            
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }

    private function updateCourseRating($review_id) {
        try {
            $stmt = $this->conn->prepare("SELECT course_id FROM reviews WHERE review_id = :review_id");
            $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $course_id = $result['course_id'] ?? null;

            if ($course_id) {
                $stmt = $this->conn->prepare("UPDATE courses SET rating = (SELECT AVG(rating) FROM reviews WHERE course_id = :course_id AND status = 'approved') WHERE course_id = :course_id2");
                $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                $stmt->bindParam(':course_id2', $course_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Lỗi cập nhật rating khóa học: " . $e->getMessage());
        }
    }
}