<?php
/**
 * OrderItem Model
 * Handles order item-related database operations
 * Note: This is a skeleton model. Uncomment and implement methods when ready to use.
 */
class OrderItem {
    private $conn;
    
    public function __construct() {
        // Require database connection
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all items for an order
     * @param int $orderId Order ID
     * @return array Order items
     */
    /*
    public function getItemsByOrderId($orderId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT oi.*, c.image
                FROM order_items oi
                LEFT JOIN courses c ON oi.course_id = c.id
                WHERE oi.order_id = :order_id
            ");
            
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get order items error: " . $e->getMessage());
            return [];
        }
    }
    */
    
    /**
     * Update order item status
     * @param int $itemId Item ID
     * @param string $status New status
     * @return boolean Success or failure
     */
    /*
    public function updateStatus($itemId, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE order_items
                SET status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $itemId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Update order item status error: " . $e->getMessage());
            return false;
        }
    }
    */
    
    /**
     * Get purchased courses for a user
     * @param int $userId User ID
     * @return array Purchased courses
     */
    /*
    public function getUserPurchasedCourses($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT oi.*, o.order_date, c.title, c.image, c.instructor
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN courses c ON oi.course_id = c.id
                WHERE o.user_id = :user_id
                AND o.payment_status = 'completed'
                AND oi.status = 'active'
                ORDER BY o.order_date DESC
            ");
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get user purchased courses error: " . $e->getMessage());
            return [];
        }
    }
    */
} 