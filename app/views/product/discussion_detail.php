<?php
// discussion_detail.php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  $_SESSION['loginError'] = "Vui lòng đăng nhập để xem chi tiết thảo luận.";
  header("Location: " . BASE_URL . "app/views/product/home.php");
  exit();
}

// Kiểm tra ID thảo luận
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: forum.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$discussion_id = intval($_GET['id']);

// Lấy thông tin chủ đề thảo luận
if ($role == 'student') {
  $stmt = $conn->prepare("
    SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
           u.username, u.user_id as author_id, c.title as course_title, c.course_id
    FROM discussion d
    JOIN users u ON d.user_id = u.user_id
    JOIN courses c ON d.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE d.discussion_id = ? AND e.user_id = ? AND e.status = 'active'
    LIMIT 1
  ");
  $stmt->bind_param("ii", $discussion_id, $user_id);
} else if ($role == 'instructor') {
  $stmt = $conn->prepare("
    SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
           u.username, u.user_id as author_id, c.title as course_title, c.course_id
    FROM discussion d
    JOIN users u ON d.user_id = u.user_id
    JOIN courses c ON d.course_id = c.course_id
    WHERE d.discussion_id = ? AND c.instructor_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("ii", $discussion_id, $user_id);
} else { // admin
  $stmt = $conn->prepare("
    SELECT d.discussion_id, d.title, d.content, d.created_at, d.updated_at,
           u.username, u.user_id as author_id, c.title as course_title, c.course_id
    FROM discussion d
    JOIN users u ON d.user_id = u.user_id
    JOIN courses c ON d.course_id = c.course_id
    WHERE d.discussion_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("i", $discussion_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Kiểm tra nếu không tìm thấy thảo luận hoặc sinh viên không có quyền xem
if ($result->num_rows == 0) {
  header("Location: forum.php");
  exit();
}

$discussion = $result->fetch_assoc();
$is_author = ($discussion['author_id'] == $user_id);

// Lấy danh sách bình luận cho chủ đề
$commentsStmt = $conn->prepare("
  SELECT c.comment_id, c.content, c.created_at, c.updated_at, c.status,
         u.username, u.user_id as author_id, u.role as author_role
  FROM comments c
  JOIN users u ON c.user_id = u.user_id
  WHERE c.discussion_id = ? AND (c.status = 'approved' OR c.user_id = ? OR ? IN ('admin', 'instructor'))
  ORDER BY c.created_at ASC
");
$commentsStmt->bind_param("iis", $discussion_id, $user_id, $role);
$commentsStmt->execute();
$comments = $commentsStmt->get_result();

// Xử lý khi thêm bình luận mới
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
  $content = trim($_POST['comment_content']);
  
  if (empty($content)) {
    $error = "Vui lòng nhập nội dung bình luận.";
  } else {
    // Tự động chấp thuận các bình luận từ admin và giảng viên
    $status = ($role == 'admin' || $role == 'instructor') ? 'approved' : 'pending';
    
    // Kiểm tra quyền truy cập thảo luận
    $insertStmt = $conn->prepare("
      INSERT INTO comments (discussion_id, user_id, content, created_at, status)
      VALUES (?, ?, ?, NOW(), ?)
    ");
    $insertStmt->bind_param("iiss", $discussion_id, $user_id, $content, $status);
    
    if ($insertStmt->execute()) {
      if ($status == 'approved') {
        $message = "Đã thêm bình luận thành công!";
      } else {
        $message = "Bình luận của bạn đã được gửi và đang chờ phê duyệt.";
      }
      
      // Refresh trang để hiển thị bình luận mới (nếu được chấp thuận)
      header("Location: discussion_detail.php?id=$discussion_id&success=1");
      exit();
    } else {
      $error = "Không thể thêm bình luận. Vui lòng thử lại.";
    }
  }
}

// Xử lý phê duyệt hoặc từ chối bình luận (chỉ cho admin và giảng viên)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_comment']) && ($role == 'admin' || $role == 'instructor')) {
  $comment_id = intval($_POST['comment_id']);
  $action = $_POST['action']; // 'approve' hoặc 'reject'
  
  $status = ($action == 'approve') ? 'approved' : 'rejected';
  
  $updateStmt = $conn->prepare("
    UPDATE comments SET status = ? WHERE comment_id = ?
  ");
  $updateStmt->bind_param("si", $status, $comment_id);
  
  if ($updateStmt->execute()) {
    $message = ($action == 'approve') ? "Đã phê duyệt bình luận." : "Đã từ chối bình luận.";
    header("Location: discussion_detail.php?id=$discussion_id&success=1");
    exit();
  } else {
    $error = "Không thể cập nhật trạng thái bình luận.";
  }
}

// Thông báo thành công
if (isset($_GET['success']) && $_GET['success'] == 1) {
  $message = "Thao tác thành công!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($discussion['title']); ?> - Thảo Luận</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font từ Google -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Reset mặc định */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Nunito', 'Quicksand', sans-serif;
        background-color: #f5f7fa;
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

    /* Logo styling */
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

    /* Bounce animation for logo */
    .logo:hover {
        animation: bounce 0.8s ease-in-out;
    }

    @keyframes bounce {
        0% { transform: scale(1); }
        20% { transform: scale(1.2); }
        40% { transform: scale(0.9); }
        60% { transform: scale(1.1); }
        80% { transform: scale(0.95); }
        100% { transform: scale(1); }
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
    .container {
        max-width: 900px;
        margin: 30px auto;
        padding: 0 20px;
        animation: fadeIn 1s ease forwards;
    }

    /* Breadcrumbs */
    .breadcrumbs {
        display: flex;
        margin-bottom: 20px;
        font-size: 0.9rem;
        color: #555;
    }

    .breadcrumbs a {
        color: #1e3c72;
        text-decoration: none;
        margin: 0 5px;
    }

    .breadcrumbs a:first-child {
        margin-left: 0;
    }

    .breadcrumbs a:hover {
        text-decoration: underline;
    }

    /* Discussion header */
    .discussion-header {
        margin-bottom: 25px;
    }

    .discussion-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 2rem;
        color: #1e3c72;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 3px solid #FFC107;
        animation: fadeInDown 1s ease;
    }

    .discussion-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 10px;
        font-size: 0.95rem;
        color: #666;
    }

    .discussion-course {
        display: inline-block;
        background: #e0f0ff;
        color: #1e3c72;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 10px;
    }

    /* Discussion content */
    .discussion-content {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    }

    .content-body {
        margin-bottom: 20px;
        line-height: 1.7;
    }

    .content-body p {
        margin-bottom: 15px;
    }

    .content-body p:last-child {
        margin-bottom: 0;
    }

    .content-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #eee;
        padding-top: 15px;
        font-size: 0.9rem;
        color: #666;
    }

    .author-info {
        font-weight: 600;
    }

    /* Comments section */
    .comments-section {
        margin-bottom: 30px;
    }

    .comments-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 1.5rem;
        color: #1e3c72;
        margin-bottom: 20px;
    }

    .comment-item {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        position: relative;
    }

    .comment-pending {
        border-left: 4px solid #ffc107;
    }

    .comment-rejected {
        border-left: 4px solid #dc3545;
        opacity: 0.7;
    }

    .comment-status {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 0.8rem;
        padding: 3px 8px;
        border-radius: 10px;
        font-weight: 600;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .comment-content {
        margin-bottom: 15px;
    }

    .comment-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        color: #666;
        border-top: 1px solid #eee;
        padding-top: 10px;
    }

    .comment-author {
        font-weight: 600;
    }

    .instructor-badge, .admin-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.8rem;
        margin-left: 8px;
        font-weight: 600;
    }

    .instructor-badge {
        background: #e0f0ff;
        color: #1e3c72;
    }

    .admin-badge {
        background: #f8d7da;
        color: #721c24;
    }

    /* Comment actions (approve/reject) */
    .comment-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .comment-actions form {
        display: inline;
    }

    .btn-approve, .btn-reject {
        padding: 5px 10px;
        border-radius: 4px;
        border: none;
        font-size: 0.85rem;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-approve {
        background: #d4edda;
        color: #155724;
    }

    .btn-approve:hover {
        background: #c3e6cb;
    }

    .btn-reject {
        background: #f8d7da;
        color: #721c24;
    }

    .btn-reject:hover {
        background: #f5c6cb;
    }

    /* Add comment form */
    .add-comment {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    }

    .form-title {
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        font-family: 'Nunito', sans-serif;
        resize: vertical;
        min-height: 120px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #1e3c72;
        box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
    }

    /* Button styles */
    .btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Nunito', sans-serif;
        text-align: center;
        text-decoration: none;
        border: none;
        font-size: 1rem;
    }

    .btn-primary {
        background: linear-gradient(90deg, #1e3c72, #2a5298);
        color: white;
        box-shadow: 0 4px 8px rgba(30, 60, 114, 0.2);
    }

    .btn-primary:hover {
        background: linear-gradient(90deg, #2a5298, #1e3c72);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(30, 60, 114, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(108, 117, 125, 0.3);
    }

    /* Form actions */
    .form-actions {
        display: flex;
        justify-content: space-between;
    }

    /* Alert boxes */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 600;
    }

    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-error {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    .alert-warning {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
    }

    /* No comments message */
    .no-comments {
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .no-comments p {
        color: #555;
        margin-bottom: 0;
    }

    /* Footer */
    footer {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: #fff;
        text-align: center;
        padding: 25px 0;
        margin-top: 40px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-container {
            flex-direction: column;
        }
        
        nav ul {
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .form-actions {
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            width: 100%;
        }
        
        .comment-meta {
            flex-direction: column;
            gap: 10px;
        }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="header-container">
      <div class="logo">Học Tập</div>
      <nav>
        <ul>
          <li><a href="home.php">Trang Chủ</a></li>
          <li><a href="<?php echo $role == 'student' ? 'student_dashboard.php' : ($role == 'instructor' ? 'instructor_dashboard.php' : 'admin_dashboard.php'); ?>">Dashboard</a></li>
          <?php if ($role == 'student'): ?>
            <li><a href="assignments.php">Bài Tập</a></li>
            <li><a href="quizzes.php">Trắc Nghiệm</a></li>
          <?php endif; ?>
          <li><a href="forum.php">Diễn Đàn</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/logout">Đăng Xuất</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container">
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
      <a href="<?php echo $role == 'student' ? 'student_dashboard.php' : ($role == 'instructor' ? 'instructor_dashboard.php' : 'admin_dashboard.php'); ?>">Dashboard</a> &gt;
      <a href="forum.php">Diễn Đàn</a> &gt;
      <a href="discussion_detail.php?id=<?php echo $discussion_id; ?>">Chi tiết thảo luận</a>
    </div>
    
    <?php if (!empty($message)): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-error">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <!-- Discussion header -->
    <div class="discussion-header">
      <h1 class="discussion-title"><?php echo htmlspecialchars($discussion['title']); ?></h1>
      <span class="discussion-course"><?php echo htmlspecialchars($discussion['course_title']); ?></span>
      <div class="discussion-meta">
        <span>Tác giả: <?php echo htmlspecialchars($discussion['username']); ?></span>
        <span>Đăng lúc: <?php echo date('d/m/Y H:i', strtotime($discussion['created_at'])); ?></span>
        <?php if ($discussion['updated_at'] != $discussion['created_at']): ?>
          <span>Cập nhật lúc: <?php echo date('d/m/Y H:i', strtotime($discussion['updated_at'])); ?></span>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Discussion content -->
    <div class="discussion-content">
      <div class="content-body">
        <?php echo nl2br(htmlspecialchars($discussion['content'])); ?>
      </div>
      <div class="content-footer">
        <div class="author-info">
          Đăng bởi: <?php echo htmlspecialchars($discussion['username']); ?>
        </div>
        <div class="date-info">
          <?php echo date('d/m/Y H:i', strtotime($discussion['created_at'])); ?>
        </div>
      </div>
    </div>
    
    <!-- Comments section -->
    <div class="comments-section">
      <h2 class="comments-title">Bình luận (<?php echo $comments->num_rows; ?>)</h2>
      
      <?php if ($comments->num_rows > 0): ?>
        <?php while ($comment = $comments->fetch_assoc()): ?>
          <div class="comment-item <?php echo ($comment['status'] == 'pending') ? 'comment-pending' : (($comment['status'] == 'rejected') ? 'comment-rejected' : ''); ?>">
            <?php if ($comment['status'] == 'pending'): ?>
              <div class="comment-status status-pending">Đang chờ phê duyệt</div>
            <?php elseif ($comment['status'] == 'rejected'): ?>
              <div class="comment-status status-rejected">Đã từ chối</div>
            <?php endif; ?>
            
            <div class="comment-content">
              <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
            </div>
            
            <div class="comment-meta">
              <div class="comment-author">
                <?php echo htmlspecialchars($comment['username']); ?>
                <?php if ($comment['author_role'] == 'instructor'): ?>
                  <span class="instructor-badge">Giảng viên</span>
                <?php elseif ($comment['author_role'] == 'admin'): ?>
                  <span class="admin-badge">Admin</span>
                <?php endif; ?>
              </div>
              <div class="comment-date">
                <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                <?php if ($comment['updated_at'] != $comment['created_at']): ?>
                  (Cập nhật: <?php echo date('d/m/Y H:i', strtotime($comment['updated_at'])); ?>)
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Comment moderation (chỉ cho giảng viên và admin) -->
            <?php if (($role == 'instructor' || $role == 'admin') && $comment['status'] == 'pending'): ?>
              <div class="comment-actions">
                <form method="post" action="" onsubmit="return confirm('Bạn có chắc chắn muốn phê duyệt bình luận này?');">
                  <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" name="approve_comment" class="btn-approve">Phê duyệt</button>
                </form>
                
                <form method="post" action="" onsubmit="return confirm('Bạn có chắc chắn muốn từ chối bình luận này?');">
                  <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" name="approve_comment" class="btn-reject">Từ chối</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-comments">
          <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Add comment form -->
    <div class="add-comment">
      <h3 class="form-title">Thêm bình luận</h3>
      
      <form action="" method="post">
        <div class="form-group">
          <label for="comment_content" class="form-label">Nội dung bình luận</label>
          <textarea name="comment_content" id="comment_content" class="form-control" required placeholder="Nhập nội dung bình luận của bạn"></textarea>
        </div>
        
        <?php if ($role == 'student'): ?>
          <div class="alert alert-warning">
            Lưu ý: Bình luận của bạn sẽ được gửi để phê duyệt trước khi xuất hiện công khai.
          </div>
        <?php endif; ?>
        
        <div class="form-actions">
          <a href="forum.php" class="btn btn-secondary">Quay lại diễn đàn</a>
          <button type="submit" name="add_comment" class="btn btn-primary">Gửi bình luận</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
  </footer>
</body>
</html> 