<?php
// includes/auth.php

/**
 * Check if a user is currently logged in
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user's data
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT user_id, username, full_name, email, profile_image, phone, location, created_at 
        FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetch() ?: null;
}

/**
 * Require authentication — redirect to login if not logged in
 * @param string $redirectUrl
 */
function requireAuth(string $redirectUrl = 'login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Require guest — redirect to home if already logged in
 * Useful for login/signup pages
 * @param string $redirectUrl
 */
function requireGuest(string $redirectUrl = 'index.php'): void {
    if (isLoggedIn()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Log in a user by setting session variables
 * @param array $user
 */
function loginUser(array $user): void {
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.jpg';
}

/**
 * Log out the current user
 */
function logoutUser(): void {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Flash message helper
 * @param string $type
 * @param string $message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash message
 * @param string $type
 * @return string|null
 */
function getFlash(string $type): ?string {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

/**
 * Check if user owns a resource
 * @param int $resourceOwnerId
 * @return bool
 */
function isOwner(int $resourceOwnerId): bool {
    return isLoggedIn() && $_SESSION['user_id'] === $resourceOwnerId;
}