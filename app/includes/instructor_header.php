<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Hệ Thống Quản Lý Khoá Học'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/instructor.css">
    <?php if (isset($include_chart_js) && $include_chart_js): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    <?php if (isset($page_specific_css)): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/<?php echo $page_specific_css; ?>">
    <?php endif; ?>
    <script>
    <?php if (isset($page_specific_script)): ?>
    <?php echo $page_specific_script; ?>
    <?php endif; ?>
    </script>
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
                <?php if (isset($unreadNotifs) && $unreadNotifs > 0): ?>
                <span class="badge"><?php echo $unreadNotifs; ?></span>
                <?php endif; ?>
            </div>
            <button class="dark-mode-toggle" id="mode-toggle">
                <i class="fas fa-moon"></i>
            </button>
            <div class="teacher-name">Xin chào, <strong><?php echo isset($username) ? htmlspecialchars($username) : 'Giảng viên'; ?></strong></div>
            <button type="button" class="logout-btn" onclick="handleLogout()">
                <i class="fas fa-sign-out-alt"></i> Đăng Xuất
            </button>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/instructor_dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'instructor_dashboard.php' ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Tổng Quan</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/create_course.php" <?php echo basename($_SERVER['PHP_SELF']) == 'create_course.php' ? 'class="active"' : ''; ?>><i class="fas fa-plus-circle"></i> Thêm Khoá Học</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/manage_lessons.php" <?php echo basename($_SERVER['PHP_SELF']) == 'manage_lessons.php' ? 'class="active"' : ''; ?>><i class="fas fa-book"></i> Quản Lý Bài Giảng</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/student_list.php" <?php echo basename($_SERVER['PHP_SELF']) == 'student_list.php' ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Danh Sách Học Viên</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/assignments.php" <?php echo basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'class="active"' : ''; ?>><i class="fas fa-tasks"></i> Bài Tập & Đánh Giá</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/forum.php" <?php echo basename($_SERVER['PHP_SELF']) == 'forum.php' ? 'class="active"' : ''; ?>><i class="fas fa-comments"></i> Thảo Luận</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/certificates.php" <?php echo basename($_SERVER['PHP_SELF']) == 'certificates.php' ? 'class="active"' : ''; ?>><i class="fas fa-certificate"></i> Chứng Chỉ</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/notifications.php" <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'class="active"' : ''; ?>><i class="fas fa-bell"></i> Thông Báo</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/analytics.php" <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'class="active"' : ''; ?>><i class="fas fa-chart-line"></i> Phân Tích Dữ Liệu</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/earnings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'earnings.php' ? 'class="active"' : ''; ?>><i class="fas fa-money-bill-wave"></i> Thu Nhập</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/settings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'class="active"' : ''; ?>><i class="fas fa-cog"></i> Cài Đặt</a></li>
            <li><a href="<?php echo BASE_URL; ?>app/views/product/support.php" <?php echo basename($_SERVER['PHP_SELF']) == 'support.php' ? 'class="active"' : ''; ?>><i class="fas fa-question-circle"></i> Hỗ Trợ</a></li>
        </ul>
        <div class="sidebar-footer">
            <p>© <?php echo date('Y'); ?> Hệ Thống Quản Lý Khoá Học</p>
        </div>
    </div>

    <div class="main-content"> 