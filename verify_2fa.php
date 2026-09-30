<?php
require_once 'includes/db.php';

if (!isset($_SESSION['2fa_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['2fa_user_id'];
$username = $_SESSION['2fa_username'];
$role = $_SESSION['2fa_role'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize($_POST['code']);
    
    // Get user's 2fa_secret
    $stmt = $pdo->prepare("SELECT 2fa_secret FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && !empty($user['2fa_secret'])) {
        $generated = generate_totp($user['2fa_secret']);
        if ($code === $generated) {
            // 2FA successful
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_username']);
            unset($_SESSION['2fa_role']);
            
            // Redirect based on role
            switch ($role) {
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
        } else {
            $error = "Invalid code. Please try again.";
        }
    } else {
        $error = "2FA not set up for this account.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify 2FA - InternLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="text-center mb-4">
            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
            <h1 class="fw-bold">Two-Factor Authentication</h1>
            <p class="text-muted">Enter your verification code</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="text-center mb-4">
                <i class="fas fa-mobile-alt fa-2x text-primary mb-3"></i>
                <h5 class="fw-bold">Check your authenticator app</h5>
                <p class="text-muted">Enter the 6-digit code from your device</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="code" class="fw-bold text-center d-block">
                        <i class="fas fa-key me-2 text-primary"></i>Verification Code
                    </label>
                    <input type="text" id="code" name="code" required maxlength="6" pattern="\d{6}" 
                           placeholder="000000" class="text-center" style="font-size: 1.5rem; letter-spacing: 0.5rem; font-weight: bold;">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-check me-2"></i>Verify Code
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="login.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>