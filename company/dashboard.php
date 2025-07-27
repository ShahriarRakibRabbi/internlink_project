<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'company') {
    header("Location: ../index.php");
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

// Get company's internships
$stmt = $pdo->prepare("
    SELECT i.*, 
           COUNT(a.application_id) as application_count,
           cat.name as category_name
    FROM internships i
    LEFT JOIN applications a ON i.internship_id = a.internship_id
    JOIN categories cat ON i.category_id = cat.category_id
    WHERE i.company_id = ?
    GROUP BY i.internship_id
    ORDER BY i.posted_at DESC
");
$stmt->execute([$company['company_id']]);
$internships = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard - InternLink</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <a href="../index.php">Home</a>
                <a href="profile.php">Company Profile</a>
                <a href="post_internship.php">Post Internship</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Company Profile Summary -->
        <div class="profile-header">
            <h2>Welcome, <?php echo htmlspecialchars($company['company_name']); ?></h2>
            <p>Email: <?php echo htmlspecialchars($company['email']); ?></p>
            <p>Location: <?php echo htmlspecialchars($company['location'] ?? 'Not set'); ?></p>
            <a href="profile.php" class="btn">Edit Profile</a>
            <a href="post_internship.php" class="btn btn-primary">Post New Internship</a>
        </div>

        <!-- Internships List -->
        <h3>Your Internship Listings</h3>
        <?php if (!empty($internships)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Applications</th>
                            <th>Status</th>
                            <th>Posted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($internships as $internship): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($internship['title']); ?></td>
                                <td><?php echo htmlspecialchars($internship['category_name']); ?></td>
                                <td>
                                    <a href="view_applications.php?id=<?php echo $internship['internship_id']; ?>">
                                        <?php echo $internship['application_count']; ?> applications
                                    </a>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $internship['status']; ?>">
                                        <?php echo ucfirst($internship['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($internship['posted_at'])); ?></td>
                                <td>
                                    <a href="edit_internship.php?id=<?php echo $internship['internship_id']; ?>" 
                                       class="btn">Edit</a>
                                    <?php if ($internship['status'] === 'open'): ?>
                                        <a href="close_internship.php?id=<?php echo $internship['internship_id']; ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Are you sure you want to close this internship?')">
                                            Close
                                        </a>
                                    <?php else: ?>
                                        <a href="reopen_internship.php?id=<?php echo $internship['internship_id']; ?>" 
                                           class="btn btn-primary">
                                            Reopen
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
                <p>You haven't posted any internships yet.</p>
                <a href="post_internship.php" class="btn btn-primary">Post Your First Internship</a>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
        }
        .status-open {
            background-color: #28a745;
            color: white;
        }
        .status-closed {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</body>
</html>
