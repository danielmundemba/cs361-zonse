<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    logoutUser();
}

header('Location: index.php');
exit;