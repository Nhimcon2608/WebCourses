<?php
// Giả sử dữ liệu bài tập được lấy từ CSDL hoặc định nghĩa sẵn trong mảng
$exercises = [
    [
        'title' => 'Bài Tập 1: HTML5',
        'description' => 'Tìm hiểu các thẻ mới của HTML5 và áp dụng chúng để xây dựng cấu trúc trang web hiện đại.'
    ],
    [
        'title' => 'Bài Tập 2: CSS3 Cơ Bản',
        'description' => 'Sử dụng CSS3 để định dạng trang web, thiết kế màu sắc, kiểu chữ và bố cục đơn giản.'
    ],
    [
        'title' => 'Bài Tập 3: Flexbox & Grid',
        'description' => 'Thực hành bố cục trang web với Flexbox và CSS Grid để tạo giao diện responsive.'
    ],
    [
        'title' => 'Bài Tập 4: JavaScript Cơ Bản',
        'description' => 'Học cách thao tác DOM, lắng nghe sự kiện và hiển thị nội dung động trên trang.'
    ],
    // Thêm các bài tập khác nếu cần
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Học Tập Trực Tuyến 2025</title>
    <style>
        /* RESET CƠ BẢN */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        /* HEADER & NAVIGATION */
        header {
            background-color: #0d6efd; /* Màu xanh dương */
            padding: 10px 20px;
            color: #fff;
        }
        .nav-menu {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-menu .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .nav-links a {
            color: #fff;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 600;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }

        /* MAIN CONTAINER */
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        /* EXERCISE CARDS */
        .exercise-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .exercise-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .exercise-card:hover {
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .exercise-header {
            background-color: #f1f1f1;
            padding: 10px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .exercise-body {
            padding: 15px;
            flex-grow: 1;
        }
        .exercise-description {
            margin-top: 10px;
            color: #555;
        }
        .exercise-footer {
            padding: 10px 15px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: flex-end;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #0d6efd;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
        }
        .btn:hover {
            background-color: #0b5ed7;
        }

        /* FOOTER */
        footer {
            text-align: center;
            padding: 10px;
            background-color: #0d6efd;
            color: #fff;
            margin-top: 40px;
        }
    </style>
</head>
<body>

    <!-- PHẦN HEADER -->
    <header>
        <div class="nav-menu">
            <div class="logo">Học Tập Trực Tuyến</div>
            <nav class="nav-links">
                <a href="#">Trang Chủ</a>
                <a href="#">Dashboard</a>
                <a href="#">Đăng Xuất</a>
            </nav>
        </div>
    </header>

    <!-- PHẦN DANH SÁCH BÀI TẬP -->
    <div class="container">
        <h2>Danh Sách Bài Tập Mới Nhất 2025</h2>
        
        <div class="exercise-list">
            <!-- Vòng lặp PHP để hiển thị danh sách bài tập -->
            <?php foreach ($exercises as $exercise): ?>
                <div class="exercise-card">
                    <div class="exercise-header">
                        <?php echo $exercise['title']; ?>
                    </div>
                    <div class="exercise-body">
                        <p class="exercise-description">
                            <?php echo $exercise['description']; ?>
                        </p>
                    </div>
                    <div class="exercise-footer">
                        <a href="#" class="btn">Làm bài</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PHẦN CHÂN TRANG -->
    <footer>
        © 2025 Học Tập Trực Tuyến. All Rights Reserved.
    </footer>

</body>
</html> 