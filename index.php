<?php
// index.php - Primary entry point router
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
} else {
    header("Location: pos.php");
}
exit;
