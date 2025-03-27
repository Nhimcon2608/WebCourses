-- Table structure for lessons
CREATE TABLE IF NOT EXISTS lessons (
  lesson_id INT(11) NOT NULL AUTO_INCREMENT,
  course_id INT(11) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  content TEXT,
  lesson_order INT(11) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (lesson_id),
  KEY course_id (course_id),
  CONSTRAINT fk_lesson_course FOREIGN KEY (course_id) REFERENCES courses (course_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for lesson attachments
CREATE TABLE IF NOT EXISTS lesson_materials (
  material_id INT(11) NOT NULL AUTO_INCREMENT,
  lesson_id INT(11) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  filepath VARCHAR(255) NOT NULL,
  filesize INT(11) DEFAULT 0,
  filetype VARCHAR(100),
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (material_id),
  KEY lesson_id (lesson_id),
  CONSTRAINT fk_material_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (lesson_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 