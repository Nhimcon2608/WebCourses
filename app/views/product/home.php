<?php
// Cấu hình cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Load cấu hình
require_once dirname(__DIR__, 3) . '/app/config/config.php';

// Khởi tạo session
session_start();

$_SESSION['redirect_count'] = 0;

// Kết nối database và khởi tạo controllers
require_once ROOT_DIR . '/app/config/connect.php';
require_once ROOT_DIR . '/app/controllers/ReviewController.php';
require_once ROOT_DIR . '/app/controllers/ChatbotController.php';
require_once ROOT_DIR . '/app/controllers/AuthController.php';
require_once ROOT_DIR . '/app/controllers/CourseController.php';
require_once ROOT_DIR . '/app/controllers/CategoryController.php';

$db = $conn; // Giả sử $conn từ connect.php
$reviewController = new ReviewController($db);
$chatbotController = new ChatbotController($db);
$authController = new AuthController($db);
$courseController = new CourseController($db);
$categoryController = new CategoryController($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['review_submit'])) {
        $reviewController->submitReview();
    } elseif (isset($_POST['register'])) {
        $authController->register();
    } elseif (isset($_POST['login'])) {
        $authController->login();
    } elseif (isset($_POST['forgot_password'])) {
        $authController->forgotPassword();
    } elseif (isset($_POST['reset-password'])) {
        $authController->resetPassword();
    }
    exit();
}

// Lấy dữ liệu từ controllers
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;

// Lấy danh sách khóa học
try {
    $courses = ($userRole === 'student' || !$isLoggedIn) ? $courseController->getCourses() : [];
    $categories = $categoryController->getCategories();
    $reviews = $reviewController->getReviews();
} catch (Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $courses = [];
    $categories = [];
    $reviews = [];
}

// Lấy thông báo từ session
$registerError = $_SESSION['registerError'] ?? null;
$registerSuccess = $_SESSION['registerSuccess'] ?? null;
$loginError = $_SESSION['loginError'] ?? null;
$loginSuccess = $_SESSION['loginSuccess'] ?? null;
$forgotError = $_SESSION['forgotError'] ?? null;
$forgotSuccess = $_SESSION['forgotSuccess'] ?? null;
$resetError = $_SESSION['resetError'] ?? null;
$resetSuccess = $_SESSION['resetSuccess'] ?? null;
$reviewError = $_SESSION['reviewError'] ?? null;
$reviewSuccess = $_SESSION['reviewSuccess'] ?? null;

// Xóa thông báo sau khi sử dụng
unset($_SESSION['registerError'], $_SESSION['registerSuccess'], $_SESSION['loginError'], 
      $_SESSION['loginSuccess'], $_SESSION['forgotError'], $_SESSION['forgotSuccess'], $_SESSION['resetError'], 
      $_SESSION['resetSuccess'], $_SESSION['reviewError'], $_SESSION['reviewSuccess']);

// Tạo CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

error_log("Đang chuyển hướng đến home.php");

// Lưu trạng thái form vào session nếu cần
if (isset($_GET['action'])) {
    $_SESSION['auth_form'] = $_GET['action'];
}

// Hiển thị thông báo lỗi/thành công và tự động mở modal khi cần
$showModal = false;
$modalContent = 'login'; // Mặc định hiển thị form login

// Chỉ hiển thị modal khi người dùng chưa đăng nhập
if (!$isLoggedIn) {
    // Kiểm tra trạng thái để hiển thị modal phù hợp
    if (isset($_SESSION['registerError']) || isset($_SESSION['registerSuccess'])) {
        $showModal = true;
    }

    if (isset($_SESSION['loginError']) || isset($_SESSION['loginSuccess'])) {
        $showModal = true;
    }

    if (isset($_SESSION['forgotError']) || isset($_SESSION['forgotSuccess'])) {
        $showModal = true;
        $modalContent = 'forgot';
    }

    if (isset($_SESSION['resetError']) || isset($_SESSION['resetSuccess'])) {
        $showModal = true;
        $modalContent = 'reset';
    }

    // Kiểm tra biến session cho xác minh mã
    if (isset($_SESSION['verification_code'])) {
        $showModal = true;
        $modalContent = 'verification';
    }

    // Hiển thị modal khi có action trong URL (ví dụ: ?action=login)
    if (isset($_SESSION['auth_form'])) {
        $showModal = true;
        if ($_SESSION['auth_form'] === 'register') {
            $modalContent = 'register';
        } elseif ($_SESSION['auth_form'] === 'forgot') {
            $modalContent = 'forgot';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Học Tập Trực Tuyến - Nâng Cao</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@400;500;600;700&display=swap&text=ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789ÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝàáâãèéêìíòóôõùúýĂăĐđĨĩŨũƠơƯưẠạẢảẤấẦầẨẩẪẫẬậẮắẰằẲẳẴẵẶặẸẹẺẻẼẽẾếỀềỂểỄễỆệỈỉỊịỌọỎỏỐốỒồỔổỖỗỘộỚớỜờỞởỠỡỢợỤụỦủỨứỪừỬửỮữỰựỲỳỴỵỶỷỸỹ"
        rel="stylesheet">
    <style>
    /* Fallback fonts for Vietnamese text */
    @font-face {
        font-family: 'Montserrat Fallback';
        src: local('Arial');
        size-adjust: 105%;
        ascent-override: 95%;
        descent-override: 25%;
        line-gap-override: normal;
    }

    @font-face {
        font-family: 'Open Sans Fallback';
        src: local('Arial');
        size-adjust: 100%;
        ascent-override: 95%;
        descent-override: 25%;
        line-gap-override: normal;
    }
    </style>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/review.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/chatbot.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>

<body>
    <header>
        <div class="container header-container">
            <div class="logo">EduHub</div>
            <nav>
                <ul>
                    <li><a href="#hero">Trang Chủ</a></li>
                    <li class="has-submenu">
                        <a href="#courses">Khoá Học</a>
                        <ul class="submenu">
                            <?php foreach ($categories as $cat): ?>
                            <li><a href="courses_management.php?category_id=<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li><a href="#reviews">Nhận Xét</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#blog">Blog</a></li>
                    <li><a href="#contact">Liên Hệ</a></li>
                    <li><a href="student_dashboard.php" class="btn btn-start">Bắt đầu ngay</a></li>
                    <?php if ($isLoggedIn): ?>
                    <li><span class="welcome-text">Xin chào,
                            <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
                    <li><a href="<?php echo BASE_URL; ?>auth/logout" class="btn btn-logout">Đăng xuất</a></li>
                    <?php else: ?>
                    <li><a href="#" id="login-btn" class="btn btn-login">Đăng Nhập</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section id="hero">
        <div class="hero-content">
            <h1>Trải Nghiệm Học Tập Hiện Đại</h1>
            <p>Khám phá các khoá học chất lượng qua giao diện tối giản và hiệu ứng động mượt mà</p>
        </div>
        <canvas id="robot"></canvas>
    </section>

    <?php if ($userRole === 'student' || !$isLoggedIn): ?>
    <section id="courses">
        <div class="container">
            <h2>Các Khoá Học Nổi Bật</h2>
            <div class="slider">
                <div class="courses-container">
                    <?php foreach (array_slice($courses, 0, 3) as $course): ?>
                    <div class="course-card">
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p><?php echo htmlspecialchars($course['description']); ?></p>
                        <p>Giảng viên: <?php echo htmlspecialchars($course['instructor_name'] ?? 'Chưa có'); ?></p>
                        <p>Danh mục: <?php echo htmlspecialchars($course['category_name'] ?? 'Chưa có'); ?></p>
                        <?php if ($isLoggedIn && $userRole === 'student'): ?>
                        <a href="enroll.php?course_id=<?php echo $course['course_id']; ?>" class="course-btn">Đăng ký
                            khóa học</a>
                        <?php else: ?>
                        <a href="#" class="course-btn"
                            onclick="alert('Vui lòng đăng nhập với vai trò sinh viên để đăng ký khóa học!')">Đăng ký
                            khóa học</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section id="testimonials">
        <div class="container">
            <h2>Nhận Xét Học Viên</h2>
            <div class="testimonials-container">
                <div class="testimonial">
                    <p>"Khóa học này thực sự đã thay đổi cách tôi học tập. Giao diện hiện đại, dễ sử dụng."</p>
                    <h4>Nguyễn Văn A</h4>
                </div>
                <div class="testimonial">
                    <p>"Tôi yêu thích trải nghiệm học tập tương tác. Mỗi bài học đều được thiết kế tỉ mỉ."</p>
                    <h4>Trần Thị B</h4>
                </div>
                <div class="testimonial">
                    <p>"Một nền tảng tuyệt vời để phát triển kỹ năng mới, phù hợp với mọi lứa tuổi."</p>
                    <h4>Lê Văn C</h4>
                </div>
            </div>
        </div>
    </section>

    <section id="faq">
        <div class="container">
            <h2>Câu Hỏi Thường Gặp</h2>
            <div class="faq-container">
                <div class="faq-item">
                    <h4>Cách đăng ký học như thế nào?</h4>
                    <p>Bạn chỉ cần nhấp vào nút "Đăng ký" ở mỗi khóa học, điền thông tin và làm theo hướng dẫn.</p>
                </div>
                <div class="faq-item">
                    <h4>Nội dung khóa học được cập nhật thường xuyên?</h4>
                    <p>Các khóa học được cập nhật định kỳ để đảm bảo thông tin luôn mới và phù hợp.</p>
                </div>
                <div class="faq-item">
                    <h4>Hỗ trợ kỹ thuật như thế nào?</h4>
                    <p>Chúng tôi cung cấp hỗ trợ trực tuyến 24/7 qua email và chat trực tiếp.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="blog">
        <div class="container">
            <h2>Blog & Tin Tức</h2>
            <div class="blog-container">
                <div class="blog-post">
                    <img src="<?php echo BASE_URL; ?>app/views/uploads/bang tin thang 112023_1.png" alt="">
                    <h3>Bí quyết học tập hiệu quả</h3>
                    <p>Các mẹo và chiến lược giúp bạn tối ưu hóa quá trình học tập.</p>
                    <a href="#" class="blog-btn">Đọc thêm</a>
                </div>
                <div class="blog-post">
                    <img src="<?php echo BASE_URL; ?>app/views/uploads/congnghein3d.jpg" alt="">
                    <h3>Công nghệ in 3D trong giáo dục</h3>
                    <p>Khám phá cách công nghệ 3D thay đổi ngành giáo dục hiện nay.</p>
                    <a href="#" class="blog-btn">Đọc thêm</a>
                </div>
            </div>
        </div>
    </section>

    <section id="contact">
        <div class="container">
            <h2>Liên Hệ</h2>
            <form id="contact-form" method="post" action="<?php echo BASE_URL; ?>contact/processContact">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="submit_contact" value="1">
                <input type="text" name="name" placeholder="Họ và tên" required>
                <input type="email" name="email" placeholder="Email" required>
                <textarea name="message" placeholder="Nội dung tin nhắn" required></textarea>
                <button type="submit">Gửi Tin Nhắn</button>
            </form>
        </div>
    </section>

    <section id="reviews">
        <div class="review-section">
            <h2>Nhận Xét Học Viên</h2>
            <?php if ($reviewError): ?>
            <p style="color:red;"><?php echo $reviewError; ?></p>
            <?php elseif ($reviewSuccess): ?>
            <p style="color:green;"><?php echo $reviewSuccess; ?></p>
            <?php endif; ?>
            <div id="reviews-container">
                <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <p class="review-text">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                    <p class="review-rating"><?php echo str_repeat('★', $review['rating']); ?></p>
                    <p class="review-author">- <?php echo htmlspecialchars($review['author']); ?></p>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p>Chưa có nhận xét nào. Hãy là người đầu tiên chia sẻ ý kiến của bạn!</p>
                <?php endif; ?>
            </div>
            <?php if ($isLoggedIn): ?>
            <div class="review-form">
                <h3>Thêm đánh giá của bạn</h3>
                <form id="reviewForm" method="post" action="<?php echo BASE_URL; ?>review/submitReview">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="review_submit" value="1">
                    <label for="comment">Bình luận:</label>
                    <textarea id="comment" name="comment" rows="4" placeholder="Viết bình luận của bạn..."
                        required></textarea>
                    <label for="rating">Đánh giá sao:</label>
                    <select id="rating" name="rating">
                        <option value="5">5 sao</option>
                        <option value="4">4 sao</option>
                        <option value="3">3 sao</option>
                        <option value="2">2 sao</option>
                        <option value="1">1 sao</option>
                    </select>
                    <button type="submit">Gửi đánh giá</button>
                </form>
            </div>
            <?php else: ?>
            <p>Vui lòng <a href="#" id="login-btn">đăng nhập</a> để thêm đánh giá.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal Đăng nhập/Đăng ký -->
    <?php if (!$isLoggedIn): ?>
    <div id="loginModal" class="modal" style="<?php echo $showModal ? 'display: flex;' : ''; ?>">
        <div class="modal-content">
            <div class="auth-form">
                <span class="close">&times;</span>
                <div class="auth-form-box">
                    <div class="auth-form-wrapper">
                        <?php if (isset($_SESSION['verification_code'])): ?>
                        <form action="<?php echo BASE_URL; ?>auth/resetPassword" method="POST"
                            class="auth-form-content">
                            <h2 class="auth-title">Xác Nhận Mã</h2>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <?php if (isset($_SESSION['resetError'])): ?>
                            <div class="error-message">
                                <?php echo $_SESSION['resetError']; unset($_SESSION['resetError']); ?></div>
                            <?php endif; ?>
                            <div class="auth-input-group">
                                <i class="fas fa-key"></i>
                                <input type="text" name="verification_code" class="auth-input"
                                    placeholder="Nhập mã xác nhận" required>
                            </div>
                            <div class="auth-input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="new_password" class="auth-input" placeholder="Mật khẩu mới"
                                    required minlength="8">
                            </div>
                            <button type="submit" name="reset-password" class="auth-submit">Đặt Lại Mật Khẩu</button>
                            <p class="auth-footer"><a href="#" class="link" id="login-link">Quay lại đăng nhập</a></p>
                        </form>
                        <?php elseif ($modalContent === 'forgot'): ?>
                        <form action="<?php echo BASE_URL; ?>auth/forgotPassword" method="POST"
                            class="auth-form-content">
                            <h2 class="auth-title">Quên Mật Khẩu</h2>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <?php if (isset($_SESSION['forgotError'])): ?>
                            <div class="error-message">
                                <?php echo $_SESSION['forgotError']; unset($_SESSION['forgotError']); ?></div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['forgotSuccess'])): ?>
                            <div class="success-message">
                                <?php echo $_SESSION['forgotSuccess']; unset($_SESSION['forgotSuccess']); ?></div>
                            <?php endif; ?>
                            <div class="auth-input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="forgot_email" class="auth-input" placeholder="Email" required>
                            </div>
                            <button type="submit" name="forgot_password" class="auth-submit">Gửi Mã Xác Nhận</button>
                            <p class="auth-footer"><a href="#" class="link" id="login-link">Quay lại đăng nhập</a></p>
                        </form>
                        <?php else: ?>
                        <form action="<?php echo BASE_URL; ?>auth/login" method="POST" class="auth-form-content"
                            id="login-form">
                            <h2 class="auth-title">Đăng Nhập</h2>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <?php if (isset($_SESSION['loginError'])): ?>
                            <div class="error-message">
                                <?php echo $_SESSION['loginError']; unset($_SESSION['loginError']); ?></div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['loginSuccess'])): ?>
                            <div class="success-message">
                                <?php echo $_SESSION['loginSuccess']; unset($_SESSION['loginSuccess']); ?></div>
                            <?php endif; ?>
                            <div class="auth-input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="username" class="auth-input" placeholder="Tên đăng nhập"
                                    required>
                            </div>
                            <div class="auth-input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" class="auth-input" placeholder="Mật khẩu"
                                    required minlength="8">
                            </div>
                            <button type="submit" name="login" class="auth-submit">Đăng Nhập</button>
                            <p class="auth-footer">Chưa có tài khoản? <a href="#" class="link" id="signup-link">Đăng
                                    ký</a> | <a href="#" class="link" id="forgot-link">Quên mật khẩu?</a></p>
                        </form>
                        <form action="<?php echo BASE_URL; ?>auth/register" method="POST" class="auth-form-content"
                            id="signup-form" style="display:none;">
                            <h2 class="auth-title">Đăng Ký</h2>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <?php if (isset($_SESSION['registerError'])): ?>
                            <div class="error-message">
                                <?php echo $_SESSION['registerError']; unset($_SESSION['registerError']); ?></div>
                            <?php endif; ?>
                            <div class="auth-input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" name="username" class="auth-input" placeholder="Tên đăng nhập"
                                    required>
                            </div>
                            <div class="auth-input-group">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" class="auth-input" placeholder="Email" required>
                            </div>
                            <div class="auth-input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" class="auth-input" placeholder="Mật khẩu"
                                    required minlength="8">
                            </div>
                            <div class="auth-input-group">
                                <i class="fas fa-user-tag"></i>
                                <select name="role" class="auth-input">
                                    <option value="student">Sinh viên</option>
                                    <option value="instructor">Giảng viên</option>
                                </select>
                            </div>
                            <button type="submit" name="register" class="auth-submit">Đăng Ký</button>
                            <p class="auth-footer">Đã có tài khoản? <a href="#" class="link" id="login-link">Đăng
                                    nhập</a></p>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="ai-bubble" class="ai-bubble">
        <div class="ai-logo">
            <svg class="message-icon" viewBox="0 0 1024 1024" fill="currentColor">
                <!-- SVG content giữ nguyên -->
            </svg>
        </div>
        <span class="bubble-text">BotEdu</span>
    </div>

    <div id="chat-widget" class="chat-widget minimized">
        <div class="chat-header">
            BotEdu
            <button id="chat-minimize" class="chat-minimize">×</button>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="message bot-message">
                <div class="gpt-bubble">
                    <svg class="message-icon" viewBox="0 0 1024 1024" fill="currentColor">
                        <!-- SVG content giữ nguyên -->
                    </svg>
                    Chào bạn! Tôi là BotEdu, sẵn sàng hỗ trợ bạn trong học tập trực tuyến.
                    <span class="timestamp" data-timestamp=""></span>
                </div>
            </div>
            <div class="suggestions">
                <button class="suggestion-btn" data-question="Làm thế nào để đăng ký khóa học?">Đăng ký khóa
                    học</button>
                <button class="suggestion-btn" data-question="Tôi có thể tìm tài liệu học ở đâu?">Tìm tài liệu
                    học</button>
                <button class="suggestion-btn" data-question="Làm sao để liên hệ với giáo viên?">Liên hệ giáo
                    viên</button>
                <button class="suggestion-btn" data-question="Tôi cần hỗ trợ kỹ thuật, bạn giúp được không?">Hỗ trợ kỹ
                    thuật</button>
            </div>
        </div>
        <div class="chat-input-area">
            <form id="chat-form" method="post" enctype="multipart/form-data"
                action="<?php echo BASE_URL; ?>chatbot/processChat">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <label for="chat-files" class="upload-label">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path
                            d="M8 1a5 5 0 0 0-5 5v1h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a6 6 0 1 1 12 0v6a2.5 2.5 0 0 1-5 0V8a1 1 0 0 1 1-1h1V6a5 5 0 0 0-5-5z" />
                    </svg>
                    Tải file
                </label>
                <input type="file" id="chat-files" name="files[]" multiple />
                <input type="text" id="chat-input" name="prompt" placeholder="Nhập câu hỏi của bạn ở đây..." />
                <button type="submit" id="chat-send" class="chat-send">
                    <svg class="send-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                    </svg>
                    Gửi
                </button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
        </div>
    </footer>

    <div style="text-align: center; margin-top: 20px;">
        <a href="<?php echo BASE_URL; ?>app/views/product/student_list.php" class="btn">Xem Danh Sách Học Viên</a>
    </div>

    <script type="importmap">
        {
        "imports": {
            "three": "https://unpkg.com/three@0.160.1/build/three.module.js",
            "three/addons/": "https://unpkg.com/three@0.160.1/examples/jsm/"
        }
    }
    </script>
    <script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
    window.hasVerificationCode = <?php echo isset($_SESSION['verification_code']) ? 'true' : 'false'; ?>;
    window.isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

    // Ẩn modal ngay lập tức nếu đã đăng nhập
    if (window.isLoggedIn) {
        const loginModal = document.getElementById('loginModal');
        if (loginModal) loginModal.style.display = 'none';
    }
    </script>
    <script type="module" src="<?php echo BASE_URL; ?>public/js/robot.js"></script>
    <script type="module" src="<?php echo BASE_URL; ?>public/js/script.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/login&&register.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/chatbot.js"></script>
</body>

</html>