<?php
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';

checkAuth();

$db = DB::getInstance();
$parentId = $_SESSION['parent_id'];

// Get children list
$stmt = $db->prepare("SELECT * FROM children WHERE parent_id = ?");
$stmt->execute([$parentId]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parental Control Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Parental Control Dashboard</h1>
    
    <div class="children-list">
        <h2>Your Children</h2>
        <?php foreach ($children as $child): ?>
        <div class="child-card">
            <h3><?= htmlspecialchars($child['name']) ?></h3>
            <a href="/generate.php?child_id=<?= $child['id'] ?>" class="btn">Generate Tracking Link</a>
            <a href="/view-logs.php?child_id=<?= $child['id'] ?>" class="btn">View Activity</a>
        </div>
        <?php endforeach; ?>
    </div>
    
    <script src="script.js"></script>
</body>
</html>