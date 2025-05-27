<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Remove cookies if they exist
if (isset($_COOKIE['username'])) {
    setcookie('username', '', time() - 3600, "/");
}
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, "/");
}

// Redirect to login page or homepage
header("Location: login.html");
exit();
?>