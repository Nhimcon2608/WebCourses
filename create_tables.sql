-- Drop tables if they exist to avoid errors
DROP TABLE IF EXISTS AssignmentSubmissions;
DROP TABLE IF EXISTS Assignments;
DROP TABLE IF EXISTS Enrollments;
DROP TABLE IF EXISTS Courses;
DROP TABLE IF EXISTS Users;

-- Create Users table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Courses table
CREATE TABLE Courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    instructor_id INT,
    status ENUM('active', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES Users(user_id) ON DELETE SET NULL
);

-- Create Enrollments table
CREATE TABLE Enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, course_id)
);

-- Create Assignments table
CREATE TABLE Assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    max_points INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

-- Create AssignmentSubmissions table
CREATE TABLE AssignmentSubmissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    user_id INT NOT NULL,
    submission_text TEXT,
    file_path VARCHAR(255),
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grade INT,
    feedback TEXT,
    graded_at TIMESTAMP NULL,
    FOREIGN KEY (assignment_id) REFERENCES Assignments(assignment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Insert a test user (student)
INSERT INTO Users (username, password, email, full_name, role) 
VALUES ('student', '$2y$10$GzXb.D42MQ0F.Cd3Xsv..e1zBLFX9.5SVUFwqIVVzikm9kKuULBEK', 'student@example.com', 'Test Student', 'student');

-- Insert a test instructor
INSERT INTO Users (username, password, email, full_name, role) 
VALUES ('instructor', '$2y$10$GzXb.D42MQ0F.Cd3Xsv..e1zBLFX9.5SVUFwqIVVzikm9kKuULBEK', 'instructor@example.com', 'Test Instructor', 'instructor');

-- Insert a test course
INSERT INTO Courses (title, description, instructor_id) 
VALUES ('Introduction to Programming', 'Learn the basics of programming with this introductory course.', 2);

-- Enroll the student in the course
INSERT INTO Enrollments (user_id, course_id, status) 
VALUES (1, 1, 'active');

-- Create a test assignment
INSERT INTO Assignments (course_id, title, description, due_date) 
VALUES (1, 'First Assignment', 'Complete the first programming assignment.', DATE_ADD(CURDATE(), INTERVAL 7 DAY)); 