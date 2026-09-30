<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'delete_user':
                    $user_id = (int)$_POST['user_id'];
                    if ($user_id && $user_id != $_SESSION['user_id']) { // Prevent deleting self
                        try {
                            $pdo->beginTransaction();

                            // Delete related records
                            $stmt = $pdo->prepare("DELETE FROM students WHERE user_id = ?");
                            $stmt->execute([$user_id]);

                            $stmt = $pdo->prepare("DELETE FROM companies WHERE user_id = ?");
                            $stmt->execute([$user_id]);

                            // Delete user (only non-admins)
                            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
                            $stmt->execute([$user_id]);

                            $pdo->commit();
                            $success = "User deleted successfully.";
                        } catch (Exception $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            throw $e;
                        }
                    }
                    break;

                case 'toggle_status':
                    $user_id = (int)$_POST['user_id'];
                    $current_status = $_POST['status'] ?? 'active';
                    $new_status = $current_status === 'active' ? 'inactive' : 'active';

                    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
                    $stmt->execute([$new_status, $user_id]);

                    $success = "User status updated successfully.";
                    break;
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error: " . $e->getMessage();
    }
}

// Get all users with additional info
try {
    $stmt = $pdo->query("
        SELECT u.*, 
               CASE 
                   WHEN u.role = 'student' THEN s.full_name
                   WHEN u.role = 'company' THEN c.company_name
                   ELSE u.username
               END AS display_name,
               CASE 
                   WHEN u.role = 'student' THEN s.university
                   WHEN u.role = 'company' THEN c.location
                   ELSE NULL
               END AS additional_info
        FROM users u
        LEFT JOIN students s ON u.user_id = s.user_id
        LEFT JOIN companies c ON u.user_id = c.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading users: " . $e->getMessage();
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - InternLink Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink Admin</h1>
            <div>
                <a href="dashboard.php">Dashboard</a>
                <a href="users.php" class="active">Users</a>
                <a href="../internship.php">Internships</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Manage Users</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Additional Info</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['display_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['additional_info'] ?? '-'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['role'] !== 'admin' && $user['user_id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $user['status'] ?? 'active'; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <?php echo ($user['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-stats">
            <h3>User Statistics</h3>
            <div class="stats-row">
                <div class="stat-item">
                    <strong>Total Users:</strong> <?php echo count($users); ?>
                </div>
                <div class="stat-item">
                    <strong>Students:</strong> <?php echo count(array_filter($users, fn($u) => $u['role'] === 'student')); ?>
                </div>
                <div class="stat-item">
                    <strong>Companies:</strong> <?php echo count(array_filter($users, fn($u) => $u['role'] === 'company')); ?>
                </div>
                <div class="stat-item">
                    <strong>Admins:</strong> <?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?>
                </div>
            </div>
        </div>
    </div>

    <style>
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
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
        }
        .status-active {
            background-color: #28a745;
            color: white;
        }
        .status-inactive {
            background-color: #6c757d;
            color: white;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 0.125rem;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .admin-stats {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .stats-row {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }
        .stat-item {
            flex: 1;
        }
        .text-muted {
            color: #6c757d;
        }
        .active {
            font-weight: bold;
            color: #007bff;
        }
    </style>
</body>
</html>

