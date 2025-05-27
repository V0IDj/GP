<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
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
    echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]);
    exit;
}

// Set the character set to utf8
$conn->set_charset("utf8");

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check what action to perform
if (isset($_POST['section']) && $_POST['section'] === 'about') {
    // Update about information
    updateAboutInfo($conn, $user_id);
} elseif (isset($_POST['section']) && $_POST['section'] === 'contact') {
    // Update contact information
    updateContactInfo($conn, $user_id);
} elseif (isset($_POST['action']) && $_POST['action'] === 'update_password') {
    // Update password
    updatePassword($conn, $user_id);
} elseif (isset($_POST['action']) && $_POST['action'] === 'update_image') {
    // Update profile image
    updateProfileImage($conn, $user_id);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
}

// Close connection
$conn->close();

// Function to update about information
function updateAboutInfo($conn, $user_id) {
    // Validate input
    if (!isset($_POST['bio']) || !isset($_POST['college']) || !isset($_POST['major'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'بيانات مفقودة']);
        exit;
    }
    
    $bio = $_POST['bio'];
    $college = $_POST['college'];
    $major = $_POST['major'];
    
    // Prepare SQL query
    $sql = "UPDATE researchuser SET bio = ?, college = ?, major = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $bio, $college, $major, $user_id);
    
    // Execute query and check result
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث المعلومات بنجاح']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل تحديث المعلومات: ' . $stmt->error]);
    }
    
    $stmt->close();
}

// Function to update contact information
function updateContactInfo($conn, $user_id) {
    // Validate input
    if (!isset($_POST['email']) || !isset($_POST['phone'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'بيانات مفقودة']);
        exit;
    }
    
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'عنوان البريد الإلكتروني غير صالح']);
        exit;
    }
    
    // Validate phone (simple validation for numeric value)
    if (!is_numeric($phone)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'رقم الهاتف غير صالح']);
        exit;
    }
    
    // Prepare SQL query
    $sql = "UPDATE researchuser SET email = ?, PhoneNumber = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $email, $phone, $user_id);
    
    // Execute query and check result
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث معلومات الاتصال بنجاح']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل تحديث معلومات الاتصال: ' . $stmt->error]);
    }
    
    $stmt->close();
}

// Function to update password
function updatePassword($conn, $user_id) {
    // Validate input
    if (!isset($_POST['currentPassword']) || !isset($_POST['newPassword'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'بيانات مفقودة']);
        exit;
    }
    
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    
    // Get current password from database
    $sql = "SELECT password FROM researchuser WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على المستخدم']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $storedPassword = $user['password'];
    
    // Verify current password
    if (!password_verify($currentPassword, $storedPassword)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة']);
        exit;
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password in database
    $sql = "UPDATE researchuser SET password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashedPassword, $user_id);
    
    // Execute query and check result
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل تحديث كلمة المرور: ' . $stmt->error]);
    }
    
    $stmt->close();
}

// Function to update profile image
function updateProfileImage($conn, $user_id) {
    // Check if file was uploaded
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'فشل تحميل الصورة']);
        exit;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = $_FILES['profile_image']['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم. يرجى تحميل صورة بصيغة JPEG أو PNG أو GIF']);
        exit;
    }
    
    // Validate file size (max 2MB)
    $maxSize = 2 * 1024 * 1024;
    if ($_FILES['profile_image']['size'] > $maxSize) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جدًا. الحد الأقصى هو 2 ميجابايت']);
        exit;
    }
    
    // Read file content
    $imageData = file_get_contents($_FILES['profile_image']['tmp_name']);
    
    // Prepare SQL query
    $sql = "UPDATE researchuser SET profile_image = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $null = NULL;
    $stmt->bind_param("bi", $null, $user_id);
    $stmt->send_long_data(0, $imageData);
    
    // Execute query and check result
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث الصورة الشخصية بنجاح']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'فشل تحديث الصورة الشخصية: ' . $stmt->error]);
    }
    
    $stmt->close();
}
?>