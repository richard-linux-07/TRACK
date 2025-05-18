<?php
require_once __DIR__.'/../../includes/db.php';

header('Content-Type: application/json');
$db = DB::getInstance();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['token'], $data['calls'])) {
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

// Process each call
foreach ($data['calls'] as $call) {
    // Parse call data (simplified)
    $parts = explode('|', $call);
    $number = trim($parts[0]);
    $type = (int)trim($parts[1]);
    $duration = (int)trim($parts[2]);
    $date = date('Y-m-d H:i:s', (int)trim($parts[3])/1000);
    
    $stmt = $db->prepare("INSERT INTO call_logs 
                         (token, number, type, duration, call_date)
                         VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['token'], $number, $type, $duration, $date]);
}

echo json_encode(['status' => 'success', 'processed' => count($data['calls'])]);
?>