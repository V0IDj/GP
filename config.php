<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}
?>