<?php
class Category {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($name, $description = '') {
        try {
            if (strlen($name) > 100) {
                return ['success' => false, 'message' => 'Tên danh mục tối đa 100 ký tự.'];
            }
            if (strlen($description) > 65535) {
                return ['success' => false, 'message' => 'Mô tả quá dài.'];
            }

            $stmt = $this->conn->prepare("SELECT category_id FROM categories WHERE name = :name");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Tên danh mục đã tồn tại.'];
            }

            $stmt = $this->conn->prepare("INSERT INTO categories (name, description, status, created_at) VALUES (:name, :description, 1, NOW())");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $success = $stmt->execute();
            $category_id = $this->conn->lastInsertId();

            return ['success' => $success, 'category_id' => $category_id, 'message' => $success ? 'Danh mục đã được tạo!' : 'Tạo danh mục thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function update($category_id, $name, $description = '', $status = 1) {
        try {
            if (!$this->getById($category_id)) {
                return ['success' => false, 'message' => 'Danh mục không tồn tại.'];
            }
            if (strlen($name) > 100) {
                return ['success' => false, 'message' => 'Tên danh mục tối đa 100 ký tự.'];
            }
            if (strlen($description) > 65535) {
                return ['success' => false, 'message' => 'Mô tả quá dài.'];
            }

            $stmt = $this->conn->prepare("SELECT category_id FROM categories WHERE name = :name AND category_id != :category_id");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Tên danh mục đã tồn tại.'];
            }

            $stmt = $this->conn->prepare("UPDATE categories SET name = :name, description = :description, status = :status WHERE category_id = :category_id");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_INT);
            $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $success = $stmt->execute();

            return ['success' => $success, 'message' => $success ? 'Danh mục đã được cập nhật!' : 'Cập nhật thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function delete($category_id) {
        try {
            if (!$this->getById($category_id)) {
                return ['success' => false, 'message' => 'Danh mục không tồn tại.'];
            }

            $stmt = $this->conn->prepare("SELECT course_id FROM courses WHERE category_id = :category_id AND status = 1");
            $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Không thể xóa danh mục vì có khóa học hoạt động liên quan.'];
            }

            $stmt = $this->conn->prepare("DELETE FROM categories WHERE category_id = :category_id");
            $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $success = $stmt->execute();

            return ['success' => $success, 'message' => $success ? 'Danh mục đã được xóa!' : 'Xóa thất bại.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function getAll($limit = 10, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("SELECT category_id, name, description, status, created_at FROM categories ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }

    public function getActive($limit = 10, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("SELECT category_id, name, description, created_at FROM categories WHERE status = 1 ORDER BY name ASC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }

    public function getById($category_id) {
        try {
            $stmt = $this->conn->prepare("SELECT category_id, name, description, status, created_at FROM categories WHERE category_id = :category_id");
            $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            throw new Exception("Lỗi: " . $e->getMessage());
        }
    }
}