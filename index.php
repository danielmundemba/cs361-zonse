<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY display_order, name");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.*, pi.image_path, u.username, u.full_name AS seller_name
    FROM products p
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = TRUE
    JOIN users u ON p.seller_id = u.user_id
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 12
");
$products = $stmt->fetchAll();

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-content">
        <h1>Buy & Sell <span class="gradient-text">Anything</span></h1>
        <p>Discover amazing deals in your community. From electronics to fashion, find what you need or turn your items into cash.</p>
        <div class="hero-buttons">
            <a href="search.php" class="btn-primary btn-lg"><i class="fas fa-search"></i> Browse Listings</a>
            <a href="add-product.php" class="btn-outline btn-lg"><i class="fas fa-plus"></i> Start Selling</a>
        </div>
    </div>
    <div class="hero-pattern"></div>
</section>

<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2><i class="fas fa-th-large"></i> Browse Categories</h2>
            <a href="categories.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="category.php?slug=<?= urlencode(strtolower(str_replace(' ', '-', $cat['name']))) ?>" class="category-card">
                    <div class="category-icon">
                        <i class="fas <?= htmlspecialchars($cat['icon'] ?? 'fa-tag') ?>"></i>
                    </div>
                    <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="listings-section">
    <div class="container">
        <div class="section-header">
            <h2><i class="fas fa-fire"></i> Fresh Listings</h2>
            <a href="search.php" class="view-all">See All <i class="fas fa-arrow-right"></i></a>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No listings yet. Be the first to <a href="add-product.php">sell something</a>!</p>
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

<section class="cta-section">
    <div class="container">
        <div class="cta-card">
            <div class="cta-content">
                <h2>Ready to declutter?</h2>
                <p>Turn your unused items into cash. Listing is free and takes less than a minute.</p>
                <a href="add-product.php" class="btn-primary btn-lg"><i class="fas fa-camera"></i> List an Item</a>
            </div>
            <div class="cta-decoration">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>