<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Get student details
$stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

// Handle skill updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Delete existing skills
        $stmt = $pdo->prepare("DELETE FROM student_skills WHERE student_id = ?");
        $stmt->execute([$student['student_id']]);
        
        // Add selected skills
        if (!empty($_POST['skills'])) {
            $stmt = $pdo->prepare("INSERT INTO student_skills (student_id, skill_id) VALUES (?, ?)");
            foreach ($_POST['skills'] as $skill_id) {
                $stmt->execute([$student['student_id'], (int)$skill_id]);
            }
        }
        
        $pdo->commit();
        $success = "Skills updated successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating skills: " . $e->getMessage();
    }
}

// Get all available skills
try {
    $stmt = $pdo->query("SELECT * FROM skills ORDER BY skill_name");
    $allSkills = $stmt->fetchAll();
} catch (PDOException $e) {
    $allSkills = [];
    $error = "Error loading skills.";
}

// Get student's current skills
try {
    $stmt = $pdo->prepare("
        SELECT s.skill_id, s.skill_name 
        FROM skills s
        JOIN student_skills ss ON s.skill_id = ss.skill_id
        WHERE ss.student_id = ?
        ORDER BY s.skill_name
    ");
    $stmt->execute([$student['student_id']]);
    $studentSkills = $stmt->fetchAll();
    $studentSkillIds = array_column($studentSkills, 'skill_id');
} catch (PDOException $e) {
    $studentSkills = [];
    $studentSkillIds = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Skills - InternLink</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">Profile</a>
                <a href="../search.php">Find Internships</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Manage Your Skills</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Current Skills</h3>
            <?php if (!empty($studentSkills)): ?>
                <div class="current-skills">
                    <?php foreach ($studentSkills as $skill): ?>
                        <span class="badge"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>You haven't added any skills yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Select Your Skills</h3>
            <form method="POST" action="">
                <?php if (!empty($allSkills)): ?>
                    <div class="skills-grid">
                        <?php foreach ($allSkills as $skill): ?>
                            <label class="skill-checkbox">
                                <input type="checkbox" name="skills[]" value="<?php echo $skill['skill_id']; ?>"
                                       <?php echo in_array($skill['skill_id'], $studentSkillIds) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No skills available. Please contact administrator.</p>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Skills</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="help-section">
            <h3>Tips</h3>
            <ul>
                <li>Select all skills that you are proficient in</li>
                <li>Your skills help companies find you for relevant internships</li>
                <li>You can update your skills anytime as you learn new ones</li>
                <li>Be honest about your skill level - companies value authenticity</li>
            </ul>
        </div>
    </div>

    <style>
        .current-skills {
            margin: 1rem 0;
        }
        .badge {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            margin: 0.25rem;
            font-size: 0.875rem;
        }
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.75rem;
            margin: 1rem 0;
        }
        .skill-checkbox {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .skill-checkbox:hover {
            background-color: #e9ecef;
        }
        .skill-checkbox input {
            margin-right: 0.5rem;
        }
        .skill-checkbox input:checked + * {
            font-weight: bold;
        }
        .form-actions {
            margin-top: 2rem;
            text-align: center;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-left: 1rem;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .help-section {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            margin-top: 2rem;
        }
        .help-section h3 {
            margin-top: 0;
            color: #495057;
        }
        .help-section ul {
            color: #6c757d;
        }
    </style>
</body>
</html>
