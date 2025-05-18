<?php
require_once __DIR__.'/../../includes/db.php';

header('Content-Type: application/json');
$db = DB::getInstance();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token'], $data['lat'], $data['lon'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Verify token exists
$stmt = $db->prepare("SELECT id FROM tracking_links WHERE token = ? AND is_active = 1");
$stmt->execute([$data['token']]);

if ($stmt->rowCount() === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Store location
$stmt = $db->prepare("INSERT INTO locations (token, latitude, longitude, timestamp) 
                      VALUES (?, ?, ?, datetime('now'))");
$stmt->execute([$data['token'], $data['lat'], $data['lon']]);

echo json_encode(['status' => 'success']);
?>