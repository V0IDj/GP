<?php
// Start or resume session
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('غير مصرح بالوصول. يرجى تسجيل الدخول أولاً.');
}

// Check if service ID and file index are provided
if (!isset($_GET['id']) || !isset($_GET['file_index'])) {
    die('معلومات غير كافية لتحميل الملف');
}

$serviceId = (int)$_GET['id'];
$fileIndex = (int)$_GET['file_index'];
$role = isset($_GET['role']) ? $_GET['role'] : '';

// Get user ID from session
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

try {
    // Check service access based on role
    $accessQuery = "";
    $params = [];
    $types = "";
    
    if ($role === 'staff' && $userRole === 'Staff') {
        // Staff can access files of services assigned to them
        $accessQuery = "SELECT service_type, completed_files FROM servicetemp WHERE request_id = ? AND assigned_staff_id = ?";
        $params = [$serviceId, $userId];
        $types = "ii";
    } elseif ($userRole === 'Admin') {
        // Admin can access all services' files
        $accessQuery = "SELECT service_type, completed_files FROM servicetemp WHERE request_id = ?";
        $params = [$serviceId];
        $types = "i";
    } else {
        // Regular users can only access files of their own services
        $accessQuery = "SELECT service_type, completed_files FROM servicetemp WHERE request_id = ? AND user_id = ?";
        $params = [$serviceId, $userId];
        $types = "ii";
    }
    
    $stmt = $conn->prepare($accessQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check if completed files exist
        if (empty($row['completed_files'])) {
            die('لا توجد ملفات مكتملة لهذه الخدمة');
        }
        
        // Decode files from JSON
        $files = json_decode($row['completed_files'], true);
        
        // Check if file index exists
        if (!isset($files[$fileIndex])) {
            die('الملف غير موجود');
        }
        
        $filePath = $files[$fileIndex];
        
        // Check if file exists
        if (!file_exists($filePath)) {
            die('الملف غير موجود في النظام');
        }
        
        // Get file information
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);
        $fileType = mime_content_type($filePath);
        
        // Set headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $fileType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        // Clear output buffer
        ob_clean();
        flush();
        
        // Output file
        readfile($filePath);
        exit;
    } else {
        die('الخدمة غير موجودة أو لا يمكنك الوصول إليها');
    }
} catch (Exception $e) {
    die('حدث خطأ أثناء تحميل الملف: ' . $e->getMessage());
}