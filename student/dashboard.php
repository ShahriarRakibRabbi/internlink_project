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

// Get student's applications
$stmt = $pdo->prepare("
    SELECT a.*, o.role as internship_title, c.company_name
    FROM applications a
    JOIN internship_offers o ON a.offer_id = o.offer_id
    JOIN companies c ON o.company_id = c.company_id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->execute([$student['student_id']]);
$applications = $stmt->fetchAll();

// Get student's skills
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM skills s
    JOIN student_skills ss ON s.skill_id = ss.skill_id
    WHERE ss.student_id = ?
");
$stmt->execute([$student['student_id']]);
$skills = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - InternLink</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <a href="../index.php">Home</a>
                <a href="profile.php">Profile</a>
                <a href="../search.php">Find Internships</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Profile Summary -->
        <div class="profile-header">
            <h2>Welcome, <?php echo htmlspecialchars($student['full_name']); ?></h2>
            <p>Email: <?php echo htmlspecialchars($student['email']); ?></p>
            <p>University: <?php echo htmlspecialchars($student['university'] ?? 'Not set'); ?></p>
            <a href="profile.php" class="btn">Edit Profile</a>
        </div>

        <!-- Skills Section -->
        <div class="card">
            <h3>My Skills</h3>
            <div class="skills-list">
                <?php foreach ($skills as $skill): ?>
                    <span class="badge"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                <?php endforeach; ?>
            </div>
            <a href="manage_skills.php" class="btn">Manage Skills</a>
        </div>

        <!-- Applications -->
        <h3>My Applications</h3>
        <?php if (!empty($applications)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Internship</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Applied Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                                <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                <td>
                                    <a href="../internship.php?id=<?php echo $app['offer_id']; ?>" class="btn">View</a>
                                    <?php if ($app['status'] === 'pending'): ?>
                                        <a href="withdraw_application.php?id=<?php echo $app['application_id']; ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Are you sure you want to withdraw this application?')">
                                            Withdraw
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card">
                <p>You haven't applied to any internships yet.</p>
                <a href="../search.php" class="btn btn-primary">Find Internships</a>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .skills-list {
            margin: 1rem 0;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: #007bff;
            color: white;
            border-radius: 3px;
            margin: 0.25rem;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
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
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</body>
</html>
