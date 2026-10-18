<?php
// logout.php - Session destruction
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    log_activity($pdo, $_SESSION['user_id'], 'User Logout', 'Auth', "User logged out");
}

session_unset();
session_destroy();

header("Location: login.php");
exit;
