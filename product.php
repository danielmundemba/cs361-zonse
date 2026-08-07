<?php
require_once __DIR__ . '/includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*,
           u.username, u.full_name, u.profile_image, u.location AS seller_location, u.created_at AS member_since,
           c.name AS category_name
    FROM products p
    JOIN users u ON p.seller_id = u.user_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.slug = ? AND p.status = 'active'
    LIMIT 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$imgStmt->execute([$product['product_id']]);
$images = $imgStmt->fetchAll();

$moreStmt = $pdo->prepare("
    SELECT p.*, pi.image_path
    FROM products p
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    WHERE p.seller_id = ? AND p.product_id != ? AND p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT 4
");
$moreStmt->execute([$product['seller_id'], $product['product_id']]);
$moreBySeller = $moreStmt->fetchAll();

$isFavorited = false;
if (isLoggedIn()) {
    $favCheck = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?");
    $favCheck->execute([$_SESSION['user_id'], $product['product_id']]);
    $isFavorited = (bool)$favCheck->fetch();
}

require_once __DIR__ . '/includes/functions.php';
$pageTitle = htmlspecialchars($product['title']);
require_once __DIR__ . '/includes/header.php';
?>

<section class="product-view-section">
    <div class="container">

        <nav class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i></a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <a href="categories.php"><?= htmlspecialchars($product['category_name'] ?? 'All') ?></a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span><?= htmlspecialchars($product['title']) ?></span>
        </nav>

        <div class="product-layout">

            <div class="gallery-col">
                <div class="main-image-wrap">
                    <img id="mainImage"
                         src="assets/images/uploads/<?= htmlspecialchars($images[0]['image_path'] ?? 'default-product.jpg') ?>"
                         alt="<?= htmlspecialchars($product['title']) ?>">

                    <div class="img-badges">
                        <span class="condition-pill condition-<?= $product['condition_status'] ?>">
                            <?= ucfirst($product['condition_status']) ?>
                        </span>
                    </div>

                    <?php if (isLoggedIn() && $_SESSION['user_id'] !== $product['seller_id']): ?>
                        <button class="fav-overlay-btn <?= $isFavorited ? 'active' : '' ?>"
                                onclick="toggleFav(this, <?= $product['product_id'] ?>)"
                                title="<?= $isFavorited ? 'Remove from favorites' : 'Save to favorites' ?>">
                            <i class="<?= $isFavorited ? 'fas' : 'far' ?> fa-heart"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (count($images) > 1): ?>
                    <div class="thumb-strip">
                        <?php foreach ($images as $i => $img): ?>
                            <button class="thumb <?= $i === 0 ? 'active' : '' ?>"
                                    onclick="switchImage(this, 'assets/images/uploads/<?= htmlspecialchars($img['image_path']) ?>')">
                                <img src="assets/images/uploads/<?= htmlspecialchars($img['image_path']) ?>"
                                     alt="Image <?= $i + 1 ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="details-col">

                <div class="details-top">
                    <span class="category-tag">
                        <i class="fas fa-th-large"></i>
                        <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                    </span>
                    <span class="post-date">
                        <i class="fas fa-clock"></i>
                        <?= timeAgo($product['created_at']) ?>
                    </span>
                </div>

                <h1 class="product-heading"><?= htmlspecialchars($product['title']) ?></h1>

                <div class="price-row">
                    <span class="big-price">ZMW <?= number_format($product['price'], 2) ?></span>
                    <span class="location-tag">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($product['location']) ?>
                    </span>
                </div>

                <div class="description-block">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>

                <div class="action-row">
                    <?php if (!isLoggedIn()): ?>
                        <a href="login.php" class="btn-contact">
                            <i class="fas fa-comment-dots"></i> Log in to Contact Seller
                        </a>
                    <?php elseif ($_SESSION['user_id'] === $product['seller_id']): ?>
                        <a href="edit-product.php?id=<?= $product['product_id'] ?>" class="btn-contact btn-edit-own">
                            <i class="fas fa-pen"></i> Edit Your Listing
                        </a>
                    <?php else: ?>
                        <a href="#" class="btn-contact"
                           onclick="event.preventDefault(); messageSeller(this, <?= $product['product_id'] ?>);">
                            <i class="fas fa-comment-dots"></i> Message Seller
                        </a>
                        <button class="btn-fav <?= $isFavorited ? 'active' : '' ?>"
                                onclick="toggleFav(this, <?= $product['product_id'] ?>)">
                            <i class="<?= $isFavorited ? 'fas' : 'far' ?> fa-heart"></i>
                            <span><?= $isFavorited ? 'Saved' : 'Save' ?></span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="seller-card">
                    <div class="seller-avatar-wrap">
                        <img src="assets/images/profiles/<?= htmlspecialchars($product['profile_image'] ?? 'default.jpg') ?>"
                             alt="<?= htmlspecialchars($product['full_name']) ?>">
                    </div>
                    <div class="seller-info">
                        <span class="seller-label">Listed by</span>
                        <a href="profile.php?u=<?= urlencode($product['username']) ?>" class="seller-name">
                            <?= htmlspecialchars($product['full_name']) ?>
                        </a>
                        <span class="seller-since">
                            <i class="fas fa-calendar-alt"></i>
                            Member since <?= date('M Y', strtotime($product['member_since'])) ?>
                        </span>
                    </div>
                    <a href="profile.php?u=<?= urlencode($product['username']) ?>" class="view-profile-btn">
                        View Profile <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

            </div>
        </div>

        <?php if (!empty($moreBySeller)): ?>
            <div class="more-section">
                <div class="section-header">
                    <h2><i class="fas fa-store"></i> More from <?= htmlspecialchars($product['full_name']) ?></h2>
                    <a href="profile.php?u=<?= urlencode($product['username']) ?>" class="view-all">
                        See all <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="products-grid">
                    <?php foreach ($moreBySeller as $item): ?>
                        <a href="product.php?slug=<?= urlencode($item['slug']) ?>" class="product-card product-link">
                            <div class="product-image">
                                <img src="assets/images/uploads/<?= htmlspecialchars($item['image_path'] ?? 'default-product.jpg') ?>"
                                     alt="<?= htmlspecialchars($item['title']) ?>">
                                <span class="product-condition"><?= ucfirst($item['condition_status']) ?></span>
                            </div>
                            <div class="product-info">
                                <p class="product-title"><?= htmlspecialchars($item['title']) ?></p>
                                <div class="product-meta">
                                    <span class="product-price">ZMW <?= number_format($item['price'], 2) ?></span>
                                    <span class="product-location">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['location']) ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<style>
.product-view-section {
    padding: 32px 0 80px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 28px;
    font-size: 0.85rem;
    color: var(--text-muted);
    flex-wrap: wrap;
}
.breadcrumb a {
    color: var(--text-muted);
    transition: color var(--transition-fast);
}
.breadcrumb a:hover { color: var(--accent); }
.breadcrumb-sep { font-size: 0.65rem; color: var(--text-muted); opacity: 0.5; }
.breadcrumb span:last-child {
    color: var(--text-secondary);
    max-width: 260px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: start;
    margin-bottom: 64px;
}

.gallery-col { position: sticky; top: calc(var(--nav-height) + 20px); }

.main-image-wrap {
    position: relative;
    aspect-ratio: 4/3;
    background: var(--bg-tertiary);
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 1px solid var(--border-color);
}
.main-image-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}
.main-image-wrap:hover img { transform: scale(1.03); }

.img-badges {
    position: absolute;
    top: 14px; left: 14px;
    display: flex; gap: 8px;
}
.condition-pill {
    padding: 5px 12px;
    border-radius: var(--radius-xl);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(8px);
}
.condition-new        { background: rgba(0,212,170,0.2);  color: var(--accent); border: 1px solid rgba(0,212,170,0.3); }
.condition-used       { background: rgba(254,202,87,0.2); color: #feca57;       border: 1px solid rgba(254,202,87,0.3); }
.condition-refurbished{ background: rgba(72,219,251,0.2); color: #48dbfb;       border: 1px solid rgba(72,219,251,0.3); }

.fav-overlay-btn {
    position: absolute;
    top: 14px; right: 14px;
    width: 40px; height: 40px;
    border-radius: 50%;
    background: rgba(15,15,18,0.75);
    backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-secondary);
    transition: all var(--transition-fast);
    border: 1px solid var(--border-color);
}
.fav-overlay-btn:hover, .fav-overlay-btn.active {
    background: rgba(255,107,107,0.9);
    color: #fff;
    border-color: transparent;
}

.thumb-strip {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    flex-wrap: wrap;
}
.thumb {
    width: 72px; height: 72px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    border: 2px solid var(--border-color);
    transition: all var(--transition-fast);
    cursor: pointer;
    padding: 0;
}
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.thumb:hover { border-color: var(--accent); }
.thumb.active { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent-glow); }

.details-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.category-tag {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.78rem; font-weight: 600;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    padding: 5px 12px;
    border-radius: var(--radius-xl);
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.category-tag i { color: var(--accent); font-size: 0.7rem; }
.post-date { font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }

.product-heading {
    font-size: clamp(1.4rem, 3vw, 2rem);
    font-weight: 700;
    line-height: 1.25;
    color: var(--text-primary);
    margin-bottom: 20px;
}

.price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 20px 0;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 24px;
}
.big-price {
    font-size: 2rem;
    font-weight: 800;
    color: var(--accent);
    letter-spacing: -0.02em;
}
.location-tag {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.9rem;
    color: var(--text-muted);
}
.location-tag i { color: var(--accent); font-size: 0.8rem; }

.description-block {
    margin-bottom: 28px;
}
.description-block h3 {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-muted);
    margin-bottom: 10px;
}
.description-block p {
    color: var(--text-secondary);
    line-height: 1.75;
    font-size: 0.95rem;
}

.action-row {
    display: flex;
    gap: 12px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
.btn-contact {
    flex: 1;
    min-width: 180px;
    padding: 14px 20px;
    background: var(--accent-gradient);
    color: var(--text-inverse);
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.95rem;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: all var(--transition-fast);
    border: none; cursor: pointer;
}
.btn-contact:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,212,170,0.3);
}
.btn-edit-own {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}
.btn-edit-own:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: var(--accent-glow);
    box-shadow: none;
    transform: translateY(-2px);
}
.btn-fav {
    padding: 14px 20px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    border-radius: var(--radius-sm);
    font-size: 0.95rem;
    font-weight: 600;
    display: flex; align-items: center; gap: 8px;
    transition: all var(--transition-fast);
    cursor: pointer;
    white-space: nowrap;
}
.btn-fav:hover, .btn-fav.active {
    background: rgba(255,107,107,0.1);
    border-color: #ff6b6b;
    color: #ff6b6b;
}

.seller-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    transition: border-color var(--transition-fast);
}
.seller-card:hover { border-color: var(--border-focus); }

.seller-avatar-wrap {
    flex-shrink: 0;
    width: 48px; height: 48px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid var(--border-color);
}
.seller-avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }

.seller-info {
    display: flex; flex-direction: column; gap: 3px;
    flex: 1; min-width: 0;
}
.seller-label { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.seller-name { font-size: 0.95rem; font-weight: 700; color: var(--text-primary); transition: color var(--transition-fast); }
.seller-name:hover { color: var(--accent); }
.seller-since { font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }

.view-profile-btn {
    flex-shrink: 0;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--accent);
    display: flex; align-items: center; gap: 6px;
    transition: gap var(--transition-fast);
    white-space: nowrap;
}
.view-profile-btn:hover { gap: 10px; }

.more-section { padding-top: 16px; }

@media (max-width: 900px) {
    .product-layout {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    .gallery-col { position: static; }
}
@media (max-width: 480px) {
    .price-row { flex-direction: column; align-items: flex-start; }
    .action-row { flex-direction: column; }
    .btn-contact { min-width: unset; }
}
</style>

<script>
function switchImage(btn, src) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
}

function messageSeller(btn, productId) {
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting chat...';
    btn.style.pointerEvents = 'none';

    fetch('api/conversations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.conversation_id) {
            window.location.href = 'messages.php?chat=' + data.conversation_id;
        } else {
            alert(data.error || 'Could not start chat. Please try again.');
            btn.innerHTML = original;
            btn.style.pointerEvents = '';
        }
    })
    .catch(() => {
        alert('Network error. Please check your connection and try again.');
        btn.innerHTML = original;
        btn.style.pointerEvents = '';
    });
}

function toggleFav(btn, productId) {
    const wasActive = btn.classList.contains('active');
    const icon  = btn.querySelector('i');
    const label = btn.querySelector('span');

    btn.classList.toggle('active', !wasActive);
    if (icon)  icon.className  = wasActive ? 'far fa-heart' : 'fas fa-heart';
    if (label) label.textContent = wasActive ? 'Save' : 'Saved';

    fetch('api/favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            btn.classList.toggle('active', wasActive);
            if (icon)  icon.className  = wasActive ? 'fas fa-heart' : 'far fa-heart';
            if (label) label.textContent = wasActive ? 'Saved' : 'Save';
        }
    })
    .catch(() => {
        btn.classList.toggle('active', wasActive);
        if (icon)  icon.className  = wasActive ? 'fas fa-heart' : 'far fa-heart';
        if (label) label.textContent = wasActive ? 'Saved' : 'Save';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>