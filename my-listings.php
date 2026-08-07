<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'], $_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];

    $verify = $pdo->prepare("SELECT status FROM products WHERE product_id = ? AND seller_id = ?");
    $verify->execute([$productId, $userId]);
    $product = $verify->fetch();

    if ($product) {
        $newStatus = $product['status'] === 'active' ? 'inactive' : 'active';
        $update = $pdo->prepare("UPDATE products SET status = ?, updated_at = NOW() WHERE product_id = ?");
        $update->execute([$newStatus, $productId]);
    }

    header('Location: my-listings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'], $_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];

    $verify = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ? AND seller_id = ?");
    $verify->execute([$productId, $userId]);

    if ($verify->fetch()) {
        $imgStmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $imgStmt->execute([$productId]);
        $images = $imgStmt->fetchAll();

        foreach ($images as $img) {
            $path = __DIR__ . '/assets/images/uploads/' . $img['image_path'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$productId]);
    }

    header('Location: my-listings.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, pi.image_path
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    WHERE p.seller_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$userId]);
$listings = $stmt->fetchAll();

$activeCount = count(array_filter($listings, fn($l) => $l['status'] === 'active'));
$totalCount  = count($listings);

$pageTitle = 'My Listings';
require_once __DIR__ . '/includes/header.php';
?>

<section class="listings-section" style="padding-top: 40px;">
    <div class="container">

        <div class="section-header">
            <h2><i class="fas fa-box"></i> My Listings</h2>
            <a href="add-product.php" class="btn-sell">
                <i class="fas fa-plus"></i> Sell New Item
            </a>
        </div>

        <?php $successMsg = flash('success'); if ($successMsg): ?>
            <div class="alert alert-success" style="margin-bottom: 24px;">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>

        <div class="stats-bar" style="display: flex; gap: 24px; margin-bottom: 32px; flex-wrap: wrap;">
            <div class="stat-card">
                <span class="stat-value"><?= $totalCount ?></span>
                <span class="stat-label">Total Listings</span>
            </div>
            <div class="stat-card">
                <span class="stat-value" style="color: var(--accent);"><?= $activeCount ?></span>
                <span class="stat-label">Active</span>
            </div>
            <div class="stat-card">
                <span class="stat-value" style="color: var(--text-muted);"><?= $totalCount - $activeCount ?></span>
                <span class="stat-label">Inactive</span>
            </div>
        </div>

        <?php if (empty($listings)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>You haven't listed any items yet.</p>
                <a href="add-product.php" class="btn-primary btn-lg" style="margin-top: 16px; display: inline-flex;">
                    <i class="fas fa-plus"></i> List Your First Item
                </a>
            </div>
        <?php else: ?>
            <div class="listings-table-wrapper">
                <table class="listings-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Listed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $item): ?>
                            <tr>
                                <td data-label="Item">
                                    <div class="item-cell">
                                        <img src="assets/images/uploads/<?= htmlspecialchars($item['image_path'] ?? 'default-product.jpg') ?>"
                                             alt="<?= htmlspecialchars($item['title']) ?>"
                                             class="item-thumb">
                                        <div class="item-info">
                                            <a href="product.php?slug=<?= urlencode($item['slug']) ?>" class="item-title">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </a>
                                            <span class="item-location">
                                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['location']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Price">
                                    <span class="item-price">ZMW <?= number_format($item['price'], 2) ?></span>
                                </td>
                                <td data-label="Category">
                                    <span class="item-category"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></span>
                                </td>
                                <td data-label="Condition">
                                    <span class="condition-badge condition-<?= $item['condition_status'] ?>">
                                        <?= ucfirst($item['condition_status']) ?>
                                    </span>
                                </td>
                                <td data-label="Status">
                                    <span class="status-badge status-<?= $item['status'] ?>">
                                        <i class="fas fa-circle" style="font-size: 6px; vertical-align: middle; margin-right: 4px;"></i>
                                        <?= ucfirst($item['status']) ?>
                                    </span>
                                </td>
                                <td data-label="Listed">
                                    <span class="item-time"><?= timeAgo($item['created_at']) ?></span>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <form method="POST" action="my-listings.php" style="display: inline;">
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <button type="submit" name="toggle_status" class="action-btn toggle-btn"
                                                    title="<?= $item['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                                <i class="fas fa-<?= $item['status'] === 'active' ? 'eye-slash' : 'eye' ?>"></i>
                                            </button>
                                        </form>

                                        <a href="edit-product.php?id=<?= $item['product_id'] ?>" class="action-btn edit-btn" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>

                                        <form method="POST" action="my-listings.php" style="display: inline;"
                                              onsubmit="return confirm('Are you sure you want to delete this listing? This cannot be undone.');">
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <button type="submit" name="delete" class="action-btn delete-btn" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</section>

<style>
.stats-bar {
    margin-bottom: 32px;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 20px 28px;
    min-width: 140px;
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 500;
}

.listings-table-wrapper {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.listings-table {
    width: 100%;
    border-collapse: collapse;
}

.listings-table thead {
    background: var(--bg-tertiary);
}

.listings-table th {
    padding: 16px 20px;
    text-align: left;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border-color);
}

.listings-table td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.listings-table tbody tr:hover {
    background: var(--bg-hover);
}

.listings-table tbody tr:last-child td {
    border-bottom: none;
}

.item-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.item-thumb {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.item-title {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
    transition: color var(--transition-fast);
}

.item-title:hover {
    color: var(--accent);
}

.item-location {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.item-location i {
    font-size: 0.7rem;
    margin-right: 2px;
}

.item-price {
    font-weight: 700;
    color: var(--accent);
    font-size: 1rem;
}

.item-category {
    font-size: 0.85rem;
    color: var(--text-secondary);
    background: var(--bg-tertiary);
    padding: 4px 12px;
    border-radius: var(--radius-sm);
    display: inline-block;
}

.condition-badge {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 4px 10px;
    border-radius: var(--radius-sm);
}

.condition-new {
    background: rgba(0, 212, 170, 0.15);
    color: var(--accent);
}

.condition-used {
    background: rgba(254, 202, 87, 0.15);
    color: #feca57;
}

.condition-refurbished {
    background: rgba(72, 219, 251, 0.15);
    color: #48dbfb;
}

.status-badge {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: var(--radius-xl);
    display: inline-flex;
    align-items: center;
}

.status-active {
    background: rgba(0, 212, 170, 0.15);
    color: var(--accent);
}

.status-inactive {
    background: rgba(107, 107, 123, 0.15);
    color: var(--text-muted);
}

.item-time {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    transition: all var(--transition-fast);
    cursor: pointer;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.toggle-btn:hover {
    background: rgba(0, 212, 170, 0.15);
    color: var(--accent);
    border-color: var(--accent);
}

.edit-btn:hover {
    background: rgba(72, 219, 251, 0.15);
    color: #48dbfb;
    border-color: #48dbfb;
}

.delete-btn:hover {
    background: rgba(255, 107, 107, 0.15);
    color: #ff6b6b;
    border-color: #ff6b6b;
}

@media (max-width: 768px) {
    .listings-table thead {
        display: none;
    }

    .listings-table tbody tr {
        display: block;
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .listings-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border: none;
    }

    .listings-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-muted);
        font-size: 0.8rem;
        text-transform: uppercase;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>