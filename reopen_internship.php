<?php
require_once '../includes/db.php';
requireLogin();

if (getUserRole() !== 'company') {
    header("Location: ../index.php");
    exit();
}

$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$internship_id) {
    $_SESSION['error'] = "Invalid internship ID.";
    header("Location: dashboard.php");
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

try {
    // Reopen the internship
    $stmt = $pdo->prepare("
        UPDATE internships 
        SET status = 'active', updated_at = NOW()
        WHERE internship_id = ? AND company_id = ?
    ");
    $stmt->execute([$internship_id, $company['company_id']]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Internship reopened successfully. Students can now apply again.";
    } else {
        $_SESSION['error'] = "Internship not found or access denied.";
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error reopening internship: " . $e->getMessage();
}

header("Location: dashboard.php");
exit();
?>
