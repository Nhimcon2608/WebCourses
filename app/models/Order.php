<?php
/**
 * Order Model
 * Handles order-related database operations
 */
class Order {
    private $conn;
    
    public function __construct() {
        try {
            // Require database connection
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $this->conn = $database->getConnection();
            
            // Test connection
            $stmt = $this->conn->prepare("SELECT 1");
            $stmt->execute();
            error_log("Database connection successful");
        } catch (Exception $e) {
            error_log("Database connection error in Order model: " . $e->getMessage());
            throw new Exception("Không thể kết nối đến cơ sở dữ liệu: " . $e->getMessage());
        }
    }
    
    /**
     * Create a new order
     * @param int $userId User ID
     * @param array $orderData Order information
     * @return string Order ID
     */
    public function createOrder($userId, $orderData) {
        try {
            // Generate unique order ID
            $orderId = $this->generateOrderId();
            
            // Begin transaction
            $this->conn->beginTransaction();
            
            // Insert order record
            $stmt = $this->conn->prepare("
                INSERT INTO orders (
                    order_id, user_id, customer_name, email, phone,
                    total_amount, payment_method, order_date, status
                ) VALUES (
                    :order_id, :user_id, :customer_name, :email, :phone,
                    :total_amount, :payment_method, NOW(), :status
                )
            ");
            
            $stmt->bindParam(':order_id', $orderId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':customer_name', $orderData['customer_name']);
            $stmt->bindParam(':email', $orderData['email']);
            $stmt->bindParam(':phone', $orderData['phone']);
            $stmt->bindParam(':total_amount', $orderData['total_amount']);
            $stmt->bindParam(':payment_method', $orderData['payment_method']);
            $status = isset($orderData['status']) ? $orderData['status'] : 'pending';
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            $insertedOrderId = $this->conn->lastInsertId();
            
            // Insert order items
            foreach ($orderData['items'] as $item) {
                $stmtItem = $this->conn->prepare("
                    INSERT INTO order_items (
                        order_id, course_id, course_title, price
                    ) VALUES (
                        :order_id, :course_id, :course_title, :price
                    )
                ");
                
                $stmtItem->bindParam(':order_id', $insertedOrderId);
                $stmtItem->bindParam(':course_id', $item['course_id']);
                $stmtItem->bindParam(':course_title', $item['title']);
                $stmtItem->bindParam(':price', $item['price']);
                
                $stmtItem->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return $orderId;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Order creation error: " . $e->getMessage());
            throw new Exception("Không thể tạo đơn hàng. Vui lòng thử lại sau.");
        }
    }
    
    /**
     * Generate a unique order ID
     * @return string Unique order ID
     */
    private function generateOrderId() {
        $prefix = 'ORD';
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Get order by order ID
     * @param string $orderId Order ID
     * @return array Order data
     */
    public function getOrderById($orderId) {
        try {
            error_log("Fetching order with ID: " . $orderId);
            
            $stmt = $this->conn->prepare("
                SELECT * FROM orders WHERE order_id = :order_id
            ");
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                error_log("Order found: " . json_encode($order));
                // Get order items
                $order['items'] = $this->getOrderItems($order['id']);
            } else {
                error_log("No order found with ID: " . $orderId);
                
                // Try to find by internal ID in case that's what we received
                $stmt2 = $this->conn->prepare("
                    SELECT * FROM orders WHERE id = :id
                ");
                $stmt2->bindParam(':id', $orderId);
                $stmt2->execute();
                
                $order = $stmt2->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    error_log("Order found by internal ID instead: " . json_encode($order));
                    // Get order items
                    $order['items'] = $this->getOrderItems($order['id']);
                }
            }
            
            return $order;
        } catch (Exception $e) {
            error_log("Get order error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get order items for an order
     * @param int $orderId Order ID
     * @return array Order items
     */
    public function getOrderItems($orderId) {
        try {
            error_log("Fetching order items for order ID: " . $orderId);
            
            if (empty($orderId)) {
                error_log("Order ID is empty, cannot fetch items");
                return [];
            }
            
            $stmt = $this->conn->prepare("
                SELECT oi.*, c.image, IFNULL(u.username, 'Unknown') as instructor 
                FROM order_items oi 
                LEFT JOIN courses c ON oi.course_id = c.course_id
                LEFT JOIN users u ON c.instructor_id = u.user_id
                WHERE oi.order_id = :order_id
            ");
            $stmt->bindParam(':order_id', $orderId);
            $stmt->execute();
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($items) . " items for order ID: " . $orderId);
            
            return $items;
        } catch (Exception $e) {
            error_log("Get order items error: " . $e->getMessage());
            error_log("Error occurred with order ID: " . $orderId);
            return [];
        }
    }
    
    /**
     * Update payment status of an order
     * @param string $orderId Order ID
     * @param string $status Payment status
     * @param string $transactionId Transaction ID
     * @param string $paymentDetails Payment details (JSON)
     * @return boolean Success or failure
     */
    public function updatePaymentStatus($orderId, $status, $transactionId, $paymentDetails) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE orders 
                SET payment_status = :status,
                    transaction_id = :transaction_id,
                    payment_details = :payment_details,
                    updated_at = NOW()
                WHERE order_id = :order_id
            ");
            
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':transaction_id', $transactionId);
            $stmt->bindParam(':payment_details', $paymentDetails);
            $stmt->bindParam(':order_id', $orderId);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Update payment status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get orders for a user
     * @param int $userId User ID
     * @return array Orders
     */
    public function getUserOrders($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get items for each order
            foreach ($orders as &$order) {
                $order['items'] = $this->getOrderItems($order['id']);
            }
            
            return $orders;
        } catch (Exception $e) {
            error_log("Get user orders error: " . $e->getMessage());
            return [];
        }
    }
} 