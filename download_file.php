<?php
// Start session if not already started
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Include database configuration
require_once 'config.php';

// Get reward ID from query parameter
$reward_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reward_id <= 0) {
    echo "Invalid reward ID";
    exit;
}

// Fetch file data from database
$sql = "SELECT files FROM reward WHERE reward_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reward_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo "Reward not found";
    exit;
}

$stmt->bind_result($file_data);
$stmt->fetch();

// Check if file data exists
if (empty($file_data)) {
    echo "No files found for this reward";
    exit;
}

// Generate a filename
$filename = "reward_files_" . $reward_id . ".zip";

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($file_data));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output file data
echo $file_data;
exit;
?>