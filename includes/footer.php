    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="index.php" class="logo">
                        <i class="fas fa-store"></i>
                        <span>Marketplace</span>
                    </a>
                    <p>Your community marketplace for buying and selling. Safe, simple, and local.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h4>Discover</h4>
                    <a href="search.php">Browse All</a>
                    <a href="categories.php">Categories</a>
                    <a href="search.php?sort=newest">New Arrivals</a>
                    <a href="search.php?sort=price_asc">Deals</a>
                </div>
                
                <div class="footer-links">
                    <h4>Selling</h4>
                    <a href="add-product.php">List an Item</a>
                    <a href="#">Selling Tips</a>
                    <a href="#">Safety Guidelines</a>
                    <a href="#">Pricing Guide</a>
                </div>
                
                <div class="footer-links">
                    <h4>Account</h4>
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php?u=<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">Profile</a>
                        <a href="my-listings.php">My Listings</a>
                        <a href="messages.php">Messages</a>
                        <a href="settings.php">Settings</a>
                    <?php else: ?>
                        <a href="login.php">Log In</a>
                        <a href="signup.php">Sign Up</a>
                        <a href="#">Help Center</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Marketplace. All rights reserved.</p>
                <div class="footer-legal">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Cookies</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="assets/js/main.js"></script>
</body>
</html>