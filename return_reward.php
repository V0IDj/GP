<?php
// Start session if not already started
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database configuration
require_once 'config.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check required parameters
if (!isset($_POST['id']) || !isset($_POST['status']) || !isset($_POST['return_target']) || !isset($_POST['comments'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get request parameters
$rewardId = $_POST['id'];
$status = $_POST['status'];
$returnTarget = $_POST['return_target'];
$comments = $_POST['comments'];

// Get user info
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$userRole = $_SESSION['role'];

// Create database connection if not already set
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli("localhost", "root", "", "gp"); // Update credentials if different
    
    // Check connection
    if ($conn->connect_error) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $conn->connect_error]);
        exit;
    }
}

// Check if reward exists - USING THE CORRECT TABLE NAME 'reward' (not 'rewards')
$stmt = $conn->prepare("SELECT * FROM reward WHERE reward_id = ?");
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $rewardId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Reward not found']);
    $stmt->close();
    exit;
}

// Create reward_history table if it doesn't exist
$checkHistoryTable = $conn->query("SHOW TABLES LIKE 'reward_history'");
if ($checkHistoryTable->num_rows == 0) {
    $createHistoryTable = "CREATE TABLE reward_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reward_id INT NOT NULL,
        user_id INT NOT NULL,
        user_name VARCHAR(255) NOT NULL,
        action TEXT NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($createHistoryTable)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to create history table: ' . $conn->error]);
        exit;
    }
}

// Begin transaction
$conn->begin_transaction();

try {
    // First, check if return_target column exists in reward table
    $checkColumn = $conn->query("SHOW COLUMNS FROM reward LIKE 'return_target'");
    
    // If return_target column doesn't exist, add it
    if ($checkColumn->num_rows == 0) {
        $conn->query("ALTER TABLE reward ADD COLUMN return_target VARCHAR(50) DEFAULT NULL");
    }
    
    // Update the status enum to include 'returned' if it doesn't already
    $checkEnum = $conn->query("SHOW COLUMNS FROM reward WHERE Field = 'status'");
    $enumRow = $checkEnum->fetch_assoc();
    
    if (!strpos($enumRow['Type'], 'returned')) {
        $enumValues = $enumRow['Type'];
        $newEnum = str_replace("'rejected'", "'rejected','returned'", $enumValues);
        $conn->query("ALTER TABLE reward MODIFY COLUMN status $newEnum DEFAULT 'wait'");
    }
    
    // Update reward status and approval fields based on return target
    $sql = "UPDATE reward SET status = ?, return_target = ?, admins_lastComments = ?";
    
    // Reset approval fields based on return target
    if ($returnTarget === 'user') {
        $sql .= ", approved_admin = 'No', approved_vp_academic = 'No', approved_president = 'No'";
    } else if ($returnTarget === 'admin') {
        $sql .= ", approved_admin = 'No', approved_vp_academic = 'No', approved_president = 'No'";
    } else if ($returnTarget === 'vp') {
        $sql .= ", approved_vp_academic = 'No', approved_president = 'No'";
    }
    
    $sql .= " WHERE reward_id = ?";
    
    $updateStmt = $conn->prepare($sql);
    if (!$updateStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $updateStmt->bind_param("sssi", $status, $returnTarget, $comments, $rewardId);
    if (!$updateStmt->execute()) {
        throw new Exception("Execute failed: " . $updateStmt->error);
    }
    
    $updateStmt->close();
    
    // Add entry to reward_history table
    $actionDetails = "Return for Edit to " . ucfirst($returnTarget) . ": " . $comments;
    
    $historyStmt = $conn->prepare("INSERT INTO reward_history (reward_id, user_id, user_name, action) VALUES (?, ?, ?, ?)");
    if (!$historyStmt) {
        throw new Exception("Prepare failed for history: " . $conn->error);
    }
    
    $historyStmt->bind_param("iiss", $rewardId, $userId, $username, $actionDetails);
    if (!$historyStmt->execute()) {
        throw new Exception("Execute failed for history: " . $historyStmt->error);
    }
    
    $historyStmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error updating reward: ' . $e->getMessage()]);
} finally {
    // Close connection if created in this script
    if (isset($conn) && !isset($GLOBALS['conn'])) {
        $conn->close();
    }
}
?>