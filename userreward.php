<?php
// Start session to access user data
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.html");
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
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Initialize response variables
$success = false;
$message = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get researcher ID from session
    $researcher_id = $_SESSION['user_id'];
    
    // Get form data
    $research_title = trim($_POST['researchTitle']);
    $journal_name = trim($_POST['journalName']);
    $research_link = trim($_POST['researchLink']);
    $publish_date = $_POST['publishDate'];
    $journal_category = $_POST['journalCategory'];
    $journal_classification = isset($_POST['journalClassification']) ? implode(", ", $_POST['journalClassification']) : "";
    $user_comments = isset($_POST['userComments']) ? trim($_POST['userComments']) : "";
    $signature_data = isset($_POST['signatureData']) ? $_POST['signatureData'] : "";
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // First check if research exists by title
        $stmt = $conn->prepare("SELECT research_id FROM research WHERE title = :title");
        $stmt->bindParam(':title', $research_title);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Research exists, get its ID
            $research_row = $stmt->fetch(PDO::FETCH_ASSOC);
            $research_id = $research_row['research_id'];
        } else {
            // Research doesn't exist, insert new research
            $stmt = $conn->prepare("INSERT INTO research (title, journal_name, research_link, publish_date, journal_category, journal_classification, researcher_id) 
                                    VALUES (:title, :journal_name, :research_link, :publish_date, :journal_category, :journal_classification, :researcher_id)");
            
            $stmt->bindParam(':title', $research_title);
            $stmt->bindParam(':journal_name', $journal_name);
            $stmt->bindParam(':research_link', $research_link);
            $stmt->bindParam(':publish_date', $publish_date);
            $stmt->bindParam(':journal_category', $journal_category);
            $stmt->bindParam(':journal_classification', $journal_classification);
            $stmt->bindParam(':researcher_id', $researcher_id);
            
            $stmt->execute();
            $research_id = $conn->lastInsertId();
        }
        
        // Initialize files blob
        $files_blob = [];
        
        // Process uploaded files
        $file_keys = [
            'rewardFormFile' => 'نموذج طلب المكافأة',
            'collegeApprovalFile' => 'موافقة مجلس الكلية',
            'scopusProofFile' => 'إثبات Scopus',
            'wosProofFile' => 'إثبات Web of Science',
            'journalRankingFile' => 'إثبات تصنيف المجلة'
        ];
        
        foreach ($file_keys as $key => $description) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
                $file_tmp = $_FILES[$key]['tmp_name'];
                $file_name = $_FILES[$key]['name'];
                $file_type = $_FILES[$key]['type'];
                $file_content = file_get_contents($file_tmp);
                
                $files_blob[] = [
                    'name' => $file_name,
                    'type' => $file_type,
                    'description' => $description,
                    'content' => base64_encode($file_content)
                ];
            }
        }
        
        // Add signature if provided
        if (!empty($signature_data)) {
            $files_blob[] = [
                'name' => 'signature.png',
                'type' => 'image/png',
                'description' => 'توقيع الباحث',
                'content' => str_replace('data:image/png;base64,', '', $signature_data)
            ];
        }
        
        // Serialize files blob
        $files_json = json_encode($files_blob);
        
        // Insert reward request
        $stmt = $conn->prepare("INSERT INTO reward (cost, approved_admin, approved_vp_academic, approved_president, user_comments, research_id, files, resercher_id, admins_lastComments) 
                                VALUES (0.00, 'No', 'No', 'No', :user_comments, :research_id, :files, :researcher_id, '')");
        
        $stmt->bindParam(':user_comments', $user_comments);
        $stmt->bindParam(':research_id', $research_id);
        $stmt->bindParam(':files', $files_json);
        $stmt->bindParam(':researcher_id', $researcher_id);
        
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $success = true;
        $message = "تم إرسال طلب المكافأة بنجاح! سيتم مراجعة الطلب من قبل الإدارة.";
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $message = "حدث خطأ أثناء معالجة الطلب: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتيجة طلب المكافأة</title>
    <link rel="stylesheet" href="style.css">
    <style>
    .result-container {
        text-align: center;
        padding: 30px;
        margin: 50px auto;
        max-width: 600px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .success-message {
        color: #2e7d32;
        font-size: 18px;
        margin-bottom: 20px;
    }

    .error-message {
        color: #c62828;
        font-size: 18px;
        margin-bottom: 20px;
    }

    .button-container {
        margin-top: 30px;
    }

    .button {
        display: inline-block;
        padding: 10px 20px;
        background-color: #003366;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 500;
        margin: 0 10px;
    }

    .button:hover {
        background-color: #00264d;
    }
    </style>
</head>

<body>
    <div class="result-container">
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <?php if ($success): ?>
        <h1>تم تقديم الطلب</h1>
        <div class="success-message"><?php echo $message; ?></div>
        <?php else: ?>
        <h1>خطأ في تقديم الطلب</h1>
        <div class="error-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php else: ?>
        <h1>لا يمكن الوصول مباشرة</h1>
        <div class="error-message">يجب تقديم النموذج أولاً</div>
        <?php endif; ?>

        <div class="button-container">
            <a href="userreward.html" class="button">العودة إلى نموذج الطلب</a>
            <a href="dashboard.php" class="button">الذهاب إلى لوحة التحكم</a>
        </div>
    </div>
</body>

</html>