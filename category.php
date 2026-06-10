<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// ─── Accept ?slug=... or legacy ?id=... ───
$slug  = trim($_GET['slug'] ?? '');
$catId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($slug) {
    $searchName = str_replace('-', ' ', $slug);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt->execute([$searchName]);
    $category = $stmt->fetch();
} elseif ($catId) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ? LIMIT 1");
    $stmt->execute([$catId]);
    $category = $stmt->fetch();
} else {
    $category = false;
}

// ─── Fetch listings if category found ───
$products = [];
if ($category) {
    $stmt = $pdo->prepare("
        SELECT p.*, pi.image_path, u.username, u.full_name AS seller_name
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
        JOIN users u ON p.seller_id = u.user_id
        WHERE p.category_id = ? AND p.status = 'active'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$category['category_id']]);
    $products = $stmt->fetchAll();
}

// ─── All logic done, safe to output HTML ───
$pageTitle = $category ? htmlspecialchars($category['name']) : 'Not Found';
require_once __DIR__ . '/includes/header.php';

if (!$category):
    http_response_code(404);
?>
    <section class="listings-section">
        <div class="container">
            <div class="empty-state">
                <i class="fas fa-folder-open" style="font-size:4rem; margin-bottom:16px; opacity:.5;"></i>
                <p style="font-size:1.1rem; color:var(--text-muted);">Category not found.</p>
                <a href="categories.php" class="btn-primary btn-lg" style="margin-top:24px; display:inline-flex;">
                    <i class="fas fa-th-large"></i> Browse Categories
                </a>
            </div>
        </div>
    </section>
<?php else: ?>

    <section class="hero" style="padding: 72px 24px 56px;">
        <div class="hero-content" style="max-width: 800px;">
            <h1 style="font-size: clamp(1.8rem, 5vw, 3rem);">
                <i class="fas <?= htmlspecialchars($category['icon'] ?? 'fa-tag') ?>" style="color: var(--accent); margin-right: 12px;"></i>
                <?= htmlspecialchars($category['name']) ?>
            </h1>
            <p style="margin-top: 8px;">
                <?= count($products) ?> active listing<?= count($products) !== 1 ? 's' : '' ?> in this category
            </p>
        </div>
    </section>

    <section class="listings-section" style="padding-top: 0;">
        <div class="container">
            <div class="section-header">
                <h2><i class="fas fa-box"></i> Listings</h2>
                <a href="categories.php" class="view-all"><i class="fas fa-arrow-left"></i> All Categories</a>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>No active listings in this category yet. Be the first to <a href="add-product.php">sell something</a>!</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="product-card">
                            <a href="product.php?slug=<?= urlencode($product['slug'] ?? $product['product_id']) ?>" class="product-link">
                                <div class="product-image">
                                    <img src="assets/images/uploads/<?= htmlspecialchars($product['image_path'] ?? 'default-product.jpg') ?>"
                                         alt="<?= htmlspecialchars($product['title']) ?>" loading="lazy">
                                    <span class="product-condition"><?= ucfirst(htmlspecialchars($product['condition_status'])) ?></span>
                                    <button class="favorite-btn" data-product-id="<?= $product['product_id'] ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title"><?= htmlspecialchars($product['title']) ?></h3>
                                    <div class="product-meta">
                                        <span class="product-price">ZMW <?= number_format($product['price'], 2) ?></span>
                                        <span class="product-location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($product['location'] ?? 'Unknown') ?></span>
                                    </div>
                                    <div class="product-seller">
                                        <span class="seller-name"><?= htmlspecialchars($product['seller_name']) ?></span>
                                        <span class="post-time"><?= timeAgo($product['created_at']) ?></span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>