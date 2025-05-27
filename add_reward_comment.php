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
$comments = isset($_POST['comments']) ? $_POST['comments'] : '';

// Validate input
if ($reward_id <= 0 || empty($comments)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // First, get the research_id for the reward
    $sql = "SELECT research_id FROM reward WHERE reward_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reward_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Reward not found');
    }
    
    $row = $result->fetch_assoc();
    $research_id = $row['research_id'];

    // Update admin comments
    $sql = "UPDATE reward SET admins_lastComments = ? WHERE reward_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $comments, $reward_id);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception('Failed to update comments');
    }

    // Determine comment type based on username
    $comment_type = "تعليق المشرف";
    if ($_SESSION['username'] === 'Fayez_99') {
        $comment_type = "تعليق نائب الرئيس الأكاديمي";
    } else if ($_SESSION['username'] === 'imad_99') {
        $comment_type = "تعليق الرئيس";
    }

    $action = $comment_type . ": " . $comments;

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