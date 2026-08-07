    <?php

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/db.php';

    function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function validateCsrf(): bool {
        $token = $_POST['csrf_token'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    function requireLogin(): void {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
            header('Location: login.php');
            exit;
        }
    }

    function currentUser(): ?array {
        if (!isLoggedIn()) return null;
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    function register(array $data): array {
        $errors = [];

        $username = trim($data['username'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors['username'] = '3-30 characters, letters, numbers & underscores only.';
        }

        $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        $firstName = trim($data['first_name'] ?? '');
        $lastName  = trim($data['last_name']  ?? '');
        if (strlen($firstName) < 1 || strlen($firstName) > 100) {
            $errors['first_name'] = 'First name is required.';
        }
        if (strlen($lastName) < 1 || strlen($lastName) > 100) {
            $errors['last_name'] = 'Last name is required.';
        }

        $password = $data['password'] ?? '';
        $confirm  = $data['confirm_password'] ?? '';
        if (strlen($password) < 8) {
            $errors['password'] = 'Must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        $phone = trim($data['phone'] ?? '');
        if ($phone && !preg_match('/^[\d\s\-\+\(\)]{7,20}$/', $phone)) {
            $errors['phone'] = 'Invalid phone number format.';
        }

        $location = trim($data['location'] ?? '');

        if (empty($data['terms'])) {
            $errors['terms'] = 'You must agree to the Terms of Service.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'user_id' => null];
        }

        global $pdo;

        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['general' => 'Username or email already exists.'], 'user_id' => null];
        }

        $fullName = $firstName . ' ' . $lastName;
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name, full_name, phone, location, profile_image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'default.jpg')
            ");
            $stmt->execute([$username, $email, $hash, $firstName, $lastName, $fullName, $phone, $location]);
            return ['success' => true, 'errors' => [], 'user_id' => (int)$pdo->lastInsertId()];
        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Something went wrong. Please try again.'], 'user_id' => null];
        }
    }

    function loginUser(string $login, string $password): array {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid username/email or password.'];
        }

        $_SESSION['user_id']       = $user['user_id'];
        $_SESSION['username']       = $user['username'];
        $_SESSION['full_name']      = $user['full_name'];
        $_SESSION['profile_image']  = $user['profile_image'] ?? 'default.jpg';
        $_SESSION['role']           = $user['role'] ?? 'user';

        session_regenerate_id(true);

        return ['success' => true, 'error' => null];
    }

    function logoutUser(): void {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
        session_destroy();
    }
    function flash(string $type, string $message = null): ?string {
        if ($message !== null) {
            $_SESSION['flash_' . $type] = $message;
            return null;
        }
        $msg = $_SESSION['flash_' . $type] ?? null;
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }