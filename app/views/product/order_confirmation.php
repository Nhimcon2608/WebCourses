<?php
session_start();

// Thiết lập múi giờ cho Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Định nghĩa hằng số
define('BASE_URL', '/WebCourses/');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Kiểm tra xem có thông tin đơn hàng trong session không
if (!isset($_SESSION['last_order'])) {
    // Kiểm tra xem có mã đơn hàng trong URL không (trường hợp redirect từ thanh toán)
    if (isset($_GET['order_id'])) {
        $orderId = $_GET['order_id'];
        $_SESSION['last_order'] = [
            'order_id' => $orderId
        ];
    } else {
        // Nếu không có thông tin đơn hàng, chuyển hướng về trang chính
        header('Location: ' . BASE_URL . 'app/views/product/home.php');
        exit;
    }
}

require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/User.php';

try {
    // Khởi tạo đối tượng Order và User
    $orderModel = new Order();
    $userModel = new User();
    
    // Lấy thông tin đơn hàng từ session
    $lastOrder = $_SESSION['last_order'];
    $orderId = $lastOrder['order_id'];
    
    error_log("Order confirmation - Order ID: " . $orderId);
    
    // Lấy thông tin chi tiết đơn hàng từ database
    $order = $orderModel->getOrderById($orderId);
    
    if (!$order) {
        error_log("Order not found: " . $orderId);
        throw new Exception('Không tìm thấy thông tin đơn hàng');
    }
    
    error_log("Order found: " . json_encode($order));
    
    // Lấy thông tin người dùng từ database
    $user = $userModel->getUserById($_SESSION['user_id']);
    
    if (!$user) {
        error_log("User not found: " . $_SESSION['user_id']);
    } else {
        error_log("User found: " . $user['username']);
    }
    
    // Gán thông tin người dùng
    $customer_name = isset($order['customer_name']) ? $order['customer_name'] : (isset($user['name']) ? $user['name'] : 'Khách hàng');
    $customer_email = isset($order['email']) ? $order['email'] : (isset($user['email']) ? $user['email'] : 'Không có email');
    $customer_phone = isset($order['phone']) ? $order['phone'] : (isset($user['phone']) ? $user['phone'] : 'Chưa cập nhật');
    
    // Format tổng tiền
    $total = $order['total_amount'];
    $formattedTotal = number_format($total, 0, ',', '.') . ' VND';
    
    // Lấy phương thức thanh toán
    $payment_method = $order['payment_method'];
    
    // Format payment method for display
    $payment_method_text = '';
    switch ($payment_method) {
        case 'credit_card':
            $payment_method_text = 'Thẻ tín dụng / Ghi nợ';
            break;
        case 'bank_transfer':
            $payment_method_text = 'Chuyển khoản ngân hàng';
            break;
        case 'momo':
            $payment_method_text = 'Ví điện tử (MoMo, ZaloPay,...)';
            break;
        default:
            $payment_method_text = $payment_method;
    }
    
    // Lấy thời gian hiện tại theo múi giờ Việt Nam
    $current_time = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    $current_time_formatted = $current_time->format('d/m/Y H:i:s') . ' (Giờ Việt Nam)';
    
    // Xóa thông tin đơn hàng khỏi session sau khi đã hiển thị
    unset($_SESSION['last_order']);
    
    // Try to get order items, add error handling
    try {
        // Attempt to get order items with better error handling
        if (isset($order['id'])) {
            $items = $orderModel->getOrderItems($order['id']);
            error_log("Successfully retrieved items for order #" . $order['id']);
        } else {
            error_log("Order ID not available in order array");
            $items = [];
        }
        
        if (!empty($items)) {
            foreach ($items as $item): 
    ?>
    <div class="order-item">
        <div class="order-item-image">
            <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://placehold.co/100x70?text=Course'); ?>" alt="<?php echo htmlspecialchars($item['course_title']); ?>">
        </div>
        <div class="order-item-details">
            <h4 class="order-item-title"><?php echo htmlspecialchars($item['course_title']); ?></h4>
            <p class="order-item-instructor">Giảng viên: <?php echo htmlspecialchars($item['instructor'] ?? 'Không có thông tin'); ?></p>
        </div>
        <div class="order-item-price">
            <?php 
                $price = number_format($item['price'], 0, ',', '.') . ' VND';
                if ($item['price'] == 0) {
                    $price = 'Miễn phí';
                }
                echo $price;
            ?>
        </div>
    </div>
    <?php 
            endforeach; 
        } else {
            echo "<div>Không có thông tin về khóa học</div>";
        }
    } catch (Exception $e) {
        error_log("Error retrieving order items: " . $e->getMessage());
        echo "<div>Đã xảy ra lỗi khi truy xuất thông tin khóa học</div>";
    }
    
    // Log lỗi và hiển thị thông báo
    error_log("Order confirmation - Order ID: " . ($orderId ?? 'unknown'));
    $_SESSION['error'] = 'Có lỗi xảy ra khi xử lý đơn hàng. Vui lòng thử lại sau.';
    
    // Chuyển về trang lỗi với thông báo rõ ràng
    $error_message = 'Không thể hiển thị trang xác nhận đơn hàng. Chi tiết: ' . $e->getMessage();
    header('Location: ' . BASE_URL . 'app/views/product/error.php?message=' . urlencode($error_message));
    exit;
} catch (Exception $e) {
    // Log lỗi và hiển thị thông báo
    error_log("Error in order_confirmation.php: " . $e->getMessage());
    error_log("Order ID: " . ($orderId ?? 'unknown'));
    $_SESSION['error'] = 'Có lỗi xảy ra khi xử lý đơn hàng. Vui lòng thử lại sau.';
    
    // Chuyển về trang lỗi với thông báo rõ ràng
    $error_message = 'Không thể hiển thị trang xác nhận đơn hàng. Chi tiết: ' . $e->getMessage();
    header('Location: ' . BASE_URL . 'app/views/product/error.php?message=' . urlencode($error_message));
    exit;
}

// Tiêu đề trang
$pageTitle = "Xác Nhận Đơn Hàng";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?> - Học Tập Trực Tuyến</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    /* Reset mặc định */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Nunito', 'Quicksand', sans-serif;
        background-color: #f8f9fa;
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        color: #333;
    }

    /* Header */
    header {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        padding: 20px 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .logo {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(90deg, #F9D423, #FF4E50);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        font-family: 'Montserrat', sans-serif;
        display: inline-block;
        cursor: pointer;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        letter-spacing: -0.5px;
    }

    nav ul {
        list-style: none;
        display: flex;
        gap: 25px;
    }

    nav ul li a {
        color: #fff;
        text-decoration: none;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        letter-spacing: 0.3px;
        font-size: 1.05rem;
    }

    nav ul li a:hover {
        color: #FFC107;
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    /* Main container */
    .main-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
        animation: fadeIn 1s ease;
    }

    /* Page title */
    .page-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1e3c72;
        margin-bottom: 30px;
        font-family: 'Montserrat', sans-serif;
        text-align: center;
    }

    /* Confirmation message */
    .confirmation-message {
        text-align: center;
        background-color: #dff0d8;
        color: #3c763d;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        border-left: 5px solid #3c763d;
    }

    .confirmation-icon {
        font-size: 4rem;
        color: #5cb85c;
        margin-bottom: 15px;
    }

    .confirmation-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .confirmation-text {
        font-size: 1.1rem;
    }

    /* Order details container */
    .order-details-container {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        padding: 30px;
        margin-bottom: 30px;
    }

    .order-details-title {
        font-size: 1.5rem;
        color: #1e3c72;
        margin-bottom: 20px;
        font-weight: 700;
        border-bottom: 2px solid #f2f2f2;
        padding-bottom: 10px;
    }

    .order-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }

    .order-info-section {
        background: #f9f9f9;
        border-radius: 10px;
        padding: 20px;
    }

    .order-info-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: #1e3c72;
    }

    .order-info-item {
        margin-bottom: 10px;
        display: flex;
    }

    .order-info-label {
        font-weight: 600;
        width: 40%;
        color: #555;
    }

    .order-info-value {
        width: 60%;
    }

    /* Order items */
    .order-items {
        margin-top: 30px;
    }

    .order-item {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f2f2f2;
    }

    .order-item-image {
        width: 80px;
        height: 50px;
        border-radius: 8px;
        overflow: hidden;
        margin-right: 15px;
    }

    .order-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .order-item-details {
        flex: 1;
    }

    .order-item-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }

    .order-item-instructor {
        color: #666;
        font-size: 0.9rem;
    }

    .order-item-price {
        font-weight: 700;
        color: #F9A826;
        text-align: right;
        min-width: 120px;
    }

    .order-total {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #f2f2f2;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .order-total-label {
        color: #333;
    }

    .order-total-price {
        color: #F9A826;
    }

    /* Navigation buttons */
    .navigation-buttons {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 30px;
    }

    .navigation-button {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        padding: 12px 25px;
        text-align: center;
        border-radius: 8px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        font-family: 'Montserrat', sans-serif;
        text-decoration: none;
        display: inline-block;
    }

    .navigation-button:hover {
        background: linear-gradient(135deg, #2a5298, #1e3c72);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3);
    }

    .navigation-button.primary {
        background: linear-gradient(135deg, #F9A826, #FF512F);
    }

    .navigation-button.primary:hover {
        background: linear-gradient(135deg, #FF512F, #F9A826);
    }

    /* Footer */
    footer {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        padding: 40px 0;
        margin-top: 60px;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
    }

    .footer-column h3 {
        font-size: 1.4rem;
        margin-bottom: 20px;
        font-family: 'Montserrat', sans-serif;
        position: relative;
        padding-bottom: 10px;
    }

    .footer-column h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: #FFC107;
        border-radius: 2px;
    }

    .footer-column ul {
        list-style: none;
    }

    .footer-column ul li {
        margin-bottom: 10px;
    }

    .footer-column ul li a {
        color: #ddd;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .footer-column ul li a:hover {
        color: #FFC107;
        padding-left: 5px;
    }

    .copyright {
        text-align: center;
        padding-top: 30px;
        margin-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.9rem;
        color: #ddd;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* Responsive design */
    @media (max-width: 992px) {
        .order-info {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    @media (max-width: 768px) {
        nav ul {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .page-title {
            font-size: 2rem;
        }
        
        .navigation-buttons {
            flex-direction: column;
            gap: 15px;
        }
    }
    </style>
</head>

<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">EduHub</div>
            <nav>
                <ul>
                    <li><a href="home.php">Trang Chủ</a></li>
                    <li><a href="course_catalog.php">Khóa Học</a></li>
                    <li><a href="student_dashboard.php">Dashboard</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
                    <?php else: ?>
                    <li><a href="home.php#login">Đăng Nhập</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main container -->
    <div class="main-container">
        <h1 class="page-title">Xác Nhận Đơn Hàng</h1>
        
        <!-- Confirmation message -->
        <div class="confirmation-message">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="confirmation-title">Đặt hàng thành công!</h2>
            <p class="confirmation-text">Cảm ơn <?php echo htmlspecialchars($customer_name); ?>, đơn hàng của bạn đã được ghi nhận. Chúng tôi đã gửi thông tin đơn hàng vào email của bạn.</p>
        </div>
        
        <!-- Order details -->
        <div class="order-details-container">
            <h2 class="order-details-title">Chi tiết đơn hàng</h2>
            
            <div class="order-info">
                <div class="order-info-section">
                    <h3 class="order-info-title">Thông tin đơn hàng</h3>
                    <div class="order-info-item">
                        <div class="order-info-label">Mã đơn hàng:</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($orderId); ?></div>
                    </div>
                    <div class="order-info-item">
                        <div class="order-info-label">Ngày đặt hàng:</div>
                        <div class="order-info-value"><?php echo $current_time_formatted; ?></div>
                    </div>
                    <div class="order-info-item">
                        <div class="order-info-label">Phương thức thanh toán:</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($payment_method_text); ?></div>
                    </div>
                </div>
                
                <div class="order-info-section">
                    <h3 class="order-info-title">Thông tin khách hàng</h3>
                    <div class="order-info-item">
                        <div class="order-info-label">Họ và tên:</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($customer_name); ?></div>
                    </div>
                    <div class="order-info-item">
                        <div class="order-info-label">Email:</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($customer_email); ?></div>
                    </div>
                    <div class="order-info-item">
                        <div class="order-info-label">Số điện thoại:</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($customer_phone); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="order-items">
                <h3 class="order-info-title">Các khóa học đã mua</h3>
                
                <?php 
                try {
                    // Attempt to get order items with better error handling
                    if (isset($order['id'])) {
                        $items = $orderModel->getOrderItems($order['id']);
                        error_log("Successfully retrieved items for order #" . $order['id']);
                    } else {
                        error_log("Order ID not available in order array");
                        $items = [];
                    }
                    
                    if (!empty($items)) {
                        foreach ($items as $item): 
                ?>
                <div class="order-item">
                    <div class="order-item-image">
                        <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://placehold.co/100x70?text=Course'); ?>" alt="<?php echo htmlspecialchars($item['course_title']); ?>">
                    </div>
                    <div class="order-item-details">
                        <h4 class="order-item-title"><?php echo htmlspecialchars($item['course_title']); ?></h4>
                        <p class="order-item-instructor">Giảng viên: <?php echo htmlspecialchars($item['instructor'] ?? 'Không có thông tin'); ?></p>
                    </div>
                    <div class="order-item-price">
                        <?php 
                            $price = number_format($item['price'], 0, ',', '.') . ' VND';
                            if ($item['price'] == 0) {
                                $price = 'Miễn phí';
                            }
                            echo $price;
                        ?>
                    </div>
                </div>
                <?php 
                        endforeach; 
                    } else {
                        echo "<div>Không có thông tin về khóa học</div>";
                    }
                } catch (Exception $e) {
                    error_log("Error retrieving order items: " . $e->getMessage());
                    echo "<div>Đã xảy ra lỗi khi truy xuất thông tin khóa học</div>";
                }
                ?>
                
                <div class="order-total">
                    <span class="order-total-label">Tổng cộng:</span>
                    <span class="order-total-price"><?php echo $formattedTotal; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Navigation buttons -->
        <div class="navigation-buttons">
            <a href="course_catalog.php" class="navigation-button">Tiếp tục mua sắm</a>
            <a href="student_dashboard.php" class="navigation-button primary">Đi đến Khóa học của tôi</a>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>Học Tập</h3>
                <p>Nền tảng học tập trực tuyến hàng đầu Việt Nam với các khóa học chất lượng cao từ những giảng viên
                    hàng đầu.</p>
            </div>
            <div class="footer-column">
                <h3>Liên Kết</h3>
                <ul>
                    <li><a href="home.php">Trang Chủ</a></li>
                    <li><a href="course_catalog.php">Khóa Học</a></li>
                    <li><a href="home.php#about">Về Chúng Tôi</a></li>
                    <li><a href="home.php#contact">Liên Hệ</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Danh Mục</h3>
                <ul>
                    <li><a href="course_catalog.php?category_id=1">Lập Trình</a></li>
                    <li><a href="course_catalog.php?category_id=2">Thiết Kế</a></li>
                    <li><a href="course_catalog.php?category_id=3">Kinh Doanh</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Liên Hệ</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> cuonghotran17022004@gmail.com</li>
                    <li><i class="fas fa-phone"></i> (035) 5999 141</li>
                    <li><i class="fas fa-map-marker-alt"></i> Trường ĐH Công Nghệ Hutech TP.HCM</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Học Tập Trực Tuyến. All Rights Reserved.</p>
        </div>
    </footer>
</body>

</html> 