<?php
// Start or resume session
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('غير مصرح بالوصول. يرجى تسجيل الدخول أولاً.');
}

// Get user role
$userRole = $_SESSION['role'] ?? '';

// Check if service ID and file index are provided
if (!isset($_GET['id']) || !isset($_GET['file_index'])) {
    die('معلومات غير كافية لتحميل الملف');
}

$serviceId = (int)$_GET['id'];
$fileIndex = (int)$_GET['file_index'];
$role = isset($_GET['role']) ? $_GET['role'] : '';

// Get user ID from session
$userId = $_SESSION['user_id'];

try {
    // Determine access permissions based on role
    $accessAllowed = false;
    $stmt = null;
    
    if ($userRole === 'Admin') {
        // Admins can access any service file
        $accessAllowed = true;
        $stmt = $conn->prepare("SELECT service_type, files FROM servicetemp WHERE request_id = ?");
        $stmt->bind_param("i", $serviceId);
    } elseif ($userRole === 'Staff' && $role === 'staff') {
        // Staff can access files of services assigned to them
        $stmt = $conn->prepare("SELECT service_type, files FROM servicetemp WHERE request_id = ? AND assigned_staff_id = ?");
        $stmt->bind_param("ii", $serviceId, $userId);
    } else {
        // Regular users can only access their own service files
        $stmt = $conn->prepare("SELECT service_type, files FROM servicetemp WHERE request_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $serviceId, $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check if files exist
        if (empty($row['files'])) {
            die('لا توجد ملفات مرفقة لهذه الخدمة');
        }
        
        // Decode files from JSON
        $files = json_decode($row['files'], true);
        
        // If files is not an array (for legacy data), try to convert it
        if (!is_array($files)) {
            // Try to treat as a blob that needs to be saved first
            $tempFile = 'temp_' . uniqid() . '.pdf';
            file_put_contents($tempFile, $row['files']);
            $files = [$tempFile];
        }
        
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
        
        // Try to determine MIME type
        if (function_exists('mime_content_type')) {
            $fileType = mime_content_type($filePath);
        } else {
            // Default to application/octet-stream if unable to determine
            $fileType = 'application/octet-stream';
        }
        
        // Set headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $fileType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        // Clear output buffer if exists
        if (ob_get_level()) {
            ob_clean();
        }
        flush();
        
        // Output file
        readfile($filePath);
        
        // Delete temporary file if created
        if (strpos($filePath, 'temp_') === 0) {
            unlink($filePath);
        }
        
        exit;
    } else {
        die('الخدمة غير موجودة أو غير مصرح لك بالوصول إليها');
    }
} catch (Exception $e) {
    die('حدث خطأ أثناء تحميل الملف: ' . $e->getMessage());
}