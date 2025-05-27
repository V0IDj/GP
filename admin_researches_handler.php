<?php
// Start or resume session
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    sendResponse(false, 'غير مصرح بالوصول. يجب أن تكون مديراً للوصول إلى هذه الصفحة.');
    exit;
}

// Get admin ID from session
$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['username'] ?? 'المدير';

// Process GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'getAdminInfo':
            getAdminInfo($adminId, $adminName);
            break;
        
        case 'getResearches':
            // Add a test parameter to bypass database and return test data
            if (isset($_GET['test']) && $_GET['test'] === '1') {
                getTestResearches();
            } else {
                getResearches();
            }
            break;
        
        case 'getResearchDetails':
            if (isset($_GET['id'])) {
                getResearchDetails($_GET['id']);
            } else {
                sendResponse(false, 'معرّف البحث مطلوب');
            }
            break;
        
        case 'getReviewers':
            getReviewers();
            break;
        
        default:
            sendResponse(false, 'إجراء غير معروف');
            break;
    }
}

// Test function to return mock data without database
function getTestResearches() {
    // Create some mock data
    $mockResearches = [
        [
            'id' => 1,
            'user_id' => 1,
            'title' => 'بحث تجريبي 1',
            'college' => 'كلية العلوم',
            'classification' => 'Q1',
            'where_to_publish' => 'Journal',
            'submission_date' => '2025-01-01 12:00:00',
            'status' => 'Pending 1',
            'r_type' => 'theoretical',
            'is_shared' => 0,
            'researcher_name' => 'باحث تجريبي',
            'has_files' => true,
            'has_participation' => false
        ],
        [
            'id' => 2,
            'user_id' => 2,
            'title' => 'بحث تجريبي 2',
            'college' => 'كلية الهندسة',
            'classification' => 'Q2',
            'where_to_publish' => 'Conference',
            'submission_date' => '2025-01-02 12:00:00',
            'status' => 'Pending 2',
            'r_type' => 'practical',
            'is_shared' => 1,
            'researcher_name' => 'باحث تجريبي آخر',
            'has_files' => true,
            'has_participation' => true
        ],
        [
            'id' => 3,
            'user_id' => 3,
            'title' => 'بحث تجريبي 3',
            'college' => 'كلية الطب',
            'classification' => 'Q3',
            'where_to_publish' => 'Book Chapter',
            'submission_date' => '2025-01-03 12:00:00',
            'status' => 'Approved',
            'r_type' => 'theoretical',
            'is_shared' => 0,
            'researcher_name' => 'باحث تجريبي ثالث',
            'has_files' => true,
            'has_participation' => false
        ]
    ];
    
    // Send mock data
    sendResponse(true, 'تم الحصول على بيانات تجريبية', [
        'researches' => $mockResearches,
        'pagination' => [
            'current_page' => 1,
            'per_page' => 10,
            'total' => 3,
            'total_pages' => 1
        ]
    ]);
}

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'assignReviewers':
            if (isset($_POST['research_id']) && isset($_POST['reviewer_ids'])) {
                $reviewerIds = json_decode($_POST['reviewer_ids'], true);
                assignReviewers($_POST['research_id'], $reviewerIds, $adminId, $adminName);
            } else {
                sendResponse(false, 'معرّف البحث ومعرّفات المراجعين مطلوبة');
            }
            break;
        
        case 'updateStatus':
            if (isset($_POST['research_id']) && isset($_POST['status'])) {
                $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
                updateResearchStatus($_POST['research_id'], $_POST['status'], $reason, $adminId, $adminName);
            } else {
                sendResponse(false, 'معرّف البحث والحالة مطلوبان');
            }
            break;
        
        default:
            sendResponse(false, 'إجراء غير معروف');
            break;
    }
}

// Function to get admin information
function getAdminInfo($adminId, $adminName) {
    global $conn;
    
    try {
        // Get admin details from database
        $stmt = $conn->prepare("SELECT name FROM researchuser WHERE user_id = ? AND role = 'Admin'");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            sendResponse(true, 'تم الحصول على بيانات المدير بنجاح', [
                'adminName' => $row['name']
            ]);
        } else {
            // Fallback to session username
            sendResponse(true, 'تم الحصول على بيانات المدير بنجاح', [
                'adminName' => $adminName
            ]);
        }
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات المدير: ' . $e->getMessage());
    }
}

// Function to get researches with pagination and filters
// Function to get researches with pagination and filters
function getResearches() {
    global $conn;
    
    try {
        // First check if submitsresearch table exists
        $tableCheckQuery = "SHOW TABLES LIKE 'submitsresearch'";
        $tableCheckResult = $conn->query($tableCheckQuery);
        
        if ($tableCheckResult->num_rows === 0) {
            sendResponse(false, 'جدول طلبات الأبحاث غير موجود في قاعدة البيانات');
            return;
        }
        
        // Get current page
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = 10; // Researches per page
        $offset = ($page - 1) * $per_page;
        
        // Get filter parameters
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        $researchTypeFilter = isset($_GET['r_type']) ? $_GET['r_type'] : '';
        $publishFilter = isset($_GET['publish_type']) ? $_GET['publish_type'] : '';
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Start building the query
        $query = "SELECT * FROM submitsresearch WHERE 1=1";
        $countQuery = "SELECT COUNT(*) as total FROM submitsresearch WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Add filters to the query
        if (!empty($statusFilter)) {
            $query .= " AND status = ?";
            $countQuery .= " AND status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }
        
        if (!empty($researchTypeFilter)) {
            $query .= " AND r_type = ?";
            $countQuery .= " AND r_type = ?";
            $params[] = $researchTypeFilter;
            $types .= "s";
        }
        
        if (!empty($publishFilter)) {
            $query .= " AND where_to_publish = ?";
            $countQuery .= " AND where_to_publish = ?";
            $params[] = $publishFilter;
            $types .= "s";
        }
        
        if (!empty($searchTerm)) {
            $query .= " AND (title LIKE ? OR college LIKE ?)";
            $countQuery .= " AND (title LIKE ? OR college LIKE ?)";
            $searchParam = "%" . $searchTerm . "%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }
        
        // Add sorting
        $query .= " ORDER BY submission_date DESC LIMIT ?, ?";
        
        // Add pagination parameters
        $params[] = $offset;
        $params[] = $per_page;
        $types .= "ii";
        
        // Get total count
        $countStmt = $conn->prepare($countQuery);
        
        if ($types !== "" && count($params) > 0) {
            // Only bind parameters if we have filters
            // Remove the last two types (ii) since count query doesn't have pagination
            $countParamTypes = substr($types, 0, -2);
            $countParams = array_slice($params, 0, count($params) - 2);
            
            if (!empty($countParamTypes)) {
                // Create references for bind_param
                $countBindParams = [$countParamTypes];
                foreach ($countParams as $key => $value) {
                    $countBindParams[] = &$countParams[$key];
                }
                
                call_user_func_array([$countStmt, 'bind_param'], $countBindParams);
            }
        }
        
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRow = $countResult->fetch_assoc();
        $total = $totalRow['total'];
        
        // Calculate pagination info
        $total_pages = ceil($total / $per_page);
        
        // Prepare and execute the main query
        $stmt = $conn->prepare($query);
        
        if ($types !== "" && count($params) > 0) {
            // Create references for bind_param
            $bindParams = [$types];
            foreach ($params as $key => $value) {
                $bindParams[] = &$params[$key];
            }
            
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $researches = [];
        while ($row = $result->fetch_assoc()) {
            // Remove large blob data
            unset($row['files']);
            
            // Mark if files exist
            $row['has_files'] = true;
            
            // Get researcher name
            $userStmt = $conn->prepare("SELECT name FROM researchuser WHERE user_id = ?");
            $userStmt->bind_param("i", $row['user_id']);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            if ($userRow = $userResult->fetch_assoc()) {
                $row['researcher_name'] = $userRow['name'];
            } else {
                $row['researcher_name'] = 'غير معروف';
            }
            
            $researches[] = $row;
        }
        
        sendResponse(true, 'تم الحصول على بيانات الأبحاث بنجاح', [
            'researches' => $researches,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages
            ]
        ]);
    } catch (Exception $e) {
        error_log('Error in getResearches: ' . $e->getMessage());
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات الأبحاث: ' . $e->getMessage());
    }
}

// Function to get research details
// Function to get research details
function getResearchDetails($researchId) {
    global $conn;
    
    try {
        // Basic error checking for database connection
        if (!$conn || $conn->connect_error) {
            throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection is null"));
        }
        
        // First, check if the research exists and get basic info with simplified query
        $query = "SELECT * FROM submitsresearch WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $researchId);
        
        if (!$stmt->execute()) {
            throw new Exception("Statement execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if (!$result) {
            throw new Exception("Result set creation failed: " . $stmt->error);
        }
        
        if ($row = $result->fetch_assoc()) {
            // Get researcher name separately
            $researcherId = $row['user_id'];
            $researcherName = "غير معروف";
            $researcherCollege = "";
            
            if ($researcherId) {
                $userQuery = "SELECT name, college FROM researchuser WHERE user_id = ?";
                $userStmt = $conn->prepare($userQuery);
                
                if ($userStmt) {
                    $userStmt->bind_param("i", $researcherId);
                    $userStmt->execute();
                    $userResult = $userStmt->get_result();
                    
                    if ($userRow = $userResult->fetch_assoc()) {
                        $researcherName = $userRow['name'];
                        $researcherCollege = $userRow['college'];
                    }
                }
            }
            
            // Add researcher info to the row
            $row['researcher_name'] = $researcherName;
            $row['researcher_college'] = $researcherCollege;
            
            // Check if files exist
            $row['has_files'] = !empty($row['files']);
            unset($row['files']); // Remove the actual file data to reduce response size
            
            // Simplify participants handling
            $participants = [];
            try {
                // Get participation data if any
                $participationQuery = "SELECT rp.*, r.name FROM researchparticipation rp 
                                      LEFT JOIN researchuser r ON rp.participate1 = r.user_id 
                                      WHERE rp.submits_research_id = ?";
                $participationStmt = $conn->prepare($participationQuery);
                
                if ($participationStmt) {
                    $participationStmt->bind_param("i", $researchId);
                    $participationStmt->execute();
                    $participationResult = $participationStmt->get_result();
                    
                    if ($participationRow = $participationResult->fetch_assoc()) {
                        // Get participant names
                        for ($i = 1; $i <= 5; $i++) {
                            $participantId = $participationRow['participate' . $i];
                            if ($participantId) {
                                $participantQuery = "SELECT name FROM researchuser WHERE user_id = ?";
                                $participantStmt = $conn->prepare($participantQuery);
                                if ($participantStmt) {
                                    $participantStmt->bind_param("i", $participantId);
                                    $participantStmt->execute();
                                    $participantResult = $participantStmt->get_result();
                                    if ($participantRow = $participantResult->fetch_assoc()) {
                                        $participants[] = [
                                            'id' => $participantId,
                                            'name' => $participantRow['name']
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Just log participation error but continue
                error_log("Error getting participants: " . $e->getMessage());
            }
            
            $row['participants'] = $participants;
            
            // Simplify reviewers handling
            $reviewers = [];
            try {
                // Check if research_reviewers table exists
                $tableCheckQuery = "SHOW TABLES LIKE 'research_reviewers'";
                $tableCheckResult = $conn->query($tableCheckQuery);
                
                if ($tableCheckResult && $tableCheckResult->num_rows > 0) {
                    $reviewersQuery = "SELECT rr.*, ru.name as reviewer_name, ru.role as reviewer_role, 
                                      ru.specialty, ru.college 
                                      FROM research_reviewers rr 
                                      LEFT JOIN researchuser ru ON rr.reviewer_id = ru.user_id 
                                      WHERE rr.research_id = ?";
                    $reviewersStmt = $conn->prepare($reviewersQuery);
                    
                    if ($reviewersStmt) {
                        $reviewersStmt->bind_param("i", $researchId);
                        $reviewersStmt->execute();
                        $reviewersResult = $reviewersStmt->get_result();
                        
                        while ($reviewerRow = $reviewersResult->fetch_assoc()) {
                            $reviewers[] = $reviewerRow;
                        }
                    }
                }
            } catch (Exception $e) {
                // Just log reviewers error but continue
                error_log("Error getting reviewers: " . $e->getMessage());
            }
            
            $row['reviewers'] = $reviewers;
            
            // Simplify history handling
            $history = [];
            try {
                // Check if research_history table exists
                $tableCheckQuery = "SHOW TABLES LIKE 'research_history'";
                $tableCheckResult = $conn->query($tableCheckQuery);
                
                if ($tableCheckResult && $tableCheckResult->num_rows > 0) {
                    $historyQuery = "SELECT * FROM research_history WHERE research_id = ? ORDER BY timestamp DESC";
                    $historyStmt = $conn->prepare($historyQuery);
                    
                    if ($historyStmt) {
                        $historyStmt->bind_param("i", $researchId);
                        $historyStmt->execute();
                        $historyResult = $historyStmt->get_result();
                        
                        while ($historyRow = $historyResult->fetch_assoc()) {
                            $history[] = $historyRow;
                        }
                    }
                }
            } catch (Exception $e) {
                // Just log history error but continue
                error_log("Error getting history: " . $e->getMessage());
            }
            
            $row['status_history'] = $history;
            
            sendResponse(true, 'تم الحصول على بيانات البحث بنجاح', [
                'research' => $row
            ]);
        } else {
            sendResponse(false, 'البحث غير موجود في جدول طلبات الأبحاث');
        }
    } catch (Exception $e) {
        error_log("Error in getResearchDetails: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات البحث: ' . $e->getMessage());
    }
}
// Function to get reviewers list (only staff role users with specialty)
function getReviewers() {
    global $conn;
    
    try {
        // Get only Staff role users who have a specialty
        $stmt = $conn->prepare("SELECT user_id, username, name, role, college, specialty 
                               FROM researchuser 
                               WHERE role = 'Staff' 
                               AND specialty IS NOT NULL AND specialty != ''
                               ORDER BY name");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviewers = [];
        while ($row = $result->fetch_assoc()) {
            $reviewers[] = $row;
        }
        
        sendResponse(true, 'تم الحصول على قائمة المراجعين بنجاح', [
            'reviewers' => $reviewers
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع قائمة المراجعين: ' . $e->getMessage());
    }
}

// Function to assign reviewers to a research
function assignReviewers($researchId, $reviewerIds, $adminId, $adminName) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if research exists and is in Pending 1 status
        $checkStmt = $conn->prepare("SELECT status FROM submitsresearch WHERE id = ?");
        $checkStmt->bind_param("i", $researchId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($row = $checkResult->fetch_assoc()) {
            if ($row['status'] !== 'Pending 1') {
                $conn->rollback();
                sendResponse(false, 'لا يمكن تعيين مراجعين لهذا البحث في حالته الحالية');
                return;
            }
        } else {
            $conn->rollback();
            sendResponse(false, 'البحث غير موجود');
            return;
        }
        
        // Update research status to Pending 2
        $updateStmt = $conn->prepare("UPDATE submitsresearch SET status = 'Pending 2' WHERE id = ?");
        $updateStmt->bind_param("i", $researchId);
        
        if (!$updateStmt->execute()) {
            $conn->rollback();
            sendResponse(false, 'فشل في تحديث حالة البحث: ' . $conn->error);
            return;
        }
        
        // Create research_history table if it doesn't exist
        $createTableQuery = "CREATE TABLE IF NOT EXISTS research_history (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            research_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            action TEXT NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT current_timestamp(),
            INDEX (research_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->query($createTableQuery);
        
        // Create history entry
        $historyQuery = "INSERT INTO research_history (research_id, user_id, user_name, action, timestamp) VALUES (?, ?, ?, ?, NOW())";
        $action = "تم تغيير حالة البحث إلى 'بانتظار المراجعة النهائية' وتعيين مراجعين";
        $historyStmt = $conn->prepare($historyQuery);
        $historyStmt->bind_param("iiss", $researchId, $adminId, $adminName, $action);
        $historyStmt->execute();
        
        // Check if research_reviewers table exists, if not create it
        $tableExistsStmt = $conn->prepare("SHOW TABLES LIKE 'research_reviewers'");
        $tableExistsStmt->execute();
        $tableExistsResult = $tableExistsStmt->get_result();
        
        if ($tableExistsResult->num_rows == 0) {
            // Create the table with proper collation and engine matching the database
            $createReviewersTableQuery = "CREATE TABLE research_reviewers (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                research_id INT(11) NOT NULL,
                reviewer_id INT(11) NOT NULL,
                assigned_by INT(11) NOT NULL,
                assigned_date TIMESTAMP NOT NULL DEFAULT current_timestamp(),
                status ENUM('pending', 'reviewed') DEFAULT 'pending',
                comments TEXT,
                INDEX (research_id),
                INDEX (reviewer_id),
                CONSTRAINT fk_research_reviewers_research FOREIGN KEY (research_id) REFERENCES submitsresearch(id) ON DELETE CASCADE,
                CONSTRAINT fk_research_reviewers_reviewer FOREIGN KEY (reviewer_id) REFERENCES researchuser(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_research_reviewers_admin FOREIGN KEY (assigned_by) REFERENCES researchuser(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $conn->query($createReviewersTableQuery);
        }
        
        // Delete any existing reviewers for this research
        $deleteStmt = $conn->prepare("DELETE FROM research_reviewers WHERE research_id = ?");
        $deleteStmt->bind_param("i", $researchId);
        $deleteStmt->execute();
        
        // Insert reviewers - only allow Staff role users
        $reviewerExistsStmt = $conn->prepare("SELECT user_id FROM researchuser WHERE user_id = ? AND role = 'Staff'");
        $insertStmt = $conn->prepare("INSERT INTO research_reviewers (research_id, reviewer_id, assigned_by) VALUES (?, ?, ?)");
        
        foreach ($reviewerIds as $reviewerId) {
            // Verify this is a valid Staff member
            $reviewerExistsStmt->bind_param("i", $reviewerId);
            $reviewerExistsStmt->execute();
            $reviewerExistsResult = $reviewerExistsStmt->get_result();
            
            if ($reviewerExistsResult->num_rows > 0) {
                $insertStmt->bind_param("iii", $researchId, $reviewerId, $adminId);
                $insertStmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        sendResponse(true, 'تم تعيين المراجعين وتحديث حالة البحث بنجاح');
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        sendResponse(false, 'حدث خطأ أثناء تعيين المراجعين: ' . $e->getMessage());
    }
}

// Function to update research status
function updateResearchStatus($researchId, $status, $reason, $adminId, $adminName) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if research exists
        $checkStmt = $conn->prepare("SELECT status FROM submitsresearch WHERE id = ?");
        $checkStmt->bind_param("i", $researchId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if (!$checkResult->fetch_assoc()) {
            $conn->rollback();
            sendResponse(false, 'البحث غير موجود');
            return;
        }
        
        // Set action description based on status
        $action = '';
        
        switch ($status) {
            case 'Approved':
                $action = "تمت الموافقة على البحث من قبل المدير";
                break;
            case 'Rejected':
                $action = "تم رفض البحث من قبل المدير. السبب: " . $reason;
                break;
            case 'Pending 2':
                $action = "تم تغيير حالة البحث إلى 'بانتظار المراجعة النهائية'";
                break;
            default:
                $conn->rollback();
                sendResponse(false, 'حالة غير صالحة');
                return;
        }
        
        // Update research status and admin notes
        $updateStmt = $conn->prepare("UPDATE submitsresearch SET status = ?, admin_notes = ? WHERE id = ?");
        $updateStmt->bind_param("ssi", $status, $reason, $researchId);
        
        if (!$updateStmt->execute()) {
            $conn->rollback();
            sendResponse(false, 'فشل في تحديث حالة البحث: ' . $conn->error);
            return;
        }
        
        // Create research_history table if it doesn't exist
        $createTableQuery = "CREATE TABLE IF NOT EXISTS research_history (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            research_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            action TEXT NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (research_id)
        )";
        
        $conn->query($createTableQuery);
        
        // Create history entry
        $historyQuery = "INSERT INTO research_history (research_id, user_id, user_name, action, timestamp) VALUES (?, ?, ?, ?, NOW())";
        $historyStmt = $conn->prepare($historyQuery);
        $historyStmt->bind_param("iiss", $researchId, $adminId, $adminName, $action);
        $historyStmt->execute();
        
        // If status is 'Approved', the trigger will handle the research creation
        // But we need to ensure the correct research_type is set
        if ($status === 'Approved') {
            // Get the r_type value from submitsresearch
            $rTypeStmt = $conn->prepare("SELECT r_type FROM submitsresearch WHERE id = ?");
            $rTypeStmt->bind_param("i", $researchId);
            $rTypeStmt->execute();
            $rTypeResult = $rTypeStmt->get_result();
            $rTypeRow = $rTypeResult->fetch_assoc();
            
            if ($rTypeRow && $rTypeRow['r_type']) {
                // Capitalize first letter for enum value in research table
                $researchType = ucfirst($rTypeRow['r_type']);
                
                // Update the research_type in the research table after the trigger creates it
                $updateResearchTypeStmt = $conn->prepare("
                    UPDATE research 
                    SET research_type = ? 
                    WHERE research_id = (
                        SELECT MAX(research_id) 
                        FROM research 
                        WHERE title = (
                            SELECT title 
                            FROM submitsresearch 
                            WHERE id = ?
                        )
                    )
                ");
                $updateResearchTypeStmt->bind_param("si", $researchType, $researchId);
                $updateResearchTypeStmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $message = ($status === 'Approved') ? 'تمت الموافقة على البحث بنجاح' : 'تم تحديث حالة البحث بنجاح';
        sendResponse(true, $message);
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        sendResponse(false, 'حدث خطأ أثناء تحديث حالة البحث: ' . $e->getMessage());
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