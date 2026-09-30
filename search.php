<?php
require_once 'includes/db.php';

// Get all categories for filter
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

// Get all skills for filter
try {
    $stmt = $pdo->query("SELECT * FROM skills ORDER BY skill_name");
    $skills = $stmt->fetchAll();
} catch (Exception $e) {
    $skills = [];
}

// Build search query with proper error handling
$where = ["i.status = 'active'"]; // Only show active internships
$params = [];

// Basic search parameters
if (!empty($_GET['q'])) {
    $where[] = "(i.title LIKE ? OR i.description LIKE ? OR i.requirements LIKE ? OR c.company_name LIKE ?)";
    $searchTerm = "%" . $_GET['q'] . "%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($_GET['category'])) {
    $where[] = "i.category_id = ?";
    $params[] = $_GET['category'];
}

if (!empty($_GET['location'])) {
    $where[] = "i.location LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
}

// Skills filter: show internships matching ANY of the selected skills
$skillsJoin = "";
if (!empty($_GET['skills'])) {
    $skillIds = $_GET['skills'];
    $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
    $where[] = "i.internship_id IN (
        SELECT DISTINCT is_skills.internship_id 
        FROM internship_skills is_skills 
        WHERE is_skills.skill_id IN ($placeholders)
    )";
    $params = array_merge($params, $skillIds);
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Execute search query
$query = "
    SELECT DISTINCT i.*, c.company_name, 
           COALESCE(cat.category_name, 'Uncategorized') as category_name
    FROM internships i
    JOIN companies c ON i.company_id = c.company_id
    LEFT JOIN categories cat ON i.category_id = cat.category_id
    $whereClause
    ORDER BY i.created_at DESC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $internships = $stmt->fetchAll();
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database error in search: " . $e->getMessage());
    $internships = [];
    $search_error = "There was an error searching for internships. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Internships - InternLink</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>InternLink</h1>
            <div>
                <a href="index.php">Home</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo getUserRole(); ?>/dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Search Internships</h2>

        <?php if (isset($search_error)): ?>
            <div class="alert alert-danger"><?php echo $search_error; ?></div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="card search-filters">
            <form method="GET" action="" id="searchForm">
                <div class="search-row">
                    <div class="form-group">
                        <input type="text" name="q" class="search-input" 
                               placeholder="Search by role, company, or requirements..."
                               value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                    <?php echo (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <input type="text" name="location" 
                               placeholder="Location"
                               value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
                    </div>
                </div>

                <div class="skills-filter">
                    <label>Skills:</label>
                    <div class="skills-grid">
                        <?php foreach ($skills as $skill): ?>
                            <label class="skill-checkbox">
                                <input type="checkbox" name="skills[]" 
                                       value="<?php echo $skill['skill_id']; ?>"
                                       <?php echo (isset($_GET['skills']) && in_array($skill['skill_id'], $_GET['skills'])) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Search</button>
                <a href="search.php" class="btn">Clear Filters</a>
            </form>
        </div>

        <!-- Results -->
        <div class="search-results">
            <h3><?php echo count($internships); ?> Internships Found</h3>
            
            <?php if (!empty($internships)): ?>
                <div class="grid">
                    <?php foreach ($internships as $internship): ?>
                        <div class="card internship-card">
                            <h3><?php echo htmlspecialchars($internship['title']); ?></h3>
                            <p class="company"><?php echo htmlspecialchars($internship['company_name']); ?></p>
                            <p class="category"><?php echo htmlspecialchars($internship['category_name']); ?></p>
                            <p class="location">📍 <?php echo htmlspecialchars($internship['location'] ?? ''); ?></p>
                            
                            <?php 
                            // Get skills for this internship
                            try {
                                $skillStmt = $pdo->prepare("
                                    SELECT s.skill_name 
                                    FROM internship_skills is_join 
                                    JOIN skills s ON is_join.skill_id = s.skill_id 
                                    WHERE is_join.internship_id = ?
                                ");
                                $skillStmt->execute([$internship['internship_id']]);
                                $internshipSkills = $skillStmt->fetchAll();
                                
                                if ($internshipSkills): ?>
                                    <div class="skills">
                                        <?php foreach ($internshipSkills as $skill): ?>
                                            <span class="badge"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif;
                            } catch (Exception $e) {
                                // Skills table might not exist, continue without skills
                            }
                            ?>

                            <p class="stipend">💰 Stipend: <?php echo htmlspecialchars($internship['stipend'] ?? 'Not specified'); ?></p>
                            <p class="duration">⏱️ Duration: <?php echo htmlspecialchars($internship['duration'] ?? 'Not specified'); ?></p>
                            <?php if ($internship['deadline']): ?>
                                <p class="deadline">📅 Deadline: <?php echo date('M d, Y', strtotime($internship['deadline'])); ?></p>
                            <?php endif; ?>
                            <p class="posted">Posted: <?php echo date('M d, Y', strtotime($internship['created_at'])); ?></p>
                            
                            <div class="card-actions">
                                <a href="internship.php?id=<?php echo $internship['internship_id']; ?>" 
                                   class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <p>No internships found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .search-filters {
            margin-bottom: 2rem;
        }
        .search-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .skills-filter {
            margin-top: 1rem;
        }
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .skill-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .internship-card {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .internship-card h3 {
            margin: 0;
        }
        .company {
            color: #666;
            font-weight: bold;
        }
        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            margin: 0.5rem 0;
        }
        .badge {
            background-color: #e9ecef;
            color: #333;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
        }
        .card-actions {
            margin-top: auto;
            padding-top: 1rem;
        }
    </style>
</body>
</html>