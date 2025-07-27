-- InternLink Database Schema
-- Port: 3307

SET FOREIGN_KEY_CHECKS=0;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS internship_skills;
DROP TABLE IF EXISTS student_skills;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS internships;
DROP TABLE IF EXISTS experience;
DROP TABLE IF EXISTS education;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'company', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- InternConnect Database Schema (12 Tables)
-- Each table is commented for clarity

-- 1. Users: Stores login credentials and role info
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'company', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Students: Student profile details
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    dob DATE,
    contact VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. Companies: Company profile details
CREATE TABLE companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    contact_email VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 4. Admins: Admin profile details
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Internship Interests: Posts by students
CREATE TABLE internship_interests (
    interest_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    availability VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- 6. Internship Offers: Posts by companies
CREATE TABLE internship_offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    role VARCHAR(100) NOT NULL,
    stipend VARCHAR(50),
    duration VARCHAR(50),
    requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

-- 7. Applications: Students apply to offers
CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES internship_offers(offer_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- 8. Hires: Companies hire students
CREATE TABLE hires (
    hire_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    hired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE
);

-- 9. Skills: List of skills (used by students and offers)
CREATE TABLE skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(50) NOT NULL UNIQUE
);

-- 10. Categories: Internship fields/categories
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE
);

-- 11. Messages: Contact logs between users
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 12. Notifications: System/user notifications
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Many-to-many relationships for skills and categories
-- Student Skills
CREATE TABLE student_skills (
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    PRIMARY KEY (student_id, skill_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

-- Offer Skills
CREATE TABLE offer_skills (
    offer_id INT NOT NULL,
    skill_id INT NOT NULL,
    PRIMARY KEY (offer_id, skill_id),
    FOREIGN KEY (offer_id) REFERENCES internship_offers(offer_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
);

-- Offer Categories
CREATE TABLE offer_categories (
    offer_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (offer_id, category_id),
    FOREIGN KEY (offer_id) REFERENCES internship_offers(offer_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

-- Interest Categories
CREATE TABLE interest_categories (
    interest_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (interest_id, category_id),
    FOREIGN KEY (interest_id) REFERENCES internship_interests(interest_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);


-- Insert some default categories
INSERT INTO categories (category_name) VALUES 
('Web Development'),
('Mobile Development'),
('Data Science'),
('UI/UX Design'),
('Software Testing'),
('DevOps'),
('Cybersecurity'),
('Machine Learning');

-- Insert some default skills
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
