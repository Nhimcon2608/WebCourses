-- Thêm đăng ký cho học sinh với khoá học Digital Marketing
INSERT INTO `enrollments` (`user_id`, `course_id`, `status`) VALUES
(3, 3, 'active');

-- Thêm bài tập cho khoá học "Lập Trình Web Cơ Bản" (course_id = 1)
INSERT INTO `assignments` (`course_id`, `title`, `description`, `due_date`, `max_points`) VALUES
(1, 'Xây Dựng Trang Portfolio', 'Tạo một trang portfolio cá nhân hoàn chỉnh bao gồm các phần: giới thiệu bản thân, kỹ năng, dự án đã làm, và thông tin liên hệ. Sử dụng HTML, CSS và JavaScript.', DATE_ADD(NOW(), INTERVAL 25 DAY), 100),
(1, 'Tạo Ứng Dụng Todo List', 'Phát triển ứng dụng Todo List sử dụng JavaScript thuần, có chức năng thêm, sửa, xóa và đánh dấu hoàn thành công việc. Dữ liệu cần được lưu vào localStorage.', DATE_ADD(NOW(), INTERVAL 30 DAY), 150);

-- Thêm bài tập cho khoá học "Thiết Kế UI/UX Chuyên Nghiệp" (course_id = 2)
INSERT INTO `assignments` (`course_id`, `title`, `description`, `due_date`, `max_points`) VALUES
(2, 'Thiết Kế Giao Diện Ứng Dụng Di Động', 'Thiết kế giao diện người dùng cho một ứng dụng di động đặt đồ ăn. Bao gồm các màn hình: đăng nhập, trang chủ, danh sách nhà hàng, chi tiết món ăn, giỏ hàng và thanh toán.', DATE_ADD(NOW(), INTERVAL 15 DAY), 100),
(2, 'Nghiên Cứu Người Dùng', 'Thực hiện nghiên cứu người dùng cho một sản phẩm số theo lựa chọn của bạn. Tạo bảng khảo sát, phỏng vấn ít nhất 5 người dùng tiềm năng, và tổng hợp kết quả thành báo cáo.', DATE_ADD(NOW(), INTERVAL 18 DAY), 120),
(2, 'Tạo User Flow và Wireframe', 'Tạo user flow và wireframe chi tiết cho một ứng dụng quản lý tài chính cá nhân. Bao gồm các tính năng: theo dõi chi tiêu, lập ngân sách, báo cáo thống kê và thiết lập mục tiêu tiết kiệm.', DATE_ADD(NOW(), INTERVAL 22 DAY), 150);

-- Thêm bài tập cho khoá học "Digital Marketing Cơ Bản" (course_id = 3)
INSERT INTO `assignments` (`course_id`, `title`, `description`, `due_date`, `max_points`) VALUES
(3, 'Phân Tích Chiến Dịch Marketing', 'Chọn một chiến dịch marketing nổi bật của một thương hiệu lớn và phân tích chi tiết: mục tiêu, đối tượng mục tiêu, kênh truyền thông, nội dung, và kết quả. Đề xuất cải tiến dựa trên phân tích của bạn.', DATE_ADD(NOW(), INTERVAL 12 DAY), 100),
(3, 'Tạo Kế Hoạch Content Marketing', 'Phát triển kế hoạch content marketing cho một doanh nghiệp nhỏ trong lĩnh vực thời trang, bao gồm: lịch đăng bài, loại nội dung, kênh phân phối và KPI đo lường hiệu quả.', DATE_ADD(NOW(), INTERVAL 20 DAY), 120),
(3, 'Chiến Lược SEO', 'Thực hiện nghiên cứu từ khóa và đề xuất chiến lược SEO cho một website bán hàng online. Xác định các từ khóa mục tiêu, tối ưu hóa on-page và đề xuất kế hoạch xây dựng backlink.', DATE_ADD(NOW(), INTERVAL 28 DAY), 150),
(3, 'Phân Tích và Báo Cáo Google Analytics', 'Sử dụng dữ liệu từ Google Analytics Demo Account để phân tích hiệu suất website và tạo báo cáo chi tiết về lưu lượng truy cập, tỷ lệ chuyển đổi và hành vi người dùng. Đề xuất 3 cải tiến dựa trên dữ liệu.', DATE_ADD(NOW(), INTERVAL 35 DAY), 100),
(3, 'Thiết Kế Quảng Cáo Facebook', 'Tạo một chiến dịch quảng cáo Facebook hoàn chỉnh cho một sản phẩm mới, bao gồm: thiết kế hình ảnh, viết nội dung, xác định đối tượng mục tiêu và ngân sách. Trình bày dưới dạng slide presentation.', DATE_ADD(NOW(), INTERVAL 40 DAY), 120); 