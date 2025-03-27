<?php
include ROOT_DIR . '/app/config/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['review_submit']) && $isLoggedIn) {
  $review_text = trim($_POST['comment'] ?? '');
  $review_rating = intval($_POST['rating'] ?? 5);
  // Lấy tên người dùng và user_id từ session
  $review_author = $_SESSION['username'];
  $user_id = $_SESSION['user_id'];
  // Nếu nhận xét áp dụng chung cho website thì để course_id là NULL. Nếu là nhận xét cho khoá học cụ thể, bạn có thể gửi kèm course_id.
  $course_id = NULL;

  if ($review_text === '') {
    $reviewError = "Vui lòng nhập bình luận.";
  } else {
    $stmt = $conn->prepare("INSERT INTO reviews (course_id, user_id, review_text, rating) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $course_id, $user_id, $review_text, $review_rating);
    if ($stmt->execute()) {
      header("Location: " . $_SERVER['PHP_SELF'] . "#reviews");
      exit();
    } else {
      $reviewError = "Không thể gửi đánh giá. Vui lòng thử lại.";
    }
    $stmt->close();
  }
}

$reviewsResult = $conn->query("SELECT r.review_text, r.rating, u.username AS author 
                                FROM reviews r 
                                JOIN users u ON r.user_id = u.user_id 
                                ORDER BY r.review_id DESC");