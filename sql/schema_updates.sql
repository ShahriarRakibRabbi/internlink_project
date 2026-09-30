
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE companies 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Ensure applications table has proper timestamp
ALTER TABLE applications 
ADD COLUMN IF NOT EXISTS applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;


CREATE TABLE IF NOT EXISTS student_skills (
    student_id INT,
    skill_id INT,
    PRIMARY KEY (student_id, skill_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS education (
    education_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    institution_name VARCHAR(255) NOT NULL,
    degree VARCHAR(255) NOT NULL,
    field_of_study VARCHAR(255),
    start_year YEAR,
    end_year YEAR,
    gpa DECIMAL(3,2),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS experience (
    experience_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    start_date DATE,
    end_date DATE,
    description TEXT,
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);
ALTER TABLE categories ADD COLUMN description TEXT AFTER category_name;


INSERT IGNORE INTO categories (category_name, description) VALUES
('Technology', 'Software development, IT, and tech-related internships'),
('Marketing', 'Digital marketing, content marketing, and advertising internships'),
('Finance', 'Banking, accounting, and financial services internships'),
('Healthcare', 'Medical, pharmaceutical, and healthcare internships'),
('Engineering', 'Mechanical, electrical, civil, and other engineering internships'),
('Business', 'Business development, management, and consulting internships'),
('Design', 'Graphic design, UI/UX, and creative design internships'),
('Sales', 'Sales, business development, and customer relations internships'),
('Research', 'Academic research, market research, and data analysis internships'),
('Education', 'Teaching, tutoring, and educational program internships');

ALTER TABLE skills ADD COLUMN category VARCHAR(100) NULL AFTER skill_name;


-- Insert some default skills 
INSERT IGNORE INTO skills (skill_name, category) VALUES
('JavaScript', 'Programming'),
('Python', 'Programming'),
('Java', 'Programming'),
('React', 'Web Development'),
('Node.js', 'Web Development'),
('HTML/CSS', 'Web Development'),
('SQL', 'Database'),
('MongoDB', 'Database'),
('Git', 'Tools'),
('Adobe Photoshop', 'Design'),
('Adobe Illustrator', 'Design'),
('Figma', 'Design'),
('Microsoft Excel', 'Office'),
('Microsoft PowerPoint', 'Office'),
('Project Management', 'Management'),
('Communication', 'Soft Skills'),
('Leadership', 'Soft Skills'),
('Problem Solving', 'Soft Skills'),
('Teamwork', 'Soft Skills'),
('Time Management', 'Soft Skills');

-- Update any existing records to have proper timestamps
UPDATE users SET created_at = NOW() WHERE created_at IS NULL;
UPDATE students SET created_at = NOW() WHERE created_at IS NULL;
UPDATE companies SET created_at = NOW() WHERE created_at IS NULL;

ALTER TABLE users ADD COLUMN `2fa_secret` VARCHAR(64) DEFAULT NULL;