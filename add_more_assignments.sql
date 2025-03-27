-- Add more courses
INSERT INTO Courses (title, description, instructor_id) VALUES 
('Web Development', 'Learn HTML, CSS, and JavaScript to create modern websites.', 2),
('Data Science Fundamentals', 'Introduction to data analysis and visualization techniques.', 2),
('Mobile App Development', 'Build applications for iOS and Android platforms.', 2);

-- Enroll the student in new courses
INSERT INTO Enrollments (user_id, course_id, status) VALUES 
(1, 2, 'active'),
(1, 3, 'active'),
(1, 4, 'active');

-- Add more assignments for Introduction to Programming
INSERT INTO Assignments (course_id, title, description, due_date, max_points) VALUES
(1, 'Variables and Data Types', 'Complete exercises on variables and data types in programming.', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 50),
(1, 'Control Structures', 'Implement conditional statements and loops.', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 75),
(1, 'Functions and Methods', 'Create reusable code with functions.', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 100),
(1, 'Final Project', 'Build a complete program using all concepts learned.', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 200);

-- Add assignments for Web Development
INSERT INTO Assignments (course_id, title, description, due_date, max_points) VALUES
(2, 'HTML Basics', 'Create a simple webpage using HTML tags.', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 50),
(2, 'CSS Styling', 'Apply styles to HTML elements using CSS.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 75),
(2, 'JavaScript Fundamentals', 'Add interactivity to webpages with JavaScript.', DATE_ADD(CURDATE(), INTERVAL 12 DAY), 100),
(2, 'Responsive Web Design', 'Make webpages responsive for different screen sizes.', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 100);

-- Add assignments for Data Science Fundamentals
INSERT INTO Assignments (course_id, title, description, due_date, max_points) VALUES
(3, 'Data Collection', 'Gather and organize data from various sources.', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 75),
(3, 'Data Cleaning', 'Preprocess and clean raw data for analysis.', DATE_ADD(CURDATE(), INTERVAL 8 DAY), 75),
(3, 'Statistical Analysis', 'Apply statistical methods to analyze data.', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 100),
(3, 'Data Visualization', 'Create meaningful visualizations of data analysis results.', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 150);

-- Add assignments for Mobile App Development
INSERT INTO Assignments (course_id, title, description, due_date, max_points) VALUES
(4, 'UI Design Principles', 'Learn principles of effective mobile UI design.', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 75),
(4, 'App Navigation', 'Implement navigation between different app screens.', DATE_ADD(CURDATE(), INTERVAL 13 DAY), 100),
(4, 'Data Storage', 'Store and retrieve data in mobile applications.', DATE_ADD(CURDATE(), INTERVAL 18 DAY), 100),
(4, 'Mobile App Project', 'Develop a complete mobile application.', DATE_ADD(CURDATE(), INTERVAL 28 DAY), 200);

-- Create some assignments with past due dates (overdue)
INSERT INTO Assignments (course_id, title, description, due_date, max_points) VALUES
(1, 'Introduction to Algorithms', 'Learn the basics of algorithm design.', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 75),
(2, 'Web Project Planning', 'Create a project plan for a website.', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 50),
(3, 'Data Ethics', 'Explore ethical considerations in data science.', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 60); 