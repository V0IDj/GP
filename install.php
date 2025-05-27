<?php
// Script to update the database and fix the issues

// Check for admin access
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die('غير مصرح بالوصول. يجب أن تكون مديراً للوصول إلى هذه الصفحة.');
}

// Include database configuration
require_once 'config.php';

// Flag to track if any error occurred
$error_occurred = false;

// Output buffer for messages
$output = '';

// Function to add a message to the output
function addMessage($message, $isError = false) {
    global $output, $error_occurred;
    if ($isError) {
        $error_occurred = true;
        $output .= '<div style="color: red; margin-bottom: 10px;"><strong>خطأ:</strong> ' . $message . '</div>';
    } else {
        $output .= '<div style="color: green; margin-bottom: 10px;"><strong>نجاح:</strong> ' . $message . '</div>';
    }
}

// Check connection
if ($conn->connect_error) {
    addMessage('فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error, true);
} else {
    addMessage('تم الاتصال بقاعدة البيانات بنجاح');
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Check and add assigned_staff_id column to servicetemp table
        $columnsStmt = $conn->prepare("SHOW COLUMNS FROM servicetemp LIKE 'assigned_staff_id'");
        $columnsStmt->execute();
        $columnsResult = $columnsStmt->get_result();
        
        if ($columnsResult->num_rows == 0) {
            // Column doesn't exist, add it
            $conn->query("ALTER TABLE servicetemp ADD COLUMN assigned_staff_id int(11) NULL DEFAULT NULL AFTER status");
            addMessage('تم إضافة عمود assigned_staff_id إلى جدول servicetemp');
        } else {
            addMessage('عمود assigned_staff_id موجود بالفعل في جدول servicetemp');
        }
        
        // 2. Check and add admin_notes column to servicetemp table
        $columnsStmt = $conn->prepare("SHOW COLUMNS FROM servicetemp LIKE 'admin_notes'");
        $columnsStmt->execute();
        $columnsResult = $columnsStmt->get_result();
        
        if ($columnsResult->num_rows == 0) {
            // Column doesn't exist, add it
            $conn->query("ALTER TABLE servicetemp ADD COLUMN admin_notes text NULL DEFAULT NULL AFTER assigned_staff_id");
            addMessage('تم إضافة عمود admin_notes إلى جدول servicetemp');
        } else {
            addMessage('عمود admin_notes موجود بالفعل في جدول servicetemp');
        }
        
        // 3. Check and add staff_notes column to servicetemp table
        $columnsStmt = $conn->prepare("SHOW COLUMNS FROM servicetemp LIKE 'staff_notes'");
        $columnsStmt->execute();
        $columnsResult = $columnsStmt->get_result();
        
        if ($columnsResult->num_rows == 0) {
            // Column doesn't exist, add it
            $conn->query("ALTER TABLE servicetemp ADD COLUMN staff_notes text NULL DEFAULT NULL AFTER admin_notes");
            addMessage('تم إضافة عمود staff_notes إلى جدول servicetemp');
        } else {
            addMessage('عمود staff_notes موجود بالفعل في جدول servicetemp');
        }
        
        // 4. Check and add completed_files column to servicetemp table
        $columnsStmt = $conn->prepare("SHOW COLUMNS FROM servicetemp LIKE 'completed_files'");
        $columnsStmt->execute();
        $columnsResult = $columnsStmt->get_result();
        
        if ($columnsResult->num_rows == 0) {
            // Column doesn't exist, add it
            $conn->query("ALTER TABLE servicetemp ADD COLUMN completed_files longblob NULL DEFAULT NULL AFTER staff_notes");
            addMessage('تم إضافة عمود completed_files إلى جدول servicetemp');
        } else {
            addMessage('عمود completed_files موجود بالفعل في جدول servicetemp');
        }
        
        // 5. Create service_history table if it doesn't exist
        $tableExistsStmt = $conn->prepare("SHOW TABLES LIKE 'service_history'");
        $tableExistsStmt->execute();
        $tableExistsResult = $tableExistsStmt->get_result();
        
        if ($tableExistsResult->num_rows == 0) {
            // Table doesn't exist, create it
            $createTableQuery = "CREATE TABLE service_history (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                service_id INT(11) NOT NULL,
                user_id INT(11) NOT NULL,
                user_name VARCHAR(255) NOT NULL,
                action TEXT NOT NULL,
                timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (service_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $conn->query($createTableQuery);
            addMessage('تم إنشاء جدول service_history بنجاح');
        } else {
            addMessage('جدول service_history موجود بالفعل');
        }
        
        // 6. Check and add specialty column to researchuser table
        $columnsStmt = $conn->prepare("SHOW COLUMNS FROM researchuser LIKE 'specialty'");
        $columnsStmt->execute();
        $columnsResult = $columnsStmt->get_result();
        
        if ($columnsResult->num_rows == 0) {
            // Column doesn't exist, add it
            $conn->query("ALTER TABLE researchuser ADD COLUMN specialty varchar(255) NULL DEFAULT NULL AFTER major");
            addMessage('تم إضافة عمود specialty إلى جدول researchuser');
        } else {
            addMessage('عمود specialty موجود بالفعل في جدول researchuser');
        }
        
        // 7. Add foreign key if it doesn't exist
        $foreignKeyStmt = $conn->prepare("SELECT * FROM information_schema.TABLE_CONSTRAINTS 
                                         WHERE CONSTRAINT_SCHEMA = DATABASE() 
                                         AND CONSTRAINT_NAME = 'fk_staff_user' 
                                         AND TABLE_NAME = 'servicetemp'");
        $foreignKeyStmt->execute();
        $foreignKeyResult = $foreignKeyStmt->get_result();
        
        if ($foreignKeyResult->num_rows == 0) {
            // Check if the index exists first
            $indexStmt = $conn->prepare("SHOW INDEX FROM servicetemp WHERE KEY_NAME = 'assigned_staff_id'");
            $indexStmt->execute();
            $indexResult = $indexStmt->get_result();
            
            if ($indexResult->num_rows == 0) {
                // Create index first
                $conn->query("ALTER TABLE servicetemp ADD INDEX (assigned_staff_id)");
                addMessage('تم إضافة الفهرس على عمود assigned_staff_id');
            }
            
            // Add foreign key
            try {
                $conn->query("ALTER TABLE servicetemp ADD CONSTRAINT fk_staff_user 
                             FOREIGN KEY (assigned_staff_id) REFERENCES researchuser(user_id) 
                             ON DELETE SET NULL ON UPDATE CASCADE");
                addMessage('تم إضافة العلاقة بين جدول servicetemp وجدول researchuser');
            } catch (Exception $e) {
                // If foreign key addition fails, it's not critical
                addMessage('لم يتم إضافة العلاقة بين الجداول: ' . $e->getMessage(), false);
            }
        } else {
            addMessage('العلاقة بين جدول servicetemp وجدول researchuser موجودة بالفعل');
        }
        
        // 8. Create uploads directories if they don't exist
        $uploadDirs = ['uploads', 'completed_uploads'];
        
        foreach ($uploadDirs as $dir) {
            if (!file_exists($dir)) {
                if (mkdir($dir, 0777, true)) {
                    addMessage('تم إنشاء مجلد ' . $dir . ' بنجاح');
                } else {
                    addMessage('فشل في إنشاء مجلد ' . $dir, true);
                }
            } else {
                addMessage('مجلد ' . $dir . ' موجود بالفعل');
            }
        }
        
        // Commit transaction if no errors
        if (!$error_occurred) {
            $conn->commit();
            addMessage('تم تنفيذ جميع التغييرات بنجاح');
        } else {
            $conn->rollback();
            addMessage('تم التراجع عن التغييرات بسبب حدوث أخطاء', true);
        }
    } catch (Exception $e) {
        $conn->rollback();
        addMessage('حدث خطأ أثناء تنفيذ التغييرات: ' . $e->getMessage(), true);
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث قاعدة البيانات</title>
    <style>
        body {
            font-family: 'Tajawal', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
            direction: rtl;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #3498db;
            margin-bottom: 20px;
            text-align: center;
        }
        .message-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            background-color: #f5f5f5;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .back-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>تحديث قاعدة البيانات</h1>
        
        <div class="message-container">
            <?php echo $output; ?>
        </div>
        
        <a href="admin_services.html" class="back-btn">العودة إلى صفحة إدارة الخدمات</a>
    </div>
</body>
</html>