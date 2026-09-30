<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'company') {
    header("Location: ../index.php");
    exit();
}

// Get company ID
$stmt = $pdo->prepare("SELECT company_id FROM companies WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch();

// Get all categories
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $error = "Categories table not found. Please run the database setup first.";
}

// Get all skills
try {
    $stmt = $pdo->query("SELECT * FROM skills ORDER BY skill_name");
    $skills = $stmt->fetchAll();
} catch (PDOException $e) {
    $skills = [];
    if (empty($error)) {
        $error = "Skills table not found. Please run the database setup first.";
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['title'])) {
            throw new Exception("Internship title is required.");
        }
        if (empty($_POST['category_id'])) {
            throw new Exception("Category is required.");
        }
        if (empty($_POST['description'])) {
            throw new Exception("Description is required.");
        }
        if (empty($_POST['location'])) {
            throw new Exception("Location is required.");
        }
        if (empty($_POST['deadline'])) {
            throw new Exception("Application deadline is required.");
        }
        
        $pdo->beginTransaction();

        // Insert internship
        $stmt = $pdo->prepare("
            INSERT INTO internships (
                company_id, category_id, title, description, 
                location, duration, stipend, requirements, deadline
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $company['company_id'],
            (int)$_POST['category_id'],
            sanitize($_POST['title']),
            sanitize($_POST['description']),
            sanitize($_POST['location']),
            sanitize($_POST['duration']),
            sanitize($_POST['stipend']),
            sanitize($_POST['requirements']),
            $_POST['deadline']
        ]);

        if (!$result) {
            throw new Exception("Failed to create internship posting.");
        }

        $internship_id = $pdo->lastInsertId();

        // Insert skills
        if (!empty($_POST['skills'])) {
            $stmt = $pdo->prepare("
                INSERT INTO internship_skills (internship_id, skill_id) 
                VALUES (?, ?)
            ");
            
            foreach ($_POST['skills'] as $skill_id) {
                $stmt->execute([$internship_id, (int)$skill_id]);
            }
        }

        $pdo->commit();
        $success = "Internship posted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        // Log the actual error for debugging (in production, log to file instead)
        error_log("Database error in internship posting: " . $e->getMessage());
        $error = "Database error occurred. Please check if all required fields are filled correctly.";
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
    <title>Post Internship - InternLink</title>
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
        <h2>Post New Internship</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
                <?php if (strpos($error, 'table not found') !== false): ?>
                    <br><br>
                    <strong>Quick Fix:</strong> 
                    <a href="../fix_database.php" class="btn btn-primary">Run Database Setup</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <br>
                <a href="dashboard.php">Return to Dashboard</a>
            </div>
        <?php endif; ?>

        <?php if (empty($categories) || empty($skills)): ?>
            <div class="alert alert-warning">
                <h3>Database Setup Required</h3>
                <p>The categories and skills tables are missing. Please run the database setup to continue.</p>
                <a href="../fix_database.php" class="btn btn-primary">Set Up Database Tables</a>
            </div>
        <?php else: ?>
        <div class="card">
            <form method="POST" action="" id="postInternshipForm">
                <div class="form-group">
                    <label for="title">Internship Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="5" required></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" required>
                </div>

                <div class="form-group">
                    <label for="duration">Duration:</label>
                    <input type="text" id="duration" name="duration" placeholder="e.g., 3 months" required>
                </div>

                <div class="form-group">
                    <label for="stipend">Stipend:</label>
                    <input type="text" id="stipend" name="stipend" placeholder="e.g., $500/month">
                </div>

                <div class="form-group">
                    <label for="requirements">Requirements:</label>
                    <textarea id="requirements" name="requirements" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="skills">Required Skills:</label>
                    <div class="skills-grid">
                        <?php foreach ($skills as $skill): ?>
                            <label class="skill-checkbox">
                                <input type="checkbox" name="skills[]" value="<?php echo $skill['skill_id']; ?>">
                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="deadline">Application Deadline:</label>
                    <input type="date" id="deadline" name="deadline" required>
                </div>

                <button type="submit" class="btn btn-primary">Post Internship</button>
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
            gap: 0.5rem;
        }
        textarea {
            width: 100%;
            padding: 0.5rem;
        }
    </style>

    <script>
        // Set minimum date for deadline
        const deadlineInput = document.getElementById('deadline');
        const today = new Date().toISOString().split('T')[0];
        deadlineInput.min = today;
        
        // Form validation
        document.getElementById('postInternshipForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (title.length < 5) {
                e.preventDefault();
                alert('Title must be at least 5 characters long');
                return;
            }
            
            if (description.length < 50) {
                e.preventDefault();
                alert('Description must be at least 50 characters long');
                return;
            }
        });
    </script>
</body>
</html>