<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'student') {
    header("Location: ../index.php");
    exit();
}

$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$application_id) {
    header("Location: dashboard.php");
    exit();
}

try {
    // Get student ID
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        throw new Exception("Student profile not found");
    }

    // Check if application belongs to this student and is still pending
    $stmt = $pdo->prepare("
        SELECT a.*, i.title as internship_title, c.company_name
        FROM applications a
        JOIN internships i ON a.internship_id = i.internship_id
        JOIN companies c ON i.company_id = c.company_id
        WHERE a.application_id = ? AND a.student_id = ? AND a.status = 'pending'
    ");
    $stmt->execute([$application_id, $student['student_id']]);
    $application = $stmt->fetch();

    if (!$application) {
        $_SESSION['error'] = "Application not found or cannot be withdrawn.";
        header("Location: dashboard.php");
        exit();
    }

    // If this is a POST request, withdraw the application
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE application_id = ?");
        $stmt->execute([$application_id]);

        $_SESSION['success'] = "Application withdrawn successfully.";
        header("Location: dashboard.php");
        exit();
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Application - InternLink</title>
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
        <div class="card">
            <h2>Withdraw Application</h2>
            
            <div class="application-details">
                <h3><?php echo htmlspecialchars($application['internship_title']); ?></h3>
                <p><strong>Company:</strong> <?php echo htmlspecialchars($application['company_name']); ?></p>
                <p><strong>Applied on:</strong> <?php echo date('M d, Y', strtotime($application['applied_at'])); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($application['status']); ?></p>
            </div>

            <div class="warning-message">
                <p><strong>Warning:</strong> Are you sure you want to withdraw this application? This action cannot be undone.</p>
            </div>

            <div class="action-buttons">
                <form method="POST" action="" style="display: inline;">
                    <button type="submit" class="btn btn-danger">Yes, Withdraw Application</button>
                </form>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>

    <style>
        .application-details {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .application-details h3 {
            margin-top: 0;
            color: #007bff;
        }
        .warning-message {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .warning-message p {
            margin: 0;
            color: #856404;
        }
        .action-buttons {
            text-align: center;
            margin-top: 2rem;
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
</body>
</html>
