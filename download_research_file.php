<?php
// Start or resume session
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('غير مصرح بالوصول. يرجى تسجيل الدخول أولاً.');
}

// Check if research ID is provided
if (!isset($_GET['id'])) {
    die('معلومات غير كافية لتحميل الملف');
}

$researchId = (int)$_GET['id'];
$role = isset($_GET['role']) ? $_GET['role'] : '';

// Get user ID and role from session
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

try {
    // Determine access permissions based on role
    $accessAllowed = false;
    $stmt = null;
    
    if ($userRole === 'Admin') {
        // Admins can access any research file
        $accessAllowed = true;
        $stmt = $conn->prepare("SELECT title, files FROM submitsresearch WHERE id = ?");
        $stmt->bind_param("i", $researchId);
    } elseif ($userRole === 'Staff' || $userRole === 'Researcher') {
        // Check if this staff/researcher is assigned as a reviewer for this research
        $reviewerStmt = $conn->prepare("SELECT id FROM research_reviewers WHERE research_id = ? AND reviewer_id = ?");
        $reviewerStmt->bind_param("ii", $researchId, $userId);
        $reviewerStmt->execute();
        $reviewerResult = $reviewerStmt->get_result();
        
        if ($reviewerResult->num_rows > 0) {
            // This staff/researcher is a reviewer for this research
            $stmt = $conn->prepare("SELECT title, files FROM submitsresearch WHERE id = ?");
            $stmt->bind_param("i", $researchId);
        } else {
            // Check if this is the researcher who submitted the research
            $stmt = $conn->prepare("SELECT title, files FROM submitsresearch WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $researchId, $userId);
        }
    } else {
        // Regular users can only access their own research files
        $stmt = $conn->prepare("SELECT title, files FROM submitsresearch WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $researchId, $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check if files exist
        if (empty($row['files'])) {
            die('لا توجد ملفات مرفقة لهذا البحث');
        }
        
        // Get a temporary file path
        $tempDir = 'temp_downloads/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        $tempFilePath = $tempDir . uniqid() . '_research_file.pdf';
        
        // Write blob to file
        file_put_contents($tempFilePath, $row['files']);
        
        // Get file size
        $fileSize = filesize($tempFilePath);
        
        // Set headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $row['title']) . '.pdf"');
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
        readfile($tempFilePath);
        
        // Delete temporary file
        unlink($tempFilePath);
        
        exit;
    } else {
        die('البحث غير موجود أو غير مصرح لك بالوصول إليه');
    }
} catch (Exception $e) {
    die('حدث خطأ أثناء تحميل الملف: ' . $e->getMessage());
}