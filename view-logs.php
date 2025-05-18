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
$stmt = $db->prepare("SELECT c.name FROM children c 
                      WHERE c.id = ? AND c.parent_id = ?");
$stmt->execute([$childId, $parentId]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    die("Invalid child ID");
}

// Get tracking token
$stmt = $db->prepare("SELECT token FROM tracking_links 
                      WHERE child_id = ? AND is_active = 1
                      ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$childId]);
$token = $stmt->fetchColumn();

// Get all activity data
$locations = $db->query("SELECT * FROM locations WHERE token = '$token' ORDER BY timestamp DESC")->fetchAll();
$calls = $db->query("SELECT * FROM call_logs WHERE token = '$token' ORDER BY call_date DESC")->fetchAll();
$activities = $db->query("SELECT * FROM activity_logs WHERE token = '$token' ORDER BY timestamp DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs - <?= htmlspecialchars($child['name']) ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Activity Logs for <?= htmlspecialchars($child['name']) ?></h1>
    
    <div class="tabs">
        <button class="tab-btn active" data-tab="locations">Locations</button>
        <button class="tab-btn" data-tab="calls">Call Logs</button>
        <button class="tab-btn" data-tab="activities">App/Browser Activity</button>
    </div>
    
    <div id="locations" class="tab-content active">
        <div id="map" style="height: 400px;"></div>
        <ul class="location-list">
            <?php foreach ($locations as $loc): ?>
            <li>
                <?= date('M j, Y g:i a', strtotime($loc['timestamp'])) ?>:
                <?= round($loc['latitude'], 4) ?>, <?= round($loc['longitude'], 4) ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div id="calls" class="tab-content">
        <table>
            <tr>
                <th>Number</th>
                <th>Type</th>
                <th>Duration</th>
                <th>Date/Time</th>
            </tr>
            <?php foreach ($calls as $call): ?>
            <tr>
                <td><?= htmlspecialchars($call['number']) ?></td>
                <td><?= match($call['type']) {1=>'Incoming',2=>'Outgoing',3=>'Missed'} ?></td>
                <td><?= gmdate("i:s", $call['duration']) ?></td>
                <td><?= date('M j, Y g:i a', strtotime($call['call_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div id="activities" class="tab-content">
        <ul>
            <?php foreach ($activities as $activity): ?>
            <li>
                <strong><?= date('M j, Y g:i a', strtotime($activity['timestamp'])) ?></strong>:
                <?= htmlspecialchars($activity['activity_type']) ?> - 
                <?= htmlspecialchars($activity['details']) ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="script.js"></script>
    <script>
    // Initialize map with all locations
    const map = L.map('map').setView([<?= $locations[0]['latitude'] ?>, <?= $locations[0]['longitude'] ?>], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    <?php foreach ($locations as $loc): ?>
    L.marker([<?= $loc['latitude'] ?>, <?= $loc['longitude'] ?>])
        .addTo(map)
        .bindPopup("<?= date('M j, Y g:i a', strtotime($loc['timestamp'])) ?>");
    <?php endforeach; ?>
    
    // Tab functionality
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).classList.add('active');
        });
    });
    </script>
</body>
</html>