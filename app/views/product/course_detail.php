<?php
session_start();

// Định nghĩa hằng số
define('BASE_URL', '/WebCourses/');

// Lấy course_id từ URL
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Dummy CourseController để lấy thông tin khóa học
class CourseController {
    private $conn;
    public function __construct($conn = null) {
        $this->conn = $conn;
    }
    public function getCourseById($course_id) {
        $dummyCourses = [
            1 => [
                'course_id'     => 1,
                'title'         => 'Khóa Học Lập Trình Web',
                'description'   => 'Học HTML, CSS, JS, PHP, MySQL để xây dựng các trang web chuyên nghiệp và ứng dụng trực tuyến.',
                'price'         => 1000000,
                'image'         => 'https://source.unsplash.com/400x250/?coding,web',
                'level'         => 'Cơ bản',
                'rating'        => 4.5,
                'instructor'    => 'Nguyễn Văn A',
                'duration'      => '10 tuần',
                'lessons'       => 24,
                'students'      => 1250,
                'full_description' => 'Khóa học lập trình web toàn diện giúp bạn nắm vững các công nghệ web hiện đại như HTML5, CSS3, JavaScript, PHP và MySQL. Bạn sẽ học cách xây dựng trang web responsive, tương tác với cơ sở dữ liệu, và triển khai các ứng dụng web đầy đủ chức năng. Khóa học bao gồm các bài tập thực hành và dự án thực tế để củng cố kiến thức.',
                'outcomes'      => [
                    'Xây dựng website responsive từ đầu đến cuối',
                    'Sử dụng thành thạo HTML5, CSS3 và JavaScript',
                    'Phát triển back-end với PHP và MySQL',
                    'Triển khai website lên hosting thực tế'
                ]
            ],
            2 => [
                'course_id'     => 2,
                'title'         => 'Khóa Học Thiết Kế UI/UX',
                'description'   => 'Học về thiết kế giao diện, trải nghiệm người dùng và các công cụ thiết kế hiện đại.',
                'price'         => 0,
                'image'         => 'https://source.unsplash.com/400x250/?design,uiux',
                'level'         => 'Nâng cao',
                'rating'        => 4.2,
                'instructor'    => 'Trần Thị B',
                'duration'      => '8 tuần',
                'lessons'       => 20,
                'students'      => 980,
                'full_description' => 'Khóa học thiết kế UI/UX cung cấp kiến thức toàn diện về nguyên tắc thiết kế giao diện người dùng và trải nghiệm người dùng. Bạn sẽ học cách sử dụng các công cụ thiết kế chuyên nghiệp như Figma, Adobe XD, và Sketch để tạo ra các thiết kế đẹp mắt và dễ sử dụng. Khóa học kết hợp lý thuyết với các dự án thực tế để giúp bạn xây dựng portfolio ấn tượng.',
                'outcomes'      => [
                    'Thiết kế giao diện người dùng hấp dẫn',
                    'Tạo wireframes và prototypes tương tác',
                    'Thực hiện nghiên cứu và kiểm thử người dùng',
                    'Sử dụng thành thạo Figma và Adobe XD'
                ]
            ],
            3 => [
                'course_id'     => 3,
                'title'         => 'Khóa Học Lập Trình Python',
                'description'   => 'Học Python từ cơ bản đến nâng cao, ứng dụng vào khoa học dữ liệu và phát triển web.',
                'price'         => 800000,
                'image'         => 'https://source.unsplash.com/400x250/?python,programming',
                'level'         => 'Cơ bản',
                'rating'        => 4.7,
                'instructor'    => 'Lê Văn C',
                'duration'      => '12 tuần',
                'lessons'       => 30,
                'students'      => 1580,
                'full_description' => 'Khóa học Python toàn diện giúp bạn tiếp cận ngôn ngữ lập trình phổ biến nhất hiện nay. Bạn sẽ học từ cú pháp cơ bản đến các khái niệm nâng cao như lập trình hướng đối tượng, xử lý dữ liệu, và phát triển web với Django. Khóa học còn giới thiệu về trí tuệ nhân tạo và machine learning với Python.',
                'outcomes'      => [
                    'Nắm vững cú pháp và cấu trúc dữ liệu Python',
                    'Phát triển ứng dụng web với Django',
                    'Phân tích dữ liệu với Pandas và NumPy',
                    'Xây dựng các dự án thực tế với Python'
                ]
            ],
            4 => [
                'course_id'     => 4,
                'title'         => 'Khóa Học Phân Tích Dữ Liệu',
                'description'   => 'Tìm hiểu cách thu thập, phân tích và trực quan hóa dữ liệu một cách chuyên nghiệp.',
                'price'         => 500000,
                'image'         => 'https://source.unsplash.com/400x250/?data,analysis',
                'level'         => 'Trung cấp',
                'rating'        => 4.3,
                'instructor'    => 'Phạm Thị D',
                'duration'      => '8 tuần',
                'lessons'       => 18,
                'students'      => 750,
                'full_description' => 'Khóa học phân tích dữ liệu giúp bạn nắm vững các kỹ thuật thu thập, làm sạch, phân tích và trực quan hóa dữ liệu. Bạn sẽ học cách sử dụng các công cụ như Python, SQL, Excel, và các thư viện như Pandas, NumPy, Matplotlib để xử lý dữ liệu hiệu quả. Khóa học tập trung vào các ví dụ thực tế và dự án từ nhiều ngành khác nhau.',
                'outcomes'      => [
                    'Thu thập và làm sạch dữ liệu hiệu quả',
                    'Phân tích dữ liệu với SQL và Python',
                    'Tạo biểu đồ và dashboard trực quan',
                    'Rút ra insight kinh doanh từ dữ liệu'
                ]
            ],
            5 => [
                'course_id'     => 5,
                'title'         => 'Khóa Học DevOps',
                'description'   => 'Học cách triển khai và quản lý hệ thống phần mềm với DevOps và các công cụ CI/CD hiện đại.',
                'price'         => 1200000,
                'image'         => 'https://source.unsplash.com/400x250/?devops,technology',
                'level'         => 'Nâng cao',
                'rating'        => 4.6,
                'instructor'    => 'Hoàng Văn E',
                'duration'      => '12 tuần',
                'lessons'       => 28,
                'students'      => 680,
                'full_description' => 'Khóa học DevOps cung cấp kiến thức toàn diện về quy trình, công cụ và triết lý DevOps. Bạn sẽ học cách tự động hóa quy trình phát triển, kiểm thử và triển khai phần mềm sử dụng Docker, Kubernetes, Jenkins, và các công cụ CI/CD hiện đại khác. Khóa học tập trung vào thực hành và giúp bạn xây dựng pipeline CI/CD hoàn chỉnh.',
                'outcomes'      => [
                    'Xây dựng và quản lý infrastructure với Terraform',
                    'Triển khai ứng dụng với Docker và Kubernetes',
                    'Thiết lập pipeline CI/CD với Jenkins',
                    'Giám sát và quản lý hệ thống với ELK stack'
                ]
            ]
        ];

        return isset($dummyCourses[$course_id]) ? $dummyCourses[$course_id] : null;
    }
}

// Tạo instance của CourseController
$courseController = new CourseController();

// Lấy thông tin khóa học
$course = $courseController->getCourseById($course_id);

// Nếu không tìm thấy khóa học, chuyển hướng đến trang lỗi
if (!$course) {
    header("Location: error.php?message=Page%20not%20found");
    exit;
}

// Format giá
$formattedPrice = number_format($course['price'], 0, ',', '.') . ' VND';
if ($course['price'] == 0) {
    $formattedPrice = 'Miễn phí';
}

// Tiêu đề trang
$pageTitle = htmlspecialchars($course['title']);

// Xử lý khi nhận lệnh thêm vào giỏ hàng
if (isset($_GET['add_to_cart']) && $_GET['add_to_cart'] == 1) {
    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Kiểm tra xem khóa học đã có trong giỏ hàng chưa
    if (!isset($_SESSION['cart'][$course_id])) {
        // Thêm khóa học vào giỏ hàng với thông tin cần thiết
        $_SESSION['cart'][$course_id] = [
            'title' => $course['title'],
            'price' => $course['price'],
            'image' => $course['image'],
            'instructor' => $course['instructor']
        ];
        
        // Hiển thị thông báo thành công
        $cartMessage = "Đã thêm khóa học vào giỏ hàng!";
        $cartMessageType = "success";
    } else {
        // Hiển thị thông báo đã có trong giỏ hàng
        $cartMessage = "Khóa học này đã có trong giỏ hàng của bạn!";
        $cartMessageType = "info";
    }
}
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        /* Course detail */
        .course-detail-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        .course-content {
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .course-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1e3c72;
            margin-bottom: 20px;
            font-family: 'Montserrat', sans-serif;
            line-height: 1.3;
        }

        .course-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .course-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 0.95rem;
        }

        .course-image {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .course-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .course-description {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #444;
            margin-bottom: 30px;
        }

        .course-outcomes {
            margin-bottom: 30px;
        }

        .course-outcomes h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1e3c72;
        }

        .outcomes-list {
            list-style: none;
        }

        .outcomes-list li {
            position: relative;
            padding-left: 30px;
            margin-bottom: 15px;
            font-size: 1.05rem;
        }

        .outcomes-list li:before {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            color: #4CAF50;
        }

        /* Sidebar */
        .course-sidebar {
            position: sticky;
            top: 100px;
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }

        .course-price {
            font-size: 2rem;
            font-weight: 800;
            color: #F9A826;
            margin-bottom: 20px;
            text-align: center;
        }

        .course-rating {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
        }

        .star {
            color: #FFC107;
        }

        .course-stats {
            margin-bottom: 30px;
        }

        .course-stat-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #eee;
        }

        .stat-label {
            color: #666;
        }

        .stat-value {
            font-weight: 700;
            color: #333;
        }

        .enroll-btn {
            display: block;
            background: linear-gradient(135deg, #F9A826, #FF512F);
            color: #fff;
            padding: 15px 0;
            text-align: center;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 1rem;
            font-family: 'Montserrat', sans-serif;
            box-shadow: 0 4px 15px rgba(249, 168, 38, 0.3);
        }

        .enroll-btn:hover {
            background: linear-gradient(135deg, #FF512F, #F9A826);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(249, 168, 38, 0.4);
        }

        .wish-btn {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            background: transparent;
            color: #666;
            padding: 12px 0;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid #ddd;
            transition: all 0.3s ease;
        }

        .wish-btn:hover {
            background: #f5f5f5;
            color: #333;
            border-color: #ccc;
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
            .course-detail-container {
                grid-template-columns: 1fr;
            }
            
            .course-sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            nav ul {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .course-title {
                font-size: 1.8rem;
            }
            
            .course-image {
                height: 300px;
            }
        }

        .cart-message {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease, fadeOut 0.5s ease 3s forwards;
        }

        .cart-message.success {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
        }

        .cart-message.info {
            background: linear-gradient(135deg, #2196F3, #0D47A1);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
                visibility: hidden;
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
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
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
        <div class="course-detail-container">
            <!-- Course content -->
            <div class="course-content">
                <h1 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h1>
                
                <div class="course-meta">
                    <div class="course-meta-item">
                        <i class="fas fa-user"></i>
                        <span>Giảng viên: <?php echo htmlspecialchars($course['instructor']); ?></span>
                    </div>
                    <div class="course-meta-item">
                        <i class="fas fa-signal"></i>
                        <span>Cấp độ: <?php echo htmlspecialchars($course['level']); ?></span>
                    </div>
                    <div class="course-meta-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo number_format($course['students']); ?> học viên</span>
                    </div>
                </div>
                
                <div class="course-image">
                    <img src="<?php echo $course['image']; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                </div>
                
                <div class="course-description">
                    <?php echo htmlspecialchars($course['full_description']); ?>
                </div>
                
                <div class="course-outcomes">
                    <h3>Bạn sẽ học được gì?</h3>
                    <ul class="outcomes-list">
                        <?php foreach ($course['outcomes'] as $outcome): ?>
                        <li><?php echo htmlspecialchars($outcome); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Course sidebar -->
            <div class="course-sidebar">
                <div class="course-price"><?php echo $formattedPrice; ?></div>
                
                <div class="course-rating">
                    <?php 
                    $rating = floatval($course['rating'] ?: 0);
                    for ($star = 1; $star <= 5; $star++): 
                        if ($star <= $rating) {
                            echo '<i class="fas fa-star star"></i>';
                        } elseif ($star - 0.5 <= $rating) {
                            echo '<i class="fas fa-star-half-alt star"></i>';
                        } else {
                            echo '<i class="far fa-star star"></i>';
                        }
                    endfor; 
                    ?>
                    <span>(<?php echo $rating; ?>)</span>
                </div>
                
                <div class="course-stats">
                    <div class="course-stat-item">
                        <div class="stat-label">Thời lượng</div>
                        <div class="stat-value"><?php echo htmlspecialchars($course['duration']); ?></div>
                    </div>
                    <div class="course-stat-item">
                        <div class="stat-label">Số bài học</div>
                        <div class="stat-value"><?php echo $course['lessons']; ?> bài</div>
                    </div>
                    <div class="course-stat-item">
                        <div class="stat-label">Cấp độ</div>
                        <div class="stat-value"><?php echo htmlspecialchars($course['level']); ?></div>
                    </div>
                    <div class="course-stat-item">
                        <div class="stat-label">Chứng chỉ</div>
                        <div class="stat-value">Có</div>
                    </div>
                    <div class="course-stat-item">
                        <div class="stat-label">Truy cập</div>
                        <div class="stat-value">Trọn đời</div>
                    </div>
                </div>
                
                <?php
                // Kiểm tra xem khóa học đã có trong giỏ hàng chưa
                $inCart = isset($_SESSION['cart'][$course_id]);
                ?>
                <?php if (!$inCart): ?>
                <a href="?course_id=<?php echo $course_id; ?>&add_to_cart=1" class="enroll-btn">Thêm vào giỏ hàng</a>
                <?php else: ?>
                <a href="cart.php" class="enroll-btn">Xem giỏ hàng</a>
                <?php endif; ?>
                <a href="#" class="wish-btn"><i class="far fa-heart"></i> Thêm vào yêu thích</a>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Scroll to top button
        const scrollToTop = document.createElement('div');
        scrollToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollToTop.style.position = 'fixed';
        scrollToTop.style.bottom = '30px';
        scrollToTop.style.right = '30px';
        scrollToTop.style.width = '50px';
        scrollToTop.style.height = '50px';
        scrollToTop.style.borderRadius = '50%';
        scrollToTop.style.backgroundColor = '#1e3c72';
        scrollToTop.style.color = '#fff';
        scrollToTop.style.display = 'flex';
        scrollToTop.style.justifyContent = 'center';
        scrollToTop.style.alignItems = 'center';
        scrollToTop.style.cursor = 'pointer';
        scrollToTop.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.2)';
        scrollToTop.style.opacity = '0';
        scrollToTop.style.transition = 'all 0.3s ease';
        scrollToTop.style.visibility = 'hidden';
        scrollToTop.style.zIndex = '999';
        document.body.appendChild(scrollToTop);

        scrollToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTop.style.opacity = '1';
                scrollToTop.style.visibility = 'visible';
            } else {
                scrollToTop.style.opacity = '0';
                scrollToTop.style.visibility = 'hidden';
            }
        });
    });
    </script>

    <?php if (isset($cartMessage)): ?>
    <div class="cart-message <?php echo $cartMessageType; ?>">
        <?php echo htmlspecialchars($cartMessage); ?>
    </div>
    <?php endif; ?>
</body>

</html> 