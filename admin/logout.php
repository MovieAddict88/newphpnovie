<?php
require_once 'auth.php';

// Unset all of the session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
