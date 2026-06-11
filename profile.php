<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// ─── Fetch user by username ───
$username = trim($_GET['u'] ?? '');
if (empty($username)) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT user_id, username, email, first_name, last_name, full_name,
           phone, location, profile_image, created_at, role
    FROM users
    WHERE username = ?
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php');
    exit;
}

// ─── Fetch active listings ───
$listingsStmt = $pdo->prepare("
    SELECT p.*, pi.image_path
    FROM products p
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    WHERE p.seller_id = ? AND p.status = 'active'
    ORDER BY p.created_at DESC
");
$listingsStmt->execute([$user['user_id']]);
$listings = $listingsStmt->fetchAll();

// ─── Fetch stats ───
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_listings,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_listings
    FROM products
    WHERE seller_id = ?
");
$statsStmt->execute([$user['user_id']]);
$stats = $statsStmt->fetch();

// ─── Check if viewing own profile ───
$isOwnProfile = isLoggedIn() && $_SESSION['user_id'] === $user['user_id'];

$pageTitle = htmlspecialchars($user['full_name'] ?? $user['username']);
require_once __DIR__ . '/includes/header.php';
?>

<section class="profile-view-section">
    <div class="container">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i></a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></span>
        </nav>

        <!-- Profile Header -->
        <div class="profile-header-card">
            <div class="profile-cover"></div>

            <div class="profile-header-body">
                <div class="profile-avatar-wrap">
                    <img src="assets/images/profiles/<?= htmlspecialchars($user['profile_image'] ?? 'default.jpg') ?>"
                         alt="<?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>">
                </div>

                <div class="profile-header-info">
                    <h1 class="profile-name"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></h1>
                    <span class="profile-handle">@<?= htmlspecialchars($user['username']) ?></span>

                    <div class="profile-stats-row">
                        <div class="stat-pill">
                            <i class="fas fa-box"></i>
                            <span><strong><?= (int)$stats['active_listings'] ?></strong> Active</span>
                        </div>
                        <div class="stat-pill">
                            <i class="fas fa-layer-group"></i>
                            <span><strong><?= (int)$stats['total_listings'] ?></strong> Total</span>
                        </div>
                        <div class="stat-pill">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Since <?= date('M Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <?php if (!empty($user['location'])): ?>
                            <div class="stat-pill">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($user['location']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="profile-action-row">
                        <?php if ($isOwnProfile): ?>
                            <a href="edit-profile.php" class="btn-contact btn-edit-own">
                                <i class="fas fa-pen"></i> Edit Profile
                            </a>
                            <a href="add-product.php" class="btn-contact">
                                <i class="fas fa-plus"></i> Sell an Item
                            </a>
                        <?php elseif (isLoggedIn()): ?>
                            <a href="#" class="btn-contact"
                               onclick="event.preventDefault(); messageUser(this, <?= $user['user_id'] ?>);">
                                <i class="fas fa-comment-dots"></i> Message Seller
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn-contact">
                                <i class="fas fa-comment-dots"></i> Log in to Message
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listings -->
        <div class="profile-listings-wrap">
            <div class="section-header">
                <h2>
                    <i class="fas fa-store"></i>
                    <?= $isOwnProfile ? 'My Listings' : 'Listings by ' . htmlspecialchars($user['full_name'] ?? $user['username']) ?>
                </h2>
                <?php if ($isOwnProfile): ?>
                    <a href="my-listings.php" class="view-all">
                        Manage all <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($listings)): ?>
                <div class="products-grid">
                    <?php foreach ($listings as $item): ?>
                        <div class="product-card">
                            <a href="product.php?slug=<?= urlencode($item['slug']) ?>" class="product-link">
                                <div class="product-image">
                                    <img src="assets/images/uploads/<?= htmlspecialchars($item['image_path'] ?? 'default-product.jpg') ?>"
                                         alt="<?= htmlspecialchars($item['title']) ?>">
                                    <span class="product-condition"><?= ucfirst($item['condition_status']) ?></span>

                                    <?php if (isLoggedIn() && !$isOwnProfile): ?>
                                        <button class="favorite-btn"
                                                onclick="event.preventDefault(); event.stopPropagation(); toggleFav(this, <?= $item['product_id'] ?>)"
                                                title="Save to favorites">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <p class="product-title"><?= htmlspecialchars($item['title']) ?></p>
                                    <div class="product-meta">
                                        <span class="product-price">ZMW <?= number_format($item['price'], 2) ?></span>
                                        <span class="product-location">
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($item['location']) ?>
                                        </span>
                                    </div>
                                    <div class="product-seller">
                                        <span class="post-time">
                                            <i class="fas fa-clock"></i> <?= timeAgo($item['created_at']) ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p><?= $isOwnProfile ? 'You have no active listings yet.' : 'This user has no active listings.' ?></p>
                    <?php if ($isOwnProfile): ?>
                        <a href="add-product.php" class="btn-contact" style="margin-top: 20px; display: inline-flex;">
                            <i class="fas fa-plus"></i> Create your first listing
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<style>
/* ─── Profile Page Styles ─── */
.profile-view-section {
    padding: 32px 0 80px;
}

/* Breadcrumb (consistent with product.php) */
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

/* Profile Header Card */
.profile-header-card {
    position: relative;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    overflow: hidden;
    margin-bottom: 48px;
    box-shadow: var(--shadow-md);
}

.profile-cover {
    height: 140px;
    background: var(--gradient-hero);
    position: relative;
}
.profile-cover::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at center, transparent 0%, var(--bg-card) 100%);
    opacity: 0.6;
}

.profile-header-body {
    display: flex;
    align-items: flex-start;
    gap: 28px;
    padding: 0 40px 40px;
    position: relative;
}

.profile-avatar-wrap {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid var(--bg-card);
    margin-top: -60px;
    position: relative;
    z-index: 2;
    flex-shrink: 0;
    background: var(--bg-tertiary);
    box-shadow: var(--shadow-md);
}
.profile-avatar-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-header-info {
    flex: 1;
    min-width: 0;
    padding-top: 16px;
}

.profile-name {
    font-size: clamp(1.4rem, 3vw, 1.9rem);
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 4px;
    line-height: 1.2;
}

.profile-handle {
    display: block;
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-bottom: 16px;
}

.profile-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    font-size: 0.85rem;
    color: var(--text-secondary);
}
.stat-pill i {
    color: var(--accent);
    font-size: 0.8rem;
}
.stat-pill strong {
    color: var(--text-primary);
    font-weight: 600;
}

.profile-action-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

/* Listings wrap */
.profile-listings-wrap {
    padding-top: 8px;
}

/* ─── Responsive ─── */
@media (max-width: 768px) {
    .profile-header-body {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 0 24px 32px;
    }
    .profile-avatar-wrap {
        width: 100px;
        height: 100px;
        margin-top: -50px;
    }
    .profile-stats-row {
        justify-content: center;
    }
    .profile-action-row {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .profile-header-body {
        padding: 0 16px 24px;
    }
    .profile-action-row {
        flex-direction: column;
    }
    .profile-action-row .btn-contact {
        min-width: unset;
        width: 100%;
    }
    .stat-pill {
        font-size: 0.8rem;
        padding: 5px 10px;
    }
}
</style>

<script>
// ── Message User ──
function messageUser(btn, userId) {
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting chat...';
    btn.style.pointerEvents = 'none';

    fetch('api/conversations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'user_id=' + encodeURIComponent(userId)
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

// ── Favorites (mirrors product.php logic) ──
function toggleFav(btn, productId) {
    const wasActive = btn.classList.contains('active');
    const icon = btn.querySelector('i');

    btn.classList.toggle('active', !wasActive);
    if (icon) icon.className = wasActive ? 'far fa-heart' : 'fas fa-heart';

    fetch('api/favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            btn.classList.toggle('active', wasActive);
            if (icon) icon.className = wasActive ? 'fas fa-heart' : 'far fa-heart';
        }
    })
    .catch(() => {
        btn.classList.toggle('active', wasActive);
        if (icon) icon.className = wasActive ? 'fas fa-heart' : 'far fa-heart';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>