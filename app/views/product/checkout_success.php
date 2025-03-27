<?php
session_start();

// Kiểm tra xem có thông tin đơn hàng trong session không
if (!isset($_SESSION['last_order'])) {
    header('Location: cart.php');
    exit;
}

$order = $_SESSION['last_order'];
$orderId = $order['order_id'];
$totalAmount = $order['total'];
$orderDate = $order['date'];

// Tiêu đề trang
$pageTitle = "Thanh Toán Thành Công";
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

    .success-container {
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .success-icon {
        font-size: 4rem;
        color: #28a745;
        margin-bottom: 20px;
    }

    .success-title {
        font-size: 2rem;
        color: #28a745;
        margin-bottom: 20px;
    }

    .success-message {
        font-size: 1.2rem;
        margin-bottom: 30px;
    }

    .order-details {
        text-align: left;
        margin-bottom: 30px;
    }

    .order-details h3 {
        font-size: 1.5rem;
        margin-bottom: 15px;
    }

    .order-details p {
        margin-bottom: 10px;
    }

    .btn-continue {
        background: linear-gradient(135deg, #F9A826, #FF512F);
        color: #fff;
        padding: 15px 30px;
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

    .btn-continue:hover {
        background: linear-gradient(135deg, #FF512F, #F9A826);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(249, 168, 38, 0.3);
    }
    </style>
</head>

<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="success-title">Thanh Toán Thành Công!</h1>
        <p class="success-message">Cảm ơn bạn đã mua hàng. Đơn hàng của bạn đã được xử lý thành công.</p>
        
        <div class="order-details">
            <h3>Chi Tiết Đơn Hàng</h3>
            <p><strong>Mã đơn hàng:</strong> <?php echo htmlspecialchars($orderId); ?></p>
            <p><strong>Tổng tiền:</strong> <?php echo number_format($totalAmount, 0, ',', '.') . ' VND'; ?></p>
            <p><strong>Ngày đặt hàng:</strong> <?php echo htmlspecialchars($orderDate); ?></p>
        </div>

        <a href="course_catalog.php" class="btn-continue">Tiếp Tục Mua Sắm</a>
    </div>
</body>

</html> 