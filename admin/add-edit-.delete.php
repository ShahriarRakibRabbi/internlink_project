<?php
session_start();

// AUTHENTICATION CHECK Korbe
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

// DATABASE CONNECTION
$mysqli = new mysqli('localhost', 'your_db_user', 'your_db_pass', 'interlink');
if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}


// ADD USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    if ($username && $email && $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $username, $email, $hashed);
        $stmt->execute();
        $stmt->close();
    }
}

// EDIT USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    if ($id && $username && $email) {
        $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->bind_param('ssi', $username, $email, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// DELETE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = intval($_POST['id']);
    if ($id) {
        $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}

// FETCH USERS
$users = [];
$res = $mysqli->query("SELECT id, username, email FROM users ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
$res->free();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - User Management</title>
    <style>
        table {border-collapse: collapse;}
        th, td {padding: 8px 12px; border: 1px solid #ccc;}
        form.inline {display: inline;}
    </style>
</head>
<body>
    <h1>User Management</h1>

    <h2>Add User</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="add_user">Add User</button>
    </form>

    <h2>User List</h2>
    <table>
        <tr>
            <th>ID</th><th>Username</th><th>Email</th><th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <form method="post" class="inline">
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </td>
                <td>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </td>
                <td>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                    <button type="submit" name="edit_user">Edit</button>
                </form>
                <form method="post" class="inline" onsubmit="return confirm('Delete this user?');">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                    <button type="submit" name="delete_user">Delete</button>
                </form>
                </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>