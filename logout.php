<?php
require_once 'config.php';

// Clear all session data
session_start();
session_unset();
session_destroy();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page with message
header('Location: /login.php?message=logged_out');
exit;
?>