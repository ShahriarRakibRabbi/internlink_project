<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, u.email 
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Get education history
$stmt = $pdo->prepare("
    SELECT * FROM education 
    WHERE student_id = ? 
    ORDER BY end_year DESC, start_year DESC
");
$stmt->execute([$student['student_id']]);
$education = $stmt->fetchAll();

// Get experience
$stmt = $pdo->prepare("
    SELECT * FROM experience 
    WHERE student_id = ? 
    ORDER BY end_date DESC, start_date DESC
");
$stmt->execute([$student['student_id']]);
$experience = $stmt->fetchAll();

// Get student's skills
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM skills s
    JOIN student_skills ss ON s.skill_id = ss.skill_id
    WHERE ss.student_id = ?
");
$stmt->execute([$student['student_id']]);
$studentSkills = $stmt->fetchAll();

// Get all available skills
$stmt = $pdo->query("SELECT * FROM skills ORDER BY name");
$allSkills = $stmt->fetchAll();

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo->beginTransaction();

        switch ($_POST['action']) {
            case 'update_profile':
                $stmt = $pdo->prepare("
                    UPDATE students 
                    SET full_name = ?, phone = ?, university = ?, graduation_year = ?, bio = ?
                    WHERE student_id = ?
                ");
                $stmt->execute([
                    sanitize($_POST['full_name']),
                    sanitize($_POST['phone']),
                    sanitize($_POST['university']),
                    $_POST['graduation_year'],
                    sanitize($_POST['bio']),
                    $student['student_id']
                ]);
                break;

            case 'add_education':
                $stmt = $pdo->prepare("
                    INSERT INTO education (student_id, institution, degree, field_of_study, start_year, end_year)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $student['student_id'],
                    sanitize($_POST['institution']),
                    sanitize($_POST['degree']),
                    sanitize($_POST['field_of_study']),
                    $_POST['start_year'],
                    $_POST['end_year']
                ]);
                break;

            case 'add_experience':
                $stmt = $pdo->prepare("
                    INSERT INTO experience (student_id, company_name, position, start_date, end_date, description)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $student['student_id'],
                    sanitize($_POST['company_name']),
                    sanitize($_POST['position']),
                    $_POST['start_date'],
                    $_POST['end_date'],
                    sanitize($_POST['description'])
                ]);
                break;

            case 'update_skills':
                // Remove existing skills
                $stmt = $pdo->prepare("DELETE FROM student_skills WHERE student_id = ?");
                $stmt->execute([$student['student_id']]);

                // Add new skills
                if (!empty($_POST['skills'])) {
                    $stmt = $pdo->prepare("INSERT INTO student_skills (student_id, skill_id) VALUES (?, ?)");
                    foreach ($_POST['skills'] as $skill_id) {
                        $stmt->execute([$student['student_id'], $skill_id]);
                    }
                }
                break;
        }

        $pdo->commit();
        $success = "Profile updated successfully!";
        
        // Refresh page to show updates
        header("Location: profile.php?success=1");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating profile. Please try again.";
    }
}

if (isset($_GET['success'])) {
    $success = "Profile updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - InternLink</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <a href="dashboard.php">Dashboard</a>
                <a href="../search.php">Find Internships</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Manage Your Profile</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="profile-sections">
            <!-- Basic Information -->
            <div class="card">
                <h3>Basic Information</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($student['email']); ?>" 
                               disabled>
                        <p class="help-text">Email cannot be changed</p>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="university">University:</label>
                        <input type="text" id="university" name="university" 
                               value="<?php echo htmlspecialchars($student['university'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="graduation_year">Expected Graduation Year:</label>
                        <input type="number" id="graduation_year" name="graduation_year" 
                               value="<?php echo htmlspecialchars($student['graduation_year'] ?? ''); ?>"
                               min="2020" max="2030">
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio:</label>
                        <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($student['bio'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <!-- Education -->
            <div class="card">
                <h3>Education</h3>
                <?php if (!empty($education)): ?>
                    <div class="education-list">
                        <?php foreach ($education as $edu): ?>
                            <div class="education-item">
                                <h4><?php echo htmlspecialchars($edu['degree']); ?></h4>
                                <p class="institution"><?php echo htmlspecialchars($edu['institution']); ?></p>
                                <p class="field"><?php echo htmlspecialchars($edu['field_of_study']); ?></p>
                                <p class="years"><?php echo $edu['start_year']; ?> - <?php echo $edu['end_year']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="add-form">
                    <input type="hidden" name="action" value="add_education">
                    
                    <div class="form-group">
                        <label for="institution">Institution:</label>
                        <input type="text" id="institution" name="institution" required>
                    </div>

                    <div class="form-group">
                        <label for="degree">Degree:</label>
                        <input type="text" id="degree" name="degree" required>
                    </div>

                    <div class="form-group">
                        <label for="field_of_study">Field of Study:</label>
                        <input type="text" id="field_of_study" name="field_of_study" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_year">Start Year:</label>
                            <input type="number" id="start_year" name="start_year" 
                                   min="2015" max="2030" required>
                        </div>

                        <div class="form-group">
                            <label for="end_year">End Year:</label>
                            <input type="number" id="end_year" name="end_year" 
                                   min="2015" max="2030" required>
                        </div>
                    </div>

                    <button type="submit" class="btn">Add Education</button>
                </form>
            </div>

            <!-- Experience -->
            <div class="card">
                <h3>Experience</h3>
                <?php if (!empty($experience)): ?>
                    <div class="experience-list">
                        <?php foreach ($experience as $exp): ?>
                            <div class="experience-item">
                                <h4><?php echo htmlspecialchars($exp['position']); ?></h4>
                                <p class="company"><?php echo htmlspecialchars($exp['company_name']); ?></p>
                                <p class="dates">
                                    <?php echo date('M Y', strtotime($exp['start_date'])); ?> - 
                                    <?php echo $exp['end_date'] ? date('M Y', strtotime($exp['end_date'])) : 'Present'; ?>
                                </p>
                                <p class="description"><?php echo nl2br(htmlspecialchars($exp['description'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="add-form">
                    <input type="hidden" name="action" value="add_experience">
                    
                    <div class="form-group">
                        <label for="company_name">Company:</label>
                        <input type="text" id="company_name" name="company_name" required>
                    </div>

                    <div class="form-group">
                        <label for="position">Position:</label>
                        <input type="text" id="position" name="position" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" required>
                        </div>

                        <div class="form-group">
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date">
                            <p class="help-text">Leave blank if current position</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>

                    <button type="submit" class="btn">Add Experience</button>
                </form>
            </div>

            <!-- Skills -->
            <div class="card">
                <h3>Skills</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_skills">
                    
                    <div class="skills-grid">
                        <?php foreach ($allSkills as $skill): ?>
                            <label class="skill-checkbox">
                                <input type="checkbox" name="skills[]" 
                                       value="<?php echo $skill['skill_id']; ?>"
                                       <?php echo in_array($skill, $studentSkills) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($skill['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn">Update Skills</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .profile-sections {
            display: grid;
            gap: 2rem;
            margin: 2rem 0;
        }
        .education-item, .experience-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .education-item:last-child, .experience-item:last-child {
            border-bottom: none;
        }
        .institution, .company {
            color: #666;
            font-weight: bold;
        }
        .field, .years, .dates {
            color: #666;
            font-size: 0.9rem;
        }
        .description {
            margin-top: 0.5rem;
        }
        .add-form {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .help-text {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.5rem;
            margin: 1rem 0;
        }
        .skill-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const endYear = this.querySelector('[name="end_year"]');
                const startYear = this.querySelector('[name="start_year"]');
                
                if (endYear && startYear && parseInt(endYear.value) < parseInt(startYear.value)) {
                    e.preventDefault();
                    alert('End year cannot be earlier than start year');
                    return;
                }

                const endDate = this.querySelector('[name="end_date"]');
                const startDate = this.querySelector('[name="start_date"]');
                
                if (endDate && startDate && endDate.value && new Date(endDate.value) < new Date(startDate.value)) {
                    e.preventDefault();
                    alert('End date cannot be earlier than start date');
                    return;
                }
            });
        });
    </script>
</body>
</html>
