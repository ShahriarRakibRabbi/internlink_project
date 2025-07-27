<?php
require_once 'includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

// Get internship details
$stmt = $pdo->prepare("
    SELECT i.*, 
           c.company_name, c.location as company_location, c.website,
           cat.name as category_name,
           GROUP_CONCAT(s.name) as required_skills
    FROM internships i
    JOIN companies c ON i.company_id = c.company_id
    JOIN categories cat ON i.category_id = cat.category_id
    LEFT JOIN internship_skills is1 ON i.internship_id = is1.internship_id
    LEFT JOIN skills s ON is1.skill_id = s.skill_id
    WHERE i.internship_id = ?
    GROUP BY i.internship_id
");
$stmt->execute([$_GET['id']]);
$internship = $stmt->fetch();

if (!$internship) {
    header("Location: index.php");
    exit();
}

// Check if student has already applied
$hasApplied = false;
$application = null;
if (isLoggedIn() && getUserRole() === 'student') {
    $stmt = $pdo->prepare("
        SELECT a.* 
        FROM applications a
        JOIN students s ON a.student_id = s.student_id
        WHERE s.user_id = ? AND a.internship_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $_GET['id']]);
    $application = $stmt->fetch();
    $hasApplied = (bool)$application;
}

// Handle application submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && getUserRole() === 'student') {
    try {
        // Get student_id
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch();

        if (!$student) {
            throw new Exception("Student profile not found");
        }

        // Insert application
        $stmt = $pdo->prepare("
            INSERT INTO applications (internship_id, student_id, cover_letter)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $_GET['id'],
            $student['student_id'],
            sanitize($_POST['cover_letter'])
        ]);

        $success = "Your application has been submitted successfully!";
        $hasApplied = true;

        // Fetch the newly created application for display
        $stmt = $pdo->prepare("
            SELECT a.* 
            FROM applications a
            JOIN students s ON a.student_id = s.student_id
            WHERE s.user_id = ? AND a.internship_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $_GET['id']]);
        $application = $stmt->fetch();
    } catch (Exception $e) {
        $error = "Error submitting application. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($internship['title']); ?> - InternLink</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <a href="index.php">Home</a>
                <a href="search.php">Search</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo getUserRole(); ?>/dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="internship-details">
            <!-- Header -->
            <div class="details-header">
                <h2><?php echo htmlspecialchars($internship['title']); ?></h2>
                <div class="company-info">
                    <h3><?php echo htmlspecialchars($internship['company_name']); ?></h3>
                    <?php if ($internship['website']): ?>
                        <a href="<?php echo htmlspecialchars($internship['website']); ?>" target="_blank" class="website-link">
                            Visit Website
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Details -->
            <div class="details-grid">
                <div class="details-main card">
                    <div class="detail-group">
                        <h4>Description</h4>
                        <p><?php echo nl2br(htmlspecialchars($internship['description'])); ?></p>
                    </div>

                    <div class="detail-group">
                        <h4>Requirements</h4>
                        <p><?php echo nl2br(htmlspecialchars($internship['requirements'])); ?></p>
                    </div>

                    <?php if ($internship['required_skills']): ?>
                        <div class="detail-group">
                            <h4>Required Skills</h4>
                            <div class="skills-list">
                                <?php foreach (explode(',', $internship['required_skills']) as $skill): ?>
                                    <span class="badge"><?php echo htmlspecialchars($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="details-sidebar">
                    <div class="card">
                        <h4>Quick Info</h4>
                        <ul class="quick-info">
                            <li>
                                <span class="icon">📍</span>
                                <span>Location: <?php echo htmlspecialchars($internship['location']); ?></span>
                            </li>
                            <li>
                                <span class="icon">⏱️</span>
                                <span>Duration: <?php echo htmlspecialchars($internship['duration']); ?></span>
                            </li>
                            <li>
                                <span class="icon">💰</span>
                                <span>Stipend: <?php echo htmlspecialchars($internship['stipend']); ?></span>
                            </li>
                            <li>
                                <span class="icon">📅</span>
                                <span>Posted: <?php echo date('M d, Y', strtotime($internship['posted_at'])); ?></span>
                            </li>
                            <li>
                                <span class="icon">⏰</span>
                                <span>Deadline: <?php echo date('M d, Y', strtotime($internship['deadline'])); ?></span>
                            </li>
                        </ul>
                    </div>

                    <?php if ($internship['status'] === 'open'): ?>
                        <?php if (isLoggedIn() && getUserRole() === 'student'): ?>
                            <?php if ($hasApplied): ?>
                                <div class="card application-status">
                                    <h4>Application Status</h4>
                                    <p class="status status-<?php echo $application['status']; ?>">
                                        <?php echo ucfirst($application['status']); ?>
                                    </p>
                                    <p class="applied-date">
                                        Applied on <?php echo date('M d, Y', strtotime($application['applied_at'])); ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="card">
                                    <h4>Apply Now</h4>
                                    <form method="POST" action="" id="applicationForm">
                                        <div class="form-group">
                                            <label for="cover_letter">Cover Letter:</label>
                                            <textarea id="cover_letter" name="cover_letter" rows="6" required></textarea>
                                            <p class="help-text">Explain why you're a good fit for this role</p>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Application</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php elseif (!isLoggedIn()): ?>
                            <div class="card">
                                <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> as a student to apply.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="card">
                            <p class="status status-closed">This internship is no longer accepting applications.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .internship-details {
            margin-top: 2rem;
        }
        .details-header {
            margin-bottom: 2rem;
        }
        .company-info {
            margin-top: 0.5rem;
            color: #666;
        }
        .website-link {
            display: inline-block;
            margin-top: 0.5rem;
            color: #007bff;
            text-decoration: none;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        .detail-group {
            margin-bottom: 2rem;
        }
        .detail-group h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        .quick-info {
            list-style: none;
            padding: 0;
        }
        .quick-info li {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .quick-info .icon {
            margin-right: 0.5rem;
            width: 24px;
        }
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .badge {
            background-color: #e9ecef;
            color: #333;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
        }
        .status {
            padding: 0.5rem;
            border-radius: 3px;
            text-align: center;
            margin: 1rem 0;
        }
        .status-pending {
            background-color: #ffd700;
            color: #000;
        }
        .status-accepted {
            background-color: #28a745;
            color: white;
        }
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }
        .status-closed {
            background-color: #6c757d;
            color: white;
        }
        .applied-date {
            color: #666;
            font-size: 0.875rem;
            text-align: center;
        }
        .help-text {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        // Form validation
        document.getElementById('applicationForm')?.addEventListener('submit', function(e) {
            const coverLetter = document.getElementById('cover_letter').value.trim();
            
            if (coverLetter.length < 100) {
                e.preventDefault();
                alert('Please write a cover letter of at least 100 characters');
                return;
            }
        });
    </script>
</body>
</html>
