<?php
require_once 'includes/db.php';

// Get internship ID from URL
$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$internship_id) {
    header("Location: search.php");
    exit();
}

// Try to get internship from new table first, then fall back to old table
$internship = null;
$error = '';

try {
    // Try new internships table
    $stmt = $pdo->prepare("
        SELECT i.*, c.company_name, c.location as company_location, c.website, c.contact_person,
               cat.category_name,
               GROUP_CONCAT(DISTINCT s.skill_name) as required_skills
        FROM internships i
        JOIN companies c ON i.company_id = c.company_id
        JOIN categories cat ON i.category_id = cat.category_id
        LEFT JOIN internship_skills is_skills ON i.internship_id = is_skills.internship_id
        LEFT JOIN skills s ON is_skills.skill_id = s.skill_id
        WHERE i.internship_id = ? AND i.status = 'active'
        GROUP BY i.internship_id
    ");
    $stmt->execute([$internship_id]);
    $internship = $stmt->fetch();
    
    if (!$internship) {
        // Try old internship_offers table
        $stmt = $pdo->prepare("
            SELECT i.*, c.company_name, c.location as company_location, c.website, c.contact_person,
                   'General' as category_name,
                   GROUP_CONCAT(DISTINCT s.skill_name) as required_skills,
                   i.role as title, i.offer_id as internship_id
            FROM internship_offers i
            JOIN companies c ON i.company_id = c.company_id
            LEFT JOIN offer_skills os ON i.offer_id = os.offer_id
            LEFT JOIN skills s ON os.skill_id = s.skill_id
            WHERE i.offer_id = ?
            GROUP BY i.offer_id
        ");
        $stmt->execute([$internship_id]);
        $internship = $stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Error fetching internship: " . $e->getMessage());
    $error = "Error loading internship details.";
}

if (!$internship) {
    $error = "Internship not found or no longer available.";
}

// Check if user can apply (must be logged in student)
$can_apply = false;
$already_applied = false;
$application = null;

// Detect legacy offer (internship coming from internship_offers) and disable applying
$is_legacy_offer = false;
if ($internship) {
    try {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM internships WHERE internship_id = ?");
        $chk->execute([$internship['internship_id']]);
        if ($chk->fetchColumn() == 0) {
            $is_legacy_offer = true;
        }
    } catch (PDOException $e) {
        // ignore
    }
}

if (isLoggedIn() && getUserRole() === 'student' && !$is_legacy_offer) {
    // Ensure the logged-in user has a student profile
    try {
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch();
    } catch (PDOException $e) {
        $student = null;
        error_log("Error checking student profile: " . $e->getMessage());
    }

    if (!$student) {
        // User does not have a student profile — cannot apply
        $can_apply = false;
        $error = "Please complete your student profile before applying to internships.";
    } else {
        $can_apply = true;

        // Check if already applied using the known student_id
        if ($internship) {
            try {
                $stmt = $pdo->prepare(
                    "SELECT * FROM applications WHERE internship_id = ? AND student_id = ?"
                );
                $stmt->execute([$internship['internship_id'], $student['student_id']]);
                $application = $stmt->fetch();
                $already_applied = $application ? true : false;
            } catch (PDOException $e) {
                // Ignore error, assume not applied
                error_log("Error checking existing application: " . $e->getMessage());
            }
        }
    }
}

// Handle application submission
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_apply && !$already_applied) {

    try {
        if (empty($student) || empty($student['student_id'])) {
            throw new Exception("Student profile missing; cannot submit application");
        }

        // Insert application using known student_id
        $stmt = $pdo->prepare(
            "INSERT INTO applications (internship_id, student_id, cover_letter, status, applied_at) VALUES (?, ?, ?, 'pending', NOW())"
        );
        $stmt->execute([
            $internship['internship_id'],
            $student['student_id'],
            sanitize($_POST['cover_letter'])
        ]);

        $success = "Your application has been submitted successfully!";
        $already_applied = true;

        // Fetch the newly created application for display
        $stmt = $pdo->prepare("
            SELECT a.* FROM applications a
            JOIN students s ON a.student_id = s.student_id
            WHERE a.internship_id = ? AND s.user_id = ?
        ");
        $stmt->execute([$internship['internship_id'], $_SESSION['user_id']]);
        $application = $stmt->fetch();
    } catch (Exception $e) {
        // Surface the DB error temporarily to help debugging
        $error = "Error submitting application. Please try again. (" . $e->getMessage() . ")";
        error_log("Application error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $internship ? htmlspecialchars($internship['title']) : 'Internship'; ?> - InternLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1>InternLink</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="search.php">Search</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo getUserRole(); ?>/dashboard.php">Dashboard</a>
                    <a href="logout.php" class="btn-logout">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
            <div class="mobile-menu-toggle" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <div class="mobile-nav" id="mobileNav">
            <a href="index.php">Home</a>
            <a href="search.php">Search</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo getUserRole(); ?>/dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <strong>Error:</strong> <?php echo $error; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <div class="alert-icon">✅</div>
                    <div class="alert-content">
                        <strong>Success:</strong> <?php echo $success; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$internship && !$error): ?>
                <div class="alert alert-warning">
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <p>Internship not found or no longer available.</p>
                        <a href="search.php" class="btn btn-primary">Browse Other Internships</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($internship): ?>
                <!-- Breadcrumb -->
                <nav class="breadcrumb">
                    <a href="index.php">Home</a>
                    <span>/</span>
                    <a href="search.php">Search</a>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($internship['title']); ?></span>
                </nav>

                <div class="internship-details">
                    <!-- Header Section -->
                    <header class="details-header">
                        <div class="header-content">
                            <div class="title-section">
                                <h1 class="internship-title"><?php echo htmlspecialchars($internship['title']); ?></h1>
                                <div class="company-info">
                                    <h2 class="company-name"><?php echo htmlspecialchars($internship['company_name']); ?></h2>
                                    <?php if ($internship['website']): ?>
                                        <a href="<?php echo htmlspecialchars($internship['website']); ?>" target="_blank" class="website-link">
                                            <span class="icon">🌐</span>
                                            Visit Company Website
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="status-badges">
                                <?php if ($internship['status'] === 'active' || $internship['status'] === 'open'): ?>
                                    <span class="badge badge-success">Open for Applications</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Closed</span>
                                <?php endif; ?>
                                <span class="badge badge-category"><?php echo htmlspecialchars($internship['category_name']); ?></span>
                            </div>
                        </div>
                    </header>

                    <!-- Single Column Content -->
                    <div class="content-container">
                        <!-- Quick Info Card -->
                        <div class="card info-card">
                            <h3 class="card-title">
                                <span class="icon">ℹ️</span>
                                Quick Info
                            </h3>
                            <div class="card-content">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-icon">📍</div>
                                        <div class="info-content">
                                            <span class="info-label">Location</span>
                                            <span class="info-value"><?php echo htmlspecialchars($internship['location']); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-icon">⏱️</div>
                                        <div class="info-content">
                                            <span class="info-label">Duration</span>
                                            <span class="info-value"><?php echo htmlspecialchars($internship['duration']); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-icon">💰</div>
                                        <div class="info-content">
                                            <span class="info-label">Stipend</span>
                                            <span class="info-value"><?php echo htmlspecialchars($internship['stipend']); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-icon">📅</div>
                                        <div class="info-content">
                                            <span class="info-label">Posted</span>
                                            <span class="info-value"><?php 
                                                if (isset($internship['posted_at']) && $internship['posted_at']) {
                                                    echo date('M d, Y', strtotime($internship['posted_at']));
                                                } elseif (isset($internship['created_at']) && $internship['created_at']) {
                                                    echo date('M d, Y', strtotime($internship['created_at']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                            ?></span>
                                        </div>
                                    </div>
                                    <div class="info-item deadline-item">
                                        <div class="info-icon">⏰</div>
                                        <div class="info-content">
                                            <span class="info-label">Deadline</span>
                                            <span class="info-value deadline-date"><?php echo date('M d, Y', strtotime($internship['deadline'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <div class="card description-card">
                            <h3 class="card-title">
                                <span class="icon">📝</span>
                                Job Description
                            </h3>
                            <div class="card-content">
                                <div class="wrapped-text">
                                    <?php echo nl2br(htmlspecialchars($internship['description'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Requirements -->
                        <div class="card requirements-card">
                            <h3 class="card-title">
                                <span class="icon">📋</span>
                                Requirements
                            </h3>
                            <div class="card-content">
                                <div class="wrapped-text">
                                    <?php echo nl2br(htmlspecialchars($internship['requirements'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Required Skills -->
                        <?php if ($internship['required_skills']): ?>
                            <div class="card skills-card">
                                <h3 class="card-title">
                                    <span class="icon">🛠️</span>
                                    Required Skills
                                </h3>
                                <div class="card-content">
                                    <div class="skills-grid">
                                        <?php foreach (explode(',', $internship['required_skills']) as $skill): ?>
                                            <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Application Section -->
                        <?php if ($internship['status'] === 'active' || $internship['status'] === 'open'): ?>
                            <?php if ($can_apply): ?>
                                <?php if ($already_applied): ?>
                                    <div class="card application-status-card">
                                        <h3 class="card-title">
                                            <span class="icon">📋</span>
                                            Application Status
                                        </h3>
                                        <div class="card-content">
                                            <div class="status-badge status-<?php echo $application['status']; ?>">
                                                <?php echo ucfirst($application['status']); ?>
                                            </div>
                                            <p class="applied-date">
                                                <span class="icon">📅</span>
                                                Applied on <?php echo date('M d, Y', strtotime($application['applied_at'])); ?>
                                            </p>
                                            <?php if ($application['cover_letter']): ?>
                                                <div class="cover-letter-preview">
                                                    <h4>Your Cover Letter:</h4>
                                                    <div class="cover-letter-text wrapped-text">
                                                        <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="card apply-card">
                                        <h3 class="card-title">
                                            <span class="icon">🚀</span>
                                            Apply Now
                                        </h3>
                                        <div class="card-content">
                                            <form method="POST" action="" id="applicationForm" class="application-form">
                                                <div class="form-group">
                                                    <label for="cover_letter" class="form-label">Cover Letter *</label>
                                                    <textarea 
                                                        id="cover_letter" 
                                                        name="cover_letter" 
                                                        rows="8" 
                                                        required 
                                                        placeholder="Tell us why you're the perfect fit for this role. Highlight your relevant experience, skills, and what excites you about this opportunity..."
                                                        class="form-textarea wrapped-input"
                                                    ></textarea>
                                                    <div class="char-counter">
                                                        <span id="charCount">0</span> characters (minimum 100)
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-full">
                                                    <span class="btn-icon">📨</span>
                                                    Submit Application
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php elseif (!isLoggedIn()): ?>
                                <div class="card auth-card">
                                    <h3 class="card-title">
                                        <span class="icon">🔐</span>
                                        Apply for this Position
                                    </h3>
                                    <div class="card-content">
                                        <p>Please login or register as a student to apply for this internship.</p>
                                        <div class="auth-buttons">
                                            <a href="login.php" class="btn btn-primary">Login</a>
                                            <a href="register.php" class="btn btn-secondary">Register</a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="card restricted-card">
                                    <h3 class="card-title">
                                        <span class="icon">👨‍🎓</span>
                                        Student Access Only
                                    </h3>
                                    <div class="card-content">
                                        <p>Only students can apply for internships.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="card closed-card">
                                <h3 class="card-title">
                                    <span class="icon">⏰</span>
                                    Application Closed
                                </h3>
                                <div class="card-content">
                                    <p>This internship is no longer accepting applications.</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Share Card -->
                        <div class="card share-card">
                            <h3 class="card-title">
                                <span class="icon">📤</span>
                                Share this Opportunity
                            </h3>
                            <div class="card-content">
                                <div class="share-buttons">
                                    <button onclick="copyToClipboard()" class="share-btn" title="Copy Link">
                                        <span class="icon">🔗</span>
                                        Copy Link
                                    </button>
                                    <button onclick="shareViaEmail()" class="share-btn" title="Share via Email">
                                        <span class="icon">✉️</span>
                                        Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .nav-brand h1 {
            color: #007bff;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .nav-links a:hover {
            color: #007bff;
            background: rgba(0, 123, 255, 0.1);
        }

        .btn-login, .btn-register {
            background: #007bff !important;
            color: white !important;
            padding: 0.6rem 1.2rem !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
        }

        .btn-logout {
            background: #dc3545 !important;
            color: white !important;
            padding: 0.6rem 1.2rem !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
        }

        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: #333;
            margin: 3px 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        .mobile-nav {
            display: none;
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 1rem;
        }

        .mobile-nav a {
            display: block;
            padding: 0.75rem 0;
            text-decoration: none;
            color: #333;
            border-bottom: 1px solid #f8f9fa;
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
        }

        .container {
            max-width: 900px; /* Reduced max-width for single column */
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Breadcrumb */
        .breadcrumb {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Alerts */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .alert-error {
            background: linear-gradient(135deg, #ffe6e6, #ffcccc);
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: linear-gradient(135deg, #e6ffe6, #ccffcc);
            border-left: 4px solid #28a745;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-left: 4px solid #ffc107;
        }

        .alert-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        /* Header */
        .details-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
        }

        .internship-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
            word-wrap: break-word;
            hyphens: auto;
        }

        .company-name {
            font-size: 1.5rem;
            font-weight: 600;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            word-wrap: break-word;
        }

        .website-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            word-break: break-all;
        }

        .website-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .status-badges {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-category {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
        }

        /* Single Column Container */
        .content-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.5rem 1.5rem 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }

        .card-title .icon {
            font-size: 1.5rem;
        }

        .card-content {
            padding: 0 1.5rem 1.5rem;
        }

        /* Text Wrapping */
        .wrapped-text {
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            line-height: 1.7;
            font-size: 1rem;
            color: #555;
            white-space: pre-wrap; /* Preserves line breaks from nl2br */
        }

        /* Skills */
        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .skill-tag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            word-wrap: break-word;
        }

        .skill-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .info-icon {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .info-content {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0; /* Allow text to wrap */
        }

        .info-label {
            font-size: 0.875rem;
            color: #666;
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: #333;
            word-wrap: break-word;
            hyphens: auto;
        }

        .deadline-item {
            border: 2px solid #ffc107;
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        }

        .deadline-date {
            color: #856404;
            font-weight: 700;
        }

        /* Application Status */
        .application-status-card {
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
        }

        .status-accepted {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .status-rejected {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }

        .applied-date {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .cover-letter-preview {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #007bff;
        }

        .cover-letter-preview h4 {
            margin-bottom: 1rem;
            color: #333;
            font-weight: 600;
        }

        .cover-letter-text {
            color: #555;
            line-height: 1.8;
        }

        /* Form Styles */
        .application-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .form-textarea, .wrapped-input {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            line-height: 1.6;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        .form-textarea:focus, .wrapped-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .char-counter {
            text-align: right;
            font-size: 0.875rem;
            color: #666;
        }

        .char-counter.valid {
            color: #28a745;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .btn-full {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
        }

        .btn-icon {
            font-size: 1.2rem;
        }

        /* Auth Buttons */
        .auth-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        /* Share Buttons */
        .share-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .share-btn {
            flex: 1;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            color: #333;
            padding: 0.75rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .share-btn:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .mobile-nav.active {
                display: block;
            }

            .mobile-menu-toggle.active span:nth-child(1) {
                transform: rotate(-45deg) translate(-5px, 6px);
            }

            .mobile-menu-toggle.active span:nth-child(2) {
                opacity: 0;
            }

            .mobile-menu-toggle.active span:nth-child(3) {
                transform: rotate(45deg) translate(-5px, -6px);
            }

            .details-header {
                padding: 2rem 1.5rem;
                text-align: center;
            }

            .header-content {
                flex-direction: column;
                gap: 1.5rem;
            }

            .internship-title {
                font-size: 2rem;
            }

            .company-name {
                font-size: 1.25rem;
            }

            .status-badges {
                align-items: center;
            }

            .main-content {
                padding: 1rem 0;
            }

            .container {
                padding: 0 0.5rem;
            }

            .card-content {
                padding: 0 1rem 1rem;
            }

            .card-title {
                padding: 1rem 1rem 0;
                font-size: 1.1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item {
                padding: 0.75rem;
            }

            .info-icon {
                width: 35px;
                height: 35px;
                font-size: 1.25rem;
            }

            .auth-buttons {
                flex-direction: column;
            }

            .share-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .internship-title {
                font-size: 1.75rem;
            }

            .company-name {
                font-size: 1.1rem;
            }

            .details-header {
                padding: 1.5rem 1rem;
            }

            .skills-grid {
                gap: 0.5rem;
            }

            .skill-tag {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading State */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: #666;
        }

        /* Accessibility Improvements */
        .btn:focus,
        .form-textarea:focus,
        .wrapped-input:focus,
        .share-btn:focus {
            outline: 2px solid #007bff;
            outline-offset: 2px;
        }

        /* Print Styles */
        @media print {
            .navbar,
            .share-card {
                display: none;
            }

            .details-header {
                background: #f8f9fa !important;
                color: #333 !important;
                box-shadow: none !important;
            }
        }

        /* Additional wrapping styles */
        .deadline-warning {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #ffc107;
            border-radius: 12px;
            margin-top: 0.5rem;
            font-weight: 600;
            color: #856404;
            animation: slideIn 0.3s ease-out;
            word-wrap: break-word;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Improved card hover effects */
        .card {
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .card:hover::before {
            left: 100%;
        }

        /* Enhanced focus states */
        .nav-links a:focus,
        .breadcrumb a:focus {
            outline: 2px solid #007bff;
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* Better loading states */
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Enhanced typography */
        .card-content p {
            font-size: 1rem;
            line-height: 1.7;
            color: #555;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        .card-content h4 {
            color: #333;
            font-weight: 600;
            margin-bottom: 0.75rem;
            word-wrap: break-word;
        }

        /* Dark mode support preparation */
        @media (prefers-color-scheme: dark) {
            /* Add dark mode styles if needed */
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .card {
                border: 2px solid #333;
            }
            
            .btn-primary {
                background: #0000ff;
                border: 2px solid #000;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('mobileNav').classList.toggle('active');
        });

        // Character counter for cover letter
        const coverLetterTextarea = document.getElementById('cover_letter');
        const charCountSpan = document.getElementById('charCount');

        if (coverLetterTextarea && charCountSpan) {
            coverLetterTextarea.addEventListener('input', function() {
                const count = this.value.length;
                charCountSpan.textContent = count;
                
                const counter = charCountSpan.parentElement;
                if (count >= 100) {
                    counter.classList.add('valid');
                } else {
                    counter.classList.remove('valid');
                }
            });
        }

        // Form validation
        document.getElementById('applicationForm')?.addEventListener('submit', function(e) {
            const coverLetter = document.getElementById('cover_letter').value.trim();
            
            if (coverLetter.length < 100) {
                e.preventDefault();
                alert('Please write a cover letter of at least 100 characters');
                document.getElementById('cover_letter').focus();
                return;
            }
            
            if (!confirm('Are you sure you want to submit this application? You cannot edit it after submission.')) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Submitting...';
                submitBtn.disabled = true;
                
                // Re-enable if form validation fails
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }, 5000);
            }
        });

        // Share functionality
        function copyToClipboard() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function() {
                // Show success feedback
                const btn = event.target.closest('.share-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="icon">✅</span> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            }).catch(function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                const btn = event.target.closest('.share-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="icon">✅</span> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }

        function shareViaEmail() {
            const subject = encodeURIComponent('Check out this internship opportunity');
            const body = encodeURIComponent(`I found this internship that might interest you:\n\n${document.querySelector('.internship-title').textContent} at ${document.querySelector('.company-name').textContent}\n\n${window.location.href}`);
            window.open(`mailto:?subject=${subject}&body=${body}`);
        }

        // Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add fade-in animation to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('fade-in');
                }, index * 100);
            });
        });

        // Auto-resize textarea
        if (coverLetterTextarea) {
            coverLetterTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.max(120, this.scrollHeight) + 'px';
            });
        }

        // Deadline warning
        function checkDeadline() {
            const deadlineElement = document.querySelector('.deadline-date');
            if (deadlineElement) {
                const deadlineText = deadlineElement.textContent;
                const deadlineDate = new Date(deadlineText);
                const today = new Date();
                const diffTime = deadlineDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays <= 7 && diffDays > 0) {
                    const deadlineItem = deadlineElement.closest('.info-item');
                    deadlineItem.style.animation = 'pulse 2s infinite';
                    deadlineItem.insertAdjacentHTML('afterend', 
                        `<div class="deadline-warning">
                            <span class="icon">⚠️</span>
                            Only ${diffDays} day${diffDays === 1 ? '' : 's'} left to apply!
                        </div>`
                    );
                } else if (diffDays <= 0) {
                    deadlineElement.style.color = '#dc3545';
                    deadlineElement.parentElement.innerHTML += '<br><small style="color: #dc3545; font-weight: bold;">DEADLINE PASSED</small>';
                }
            }
        }

        checkDeadline();

        // Enhanced error handling
        window.addEventListener('error', function(e) {
            console.error('JavaScript error:', e.error);
        });

        // Form auto-save to prevent data loss (using memory only)
        let autoSaveData = {};
        
        if (coverLetterTextarea) {
            // Load saved data
            const saved = autoSaveData.coverLetter;
            if (saved) {
                coverLetterTextarea.value = saved;
                coverLetterTextarea.dispatchEvent(new Event('input'));
            }

            // Auto-save every 5 seconds
            setInterval(() => {
                autoSaveData.coverLetter = coverLetterTextarea.value;
            }, 5000);

            // Save on input
            coverLetterTextarea.addEventListener('input', function() {
                autoSaveData.coverLetter = this.value;
            });
        }
    </script>
</body>
</html>
