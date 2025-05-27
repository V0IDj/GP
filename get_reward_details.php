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

// Get reward ID from query parameter
$reward_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reward_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid reward ID']);
    exit;
}

// Fetch reward details
$sql = "SELECT r.*, ru.name as researcher_name, rs.title as research_title 
        FROM reward r 
        LEFT JOIN researchuser ru ON r.resercher_id = ru.user_id
        LEFT JOIN research rs ON r.research_id = rs.research_id
        WHERE r.reward_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reward_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Reward not found']);
    exit;
}

$reward = $result->fetch_assoc();

// Check if files exist
if (isset($reward['files']) && !empty($reward['files'])) {
    $reward['has_files'] = true;
    // Remove the actual binary data to reduce response size
    unset($reward['files']);
} else {
    $reward['has_files'] = false;
}

// Get comments for this reward
$sql = "SELECT * FROM research_history 
        WHERE research_id = ? AND action LIKE '%reward%' 
        ORDER BY timestamp DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reward['research_id']);
$stmt->execute();
$comment_result = $stmt->get_result();

$comments = [];
while ($row = $comment_result->fetch_assoc()) {
    $comments[] = $row;
}

$reward['comments'] = $comments;

// Return JSON response
header('Content-Type: application/json');
echo json_encode($reward);
?>