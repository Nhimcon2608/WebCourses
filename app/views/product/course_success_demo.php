<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/WebCourses/');
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simulate logged in user
$user_id = 1;
$username = "Nguyễn Văn A";
$unreadNotifs = 3;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Khóa Học Mới</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/instructor_dashboard.css">
    <style>
        .form-card {
            background: var(--card-light);
            border-radius: 16px;
            box-shadow: var(--shadow-light);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .dark-mode .form-card {
            background: var(--card-dark);
            box-shadow: var(--shadow-dark);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .dark-mode .form-group label {
            color: var(--text-light);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: var(--card-light);
            color: var(--text-dark);
            transition: all 0.3s;
        }
        
        .dark-mode .form-control {
            background: var(--card-dark);
            color: var(--text-light);
            border-color: #444;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(98, 74, 242, 0.2);
        }
        
        .form-control-file {
            padding: 8px 0;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background: #513dd8;
            transform: translateY(-2px);
        }
        
        .form-subtitle {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: var(--primary-color);
        }
        
        .dark-mode .form-subtitle {
            border-bottom-color: #333;
        }
        
        .hint-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        .dark-mode .hint-text {
            color: #999;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="mobile-menu-toggle" id="mobile-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Học Tập</span>
        </div>
        <div class="user-actions">
            <div class="notification-icon">
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotifs > 0): ?>
                <span class="badge"><?php echo $unreadNotifs; ?></span>
                <?php endif; ?>
            </div>
            <button class="mode-toggle" id="mode-toggle">
                <i class="fas fa-moon"></i>
            </button>
            <div class="teacher-name">Xin chào, <strong><?php echo htmlspecialchars($username); ?></strong></div>
            <form method="post" action="<?php echo BASE_URL; ?>app/controllers/logout.php" style="display:inline;">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng Xuất
                </button>
            </form>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/instructor_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tổng Quan</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/create_course.php" class="active"><i class="fas fa-plus-circle"></i> Thêm Khoá Học</a></li>
            <li><a href="#"><i class="fas fa-book"></i> Quản Lý Bài Giảng</a></li>
            <li><a href="#"><i class="fas fa-users"></i> Danh Sách Học Viên</a></li>
            <li><a href="#"><i class="fas fa-tasks"></i> Bài Tập & Đánh Giá</a></li>
            <li><a href="#"><i class="fas fa-comments"></i> Thảo Luận</a></li>
            <li><a href="#"><i class="fas fa-certificate"></i> Chứng Chỉ</a></li>
            <li><a href="#"><i class="fas fa-bell"></i> Thông Báo</a></li>
            <li><a href="#"><i class="fas fa-chart-line"></i> Phân Tích Dữ Liệu</a></li>
            <li><a href="#"><i class="fas fa-money-bill-wave"></i> Thu Nhập</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Cài Đặt</a></li>
            <li><a href="#"><i class="fas fa-question-circle"></i> Hỗ Trợ</a></li>
        </ul>
        <div class="sidebar-footer">
            © 2025 Học Tập Trực Tuyến
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2>Tạo Khóa Học Mới</h2>
        
        <div class="success-message">
            <i class="fas fa-check-circle"></i> Khóa học đã được tạo thành công!
        </div>
        
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-subtitle">Thông Tin Cơ Bản</div>
                
                <div class="form-group">
                    <label for="title">Tiêu Đề Khóa Học</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Ví dụ: Lập Trình PHP Cơ Bản" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Mô Tả Khóa Học</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Mô tả chi tiết về khóa học của bạn..." required></textarea>
                    <div class="hint-text">Viết mô tả hấp dẫn, nêu rõ những gì học viên sẽ học được</div>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Danh Mục</label>
                    <div class="category-row">
                        <div class="category-dropdown">
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">-- Chọn danh mục --</option>
                                <option value="1">Lập trình web</option>
                                <option value="2">Cơ sở dữ liệu</option>
                                <option value="3">Thiết kế UI/UX</option>
                            </select>
                        </div>
                        <button type="button" id="openCategoryModal" class="add-category-btn">
                            <i class="fas fa-plus"></i> Thêm Mới
                        </button>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Giá Khóa Học (VNĐ)</label>
                        <input type="number" id="price" name="price" class="form-control" placeholder="Nhập 0 nếu miễn phí" required>
                    </div>
                
                    <div class="form-group">
                        <label for="level">Cấp Độ</label>
                        <select id="level" name="level" class="form-control" required>
                            <option value="">-- Chọn cấp độ --</option>
                            <option value="Cơ bản">Cơ bản</option>
                            <option value="Trung cấp">Trung cấp</option>
                            <option value="Nâng cao">Nâng cao</option>
                            <option value="Tất cả">Tất cả trình độ</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="language">Ngôn Ngữ Giảng Dạy</label>
                        <select id="language" name="language" class="form-control">
                            <option value="Tiếng Việt">Tiếng Việt</option>
                            <option value="Tiếng Anh">Tiếng Anh</option>
                            <option value="Song ngữ">Song ngữ (Việt - Anh)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tags">Từ Khóa (Tags)</label>
                        <input type="text" id="tags" name="tags" class="form-control" placeholder="Ví dụ: php, web, cơ bản, lập trình">
                        <div class="hint-text">Các từ khóa cách nhau bằng dấu phẩy</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Hình Ảnh Khóa Học</label>
                    <input type="file" id="image" name="image" class="form-control form-control-file" accept="image/*" required>
                    <div class="hint-text">Kích thước khuyến nghị: 1280x720 pixel (tỷ lệ 16:9)</div>
                </div>
                
                <div class="form-subtitle">Thông Tin Chi Tiết</div>
                
                <div class="form-group">
                    <label for="learning_outcomes">Kết Quả Học Tập</label>
                    <textarea id="learning_outcomes" name="learning_outcomes" class="form-control" placeholder="Liệt kê những gì học viên sẽ học được sau khóa học..."></textarea>
                    <div class="hint-text">Liệt kê dưới dạng danh sách, mỗi dòng một kết quả học tập</div>
                </div>
                
                <div class="form-group">
                    <label for="requirements">Yêu Cầu Tiên Quyết</label>
                    <textarea id="requirements" name="requirements" class="form-control" placeholder="Những kiến thức hoặc kỹ năng học viên cần có trước khi tham gia khóa học..."></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus-circle"></i> Tạo Khóa Học
                </button>
            </form>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>public/js/instructor_dashboard.js"></script>
</body>
</html> 