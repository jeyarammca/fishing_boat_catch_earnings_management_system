<?php
// Home page - Redirect to appropriate dashboard based on login status
session_start();
require_once 'config/config.php';

if (IS_LOGGED_IN) {
    $user = execute_query("SELECT role FROM users WHERE id = " . $_SESSION['user_id'])->fetch_assoc();
    if ($user['role'] == 'admin') {
        header('Location: ' . SITE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . SITE_URL . 'staff/dashboard.php');
    }
    exit();
} else {
    header('Location: ' . SITE_URL . 'login.php');
    exit();
}
?>
