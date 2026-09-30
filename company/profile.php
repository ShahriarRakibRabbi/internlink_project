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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['company_name'])) {
            throw new Exception("Company name is required.");
        }
        
        $stmt = $pdo->prepare("
            UPDATE companies 
            SET company_name = ?, description = ?, location = ?, 
                website = ?, phone = ?, contact_person = ?
            WHERE company_id = ?
        ");
        
        $result = $stmt->execute([
            sanitize($_POST['company_name']),
            sanitize($_POST['description']),
            sanitize($_POST['location']),
            sanitize($_POST['website']),
            sanitize($_POST['phone']),
            sanitize($_POST['contact_person']),
            $company['company_id']
        ]);

        if ($result) {
            $success = "Company profile updated successfully!";
            
            // Refresh the page to show updated data
            header("Location: profile.php?success=1");
            exit();
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    } catch (PDOException $e) {
        // Log the actual error for debugging (in production, log to file instead)
        error_log("Database error in profile update: " . $e->getMessage());
        $error = "Database error occurred. Please check if all required fields are filled correctly.";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = "Company profile updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - InternLink</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <a href="dashboard.php">Dashboard</a>
                <a href="post_internship.php">Post Internship</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Company Profile</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="company_name">Company Name:</label>
                    <input type="text" id="company_name" name="company_name" 
                           value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" 
                           value="<?php echo htmlspecialchars($company['email']); ?>" disabled>
                    <p class="help-text">Email cannot be changed</p>
                </div>

                <div class="form-group">
                    <label for="description">Company Description:</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo htmlspecialchars($company['location'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="website">Website:</label>
                    <input type="url" id="website" name="website" 
                           value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>"
                           placeholder="https://example.com">
                </div>

                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="contact_person">Contact Person:</label>
                    <input type="text" id="contact_person" name="contact_person" 
                           value="<?php echo htmlspecialchars($company['contact_person'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>

    <script>
        // Basic form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const companyName = document.getElementById('company_name').value.trim();
            const website = document.getElementById('website').value.trim();
            
            // Check if company name is empty
            if (!companyName) {
                e.preventDefault();
                alert('Company name is required.');
                return;
            }
            
            // Validate website URL format
            if (website && !website.match(/^https?:\/\/.+\..+$/)) {
                e.preventDefault();
                alert('Please enter a valid website URL starting with http:// or https://');
                return;
            }
        });
    </script>
</body>
</html>
