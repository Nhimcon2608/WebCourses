<?php
class Course {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($instructor_id, $title, $description, $category_id, $price, $image, $level, $video = '', $duration = '') {
        try {
            if (empty($title) || strlen($title) > 100 || strlen($description) > 65535 || $price < 0) {
                return ['success' => false, 'message' => 'Dữ liệu không hợp lệ: tiêu đề tối đa 100 ký tự, mô tả tối đa 65535 ký tự, giá không âm.'];
            }

            $stmt = $this->conn->prepare("SELECT category_id FROM categories WHERE category_id = :category_id");
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                return ['success' => false, 'message' => 'Danh mục không tồn tại.'];
            }

            $stmt = $this->conn->prepare("INSERT INTO courses (instructor_id, category_id, title, description, video, duration, price, image, level, created_at) VALUES (:instructor_id, :category_id, :title, :description, :video, :duration, :price, :image, :level, NOW())");
            $stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':video', $video, PDO::PARAM_STR);
            $stmt->bindParam(':duration', $duration, PDO::PARAM_STR);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':image', $image, PDO::PARAM_STR);
            $stmt->bindParam(':level', $level, PDO::PARAM_STR);
            $success = $stmt->execute();
            $course_id = $this->conn->lastInsertId();
            return ['success' => $success, 'course_id' => $course_id, 'message' => $success ? 'Khóa học đã được tạo!' : 'Tạo khóa học thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function update($course_id, $instructor_id, $title, $description, $category_id, $price, $image, $level, $video = '', $duration = '', $status = 1) {
        try {
            if (!$this->getById($course_id)) {
                return ['success' => false, 'message' => 'Khóa học không tồn tại.'];
            }
            if (empty($title) || strlen($title) > 100 || strlen($description) > 65535 || $price < 0) {
                return ['success' => false, 'message' => 'Dữ liệu không hợp lệ.'];
            }

            $stmt = $this->conn->prepare("UPDATE courses SET title = :title, description = :description, category_id = :category_id, price = :price, image = :image, level = :level, video = :video, duration = :duration, status = :status WHERE course_id = :course_id AND instructor_id = :instructor_id");
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':image', $image, PDO::PARAM_STR);
            $stmt->bindParam(':level', $level, PDO::PARAM_STR);
            $stmt->bindParam(':video', $video, PDO::PARAM_STR);
            $stmt->bindParam(':duration', $duration, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            return ['success' => $success, 'message' => $success ? 'Khóa học đã được cập nhật!' : 'Cập nhật thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function delete($course_id, $instructor_id) {
        try {
            if (!$this->getById($course_id)) {
                return ['success' => false, 'message' => 'Khóa học không tồn tại.'];
            }

            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = :course_id");
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($count['count'] > 0) {
                return ['success' => false, 'message' => 'Không thể xóa khóa học vì đã có học viên đăng ký.'];
            }

            $stmt = $this->conn->prepare("DELETE FROM courses WHERE course_id = :course_id AND instructor_id = :instructor_id");
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            return ['success' => $success, 'message' => $success ? 'Khóa học đã được xóa!' : 'Xóa thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function getByInstructor($instructor_id) {
        try {
            $stmt = $this->conn->prepare("SELECT course_id, title, description, image, price, level, video, duration, rating FROM courses WHERE instructor_id = :instructor_id ORDER BY created_at DESC");
            $stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }

    public function getById($course_id) {
        try {
            $stmt = $this->conn->prepare("SELECT course_id, title, description, category_id, price, image, level, video, duration, instructor_id, rating, students FROM courses WHERE course_id = :course_id");
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }

    public function getAll($category_id = null) {
        try {
            $sql = "SELECT c.course_id, c.title, c.description, c.price, c.level, c.created_at, c.status,
                   u.username as instructor_name, u.user_id as instructor_id,
                   cat.name as category_name, cat.category_id
                   FROM courses c 
                   LEFT JOIN users u ON c.instructor_id = u.user_id 
                   LEFT JOIN categories cat ON c.category_id = cat.category_id";
            
            if ($category_id) {
                $sql .= " WHERE c.category_id = :category_id";
            }
            
            $sql .= " ORDER BY c.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            
            if ($category_id) {
                $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results ?: [];
        } catch (Exception $e) {
            error_log("Error in Course::getAll: " . $e->getMessage());
            return [];
        }
    }

    public function updateRating($course_id) {
        try {
            $stmt = $this->conn->prepare("UPDATE courses SET rating = (SELECT AVG(rating) FROM reviews WHERE course_id = :course_id AND status = 'approved') WHERE course_id = :course_id2");
            $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt->bindParam(':course_id2', $course_id, PDO::PARAM_INT);
            $success = $stmt->execute();
            return ['success' => $success, 'message' => $success ? 'Rating đã được cập nhật!' : 'Cập nhật rating thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}