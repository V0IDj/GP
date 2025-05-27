<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'غير مصرح لك بالوصول']);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "gp";    // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]);
    exit;
}

// Set the character set to utf8
$conn->set_charset("utf8");

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Prepare SQL query to get user data
$sql = "SELECT user_id, name, email, organization_id, username, bio, profile_image, 
               college, major, Number, PhoneNumber 
        FROM researchuser 
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Get user data
    $user = $result->fetch_assoc();
    
    // Convert profile image to base64 if exists
    if ($user['profile_image']) {
        $user['profile_image'] = base64_encode($user['profile_image']);
    } else {
        $user['profile_image'] = null;
    }
    
    // Remove sensitive data
    unset($user['password']);
    
    // Return user data as JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($user, JSON_UNESCAPED_UNICODE);
} else {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'لم يتم العثور على بيانات المستخدم']);
}

// Close connection
$stmt->close();
$conn->close();
?>