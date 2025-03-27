-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `online_courses` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Use the database
USE `online_courses`;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('student', 'instructor', 'admin') DEFAULT 'student',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create categories table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create courses table
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `instructor_id` INT,
    `category_id` INT,
    `price` DECIMAL(10, 2) DEFAULT 0.00,
    `image` VARCHAR(255) DEFAULT NULL,
    `duration` VARCHAR(50) DEFAULT NULL,
    `level` ENUM('beginner', 'intermediate', 'advanced', 'all-levels') DEFAULT 'all-levels',
    `is_featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create enrollments table
CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create orders table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT,
    `customer_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NOT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `order_date` DATETIME NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create order_items table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `course_title` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create assignments table
CREATE TABLE IF NOT EXISTS `assignments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `course_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `due_date` DATETIME,
    `max_points` INT DEFAULT 100,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create assignment_submissions table
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `assignment_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `submission_text` TEXT,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `submission_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `grade` INT DEFAULT NULL,
    `feedback` TEXT,
    `graded_by` INT DEFAULT NULL,
    `graded_at` TIMESTAMP NULL,
    FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`graded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes to speed up queries
CREATE INDEX `idx_courses_instructor` ON `courses`(`instructor_id`);
CREATE INDEX `idx_courses_category` ON `courses`(`category_id`);
CREATE INDEX `idx_enrollments_user` ON `enrollments`(`user_id`);
CREATE INDEX `idx_enrollments_course` ON `enrollments`(`course_id`);
CREATE INDEX `idx_orders_user_id` ON `orders`(`user_id`);
CREATE INDEX `idx_order_items_order_id` ON `order_items`(`order_id`);
CREATE INDEX `idx_orders_order_id` ON `orders`(`order_id`);
CREATE INDEX `idx_assignments_course` ON `assignments`(`course_id`);
CREATE INDEX `idx_submissions_assignment` ON `assignment_submissions`(`assignment_id`);
CREATE INDEX `idx_submissions_user` ON `assignment_submissions`(`user_id`);

-- Insert sample categories
INSERT INTO `categories` (`name`, `description`, `image`) VALUES
('Lập Trình', 'Các khóa học về lập trình và phát triển phần mềm', '/WebCourses/assets/images/categories/programming.jpg'),
('Thiết Kế', 'Các khóa học về thiết kế đồ họa và UX/UI', '/WebCourses/assets/images/categories/design.jpg'),
('Kinh Doanh', 'Các khóa học về kinh doanh và tiếp thị', '/WebCourses/assets/images/categories/business.jpg');

-- Insert sample admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample instructors
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`) VALUES
('Nguyễn Văn A', 'instructor1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0901234567', 'instructor'),
('Trần Thị B', 'instructor2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0909876543', 'instructor');

-- Insert sample student user
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`) VALUES
('Học Sinh A', 'student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0908888888', 'student');

-- Insert sample courses
INSERT INTO `courses` (`title`, `description`, `instructor_id`, `category_id`, `price`, `image`, `duration`, `level`, `is_featured`) VALUES
('Lập Trình Web Cơ Bản', 'Khóa học giúp bạn làm quen với HTML, CSS và JavaScript để xây dựng các trang web đơn giản', 1, 1, 299000, '/WebCourses/assets/images/courses/web-basic.jpg', '8 tuần', 'beginner', 1),
('Thiết Kế UI/UX Chuyên Nghiệp', 'Học cách thiết kế giao diện người dùng thân thiện và hiệu quả', 2, 2, 399000, '/WebCourses/assets/images/courses/uiux.jpg', '10 tuần', 'intermediate', 1),
('Digital Marketing Cơ Bản', 'Tìm hiểu các chiến lược tiếp thị số hiệu quả', 2, 3, 349000, '/WebCourses/assets/images/courses/marketing.jpg', '6 tuần', 'beginner', 0);

-- Insert sample enrollments
INSERT INTO `enrollments` (`user_id`, `course_id`, `status`) VALUES
(3, 1, 'active'),
(3, 2, 'active');

-- Insert sample assignments
INSERT INTO `assignments` (`course_id`, `title`, `description`, `due_date`, `max_points`) VALUES
(1, 'Tạo Trang Web Cá Nhân', 'Sử dụng HTML và CSS để xây dựng trang web cá nhân giới thiệu bản thân', DATE_ADD(NOW(), INTERVAL 14 DAY), 100),
(1, 'Xây Dựng Form Đăng Ký', 'Tạo form đăng ký có validation sử dụng JavaScript', DATE_ADD(NOW(), INTERVAL 21 DAY), 100),
(2, 'Thiết Kế Wireframe', 'Tạo wireframe cho một ứng dụng di động theo yêu cầu', DATE_ADD(NOW(), INTERVAL 10 DAY), 100); 