<?php
// Start session to access user data
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not authenticated']);
    exit();
}

// Database connection
$host = "localhost";
$dbname = "gp"; // Adjust to your database name
$username = "root"; // Adjust as needed
$password = ""; // Adjust as needed

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get search term from query string
$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

if (strlen($searchTerm) < 3) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

try {
    // Search for research titles that match the search term
    // and belong to the current researcher
    $stmt = $conn->prepare("SELECT research_id as id, title 
FROM research 
JOIN researchuser ON research.user_id = researchuser.user_id
JOIN reward ON researchuser.user_id = reward.resercher_id
WHERE (title LIKE :searchTerm OR researchuser.name LIKE :searchTerm) 
AND research.user_id = :user_id 
ORDER BY title 
LIMIT 10;

");
                           
    $searchPattern = '%' . $searchTerm . '%';
    $stmt->bindParam(':searchTerm', $searchPattern);
    $stmt->bindParam(':researcher_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($results);
    
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    exit();
}
?>