<?php
if (!function_exists('timeAgo')) {
    require_once __DIR__ . '/functions.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Marketplace</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/media.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="assets/js/main.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-store"></i>
                <span>Marketplace</span>
            </a>

            <div class="search-bar">
                <form action="search.php" method="GET">
                    <input type="text" name="q" placeholder="Search for anything..." autocomplete="off">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="nav-links">
                <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
                <a href="categories.php" class="nav-link"><i class="fas fa-th-large"></i> Categories</a>

                <?php if (isLoggedIn()): ?>
                    <a href="conversations.php" class="nav-link" id="navChatsLink">
                        <i class="fas fa-comment-dots"></i> Chats
                        <span id="navMsgBadge" style="
                            display: none;
                            background: var(--accent);
                            color: var(--text-inverse);
                            font-size: 0.6rem;
                            font-weight: 700;
                            min-width: 18px;
                            height: 18px;
                            border-radius: 9px;
                            padding: 0 5px;
                            align-items: center;
                            justify-content: center;
                            margin-left: 2px;
                        "></span>
                    </a>
                    <a href="add-product.php" class="btn-sell"><i class="fas fa-plus"></i> Sell</a>

                    <div class="user-dropdown">
                        <button class="user-btn">
                            <img src="assets/images/profiles/<?= htmlspecialchars($_SESSION['profile_image'] ?? 'default.jpg') ?>"
                                 alt="Profile" class="nav-avatar">
                            <span><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php?u=<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="my-listings.php"><i class="fas fa-box"></i> My Listings</a>
                            <a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a>

                            <?php if (!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                                <hr>
                                <a href="admin.php" class="admin-link">
                                    <i class="fas fa-shield-alt"></i> Admin Dashboard
                                </a>
                            <?php endif; ?>

                            <hr>
                            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Log In</a>
                    <a href="signup.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>

            <button class="mobile-menu-btn" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <div class="mobile-menu-overlay"></div>
    <div class="mobile-menu">
        <div class="mobile-menu-header">
            <span class="logo"><i class="fas fa-store"></i> Marketplace</span>
            <button class="mobile-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="mobile-search">
            <form action="search.php" method="GET">
                <input type="text" name="q" placeholder="Search...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="mobile-links">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="categories.php"><i class="fas fa-th-large"></i> Categories</a>
            <?php if (isLoggedIn()): ?>
                <a href="conversations.php"><i class="fas fa-comment-dots"></i> Chats</a>
                <a href="add-product.php"><i class="fas fa-plus"></i> Sell an Item</a>
                <a href="profile.php?u=<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="my-listings.php"><i class="fas fa-box"></i> My Listings</a>
                <a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a>

                <?php if (!empty($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                    <a href="admin.php" class="admin-link">
                        <i class="fas fa-shield-alt"></i> Admin Dashboard
                    </a>
                <?php endif; ?>

                <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Log In</a>
                <a href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
            <?php endif; ?>
        </div>
    </div>

    <main>