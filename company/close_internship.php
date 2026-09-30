<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'company') {
    header("Location: ../index.php");
    exit();
}

$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$internship_id) {
    $_SESSION['error'] = "Invalid internship ID.";
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

// Verify internship belongs to this company and get details
$stmt = $pdo->prepare("
    SELECT i.*, cat.category_name,
           COUNT(a.application_id) as application_count
    FROM internships i
    LEFT JOIN categories cat ON i.category_id = cat.category_id
    LEFT JOIN applications a ON i.internship_id = a.internship_id
    WHERE i.internship_id = ? AND i.company_id = ?
    GROUP BY i.internship_id
");
$stmt->execute([$internship_id, $company['company_id']]);
$internship = $stmt->fetch();

if (!$internship) {
    $_SESSION['error'] = "Internship not found or access denied.";
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update internship status to closed
        $stmt = $pdo->prepare("
            UPDATE internships 
            SET status = 'closed', updated_at = NOW()
            WHERE internship_id = ? AND company_id = ?
        ");
        $stmt->execute([$internship_id, $company['company_id']]);

        // Optionally notify applicants (if notifications system exists)
        // You could add notification logic here

        $_SESSION['success'] = "Internship closed successfully. No new applications will be accepted.";
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        $error = "Error closing internship: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Close Internship - InternLink</title>
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
        <div class="card">
            <h2>Close Internship</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="internship-details">
                <h3><?php echo htmlspecialchars($internship['title']); ?></h3>
                <div class="detail-row">
                    <strong>Category:</strong> <?php echo htmlspecialchars($internship['category_name'] ?? 'N/A'); ?>
                </div>
                <div class="detail-row">
                    <strong>Location:</strong> <?php echo htmlspecialchars($internship['location']); ?>
                </div>
                <div class="detail-row">
                    <strong>Current Status:</strong> 
                    <span class="status-badge status-<?php echo $internship['status']; ?>">
                        <?php echo ucfirst($internship['status']); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <strong>Applications Received:</strong> <?php echo $internship['application_count']; ?>
                </div>
                <div class="detail-row">
                    <strong>Posted Date:</strong> <?php echo date('M d, Y', strtotime($internship['created_at'])); ?>
                </div>
                <div class="detail-row">
                    <strong>Application Deadline:</strong> <?php echo date('M d, Y', strtotime($internship['deadline'])); ?>
                </div>
            </div>

            <?php if ($internship['status'] === 'active'): ?>
                <div class="warning-message">
                    <h4>⚠️ Warning</h4>
                    <p>Closing this internship will:</p>
                    <ul>
                        <li>Stop accepting new applications</li>
                        <li>Hide the internship from search results</li>
                        <li>Keep existing applications accessible for review</li>
                        <li>This action can be reversed later if needed</li>
                    </ul>
                </div>

                <div class="action-buttons">
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to close this internship? Students will no longer be able to apply.')">
                            Yes, Close Internship
                        </button>
                    </form>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            <?php else: ?>
                <div class="info-message">
                    <p>This internship is already closed. You can reopen it from your dashboard if needed.</p>
                    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .internship-details {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .internship-details h3 {
            margin-top: 0;
            color: #007bff;
        }
        .detail-row {
            margin: 0.75rem 0;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
        }
        .status-active {
            background-color: #28a745;
            color: white;
        }
        .status-closed {
            background-color: #dc3545;
            color: white;
        }
        .warning-message {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        .warning-message h4 {
            margin-top: 0;
            color: #856404;
        }
        .warning-message ul {
            color: #856404;
        }
        .info-message {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            text-align: center;
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
