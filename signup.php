<?php
$pageTitle = 'Sign Up';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireGuest();

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $full_name = "$first_name $last_name";
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    $old = $_POST;
    
    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
        $errors['username'] = '3-30 characters. Letters, numbers, underscores, hyphens only.';
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'Username already taken.';
        }
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email.';
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already registered.';
        }
    }
    
    if (empty($first_name)) {
        $errors['first_name'] = 'First name is required.';
    }
    
    if (empty($last_name)) {
        $errors['last_name'] = 'Last name is required.';
    }
    
    if (!empty($phone) && !preg_match('/^[0-9+\-\s\(\)]{7,20}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain letters and numbers.';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, full_name, password_hash, phone) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $full_name, $password_hash, $phone ?: null]);
        
        $user_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("SELECT user_id, username, full_name, email, profile_image FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        loginUser($user);
        header('Location: index.php');
        exit;
    }
}

require_once 'includes/header.php';
?>

<section class="auth-section">
    <div class="auth-card">
        <div class="auth-header">
            <a href="index.php" class="logo">
                <i class="fas fa-store"></i>
                <span>Marketplace</span>
            </a>
            <h1>Create your account</h1>
            <p>Join our community to buy and sell</p>
        </div>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>
        
        <form action="signup.php" method="POST" class="auth-form" id="signupForm" novalidate>
            
            <!-- Username with live validation -->
            <div class="form-group <?= isset($errors['username']) ? 'has-error' : '' ?>" id="usernameGroup">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-at"></i>
                    <input type="text" id="username" name="username" placeholder="johndoe" 
                           value="<?= htmlspecialchars($old['username'] ?? '') ?>" 
                           autocomplete="off" required
                           data-check-url="check-username.php">
                    <span class="input-status" id="usernameStatus">
                        <i class="fas fa-spinner fa-spin" style="display:none;"></i>
                        <i class="fas fa-check" style="display:none;"></i>
                        <i class="fas fa-times" style="display:none;"></i>
                    </span>
                </div>
                <span class="error-text" id="usernameError">
                    <?php if (isset($errors['username'])): ?>
                        <i class="fas fa-exclamation-triangle"></i> <?= $errors['username'] ?>
                    <?php endif; ?>
                </span>
                <span class="hint-text" id="usernameHint">3-30 characters, letters, numbers, underscores, hyphens</span>
            </div>
            
            <!-- Email -->
            <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="you@example.com" 
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                </div>
                <?php if (isset($errors['email'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-triangle"></i> <?= $errors['email'] ?></span>
                <?php endif; ?>
            </div>
            
            <!-- First & Last Name -->
            <div class="form-row names-row">
                <div class="form-group <?= isset($errors['first_name']) ? 'has-error' : '' ?>">
                    <label for="first_name">First Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="first_name" name="first_name" placeholder="John" 
                               value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
                    </div>
                    <?php if (isset($errors['first_name'])): ?>
                        <span class="error-text"><i class="fas fa-exclamation-triangle"></i> <?= $errors['first_name'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group <?= isset($errors['last_name']) ? 'has-error' : '' ?>">
                    <label for="last_name">Last Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="last_name" name="last_name" placeholder="Doe" 
                               value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
                    </div>
                    <?php if (isset($errors['last_name'])): ?>
                        <span class="error-text"><i class="fas fa-exclamation-triangle"></i> <?= $errors['last_name'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Phone Number -->
            <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                <label for="phone">Phone Number</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone" placeholder="+1 (555) 000-0000" 
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
                <?php if (isset($errors['phone'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-triangle"></i> <?= $errors['phone'] ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Password -->
            <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-triangle"></i> <?= $errors['password'] ?></span>
                <?php endif; ?>
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <span class="strength-text">Password strength</span>
                </div>
            </div>
            
            <!-- Confirm Password -->
            <div class="form-group <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                    <span class="input-status" id="matchStatus">
                        <i class="fas fa-check" style="display:none;"></i>
                        <i class="fas fa-times" style="display:none;"></i>
                    </span>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <span class="error-text"><i class="fas fa-exclamation-triangle"></i> <?= $errors['confirm_password'] ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Terms -->
            <div class="form-group">
                <label class="checkbox-wrapper terms">
                    <input type="checkbox" name="terms" id="terms" required>
                    <span class="checkmark"></span>
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="btn-auth" id="submitBtn" disabled>
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
        
        <div class="auth-divider">
            <span>or</span>
        </div>
        
        <p class="auth-switch">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>