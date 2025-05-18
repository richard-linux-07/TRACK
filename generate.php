<?php
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';

checkAuth();

if (!isset($_GET['child_id'])) {
    header("Location: /index.php");
    exit;
}

$db = DB::getInstance();
$childId = $_GET['child_id'];
$parentId = $_SESSION['parent_id'];

// Verify child belongs to parent
$stmt = $db->prepare("SELECT id FROM children WHERE id = ? AND parent_id = ?");
$stmt->execute([$childId, $parentId]);
if ($stmt->rowCount() === 0) {
    die("Invalid child ID");
}

// Generate new token
$token = bin2hex(random_bytes(32));
$stmt = $db->prepare("INSERT INTO tracking_links (child_id, token) VALUES (?, ?)");
$stmt->execute([$childId, $token]);

$trackingUrl = "https://" . $_SERVER['HTTP_HOST'] . "/track/" . $token;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tracking Link Generated</title>
</head>
<body>
    <h1>Tracking Link Generated</h1>
    <p>Share this link with your child's device:</p>
    <div class="tracking-link">
        <input type="text" value="<?= htmlspecialchars($trackingUrl) ?>" readonly>
        <button onclick="copyToClipboard()">Copy Link</button>
    </div>
    
    <script>
    function copyToClipboard() {
        const copyText = document.querySelector(".tracking-link input");
        copyText.select();
        document.execCommand("copy");
        alert("Link copied to clipboard!");
    }
    </script>
</body>
</html>