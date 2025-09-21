<?php
session_start();

// Hardcoded credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'password123');

// Check if user is logged in, if not, redirect to login page
function require_login() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
}
?>
