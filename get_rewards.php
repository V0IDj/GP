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

// Get status filter from query parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build SQL query based on filter
$sql = "SELECT r.*, ru.name as researcher_name, rs.title as research_title 
        FROM reward r 
        LEFT JOIN researchuser ru ON r.resercher_id = ru.user_id
        LEFT JOIN research rs ON r.research_id = rs.research_id";

if ($status_filter !== 'all') {
    $sql .= " WHERE r.status = ?";
}

$sql .= " ORDER BY r.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);

if ($status_filter !== 'all') {
    $stmt->bind_param("s", $status_filter);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch all rewards
$rewards = [];
while ($row = $result->fetch_assoc()) {
    // Don't include the actual file data in the list to keep response size small
    if (isset($row['files'])) {
        $row['has_files'] = !empty($row['files']);
        unset($row['files']);
    }
    
    $rewards[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($rewards);
?>