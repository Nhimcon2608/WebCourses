<?php
// Giả sử bạn có mảng khóa học như sau (thay bằng truy vấn DB nếu cần)
$courses = [
    [
        "title"       => "Khóa học Lập trình Web",
        "description" => "Tìm hiểu về HTML, CSS, JavaScript và các framework phổ biến...",
        "image"       => "https://source.unsplash.com/400x300/?coding,computer"
    ],
    [
        "title"       => "Thiết kế UI/UX",
        "description" => "Nắm vững kiến thức về trải nghiệm người dùng, thiết kế giao diện...",
        "image"       => "https://source.unsplash.com/400x300/?design,uiux"
    ],
    [
        "title"       => "Phân tích Dữ liệu",
        "description" => "Học cách thu thập, xử lý và phân tích dữ liệu với Python, R, SQL...",
        "image"       => "https://source.unsplash.com/400x300/?data,analysis"
    ],
    [
        "title"       => "Marketing Kỹ thuật số",
        "description" => "Khám phá các kênh digital marketing, SEO, SEM, Email Marketing...",
        "image"       => "https://source.unsplash.com/400x300/?marketing,business"
    ],
    [
        "title"       => "Đầu tư Tài chính",
        "description" => "Nắm vững kiến thức về cổ phiếu, trái phiếu, quỹ và quản lý rủi ro...",
        "image"       => "https://source.unsplash.com/400x300/?finance,stock"
    ]
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>Demo Danh Mục Khóa Học (PHP)</title>
  <!-- Google Font + CSS thuần -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Noto+Sans:wght@400;500;600;700&display=swap&text=ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789ÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝàáâãèéêìíòóôõùúýĂăĐđĨĩŨũƠơƯưẠạẢảẤấẦầẨẩẪẫẬậẮắẰằẲẳẴẵẶặẸẹẺẻẼẽẾếỀềỂểỄễỆệỈỉỊịỌọỎỏỐốỒồỔổỖỗỘộỚớỜờỞởỠỡỢợỤụỦủỨứỪừỬửỮữỰựỲỳỴỵỶỷỸỹ" rel="stylesheet">
  <style>
    :root {
      --heading-font: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
      --body-font: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    * {
      margin: 0; 
      padding: 0; 
      box-sizing: border-box;
    }
    
    body {
      font-family: var(--body-font);
      background: #f9f9f9;
      color: #333;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      text-rendering: optimizeLegibility;
      font-feature-settings: "kern" 1, "liga" 1, "calt" 1;
      font-variant-ligatures: no-common-ligatures;
      font-synthesis: none;
      font-size: 16px;
    }
    
    /* Phần hero header */
    .hero {
      background: linear-gradient(135deg, #5e60ce, #64dfdf);
      padding: 50px 20px;
      text-align: center;
      color: #fff;
      border-bottom-left-radius: 50px;
      border-bottom-right-radius: 50px;
    }
    
    .hero h1 {
      font-size: 2.5rem;
      margin-bottom: 15px;
      font-family: var(--heading-font);
      font-weight: 700;
      letter-spacing: -0.01em;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      line-height: 1.2;
    }
    
    .hero p {
      font-size: 1.1rem;
      opacity: 0.95;
      margin-bottom: 25px;
      line-height: 1.5;
      font-weight: 400;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }
    
    /* Phần tìm kiếm */
    .search-container {
      margin: -30px auto 30px auto;
      text-align: center;
      max-width: 600px;
      padding: 0 20px;
    }
    
    .search-box {
      display: flex;
      justify-content: center;
      margin-top: 0.5rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      background: white;
    }
    
    .search-box input {
      flex: 1;
      padding: 15px 20px;
      border: 1px solid #e0e0e0;
      border-right: none;
      border-radius: 8px 0 0 8px;
      outline: none;
      font-size: 1rem;
      font-family: var(--body-font);
      transition: all 0.3s ease;
      color: #333;
    }
    
    .search-box input:focus {
      border-color: #5e60ce;
      box-shadow: 0 0 0 1px rgba(94, 96, 206, 0.2);
    }
    
    .search-box button {
      padding: 15px 25px;
      border: none;
      background: #5e60ce;
      color: #fff;
      cursor: pointer;
      font-weight: 600;
      font-family: var(--heading-font);
      border-radius: 0 8px 8px 0;
      transition: all 0.3s ease;
      letter-spacing: 0.3px;
    }
    
    .search-box button:hover {
      background: #4b4fab;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(75, 79, 171, 0.3);
    }
    
    /* Phần danh sách khóa học */
    .course-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px 60px;
    }
    
    .course-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      height: 100%;
      display: flex;
      flex-direction: column;
      border: 1px solid rgba(0, 0, 0, 0.04);
    }
    
    .course-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
      border-color: rgba(94, 96, 206, 0.2);
    }
    
    .course-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      transition: transform 0.6s ease;
    }
    
    .course-card:hover img {
      transform: scale(1.08);
    }
    
    .course-card-content {
      padding: 25px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    
    .course-card-content h3 {
      font-size: 1.4rem;
      margin-bottom: 12px;
      color: #5e60ce;
      font-family: var(--heading-font);
      font-weight: 700;
      line-height: 1.3;
    }
    
    .course-card-content p {
      font-size: 1rem;
      line-height: 1.6;
      margin-bottom: 20px;
      color: #555;
      flex: 1;
    }
    
    .course-card-content .btn {
      display: inline-block;
      padding: 12px 20px;
      border-radius: 8px;
      background: #64dfdf;
      color: #fff;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      text-align: center;
      font-family: var(--heading-font);
      box-shadow: 0 3px 8px rgba(100, 223, 223, 0.4);
      margin-top: auto;
      letter-spacing: 0.3px;
    }
    
    .course-card-content .btn:hover {
      background: #5e60ce;
      transform: translateY(-3px);
      box-shadow: 0 5px 12px rgba(94, 96, 206, 0.4);
    }

    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(25px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .course-card {
      animation: fadeIn 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275) backwards;
    }

    .course-card:nth-child(1) { animation-delay: 0.1s; }
    .course-card:nth-child(2) { animation-delay: 0.2s; }
    .course-card:nth-child(3) { animation-delay: 0.3s; }
    .course-card:nth-child(4) { animation-delay: 0.4s; }
    .course-card:nth-child(5) { animation-delay: 0.5s; }

    /* Responsive */
    @media (max-width: 768px) {
      .hero h1 {
        font-size: 2rem;
      }
      .hero p {
        font-size: 1rem;
      }
      .course-list {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
      }
    }

    @media (max-width: 480px) {
      .hero {
        padding: 40px 15px;
        border-bottom-left-radius: 35px;
        border-bottom-right-radius: 35px;
      }
      .search-container {
        margin: -25px auto 25px auto;
      }
      .course-list {
        grid-template-columns: 1fr;
        padding: 0 15px 40px;
      }
    }

    /* Font fix for Vietnamese */
    @supports (font-variant-east-asian: jis78) {
      body, button, input {
        font-language-override: "VIT";
      }
    }
  </style>
</head>
<body>

  <!-- Hero Section -->
  <div class="hero">
    <h1>Danh Mục Khóa Học (PHP)</h1>
    <p>Khám phá các khóa học hấp dẫn. Xem ngay và bắt đầu hành trình học tập của bạn!</p>
  </div>

  <!-- Search Section -->
  <div class="search-container">
    <div class="search-box">
      <input type="text" placeholder="Tìm kiếm khóa học..." />
      <button>Tìm kiếm</button>
    </div>
  </div>

  <!-- Course List -->
  <div class="course-list">
    <?php foreach ($courses as $course): ?>
      <div class="course-card">
        <img src="<?php echo htmlspecialchars($course['image']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
        <div class="course-card-content">
          <h3><?php echo htmlspecialchars($course['title']); ?></h3>
          <p><?php echo htmlspecialchars($course['description']); ?></p>
          <a href="#" class="btn">Xem chi tiết</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</body>
</html> 