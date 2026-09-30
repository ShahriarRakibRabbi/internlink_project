

SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS internship_skills;
DROP TABLE IF EXISTS student_skills;
DROP TABLE IF EXISTS offer_skills;
DROP TABLE IF EXISTS offer_categories;
DROP TABLE IF EXISTS interest_categories;
DROP TABLE IF EXISTS hires;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS internship_offers;
DROP TABLE IF EXISTS internships;
DROP TABLE IF EXISTS internship_interests;
DROP TABLE IF EXISTS education;
DROP TABLE IF EXISTS experience;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS=1;

-- 1. Users
CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'company', 'admin') NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Students
CREATE TABLE students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    dob DATE,
    contact VARCHAR(20),
    university VARCHAR(100),
    graduation_year INT,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. Companies
CREATE TABLE companies (
    company_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(100),
    website VARCHAR(255),
    phone VARCHAR(20),
    contact_person VARCHAR(100),
    contact_email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 4. Admins
CREATE TABLE admins (
    admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Categories
CREATE TABLE categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE
);

-- 6. Internship Interests
CREATE TABLE internship_interests (
    interest_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    availability VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- 7. Internships
CREATE TABLE internships (
    internship_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(100) NOT NULL,
    duration VARCHAR(50),
    stipend VARCHAR(50),
    requirements TEXT,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'closed') DEFAULT 'active',
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

-- 8. Internship Offers (legacy support)
CREATE TABLE internship_offers (
    offer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    role VARCHAR(100) NOT NULL,
    stipend VARCHAR(50),
    duration VARCHAR(50),
    requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

-- 9. Applications
CREATE TABLE applications (
    application_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    internship_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    cover_letter TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (internship_id) REFERENCES internships(internship_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- 10. Hires
CREATE TABLE hires (
    hire_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    hired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE
);

-- 11. Skills
CREATE TABLE skills (
    skill_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(50) NOT NULL UNIQUE
);

-- 12. Messages
CREATE TABLE messages (
    message_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 13. Notifications
CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 14. Many-to-Many: Student Skills
CREATE TABLE student_skills (
    student_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (student_id, skill_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

-- 15. Many-to-Many: Internship Skills
CREATE TABLE internship_skills (
    internship_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (internship_id, skill_id),
    FOREIGN KEY (internship_id) REFERENCES internships(internship_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

-- 16. Legacy: Offer Skills
CREATE TABLE offer_skills (
    offer_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (offer_id, skill_id),
    FOREIGN KEY (offer_id) REFERENCES internship_offers(offer_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

-- 17. Legacy: Offer Categories
CREATE TABLE offer_categories (
    offer_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (offer_id, category_id),
    FOREIGN KEY (offer_id) REFERENCES internship_offers(offer_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

-- 18. Legacy: Interest Categories
CREATE TABLE interest_categories (
    interest_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (interest_id, category_id),
    FOREIGN KEY (interest_id) REFERENCES internship_interests(interest_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

-- 19. Education
CREATE TABLE education (
    education_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    institution VARCHAR(100) NOT NULL,
    degree VARCHAR(100) NOT NULL,
    field_of_study VARCHAR(100),
    start_year INT,
    end_year INT,
    gpa DECIMAL(3,2),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- 20. Experience
CREATE TABLE experience (
    experience_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    start_date DATE,
    end_date DATE,
    description TEXT,
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (category_name) VALUES 
('Web Development'),
('Mobile Development'),
('Data Science'),
('UI/UX Design'),
('Software Testing'),
('DevOps'),
('Cybersecurity'),
('Machine Learning');

-- Insert default skills
INSERT INTO skills (skill_name) VALUES 
('HTML'),
('CSS'),
('JavaScript'),
('PHP'),
('MySQL'),
('Python'),
('Java'),
('React'),
('Node.js'),
('Git'),
('UI Design'),
('Testing');

