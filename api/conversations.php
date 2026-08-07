<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT
            c.conversation_id,
            c.product_id,
            c.buyer_id,
            c.seller_id,
            c.updated_at,
            p.title     AS product_title,
            p.slug      AS product_slug,
            p.price     AS product_price,
            pi.image_path AS product_image,
            CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END AS other_user_id,
            ou.username       AS other_username,
            ou.full_name      AS other_full_name,
            ou.profile_image  AS other_profile_image,
            (SELECT message_text FROM messages
             WHERE conversation_id = c.conversation_id
             ORDER BY created_at DESC LIMIT 1) AS last_message,
            (SELECT created_at FROM messages
             WHERE conversation_id = c.conversation_id
             ORDER BY created_at DESC LIMIT 1) AS last_message_time,
            (SELECT COUNT(*) FROM messages
             WHERE conversation_id = c.conversation_id
               AND sender_id != ? AND is_read = 0) AS unread_count
        FROM conversations c
        JOIN products p ON c.product_id = p.product_id
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        JOIN users ou ON ou.user_id = CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END
        WHERE c.buyer_id = ? OR c.seller_id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
    $conversations = $stmt->fetchAll();

    foreach ($conversations as &$conv) {
        $conv['time_ago']            = timeAgo($conv['updated_at']);
        $conv['last_message_preview'] = $conv['last_message']
            ? substr($conv['last_message'], 0, 50) . (strlen($conv['last_message']) > 50 ? '...' : '')
            : 'No messages yet';
    }

    echo json_encode(['success' => true, 'conversations' => $conversations]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product']);
        exit;
    }

    $prodStmt = $pdo->prepare("SELECT seller_id FROM products WHERE product_id = ? AND status = 'active'");
    $prodStmt->execute([$productId]);
    $product = $prodStmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Product not found or no longer active']);
        exit;
    }

    $sellerId = (int)$product['seller_id'];
    $buyerId  = $currentUserId;

    if ($sellerId === $buyerId) {
        echo json_encode(['success' => false, 'error' => 'You cannot message yourself']);
        exit;
    }

    $check = $pdo->prepare("
        SELECT conversation_id FROM conversations
        WHERE product_id = ? AND buyer_id = ? AND seller_id = ?
    ");
    $check->execute([$productId, $buyerId, $sellerId]);
    $existing = $check->fetch();

    if ($existing) {
        echo json_encode(['success' => true, 'conversation_id' => $existing['conversation_id'], 'exists' => true]);
        exit;
    }

    $pdo->prepare("INSERT INTO conversations (product_id, buyer_id, seller_id) VALUES (?, ?, ?)")
        ->execute([$productId, $buyerId, $sellerId]);

    echo json_encode(['success' => true, 'conversation_id' => (int)$pdo->lastInsertId(), 'exists' => false]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request method']);