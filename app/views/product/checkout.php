<?php
session_start();

// Định nghĩa hằng số
define('BASE_URL', '/WebCourses/');

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    // Nếu giỏ hàng trống, chuyển về trang giỏ hàng
    header('Location: cart.php');
    exit;
}

// Xử lý khi người dùng xác nhận thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Kiểm tra xem người dùng đã đăng nhập chưa
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Vui lòng đăng nhập để thanh toán';
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
    
    // Lấy dữ liệu từ form
    $customerName = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'credit_card';
    
    // Tính tổng tiền
    $totalAmount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $totalAmount += $item['price'];
    }
    
    // Chuẩn bị dữ liệu đơn hàng
    $orderData = [
        'customer_name' => $customerName,
        'email' => $email,
        'phone' => $phone,
        'total_amount' => $totalAmount,
        'payment_method' => $paymentMethod,
        'status' => 'pending',
        'items' => []
    ];
    
    // Chuẩn bị dữ liệu các sản phẩm trong đơn hàng
    foreach ($_SESSION['cart'] as $key => $item) {
        $courseId = (is_array($key)) ? $key : (isset($item['id']) ? $item['id'] : $key);
        $title = isset($item['title']) ? $item['title'] : 'Không có tiêu đề';
        $price = isset($item['price']) ? $item['price'] : 0;
        
        $orderData['items'][] = [
            'course_id' => $courseId,
            'title' => $title,
            'price' => $price
        ];
    }
    
    try {
        // Kết nối với Order model để lưu đơn hàng vào database
        require_once __DIR__ . '/../../models/Order.php';
        $orderModel = new Order();
        
        // Lưu đơn hàng và lấy mã đơn hàng
        $orderId = $orderModel->createOrder($_SESSION['user_id'], $orderData);
        
        // Lưu thông tin đơn hàng vào session để hiển thị trên trang xác nhận
        $_SESSION['last_order'] = [
            'order_id' => $orderId,
            'items' => $_SESSION['cart'],
            'total' => $totalAmount,
            'date' => date('Y-m-d H:i:s')
        ];
        
        // Xóa giỏ hàng
        $_SESSION['cart'] = [];
        
        // Ghi log thành công
        error_log("Đơn hàng {$orderId} được tạo thành công cho người dùng {$_SESSION['user_id']}");
        
        // Đảm bảo rằng BASE_URL là đúng
        error_log("BASE_URL is: " . BASE_URL);
        
        // Tạo URL tuyệt đối đến trang xác nhận thanh toán
        $redirect_url = BASE_URL . 'checkout_success.php?order_id=' . urlencode($orderId);
        error_log("Redirecting to: " . $redirect_url);
        
        // Thực hiện chuyển hướng
        header('Location: ' . $redirect_url);
        exit;
    } catch (Exception $e) {
        // Ghi log lỗi
        error_log("Lỗi khi tạo đơn hàng: " . $e->getMessage());
        
        // Hiển thị thông báo lỗi cho người dùng
        $_SESSION['error'] = "Có lỗi xảy ra khi xử lý thanh toán: " . $e->getMessage();
        
        // Chuyển hướng đến trang lỗi hoặc quay lại trang checkout
        header('Location: checkout.php');
        exit;
    }
}

// Tính tổng giá trị giỏ hàng
$totalPrice = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalPrice += $item['price'];
}

// Format tổng tiền
$formattedTotal = number_format($totalPrice, 0, ',', '.') . ' VND';

// Tiêu đề trang
$pageTitle = "Thanh Toán";
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

    .cart-icon {
        position: relative;
    }

    .cart-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #F9A826;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
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

    /* Checkout container */
    .checkout-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }

    /* Order summary */
    .order-summary {
        background: #fff;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .summary-title {
        font-size: 1.5rem;
        color: #1e3c72;
        margin-bottom: 20px;
        font-weight: 700;
        border-bottom: 2px solid #f2f2f2;
        padding-bottom: 10px;
    }

    .summary-item {
        display: flex;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f2f2f2;
    }

    .summary-item-image {
        width: 80px;
        height: 50px;
        border-radius: 8px;
        overflow: hidden;
        margin-right: 15px;
    }

    .summary-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .summary-item-details {
        flex: 1;
    }

    .summary-item-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }

    .summary-item-instructor {
        color: #666;
        font-size: 0.9rem;
    }

    .summary-item-price {
        font-weight: 700;
        color: #F9A826;
        text-align: right;
    }

    .summary-total {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #f2f2f2;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .summary-total-label {
        color: #333;
    }

    .summary-total-price {
        color: #F9A826;
    }

    /* Payment form */
    .payment-form {
        background: #fff;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .form-title {
        font-size: 1.5rem;
        color: #1e3c72;
        margin-bottom: 25px;
        font-weight: 700;
        border-bottom: 2px solid #f2f2f2;
        padding-bottom: 10px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #444;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: 'Nunito', sans-serif;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #1e3c72;
    }

    .form-row {
        display: flex;
        gap: 15px;
    }

    .form-col {
        flex: 1;
    }

    .payment-options {
        margin-bottom: 25px;
    }

    .payment-option {
        display: block;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-option:hover {
        background-color: #f9f9f9;
    }

    .payment-option input {
        margin-right: 10px;
    }

    .checkout-btn {
        background: linear-gradient(135deg, #F9A826, #FF512F);
        color: #fff;
        padding: 15px 0;
        text-align: center;
        border-radius: 8px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        width: 100%;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        font-family: 'Montserrat', sans-serif;
        margin-top: 20px;
    }

    .checkout-btn:hover {
        background: linear-gradient(135deg, #FF512F, #F9A826);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(249, 168, 38, 0.3);
    }

    .back-to-cart {
        display: block;
        text-align: center;
        margin-top: 15px;
        color: #666;
        text-decoration: none;
    }

    .back-to-cart:hover {
        color: #1e3c72;
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
        .checkout-container {
            grid-template-columns: 1fr;
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
        
        .form-row {
            flex-direction: column;
            gap: 0;
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
                    <li class="cart-icon">
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i>
                            <?php if (count($_SESSION['cart']) > 0): ?>
                            <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
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
        <h1 class="page-title">Thanh Toán</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #f5c6cb;">
            <strong>Lỗi:</strong> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <!-- Order summary -->
            <div class="order-summary">
                <h2 class="summary-title">Tóm Tắt Đơn Hàng</h2>
                
                <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="summary-item">
                    <div class="summary-item-image">
                        <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://placehold.co/100x70?text=Course'); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Khóa học'); ?>">
                    </div>
                    <div class="summary-item-details">
                        <h3 class="summary-item-title"><?php echo htmlspecialchars($item['title'] ?? 'Không có tiêu đề'); ?></h3>
                        <p class="summary-item-instructor">Giảng viên: <?php echo htmlspecialchars($item['instructor'] ?? 'Không có thông tin'); ?></p>
                    </div>
                    <div class="summary-item-price">
                        <?php 
                            $price = isset($item['price']) ? number_format($item['price'], 0, ',', '.') . ' VND' : 'N/A';
                            if (isset($item['price']) && $item['price'] == 0) {
                                $price = 'Miễn phí';
                            }
                            echo $price;
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="summary-total">
                    <span class="summary-total-label">Tổng cộng:</span>
                    <span class="summary-total-price"><?php echo $formattedTotal; ?></span>
                </div>
            </div>
            
            <!-- Payment form -->
            <div class="payment-form">
                <h2 class="form-title">Thông Tin Thanh Toán</h2>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="form-group">
                        <label class="form-label" for="name">Họ và tên</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" class="form-control" required>
                    </div>
                    
                    <div class="payment-options">
                        <h3 class="form-label">Phương thức thanh toán</h3>
                        
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="credit_card" checked> Thẻ tín dụng / Ghi nợ
                        </label>
                        
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer"> Chuyển khoản ngân hàng
                        </label>
                        
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="momo"> Ví điện tử (MoMo, ZaloPay,...)
                        </label>
                    </div>
                    
                    <button type="submit" name="checkout" class="checkout-btn">Hoàn Tất Thanh Toán</button>
                    <a href="cart.php" class="back-to-cart">Quay lại giỏ hàng</a>
                </form>
            </div>
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