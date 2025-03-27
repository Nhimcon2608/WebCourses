<?php
session_start();

// Định nghĩa hằng số
define('BASE_URL', '/WebCourses/');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/User.php';

try {
    // Khởi tạo đối tượng Order và User
    $orderModel = new Order();
    $userModel = new User();
    
    // Lấy thông tin người dùng
    $user = $userModel->getUserById($_SESSION['user_id']);
    
    // Lấy danh sách đơn hàng của người dùng
    $orders = $orderModel->getUserOrders($_SESSION['user_id']);
    
} catch (Exception $e) {
    // Log lỗi và hiển thị thông báo
    error_log($e->getMessage());
    $_SESSION['error'] = 'Có lỗi xảy ra khi lấy thông tin đơn hàng. Vui lòng thử lại sau.';
    header('Location: ' . BASE_URL . 'app/views/product/error.php?message=' . urlencode($e->getMessage()));
    exit;
}

// Tiêu đề trang
$pageTitle = "Đơn Hàng Của Tôi";
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

    /* Orders container */
    .orders-container {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        padding: 30px;
        margin-bottom: 30px;
    }

    .no-orders {
        text-align: center;
        padding: 40px 0;
        color: #666;
    }

    .no-orders-icon {
        font-size: 4rem;
        color: #ccc;
        margin-bottom: 15px;
    }

    .no-orders-text {
        font-size: 1.2rem;
        margin-bottom: 20px;
    }

    .shop-now-btn {
        display: inline-block;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        padding: 12px 25px;
        text-align: center;
        border-radius: 8px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .shop-now-btn:hover {
        background: linear-gradient(135deg, #2a5298, #1e3c72);
        transform: translateY(-3px);
    }

    /* Order card */
    .order-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid #eee;
    }

    .order-header {
        background: #f8f9fa;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
    }

    .order-id {
        font-weight: 700;
        color: #1e3c72;
    }

    .order-date {
        color: #666;
        font-size: 0.9rem;
    }

    .order-status {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-completed {
        background-color: #e8f5e9;
        color: #4caf50;
    }

    .status-pending {
        background-color: #fff8e1;
        color: #ffc107;
    }

    .status-failed {
        background-color: #ffebee;
        color: #f44336;
    }

    .order-content {
        padding: 20px;
    }

    .order-items {
        margin-bottom: 20px;
    }

    .order-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f2f2f2;
    }

    .order-item:last-child {
        border-bottom: none;
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

    .order-footer {
        background: #f8f9fa;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #eee;
    }

    .order-total {
        font-weight: 700;
        font-size: 1.2rem;
    }

    .order-total-label {
        color: #666;
        font-size: 0.9rem;
        font-weight: 400;
    }

    .order-total-price {
        color: #1e3c72;
    }

    .view-details-btn {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        padding: 8px 15px;
        text-align: center;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .view-details-btn:hover {
        background: linear-gradient(135deg, #2a5298, #1e3c72);
        transform: translateY(-2px);
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
    @media (max-width: 768px) {
        nav ul {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .page-title {
            font-size: 2rem;
        }
        
        .order-header, 
        .order-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .view-details-btn {
            align-self: flex-end;
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
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/home.php">Trang Chủ</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/course_catalog.php">Khóa Học</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/student_dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/my_orders.php">Đơn Hàng</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo BASE_URL; ?>auth/logout.php">Đăng Xuất</a></li>
                    <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/home.php#login">Đăng Nhập</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main container -->
    <div class="main-container">
        <h1 class="page-title">Đơn Hàng Của Tôi</h1>
        
        <div class="orders-container">
            <?php if (empty($orders)): ?>
            <div class="no-orders">
                <div class="no-orders-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h2 class="no-orders-text">Bạn chưa có đơn hàng nào!</h2>
                <a href="<?php echo BASE_URL; ?>app/views/product/course_catalog.php" class="shop-now-btn">Mua sắm ngay</a>
            </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">Mã đơn hàng: <?php echo htmlspecialchars($order['order_id']); ?></div>
                        <div class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                        <?php 
                            $statusClass = '';
                            switch($order['payment_status']) {
                                case 'completed':
                                    $statusClass = 'status-completed';
                                    $statusText = 'Đã thanh toán';
                                    break;
                                case 'pending':
                                    $statusClass = 'status-pending';
                                    $statusText = 'Đang xử lý';
                                    break;
                                case 'failed':
                                    $statusClass = 'status-failed';
                                    $statusText = 'Thất bại';
                                    break;
                                default:
                                    $statusClass = 'status-pending';
                                    $statusText = 'Đang xử lý';
                            }
                        ?>
                        <span class="order-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                    
                    <div class="order-content">
                        <div class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <div class="order-item-image">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['course_title']); ?>">
                                </div>
                                <div class="order-item-details">
                                    <h4 class="order-item-title"><?php echo htmlspecialchars($item['course_title']); ?></h4>
                                    <p class="order-item-instructor">Giảng viên: <?php echo htmlspecialchars($item['instructor']); ?></p>
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
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="order-footer">
                        <div class="order-total">
                            <span class="order-total-label">Tổng cộng:</span>
                            <span class="order-total-price"><?php echo number_format($order['total_amount'], 0, ',', '.') . ' VND'; ?></span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>app/views/product/order_detail.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="view-details-btn">Xem chi tiết</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/home.php">Trang Chủ</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/course_catalog.php">Khóa Học</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/home.php#about">Về Chúng Tôi</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/home.php#contact">Liên Hệ</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Danh Mục</h3>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/course_catalog.php?category_id=1">Lập Trình</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/course_catalog.php?category_id=2">Thiết Kế</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/views/product/course_catalog.php?category_id=3">Kinh Doanh</a></li>
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