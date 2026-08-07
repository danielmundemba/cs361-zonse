<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $conversationId = (int)($_GET['conversation_id'] ?? 0);
    $lastId = (int)($_GET['last_id'] ?? 0);

    if ($conversationId <= 0) {
        echo json_encode(['error' => 'Invalid conversation']);
        exit;
    }

    $verify = $pdo->prepare("
        SELECT 1 FROM conversations 
        WHERE conversation_id = ? AND (buyer_id = ? OR seller_id = ?)
    ");
    $verify->execute([$conversationId, $currentUserId, $currentUserId]);
    if (!$verify->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    $pdo->prepare("
        UPDATE messages SET is_read = 1 
        WHERE conversation_id = ? AND sender_id != ? AND is_read = 0
    ")->execute([$conversationId, $currentUserId]);

    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.full_name, u.profile_image
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.conversation_id = ? AND m.message_id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversationId, $lastId]);
    $messages = $stmt->fetchAll();

    foreach ($messages as &$msg) {
        $msg['is_me'] = ((int)$msg['sender_id'] === $currentUserId);
        $msg['time'] = date('g:i A', strtotime($msg['created_at']));
        $msg['date'] = date('M j, Y', strtotime($msg['created_at']));
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $messageText = trim($_POST['message'] ?? '');

    if ($conversationId <= 0 || empty($messageText)) {
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    $verify = $pdo->prepare("
        SELECT 1 FROM conversations 
        WHERE conversation_id = ? AND (buyer_id = ? OR seller_id = ?)
    ");
    $verify->execute([$conversationId, $currentUserId, $currentUserId]);
    if (!$verify->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, message_text) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$conversationId, $currentUserId, $messageText]);
    $messageId = $pdo->lastInsertId();

    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE conversation_id = ?")
        ->execute([$conversationId]);

    echo json_encode(['success' => true, 'message_id' => $messageId]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);