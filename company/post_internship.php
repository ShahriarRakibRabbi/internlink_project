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
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get all skills
$stmt = $pdo->query("SELECT * FROM skills ORDER BY name");
$skills = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insert internship
        $stmt = $pdo->prepare("
            INSERT INTO internships (
                company_id, category_id, title, description, 
                location, duration, stipend, requirements, deadline
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $company['company_id'],
            $_POST['category_id'],
            sanitize($_POST['title']),
            sanitize($_POST['description']),
            sanitize($_POST['location']),
            sanitize($_POST['duration']),
            sanitize($_POST['stipend']),
            sanitize($_POST['requirements']),
            $_POST['deadline']
        ]);

        $internship_id = $pdo->lastInsertId();

        // Insert skills
        if (!empty($_POST['skills'])) {
            $stmt = $pdo->prepare("
                INSERT INTO internship_skills (internship_id, skill_id) 
                VALUES (?, ?)
            ");
            
            foreach ($_POST['skills'] as $skill_id) {
                $stmt->execute([$internship_id, $skill_id]);
            }
        }

        $pdo->commit();
        $success = "Internship posted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error posting internship. Please try again.";
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
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <br>
                <a href="dashboard.php">Return to Dashboard</a>
            </div>
        <?php endif; ?>

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
                                <?php echo htmlspecialchars($category['name']); ?>
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
                                <?php echo htmlspecialchars($skill['name']); ?>
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
