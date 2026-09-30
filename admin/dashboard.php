<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'admin') {
    header("Location: ../index.php");
    exit();
}


try {
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
    // Get total students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $totalStudents = $stmt->fetch()['total'];
    

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM companies");
    $totalCompanies = $stmt->fetch()['total'];
    

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM internships");
    $totalInternships = $stmt->fetch()['total'];
    
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM applications");
    $totalApplications = $stmt->fetch()['total'];
    
    // Get recent users
    $stmt = $pdo->query("
        SELECT u.*, 
               CASE 
                   WHEN u.role = 'student' THEN s.full_name
                   WHEN u.role = 'company' THEN c.company_name
                   ELSE u.username
               END as display_name
        FROM users u
        LEFT JOIN students s ON u.user_id = s.user_id
        LEFT JOIN companies c ON u.user_id = c.user_id
        ORDER BY u.created_at DESC 
        LIMIT 10
    ");
    $recentUsers = $stmt->fetchAll();
    
    // Get recent internships
    $stmt = $pdo->query("
        SELECT i.*, c.company_name 
        FROM internships i
        JOIN companies c ON i.company_id = c.company_id
        ORDER BY i.created_at DESC 
        LIMIT 10
    ");
    $recentInternships = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - InternLink</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink Admin</h1>
            <div>
                <a href="users.php">Manage Users</a>
                    <a href="../internship.php">Manage Internships</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Admin Dashboard</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="stat-number"><?php echo $totalUsers ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Students</h3>
                <div class="stat-number"><?php echo $totalStudents ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Companies</h3>
                <div class="stat-number"><?php echo $totalCompanies ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Internships</h3>
                <div class="stat-number"><?php echo $totalInternships ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Applications</h3>
                <div class="stat-number"><?php echo $totalApplications ?? 0; ?></div>
            </div>
        </div>

       
        <div class="admin-sections">
            <div class="admin-section">
                <h3>Recent Users</h3>
                <?php if (!empty($recentUsers)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['display_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="users.php?edit=<?php echo $user['user_id']; ?>" class="btn btn-sm">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="users.php" class="btn">View All Users</a>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            </div>

            <div class="admin-section">
                <h3>Recent Internships</h3>
                <?php if (!empty($recentInternships)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Company</th>
                                    <th>Status</th>
                                    <th>Posted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInternships as $internship): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($internship['title']); ?></td>
                                        <td><?php echo htmlspecialchars($internship['company_name']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $internship['status']; ?>">
                                                <?php echo ucfirst($internship['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($internship['created_at'])); ?></td>
                                        <td>
                                            <a href="../internship.php?id=<?php echo $internship['internship_id']; ?>" 
                                               class="btn btn-sm">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="../internship.php" class="btn">View All Internships</a>
                <?php else: ?>
                    <p>No internships found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        .admin-sections {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        .admin-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-section h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #333;
        }
        .badge-student {
            background-color: #28a745;
            color: white;
        }
        .badge-company {
            background-color: #007bff;
            color: white;
        }
        .badge-admin {
            background-color: #dc3545;
            color: white;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</body>
</html>