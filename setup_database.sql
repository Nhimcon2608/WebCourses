-- Create database if not exists
CREATE DATABASE IF NOT EXISTS webcourses;
USE webcourses;

-- Create Users table
CREATE TABLE IF NOT EXISTS Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Courses table
CREATE TABLE IF NOT EXISTS Courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructor_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES Users(user_id)
);

-- Create Enrollments table
CREATE TABLE IF NOT EXISTS Enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    status ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (course_id) REFERENCES Courses(course_id)
);

-- Create Lessons table
CREATE TABLE IF NOT EXISTS Lessons (
    lesson_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    order_num INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id)
);

-- Create Assignments table
CREATE TABLE IF NOT EXISTS Assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id)
);

-- Create AssignmentSubmissions table
CREATE TABLE IF NOT EXISTS AssignmentSubmissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    user_id INT NOT NULL,
    submission_text TEXT,
    file_path VARCHAR(255),
    grade DECIMAL(5,2),
    feedback TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES Assignments(assignment_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

-- Insert sample admin user
INSERT INTO Users (username, email, password, full_name, role)
VALUES ('admin', 'admin@example.com', '$2y$10$8KsRftBtX7gTY6S9MzpsVu6bpP7DP0xV.qqP5TQsQiBaVz2kQQg2a', 'Admin User', 'admin');

-- Insert sample instructor
INSERT INTO Users (username, email, password, full_name, role)
VALUES ('instructor', 'instructor@example.com', '$2y$10$8KsRftBtX7gTY6S9MzpsVu6bpP7DP0xV.qqP5TQsQiBaVz2kQQg2a', 'Instructor User', 'instructor');

-- Insert sample student
INSERT INTO Users (username, email, password, full_name, role)
VALUES ('student', 'student@example.com', '$2y$10$8KsRftBtX7gTY6S9MzpsVu6bpP7DP0xV.qqP5TQsQiBaVz2kQQg2a', 'Student User', 'student');

-- Insert sample course
INSERT INTO Courses (title, description, instructor_id)
VALUES ('Introduction to Web Development', 'Learn the basics of HTML, CSS, and JavaScript to create modern websites.', 2);

-- Enroll sample student in the course
INSERT INTO Enrollments (user_id, course_id, status)
VALUES (3, 1, 'active');

-- Add sample lessons
INSERT INTO Lessons (course_id, title, content, order_num)
VALUES 
(1, 'HTML Basics', 'Introduction to HTML structure and common elements.', 1),
(1, 'CSS Styling', 'Learn how to style your HTML with CSS.', 2),
(1, 'JavaScript Fundamentals', 'Introduction to JavaScript programming.', 3);

-- Add sample assignment
INSERT INTO Assignments (course_id, title, description, due_date)
VALUES (1, 'Create a Simple Webpage', 'Create a webpage using HTML and CSS based on the provided design.', DATE_ADD(NOW(), INTERVAL 7 DAY)); 