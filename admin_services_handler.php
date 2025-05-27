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
        
        case 'getServices':
            getServices($adminId);
            break;
        
        case 'getServiceDetails':
            if (isset($_GET['id'])) {
                getServiceDetails($_GET['id'], $adminId);
            } else {
                sendResponse(false, 'معرّف الخدمة مطلوب');
            }
            break;
        
        case 'getStaffList':
            getStaffList();
            break;
        
        default:
            sendResponse(false, 'إجراء غير معروف');
            break;
    }
}

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'assignStaff':
            if (isset($_POST['service_id']) && isset($_POST['staff_id'])) {
                assignStaff($_POST['service_id'], $_POST['staff_id'], $adminId, $adminName);
            } else {
                sendResponse(false, 'معرّف الخدمة ومعرّف الموظف مطلوبان');
            }
            break;
        
        case 'updateStatus':
            if (isset($_POST['service_id']) && isset($_POST['status_action'])) {
                $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
                updateServiceStatus($_POST['service_id'], $_POST['status_action'], $reason, $adminId, $adminName);
            } else {
                sendResponse(false, 'معرّف الخدمة والإجراء مطلوبان');
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

// Function to get services with pagination and filters
function getServices($adminId) {
    global $conn;
    
    try {
        // Get current page
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = 10; // Services per page
        $offset = ($page - 1) * $per_page;
        
        // Define base query
        $query = "SELECT * FROM servicetemp";
        $countQuery = "SELECT COUNT(*) as total FROM servicetemp";
        
        // Add filters
        $whereClause = [];
        $params = [];
        $types = "";
        
        // Status filter
        if (isset($_GET['status']) && $_GET['status'] !== 'all') {
            $whereClause[] = "status = ?";
            $params[] = $_GET['status'];
            $types .= "s";
        }
        
        // Service type filter
        if (isset($_GET['service_type']) && $_GET['service_type'] !== 'all') {
            $whereClause[] = "service_type = ?";
            $params[] = $_GET['service_type'];
            $types .= "s";
        }
        
        // Search filter
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $whereClause[] = "(user_name LIKE ? OR service_type LIKE ?)";
            $searchTerm = "%" . $_GET['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }
        
        // Build final query
        if (!empty($whereClause)) {
            $query .= " WHERE " . implode(" AND ", $whereClause);
            $countQuery .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        // Add order and limit
        $query .= " ORDER BY created_at DESC LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $per_page;
        $types .= "ii";
        
        // Get total count
        $countStmt = $conn->prepare($countQuery);
        if (!empty($params) && count($params) > 0) {
            // Remove the last two parameters (offset and limit) for count query
            $countParams = array_slice($params, 0, -2);
            $countTypes = substr($types, 0, -2);
            
            if (!empty($countParams)) {
                $countStmt->bind_param($countTypes, ...$countParams);
            }
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRow = $countResult->fetch_assoc();
        $total = $totalRow['total'];
        
        // Calculate pagination info
        $total_pages = ceil($total / $per_page);
        
        // Get services
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            // Check if files exist
            $row['has_files'] = !empty($row['files']);
            
            // Don't send file contents in list view
            unset($row['files']);
            
            $services[] = $row;
        }
        
        sendResponse(true, 'تم الحصول على بيانات الخدمات بنجاح', [
            'services' => $services,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages
            ]
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات الخدمات: ' . $e->getMessage());
    }
}

// Function to get service details
function getServiceDetails($serviceId, $adminId) {
    global $conn;
    
    try {
        // First, check if the service exists and get basic info
        $stmt = $conn->prepare("SELECT s.*, ru.name as assigned_staff_name 
                               FROM servicetemp s 
                               LEFT JOIN researchuser ru ON s.assigned_staff_id = ru.user_id 
                               WHERE s.request_id = ?");
        $stmt->bind_param("i", $serviceId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Check if files exist
            $row['has_files'] = !empty($row['files']);
            
            // Get status history from service_history table if exists
            // First check if the table exists
            $tableExistsStmt = $conn->prepare("SHOW TABLES LIKE 'service_history'");
            $tableExistsStmt->execute();
            $tableExistsResult = $tableExistsStmt->get_result();
            
            $history = [];
            
            if ($tableExistsResult->num_rows > 0) {
                $historyStmt = $conn->prepare("SELECT * FROM service_history WHERE service_id = ? ORDER BY timestamp DESC");
                $historyStmt->bind_param("i", $serviceId);
                $historyStmt->execute();
                $historyResult = $historyStmt->get_result();
                
                while ($historyRow = $historyResult->fetch_assoc()) {
                    $history[] = $historyRow;
                }
            }
            
            $row['status_history'] = $history;
            
            sendResponse(true, 'تم الحصول على بيانات الخدمة بنجاح', [
                'service' => $row
            ]);
        } else {
            sendResponse(false, 'الخدمة غير موجودة');
        }
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات الخدمة: ' . $e->getMessage());
    }
}

// Function to get staff list
function getStaffList() {
    global $conn;
    
    try {
        // Check if specialty column exists in researchuser table
        $columnsStmt = $conn->prepare("SHOW COLUMNS FROM researchuser LIKE 'specialty'");
        $columnsStmt->execute();
        $columnsResult = $columnsStmt->get_result();
        
        if ($columnsResult->num_rows == 0) {
            // Specialty column doesn't exist, so don't include it in the query
            $stmt = $conn->prepare("SELECT user_id, username, name, role FROM researchuser WHERE role = 'Staff'");
        } else {
            // Specialty column exists, include it in the query
            $stmt = $conn->prepare("SELECT user_id, username, name, role, specialty FROM researchuser WHERE role = 'Staff'");
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $staff = [];
        while ($row = $result->fetch_assoc()) {
            // If specialty doesn't exist, add an empty value
            if (!isset($row['specialty'])) {
                $row['specialty'] = '';
            }
            
            $staff[] = $row;
        }
        
        sendResponse(true, 'تم الحصول على قائمة الموظفين بنجاح', [
            'staff' => $staff
        ]);
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع قائمة الموظفين: ' . $e->getMessage());
    }
}

// Function to assign staff to a service
function assignStaff($serviceId, $staffId, $adminId, $adminName) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if service exists and is in wait 1 status
        $checkStmt = $conn->prepare("SELECT status FROM servicetemp WHERE request_id = ?");
        $checkStmt->bind_param("i", $serviceId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($row = $checkResult->fetch_assoc()) {
            if ($row['status'] !== 'wait 1') {
                $conn->rollback();
                sendResponse(false, 'لا يمكن تعيين موظف لهذه الخدمة في حالتها الحالية');
                return;
            }
        } else {
            $conn->rollback();
            sendResponse(false, 'الخدمة غير موجودة');
            return;
        }
        
        // Verify that staff exists and is a staff user
        $staffCheckStmt = $conn->prepare("SELECT name FROM researchuser WHERE user_id = ? AND role = 'Staff'");
        $staffCheckStmt->bind_param("i", $staffId);
        $staffCheckStmt->execute();
        $staffCheckResult = $staffCheckStmt->get_result();
        
        if (!$staffCheckResult->fetch_assoc()) {
            $conn->rollback();
            sendResponse(false, 'الموظف غير موجود أو ليس لديه دور موظف');
            return;
        }
        
        // Check if the assigned_staff_id column exists
        $columnsStmt = $conn->prepare("SHOW COLUMNS FROM servicetemp LIKE 'assigned_staff_id'");
        $columnsStmt->execute();
        $columnsResult = $columnsStmt->get_result();
        
        if ($columnsResult->num_rows == 0) {
            // Column doesn't exist, add it
            $conn->query("ALTER TABLE servicetemp ADD COLUMN assigned_staff_id int(11) NULL DEFAULT NULL AFTER status");
        }
        
        // Update service status to wait 2 and set assigned staff
        $updateStmt = $conn->prepare("UPDATE servicetemp SET status = 'wait 2', assigned_staff_id = ? WHERE request_id = ?");
        $updateStmt->bind_param("ii", $staffId, $serviceId);
        
        if (!$updateStmt->execute()) {
            $conn->rollback();
            sendResponse(false, 'فشل في تحديث حالة الخدمة: ' . $conn->error);
            return;
        }
        
        // Create service_history table if it doesn't exist
        $createTableQuery = "CREATE TABLE IF NOT EXISTS service_history (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            service_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            action TEXT NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (service_id)
        )";
        
        $conn->query($createTableQuery);
        
        // Create history entry
        $historyQuery = "INSERT INTO service_history (service_id, user_id, user_name, action, timestamp) VALUES (?, ?, ?, ?, NOW())";
        $action = "تم تعيين موظف للخدمة وتغيير الحالة إلى 'بانتظار موافقة مقدم الخدمة'";
        $historyStmt = $conn->prepare($historyQuery);
        $historyStmt->bind_param("iiss", $serviceId, $adminId, $adminName, $action);
        $historyStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        sendResponse(true, 'تم تعيين الموظف وتحديث حالة الخدمة بنجاح');
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        sendResponse(false, 'حدث خطأ أثناء تعيين الموظف: ' . $e->getMessage());
    }
}

// Function to update service status
function updateServiceStatus($serviceId, $statusAction, $reason, $adminId, $adminName) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if service exists and is in wait 1 status
        $checkStmt = $conn->prepare("SELECT status FROM servicetemp WHERE request_id = ?");
        $checkStmt->bind_param("i", $serviceId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($row = $checkResult->fetch_assoc()) {
            if ($row['status'] !== 'wait 1') {
                $conn->rollback();
                sendResponse(false, 'لا يمكن تحديث حالة هذه الخدمة في حالتها الحالية');
                return;
            }
        } else {
            $conn->rollback();
            sendResponse(false, 'الخدمة غير موجودة');
            return;
        }
        
        // Set new status based on action
        $newStatus = '';
        $action = '';
        
        if ($statusAction === 'approve') {
            $newStatus = 'wait 2';
            $action = "تمت الموافقة على الخدمة من قبل المدير";
        } elseif ($statusAction === 'reject') {
            $newStatus = 'rejected';
            $action = "تم رفض الخدمة من قبل المدير. السبب: " . $reason;
        } else {
            $conn->rollback();
            sendResponse(false, 'إجراء غير صالح');
            return;
        }
        
        // Check if admin_notes column exists
        $columnsStmt = $conn->prepare("SHOW COLUMNS FROM servicetemp LIKE 'admin_notes'");
        $columnsStmt->execute();
        $columnsResult = $columnsStmt->get_result();
        
        if ($columnsResult->num_rows == 0) {
            // Column doesn't exist, add it
            $conn->query("ALTER TABLE servicetemp ADD COLUMN admin_notes text NULL DEFAULT NULL AFTER assigned_staff_id");
        }
        
        // Update service status and admin notes
        $updateStmt = $conn->prepare("UPDATE servicetemp SET status = ?, admin_notes = ? WHERE request_id = ?");
        $updateStmt->bind_param("ssi", $newStatus, $reason, $serviceId);
        
        if (!$updateStmt->execute()) {
            $conn->rollback();
            sendResponse(false, 'فشل في تحديث حالة الخدمة: ' . $conn->error);
            return;
        }
        
        // Create service_history table if it doesn't exist
        $createTableQuery = "CREATE TABLE IF NOT EXISTS service_history (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            service_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            action TEXT NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (service_id)
        )";
        
        $conn->query($createTableQuery);
        
        // Create history entry
        $historyQuery = "INSERT INTO service_history (service_id, user_id, user_name, action, timestamp) VALUES (?, ?, ?, ?, NOW())";
        $historyStmt = $conn->prepare($historyQuery);
        $historyStmt->bind_param("iiss", $serviceId, $adminId, $adminName, $action);
        $historyStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $message = ($statusAction === 'approve') ? 'تمت الموافقة على الخدمة بنجاح' : 'تم رفض الخدمة بنجاح';
        sendResponse(true, $message);
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        sendResponse(false, 'حدث خطأ أثناء تحديث حالة الخدمة: ' . $e->getMessage());
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