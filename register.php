<?php
require_once 'includes/db.php';

$error = '';
$success = '';

// Enable PDO error reporting for debugging
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);

    // Basic validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif ($role === 'student' && (!isset($_POST['full_name']) || empty(trim($_POST['full_name'])))) {
        $error = "Full name is required for students";
    } elseif ($role === 'company' && (!isset($_POST['company_name']) || empty(trim($_POST['company_name'])))) {
        $error = "Company name is required for companies";
    } elseif ($role === 'admin') {
        $error = "Admin registration is not allowed";
    } else {
        try {
            // Check for existing username or email
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "Username or email already exists";
            } else {
                $pdo->beginTransaction();

                // Generate 2FA secret
                $secret = generate_2fa_secret();

                // Insert into users table
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, role, 2fa_secret) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([$username, $email, $hashed_password, $role, $secret]);
                $user_id = $pdo->lastInsertId();

                // Insert additional info based on role
                if ($role === 'student') {
                    $stmt = $pdo->prepare("
                        INSERT INTO students (user_id, full_name) 
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$user_id, sanitize($_POST['full_name'])]);
                } elseif ($role === 'company') {
                    $stmt = $pdo->prepare("
                        INSERT INTO companies (user_id, company_name) 
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$user_id, sanitize($_POST['company_name'])]);
                }

                $pdo->commit();
                // Store secret in session for QR code display
                $_SESSION['2fa_secret'] = $secret;
                $_SESSION['username'] = $username;
                $success = "Registration successful! Please set up 2FA.";
                // Redirect to 2FA setup
                header("Location: setup_2fa.php");
                exit();
            }
        } catch(PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - InternLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="text-center mb-4">
            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i><br />
            <span class="fw-bold" style="color: #f2f3f5df; font-size: 35px; font-weight: bold;">Join InternLink</span>
            <p class="" style="color: white;">Create your account and start your journey</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <br>
                <a href="login.php" class="alert-link">Click here to login</a>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user me-2 text-primary"></i>Username
                    </label>
                    <input type="text" id="username" name="username" required placeholder="Choose a username">
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope me-2 text-primary"></i>Email Address
                    </label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock me-2 text-primary"></i>Password
                    </label>
                    <input type="password" id="password" name="password" required placeholder="Create a strong password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock me-2 text-primary"></i>Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                </div>

                <div class="form-group">
                    <label for="role">
                        <i class="fas fa-users me-2 text-primary"></i>I am a
                    </label>
                    <select id="role" name="role" required class="form-select">
                        <option value="">Select your role</option>
                        <option value="student">🎓 Student</option>
                        <option value="company">🏢 Company</option>
                    </select>
                </div>

                <!-- Dynamic fields based on role -->
                <div id="studentFields" style="display: none;">
                    <div class="form-group">
                        <label for="full_name">
                            <i class="fas fa-id-card me-2 text-primary"></i>Full Name
                        </label>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name">
                    </div>
                </div>

                <div id="companyFields" style="display: none;">
                    <div class="form-group">
                        <label for="company_name">
                            <i class="fas fa-building me-2 text-primary"></i>Company Name
                        </label>
                        <input type="text" id="company_name" name="company_name" placeholder="Enter your company name">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-muted mb-2">Already have an account?</p>
                <a href="login.php" class="btn btn-outline-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In Instead
                </a>
            </div>
        </div>
    </div>

    <script>
        // Show/hide fields based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const studentFields = document.getElementById('studentFields');
            const companyFields = document.getElementById('companyFields');
            const adminFields = document.getElementById('adminFields');

            if (this.value === 'student') {
                studentFields.style.display = 'block';
                companyFields.style.display = 'none';
                adminFields.style.display = 'none';
                document.getElementById('full_name').required = true;
                document.getElementById('company_name').required = false;
            } else if (this.value === 'company') {
                studentFields.style.display = 'none';
                companyFields.style.display = 'block';
                adminFields.style.display = 'none';
                document.getElementById('full_name').required = false;
                document.getElementById('company_name').required = true;
            } else if (this.value === 'admin') {
                studentFields.style.display = 'none';
                companyFields.style.display = 'none';
                adminFields.style.display = 'block';
                document.getElementById('full_name').required = false;
                document.getElementById('company_name').required = false;
            } else {
                studentFields.style.display = 'none';
                companyFields.style.display = 'none';
                adminFields.style.display = 'none';
                document.getElementById('full_name').required = false;
                document.getElementById('company_name').required = false;
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
