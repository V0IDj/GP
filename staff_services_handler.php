<?php
// Start or resume session
session_start();

// Include database configuration
require_once 'config.php';

// Check if user is logged in and is a staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Staff') {
    sendResponse(false, 'غير مصرح بالوصول. يجب أن تكون موظفاً للوصول إلى هذه الصفحة.');
    exit;
}

// Get staff ID from session
$staffId = $_SESSION['user_id'];
$staffName = $_SESSION['username'] ?? 'الموظف';

// Process GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'getStaffInfo':
            getStaffInfo($staffId, $staffName);
            break;
        
        case 'getServices':
            getServices($staffId);
            break;
        
        case 'getServiceDetails':
            if (isset($_GET['id'])) {
                getServiceDetails($_GET['id'], $staffId);
            } else {
                sendResponse(false, 'معرّف الخدمة مطلوب');
            }
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
        case 'updateStatus':
            if (isset($_POST['service_id']) && isset($_POST['status_action'])) {
                $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
                updateServiceStatus($_POST['service_id'], $_POST['status_action'], $reason, $staffId, $staffName);
            } else {
                sendResponse(false, 'معرّف الخدمة والإجراء مطلوبان');
            }
            break;
        
        case 'completeService':
            if (isset($_POST['service_id']) && isset($_FILES['files'])) {
                $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
                completeService($_POST['service_id'], $_FILES['files'], $notes, $staffId, $staffName);
            } else {
                sendResponse(false, 'معرّف الخدمة والملفات مطلوبة');
            }
            break;
            
        case 'revertApproval':
            if (isset($_POST['service_id'])) {
                revertApproval($_POST['service_id'], $staffId, $staffName);
            } else {
                sendResponse(false, 'معرّف الخدمة مطلوب');
            }
            break;
        
        default:
            sendResponse(false, 'إجراء غير معروف');
            break;
    }
}

// Function to get staff information
function getStaffInfo($staffId, $staffName) {
    global $conn;
    
    try {
        // Get staff details from database
        $stmt = $conn->prepare("SELECT name FROM researchuser WHERE user_id = ? AND role = 'Staff'");
        $stmt->bind_param("i", $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            sendResponse(true, 'تم الحصول على بيانات الموظف بنجاح', [
                'staffName' => $row['name']
            ]);
        } else {
            // Fallback to session username
            sendResponse(true, 'تم الحصول على بيانات الموظف بنجاح', [
                'staffName' => $staffName
            ]);
        }
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات الموظف: ' . $e->getMessage());
    }
}

// Function to get services assigned to staff
function getServices($staffId) {
    global $conn;
    
    try {
        // Get current page
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = 10; // Services per page
        $offset = ($page - 1) * $per_page;
        
        // Define base query - only get services assigned to this staff
        $query = "SELECT *, (completed_files IS NOT NULL) as has_completed_files FROM servicetemp WHERE assigned_staff_id = ?";
        $countQuery = "SELECT COUNT(*) as total FROM servicetemp WHERE assigned_staff_id = ?";
        
        // Add filters
        $whereClause = [];
        $params = [$staffId]; // Initial parameter is staff ID
        $types = "i"; // Initial type is integer for staff ID
        
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
            $query .= " AND " . implode(" AND ", $whereClause);
            $countQuery .= " AND " . implode(" AND ", $whereClause);
        }
        
        // Add order and limit
        $query .= " ORDER BY created_at DESC LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $per_page;
        $types .= "ii";
        
        // Get total count
        $countStmt = $conn->prepare($countQuery);
        if (!empty($params)) {
            // Remove the last two parameters (offset and limit) for count query
            $countParams = array_slice($params, 0, -2);
            $countTypes = substr($types, 0, -2);
            
            $countStmt->bind_param($countTypes, ...$countParams);
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
function getServiceDetails($serviceId, $staffId) {
    global $conn;
    
    try {
        // Get service details - ensure it's assigned to this staff
        $stmt = $conn->prepare("SELECT * FROM servicetemp WHERE request_id = ? AND assigned_staff_id = ?");
        $stmt->bind_param("ii", $serviceId, $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Check if files exist
            $row['has_files'] = !empty($row['files']);
            
            // Get status history from service_history table if exists
            $historyStmt = $conn->prepare("SELECT * FROM service_history WHERE service_id = ? ORDER BY timestamp DESC");
            $historyStmt->bind_param("i", $serviceId);
            $historyStmt->execute();
            $historyResult = $historyStmt->get_result();
            
            $history = [];
            while ($historyRow = $historyResult->fetch_assoc()) {
                $history[] = $historyRow;
            }
            
            $row['status_history'] = $history;
            
            sendResponse(true, 'تم الحصول على بيانات الخدمة بنجاح', [
                'service' => $row
            ]);
        } else {
            sendResponse(false, 'الخدمة غير موجودة أو غير معينة لك');
        }
    } catch (Exception $e) {
        sendResponse(false, 'حدث خطأ أثناء استرجاع بيانات الخدمة: ' . $e->getMessage());
    }
}

// Function to update service status
function updateServiceStatus($serviceId, $statusAction, $reason, $staffId, $staffName) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if service exists and is assigned to this staff and has status wait 2
        $checkStmt = $conn->prepare("SELECT status FROM servicetemp WHERE request_id = ? AND assigned_staff_id = ?");
        $checkStmt->bind_param("ii", $serviceId, $staffId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($row = $checkResult->fetch_assoc()) {
            if ($row['status'] !== 'wait 2') {
                $conn->rollback();
                sendResponse(false, 'لا يمكن تحديث حالة هذه الخدمة في حالتها الحالية');
                return;
            }
        } else {
            $conn->rollback();
            sendResponse(false, 'الخدمة غير موجودة أو غير معينة لك');
            return;
        }
        
        // Set new status based on action
        $newStatus = '';
        $action = '';
        
        if ($statusAction === 'approve') {
            $newStatus = 'approved';
            $action = "تمت الموافقة على الخدمة من قبل مقدم الخدمة";
        } elseif ($statusAction === 'reject') {
            $newStatus = 'rejected';
            $action = "تم رفض الخدمة من قبل مقدم الخدمة. السبب: " . $reason;
        } else {
            $conn->rollback();
            sendResponse(false, 'إجراء غير صالح');
            return;
        }
        
        // Update service status
        $updateStmt = $conn->prepare("UPDATE servicetemp SET status = ?, staff_notes = ? WHERE request_id = ?");
        $updateStmt->bind_param("ssi", $newStatus, $reason, $serviceId);
        
        if (!$updateStmt->execute()) {
            $conn->rollback();
            sendResponse(false, 'فشل في تحديث حالة الخدمة: ' . $conn->error);
            return;
        }
        
        // Create entry in service_history table if it exists
        addServiceHistory($conn, $serviceId, $staffId, $staffName, $action);
        
        // Commit transaction
        $conn->commit();
        
        $message = ($statusAction === 'approve') ? 'تمت الموافقة على الخدمة بنجاح. يرجى إكمال الخدمة الآن.' : 'تم رفض الخدمة بنجاح';
        sendResponse(true, $message);
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        sendResponse(false, 'حدث خطأ أثناء تحديث حالة الخدمة: ' . $e->getMessage());
    }
}

// Function to revert approval status back to wait_2
function revertApproval($serviceId, $staffId, $staffName) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if service exists, is assigned to this staff, and has status 'approved'
        $checkStmt = $conn->prepare("SELECT status, completed_files FROM servicetemp WHERE request_id = ? AND assigned_staff_id = ?");
        $checkStmt->bind_param("ii", $serviceId, $staffId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($row = $checkResult->fetch_assoc()) {
            if ($row['status'] !== 'approved') {
                $conn->rollback();
                sendResponse(false, 'لا يمكن التراجع عن قبول هذه الخدمة لأنها ليست في حالة "تمت الموافقة"');
                return;
            }
            
            if (!empty($row['completed_files'])) {
                $conn->rollback();
                sendResponse(false, 'لا يمكن التراجع عن قبول هذه الخدمة لأنها تم إكمالها بالفعل');
                return;
            }
        } else {
            $conn->rollback();
            sendResponse(false, 'الخدمة غير موجودة أو غير معينة لك');
            return;
        }
        
        // Update service status back to 'wait 2'
        $updateStmt = $conn->prepare("UPDATE servicetemp SET status = 'wait 2' WHERE request_id = ?");
        $updateStmt->bind_param("i", $serviceId);
        
        if (!$updateStmt->execute()) {
            $conn->rollback();
            sendResponse(false, 'فشل في تحديث حالة الخدمة: ' . $conn->error);
            return;
        }
        
        // Add history entry
        $action = "تم التراجع عن قبول الخدمة من قبل مقدم الخدمة.";
        addServiceHistory($conn, $serviceId, $staffId, $staffName, $action);
        
        // Commit transaction
        $conn->commit();
        
        sendResponse(true, 'تم التراجع عن قبول الخدمة بنجاح');
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        sendResponse(false, 'حدث خطأ أثناء التراجع عن قبول الخدمة: ' . $e->getMessage());
    }
}

// Function to complete service
function completeService($serviceId, $files, $notes, $staffId, $staffName) {
    global $conn;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if service exists and is assigned to this staff and has approved status
        $checkStmt = $conn->prepare("SELECT status FROM servicetemp WHERE request_id = ? AND assigned_staff_id = ?");
        $checkStmt->bind_param("ii", $serviceId, $staffId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($row = $checkResult->fetch_assoc()) {
            if ($row['status'] !== 'approved') {
                $conn->rollback();
                sendResponse(false, 'لا يمكن إكمال هذه الخدمة لأنها ليست في حالة "تمت الموافقة"');
                return;
            }
        } else {
            $conn->rollback();
            sendResponse(false, 'الخدمة غير موجودة أو غير معينة لك');
            return;
        }
        
        // Handle file uploads
        $uploadedFiles = [];
        $upload_dir = 'completed_uploads/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                
                // Generate unique filename
                $filename = uniqid() . '_' . $name;
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $uploadedFiles[] = $file_path;
                }
            }
        }
        
        if (empty($uploadedFiles)) {
            $conn->rollback();
            sendResponse(false, 'فشل في رفع الملفات');
            return;
        }
        
        // Convert uploaded files array to string
        $files_json = json_encode($uploadedFiles);
        
        // Update service with completed files and staff notes
        $updateStmt = $conn->prepare("UPDATE servicetemp SET staff_notes = ?, completed_files = ? WHERE request_id = ?");
        $updateStmt->bind_param("ssi", $notes, $files_json, $serviceId);
        
        if (!$updateStmt->execute()) {
            $conn->rollback();
            sendResponse(false, 'فشل في تحديث حالة الخدمة: ' . $conn->error);
            return;
        }
        
        // Create entry in service_history table if it exists
        $action = "تم إكمال الخدمة بنجاح من قبل مقدم الخدمة";
        addServiceHistory($conn, $serviceId, $staffId, $staffName, $action);
        
        // Commit transaction
        $conn->commit();
        
        sendResponse(true, 'تم إكمال الخدمة بنجاح');
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        sendResponse(false, 'حدث خطأ أثناء إكمال الخدمة: ' . $e->getMessage());
    }
}

// Helper function to add service history
function addServiceHistory($conn, $serviceId, $userId, $userName, $action) {
    $historyQuery = "INSERT INTO service_history (service_id, user_id, user_name, action, timestamp) VALUES (?, ?, ?, ?, NOW())";
    
    // Check if service_history table exists
    $tableExistsStmt = $conn->prepare("SHOW TABLES LIKE 'service_history'");
    $tableExistsStmt->execute();
    $tableExistsResult = $tableExistsStmt->get_result();
    
    if ($tableExistsResult->num_rows > 0) {
        $historyStmt = $conn->prepare($historyQuery);
        $historyStmt->bind_param("iiss", $serviceId, $userId, $userName, $action);
        $historyStmt->execute();
    } else {
        // Create the service_history table
        $createTableQuery = "CREATE TABLE service_history (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            service_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            action TEXT NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (service_id)
        )";
        
        $conn->query($createTableQuery);
        
        // Insert the history record
        $historyStmt = $conn->prepare($historyQuery);
        $historyStmt->bind_param("iiss", $serviceId, $userId, $userName, $action);
        $historyStmt->execute();
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