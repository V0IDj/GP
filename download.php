<?php
// Start or resume session
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('غير مصرح بالوصول. يرجى تسجيل الدخول أولاً.');
}

// Get current user ID
$userId = $_SESSION['user_id'];

// Check if file type and ID are provided
if (!isset($_GET['type']) || !isset($_GET['id'])) {
    die('معلومات غير كافية لتحميل الملف');
}

$type = $_GET['type'];
$id = (int)$_GET['id'];

// Process the file download based on type
switch ($type) {
    case 'research':
        downloadResearchFile($userId, $id);
        break;
        
    case 'reward':
        downloadRewardFile($userId, $id);
        break;
        
    case 'service':
        downloadServiceFile($userId, $id);
        break;
        
    case 'research_submission':
        downloadResearchSubmissionFile($userId, $id);
        break;
        
    default:
        die('نوع ملف غير معروف');
}

// Function to download research file
function downloadResearchFile($userId, $researchId) {
    global $conn;
    
    try {
        // Make sure the file belongs to the current user
        $stmt = $conn->prepare("SELECT files, title FROM research WHERE research_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $researchId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (empty($row['files'])) {
                die('الملف غير موجود');
            }
            
            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="research_' . sanitizeFilename($row['title']) . '.pdf"');
            header('Content-Length: ' . strlen($row['files']));
            header('Cache-Control: no-cache');
            
            // Output the file data
            echo $row['files'];
            exit;
        } else {
            die('غير مصرح بتحميل هذا الملف');
        }
    } catch (Exception $e) {
        die('حدث خطأ أثناء تحميل الملف: ' . $e->getMessage());
    }
}

// Function to download reward file
function downloadRewardFile($userId, $rewardId) {
    global $conn;
    
    try {
        // Make sure the file belongs to the current user
        $stmt = $conn->prepare("SELECT files FROM reward WHERE reward_id = ? AND resercher_id = ?");
        $stmt->bind_param("ii", $rewardId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (empty($row['files'])) {
                die('الملف غير موجود');
            }
            
            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="reward_' . $rewardId . '.pdf"');
            header('Content-Length: ' . strlen($row['files']));
            header('Cache-Control: no-cache');
            
            // Output the file data
            echo $row['files'];
            exit;
        } else {
            die('غير مصرح بتحميل هذا الملف');
        }
    } catch (Exception $e) {
        die('حدث خطأ أثناء تحميل الملف: ' . $e->getMessage());
    }
}

// Function to download service file
function downloadServiceFile($userId, $requestId) {
    global $conn;
    
    try {
        // Make sure the file belongs to the current user
        $stmt = $conn->prepare("SELECT files, service_type FROM servicetemp WHERE request_id = ? AND user_id = ?");
        $stmt->bind_param("is", $requestId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (empty($row['files'])) {
                die('الملف غير موجود');
            }
            
            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="service_' . sanitizeFilename($row['service_type']) . '_' . $requestId . '.pdf"');
            header('Content-Length: ' . strlen($row['files']));
            header('Cache-Control: no-cache');
            
            // Output the file data
            echo $row['files'];
            exit;
        } else {
            die('غير مصرح بتحميل هذا الملف');
        }
    } catch (Exception $e) {
        die('حدث خطأ أثناء تحميل الملف: ' . $e->getMessage());
    }
}

// Function to download research submission file
function downloadResearchSubmissionFile($userId, $submissionId) {
    global $conn;
    
    try {
        // Make sure the file belongs to the current user
        $stmt = $conn->prepare("SELECT files, title FROM submitsresearch WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $submissionId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (empty($row['files'])) {
                die('الملف غير موجود');
            }
            
            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="submission_' . sanitizeFilename($row['title']) . '.pdf"');
            header('Content-Length: ' . strlen($row['files']));
            header('Cache-Control: no-cache');
            
            // Output the file data
            echo $row['files'];
            exit;
        } else {
            die('غير مصرح بتحميل هذا الملف');
        }
    } catch (Exception $e) {
        die('حدث خطأ أثناء تحميل الملف: ' . $e->getMessage());
    }
}

// Helper function to sanitize filenames
function sanitizeFilename($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Replace special characters
    $filename = preg_replace('/[^\w\._]+/', '_', $filename);
    
    // Limit length
    if (strlen($filename) > 100) {
        $filename = substr($filename, 0, 96) . '...';
    }
    
    return $filename;
}