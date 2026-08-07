<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors['general'] = 'Invalid request. Please try again.';
    } else {
        $result = register($_POST);
        if ($result['success']) {
            flash('success', 'Account created! Please log in.');
            header('Location: login.php');
            exit;
        } else {
            $errors = $result['errors'];
            $old    = $_POST;
        }
    }
}

$pageTitle = 'Sign Up';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="auth-card">
        <div class="auth-header">
            <a href="index.php" class="logo">
                <i class="fas fa-store"></i>
                <span>Marketplace</span>
            </a>
            <h1>Create Account</h1>
            <p>Join our community to start buying and selling</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php" class="auth-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="names-row">
                <div class="form-group <?= isset($errors['first_name']) ? 'has-error' : '' ?>">
                    <label for="first_name">First Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="first_name" name="first_name" placeholder="John"
                               value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required autocomplete="given-name">
                    </div>
                    <?php if (isset($errors['first_name'])): ?>
                        <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['first_name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group <?= isset($errors['last_name']) ? 'has-error' : '' ?>">
                    <label for="last_name">Last Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="last_name" name="last_name" placeholder="Doe"
                               value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required autocomplete="family-name">
                    </div>
                    <?php if (isset($errors['last_name'])): ?>
                        <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['last_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group <?= isset($errors['username']) ? 'has-error' : '' ?>">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-at"></i>
                    <input type="text" id="username" name="username" placeholder="johndoe"
                           value="<?= htmlspecialchars($old['username'] ?? '') ?>" required autocomplete="username">
                </div>
                <?php if (isset($errors['username'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['username']) ?></span>
                <?php else: ?>
                    <span class="hint-text">3-30 characters, letters, numbers & underscores</span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="you@example.com"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>" required autocomplete="email">
                </div>
                <?php if (isset($errors['email'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                <label for="phone">Phone <span class="optional">(optional)</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone" placeholder="+260 97 123 4567"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>" autocomplete="tel">
                </div>
                <?php if (isset($errors['phone'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['phone']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="location">Location <span class="optional">(optional)</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="location" name="location" placeholder="e.g. Lusaka, Kitwe"
                           value="<?= htmlspecialchars($old['location'] ?? '') ?>" autocomplete="address-level2">
                </div>
            </div>

            <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••"
                           required autocomplete="new-password">
                    <button type="button" class="toggle-password" data-target="password" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="password-strength" style="display:none;">
                    <div class="strength-bar">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <span class="strength-text">Enter password</span>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••"
                           required autocomplete="new-password">
                    <button type="button" class="toggle-password" data-target="confirm_password" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['confirm_password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group <?= isset($errors['terms']) ? 'has-error' : '' ?>">
                <label class="checkbox-wrapper terms">
                    <input type="checkbox" name="terms" value="1" <?= !empty($old['terms']) ? 'checked' : '' ?> required>
                    <span class="checkmark"></span>
                    <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                </label>
                <?php if (isset($errors['terms'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['terms']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-auth">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="auth-divider"><span>or</span></div>

        <div class="auth-switch">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        const icon   = btn.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            target.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

const passwordInput = document.getElementById('password');
const strengthBox   = document.getElementById('password-strength');
const strengthBars  = strengthBox.querySelectorAll('.strength-bar span');
const strengthText  = strengthBox.querySelector('.strength-text');

passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    if (!val.length) { strengthBox.style.display = 'none'; return; }
    strengthBox.style.display = 'flex';

    let score = 0;
    if (val.length >= 8)           score++;
    if (val.length >= 12)          score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    const levels = ['weak', 'fair', 'good', 'strong'];
    let level = 0;
    if (score >= 2) level = 1;
    if (score >= 3) level = 2;
    if (score >= 5) level = 3;

    strengthBars.forEach((bar, i) => {
        bar.className = '';
        if (i <= level) bar.classList.add(levels[level]);
    });
    strengthText.textContent = levels[level].charAt(0).toUpperCase() + levels[level].slice(1);
    strengthText.className   = 'strength-text ' + levels[level];
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>