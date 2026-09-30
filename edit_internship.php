<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'company') {
    header("Location: ../index.php");
    exit();
}

$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$internship_id) {
    header("Location: dashboard.php");
    exit();
}

// Get company details
$stmt = $pdo->prepare("
    SELECT c.*, u.email 
    FROM companies c
    JOIN users u ON c.user_id = u.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch();

// Verify internship belongs to this company
$stmt = $pdo->prepare("
    SELECT * FROM internships 
    WHERE internship_id = ? AND company_id = ?
");
$stmt->execute([$internship_id, $company['company_id']]);
$internship = $stmt->fetch();

if (!$internship) {
    $_SESSION['error'] = "Internship not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

// Get categories and skills for form
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

try {
    $stmt = $pdo->query("SELECT * FROM skills ORDER BY skill_name");
    $skills = $stmt->fetchAll();
} catch (PDOException $e) {
    $skills = [];
}

// Get current internship skills
try {
    $stmt = $pdo->prepare("
        SELECT skill_id FROM internship_skills 
        WHERE internship_id = ?
    ");
    $stmt->execute([$internship_id]);
    $currentSkills = array_column($stmt->fetchAll(), 'skill_id');
} catch (PDOException $e) {
    $currentSkills = [];
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['title'])) {
            throw new Exception("Internship title is required.");
        }
        if (empty($_POST['description'])) {
            throw new Exception("Description is required.");
        }

        $pdo->beginTransaction();

        // Update internship
        $stmt = $pdo->prepare("
            UPDATE internships 
            SET title = ?, description = ?, requirements = ?, location = ?, 
                duration = ?, stipend = ?, deadline = ?, category_id = ?, 
                updated_at = NOW()
            WHERE internship_id = ? AND company_id = ?
        ");
        
        $stmt->execute([
            sanitize($_POST['title']),
            sanitize($_POST['description']),
            sanitize($_POST['requirements']),
            sanitize($_POST['location']),
            sanitize($_POST['duration']),
            sanitize($_POST['stipend']),
            $_POST['deadline'],
            (int)$_POST['category_id'],
            $internship_id,
            $company['company_id']
        ]);

        // Update skills
        // First, delete existing skills
        $stmt = $pdo->prepare("DELETE FROM internship_skills WHERE internship_id = ?");
        $stmt->execute([$internship_id]);

        // Then add new skills
        if (!empty($_POST['skills'])) {
            $stmt = $pdo->prepare("INSERT INTO internship_skills (internship_id, skill_id) VALUES (?, ?)");
            foreach ($_POST['skills'] as $skill_id) {
                $stmt->execute([$internship_id, (int)$skill_id]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Internship updated successfully!";
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Internship - InternLink</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <a href="dashboard.php">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Edit Internship</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($categories)): ?>
            <div class="alert alert-warning">
                <h3>Database Setup Required</h3>
                <p>Categories table is missing. Please run the database setup.</p>
                <a href="../fix_database.php" class="btn btn-primary">Set Up Database</a>
            </div>
        <?php else: ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Internship Title:</label>
                    <input type="text" id="title" name="title" 
                           value="<?php echo htmlspecialchars($internship['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"
                                    <?php echo ($category['category_id'] == $internship['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($internship['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo htmlspecialchars($internship['location']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="duration">Duration:</label>
                    <input type="text" id="duration" name="duration" 
                           value="<?php echo htmlspecialchars($internship['duration']); ?>" 
                           placeholder="e.g., 3 months" required>
                </div>

                <div class="form-group">
                    <label for="stipend">Stipend:</label>
                    <input type="text" id="stipend" name="stipend" 
                           value="<?php echo htmlspecialchars($internship['stipend']); ?>" 
                           placeholder="e.g., $500/month">
                </div>

                <div class="form-group">
                    <label for="requirements">Requirements:</label>
                    <textarea id="requirements" name="requirements" rows="3"><?php echo htmlspecialchars($internship['requirements']); ?></textarea>
                </div>

                <?php if (!empty($skills)): ?>
                <div class="form-group">
                    <label for="skills">Required Skills:</label>
                    <div class="skills-grid">
                        <?php foreach ($skills as $skill): ?>
                            <label class="skill-checkbox">
                                <input type="checkbox" name="skills[]" value="<?php echo $skill['skill_id']; ?>"
                                       <?php echo in_array($skill['skill_id'], $currentSkills) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="deadline">Application Deadline:</label>
                    <input type="date" id="deadline" name="deadline" 
                           value="<?php echo $internship['deadline']; ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Internship</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .skill-checkbox {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 3px;
            cursor: pointer;
        }
        .skill-checkbox input {
            margin-right: 0.5rem;
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
    </style>

    <script>
        // Set minimum date for deadline
        const deadlineInput = document.getElementById('deadline');
        const today = new Date().toISOString().split('T')[0];
        deadlineInput.min = today;
    </script>
</body>
</html>
