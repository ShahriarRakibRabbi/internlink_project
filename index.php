<?php
require_once 'includes/db.php';

// Get latest internships (with category and company info)
$stmt = $pdo->query("
    SELECT i.*, c.company_name, cat.category_name
    FROM internship_offers i
    JOIN companies c ON i.company_id = c.company_id
    JOIN offer_categories oc ON i.offer_id = oc.offer_id
    JOIN categories cat ON oc.category_id = cat.category_id
    ORDER BY i.created_at DESC
    LIMIT 6
");
$internships = $stmt->fetchAll();

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to InternLink</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <?php if (isLoggedIn()): ?>
                    <?php if (getUserRole() === 'student'): ?>
                        <a href="student/dashboard.php">Dashboard</a>
                    <?php elseif (getUserRole() === 'company'): ?>
                        <a href="company/dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Search Section -->
        <div class="search-form">
            <h2>Find Your Perfect Internship</h2>
            <form action="search.php" method="GET">
                <input type="text" name="q" class="search-input" placeholder="Search internships...">
                <select name="category" class="form-group">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <!-- Latest Internships -->
        <h2>Latest Internships</h2>
        <div class="grid">
            <?php foreach ($internships as $internship): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($internship['role']); ?></h3>
                    <p><strong>Company:</strong> <?php echo htmlspecialchars($internship['company_name']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($internship['category_name']); ?></p>
                    <p><strong>Stipend:</strong> <?php echo htmlspecialchars($internship['stipend']); ?></p>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($internship['duration']); ?></p>
                    <p><strong>Posted:</strong> <?php echo date('M d, Y', strtotime($internship['created_at'])); ?></p>
                    <a href="internship.php?id=<?php echo $internship['offer_id']; ?>" class="btn">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($internships)): ?>
            <p>No internships available at the moment.</p>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="card" style="text-align: center; margin-top: 2rem;">
            <h2>Join InternLink Today!</h2>
            <p>Connect with top companies and find your dream internship.</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
