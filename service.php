<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ]);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "gp"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error
    ]);
    exit;
}

// Get user information from session
$username = $_SESSION['username'];

// Get user details from researchuser table
$stmt = $conn->prepare("SELECT username, name,user_id FROM researchuser WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'لم يتم العثور على بيانات المستخدم'
    ]);
    exit;
}

$user = $result->fetch_assoc();
$user_id = $user['user_id'];
$user_name = $user['username'];
$name = $user['name'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get service type
    $service_type = $_POST['service_type'];
    
    // For inquiry service
    if ($service_type === 'inquiry') {
        $inquiry_text = $_POST['inquiry_text'];
        
        // Insert into askmessages table
        $stmt = $conn->prepare("INSERT INTO askmessages (message_id, user_id, user_name, message_text) VALUES (NULL, ?, ?, ?)");
        $stmt->bind_param("sss", $user_id, $user_name, $inquiry_text);
        
        if ($stmt->execute()) {
            $message_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'request_id' => $message_id,
                'message' => 'تم إرسال استفسارك بنجاح'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'فشل في إرسال الاستفسار: ' . $stmt->error
            ]);
        }
        
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // For other services
    // Get the service name for "other" service
    $service_name = $service_type;
    if ($service_type === 'other' && isset($_POST['other_service'])) {
        $service_name = $_POST['other_service'];
    } elseif ($service_type === 'statistics') {
        $service_name = 'إحصاء تحليلي';
    } elseif ($service_type === 'translation') {
        $service_name = 'خدمة الترجمة';
    } elseif ($service_type === 'proofreading') {
        $service_name = 'تدقيق لغوي';
    } elseif ($service_type === 'booking') {
        $service_name = 'حجز مباشر';
    }
    
    // Get notes
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Handle file uploads
    $uploaded_files = [];
    $upload_dir = 'uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['files'])) {
        $file_count = count($_FILES['files']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['files']['tmp_name'][$i];
                $name = $_FILES['files']['name'][$i];
                
                // Generate unique filename
                $filename = uniqid() . '_' . $name;
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $uploaded_files[] = $file_path;
                }
            }
        }
    }
    
    // Convert uploaded files array to string
    $files_json = json_encode($uploaded_files);
    
    // Insert service request into servicetemp table
    $stmt = $conn->prepare("INSERT INTO servicetemp ( user_id, user_name, service_type, files, notes) VALUES ( ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $user_id, $user_name, $service_name, $files_json, $notes);
    
    if ($stmt->execute()) {
        $request_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'request_id' => $request_id,
            'message' => 'تم تقديم طلبك بنجاح'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'فشل في تقديم الطلب: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'طريقة الطلب غير صالحة'
    ]);
}

$conn->close();
?>