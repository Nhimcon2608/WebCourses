<?php
session_start();

// Định nghĩa hằng số
define('BASE_URL', '/WebCourses/');

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý khi nhận lệnh xóa khóa học khỏi giỏ hàng
if (isset($_GET['remove']) && isset($_SESSION['cart'][$_GET['remove']])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    // Chuyển hướng để tránh gửi lại form khi refresh
    header('Location: cart.php');
    exit;
}

// Xử lý khi nhận lệnh xóa toàn bộ giỏ hàng
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    // Chuyển hướng để tránh gửi lại form khi refresh
    header('Location: cart.php');
    exit;
}

// Tính tổng giá trị giỏ hàng
$totalPrice = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalPrice += $item['price'];
}

// Tiêu đề trang
$pageTitle = "Giỏ Hàng";

// Format tổng tiền
$formattedTotal = number_format($totalPrice, 0, ',', '.') . ' VND';
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

    /* Cart styles */
    .cart-container {
        background: #fff;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .cart-empty {
        text-align: center;
        padding: 40px 20px;
    }

    .cart-empty i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }

    .cart-empty h3 {
        font-size: 1.5rem;
        color: #666;
        margin-bottom: 20px;
    }

    .cart-empty p {
        margin-bottom: 30px;
        color: #888;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    .cart-empty .browse-btn {
        display: inline-block;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        padding: 12px 25px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .cart-empty .browse-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cart-table thead th {
        padding: 15px 10px;
        background: #f8f9fa;
        text-align: left;
        font-weight: 700;
        color: #444;
        border-bottom: 2px solid #eee;
    }

    .cart-table tbody td {
        padding: 20px 10px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .cart-item {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .cart-item-image {
        width: 80px;
        height: 50px;
        border-radius: 8px;
        overflow: hidden;
    }

    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cart-item-details h3 {
        font-size: 1.1rem;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .cart-item-details p {
        color: #666;
        font-size: 0.9rem;
    }

    .cart-price {
        font-weight: 700;
        color: #F9A826;
    }

    .cart-actions a {
        color: #dc3545;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .cart-actions a:hover {
        color: #c82333;
    }

    .cart-summary {
        margin-top: 30px;
        background: #f8f9fa;
        border-radius: 10px;
        padding: 25px;
    }

    .cart-summary h3 {
        font-size: 1.3rem;
        margin-bottom: 20px;
        color: #333;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
    }

    .summary-row.total {
        border-top: 2px solid #ddd;
        margin-top: 10px;
        padding-top: 20px;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .cart-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        gap: 15px;
    }

    .continue-btn, .checkout-btn {
        padding: 12px 25px;
        border-radius: 6px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s ease;
        text-align: center;
    }

    .continue-btn {
        background: #fff;
        color: #1e3c72;
        border: 2px solid #1e3c72;
        flex: 1;
    }

    .continue-btn:hover {
        background: #f5f5f5;
    }

    .checkout-btn {
        background: linear-gradient(135deg, #F9A826, #FF512F);
        color: #fff;
        flex: 2;
        border: none;
    }

    .checkout-btn:hover {
        background: linear-gradient(135deg, #FF512F, #F9A826);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(249, 168, 38, 0.3);
    }

    .clear-cart {
        display: inline-block;
        background: #f8f9fa;
        color: #666;
        padding: 8px 15px;
        border-radius: 4px;
        margin-top: 20px;
        font-size: 0.9rem;
        text-decoration: none;
    }

    .clear-cart:hover {
        background: #e9ecef;
        color: #333;
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
        
        .cart-table {
            display: block;
            overflow-x: auto;
        }
        
        .cart-buttons {
            flex-direction: column;
        }
        
        .cart-item {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        
        .cart-item-image {
            width: 100%;
            height: 120px;
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
        <h1 class="page-title">Giỏ Hàng Của Bạn</h1>
        
        <div class="cart-container">
            <?php if (empty($_SESSION['cart'])): ?>
            <!-- Empty cart state -->
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <h3>Giỏ hàng của bạn đang trống</h3>
                <p>Bạn chưa thêm bất kỳ khóa học nào vào giỏ hàng. Hãy khám phá các khóa học phù hợp với bạn!</p>
                <a href="course_catalog.php" class="browse-btn">Khám Phá Khóa Học</a>
            </div>
            <?php else: ?>
            <!-- Cart with items -->
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Khóa Học</th>
                        <th>Giá</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                    <tr>
                        <td>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                </div>
                                <div class="cart-item-details">
                                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p>Giảng viên: <?php echo htmlspecialchars($item['instructor']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="cart-price">
                            <?php 
                                $price = number_format($item['price'], 0, ',', '.') . ' VND';
                                if ($item['price'] == 0) {
                                    $price = 'Miễn phí';
                                }
                                echo $price;
                            ?>
                        </td>
                        <td class="cart-actions">
                            <a href="cart.php?remove=<?php echo $id; ?>" title="Xóa khỏi giỏ hàng">
                                <i class="fas fa-times"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <h3>Tổng Đơn Hàng</h3>
                <div class="summary-row">
                    <span>Tổng số khóa học:</span>
                    <span><?php echo count($_SESSION['cart']); ?> khóa học</span>
                </div>
                <div class="summary-row total">
                    <span>Tổng tiền:</span>
                    <span><?php echo $formattedTotal; ?></span>
                </div>
            </div>
            
            <div class="cart-buttons">
                <a href="course_catalog.php" class="continue-btn">Tiếp Tục Mua Sắm</a>
                <a href="checkout.php" class="checkout-btn">Tiến Hành Thanh Toán</a>
            </div>
            
            <div style="text-align: center;">
                <a href="cart.php?clear=1" class="clear-cart">Xóa Toàn Bộ Giỏ Hàng</a>
            </div>
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