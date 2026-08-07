<?php
require_once __DIR__ . '/includes/auth.php';

$stmt = $pdo->query("
    SELECT c.*, COUNT(p.product_id) AS listing_count
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'active'
    GROUP BY c.category_id
    ORDER BY c.display_order, c.name
");
$categories = $stmt->fetchAll();

$pageTitle = 'Categories';
require_once __DIR__ . '/includes/header.php';
?>

<section class="categories-section categories-page">
    <div class="container">
        <div class="section-header">
            <h2><i class="fas fa-th-large"></i> Browse Categories</h2>
        </div>

        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>No categories available yet.</p>
            </div>
        <?php else: ?>
            <div class="categories-grid">
                <?php foreach ($categories as $cat): ?>
                    <a href="category.php?slug=<?= urlencode(strtolower(str_replace(' ', '-', $cat['name']))) ?>" class="category-card">
                        <div class="category-icon">
                            <i class="fas <?= htmlspecialchars($cat['icon'] ?? 'fa-tag') ?>"></i>
                        </div>
                        <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                        <span class="category-count">
                            <?= (int)$cat['listing_count'] ?> listing<?= (int)$cat['listing_count'] !== 1 ? 's' : '' ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.categories-page { padding-top: 40px; }
.category-count {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 4px;
    font-weight: 500;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>