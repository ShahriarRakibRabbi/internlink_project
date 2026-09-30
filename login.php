<?php
session_start();
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check if 2FA is enabled
            if (!empty($user['2fa_secret'])) {
                $_SESSION['2fa_user_id'] = $user['user_id'];
                $_SESSION['2fa_username'] = $user['username'];
                $_SESSION['2fa_role'] = $user['role'];
                header("Location: verify_2fa.php");
                exit();
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'student':
                        header("Location: student/dashboard.php");
                        break;
                    case 'company':
                        header("Location: company/dashboard.php");
                        break;
                    case 'admin':
                        header("Location: admin/dashboard.php");
                        break;
                }
                exit();
            }
        } else {
            $error = "Invalid username or password";
        }
    } catch(PDOException $e) {
        $error = "An error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InternLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="text-center mb-4">
            <i class="fas fa-sign-in-alt fa-3x text-primary mb-3"></i><br />
            <span class="fw-bold" style="color: white; font-size: 35px; font-weight: bold;" >Welcome Back</span>
            <p class="" style="color: white; font-size: 10; font-weight: bold;">Sign in to your InternLink account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user me-2 text-primary"></i>Username
                    </label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock me-2 text-primary"></i>Password
                    </label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-muted mb-2">Don't have an account?</p>
                <a href="register.php" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
