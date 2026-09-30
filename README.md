# InternLink

## **A Web-Based Internship Portal for Students and Companies**

1. **Objective:**

The main objective is to develop a secure and user-friendly web portal that bridges the gap between students and companies by enhancing the internship recruitment process. The system will allow students to discover and apply for internships, while companies can post opportunities and track applications. The platform will also feature an admin panel and a responsive UI.

2. **Key Features:**  
* **User Authentication**

  * Secure user registration and login

  * OTP-based two-step verification for added security (optional)

  * **Technologies**: PHP, MySQL, Sessions, JavaScript


* **Admin Panel**

  * Admins can manage users, internship posts, and monitor activities

  * Role-based access control ensures secure and limited access

  * **Technologies:** PHP, SQL


* **Role-Based Dashboards**

  * Separate, customized panels for Students, Companies, and Admins

  * **Technologies:** PHP, HTML, CSS


* **Internship CRUD (Company)**

  * Companies can **create**, read, update, and **delete** internship postings

  * **Technologies:** PHP, SQL,JavaScript


* **Application Handling (Student)**

  * Students can **apply** to or **withdraw** from internships

  * Uses foreign keys to manage student-internship relationships

  * **Technologies:** PHP, MySQL


* **View Applicants (Company)**

  * Companies can view a list of all students who applied to their internships

  * **Technologies:** PHP, MySQL


* **Track Application Status (Student)**

  * Students can track the status of their applications (e.g., pending, accepted, rejected)

  * **Technologies:** SQL, PHP


* **Search / Filter / Sort**

  * Dynamic **search**, sorting, and filtering of internships and applicants

  * **Technologies:** SQL, PHP


* **Responsive UI**

  * Optimized interface for the users

  * **Technologies:** CSS 


* **Hosting Ready**

  * Built to be easily deployed on local (XAMPP/WAMP) or live LAMP servers

  * **Technologies:** XAMPP 

3. ##  **Code Functionality Overview**

### **3.1 Main PHP Files**

#### ***includes/db.php***

* Purpose: Handles database connection and session management.  
* Functions: isLoggedIn(), getUserRole(), requireLogin(), sanitize(\$input).  
* Libraries Used: PDO (for MySQL), PHP sessions.

  #### ***login.php / logout.php***

* Purpose: User authentication.  
* Logic: Validates credentials, sets session variables, handles logout.

  #### ***register.php***

* Purpose: User registration.  
* Logic: Validates input, inserts new user into the database, handles errors.

  #### ***student/dashboard.php***

* Purpose: Student home page.  
* Logic: Displays available internships, application status, and skills.

  #### ***student/profile.php / company/profile.php***

* Purpose: Profile management.  
* Logic: Allows users to view and update their details.

  #### ***student/manage\_skills.php***

* Purpose: Manage student skills.  
* Logic: Add, edit, or delete skills linked to the student.

  #### ***student/withdraw\_application.php***

* Purpose: Withdraw internship applications.  
* Logic: Removes application record from the database.

  #### ***company/dashboard.php***

* Purpose: Company home page.  
* Logic: Shows posted internships and received applications.

  #### ***company/post\_internship.php , company/edit\_internship.php , company/close\_internship.php , company/reopen\_internship.php***

* Purpose: Internship management.  
* Logic: CRUD operations for internships.

  #### ***company/view-ac-applications.php***

* Purpose: View applications for a specific internship and accept/reject the application.  
* Logic: Lists applicants, updates application status.

  #### ***admin/dashboard.php***

* Purpose: Admin home page.  
* Logic: Shows platform statistics and user management options.

  #### ***admin/users.php , admin/add-edit-.delete.php***

* Purpose: Manage users (add, edit, delete).  
* Logic: Uses SQL queries for user management.

### **3.2 CSS** 

#### ***css/style.css***

* Purpose: Styles the entire web app.  
* Features: Responsive design, theme, background, styled components (navbar, cards, alerts, tables).

**3.3 SQL**

#### ***sql/database.sql , sql/schema\_updates.sql, admin\_setup.sql***

* Purpose: Database schema and updates.  
* Tables: Users, internships, applications, skills, admin, categories, internships etc.

4. ## **Libraries and Technologies Used**

* PHP:  Core backend logic.  
* PDO/MySQLi:  Database access.  
* HTML/CSS:  Frontend structure and styling.  
* JavaScript: (If present) for interactivity.  
* Sessions:  User authentication and state management.


5. ##  **How the Code Works (Flow Example)**

* **User Registration/Login**: User registers via register.php, logs in via login.php. Session is started and user role is set.  
* **Dashboard Access**: Based on role, user is redirected to the appropriate dashboard (student/, company/, admin/).  
* **Internship Management**: Companies post internships, students view and apply, admins oversee.  
* **Application Handling**: Students apply/withdraw, companies view/manage applications.  
* **Profile and Skills:** Users update their profiles and skills for better matching.

Watch the video or check the pdf report for details

7. **Future Enhancements**                                                                                                                                                                             
* Payment integration for premium job listings

* Advanced analytics with charts (applicant stats, post-performance)

* AI-driven internship recommendations

* Mobile app (Android/iOS) integration via RESTful API

* Smart Matching: Internships shown based on skillset, location

* Status sharing Platform

* Messaging System: Communicate between company and applicant

* Password Reset: Secure recovery mechanism

* Admin Moderation Tools: Approve posts, flag spam, analytics


8. **Lessons Learnt**  
* Hosting a web app  
* Protection against sql injections by using prepare statement  
* Two Factor Authentication  
* Php,css,html,javascript


9. **Challenges**  
*  Adjusting the code to the ui design  
*  Merging the codes  
* Two factor authentication


10. ## **Conclusion:**

InternLink is a modular, secure, and user-friendly platform for managing internships. The code is organized by user roles, with clear separation of concerns and reusable components.

