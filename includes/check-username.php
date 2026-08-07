<?php

require_once 'includes/db.php';

header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');

if (empty($username)) {
    echo json_encode(['available' => false, 'message' => 'Username is required']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
    echo json_encode(['available' => false, 'message' => 'Invalid format']);
    exit;
}

$stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
$stmt->execute([$username]);

if ($stmt->fetch()) {
    echo json_encode(['available' => false, 'message' => 'Username taken']);
} else {
    echo json_encode(['available' => true, 'message' => 'Username available']);
}