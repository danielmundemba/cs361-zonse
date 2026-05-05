<?php
$pageTitle = 'Log In';
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireGuest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id, username, full_name, email, password_hash, profile_image FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
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
            <h1>Welcome back</h1>
            <p>Log in to your account to continue</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-options">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="remember">
                    <span class="checkmark"></span>
                    Remember me
                </label>
                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn-auth">
                <i class="fas fa-sign-in-alt"></i> Log In
            </button>
        </form>
        
        <div class="auth-divider">
            <span>or</span>
        </div>
        
        <p class="auth-switch">
            Don't have an account? <a href="signup.php">Sign up</a>
        </p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>