<?php
require_once 'includes/db.php';

//  latest internships Dekhabe
try {
    $stmt = $pdo->query("
        SELECT i.*, c.company_name, cat.category_name
        FROM internships i
        JOIN companies c ON i.company_id = c.company_id
        LEFT JOIN categories cat ON i.category_id = cat.category_id
        WHERE i.status = 'active'
        ORDER BY i.created_at DESC
        LIMIT 6
    ");
    $internships = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback if internships table doesn't exist
    $internships = [];
}

// Get all categories
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback if categories table doesn't exist
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to InternLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-briefcase me-2"></i>InternLink
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if (getUserRole() === 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="student/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                        <?php elseif (getUserRole() === 'company'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="company/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
       
        <div class="text-center py-5 mb-5">
            <h1 class="display-4 fw-bold mb-3" style="background: linear-gradient(135deg, #d4dceeff 0%, #d6bcfa 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                Find Your Dream Internship
            </h1>
            <p class="lead mb-4 text-muted">Connect with top companies and launch your career journey</p>
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <form action="search.php" method="GET" class="d-flex gap-3 justify-content-center flex-wrap">
                        <div class="flex-grow-1" style="min-width: 250px;">
                            <input type="text" name="q" class="form-control form-control-lg" placeholder="Search internships..." style="border-radius: 25px; border: 2px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.9);">
                        </div>
                        <div style="min-width: 180px;">
                            <select name="category" class="form-select form-select-lg" style="border-radius: 25px; border: 2px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.9);">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-lg px-4" style="border-radius: 25px; font-weight: 600;">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="container">
            <h2 class="text-center mb-5 fw-bold" style="color: #1a202c;">Latest Internship Opportunities</h2>
            <div class="row g-4">
                <?php if (!empty($internships)): ?>
                    <?php foreach ($internships as $internship): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3">
                                            <i class="fas fa-briefcase text-primary"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1 fw-bold"><?php echo htmlspecialchars($internship['title'] ?? 'Untitled'); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($internship['category_name'] ?? 'Uncategorized'); ?></small>
                                        </div>
                                    </div>
                                    <p class="card-text mb-3">
                                        <i class="fas fa-building text-muted me-2"></i>
                                        <strong><?php echo htmlspecialchars($internship['company_name'] ?? 'Unknown'); ?></strong>
                                    </p>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Stipend</small>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($internship['stipend'] ?? 'Not specified'); ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Duration</small>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($internship['duration'] ?? 'Not specified'); ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo isset($internship['created_at']) ? date('M d, Y', strtotime($internship['created_at'])) : 'Unknown date'; ?>
                                        </small>
                                        <a href="internship.php?id=<?php echo $internship['internship_id'] ?? 0; ?>" class="btn btn-primary btn-sm px-3" style="border-radius: 20px;">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (empty($internships)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No internships available at the moment</h4>
                    <p class="text-muted">Check back later for new opportunities!</p>
                </div>
            <?php endif; ?>

            
            <div class="card border-0 shadow-lg mt-5" style="border-radius: 25px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);">
                <div class="card-body text-center p-5">
                    <i class="fas fa-rocket fa-3x text-primary mb-4"></i>
                    <h2 class="fw-bold mb-3">Ready to Start Your Journey?</h2>
                    <p class="lead mb-4 text-muted">Join thousands of students who have found their dream internships through InternLink</p>
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-lg px-5 py-3 me-3" style="border-radius: 25px; font-weight: 600;">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="login.php" class="btn btn-outline-primary btn-lg px-5 py-3" style="border-radius: 25px; font-weight: 600;">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>