<?php
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);

    // Basic validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Insert into users table
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES (?, ?, ?, ?)
            ");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$username, $email, $hashed_password, $role]);
            
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
            $success = "Registration successful! You can now login.";

        } catch(PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) { // Duplicate entry error
                $error = "Username or email already exists";
            } else {
                $error = "An error occurred. Please try again later.";
            }
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
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Register for InternLink</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <br>
                <a href="login.php">Click here to login</a>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <label for="role">I am a:</label>
                    <select id="role" name="role" required>
                        <option value="">Select role</option>
                        <option value="student">Student</option>
                        <option value="company">Company</option>
                    </select>
                </div>

                <!-- Dynamic fields based on role -->
                <div id="studentFields" style="display: none;">
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name">
                    </div>
                </div>

                <div id="companyFields" style="display: none;">
                    <div class="form-group">
                        <label for="company_name">Company Name:</label>
                        <input type="text" id="company_name" name="company_name">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Register</button>
            </form>

            <p style="margin-top: 1rem;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>

    <script>
        // Show/hide fields based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const studentFields = document.getElementById('studentFields');
            const companyFields = document.getElementById('companyFields');
            
            if (this.value === 'student') {
                studentFields.style.display = 'block';
                companyFields.style.display = 'none';
                document.getElementById('full_name').required = true;
                document.getElementById('company_name').required = false;
            } else if (this.value === 'company') {
                studentFields.style.display = 'none';
                companyFields.style.display = 'block';
                document.getElementById('full_name').required = false;
                document.getElementById('company_name').required = true;
            } else {
                studentFields.style.display = 'none';
                companyFields.style.display = 'none';
                document.getElementById('full_name').required = false;
                document.getElementById('company_name').required = false;
            }
        });
    </script>
</body>
</html>
