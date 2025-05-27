<?php
function check_login() {
    // Start session
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Redirect to login page
        header("Location: login.html");
        exit;
    }
}
?>