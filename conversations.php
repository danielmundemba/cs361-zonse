<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$currentUserId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        c.conversation_id,
        c.updated_at,
        p.title    AS product_title,
        p.slug     AS product_slug,
        p.price    AS product_price,
        pi.image_path AS product_image,
        CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END AS other_user_id,
        ou.username       AS other_username,
        ou.full_name      AS other_full_name,
        ou.profile_image  AS other_profile_image,
        (SELECT message_text FROM messages
         WHERE conversation_id = c.conversation_id
         ORDER BY created_at DESC LIMIT 1) AS last_message,
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

require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Messages';
require_once __DIR__ . '/includes/header.php';
?>

<section class="conv-section">
    <div class="container" style="max-width: 760px;">

        <div class="section-header" style="margin-top: 32px;">
            <h2><i class="fas fa-comment-dots"></i> Messages</h2>
            <span class="conv-total"><?= count($conversations) ?> conversation<?= count($conversations) !== 1 ? 's' : '' ?></span>
        </div>

        <?php if (empty($conversations)): ?>
            <div class="empty-state" style="padding: 80px 24px;">
                <i class="fas fa-inbox"></i>
                <p>No conversations yet</p>
                <a href="index.php" class="btn-primary" style="display:inline-flex; margin-top: 20px; gap: 8px;">
                    <i class="fas fa-store"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="conv-list">
                <?php foreach ($conversations as $conv):
                    $preview = $conv['last_message']
                        ? (strlen($conv['last_message']) > 55 ? substr($conv['last_message'], 0, 55) . '…' : $conv['last_message'])
                        : 'No messages yet';
                    $hasUnread = (int)$conv['unread_count'] > 0;
                ?>
                    <a href="messages.php?chat=<?= $conv['conversation_id'] ?>" class="conv-item <?= $hasUnread ? 'conv-unread' : '' ?>">
                        <div class="conv-avatar-wrap">
                            <img src="assets/images/profiles/<?= htmlspecialchars($conv['other_profile_image'] ?? 'default.jpg') ?>"
                                 alt="<?= htmlspecialchars($conv['other_full_name']) ?>">
                            <?php if ($hasUnread): ?>
                                <span class="unread-dot"><?= (int)$conv['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="conv-body">
                            <div class="conv-row">
                                <span class="conv-name"><?= htmlspecialchars($conv['other_full_name']) ?></span>
                                <span class="conv-time"><?= timeAgo($conv['updated_at']) ?></span>
                            </div>
                            <div class="conv-row">
                                <span class="conv-preview <?= $hasUnread ? 'conv-preview-bold' : '' ?>"><?= htmlspecialchars($preview) ?></span>
                            </div>
                            <div class="conv-product-tag">
                                <img src="assets/images/uploads/<?= htmlspecialchars($conv['product_image'] ?? 'default-product.jpg') ?>"
                                     alt="">
                                <span><?= htmlspecialchars($conv['product_title']) ?></span>
                                <span class="conv-product-price">ZMW <?= number_format($conv['product_price'], 2) ?></span>
                            </div>
                        </div>

                        <i class="fas fa-chevron-right conv-arrow"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<style>
.conv-section { padding-bottom: 60px; }

.conv-total {
    font-size: 0.82rem;
    color: var(--text-muted);
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    padding: 4px 12px;
    border-radius: var(--radius-xl);
}

.conv-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.conv-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 20px;
    transition: background var(--transition-fast);
    border-bottom: 1px solid var(--border-color);
    position: relative;
}
.conv-item:last-child { border-bottom: none; }
.conv-item:hover { background: var(--bg-hover); }
.conv-unread { background: var(--accent-glow); }
.conv-unread:hover { background: rgba(0, 212, 170, 0.12); }

.conv-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}
.conv-avatar-wrap img {
    width: 52px; height: 52px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-color);
    display: block;
}
.unread-dot {
    position: absolute;
    bottom: -2px; right: -2px;
    min-width: 20px; height: 20px;
    background: var(--accent);
    color: var(--text-inverse);
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid var(--bg-card);
    padding: 0 4px;
}

.conv-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.conv-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.conv-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--text-primary);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.conv-time {
    font-size: 0.72rem;
    color: var(--text-muted);
    flex-shrink: 0;
}
.conv-preview {
    font-size: 0.83rem;
    color: var(--text-muted);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.conv-preview-bold {
    font-weight: 600;
    color: var(--text-secondary);
}

.conv-product-tag {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
}
.conv-product-tag img {
    width: 18px; height: 18px;
    border-radius: 3px;
    object-fit: cover;
    border: 1px solid var(--border-color);
}
.conv-product-tag span {
    font-size: 0.72rem;
    color: var(--accent);
    font-weight: 500;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 160px;
}
.conv-product-price {
    color: var(--text-muted) !important;
    font-weight: 400 !important;
    flex-shrink: 0;
}

.conv-arrow {
    font-size: 0.7rem;
    color: var(--text-muted);
    flex-shrink: 0;
    transition: transform var(--transition-fast);
}
.conv-item:hover .conv-arrow { transform: translateX(3px); }

@media (max-width: 480px) {
    .conv-item { padding: 14px 16px; }
    .conv-avatar-wrap img { width: 44px; height: 44px; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>