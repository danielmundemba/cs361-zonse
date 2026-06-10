<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM messages m
    JOIN conversations c ON m.conversation_id = c.conversation_id
    WHERE m.sender_id != ? AND m.is_read = 0
    AND (c.buyer_id = ? OR c.seller_id = ?)
");
$stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
$count = (int)$stmt->fetchColumn();

echo json_encode(['count' => $count]);