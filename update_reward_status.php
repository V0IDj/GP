<?php
// Start session if not already started
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Include database configuration
require_once 'config.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get POST data
$reward_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$field = isset($_POST['field']) ? $_POST['field'] : '';
$value = isset($_POST['value']) ? $_POST['value'] : '';
$comments = isset($_POST['comments']) ? $_POST['comments'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : null;

// Validate input
if ($reward_id <= 0 || empty($field) || empty($value)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Validate field name to prevent SQL injection
$allowed_fields = ['approved_admin', 'approved_vp_academic', 'approved_president'];
if (!in_array($field, $allowed_fields)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid field name']);
    exit;
}

// Check user permissions based on username and role
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$is_vp_academic = ($username === 'Fayez_99');
$is_president = ($username === 'imad_99');

// Check if user has permission to update the specified field
$has_permission = false;

if ($field === 'approved_admin' && $role === 'Admin' && !$is_vp_academic && !$is_president) {
    $has_permission = true;
} else if ($field === 'approved_vp_academic' && $is_vp_academic) {
    $has_permission = true;
} else if ($field === 'approved_president' && $is_president) {
    $has_permission = true;
}

if (!$has_permission) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'You do not have permission to update this field']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // First, get the current reward status
    $sql = "SELECT research_id, resercher_id FROM reward WHERE reward_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reward_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Reward not found');
    }
    
    $row = $result->fetch_assoc();
    $research_id = $row['research_id'];
    $researcher_id = $row['resercher_id'];

    // Update the approval field
    $sql = "UPDATE reward SET $field = ? WHERE reward_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $value, $reward_id);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('Failed to update approval status');
    }

    // Update status if provided
    if ($status !== null) {
        $sql = "UPDATE reward SET status = ? WHERE reward_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $reward_id);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception('Failed to update status');
        }
    }

    // Update admin comments if provided
    if (!empty($comments)) {
        $sql = "UPDATE reward SET admins_lastComments = ? WHERE reward_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $comments, $reward_id);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception('Failed to update comments');
        }
    }

    // Determine which approval level was updated for the history entry
    $action_type = "";
    switch ($field) {
        case 'approved_admin':
            $action_type = "مراجعة المشرف";
            break;
        case 'approved_vp_academic':
            $action_type = "مراجعة نائب الرئيس الأكاديمي";
            break;
        case 'approved_president':
            $action_type = "مراجعة الرئيس";
            break;
    }

    $action_status = ($value === 'Yes') ? "تمت الموافقة" : "تم الرفض";
    $action = $action_type . ": " . $action_status;
    
    if (!empty($comments)) {
        $action .= " - " . $comments;
    }

    // Add entry to research_history table
    $sql = "INSERT INTO research_history (research_id, user_id, user_name, action) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['username'];
    $stmt->bind_param("iiss", $research_id, $user_id, $user_name, $action);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('Failed to add history entry');
    }

    // If this is the president approving, check if all approvals are now complete
    if ($field === 'approved_president' && $value === 'Yes') {
        $sql = "UPDATE reward SET status = 'approved' WHERE reward_id = ? AND approved_admin = 'Yes' AND approved_vp_academic = 'Yes' AND approved_president = 'Yes'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reward_id);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>