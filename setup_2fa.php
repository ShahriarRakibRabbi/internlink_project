<?php
require_once 'includes/db.php';

if (!isset($_SESSION['2fa_secret']) || !isset($_SESSION['username'])) {
    header("Location: register.php");
    exit();
}

$secret = $_SESSION['2fa_secret'];
$username = $_SESSION['username'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize($_POST['code']);
    $generated = generate_totp($secret);
    if ($code === $generated) {
        // 2FA setup successful
        unset($_SESSION['2fa_secret']);
        unset($_SESSION['username']);
        $success = "2FA setup successful! You can now login.";
        header("Location: login.php");
        exit();
    } else {
        $error = "Invalid code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup 2FA - InternLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="text-center mb-4">
            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
            <h1 class="fw-bold">Set up Two-Factor Authentication</h1>
            <p class="text-muted">Secure your account with 2FA</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="text-center mb-4">
                <i class="fas fa-qrcode fa-2x text-primary mb-3"></i>
                <h5 class="fw-bold">Scan this QR code with your authenticator app</h5>
                <p class="text-muted small">Use Google Authenticator, Authy, or similar apps</p>
            </div>
            
            <div class="text-center mb-4">
                <div id="qrcode" class="d-inline-block p-3 bg-white rounded-3 shadow-sm"></div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Manual Entry:</strong> <?php echo $secret; ?>
                <br>
                <small class="text-muted">Account: <?php echo $username; ?> | Issuer: InternLink</small>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="code" class="fw-bold">
                        <i class="fas fa-keyboard me-2 text-primary"></i>Enter the 6-digit code
                    </label>
                    <input type="text" id="code" name="code" required maxlength="6" pattern="\d{6}" 
                           placeholder="000000" class="text-center" style="font-size: 1.2rem; letter-spacing: 0.5rem;">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-check me-2"></i>Verify and Complete Setup
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        var qrcode = new QRCode(document.getElementById("qrcode"), {
            text: "otpauth://totp/InternLink:<?php echo $username; ?>?secret=<?php echo $secret; ?>&issuer=InternLink",
            width: 128,
            height: 128,
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
