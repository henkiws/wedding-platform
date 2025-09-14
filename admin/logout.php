<?php
// admin/logout.php - Admin Logout
session_start();

// Destroy admin session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

// Destroy the entire session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>