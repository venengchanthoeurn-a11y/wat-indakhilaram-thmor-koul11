<?php
// Always include config to start the session
include 'config.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page
header('Location: login.php');
exit();
?>
