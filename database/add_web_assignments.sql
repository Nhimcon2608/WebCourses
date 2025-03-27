-- Thêm 10 bài tập web programming bổ sung cho khóa học "Lập Trình Web Cơ Bản" (course_id = 1)
INSERT INTO `assignments` (`course_id`, `title`, `description`, `due_date`, `max_points`) VALUES
(1, 'JavaScript Cơ Bản', 'Tạo các chức năng tương tác cơ bản cho trang web sử dụng JavaScript: xử lý sự kiện click, thay đổi nội dung và CSS.', DATE_ADD(NOW(), INTERVAL 7 DAY), 100),
(1, 'Responsive Design', 'Thiết kế một trang web responsive sử dụng media queries để hiển thị tốt trên các thiết bị di động, máy tính bảng và desktop.', DATE_ADD(NOW(), INTERVAL 9 DAY), 100),
(1, 'CSS Grid Layout', 'Sử dụng CSS Grid để tạo layout cho một trang web tin tức với các thành phần header, footer, sidebar và nội dung chính.', DATE_ADD(NOW(), INTERVAL 11 DAY), 100),
(1, 'Form Validation', 'Tạo form đăng ký và đăng nhập có validation phía client bằng JavaScript, kiểm tra email, password và các trường required.', DATE_ADD(NOW(), INTERVAL 13 DAY), 100),
(1, 'CSS Animation', 'Tạo các hiệu ứng animation sử dụng CSS cho các phần tử trên trang web như nút bấm, menu và các thành phần tương tác.', DATE_ADD(NOW(), INTERVAL 17 DAY), 100),
(1, 'DOM Manipulation', 'Sử dụng JavaScript để thao tác với DOM: thêm, sửa, xóa các phần tử và thay đổi thuộc tính của chúng một cách động.', DATE_ADD(NOW(), INTERVAL 19 DAY), 100),
(1, 'Bootstrap Framework', 'Xây dựng một trang landing page đơn giản sử dụng Bootstrap framework với các component có sẵn như navbar, carousel, cards.', DATE_ADD(NOW(), INTERVAL 21 DAY), 100),
(1, 'Local Storage', 'Tạo ứng dụng quản lý ghi chú đơn giản sử dụng LocalStorage để lưu trữ dữ liệu người dùng giữa các lần truy cập.', DATE_ADD(NOW(), INTERVAL 23 DAY), 100),
(1, 'Fetch API', 'Sử dụng Fetch API để lấy dữ liệu từ REST API công khai và hiển thị lên trang web theo định dạng có cấu trúc.', DATE_ADD(NOW(), INTERVAL 25 DAY), 100),
(1, 'Single Page Application', 'Xây dựng ứng dụng web một trang đơn giản với định tuyến phía client sử dụng JavaScript thuần, không reload trang.', DATE_ADD(NOW(), INTERVAL 27 DAY), 100); 