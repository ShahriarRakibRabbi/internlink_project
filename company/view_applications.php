<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'company') {
    header("Location: ../index.php");
    exit();
}

// Get internship details and applications
$stmt = $pdo->prepare("
    SELECT i.* 
    FROM internships i
    JOIN companies c ON i.company_id = c.company_id
    WHERE i.internship_id = ? AND c.user_id = ?
");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$internship = $stmt->fetch();

if (!$internship) {
    header("Location: dashboard.php");
    exit();
}

// Get applications for this internship
$stmt = $pdo->prepare("
    SELECT a.*, 
           s.full_name, s.university,
           GROUP_CONCAT(sk.name) as skills
    FROM applications a
    JOIN students s ON a.student_id = s.student_id
    LEFT JOIN student_skills ss ON s.student_id = ss.student_id
    LEFT JOIN skills sk ON ss.skill_id = sk.skill_id
    WHERE a.internship_id = ?
    GROUP BY a.application_id
    ORDER BY a.applied_at DESC
");
$stmt->execute([$_GET['id']]);
$applications = $stmt->fetchAll();

// Handle application status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET status = ? 
            WHERE application_id = ? AND internship_id = ?
        ");
        
        $stmt->execute([
            $_POST['status'],
            $_POST['application_id'],
            $_GET['id']
        ]);

        // Add notification for the student
        $notificationMsg = "Your application for '{$internship['title']}' has been " . $_POST['status'];
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message)
            SELECT u.user_id, ?
            FROM applications a
            JOIN students s ON a.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            WHERE a.application_id = ?
        ");
        $stmt->execute([$notificationMsg, $_POST['application_id']]);

        header("Location: view_applications.php?id=" . $_GET['id'] . "&success=1");
        exit();
    } catch (Exception $e) {
        $error = "Error updating application status.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - InternLink</title>
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
        <div class="internship-header">
            <h2>Applications for: <?php echo htmlspecialchars($internship['title']); ?></h2>
            <div class="internship-meta">
                <span class="badge">
                    <?php echo count($applications); ?> applications
                </span>
                <span class="status status-<?php echo $internship['status']; ?>">
                    <?php echo ucfirst($internship['status']); ?>
                </span>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Application status updated successfully!</div>
        <?php endif; ?>

        <?php if (!empty($applications)): ?>
            <div class="applications-grid">
                <?php foreach ($applications as $application): ?>
                    <div class="card application-card">
                        <div class="application-header">
                            <h3><?php echo htmlspecialchars($application['full_name']); ?></h3>
                            <span class="status status-<?php echo $application['status']; ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </span>
                        </div>

                        <div class="application-details">
                            <p><strong>University:</strong> <?php echo htmlspecialchars($application['university']); ?></p>
                            
                            <?php if ($application['skills']): ?>
                                <div class="skills">
                                    <strong>Skills:</strong>
                                    <div class="skills-list">
                                        <?php foreach (explode(',', $application['skills']) as $skill): ?>
                                            <span class="badge"><?php echo htmlspecialchars($skill); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="cover-letter">
                                <strong>Cover Letter:</strong>
                                <p><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
                            </div>

                            <p class="applied-date">
                                Applied on <?php echo date('M d, Y', strtotime($application['applied_at'])); ?>
                            </p>
                        </div>

                        <?php if ($application['status'] === 'pending'): ?>
                            <div class="application-actions">
                                <form method="POST" action="" class="status-form">
                                    <input type="hidden" name="application_id" 
                                           value="<?php echo $application['application_id']; ?>">
                                    
                                    <button type="submit" name="action" value="update_status"
                                            class="btn btn-success" 
                                            onclick="return confirm('Are you sure you want to accept this application?')"
                                            formmethod="POST">
                                        <input type="hidden" name="status" value="accepted">
                                        Accept
                                    </button>
                                </form>
                                <form method="POST" action="" class="status-form">
                                    <input type="hidden" name="application_id" 
                                           value="<?php echo $application['application_id']; ?>">
                                    
                                    <button type="submit" name="action" value="update_status"
                                            class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to reject this application?')"
                                            formmethod="POST">
                                        <input type="hidden" name="status" value="rejected">
                                        Reject
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <p>No applications received yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .internship-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .internship-meta {
            display: flex;
            gap: 1rem;
        }
        .applications-grid {
            display: grid;
            gap: 1rem;
        }
        .application-card {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .application-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .cover-letter {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 3px;
            margin: 0.5rem 0;
        }
        .applied-date {
            color: #666;
            font-size: 0.875rem;
        }
        .application-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .status {
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
        .status-closed {
            background-color: #6c757d;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        @media (min-width: 768px) {
            .applications-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</body>
</html>
