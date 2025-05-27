<?php
// share_research.php - لنقل البحث من نظام التقديم إلى نظام المشاركة

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
session_start();

// للتصحيح - تسجيل الخطوات والمعلومات
$debug = [];
$debug[] = "بدء العملية";

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً لمشاركة البحث.']));
}

$userId = $_SESSION['user_id'];
$debug[] = "معرف المستخدم: $userId";

// التأكد من وجود معرف البحث
if (!isset($_POST['research_id']) || empty($_POST['research_id'])) {
    die(json_encode(['success' => false, 'message' => 'معرف البحث مطلوب']));
}

$researchId = intval($_POST['research_id']);
$debug[] = "معرف البحث: $researchId";

// تهيئة الاتصال بقاعدة البيانات
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'gp';

// إنشاء اتصال جديد
$conn = new mysqli($host, $username, $password, $database);

// التحقق من الاتصال
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]));
}

// التأكد من ضبط الترميز المناسب
$conn->set_charset("utf8mb4");
$debug[] = "تم الاتصال بقاعدة البيانات";

// التحقق مما إذا كان المستخدم هو مالك البحث
$checkOwnerQuery = "SELECT * FROM submitsresearch WHERE id = ? AND user_id = ?";
$checkOwnerStmt = $conn->prepare($checkOwnerQuery);
$checkOwnerStmt->bind_param("ii", $researchId, $userId);
$checkOwnerStmt->execute();
$checkOwnerResult = $checkOwnerStmt->get_result();

if ($checkOwnerResult->num_rows === 0) {
    $checkOwnerStmt->close();
    $conn->close();
    die(json_encode(['success' => false, 'message' => 'غير مسموح لك بمشاركة هذا البحث، أنت لست المالك.']));
}

$researchData = $checkOwnerResult->fetch_assoc();
$debug[] = "المستخدم هو مالك البحث - عنوان البحث: " . $researchData['title'];
$debug[] = "حالة البحث: " . $researchData['status'];
$debug[] = "حالة المشاركة: " . ($researchData['is_shared'] ? "تمت المشاركة بالفعل" : "لم تتم المشاركة");
$checkOwnerStmt->close();

// تحديث حالة المشاركة
$updateShareStatusQuery = "SELECT * FROM submitsresearch WHERE id = ? AND is_shared = 1";
$updateShareStatusStmt = $conn->prepare($updateShareStatusQuery);
$updateShareStatusStmt->bind_param("i", $researchId);
$updateShareStatusStmt->execute();
$updateShareStatusResult = $updateShareStatusStmt->get_result();

// إذا كان البحث قد تمت مشاركته من قبل في جدول submitsresearch
if ($updateShareStatusResult->num_rows > 0) {
    $updateShareStatusStmt->close();
    $debug[] = "البحث تمت مشاركته بالفعل حسب جدول submitsresearch";
    
    // تحقق إذا كان موجود بالفعل في جدول researchparticipation
    $checkResearchParticipationQuery = "SELECT * FROM researchparticipation WHERE submits_research_id = ?";
    $checkResearchParticipationStmt = $conn->prepare($checkResearchParticipationQuery);
    $checkResearchParticipationStmt->bind_param("i", $researchId);
    $checkResearchParticipationStmt->execute();
    $checkResearchParticipationResult = $checkResearchParticipationStmt->get_result();
    
    if ($checkResearchParticipationResult->num_rows > 0) {
        $checkResearchParticipationStmt->close();
        $debug[] = "البحث موجود بالفعل في جدول researchparticipation";
        $conn->close();
        die(json_encode([
            'success' => false, 
            'message' => 'تمت مشاركة هذا البحث بالفعل.',
            'debug' => $debug
        ]));
    }
    
    $checkResearchParticipationStmt->close();
    $debug[] = "البحث غير موجود في جدول researchparticipation رغم أن حالته is_shared = 1";
    
    // في هذه الحالة، نقوم بإعادة تعيين حالة المشاركة في submitsresearch
    $resetShareStatusQuery = "UPDATE submitsresearch SET is_shared = 0 WHERE id = ?";
    $resetShareStatusStmt = $conn->prepare($resetShareStatusQuery);
    $resetShareStatusStmt->bind_param("i", $researchId);
    $resetShareStatusStmt->execute();
    $resetShareStatusStmt->close();
    $debug[] = "تم إعادة تعيين حالة المشاركة في جدول submitsresearch";
}
$updateShareStatusStmt->close();

// التحقق مرة أخرى مما إذا كان البحث موجود في جدول المشاركة
$checkExistingQuery = "SELECT * FROM researchparticipation WHERE submits_research_id = ?";
$checkExistingStmt = $conn->prepare($checkExistingQuery);
$checkExistingStmt->bind_param("i", $researchId);
$checkExistingStmt->execute();
$checkExistingResult = $checkExistingStmt->get_result();

if ($checkExistingResult->num_rows > 0) {
    $checkExistingStmt->close();
    $debug[] = "البحث موجود بالفعل في جدول researchparticipation";
    $conn->close();
    die(json_encode([
        'success' => false, 
        'message' => 'تمت مشاركة هذا البحث بالفعل.',
        'debug' => $debug
    ]));
}

$checkExistingStmt->close();
$debug[] = "البحث غير موجود في جدول researchparticipation، جاري المتابعة";

// إدراج البحث في جدول المشاركة
$insertQuery = "
    INSERT INTO researchparticipation (
        title, 
        participate1, 
        category, 
        classification, 
        status,
        submits_research_id
    ) VALUES (?, ?, ?, ?, 'approved', ?)
";

$insertStmt = $conn->prepare($insertQuery);

// تحديد التصنيف بناءً على قيمة classification و r_type في جدول submitsresearch
$category = $researchData['r_type'] ?? 'theoretical'; // practical أو theoretical
$classification = $researchData['classification'] ?? 'Q4'; // Q1, Q2, Q3, Q4

$insertStmt->bind_param("sissi", 
    $researchData['title'], 
    $userId, 
    $category, 
    $classification, 
    $researchId
);

$insertResult = $insertStmt->execute();
$debug[] = "محاولة إدراج البحث في جدول researchparticipation: " . ($insertResult ? "نجاح" : "فشل");

if (!$insertResult) {
    $error = $conn->error;
    $insertStmt->close();
    $conn->close();
    die(json_encode([
        'success' => false, 
        'message' => 'حدث خطأ أثناء مشاركة البحث: ' . $error,
        'debug' => $debug
    ]));
}

// الحصول على معرف البحث المشترك الجديد
$newResearchId = $conn->insert_id;
$insertStmt->close();
$debug[] = "تم إدراج البحث بنجاح، معرف البحث الجديد: $newResearchId";

// تحديث حالة البحث في جدول التقديم للإشارة إلى أنه تمت مشاركته
$updateSubmitQuery = "UPDATE submitsresearch SET is_shared = 1 WHERE id = ?";
$updateSubmitStmt = $conn->prepare($updateSubmitQuery);
$updateSubmitStmt->bind_param("i", $researchId);
$updateSubmitResult = $updateSubmitStmt->execute();
$debug[] = "تحديث حالة المشاركة في جدول submitsresearch: " . ($updateSubmitResult ? "نجاح" : "فشل");
$updateSubmitStmt->close();

// إغلاق الاتصال
$conn->close();

// إعادة النجاح
header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'message' => 'تمت مشاركة البحث بنجاح وهو متاح الآن للمشاركة.',
    'research_id' => $newResearchId,
    'debug' => $debug
]);
?>