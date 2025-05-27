<?php
session_start();
require 'config.php'; // Include your database configuration file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Save to the database
    $stmt = $conn->prepare("INSERT INTO researchuser (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashedPassword);

    if ($stmt->execute()) {
        setcookie('username', $username, time() + (86400 * 30), '/'); // 30 days cookie
        echo "حسابك تم إنشاؤه بنجاح!";
    } else {
        echo "خطأ في إنشاء الحساب.";
    }
    
    $stmt->close();
    $conn->close();
}
?>