<?php
session_start();

// Định nghĩa hằng số
define('BASE_URL', '/WebCourses/');
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// Giả lập kết nối CSDL (thay bằng code kết nối thực tế nếu có)
$conn = null;

// Dummy CourseController
class CourseController {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getCourses($category_id = null) {
        $dummyCourses = [
            [
                'course_id'   => 1,
                'title'       => 'Khóa Học Lập Trình Web',
                'description' => 'Học HTML, CSS, JS, PHP, MySQL để xây dựng các trang web chuyên nghiệp và ứng dụng trực tuyến.',
                'price'       => 1000000,
                'image'       => 'https://source.unsplash.com/400x250/?coding,web',
                'level'       => 'Cơ bản',
                'rating'      => 4.5
            ],
            [
                'course_id'   => 2,
                'title'       => 'Khóa Học Thiết Kế UI/UX',
                'description' => 'Học về thiết kế giao diện, trải nghiệm người dùng và các công cụ thiết kế hiện đại.',
                'price'       => 0,
                'image'       => 'https://source.unsplash.com/400x250/?design,uiux',
                'level'       => 'Nâng cao',
                'rating'      => 4.2
            ],
            [
                'course_id'   => 3,
                'title'       => 'Khóa Học Lập Trình Python',
                'description' => 'Học Python từ cơ bản đến nâng cao, ứng dụng vào khoa học dữ liệu và phát triển web.',
                'price'       => 800000,
                'image'       => 'https://source.unsplash.com/400x250/?python,programming',
                'level'       => 'Cơ bản',
                'rating'      => 4.7
            ],
            [
                'course_id'   => 4,
                'title'       => 'Khóa Học Phân Tích Dữ Liệu',
                'description' => 'Tìm hiểu cách thu thập, phân tích và trực quan hóa dữ liệu một cách chuyên nghiệp.',
                'price'       => 500000,
                'image'       => 'https://source.unsplash.com/400x250/?data,analysis',
                'level'       => 'Trung cấp',
                'rating'      => 4.3
            ],
            [
                'course_id'   => 5,
                'title'       => 'Khóa Học DevOps',
                'description' => 'Học cách triển khai và quản lý hệ thống phần mềm với DevOps và các công cụ CI/CD hiện đại.',
                'price'       => 1200000,
                'image'       => 'https://source.unsplash.com/400x250/?devops,technology',
                'level'       => 'Nâng cao',
                'rating'      => 4.6
            ]

            // Bạn có thể thêm nhiều khóa học khác vào đây...
        ];
        if ($category_id) {
            // Ví dụ: nếu category_id == 1 thì chỉ trả về khóa học đầu tiên, nếu 2 thì khóa học thứ hai
            if ($category_id == 1) {
                return [$dummyCourses[0]];
            } elseif ($category_id == 2) {
                return [$dummyCourses[1]];
            } else {
                return [];
            }
        }
        return $dummyCourses;
    }
}

// Dummy CategoryController
class CategoryController {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getCategories() {
        return [
            ['category_id' => 1, 'name' => 'Lập Trình'],
            ['category_id' => 2, 'name' => 'Thiết Kế'],
            ['category_id' => 3, 'name' => 'Kinh Doanh'],
        ];
    }
}

// Khởi tạo controllers
$courseController = new CourseController($conn);
$categoryController = new CategoryController($conn);

// Lấy category_id từ URL nếu có
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

// Lấy danh sách khóa học theo danh mục (nếu có)
$courses = $courseController->getCourses($category_id);

// Lấy danh sách tất cả danh mục
$categories = $categoryController->getCategories();

// Tiêu đề trang
$pageTitle = "Danh Mục Khóa Học";
if ($category_id) {
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $category_id) {
            $pageTitle = "Khóa Học " . htmlspecialchars($cat['name']);
            break;
        }
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

    .logo:hover {
        animation: bounce 0.8s ease-in-out;
    }

    @keyframes bounce {
        0% {
            transform: scale(1);
        }

        20% {
            transform: scale(1.2);
        }

        40% {
            transform: scale(0.9);
        }

        60% {
            transform: scale(1.1);
        }

        80% {
            transform: scale(0.95);
        }

        100% {
            transform: scale(1);
        }
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

    /* Hero banner */
    .hero-banner {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 15px;
        padding: 60px 40px;
        margin-bottom: 40px;
        color: #fff;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .hero-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(rgba(255, 255, 255, 0.1), transparent);
        transform: rotate(30deg);
        z-index: 1;
    }

    .hero-banner h1 {
        font-family: 'Montserrat', sans-serif;
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 20px;
        letter-spacing: -0.5px;
        position: relative;
        z-index: 2;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .hero-banner p {
        font-size: 1.2rem;
        max-width: 700px;
        margin: 0 auto 30px;
        position: relative;
        z-index: 2;
    }

    /* Category filters */
    .category-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 30px;
        justify-content: center;
    }

    .category-btn {
        background: #fff;
        color: #1e3c72;
        border: 2px solid #1e3c72;
        padding: 10px 20px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        letter-spacing: 0.5px;
    }

    .category-btn:hover,
    .category-btn.active {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3);
    }

    /* Courses grid */
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }

    .course-card {
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        transition: all 0.4s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        opacity: 0;
        transform: translateY(20px);
        animation: fadeUp 0.6s ease forwards;
        animation-delay: calc(0.1s * var(--i));
    }

    .course-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .course-image {
        height: 180px;
        overflow: hidden;
        position: relative;
    }

    .course-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .course-card:hover .course-image img {
        transform: scale(1.1);
    }

    .course-level {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 193, 7, 0.9);
        color: #000;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        z-index: 2;
    }

    .course-content {
        padding: 25px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .course-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: #1e3c72;
        font-family: 'Montserrat', sans-serif;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: color 0.3s ease;
    }

    .course-card:hover .course-title {
        color: #F9A826;
    }

    .course-description {
        color: #555;
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }

    .course-info {
        display: flex;
        justify-content: space-between;
        padding-top: 15px;
        border-top: 1px solid #eee;
        margin-top: auto;
    }

    .course-price {
        font-weight: 700;
        font-size: 1.2rem;
        color: #F9A826;
    }

    .course-rating {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .star {
        color: #FFC107;
    }

    .course-btn {
        display: block;
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        padding: 12px 0;
        text-align: center;
        border-radius: 8px;
        font-weight: 700;
        text-decoration: none;
        margin-top: 20px;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.9rem;
        font-family: 'Montserrat', sans-serif;
        box-shadow: 0 4px 15px rgba(30, 60, 114, 0.2);
    }

    .course-btn:hover {
        background: linear-gradient(135deg, #2a5298, #1e3c72);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(30, 60, 114, 0.3);
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .empty-state i {
        font-size: 5rem;
        color: #ddd;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        font-size: 1.8rem;
        color: #555;
        margin-bottom: 15px;
    }

    .empty-state p {
        color: #777;
        max-width: 500px;
        margin: 0 auto 25px;
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

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive design */
    @media (max-width: 992px) {
        .hero-banner h1 {
            font-size: 2.3rem;
        }

        .hero-banner p {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 768px) {
        nav ul {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .hero-banner {
            padding: 40px 20px;
        }

        .hero-banner h1 {
            font-size: 2rem;
        }

        .hero-banner p {
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .courses-grid {
            grid-template-columns: 1fr;
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
                    <li><a href="course_catalog.php" class="active">Khóa Học</a></li>
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
        <!-- Hero banner -->
        <div class="hero-banner">
            <h1><?php echo $pageTitle; ?></h1>
            <p>Khám phá các khóa học chất lượng cao từ những giảng viên hàng đầu trong nhiều lĩnh vực. Bắt đầu hành
                trình học tập của bạn ngay hôm nay!</p>
        </div>

        <!-- Category filters -->
        <div class="category-filters">
            <a href="course_catalog.php" class="category-btn <?php echo !$category_id ? 'active' : ''; ?>">Tất Cả</a>
            <?php foreach ($categories as $cat): ?>
            <a href="course_catalog.php?category_id=<?php echo $cat['category_id']; ?>"
                class="category-btn <?php echo $category_id == $cat['category_id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Courses Grid -->
        <?php if (count($courses) > 0): ?>
        <div class="courses-grid">
            <?php 
                $i = 0;
                foreach ($courses as $course): 
                    $i++;
                    $courseImage = !empty($course['image']) ? $course['image'] : 'https://via.placeholder.com/400x250/1e3c72/ffffff?text=Khóa+Học';
                    $formattedPrice = number_format($course['price'], 0, ',', '.') . ' VND';
                    if ($course['price'] == 0) {
                        $formattedPrice = 'Miễn phí';
                    }
                ?>
            <div class="course-card" style="--i:<?php echo $i; ?>">
                <div class="course-image">
                    <img src="<?php echo $courseImage; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <div class="course-level"><?php echo htmlspecialchars($course['level'] ?: 'Cơ bản'); ?></div>
                </div>
                <div class="course-content">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p class="course-description">
                        <?php echo htmlspecialchars(substr($course['description'], 0, 150)) . (strlen($course['description']) > 150 ? '...' : ''); ?>
                    </p>
                    <div class="course-info">
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
                    </div>
                    <a href="course_detail.php?course_id=<?php echo $course['course_id']; ?>" class="course-btn">Xem Chi
                        Tiết</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Empty state when no courses found -->
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Không tìm thấy khóa học</h3>
            <p>Không có khóa học nào trong danh mục này hoặc phù hợp với bộ lọc của bạn.</p>
            <a href="course_catalog.php" class="category-btn">Xem tất cả khóa học</a>
        </div>
        <?php endif; ?>
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
                    <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                    <li><a
                            href="course_catalog.php?category_id=<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a>
                    </li>
                    <?php endforeach; ?>
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
        // Đặt độ trễ cho animation của các card khóa học
        const courseCards = document.querySelectorAll('.course-card');
        courseCards.forEach((card, index) => {
            card.style.setProperty('--i', index + 1);
        });
        // Nút cuộn lên đầu trang
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
</body>

</html>