<?php
/**
 * Admin Panel - Logout
 * CSE 391: Assignment 3 - Car Workshop Appointment System
 */

session_start();
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
header('Location: index.php?msg=' . urlencode('You have been logged out successfully.'));
exit;
?>

