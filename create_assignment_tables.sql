-- Create the Assignments table if it doesn't exist
CREATE TABLE IF NOT EXISTS Assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    course_id INT NOT NULL,
    due_date DATETIME NOT NULL,
    created_by INT NOT NULL,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id),
    FOREIGN KEY (created_by) REFERENCES Users(user_id)
);

-- Create the AssignmentSubmissions table
CREATE TABLE IF NOT EXISTS AssignmentSubmissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    user_id INT NOT NULL,
    submission_text TEXT,
    file_path VARCHAR(255),
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5,2) DEFAULT NULL,
    feedback TEXT,
    FOREIGN KEY (assignment_id) REFERENCES Assignments(assignment_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

-- Insert some sample assignments
INSERT INTO Assignments (title, description, course_id, due_date, created_by)
SELECT 
    'Bài tập về HTML/CSS cơ bản',
    'Thiết kế một trang web đơn giản sử dụng các kiến thức HTML và CSS đã học. Trang web cần có header, footer, và ít nhất 3 section.',
    course_id,
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    created_by
FROM Courses 
WHERE title LIKE '%Web%' OR title LIKE '%HTML%' OR title LIKE '%CSS%'
LIMIT 1;

INSERT INTO Assignments (title, description, course_id, due_date, created_by)
SELECT 
    'Bài tập về JavaScript',
    'Viết một ứng dụng web nhỏ sử dụng JavaScript để thực hiện các thao tác CRUD (Create, Read, Update, Delete) trên một danh sách các mục.',
    course_id,
    DATE_ADD(NOW(), INTERVAL 14 DAY),
    created_by
FROM Courses 
WHERE title LIKE '%Web%' OR title LIKE '%JavaScript%'
LIMIT 1;

INSERT INTO Assignments (title, description, course_id, due_date, created_by)
SELECT 
    'Bài tập về Cơ sở dữ liệu',
    'Thiết kế một cơ sở dữ liệu phù hợp cho một hệ thống quản lý thư viện. Cơ sở dữ liệu cần bao gồm các bảng cho sách, độc giả, mượn/trả sách.',
    course_id,
    DATE_ADD(NOW(), INTERVAL 10 DAY),
    created_by
FROM Courses 
WHERE title LIKE '%Database%' OR title LIKE '%SQL%' OR title LIKE '%Cơ sở dữ liệu%'
LIMIT 1;
