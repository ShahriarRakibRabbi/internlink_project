<?php
require_once 'includes/db.php';

// Get all categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get all skills for filter
$stmt = $pdo->query("SELECT * FROM skills ORDER BY name");
$skills = $stmt->fetchAll();

// Build search query
$where = ["i.status = 'open'"]; // Only show open internships
$params = [];

if (!empty($_GET['q'])) {
    $where[] = "(i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ? OR c.company_name LIKE ?)";
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

if (!empty($_GET['skills'])) {
    $skillIds = $_GET['skills'];
    $placeholders = str_repeat('?,', count($skillIds) - 1) . '?';
    $where[] = "EXISTS (
        SELECT 1 FROM internship_skills is2 
        WHERE is2.internship_id = i.internship_id 
        AND is2.skill_id IN ($placeholders)
    )";
    $params = array_merge($params, $skillIds);
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Execute search query
$query = "
    SELECT DISTINCT i.*, c.company_name, cat.name as category_name,
           GROUP_CONCAT(s.name) as skills
    FROM internships i
    JOIN companies c ON i.company_id = c.company_id
    JOIN categories cat ON i.category_id = cat.category_id
    LEFT JOIN internship_skills is1 ON i.internship_id = is1.internship_id
    LEFT JOIN skills s ON is1.skill_id = s.skill_id
    $whereClause
    GROUP BY i.internship_id
    ORDER BY i.posted_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$internships = $stmt->fetchAll();
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

        <!-- Search Form -->
        <div class="card search-filters">
            <form method="GET" action="" id="searchForm">
                <div class="search-row">
                    <div class="form-group">
                        <input type="text" name="q" class="search-input" 
                               placeholder="Search by title, company, or location..."
                               value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                    <?php echo (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
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
                                <?php echo htmlspecialchars($skill['name']); ?>
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
                            <p class="location">📍 <?php echo htmlspecialchars($internship['location']); ?></p>
                            
                            <?php if ($internship['skills']): ?>
                                <div class="skills">
                                    <?php foreach (explode(',', $internship['skills']) as $skill): ?>
                                        <span class="badge"><?php echo htmlspecialchars($skill); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <p class="stipend">💰 Stipend: <?php echo htmlspecialchars($internship['stipend']); ?></p>
                            <p class="duration">⏱️ Duration: <?php echo htmlspecialchars($internship['duration']); ?></p>
                            <p class="deadline">Deadline: <?php echo date('M d, Y', strtotime($internship['deadline'])); ?></p>
                            
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
