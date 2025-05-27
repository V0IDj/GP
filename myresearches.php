<?php
// Start or resume session
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'غير مصرح بالوصول. يرجى تسجيل الدخول أولاً.');
    exit;
}

// Get current user ID
$userId = $_SESSION['user_id'];

// Process the requested action
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'getUserInfo':
        getUserInfo($userId);
        break;
        
    case 'getCompletedResearch':
        getCompletedResearch($userId);
        break;
        
    case 'getRewardStatus':
        getRewardStatus($userId);
        break;
        
    case 'getServiceStatus':
        getServiceStatus($userId);
        break;
        
    case 'getResearchStatus':
        getResearchStatus($userId);
        break;
        
    default:
        sendResponse(false, 'إجراء غير معروف');
}

// Function to get user information
function getUserInfo($userId) {
    global $conn;
    
    try {
        // Assuming you have a users table
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            sendResponse(true, 'تم الحصول على بيانات المستخدم بنجاح', [
                'userName' => $row['name']
            ]);
        } else {
            sendResponse(false, 'لم يتم العثور على بيانات المستخدم');
        }
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات المستخدم: ' . $e->getMessage());
    }
}

// Function to get completed research
function getCompletedResearch($userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT research_id, title, research_type, doi, publish_date, user_id FROM research WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $research = [];
        while ($row = $result->fetch_assoc()) {
            // Check if files exist for this research
            $fileCheckStmt = $conn->prepare("SELECT 1 FROM research WHERE research_id = ? AND files IS NOT NULL");
            $fileCheckStmt->bind_param("i", $row['research_id']);
            $fileCheckStmt->execute();
            $fileCheckResult = $fileCheckStmt->get_result();
            $row['has_files'] = $fileCheckResult->num_rows > 0;
            
            $research[] = $row;
        }
        
        sendResponse(true, 'تم الحصول على بيانات الأبحاث بنجاح', [
            'research' => $research
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات الأبحاث: ' . $e->getMessage());
    }
}

// Function to get reward status
function getRewardStatus($userId) {
    global $conn;
    
    try {
        // Join with research table to get research titles
        $query = "SELECT r.reward_id, r.cost, r.approved_admin, r.approved_vp_academic, 
                        r.approved_president, r.user_comments, r.research_id, r.resercher_id, 
                        r.admins_lastComments, r.status, rs.title as research_title 
                  FROM reward r 
                  LEFT JOIN research rs ON r.research_id = rs.research_id 
                  WHERE r.resercher_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rewards = [];
        while ($row = $result->fetch_assoc()) {
            // Check if files exist for this reward
            $fileCheckStmt = $conn->prepare("SELECT 1 FROM reward WHERE reward_id = ? AND files IS NOT NULL");
            $fileCheckStmt->bind_param("i", $row['reward_id']);
            $fileCheckStmt->execute();
            $fileCheckResult = $fileCheckStmt->get_result();
            $row['has_files'] = $fileCheckResult->num_rows > 0;
            
            $rewards[] = $row;
        }
        
        sendResponse(true, 'تم الحصول على بيانات المكافآت بنجاح', [
            'rewards' => $rewards
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات المكافآت: ' . $e->getMessage());
    }
}

// Function to get service status
function getServiceStatus($userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT request_id, service_type, notes, created_at, status FROM servicetemp WHERE user_id = ?");
        $stmt->bind_param("s", $userId); // Note: user_id is varchar in this table
        $stmt->execute();
        $result = $stmt->get_result();
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            // Check if files exist for this service
            $fileCheckStmt = $conn->prepare("SELECT 1 FROM servicetemp WHERE request_id = ? AND files IS NOT NULL");
            $fileCheckStmt->bind_param("i", $row['request_id']);
            $fileCheckStmt->execute();
            $fileCheckResult = $fileCheckStmt->get_result();
            $row['has_files'] = $fileCheckResult->num_rows > 0;
            
            $services[] = $row;
        }
        
        sendResponse(true, 'تم الحصول على بيانات الخدمات بنجاح', [
            'services' => $services
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات الخدمات: ' . $e->getMessage());
    }
}

// Function to get research status
function getResearchStatus($userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id, classification, where_to_publish, college, title, 
                                 user_notes, admin_notes, submission_date, status 
                                 FROM submitsresearch WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $research = [];
        while ($row = $result->fetch_assoc()) {
            // Check if files exist for this submission
            $fileCheckStmt = $conn->prepare("SELECT 1 FROM submitsresearch WHERE id = ? AND files IS NOT NULL");
            $fileCheckStmt->bind_param("i", $row['id']);
            $fileCheckStmt->execute();
            $fileCheckResult = $fileCheckStmt->get_result();
            $row['has_files'] = $fileCheckResult->num_rows > 0;
            
            $research[] = $row;
        }
        
        sendResponse(true, 'تم الحصول على بيانات حالة الأبحاث بنجاح', [
            'research' => $research
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات حالة الأبحاث: ' . $e->getMessage());
    }
}

// Helper function to send JSON response
function sendResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}